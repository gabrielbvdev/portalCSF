<?php

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/sincronizacao.php';
require_once dirname(__DIR__) . '/includes/ivertex.php';

header('Content-Type: application/json; charset=utf-8');

function responderJson(int $codigoHttp, array $dados): void
{
    http_response_code($codigoHttp);
    echo json_encode($dados);
    exit;
}

function iniciarSessaoDoUsuario(array $usuario, bool $precisaTrocarSenha): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome_completo'];
    $_SESSION['usuario_cpf'] = $usuario['cpf'];
    $_SESSION['usuario_status'] = $usuario['status'];
    $_SESSION['usuario_perfil'] = 'cliente';
    $_SESSION['forcar_troca_senha'] = $precisaTrocarSenha;
}

function usuarioPrecisaTrocarSenha(array $usuario): bool
{
    return (int) ($usuario['deve_trocar_senha'] ?? 1) === 1 || trim((string) ($usuario['senha_hash'] ?? '')) === '';
}

function buscarClientePorCpf(string $cpf): ?array
{
    $linhas = dbQuery('SELECT * FROM adm_clientes WHERE cpf = :cpf LIMIT 1', ['cpf' => $cpf]);
    if ($linhas === false) {
        throw new RuntimeException('Falha ao consultar o cliente no banco de dados.');
    }

    return $linhas[0] ?? null;
}

function salvarClienteDoIvertex(array $dados, string $senhaInicial): ?int
{
    $cpf = normalizarCpf((string) ($dados['cpf'] ?? ''));
    if ($cpf === '') {
        return null;
    }

    $existente = buscarClientePorCpf($cpf);
    if ($existente) {
        $hashExistente = (string) ($existente['senha_hash'] ?? '');

        dbQuery(
            'UPDATE adm_clientes SET nome_completo = :nome_completo, email = :email, telefone = :telefone, data_nascimento = :data_nascimento, id_ivertex = :id_ivertex, senha_hash = :senha_hash, deve_trocar_senha = :deve_trocar_senha, ultimo_acesso = NOW() WHERE cpf = :cpf',
            [
                'nome_completo' => $dados['nome_completo'] ?? $existente['nome_completo'],
                'email' => $dados['email'] ?? $existente['email'],
                'telefone' => $dados['telefone'] ?? $existente['telefone'],
                'data_nascimento' => $dados['data_nascimento'] ?? $existente['data_nascimento'],
                'id_ivertex' => $dados['id_ivertex'] ?? $existente['id_ivertex'],
                'senha_hash' => $hashExistente !== '' ? $hashExistente : password_hash($senhaInicial, PASSWORD_DEFAULT),
                'deve_trocar_senha' => usuarioPrecisaTrocarSenha($existente) ? 1 : 0,
                'cpf' => $cpf,
            ]
        );

        return (int) $existente['id'];
    }

    $gravou = dbQuery(
        'INSERT INTO adm_clientes (id_ivertex, cpf, nome_completo, email, telefone, data_nascimento, senha_hash, deve_trocar_senha, status, plano_atual, ultimo_acesso) VALUES (:id_ivertex, :cpf, :nome_completo, :email, :telefone, :data_nascimento, :senha_hash, :deve_trocar_senha, :status, :plano_atual, NOW())',
        [
            'id_ivertex' => $dados['id_ivertex'] ?? null,
            'cpf' => $cpf,
            'nome_completo' => $dados['nome_completo'] ?? 'Cliente',
            'email' => $dados['email'] ?? null,
            'telefone' => $dados['telefone'] ?? null,
            'data_nascimento' => $dados['data_nascimento'] ?? null,
            'senha_hash' => password_hash($senhaInicial, PASSWORD_DEFAULT),
            'deve_trocar_senha' => 1,
            'status' => 'ativo',
            'plano_atual' => $dados['plano_atual'] ?? null,
        ]
    );

    if (!$gravou) {
        return null;
    }

    $cliente = buscarClientePorCpf($cpf);

    return $cliente ? (int) $cliente['id'] : null;
}

