<?php

// Caixa de mensagem escondida. Use mostrarAlerta('#id', 'texto') e ocultarAlerta('#id') no JS.
function alerta(string $id, string $tipo = 'erro'): string
{
    $tipos = [
        'erro' => 'border-red-200 bg-red-50 text-red-700',
        'sucesso' => 'border-green-200 bg-green-50 text-green-700',
    ];

    return '<div' . montarAtributos([
        'id' => $id,
        'role' => 'alert',
        'class' => 'hidden rounded-xl border px-3 py-2 text-sm ' . ($tipos[$tipo] ?? $tipos['erro']),
    ]) . '></div>';
}

function formulario(string $id, string $conteudo, string $classe = ''): string
{
    return '<form' . montarAtributos(['id' => $id, 'method' => 'post', 'novalidate' => true, 'class' => $classe ?: null]) . '>' . $conteudo . '</form>';
}

function cartaoIndicador(string $rotulo, string $valor, string $detalhe = ''): string
{
    return '<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">'
        . '<p class="text-sm text-gray-500">' . escapar($rotulo) . '</p>'
        . '<p class="mt-2 text-2xl font-bold text-marca-primaria">' . escapar($valor) . '</p>'
        . ($detalhe !== '' ? '<p class="mt-3 text-sm text-gray-500">' . escapar($detalhe) . '</p>' : '')
        . '</div>';
}

function cabecalhoPortal(string $nome, string $cpf): string
{
    return '<header class="border-b border-marca-primaria/10 bg-white/90 backdrop-blur-sm">'
        . '<div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">'
        . '<div class="flex items-center gap-3">'
        . imagem('logo_verde.png', 'Logo', 'h-12 w-auto')
        . '<div><p class="text-xs uppercase tracking-[0.2em] text-marca-primaria/70">Portal</p>'
        . '<h1 class="text-lg font-semibold text-marca-primaria">' . escapar(APP_NOME) . '</h1></div>'
        . '</div>'
        . '<div class="flex items-center gap-4">'
        . '<div class="text-right"><p class="text-sm font-semibold text-marca-primaria">' . escapar($nome) . '</p>'
        . '<p class="text-xs text-gray-500">' . escapar($cpf) . '</p></div>'
        . botao('Sair', 'pilula', href: APP_URL_BASE . '/sair/')
        . '</div></div></header>';
}
