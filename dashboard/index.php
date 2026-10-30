<?php
require_once dirname(__DIR__) . '/includes/framework.php';
require_once dirname(__DIR__) . '/includes/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . APP_URL_BASE . '/login/');
    exit;
}

$idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Cliente';
$cpfUsuario = $_SESSION['usuario_cpf'] ?? '---';

$usuarios = dbQuery('SELECT deve_trocar_senha, senha_hash FROM adm_clientes WHERE id = :id LIMIT 1', ['id' => $idUsuario]);
$usuario = $usuarios[0] ?? null;
$exigeTrocaSenha = !empty($_SESSION['forcar_troca_senha'])
    || ($usuario !== null && ((int) $usuario['deve_trocar_senha'] === 1 || trim((string) $usuario['senha_hash']) === ''));

$contratos = dbQuery('SELECT cp.*, vp.nome_produto, vp.tipo_produto FROM adm_clientes_produtos cp LEFT JOIN vitrine_produtos vp ON vp.id = cp.id_vitrine_produto WHERE cp.id_cliente = :id_cliente ORDER BY cp.data_contratacao DESC LIMIT 1', ['id_cliente' => $idUsuario]);
$contrato = $contratos[0] ?? null;

$pagamentos = dbQuery('SELECT * FROM adm_pagamentos WHERE id_cliente = :id_cliente ORDER BY data_vencimento ASC', ['id_cliente' => $idUsuario]) ?: [];

$pagamentosPendentes = 0;
$proximoVencimento = null;
foreach ($pagamentos as $pagamento) {
    $status = trim((string) ($pagamento['status_pagamento'] ?? ''));
    if (in_array($status, ['pendente', 'Em Atraso', 'A Receber'], true)) {
        $pagamentosPendentes++;
    }

    $vencimento = trim((string) ($pagamento['data_vencimento'] ?? ''));
    if ($vencimento !== '' && ($proximoVencimento === null || $vencimento < $proximoVencimento)) {
        $proximoVencimento = $vencimento;
    }
}

$consultaSaldo = dbQuery('SELECT saldo_moedas FROM gamificacao_saldo WHERE id_cliente = :id_cliente ORDER BY data_atualizacao DESC LIMIT 1', ['id_cliente' => $idUsuario]);
$saldoAtual = (int) ($consultaSaldo[0]['saldo_moedas'] ?? 0);

$nomePlano = trim((string) ($contrato['nome_produto'] ?? 'Plano não identificado'));
$statusContrato = trim((string) ($contrato['status'] ?? 'ativo')) ?: 'ativo';
$rotuloProximoVencimento = $proximoVencimento ? date('d/m/Y', strtotime($proximoVencimento)) : 'Sem vencimento';

$pagina = new Pagina('Painel', 'bg-marca-clara min-h-screen text-gray-700');

if ($exigeTrocaSenha) {
    $formularioTrocaSenha = formulario(
        'formTrocaSenha',
        campo(nome: 'nova_senha', rotulo: 'Nova senha', tipo: 'password', placeholder: 'Digite a sua nova senha', minimo: 6, id: 'novaSenha')
        . campo(nome: 'confirmar_senha', rotulo: 'Confirmar senha', tipo: 'password', placeholder: 'Confirme a nova senha', minimo: 6, id: 'confirmarSenha')
        . alerta('mensagemTrocaSenha')
        . '<div class="flex flex-col gap-3 pt-2 sm:flex-row sm:justify-between">'
        . botao('Salvar nova senha', tipo: 'submit', id: 'botaoSalvarSenha')
        . botao('Sair do sistema', 'secundario', href: APP_URL_BASE . '/sair/')
        . '</div>',
        'space-y-4'
    );

    $pagina->html(modal(
        id: 'modalTrocaSenha',
        titulo: 'Troca de senha obrigatória',
        etiqueta: 'Atenção',
        conteudo: $formularioTrocaSenha,
        fechavel: false,
        aberto: true,
        tamanho: 'lg'
    ));

    $pagina->js(<<<'JS'
            $('#formTrocaSenha').on('submit', function (evento) {
                evento.preventDefault();

                const novaSenha = $('#novaSenha').val();
                const confirmarSenha = $('#confirmarSenha').val();

                if (novaSenha.length < 6) {
                    mostrarAlerta('#mensagemTrocaSenha', 'A nova senha deve ter pelo menos 6 caracteres.');
                    return;
                }

                if (novaSenha !== confirmarSenha) {
                    mostrarAlerta('#mensagemTrocaSenha', 'A confirmação da senha não confere.');
                    return;
                }

                ocultarAlerta('#mensagemTrocaSenha');

                postarJson(URL_BASE + '/dashboard/trocar_senha.php', { nova_senha: novaSenha, confirmar_senha: confirmarSenha })
                    .done(function () {
                        window.location.reload();
                    })
                    .fail(function (mensagem) {
                        mostrarAlerta('#mensagemTrocaSenha', mensagem);
                    });
            });
JS);
}

$nomeEscapado = escapar($nomeUsuario);

$pagina->html(cabecalhoPortal($nomeUsuario, $cpfUsuario));

$pagina->html(<<<HTML
    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="mb-8 rounded-3xl bg-gradient-to-r from-marca-primaria to-marca-secundaria p-8 text-white shadow-lg shadow-marca-primaria/20">
            <p class="text-sm uppercase tracking-[0.25em] text-white/70">Painel do cliente</p>
            <h2 class="mt-3 text-3xl font-bold">Bem-vindo, {$nomeEscapado}.</h2>
            <p class="mt-2 max-w-xl text-sm text-white/80">Seu ecossistema digital de pós-venda está pronto para acompanhar planos, pagamentos, dependentes e benefícios.</p>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
HTML);

$pagina->html(cartaoIndicador('Plano atual', $nomePlano ?: 'Plano não identificado', 'Status: ' . strtolower($statusContrato)));
$pagina->html(cartaoIndicador(
    'Pagamentos',
    $pagamentosPendentes . ' pendente' . ($pagamentosPendentes === 1 ? '' : 's'),
    'Próximo vencimento: ' . $rotuloProximoVencimento
));
$pagina->html(cartaoIndicador('Moedas de benefícios', (string) $saldoAtual, 'Saldo disponível no clube'));

$pagina->html('        </div>
    </main>');

$pagina->exibir();
