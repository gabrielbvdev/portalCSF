<?php

/**
 * Variantes (estilo/cor): primario, secundario, pilula, link, claro (translúcido, para fundos escuros).
 * $tamanho: pequeno (padrão), medio, grande. Não se aplica à variante link.
 * $largo ocupa a linha toda e combina com qualquer variante.
 * Com $href vira um <a>. Com $carregando o botão ganha spinner (use definirCarregando() no JS).
 * $iconeDireita coloca o ícone depois do texto. $desabilitado deixa o botão esmaecido e sem ação
 * (com $href vira um <span>).
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
    array $atributos = [],
    string $iconeDireita = '',
    bool $desabilitado = false,
    string $tamanho = 'pequeno',
    bool $largo = false
): string {
    $variantes = [
        'primario' => 'rounded-xl bg-marca-primaria font-bold text-white hover:bg-marca-secundaria',
        'secundario' => 'rounded-xl border border-gray-200 bg-white font-bold text-gray-700 hover:bg-gray-100',
        'pilula' => 'rounded-full bg-marca-primaria font-semibold text-white hover:bg-marca-secundaria',
        'link' => 'inline-flex items-center gap-2 font-semibold text-marca-primaria hover:underline',
        'claro' => 'rounded-xl border border-white/30 bg-white/20 font-bold text-white hover:bg-white/30',
    ];
    $tamanhos = [
        'pequeno' => 'px-4 py-2 text-sm',
        'medio' => 'px-5 py-3 text-sm',
        'grande' => 'px-6 py-3.5 text-base',
    ];

    $variante = isset($variantes[$variante]) ? $variante : 'primario';
    $classes = $variantes[$variante];

    if ($variante !== 'link') {
        $classes .= ' flex items-center justify-center gap-2 text-center transition disabled:cursor-not-allowed disabled:opacity-60 ' . ($tamanhos[$tamanho] ?? $tamanhos['pequeno']);
        $classes .= $largo ? ' w-full' : ' inline-flex w-fit';
    }

    $classes = trim($classes . ' ' . $classe);

    if ($desabilitado) {
        $classes .= ' pointer-events-none cursor-not-allowed opacity-50';
    }
    $conteudo = $icone !== '' ? '<i class="' . escapar($icone) . '"></i> ' : '';
    $conteudo .= $carregando
        ? '<span data-texto>' . escapar($texto) . '</span><span data-spinner class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>'
        : escapar($texto);
    $conteudo .= $iconeDireita !== '' ? ' <i class="' . escapar($iconeDireita) . '"></i>' : '';

    if ($href !== '' && $desabilitado) {
        return '<span' . montarAtributos(['id' => $id ?: null, 'class' => $classes, 'aria-disabled' => 'true'] + $atributos) . '>' . $conteudo . '</span>';
    }

    if ($href !== '') {
        return '<a' . montarAtributos(['id' => $id ?: null, 'href' => $href, 'class' => $classes] + $atributos) . '>' . $conteudo . '</a>';
    }

    return '<button' . montarAtributos(['type' => $tipo, 'id' => $id ?: null, 'class' => $classes, 'disabled' => $desabilitado] + $atributos) . '>' . $conteudo . '</button>';
}

/**
 * Botão médio: ícone num círculo em cima e rótulo embaixo (ex.: Acesso Rápido).
 * $cor: marca-primaria ou vermelho. Com $href vira <a>, sem $href vira <button>.
 */
function botaoMedio(
    string $rotulo,
    string $icone,
    string $cor = 'marca-primaria',
    string $href = '',
    string $id = '',
    array $atributos = [],
    bool $desabilitado = false
): string {
    $cores = [
        'marca-primaria' => ['hover:border-marca-primaria', 'bg-marca-clara text-marca-primaria group-hover:bg-marca-primaria group-hover:text-white'],
        'vermelho' => ['hover:border-red-600', 'bg-red-50 text-red-600 group-hover:bg-red-600 group-hover:text-white'],
    ];
    [$classeBorda, $classeIcone] = $cores[$cor] ?? $cores['marca-primaria'];

    $classes = 'group flex flex-col items-center justify-center gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm transition-colors ' . $classeBorda
        . ($desabilitado ? ' pointer-events-none cursor-not-allowed opacity-50' : '');

    $conteudo = '<div class="flex h-10 w-10 items-center justify-center rounded-full transition-colors ' . $classeIcone . '"><i class="' . escapar($icone) . '"></i></div>'
        . '<span class="text-sm font-medium text-gray-700">' . escapar($rotulo) . '</span>';

    if ($href !== '' && !$desabilitado) {
        return '<a' . montarAtributos(['id' => $id ?: null, 'href' => $href, 'class' => $classes] + $atributos) . '>' . $conteudo . '</a>';
    }

    return '<button' . montarAtributos(['type' => 'button', 'id' => $id ?: null, 'class' => $classes, 'disabled' => $desabilitado] + $atributos) . '>' . $conteudo . '</button>';
}