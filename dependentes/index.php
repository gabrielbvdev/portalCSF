<?php
require_once dirname(__DIR__) . '/includes/framework.php';
require_once dirname(__DIR__) . '/includes/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . APP_URL_BASE . '/login/');
    exit;
}

$idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Cliente';

$contratos = dbQuery('SELECT cp.*, vp.nome_produto, vp.tipo_produto FROM adm_clientes_produtos cp LEFT JOIN vitrine_produtos vp ON vp.id = cp.id_vitrine_produto WHERE cp.id_cliente = :id_cliente ORDER BY cp.data_contratacao DESC LIMIT 1', ['id_cliente' => $idUsuario]);
$contrato = $contratos[0] ?? null;

$nomePlano = trim((string) ($contrato['nome_produto'] ?? '')) ?: 'Plano não identificado';
$subtituloUsuario = 'Titular - ' . $nomePlano;

$pagina = new Pagina('Dependentes', 'bg-gray-50 text-gray-800');

$conteudo = '';

$dependentes = dbQuery("SELECT nome, parentesco, data_nascimento, status FROM adm_dependentes WHERE id_cliente = :id_cliente ORDER BY status ASC, data_nascimento ASC", ['id_cliente' => $_SESSION['usuario_id']]);

$titular = [
    'nome' => $_SESSION['usuario_nome'],
    'parentesco' => 'Titular',
    'data_nascimento' => $_SESSION['usuario_data_nascimento'],
    'status' => $_SESSION['usuario_status'],
];
array_unshift($dependentes, $titular);


$colunas = [
    ['orientacao' => 'centro'],
    ['orientacao' => 'esquerda', 'expandir' => true],
    ['orientacao' => 'centro'],
];
$linhas = [];
$contVagasUtilizadas = 0;
foreach ($dependentes as $dependente) {

    $corSelo = 'sucesso';
    if ($dependente['status'] == 'inativo') {
        $corSelo = 'perigo';
    } else if ($dependente['status'] == 'cancelado') {
        $corSelo = 'neutro';
    } else if ($dependente['status'] == 'ativo') {
        $contVagasUtilizadas++;
    }

    $idade = (new DateTime($dependente['data_nascimento']))->diff(new DateTime())->y;

    $linhas[] = [
            seloIniciais($dependente['nome'], $corSelo),
            '<p class="font-bold text-gray-800">' . $dependente['nome'] . '</p><p class="text-sm font-normal text-gray-500">' . $dependente['parentesco'] . ' • ' . $idade . ' anos</p>',
            selo(mb_convert_case($dependente['status'], MB_CASE_TITLE), $corSelo)
    ];
}
$tabelaDependentes = tabela($colunas, $linhas);



$tipoPlano = mb_convert_case($contrato['nome_produto'], MB_CASE_TITLE);
$vagasUtilizadas = selo($contVagasUtilizadas." de 10 Vagas Utilizadas");

$conteudo = <<<HTML
<section>
    <div class="flex flex-col md:flex-row md:items-center justify-between items-start mb-6 gap-4">
        <div>
            <h3 class="text-2xl font-bold text-gray-800">Meus dependentes</h3>
             <p class="text-gray-500">Gerencie as pessoas cobertas pelo seu {$tipoPlano}.</p>
        </div>
        {$vagasUtilizadas}
    </div>
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">{$tabelaDependentes}</div>
</section>
HTML;


$pagina->html(layoutPortal('dependentes', 'Dependentes', $conteudo, $nomeUsuario, $subtituloUsuario));

$pagina->exibir();