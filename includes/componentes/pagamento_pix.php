<?php

/**
 * Pagamento via Pix: botão "Pagar" + modal com o código copia e cola.
 * Use botaoPagarPix() em cada fatura em aberto e chame modalPix() uma vez por página (depois de criar a Pagina).
 * $idPagamento é o id local da fatura (adm_pagamentos.id), nunca o id do iVertex.
 */
function botaoPagarPix(int $idPagamento, bool $largo = false): string
{
    return botao('Pagar', largo: $largo, icone: 'fa-brands fa-pix', atributos: ['data-pagar-pix' => $idPagamento]);
}

function modalPix(): string
{
    Pagina::atual()?->js(<<<'JS'
        let pixCodigoAtual = '';

        function mostrarEstadoPix(estado) {
            $('#pixCarregando, #pixErro, #pixCodigo').addClass('hidden');
            $('#' + estado).removeClass('hidden');
        }

        $(document).on('click', '[data-pagar-pix]', function () {
            const idPagamento = $(this).data('pagar-pix');

            mostrarEstadoPix('pixCarregando');
            abrirModal('modalPix');

            postarJson(URL_BASE + '/financeiro/pix.php', { id_pagamento: idPagamento })
                .done(function (retorno) {
                    pixCodigoAtual = retorno.codigo;
                    $('#pixTexto').val(retorno.codigo);
                    $('#pixCopiado').addClass('hidden');
                    mostrarEstadoPix('pixCodigo');
                })
                .fail(function (mensagem) {
                    $('#pixMensagemErro').text(mensagem);
                    mostrarEstadoPix('pixErro');
                });
        });

        $('#pixCopiar').on('click', function () {
            const concluir = function () { $('#pixCopiado').removeClass('hidden'); };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(pixCodigoAtual).then(concluir);
                return;
            }

            $('#pixTexto').trigger('select');
            document.execCommand('copy');
            concluir();
        });
JS, 'modalPix');

    $conteudo = '<div id="pixCarregando" class="flex flex-col items-center gap-3 py-8 text-gray-500">'
        . '<span class="h-8 w-8 animate-spin rounded-full border-4 border-marca-primaria border-t-transparent"></span>'
        . '<p>Gerando o código Pix...</p></div>'
        . '<div id="pixErro" class="hidden rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"><p id="pixMensagemErro"></p></div>'
        . '<div id="pixCodigo" class="hidden space-y-4">'
        . '<p class="text-sm text-gray-600">Copie o código abaixo e cole na área <strong>Pix Copia e Cola</strong> do aplicativo do seu banco.</p>'
        . '<textarea id="pixTexto" readonly rows="5" class="w-full resize-none break-all rounded-xl border border-gray-200 bg-gray-50 p-3 font-mono text-xs text-gray-700"></textarea>'
        . botao('Copiar código', largo: true, id: 'pixCopiar', icone: 'fa-regular fa-copy')
        . '<p id="pixCopiado" class="hidden text-center text-sm font-semibold text-green-600"><i class="fa-solid fa-check"></i> Código copiado!</p>'
        . '<p class="text-center text-xs text-gray-400">Após o pagamento, use o botão "Atualizar" no Financeiro para ver a baixa.</p>'
        . '</div>';

    return modal(id: 'modalPix', titulo: 'Pagar com Pix', etiqueta: 'Pagamento', conteudo: $conteudo);
}
