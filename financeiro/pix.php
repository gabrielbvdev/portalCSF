<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/ivertex.php';

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigoHttp, array $dados): void
{
    http_response_code($codigoHttp);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
}

if (!isset($_SESSION['usuario_id']) || !empty($_SESSION['forcar_troca_senha'])) {
    responder(401, ['sucesso' => false, 'mensagem' => 'Sessão expirada. Faça login novamente.']);
}

// A fatura é buscada pelo id local e pelo cliente da sessão, para ninguém gerar Pix de fatura alheia.
$faturas = dbQuery(
    'SELECT id, id_documento_ivertex, status_pagamento FROM adm_pagamentos WHERE id = :id AND id_cliente = :id_cliente LIMIT 1',
    ['id' => (int) ($_POST['id_pagamento'] ?? 0), 'id_cliente' => (int) $_SESSION['usuario_id']]
);
$fatura = $faturas[0] ?? null;

if ($fatura === null || (int) $fatura['id_documento_ivertex'] <= 0) {
    responder(404, ['sucesso' => false, 'mensagem' => 'Fatura não encontrada.']);
}

if (in_array(strtolower((string) $fatura['status_pagamento']), ['pago', 'cancelado'], true)) {
    responder(409, ['sucesso' => false, 'mensagem' => 'Esta fatura não está em aberto.']);
}

$codigo = gerarPixNoIvertex((int) $fatura['id_documento_ivertex']);
if ($codigo === null) {
    responder(502, ['sucesso' => false, 'mensagem' => 'Não foi possível gerar o Pix agora. Tente novamente em instantes.']);
}

dbQuery('UPDATE adm_pagamentos SET codigo_pix = :codigo, metodo_pagamento = :metodo WHERE id = :id', ['codigo' => $codigo, 'metodo' => 'pix', 'id' => (int) $fatura['id']]);

responder(200, ['sucesso' => true, 'codigo' => $codigo]);
