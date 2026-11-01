<?php
require_once dirname(__DIR__) . '/includes/framework.php';
require_once dirname(__DIR__) . '/includes/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . APP_URL_BASE . '/login/');
    exit;
}

if (!empty($_SESSION['forcar_troca_senha'])) {
    header('Location: ' . APP_URL_BASE . '/dashboard/');
    exit;
}

$idUsuario = (int) $_SESSION['usuario_id'];
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Cliente';

$contratos = dbQuery('SELECT vp.nome_produto FROM adm_clientes_produtos cp LEFT JOIN vitrine_produtos vp ON vp.id = cp.id_vitrine_produto WHERE cp.id_cliente = :id ORDER BY cp.data_contratacao DESC LIMIT 1', ['id' => $idUsuario]);
$nomePlano = trim((string) ($contratos[0]['nome_produto'] ?? '')) ?: 'Plano não identificado';

$faturas = dbQuery('SELECT id, descricao_fatura, valor, data_vencimento, data_pagamento, status_pagamento FROM adm_pagamentos WHERE id_cliente = :id', ['id' => $idUsuario]) ?: [];

// Situação visual de cada fatura: pago, aberto, atraso ou cancelado
$hoje = date('Y-m-d');
foreach ($faturas as &$fatura) {
    $status = mb_strtolower(trim((string) $fatura['status_pagamento']));
    if ($status === 'pago') {
        $fatura['situacao'] = 'pago';
    } elseif ($status === 'cancelado') {
        $fatura['situacao'] = 'cancelado';
    } elseif (in_array($status, ['vencido', 'em atraso'], true) || $fatura['data_vencimento'] < $hoje) {
        $fatura['situacao'] = 'atraso';
    } else {
        $fatura['situacao'] = 'aberto';
    }
}
unset($fatura);

// Em aberto e em atraso primeiro (vencimento mais próximo no topo), depois o histórico do mais recente ao mais antigo
usort($faturas, function ($a, $b) {
    $aAberta = in_array($a['situacao'], ['aberto', 'atraso'], true);
    $bAberta = in_array($b['situacao'], ['aberto', 'atraso'], true);
    if ($aAberta !== $bAberta) {
        return $aAberta ? -1 : 1;
    }

    return $aAberta ? strcmp($a['data_vencimento'], $b['data_vencimento']) : strcmp($b['data_vencimento'], $a['data_vencimento']);
});

$visual = [
    'pago' => ['fa-solid fa-check', 'bg-green-100 text-green-600', 'Pago', 'sucesso'],
    'aberto' => ['fa-solid fa-clock', 'bg-yellow-100 text-yellow-600', 'Em Aberto', 'alerta'],
    'atraso' => ['fa-solid fa-triangle-exclamation', 'bg-red-100 text-red-600', 'Em Atraso', 'perigo'],
    'cancelado' => ['fa-solid fa-ban', 'bg-gray-100 text-gray-500', 'Cancelado', 'neutro'],
];

$colunas = [
    ['orientacao' => 'esquerda', 'mobile' => false],
    ['orientacao' => 'esquerda', 'expandir' => true],
    ['orientacao' => 'direita'],
    ['orientacao' => 'centro', 'mobile' => false],
    ['orientacao' => 'direita'],
];

$linhas = [];
foreach ($faturas as $fatura) {
    [$icone, $classeIcone, $rotulo, $corSelo] = $visual[$fatura['situacao']];

    $titulo = escapar(mb_convert_case(trim((string) $fatura['descricao_fatura']), MB_CASE_TITLE));
    $valor = escapar('R$ ' . number_format((float) $fatura['valor'], 2, ',', '.'));

    if ($fatura['situacao'] === 'pago' && !empty($fatura['data_pagamento'])) {
        $detalhe = 'Pago em: ' . date('d/m/Y', strtotime($fatura['data_pagamento']));
    } else {
        $detalhe = 'Vencimento: ' . date('d/m/Y', strtotime($fatura['data_vencimento']));
    }

    $acao = in_array($fatura['situacao'], ['aberto', 'atraso'], true)
        ? botaoPagarPix((int) $fatura['id'])
        : '';

    $linhas[] = [
        '<div class="flex h-10 w-10 items-center sm:h-12 sm:w-12 justify-center rounded-xl text-lg ' . $classeIcone . '"><i class="' . $icone . '"></i></div>',
        '<p class="font-bold text-gray-800">' . $titulo . '</p><p class="text-sm font-normal text-gray-500">' . escapar($detalhe) . '</p>'
            . '<div class="mt-1 sm:hidden">' . selo($rotulo, $corSelo) . '</div>',
        '<p class="font-bold text-gray-800">' . $valor . '</p>',
        selo($rotulo, $corSelo),
        $acao,
    ];
}

$tabelaFaturas = $linhas !== []
    ? tabela($colunas, $linhas)
    : '<p class="px-6 py-10 text-center text-gray-500">Nenhuma fatura encontrada.</p>';
$pagina = new Pagina('Financeiro', 'bg-gray-50 text-gray-800');
$botaoAtualizar = botao('Atualizar', 'secundario', id: 'btn-atualizar', icone: 'fa-solid fa-rotate');

$conteudo = <<<HTML
<section>
    <div class="mb-6 flex items-center justify-between gap-4">
        <h3 class="text-2xl font-bold text-gray-800">Histórico de Faturas</h3>
        {$botaoAtualizar}
    </div>
    <p id="mensagem-atualizar" class="mb-4 hidden rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"></p>
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">{$tabelaFaturas}</div>
</section>
HTML;

$pagina->js(<<<'JS'
$('#btn-atualizar').on('click', function () {
    const $botao = $(this);
    const $icone = $botao.find('i');
    const $mensagem = $('#mensagem-atualizar').addClass('hidden');

    $botao.prop('disabled', true);
    $icone.addClass('fa-spin');

    postarJson('atualizar.php', {})
        .done(function () {
            window.location.reload();
        })
        .fail(function (mensagem) {
            $mensagem.text(mensagem).removeClass('hidden');
            $botao.prop('disabled', false);
            $icone.removeClass('fa-spin');
        });
});
JS);

$pagina->html(layoutPortal('financeiro', 'Financeiro', $conteudo, $nomeUsuario, 'Titular - ' . $nomePlano));
$pagina->html(modalPix());
$pagina->exibir();