function garantirProduto(string $nome): int
{
    $rotulo = trim($nome) !== '' ? trim($nome) : 'Plano familiar';

    $produtos = dbQuery('SELECT id FROM vitrine_produtos WHERE nome_produto = :nome_produto LIMIT 1', ['nome_produto' => $rotulo]);
    if (!empty($produtos)) {
        return (int) $produtos[0]['id'];
    }

    dbQuery(
        'INSERT INTO vitrine_produtos (nome_produto, tipo_produto, descricao, preco, ativo) VALUES (:nome_produto, :tipo_produto, :descricao, :preco, :ativo)',
        [
            'nome_produto' => $rotulo,
            'tipo_produto' => 'plano_familiar',
            'descricao' => 'Tipo de contrato obtido do iVertex.',
            'preco' => 0.00,
            'ativo' => 1,
        ]
    );

    $produtos = dbQuery('SELECT id FROM vitrine_produtos WHERE nome_produto = :nome_produto LIMIT 1', ['nome_produto' => $rotulo]);
    if (empty($produtos)) {
        throw new RuntimeException('Não foi possível cadastrar o produto "' . $rotulo . '".');
    }

    return (int) $produtos[0]['id'];
}

function mapearStatusContrato(string $status): string
{
    $status = strtolower(trim($status));
    if (str_contains($status, 'cancel')) {
        return 'cancelado';
    }
    if (str_contains($status, 'suspens') || str_contains($status, 'inadimpl')) {
        return 'suspenso';
    }

    return 'ativo';
}

function sincronizarContratosDoIvertex(int $idCliente, string $cpf): void
{
    $contratos = buscarContratosNoIvertex($cpf);
    if ($contratos === []) {
        error_log('sincronizarContratosDoIvertex: nenhum contrato retornado para o CPF ' . $cpf);
        return;
    }

    foreach ($contratos as $contrato) {
        $idContrato = trim((string) ($contrato['Id'] ?? ''));
        if ($idContrato === '') {
            continue;
        }

        $referencia = trim((string) ($contrato['Referencia'] ?? ''));
        $parametros = [
            'referencia' => $referencia !== '' ? $referencia : null,
            'produto' => garantirProduto((string) ($contrato['NomeTipoContrato'] ?? '')),
            'status' => mapearStatusContrato((string) ($contrato['Status'] ?? 'ATIVO')),
            'data' => (string) ($contrato['DataContrato'] ?? date('Y-m-d')),
        ];

        $existentes = dbQuery(
            'SELECT id FROM adm_clientes_produtos WHERE id_cliente = :id_cliente AND codigo_contrato_ivertex = :codigo LIMIT 1',
            ['id_cliente' => $idCliente, 'codigo' => $idContrato]
        );

        if (!empty($existentes)) {
            dbQuery(
                'UPDATE adm_clientes_produtos SET referencia_contrato_ivertex = :referencia, id_vitrine_produto = :produto, status = :status, data_contratacao = :data WHERE id = :id',
                $parametros + ['id' => (int) $existentes[0]['id']]
            );
            continue;
        }

        dbQuery(
            'INSERT INTO adm_clientes_produtos (codigo_contrato_ivertex, referencia_contrato_ivertex, id_cliente, id_vitrine_produto, status, data_contratacao) VALUES (:codigo, :referencia, :id_cliente, :produto, :status, :data)',
            $parametros + ['codigo' => $idContrato, 'id_cliente' => $idCliente]
        );
    }
}

