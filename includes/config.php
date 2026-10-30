<?php

function normalizarCpf(string $cpf): string
{
    return preg_replace('/\D/', '', trim($cpf)) ?? '';
}

function carregarEnv(string $caminho): void
{
    if (!is_readable($caminho)) {
        return;
    }

    foreach (file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#' || !str_contains($linha, '=')) {
            continue;
        }

        [$nome, $valor] = explode('=', $linha, 2);
        $nome = trim($nome);
        $valor = trim($valor);

        if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && $valor[-1] === $valor[0]) {
            $valor = substr($valor, 1, -1);
        }

        if (getenv($nome) === false) {
            putenv($nome . '=' . $valor);
        }
    }
}

function obterEnv(string $chave, ?string $padrao = null): ?string
{
    $valor = getenv($chave);

    return $valor === false ? $padrao : $valor;
}

function exigirEnv(string $chave): string
{
    $valor = obterEnv($chave);
    if ($valor === null || $valor === '') {
        throw new RuntimeException('Variável de ambiente obrigatória não definida no .env: ' . $chave);
    }

    return $valor;
}

carregarEnv(dirname(__DIR__) . '/.env');

define('APP_NOME', exigirEnv('APP_NOME'));

define('BD_HOST', exigirEnv('BD_HOST'));
define('BD_PORTA', (int) exigirEnv('BD_PORTA'));
define('BD_NOME', exigirEnv('BD_NOME'));
define('BD_USUARIO', exigirEnv('BD_USUARIO'));
define('BD_SENHA', obterEnv('BD_SENHA', ''));

define('IVERTEX_URL_BASE', exigirEnv('IVERTEX_URL_BASE'));
define('IVERTEX_TOKEN', exigirEnv('IVERTEX_TOKEN'));
define('IVERTEX_CABECALHO_TOKEN', exigirEnv('IVERTEX_CABECALHO_TOKEN'));
define('IVERTEX_ENDPOINT_CLIENTE', exigirEnv('IVERTEX_ENDPOINT_CLIENTE'));
define('IVERTEX_ENDPOINT_CONTRATOS', exigirEnv('IVERTEX_ENDPOINT_CONTRATOS'));
define('IVERTEX_ENDPOINT_DOCUMENTOS', exigirEnv('IVERTEX_ENDPOINT_DOCUMENTOS'));
define('IVERTEX_ENDPOINT_DEPENDENTES', exigirEnv('IVERTEX_ENDPOINT_DEPENDENTES'));
define('IVERTEX_TIMEOUT_SEGUNDOS', (int) exigirEnv('IVERTEX_TIMEOUT_SEGUNDOS'));

define('APP_CAMINHO_BASE', dirname(__DIR__));

// Caminho da pasta do projeto a partir da raiz do servidor (vazio quando o projeto está na raiz, ex.: /portalCSF em XAMPP).
$raizServidor = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: '';
$caminhoProjeto = realpath(APP_CAMINHO_BASE) ?: APP_CAMINHO_BASE;
$prefixoUrl = $raizServidor !== '' && str_starts_with($caminhoProjeto, $raizServidor)
    ? str_replace('\\', '/', substr($caminhoProjeto, strlen($raizServidor)))
    : '';
define('APP_URL_BASE', rtrim($prefixoUrl, '/'));
unset($raizServidor, $caminhoProjeto, $prefixoUrl);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
