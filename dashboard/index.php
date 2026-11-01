<?php
require_once dirname(__DIR__) . '/includes/framework.php';
require_once dirname(__DIR__) . '/includes/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . APP_URL_BASE . '/login/');
    exit;
}

$idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Cliente';

$usuarios = dbQuery('SELECT deve_trocar_senha, senha_hash FROM adm_clientes WHERE id = :id LIMIT 1', ['id' => $idUsuario]);
$usuario = $usuarios[0] ?? null;
$exigeTrocaSenha = !empty($_SESSION['forcar_troca_senha'])
    || ($usuario !== null && ((int) $usuario['deve_trocar_senha'] === 1 || trim((string) $usuario['senha_hash']) === ''));

$contratos = dbQuery('SELECT cp.*, vp.nome_produto, vp.tipo_produto FROM adm_clientes_produtos cp LEFT JOIN vitrine_produtos vp ON vp.id = cp.id_vitrine_produto WHERE cp.id_cliente = :id_cliente ORDER BY cp.data_contratacao DESC LIMIT 1', ['id_cliente' => $idUsuario]);
$contrato = $contratos[0] ?? null;

$nomePlano = trim((string) ($contrato['nome_produto'] ?? '')) ?: 'Plano não identificado';
$statusContrato = strtolower(trim((string) ($contrato['status'] ?? 'ativo'))) ?: 'ativo';

$totalDependentes = (int) (dbQuery('SELECT COUNT(*) AS total FROM adm_dependentes WHERE id_cliente = :id_cliente', ['id_cliente' => $idUsuario])[0]['total'] ?? 0);

$pagamentos = dbQuery('SELECT * FROM adm_pagamentos WHERE id_cliente = :id_cliente ORDER BY data_vencimento ASC', ['id_cliente' => $idUsuario]) ?: [];

$proximaFatura = null;
foreach ($pagamentos as $pagamento) {
    $statusPagamento = trim((string) ($pagamento['status_pagamento'] ?? ''));
    $vencimento = trim((string) ($pagamento['data_vencimento'] ?? ''));
    if (in_array($statusPagamento, ['pendente', 'Em Atraso', 'A Receber'], true) && $vencimento !== '') {
        $proximaFatura = $pagamento;
        break;
    }
}

$consultaSaldo = dbQuery('SELECT saldo_moedas FROM gamificacao_saldo WHERE id_cliente = :id_cliente ORDER BY data_atualizacao DESC LIMIT 1', ['id_cliente' => $idUsuario]);
$saldoAtual = (int) ($consultaSaldo[0]['saldo_moedas'] ?? 0);

$pagina = new Pagina('Painel', 'bg-gray-50 text-gray-800');

// Cartão: Meu Plano
$primeiroNome = escapar(mb_convert_case(explode(' ', trim($nomeUsuario))[0] ?? $nomeUsuario, MB_CASE_TITLE));
$seloStatus = selo(mb_convert_case($statusContrato, MB_CASE_TITLE), $statusContrato === 'ativo' ? 'sucesso' : 'neutro');
$nomePlanoEscapado = escapar($nomePlano);
$textoDependentes = $totalDependentes . ' dependente' . ($totalDependentes === 1 ? '' : 's');

$temDependentes = paginaPortalExiste('dependentes');
$linkDependentes = botao(
    $temDependentes ? 'Ver dependentes' : 'Ver dependentes · em breve',
    'link',
    href: APP_URL_BASE . '/dependentes/',
    iconeDireita: $temDependentes ? 'fa-solid fa-arrow-right' : '',
    desabilitado: !$temDependentes
);

// Cartão: Próxima Fatura
if ($proximaFatura !== null) {
    $valorFatura = escapar('R$ ' . number_format((float) $proximaFatura['valor'], 2, ',', '.'));
    $vencimentoFatura = escapar('Vence em ' . date('d/m/Y', strtotime($proximaFatura['data_vencimento'])));
} else {
    $valorFatura = 'Em dia';
    $vencimentoFatura = 'Nenhuma fatura em aberto';
}

// Cartão: Clube de Vantagens
$saldoFormatado = escapar(number_format($saldoAtual, 0, ',', '.'));

$temClube = paginaPortalExiste('clube');
$linkClube = botao(
    $temClube ? 'Resgatar Prêmios' : 'Resgatar Prêmios · em breve',
    'claro',
    href: APP_URL_BASE . '/clube/',
    largo: true,
    desabilitado: !$temClube
);
$cardPlano = card(
    icone: '<i class="fa-solid fa-house"></i>',
    superior: $seloStatus,
    descricao: '<p class="text-sm font-medium text-gray-500">Meu Plano</p>'
        . '<h4 class="text-xl font-bold text-marca-primaria">' . $nomePlanoEscapado . '</h4>'
        . '<p class="mt-1 text-sm text-gray-500">' . $textoDependentes . '</p>',
    rodape: $linkDependentes
);

$cardFatura = card(
    icone: '<i class="fa-solid fa-file-invoice"></i>',
    superior: '<span class="text-xs font-bold text-gray-500">' . $vencimentoFatura . '</span>',
    descricao: '<p class="text-sm font-medium text-gray-500">Próxima Fatura</p>'
        . '<h4 class="text-2xl font-bold text-gray-800">' . $valorFatura . '</h4>',
    rodape: $proximaFatura !== null
        ? botaoPagarPix((int) $proximaFatura['id'], largo: true)
        : botao('Nenhuma fatura em aberto', largo: true, desabilitado: true),
    corIcone: 'marca-destaque',
    barra: true
);

$cardClube = card(
    icone: '<i class="fa-solid fa-star"></i>',
    descricao: '<p class="text-sm font-medium text-green-100">Saldo Clube de Vantagens</p>'
        . '<h4 class="mt-1 text-3xl font-bold text-marca-destaque">' . $saldoFormatado . ' <span class="text-lg font-medium text-white">moedas</span></h4>',
    rodape: $linkClube,
    corFundo: 'marca-primaria',
    corIcone: 'marca-destaque'
);

// Acesso Rápido (ainda sem destino)
$acessosRapidos = [
    ['rotulo' => 'Meus Dados', 'icone' => 'fa-solid fa-user-pen', 'perigo' => false],
    ['rotulo' => 'Meu Contrato', 'icone' => 'fa-solid fa-file-pdf', 'perigo' => false],
    ['rotulo' => 'Cartão Virtual', 'icone' => 'fa-solid fa-id-card', 'perigo' => false],
    ['rotulo' => 'Plantão 24h', 'icone' => 'fa-solid fa-phone-volume', 'perigo' => true],
];

$htmlAcessosRapidos = '';
foreach ($acessosRapidos as $acesso) {
    $htmlAcessosRapidos .= botaoMedio($acesso['rotulo'], $acesso['icone'], $acesso['perigo'] ? 'vermelho' : 'marca-primaria');
}
$conteudo = <<<HTML
<section>
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-gray-800">Olá, {$primeiroNome}!</h3>
        <p class="text-gray-500">Bem-vindo ao seu portal de autosserviço.</p>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
        {$cardPlano}
        {$cardFatura}
        {$cardClube}
    </div>

    <h4 class="mb-4 text-lg font-bold text-gray-800">Acesso Rápido</h4>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">{$htmlAcessosRapidos}</div>
</section>
HTML;

$subtituloUsuario = 'Titular - ' . $nomePlano;

$pagina->html(layoutPortal('inicio', 'Visão Geral', $conteudo, $nomeUsuario, $subtituloUsuario));
$pagina->html(modalPix());

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

$pagina->exibir();
