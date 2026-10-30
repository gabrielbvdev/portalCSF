<?php

/**
 * Campo de formulário com rótulo e, opcionalmente, ícone.
 * Com $alternarSenha (tipo password) aparece o botão de mostrar/ocultar senha, com o JS já registrado na página.
 */
function campo(
    string $nome,
    string $rotulo,
    string $tipo = 'text',
    string $icone = '',
    string $placeholder = '',
    string $autocomplete = '',
    bool $obrigatorio = true,
    string $id = '',
    bool $alternarSenha = false,
    ?int $minimo = null,
    string $modoEntrada = ''
): string {
    $id = $id !== '' ? $id : $nome;

    $classesInput = 'w-full rounded-xl border border-gray-200 bg-gray-50 py-3 text-sm text-gray-800 focus:border-marca-primaria focus:outline-none focus:ring-2 focus:ring-marca-primaria/20';
    $classesInput .= $icone !== '' ? ' pl-10' : ' pl-3';
    $classesInput .= $alternarSenha ? ' pr-10' : ' pr-3';

    $input = '<input' . montarAtributos([
        'id' => $id,
        'name' => $nome,
        'type' => $tipo,
        'inputmode' => $modoEntrada ?: null,
        'autocomplete' => $autocomplete ?: null,
        'placeholder' => $placeholder ?: null,
        'minlength' => $minimo,
        'class' => $classesInput,
        'required' => $obrigatorio,
    ]) . '>';

    $iconeHtml = $icone !== ''
        ? '<span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500"><i class="' . escapar($icone) . '"></i></span>'
        : '';

    $alternarHtml = '';
    if ($alternarSenha) {
        $alternarHtml = '<button type="button" data-alternar-senha="' . escapar($id) . '" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 transition hover:text-marca-primaria" aria-label="Mostrar senha"><i class="fa-regular fa-eye"></i></button>';

        Pagina::atual()?->js(<<<'JS'
            $(document).on('click', '[data-alternar-senha]', function () {
                const $campo = $('#' + $(this).data('alternar-senha'));
                $campo.attr('type', $campo.attr('type') === 'password' ? 'text' : 'password');
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });
JS, 'componente-alternar-senha');
    }

    return '<div>'
        . '<label for="' . escapar($id) . '" class="mb-2 block text-sm font-medium text-gray-700">' . escapar($rotulo) . '</label>'
        . '<div class="relative">' . $iconeHtml . $input . $alternarHtml . '</div>'
        . '</div>';
}
