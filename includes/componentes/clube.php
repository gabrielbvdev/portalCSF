<?php

/**
 * Componentes do Clube & Moedas.
 * Níveis pelo saldo atual, de 500 em 500: bronze, prata, ouro, diamante. A partir de 2000 é platina (sem contador).
 */
const CLUBE_MOEDAS_POR_NIVEL = 500;

/**
 * Dados do nível do saldo: nome, cores (classes Tailwind por extenso), progresso na faixa e o que falta para o próximo.
 *
 * @return array{nome: string, textoMoedas: string, fundoTrofeu: string, barra: string, proximo: ?string, faltam: int, porcentagem: int}
 */
function nivelClube(int $saldo): array
{
    $niveis = [
        ['nome' => 'Bronze', 'textoMoedas' => 'text-amber-500', 'fundoTrofeu' => 'bg-amber-700', 'barra' => 'bg-amber-500'],
        ['nome' => 'Prata', 'textoMoedas' => 'text-slate-200', 'fundoTrofeu' => 'bg-slate-400', 'barra' => 'bg-slate-300'],
        ['nome' => 'Ouro', 'textoMoedas' => 'text-yellow-400', 'fundoTrofeu' => 'bg-yellow-500', 'barra' => 'bg-yellow-400'],
        ['nome' => 'Diamante', 'textoMoedas' => 'text-cyan-300', 'fundoTrofeu' => 'bg-cyan-500', 'barra' => 'bg-cyan-300'],
        ['nome' => 'Platina', 'textoMoedas' => 'text-fuchsia-300', 'fundoTrofeu' => 'bg-fuchsia-500', 'barra' => 'bg-fuchsia-300'],
    ];

    $saldo = max(0, $saldo);
    $indice = min(intdiv($saldo, CLUBE_MOEDAS_POR_NIVEL), count($niveis) - 1);
    $nivel = $niveis[$indice];
    $ultimo = $indice === count($niveis) - 1;

    return $nivel + [
        'proximo' => $ultimo ? null : $niveis[$indice + 1]['nome'],
        'faltam' => $ultimo ? 0 : (($indice + 1) * CLUBE_MOEDAS_POR_NIVEL) - $saldo,
        'porcentagem' => $ultimo ? 100 : (int) floor((($saldo % CLUBE_MOEDAS_POR_NIVEL) / CLUBE_MOEDAS_POR_NIVEL) * 100),
    ];
}

/**
 * Card de saldo do clube: troféu, saldo e barra de progresso do nível. Troféu, saldo e barra usam a cor do nível.
 * No nível máximo (platina) não há contador, só a indicação do nível.
 */
function cardSaldoClube(int $saldo): string
{
    $nivel = nivelClube($saldo);
    $saldoFormatado = escapar(number_format($saldo, 0, ',', '.'));
    $nome = escapar($nivel['nome']);

    if ($nivel['proximo'] === null) {
        $progresso = '<p class="flex items-center justify-between text-sm"><span>Nível ' . $nome . '</span><span><i class="fa-solid fa-crown"></i> Nível máximo</span></p>'
            . '<div class="mt-2 h-2.5 w-full rounded-full bg-black/25"><div class="h-2.5 rounded-full ' . $nivel['barra'] . '" style="width: 100%"></div></div>'
            . '<p class="mt-2 text-right text-xs text-green-100">Você alcançou o maior nível do clube!</p>';
    } else {
        $progresso = '<p class="flex items-center justify-between text-sm"><span>Nível ' . $nome . '</span><span>Faltam ' . escapar(number_format($nivel['faltam'], 0, ',', '.')) . ' moedas</span></p>'
            . '<div class="mt-2 h-2.5 w-full rounded-full bg-black/25"><div class="h-2.5 rounded-full ' . $nivel['barra'] . '" style="width: ' . $nivel['porcentagem'] . '%"></div></div>'
            . '<p class="mt-2 text-right text-xs text-green-100">para o Nível ' . escapar($nivel['proximo']) . '</p>';
    }

    return '<div class="mb-8 flex flex-col items-center justify-between gap-6 rounded-2xl bg-gradient-to-r from-marca-primaria to-green-900 p-6 text-white shadow-md md:flex-row">'
        . '<div class="flex w-full items-center gap-6 md:w-auto">'
        . '<div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full border-4 border-white/20 text-4xl shadow-lg ' . $nivel['fundoTrofeu'] . '"><i class="fa-solid fa-trophy text-white"></i></div>'
        . '<div><p class="text-green-100">Seu saldo atual é de</p>'
        . '<h4 class="text-4xl font-bold ' . $nivel['textoMoedas'] . '">' . $saldoFormatado . ' <span class="text-lg font-normal text-white">moedas</span></h4></div>'
        . '</div>'
        . '<div class="w-full rounded-xl border border-white/20 bg-white/10 p-4 md:w-1/3">' . $progresso . '</div>'
        . '</div>';
}

/**
 * Card da vitrine de recompensas. A área de destaque mostra uma imagem ($imagem, URL) ou, sem imagem, um ícone ($icone, classe FontAwesome).
 * $rodape é o HTML do botão de ação.
 */
function cardRecompensa(string $titulo, string $descricao, int $custo, string $rodape, string $icone = '', string $imagem = ''): string
{
    $destaque = $imagem !== ''
        ? '<img src="' . escapar($imagem) . '" alt="' . escapar($titulo) . '" class="h-full w-full object-cover">'
        : '<i class="' . escapar($icone !== '' ? $icone : 'fa-solid fa-gift') . ' text-4xl"></i>';

    return '<div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-shadow hover:shadow-md">'
        . '<div class="relative flex h-32 items-center justify-center bg-marca-clara text-marca-primaria">' . $destaque
        . '<span class="absolute right-2 top-2 rounded bg-marca-destaque px-2 py-1 text-xs font-bold text-white">' . escapar(number_format($custo, 0, ',', '.')) . ' Moedas</span></div>'
        . '<div class="p-5"><h5 class="mb-1 font-bold text-gray-800">' . escapar($titulo) . '</h5>'
        . '<p class="mb-4 text-sm text-gray-500">' . escapar($descricao) . '</p>' . $rodape . '</div>'
        . '</div>';
}
