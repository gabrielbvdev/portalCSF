<?php

/**
 * Esqueleto das páginas internas do portal: menu lateral (desktop), topo com menu do usuário e menu inferior (mobile).
 *
 * Uso: $pagina->html(layoutPortal('inicio', 'Visão Geral', $conteudo, $nomeUsuario, 'Titular - Plano X'));
 */

function itensMenuPortal(): array
{
    return [
        'inicio' => ['rotulo' => 'Início', 'rotuloMobile' => 'Início', 'icone' => 'fa-solid fa-house', 'pasta' => 'dashboard'],
        'dependentes' => ['rotulo' => 'Dependentes', 'rotuloMobile' => 'Família', 'icone' => 'fa-solid fa-users', 'pasta' => 'dependentes'],
        'financeiro' => ['rotulo' => 'Financeiro', 'rotuloMobile' => 'Faturas', 'icone' => 'fa-solid fa-file-invoice-dollar', 'pasta' => 'financeiro'],
        'clube' => ['rotulo' => 'Clube & Moedas', 'rotuloMobile' => 'Clube', 'icone' => 'fa-solid fa-award', 'pasta' => 'clube'],
    ];
}

// Uma página só aparece como link quando a pasta dela já existe no projeto.
function paginaPortalExiste(string $pasta): bool
{
    return is_file(APP_CAMINHO_BASE . '/' . $pasta . '/index.php');
}

function iniciaisNome(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if ($partes === []) {
        return '?';
    }

    $iniciais = mb_substr($partes[0], 0, 1);
    if (count($partes) > 1) {
        $iniciais .= mb_substr($partes[count($partes) - 1], 0, 1);
    }

    return mb_strtoupper($iniciais);
}

function layoutPortal(string $paginaAtiva, string $titulo, string $conteudo, string $nomeUsuario, string $subtituloUsuario = ''): string
{
    $menuLateral = '';
    $menuInferior = '';

    foreach (itensMenuPortal() as $chave => $item) {
        $ativo = $chave === $paginaAtiva;
        $disponivel = paginaPortalExiste($item['pasta']);
        $url = escapar(APP_URL_BASE . '/' . $item['pasta'] . '/');
        $icone = escapar($item['icone']);
        $rotulo = escapar($item['rotulo']);
        $rotuloMobile = escapar($item['rotuloMobile']);

        if ($disponivel) {
            $classeLateral = $ativo ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/10';
            $classeInferior = $ativo ? 'text-marca-primaria' : 'text-gray-400 hover:text-marca-primaria';

            $menuLateral .= '<a href="' . $url . '" class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left transition-colors ' . $classeLateral . '">'
                . '<i class="' . $icone . ' w-5 text-center"></i> ' . $rotulo . '</a>';
            $menuInferior .= '<a href="' . $url . '" class="flex h-full w-full flex-col items-center justify-center transition-colors ' . $classeInferior . '">'
                . '<i class="' . $icone . ' mb-1"></i><span class="text-[10px] font-medium">' . $rotuloMobile . '</span></a>';
        } else {
            $menuLateral .= '<span class="flex w-full cursor-not-allowed items-center gap-3 rounded-lg px-4 py-3 text-left text-white/40" title="Em breve">'
                . '<i class="' . $icone . ' w-5 text-center"></i> ' . $rotulo . '</span>';
            $menuInferior .= '<span class="flex h-full w-full cursor-not-allowed flex-col items-center justify-center text-gray-300" title="Em breve">'
                . '<i class="' . $icone . ' mb-1"></i><span class="text-[10px] font-medium">' . $rotuloMobile . '</span></span>';
        }
    }

    Pagina::atual()?->js(<<<'JS'
            $('#botaoMenuUsuario').on('click', function (evento) {
                evento.stopPropagation();
                $('#menuUsuario').toggleClass('hidden');
            });
            $(document).on('click', function (evento) {
                if (!$(evento.target).closest('#menuUsuario').length) {
                    $('#menuUsuario').addClass('hidden');
                }
            });
            $(document).on('keydown', function (evento) {
                if (evento.key === 'Escape') {
                    $('#menuUsuario').addClass('hidden');
                }
            });
JS, 'componente-menu-usuario');

    $logoDesktop = imagem('logo_branca.png', APP_NOME, 'h-auto w-44');
    $logoMobile = imagem('favicon-verde.png', APP_NOME, 'h-10 w-10 object-contain');
    $urlSair = escapar(APP_URL_BASE . '/sair/');
    $nomeEscapado = escapar($nomeUsuario);
    $subtituloEscapado = escapar($subtituloUsuario);
    $iniciais = escapar(iniciaisNome($nomeUsuario));
    $tituloEscapado = escapar($titulo);

    return <<<HTML
    <div class="flex h-screen overflow-hidden">
        <aside class="hidden w-64 flex-col bg-marca-primaria text-white shadow-xl md:flex">
            <div class="flex items-center justify-center border-b border-white/10 p-6">{$logoDesktop}</div>

            <nav class="flex-1 space-y-2 px-4 py-6">{$menuLateral}</nav>

            <div class="border-t border-white/10 p-4">
                <a href="{$urlSair}" class="flex w-full items-center gap-3 rounded-lg px-4 py-2 text-left text-white/70 transition-colors hover:bg-white/10">
                    <i class="fa-solid fa-right-from-bracket w-5 text-center"></i> Sair
                </a>
            </div>
        </aside>

        <main class="relative flex h-screen flex-1 flex-col overflow-y-auto pb-20 md:pb-0">
            <header class="sticky top-0 isolate z-40 bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3 md:hidden">
                        {$logoMobile}
                        <span class="font-bold text-marca-primaria">Portal do Cliente</span>
                    </div>
                    <div class="hidden md:block">
                        <h2 class="text-xl font-semibold text-gray-800">{$tituloEscapado}</h2>
                    </div>

                    <div class="relative flex items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-semibold text-gray-800">{$nomeEscapado}</p>
                            <p class="text-xs text-gray-500">{$subtituloEscapado}</p>
                        </div>
                        <button type="button" id="botaoMenuUsuario" aria-label="Menu do usuário" class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-marca-primaria bg-marca-clara text-sm font-bold text-marca-primaria shadow-sm transition-colors hover:bg-marca-primaria hover:text-white">{$iniciais}</button>

                        <div id="menuUsuario" class="absolute right-0 top-full z-50 mt-2 hidden w-56 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-lg">
                            <span class="flex cursor-not-allowed items-center gap-3 px-4 py-3 text-sm text-gray-400" title="Em breve">
                                <i class="fa-solid fa-user-pen w-4 text-center"></i> <span class="whitespace-nowrap">Meus dados</span>
                                                                <span class="ml-auto whitespace-nowrap rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase">Em breve</span>
                            </span>
                            <a href="{$urlSair}" class="flex items-center gap-3 border-t border-gray-100 px-4 py-3 text-sm text-gray-700 transition-colors hover:bg-gray-50">
                                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Sair
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="mx-auto w-full max-w-7xl p-4 md:p-8">
                {$conteudo}
            </div>
        </main>
    </div>

    <nav class="fixed bottom-0 z-20 flex h-16 w-full items-center justify-around border-t border-gray-200 bg-white md:hidden">{$menuInferior}</nav>
HTML;
}
