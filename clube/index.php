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

$consultaSaldo = dbQuery('SELECT saldo_moedas FROM gamificacao_saldo WHERE id_cliente = :id_cliente ORDER BY data_atualizacao DESC LIMIT 1', ['id_cliente' => $idUsuario]);
$saldoAtual = (int) ($consultaSaldo[0]['saldo_moedas'] ?? 0);

// Recompensas fixas por enquanto. Use 'icone' (FontAwesome) ou 'imagem' (URL) para a área de destaque.
$recompensas = [
    ['titulo' => 'Voucher Drogaria S. Paulo', 'descricao' => 'Ganhe 20% de desconto extra na compra de medicamentos genéricos.', 'custo' => 200, 'icone' => 'fa-solid fa-pills'],
    ['titulo' => 'Consulta Santa Izabel', 'descricao' => 'Isenção da coparticipação em uma consulta clínica geral.', 'custo' => 400, 'icone' => 'fa-solid fa-stethoscope'],
];

$cardsRecompensas = '';
foreach ($recompensas as $recompensa) {
    $cardsRecompensas .= cardRecompensa(
        titulo: $recompensa['titulo'],
        descricao: $recompensa['descricao'],
        custo: $recompensa['custo'],
        icone: $recompensa['icone'] ?? '',
        imagem: $recompensa['imagem'] ?? '',
        rodape: botao('Resgatar · em breve', $saldoAtual >= $recompensa['custo'] ? 'primario' : 'secundario', largo: true, desabilitado: true)
    );
}

$cardSaldo = cardSaldoClube($saldoAtual);
$pagina = new Pagina('Clube & Moedas', 'bg-gray-50 text-gray-800');

$conteudo = <<<HTML
<section>
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-gray-800">Clube de Benefícios</h3>
        <p class="text-gray-500">Suas ações valem moedas. Troque por descontos em parceiros!</p>
    </div>

    {$cardSaldo}

    <h4 class="mb-4 text-lg font-bold text-gray-800">Vitrine de Recompensas</h4>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">{$cardsRecompensas}</div>
</section>
HTML;

$pagina->html(layoutPortal('clube', 'Clube de Benefícios', $conteudo, $nomeUsuario, 'Titular - ' . $nomePlano));
$pagina->exibir();