function sincronizarDependentesDoIvertex(int $idCliente, string $cpf): void
{
    $cpfNormalizado = normalizarCpf($cpf);
    $dependentes = buscarDependentesNoIvertex($cpf);
    if ($dependentes === []) {
        error_log('sincronizarDependentesDoIvertex: nenhum dependente retornado para o CPF ' . $cpf);
        return;
    }

    $titular = buscarClientePorCpf($cpfNormalizado);
    $nomeTitular = strtolower(trim((string) ($titular['nome_completo'] ?? '')));

    foreach ($dependentes as $dependente) {
        $nome = (string) (
            $dependente['nome_completo']
            ?? $dependente['nome']
            ?? $dependente['Name']
            ?? $dependente['name']
            ?? $dependente['NomeCompleto']
            ?? $dependente['Nome']
            ?? ''
        );

        $cpfDependente = normalizarCpf((string) (
            $dependente['cpf']
            ?? $dependente['CPF']
            ?? $dependente['document']
            ?? $dependente['documento']
            ?? $dependente['inscricao_ativa']
            ?? $dependente['InscricaoAtiva']
            ?? ''
        ));

        if ($nome === '' || $cpfDependente === $cpfNormalizado) {
            continue;
        }

        $nomeDependente = strtolower(trim($nome));
        if ($nomeTitular !== '' && $nomeDependente === $nomeTitular) {
            continue;
        }

        $existentes = dbQuery(
            'SELECT id FROM adm_dependentes WHERE id_cliente = :id_cliente AND cpf <=> :cpf LIMIT 1',
            ['id_cliente' => $idCliente, 'cpf' => $cpfDependente !== '' ? $cpfDependente : null]
        );

        if (!empty($existentes)) {
            continue;
        }

        dbQuery(
            'INSERT INTO adm_dependentes (id_cliente, id_ivertex, nome, cpf, data_nascimento, parentesco, status) VALUES (:id_cliente, :id_ivertex, :nome, :cpf, :data_nascimento, :parentesco, :status)',
            [
                'id_cliente' => $idCliente,
                'id_ivertex' => $dependente['id'] ?? $dependente['id_ivertex'] ?? $dependente['Id'] ?? $dependente['beneficiary_id'] ?? null,
                'nome' => $nome,
                'cpf' => $cpfDependente !== '' ? $cpfDependente : null,
                'data_nascimento' => $dependente['data_nascimento'] ?? $dependente['DataNascimento'] ?? $dependente['birthdate'] ?? $dependente['birth_date'] ?? null,
                'parentesco' => $dependente['parentesco'] ?? $dependente['Parentesco'] ?? $dependente['relationship'] ?? $dependente['relacao'] ?? null,
                'status' => 'ativo',
            ]
        );
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
        responderJson(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    }

    header('Location: ' . APP_URL_BASE . '/login/');
    exit;
}

$identificador = strtolower(trim((string) ($_POST['cpf'] ?? '')));
$senha = trim((string) ($_POST['senha'] ?? ''));

if ($identificador === '' || $senha === '') {
    responderJson(400, ['sucesso' => false, 'mensagem' => 'CPF e senha são obrigatórios.']);
}

$cpf = normalizarCpf($identificador);
$senhaPrimeiroAcesso = $cpf !== '' ? $cpf : $senha;

try {
    $usuario = buscarClientePorCpf($cpf);

    if ($usuario) {
        $precisaTrocarSenha = usuarioPrecisaTrocarSenha($usuario);
        $senhaValida = password_verify($senha, $usuario['senha_hash']) || ($precisaTrocarSenha && $senha === $senhaPrimeiroAcesso);
        if (!$senhaValida) {
            responderJson(401, ['sucesso' => false, 'mensagem' => 'CPF ou senha inválidos.']);
        }

        iniciarSessaoDoUsuario($usuario, $precisaTrocarSenha);

        responderJson(200, [
            'sucesso' => true,
            'redirecionar' => APP_URL_BASE . '/dashboard/',
            'mensagem' => $precisaTrocarSenha ? 'Primeiro acesso identificado. Defina sua nova senha.' : 'Login realizado com sucesso.',
        ]);
    }

    $clienteIvertex = buscarClienteNoIvertex($cpf);
    if ($clienteIvertex) {
        $idCliente = salvarClienteDoIvertex($clienteIvertex, $senhaPrimeiroAcesso);
        if ($idCliente) {
            $sincronizacoes = [
                'contratos' => fn () => sincronizarContratosDoIvertex($idCliente, $cpf),
                'documentos' => fn () => sincronizarDocumentosDoIvertex($idCliente),
                'dependentes' => fn () => sincronizarDependentesDoIvertex($idCliente, $cpf),
            ];

            foreach ($sincronizacoes as $nome => $sincronizar) {
                try {
                    $sincronizar();
                } catch (Throwable $erro) {
                    error_log('Falha na sincronização de ' . $nome . ': ' . $erro->getMessage());
                }
            }

            $usuario = buscarClientePorCpf($cpf);

            if ($usuario && $senha === $senhaPrimeiroAcesso) {
                $precisaTrocarSenha = usuarioPrecisaTrocarSenha($usuario);
                iniciarSessaoDoUsuario($usuario, $precisaTrocarSenha);

                responderJson(200, [
                    'sucesso' => true,
                    'redirecionar' => APP_URL_BASE . '/dashboard/',
                    'mensagem' => $precisaTrocarSenha ? 'Primeiro acesso identificado. Defina sua nova senha.' : 'Cadastro sincronizado e login realizado com sucesso.',
                ]);
            }
        }
    }

    responderJson(401, ['sucesso' => false, 'mensagem' => 'CPF ou senha inválidos.']);
} catch (Throwable $erro) {
    error_log('Erro no login: ' . $erro->getMessage());
    responderJson(500, ['sucesso' => false, 'mensagem' => 'Não foi possível concluir o login no momento. Verifique a conexão com o banco de dados.']);
}
