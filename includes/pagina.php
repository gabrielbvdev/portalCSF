<?php

require_once __DIR__ . '/config.php';

/**
 * Monta uma página inteira a partir de três buffers (html, css e js).
 *
 * Uso:
 *   $pagina = new Pagina('Painel');
 *   $pagina->html(botao('Salvar'))->html('<div>HTML manual</div>');
 *   $pagina->css('.x { color: red; }');
 *   $pagina->js("$('#x').on('click', function () { ... });");
 *   $pagina->exibir();
 *
 * O js() recebe código jQuery e é executado dentro do $(function () { ... }).
 * Os componentes (includes/componentes) usam Pagina::atual() para registrar o próprio JS.
 */
class Pagina
{
    private static ?Pagina $atual = null;

    private string $bufferHtml = '';
    private string $bufferCss = '';
    private string $bufferJs = '';
    private array $chavesJs = [];

    public function __construct(
        private string $titulo,
        private string $classeBody = ''
    ) {
        self::$atual = $this;
    }

    public static function atual(): ?Pagina
    {
        return self::$atual;
    }

    public function html(string $conteudo): static
    {
        $this->bufferHtml .= $conteudo . "\n";
        return $this;
    }

    public function css(string $conteudo): static
    {
        $this->bufferCss .= $conteudo . "\n";
        return $this;
    }

    // Com $chave, o mesmo trecho só entra uma vez (útil para componentes usados várias vezes na página).
    public function js(string $conteudo, ?string $chave = null): static
    {
        if ($chave !== null) {
            if (isset($this->chavesJs[$chave])) {
                return $this;
            }
            $this->chavesJs[$chave] = true;
        }

        $this->bufferJs .= $conteudo . "\n";
        return $this;
    }

    public function exibir(): void
    {
        $titulo = escapar($this->titulo . ' | ' . APP_NOME);
        $favicon = escapar(APP_URL_BASE . '/assets/img/favicon-verde.png');
        $classeBody = escapar($this->classeBody);

        echo <<<HTML
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titulo}</title>
    <link rel="icon" href="{$favicon}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        marca: {
                            escura: '#0f2418',
                            primaria: '#183724',
                            secundaria: '#234e34',
                            destaque: '#b69d5e',
                            'destaque-hover': '#9e864b',
                            clara: '#f2f4ee'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        };
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
{$this->bufferCss}    </style>
</head>
<body class="{$classeBody}">
{$this->bufferHtml}
    <script>
{$this->jsBase()}
        \$(function () {
{$this->bufferJs}        });
    </script>
</body>
</html>
HTML;
    }

    // Funções JavaScript disponíveis em todas as páginas.
    private function jsBase(): string
    {
        $urlBase = json_encode(APP_URL_BASE);

        return str_replace('__URL_BASE__', $urlBase, <<<'JS'
        const URL_BASE = __URL_BASE__;

        function mostrarAlerta(seletor, texto) {
            $(seletor).text(texto).removeClass('hidden');
        }

        function ocultarAlerta(seletor) {
            $(seletor).addClass('hidden').text('');
        }

        function definirCarregando(seletor, carregando, textoCarregando) {
            const $botao = $(seletor);
            const $texto = $botao.find('[data-texto]');
            if ($texto.data('original') === undefined) {
                $texto.data('original', $texto.text());
            }
            $botao.prop('disabled', carregando);
            $texto.text(carregando ? textoCarregando : $texto.data('original'));
            $botao.find('[data-spinner]').toggleClass('hidden', !carregando);
        }

        function abrirModal(id) {
            $('#' + id).removeClass('hidden').addClass('flex');
        }

        function fecharModal(id) {
            $('#' + id).addClass('hidden').removeClass('flex');
        }

        $(document).on('click', '[data-abrir-modal]', function () {
            abrirModal($(this).data('abrir-modal'));
        });

        $(document).on('click', '[data-fechar-modal]', function () {
            fecharModal($(this).closest('[data-modal]').attr('id'));
        });

        $(document).on('click', '[data-modal][data-fechavel="1"]', function (evento) {
            if (evento.target === this) {
                fecharModal(this.id);
            }
        });

        function lerRetornoJson(texto) {
            try {
                return texto ? JSON.parse(texto) : null;
            } catch (erro) {
                return null;
            }
        }

        // POST que espera o JSON {sucesso, mensagem, ...}. Resolve com o retorno; rejeita com a mensagem de erro.
        function postarJson(url, dados) {
            const adiado = $.Deferred();

            $.ajax({
                url: url,
                method: 'POST',
                data: dados,
                dataType: 'text',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (texto) {
                const retorno = lerRetornoJson(texto);
                if (retorno && retorno.sucesso) {
                    adiado.resolve(retorno);
                } else {
                    adiado.reject((retorno && retorno.mensagem) || 'Resposta inválida do servidor.');
                }
            }).fail(function (xhr) {
                const retorno = lerRetornoJson(xhr.responseText);
                adiado.reject((retorno && retorno.mensagem) || 'Erro de conexão. Tente novamente.');
            });

            return adiado.promise();
        }
JS);
    }
}
