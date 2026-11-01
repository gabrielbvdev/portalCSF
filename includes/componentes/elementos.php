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

// Etiqueta arredondada. Cores: sucesso, alerta, perigo, neutro, info.
function selo(string $texto, string $cor = 'sucesso'): string
{
    $cores = [
        'sucesso' => 'bg-green-100 text-green-700',
        'alerta' => 'bg-yellow-100 text-yellow-700',
        'perigo' => 'bg-red-100 text-red-700',
        'neutro' => 'bg-gray-100 text-gray-600',
        'info' => 'bg-blue-100 text-blue-700',
    ];

    return '<span class="rounded-full px-2 py-1 text-xs font-bold ' . ($cores[$cor] ?? $cores['neutro']) . '">' . escapar($texto) . '</span>';
}

// Etiqueta arredondada com iniciais dos nomes. Cores: sucesso, alerta, perigo, neutro, info.
function seloIniciais(string $texto, string $cor = 'info'): string
{
    $cores = [
        'sucesso' => 'bg-green-100 text-green-700',
        'alerta' => 'bg-yellow-100 text-yellow-700',
        'perigo' => 'bg-red-100 text-red-700',
        'neutro' => 'bg-gray-100 text-gray-600',
        'info' => 'bg-blue-100 text-blue-700',
    ];

    $palavras = explode(' ', $texto);
    $filtradas = array_filter($palavras, function($palavra) {
        return !in_array(mb_strtolower($palavra, "UTF-8"), ['de', 'da', 'do', 'das', 'dos', 'e']);
    });
    $filtradas = array_values($filtradas);

    $inicial1 = mb_substr($filtradas[0] ?? '', 0, 1, "UTF-8");
    $inicial2 = mb_substr($filtradas[1] ?? '', 0, 1, "UTF-8");
    $iniciais = mb_strtoupper($inicial1 . $inicial2, "UTF-8");

    return '<div class="w-12 h-12 rounded-full '.$cores[$cor].' flex items-center justify-center font-bold text-lg">'.$iniciais.'</div>';
};

/*
 * Card de informação.
 * $icone, $superior, $descricao e $rodape aceitam HTML pronto (escape o que vier de usuário/banco).
 * $corFundo: branco, marca-primaria, marca-secundaria (fundo escuro deixa o texto branco).
 * $corIcone: marca-primaria, marca-secundaria, marca-destaque, verde, vermelho, azul, cinza.
 * O fundo claro do ícone e a barra lateral (se $barra) derivam de $corIcone.
 */
function card(
    string $icone,
    string $descricao,
    string $superior = '',
    string $rodape = '',
    string $corFundo = 'branco',
    string $corIcone = 'marca-primaria',
    bool $barra = false
): string {
    $fundos = [
        'branco' => ['bg-white border-gray-100', false],
        'marca-primaria' => ['bg-marca-primaria border-transparent', true],
        'marca-secundaria' => ['bg-marca-secundaria border-transparent', true],
    ];
    $icones = [
        'marca-primaria' => ['bg-marca-clara text-marca-primaria', 'border-l-marca-primaria'],
        'marca-secundaria' => ['bg-marca-secundaria/15 text-marca-secundaria', 'border-l-marca-secundaria'],
        'marca-destaque' => ['bg-marca-destaque/15 text-marca-destaque', 'border-l-marca-destaque'],
        'verde' => ['bg-green-100 text-green-600', 'border-l-green-500'],
        'vermelho' => ['bg-red-100 text-red-600', 'border-l-red-500'],
        'azul' => ['bg-blue-100 text-blue-600', 'border-l-blue-500'],
        'cinza' => ['bg-gray-100 text-gray-600', 'border-l-gray-400'],
    ];

    [$classeFundo, $escuro] = $fundos[$corFundo] ?? $fundos['branco'];
    [$classeIcone, $classeBarra] = $icones[$corIcone] ?? $icones['marca-primaria'];

    // Em fundo escuro o ícone usa um badge translúcido, mantendo só a cor do símbolo
    if ($escuro) {
        $classeIcone = 'bg-white/20 ' . explode(' ', $classeIcone)[1];
    }

    $classes = 'flex flex-col justify-between rounded-2xl border p-6 shadow-sm ' . $classeFundo
        . ($escuro ? ' text-white' : ' text-gray-500')
        . ($barra ? ' border-l-4 ' . $classeBarra : '');

    return '<div class="' . $classes . '">'
        . '<div>'
        . '<div class="mb-4 flex items-start justify-between">'
        . '<div class="flex h-12 w-12 items-center justify-center rounded-xl text-xl ' . $classeIcone . '">' . $icone . '</div>'
        . $superior
        . '</div>'
        . $descricao
        . '</div>'
        . ($rodape !== '' ? '<div class="mt-4">' . $rodape . '</div>' : '')
        . '</div>';
}

