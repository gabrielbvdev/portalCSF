<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/sincronizacao.php';

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

try {
    $resumo = sincronizarDocumentosDoIvertex((int) $_SESSION['usuario_id']);
} catch (Throwable $erro) {
    error_log('Falha ao atualizar faturas: ' . $erro->getMessage());
    responder(500, ['sucesso' => false, 'mensagem' => 'Não foi possível atualizar agora. Tente novamente.']);
}

if ($resumo['falhas'] > 0) {
    responder(502, ['sucesso' => false, 'mensagem' => 'Não foi possível consultar o iVertex agora. Tente novamente em instantes.']);
}

responder(200, ['sucesso' => true, 'mensagem' => 'Faturas atualizadas.'] + $resumo);
