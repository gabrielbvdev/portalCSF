<?php

/**
 * Variantes: primario, secundario, largo (ocupa a linha toda), pilula, link.
 * Com $href vira um <a>. Com $carregando o botão ganha spinner (use definirCarregando() no JS).
 */
function botao(
    string $texto,
    string $variante = 'primario',
    string $id = '',
    string $tipo = 'button',
    string $href = '',
    string $icone = '',
    bool $carregando = false,
    string $classe = '',
    array $atributos = []
): string {
    $variantes = [
        'primario' => 'rounded-xl bg-marca-primaria px-5 py-3 text-sm font-bold text-white transition hover:bg-marca-secundaria',
        'secundario' => 'rounded-xl border border-gray-200 bg-white px-5 py-3 text-center text-sm font-bold text-gray-700 transition hover:bg-gray-100',
        'largo' => 'flex w-full items-center justify-center gap-2 rounded-xl bg-marca-primaria px-5 py-3.5 text-sm font-bold text-white transition hover:bg-marca-secundaria disabled:cursor-not-allowed disabled:opacity-60',
        'pilula' => 'rounded-full bg-marca-primaria px-4 py-2 text-sm font-semibold text-white transition hover:bg-marca-secundaria',
        'link' => 'inline-flex items-center gap-2 font-semibold text-marca-primaria hover:underline',
    ];

    $classes = trim(($variantes[$variante] ?? $variantes['primario']) . ' ' . $classe);

    $conteudo = $icone !== '' ? '<i class="' . escapar($icone) . '"></i> ' : '';
    $conteudo .= $carregando
        ? '<span data-texto>' . escapar($texto) . '</span><span data-spinner class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>'
        : escapar($texto);

    if ($href !== '') {
        return '<a' . montarAtributos(['id' => $id ?: null, 'href' => $href, 'class' => $classes] + $atributos) . '>' . $conteudo . '</a>';
    }

    return '<button' . montarAtributos(['type' => $tipo, 'id' => $id ?: null, 'class' => $classes] + $atributos) . '>' . $conteudo . '</button>';
}
