<?php

function escapar(string|int|float|null $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Converte ['id' => 'x', 'required' => true] em ' id="x" required'. Valores false/null são omitidos.
function montarAtributos(array $atributos): string
{
    $saida = '';
    foreach ($atributos as $nome => $valor) {
        if ($valor === false || $valor === null) {
            continue;
        }
        $saida .= $valor === true ? ' ' . $nome : ' ' . $nome . '="' . escapar($valor) . '"';
    }

    return $saida;
}

function urlImagem(string $arquivo): string
{
    return APP_URL_BASE . '/assets/img/' . $arquivo;
}

function imagem(string $arquivo, string $alt = '', string $classe = ''): string
{
    return '<img' . montarAtributos(['src' => urlImagem($arquivo), 'alt' => $alt, 'class' => $classe]) . '>';
}
