<?php

require_once __DIR__ . '/config.php';

function obterPdo(): PDO
{
    static $conexao = null;

    if ($conexao instanceof PDO) {
        return $conexao;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        BD_HOST,
        BD_PORTA,
        BD_NOME
    );

    $conexao = new PDO(
        $dsn,
        BD_USUARIO,
        BD_SENHA,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $conexao;
}

/**
 * Executa qualquer consulta SQL com prepared statements.
 *
 * SELECT => array de linhas (array de arrays associativos; vazio se nada for encontrado).
 * INSERT/UPDATE/DELETE => true em caso de sucesso.
 * Qualquer falha (inclusive de conexão) é registrada no log e retorna false.
 */
function dbQuery(string $consulta, array $parametros = []): array|bool
{
    try {
        $comando = obterPdo()->prepare($consulta);
        $comando->execute($parametros);

        if ($comando->columnCount() > 0) {
            return $comando->fetchAll();
        }

        return true;
    } catch (PDOException $erro) {
        error_log('dbQuery falhou: ' . $erro->getMessage() . ' | SQL: ' . $consulta);
        return false;
    }
}
