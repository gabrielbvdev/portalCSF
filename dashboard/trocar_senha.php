<?php
require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigoHttp, array $dados): void
{
    http_response_code($codigoHttp);
    echo json_encode($dados);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
}

if (!isset($_SESSION['usuario_id'])) {
    responder(401, ['sucesso' => false, 'mensagem' => 'Sessão expirada. Faça login novamente.']);
}

$novaSenha = trim((string) ($_POST['nova_senha'] ?? ''));
$confirmarSenha = trim((string) ($_POST['confirmar_senha'] ?? ''));

if ($novaSenha === '' || $confirmarSenha === '') {
    responder(400, ['sucesso' => false, 'mensagem' => 'Preencha a nova senha e a confirmação.']);
}

if (strlen($novaSenha) < 6) {
    responder(400, ['sucesso' => false, 'mensagem' => 'A nova senha deve ter pelo menos 6 caracteres.']);
}

if ($novaSenha !== $confirmarSenha) {
    responder(400, ['sucesso' => false, 'mensagem' => 'A confirmação da senha não confere.']);
}

$linhas = dbQuery('SELECT id, cpf FROM adm_clientes WHERE id = :id AND cpf = :cpf LIMIT 1', [
    'id' => (int) $_SESSION['usuario_id'],
    'cpf' => (string) ($_SESSION['usuario_cpf'] ?? ''),
]);
if (empty($linhas)) {
    responder(401, ['sucesso' => false, 'mensagem' => 'Usuário não encontrado.']);
}
$usuario = $linhas[0];

if ($novaSenha === normalizarCpf((string) $usuario['cpf'])) {
    responder(400, ['sucesso' => false, 'mensagem' => 'A nova senha não pode ser igual ao CPF.']);
}

$atualizou = dbQuery('UPDATE adm_clientes SET senha_hash = :senha_hash, deve_trocar_senha = 0 WHERE id = :id', [
    'senha_hash' => password_hash($novaSenha, PASSWORD_DEFAULT),
    'id' => (int) $usuario['id'],
]);

if (!$atualizou) {
    responder(500, ['sucesso' => false, 'mensagem' => 'Não foi possível atualizar a senha.']);
}

$_SESSION['forcar_troca_senha'] = false;

responder(200, ['sucesso' => true, 'mensagem' => 'Senha alterada com sucesso.']);
