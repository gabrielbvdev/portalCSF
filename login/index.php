<?php
require_once dirname(__DIR__) . '/includes/framework.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . APP_URL_BASE . '/dashboard/');
    exit;
}

$pagina = new Pagina('Login', 'min-h-screen flex items-center justify-center p-4 overflow-hidden');

$pagina->css(<<<'CSS'
        body { background: linear-gradient(135deg, rgba(24,55,36,0.08), rgba(182,157,94,0.12)); }
        .brilho-marca { box-shadow: 0 18px 45px rgba(24, 55, 36, 0.18); }
CSS);

$formularioLogin = formulario(
    'loginForm',
    campo(nome: 'cpf', rotulo: 'CPF', icone: 'fa-solid fa-id-card', placeholder: 'Digite seu CPF', autocomplete: 'username')
    . campo(nome: 'senha', rotulo: 'Senha', tipo: 'password', icone: 'fa-solid fa-lock', placeholder: 'Digite sua senha', autocomplete: 'current-password', alternarSenha: true)
    . alerta('mensagemFormulario')
    . botao('Acessar meu Portal', 'largo', id: 'botaoEnviar', tipo: 'submit', carregando: true),
    'space-y-5'
);

$conteudoPrimeiroAcesso = <<<HTML
<div class="space-y-4 text-sm text-gray-700">
    <p>Para acessar o portal, use seu <strong>CPF</strong> como login e também como senha no primeiro acesso.</p>
    <div class="rounded-xl     border border-marca-primaria/10 bg-marca-clara p-4">
            <p class="font-semibold text-marca-primaria">Exemplo:</p>
        <p>Se seu CPF for <span class="font-semibold">123.456.789-00</span></p>
        <p class="mt-2">Login: <span class="font-mono">12345678900</span></p>
        <p>Senha: <span class="font-mono">12345678900</span></p>
    </div>
</div>
HTML;

$modalPrimeiroAcesso = modal(
    id: 'modalPrimeiroAcesso',
    titulo: 'Como funciona o login',
    etiqueta: 'Primeiro acesso',
    conteudo: $conteudoPrimeiroAcesso,
    rodape: '<div class="flex justify-end">' . botao('Entendi', atributos: ['data-fechar-modal' => true]) . '</div>'
);

$imagemLogo = imagem('logo_verde.png', APP_NOME, 'h-16 w-auto object-contain');
$botaoPrimeiroAcesso = botao('Primeiro acesso?', 'link', icone: 'fa-solid fa-circle-info', atributos: ['data-abrir-modal' => 'modalPrimeiroAcesso']);

$pagina->html(<<<HTML
    <div class="absolute inset-0 opacity-25">
        <div class="absolute -top-12 -left-12 h-64 w-64 rounded-full bg-marca-primaria filter blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-marca-destaque filter blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md mx-auto">
        <div class="bg-white/95 backdrop-blur-sm rounded-[28px] border border-marca-primaria/10 brilho-marca p-8 md:p-10">
            <div class="text-center mb-8">
                <div class="mb-5 flex items-center justify-center">{$imagemLogo}</div>
                <h1 class="text-3xl font-bold text-marca-primaria">Bem-vindo(a)</h1>
                <p class="mt-2 text-sm text-gray-600">Acesse o portal do cliente e acompanhe seu plano com segurança.</p>
            </div>

            {$formularioLogin}

            <div class="mt-6 border-t border-gray-200 pt-5 text-center text-xs text-gray-500">{$botaoPrimeiroAcesso}</div>
        </div>
    </div>

    {$modalPrimeiroAcesso}
HTML);

$pagina->js(<<<'JS'
            $('#cpf').on('input', function () {
                let valor = this.value.replace(/\D/g, '').slice(0, 11);
                if (valor.length > 9) {
                    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                    valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                } else if (valor.length > 3) {
                    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                }
                this.value = valor;
            });

            $('#loginForm').on('submit', function (evento) {
                evento.preventDefault();

                const valorInformado = $('#cpf').val().trim();
                const cpf = valorInformado.replace(/\D/g, '');
                const senha = $('#senha').val();
                const usuarioNormalizado = valorInformado.toLowerCase();

                if (!valorInformado) {
                    mostrarAlerta('#mensagemFormulario', 'Informe seu CPF ou usuário para continuar.');
                    return;
                }

                if (!senha || senha.length < 4) {
                    mostrarAlerta('#mensagemFormulario', 'Informe sua senha correta para continuar.');
                    return;
                }

                if ((usuarioNormalizado === 'usuario' || usuarioNormalizado === 'administrador') && cpf === '') {
                    // demonstração do protótipo aceita usuário e senha sem CPF válido
                } else if (cpf.length !== 11) {
                    mostrarAlerta('#mensagemFormulario', 'Informe um CPF válido com 11 dígitos.');
                    return;
                }

                ocultarAlerta('#mensagemFormulario');
                definirCarregando('#botaoEnviar', true, 'Validando dados...');

                postarJson(URL_BASE + '/login/autenticar.php', { cpf: valorInformado, senha: senha })
                    .done(function (retorno) {
                        window.location.href = retorno.redirecionar || URL_BASE + '/dashboard/';
                    })
                    .fail(function (mensagem) {
                        mostrarAlerta('#mensagemFormulario', mensagem);
                        definirCarregando('#botaoEnviar', false);
                    });
            });
JS);

$pagina->exibir();