/*
 * Tabela. $colunas: lista de ['titulo' => ..., 'orientacao' => 'esquerda'|'direita'|'centro', 'expandir' => bool, 'mobile' => bool].
 * 'orientacao' é obrigatória e vale para título e dados; 'titulo' é opcional (sem nenhum título, não há cabeçalho).
 * 'expandir' faz a coluna ocupar o espaço que sobra; 'mobile' => false esconde a coluna em telas pequenas.
 * $linhas: lista de linhas, cada uma com os dados (HTML pronto) na mesma ordem das colunas.
 * Escape com escapar() o que vier do banco antes de passar.
 */
function tabela(array $colunas, array $linhas, bool $separador = true, bool $zebra = false, string $classe = ''): string
{
    $alinhamentos = ['esquerda' => 'text-left', 'direita' => 'text-right', 'centro' => 'text-center'];

    foreach ($colunas as $indice => $coluna) {
        if (!isset($coluna['orientacao'], $alinhamentos[$coluna['orientacao']])) {
            throw new InvalidArgumentException("tabela(): a coluna $indice precisa de 'orientacao' (esquerda, direita ou centro).");
        }
    }

    $classesColuna = [];
    foreach ($colunas as $coluna) {
        $classes = 'px-2 py-4 align-middle first:pl-4 last:pr-4 sm:px-4 sm:first:pl-6 sm:last:pr-6 ' . $alinhamentos[$coluna['orientacao']];
        $classes .= !empty($coluna['expandir']) ? ' w-full' : ' w-px whitespace-nowrap';
        if (($coluna['mobile'] ?? true) === false) {
            $classes .= ' hidden sm:table-cell';
        }
        $classesColuna[] = $classes;
    }

    $temTitulos = false;
    foreach ($colunas as $coluna) {
        $temTitulos = $temTitulos || trim((string) ($coluna['titulo'] ?? '')) !== '';
    }

    $cabecalho = '';
    if ($temTitulos) {
        $cabecalho = '<thead class="border-b border-gray-100 bg-gray-50 text-xs font-semibold uppercase text-gray-500"><tr>';
        foreach ($colunas as $indice => $coluna) {
            $cabecalho .= '<th scope="col" class="' . $classesColuna[$indice] . '">' . escapar((string) ($coluna['titulo'] ?? '')) . '</th>';
        }
        $cabecalho .= '</tr></thead>';
    }

    $classeCorpo = trim(($separador ? 'divide-y divide-gray-100' : '') . ' ' . ($zebra ? '[&>tr:nth-child(even)]:bg-gray-50' : ''));

    $corpo = '';
    foreach ($linhas as $linha) {
        $corpo .= '<tr>';
        foreach ($colunas as $indice => $coluna) {
            $corpo .= '<td class="' . $classesColuna[$indice] . '">' . ($linha[$indice] ?? '') . '</td>';
        }
        $corpo .= '</tr>';
    }

    return '<div class="overflow-x-auto ' . $classe . '"><table class="w-full">' . $cabecalho . '<tbody class="' . $classeCorpo . '">' . $corpo . '</tbody></table></div>';
}