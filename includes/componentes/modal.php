<?php

/**
 * Modal escondido por padrão (abrirModal('id') / fecharModal('id') no JS, ou data-abrir-modal="id" em qualquer elemento).
 * Com $fechavel = false não há botão de fechar nem fechamento ao clicar fora (ex.: troca de senha obrigatória).
 * $conteudo e $rodape são HTML já montado; $titulo e $etiqueta são escapados.
 */
function modal(
    string $id,
    string $titulo,
    string $conteudo,
    string $etiqueta = '',
    bool $fechavel = true,
    bool $aberto = false,
    string $rodape = '',
    string $tamanho = 'md'
): string {
    $tamanhos = [
        'md' => ['caixa' => 'max-w-md rounded-2xl', 'titulo' => 'text-2xl', 'fundo' => 'bg-marca-escura/60'],
        'lg' => ['caixa' => 'max-w-lg rounded-3xl', 'titulo' => 'text-3xl', 'fundo' => 'bg-marca-escura/70'],
    ];
    $estilo = $tamanhos[$tamanho] ?? $tamanhos['md'];

    $cabecalho = '<div>'
        . ($etiqueta !== '' ? '<p class="text-xs font-semibold uppercase tracking-[0.2em] text-marca-primaria/70">' . escapar($etiqueta) . '</p>' : '')
        . '<h2 class="mt-2 ' . $estilo['titulo'] . ' font-bold text-marca-primaria">' . escapar($titulo) . '</h2></div>';

    if ($fechavel) {
        $cabecalho .= '<button type="button" data-fechar-modal class="rounded-full p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800" aria-label="Fechar modal"><i class="fa-solid fa-xmark"></i></button>';
    }

    return '<div' . montarAtributos([
        'id' => $id,
        'data-modal' => true,
        'data-fechavel' => $fechavel ? '1' : '0',
        'class' => 'fixed inset-0 z-50 items-center justify-center p-4 ' . $estilo['fundo'] . ($aberto ? ' flex' : ' hidden'),
    ]) . '>'
        . '<div class="w-full ' . $estilo['caixa'] . ' bg-white p-6 shadow-2xl">'
        . '<div class="mb-5 flex items-start justify-between gap-3">' . $cabecalho . '</div>'
        . $conteudo
        . ($rodape !== '' ? '<div class="mt-6">' . $rodape . '</div>' : '')
        . '</div></div>';
}
