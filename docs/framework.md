# Mini-framework de páginas — Portal CSF

Guia para criar páginas do portal sem escrever o HTML completo. O PHP monta a página em **buffers** (HTML, CSS e JS) e, no final, `exibir()` entrega tudo pronto.

Stack: PHP 8+, jQuery 3.7, Tailwind (CDN), Font Awesome 6. Sem Node e sem build.

---

## 1. Conceito

```
new Pagina()  ->  html() / css() / js()  ->  exibir()
                  (acumulam em buffers)       (monta <head>, <body> e <script>)
```

- **Componentes** (`botao()`, `campo()`, `modal()`...) são funções que **devolvem uma string HTML**. Não imprimem nada.
- Você concatena ou passa essas strings para `$pagina->html()`.
- Quando um componente não existe, escreva o HTML manualmente (heredoc) e passe para `html()`.

## 2. Estrutura de pastas

```
includes/
  framework.php          <- único include que a página precisa
  pagina.php             <- classe Pagina + JS base
  componentes/
    base.php             <- escapar, montarAtributos, imagem
    botao.php  campo.php  elementos.php  modal.php
nome_da_pagina/
  index.php              <- a página (URL: /nome_da_pagina/)
  endpoint.php           <- endpoints auxiliares dessa página (JSON)
```

Cada página vive na sua pasta, com `index.php`, para a URL ficar sem `.php`. Arquivos auxiliares usados só por aquela página ficam na mesma pasta. Subpáginas são pastas dentro da pasta da página.

## 3. Esqueleto mínimo de uma página

```php
<?php
require_once dirname(__DIR__) . '/includes/framework.php';
require_once dirname(__DIR__) . '/includes/database.php'; // só se usar dbQuery

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . APP_URL_BASE . '/login/');
    exit;
}

// 1) Lógica e consultas (dbQuery) ficam no topo
$faturas = dbQuery('SELECT * FROM adm_pagamentos WHERE id_cliente = :id', ['id' => $_SESSION['usuario_id']]);

// 2) Criar a página ANTES de usar campo() ou modal()
$pagina = new Pagina('Minhas faturas', 'bg-marca-clara min-h-screen text-gray-700');

// 3) Montar o conteúdo
$pagina->html(cabecalhoPortal($_SESSION['usuario_nome'], $_SESSION['usuario_cpf']));
$pagina->html('<main class="mx-auto max-w-7xl px-6 py-10">Conteúdo</main>');

// 4) JS e CSS opcionais
$pagina->js("$('#algo').on('click', function () { mostrarAlerta('#aviso', 'Olá'); });");

// 5) Exibir
$pagina->exibir();
```

> **Regra de ordem:** `new Pagina(...)` deve vir antes de `campo(... alternarSenha: true)` e de `modal(...)`, pois esses componentes registram o próprio JS na página atual.

## 4. Classe `Pagina`

| Método | O que faz |
|---|---|
| `new Pagina(string $titulo, string $classeBody = '')` | Cria a página. O título vira `"{titulo} \| {APP_NOME}"`. `$classeBody` são classes Tailwind do `<body>`. |
| `->html(string $conteudo)` | Acrescenta HTML ao corpo. |
| `->css(string $conteudo)` | Acrescenta CSS ao `<style>` do `<head>`. |
| `->js(string $codigo, ?string $chave = null)` | Acrescenta JS (jQuery). Roda dentro de `$(function () { ... })`. Com `$chave`, o mesmo trecho só é registrado uma vez. |
| `->exibir()` | Imprime a página completa. Chame uma vez, no final. |
| `Pagina::atual()` | Devolve a página em construção (usado pelos componentes). |

Todos os métodos, exceto `exibir()`, devolvem a própria página, então dá para encadear:

```php
$pagina->css('...')->html('...')->js('...');
```

O `<head>` já inclui: fonte Inter, Font Awesome, Tailwind com as cores `marca`, e jQuery. Não precisa declarar nada disso.

## 5. Componentes

Todos aceitam **argumentos nomeados** (PHP 8), o que deixa a chamada legível: `campo(nome: 'cpf', rotulo: 'CPF')`.

### `botao()`

```php
botao(string $texto, string $variante = 'primario', string $id = '', string $tipo = 'button',
      string $href = '', string $icone = '', bool $carregando = false,
      string $classe = '', array $atributos = []): string
```

| Parâmetro | Detalhe |
|---|---|
| `variante` | `primario`, `secundario`, `largo` (100% de largura), `pilula`, `link` |
| `tipo` | `button` ou `submit` |
| `href` | Se informado, gera `<a>` em vez de `<button>` |
| `icone` | Classe do Font Awesome, ex.: `fa-solid fa-circle-info` |
| `carregando` | Adiciona spinner e permite usar `definirCarregando()` no JS |
| `classe` | Classes Tailwind extras somadas à variante |
| `atributos` | Atributos extras, ex.: `['data-abrir-modal' => 'meuModal']` |

```php
botao('Acessar', 'largo', id: 'botaoEnviar', tipo: 'submit', carregando: true);
botao('Sair', 'pilula', href: APP_URL_BASE . '/sair/');
botao('Detalhes', 'link', icone: 'fa-solid fa-circle-info', atributos: ['data-abrir-modal' => 'modalDetalhes']);
```

### `campo()`

```php
campo(string $nome, string $rotulo, string $tipo = 'text', string $icone = '', string $placeholder = '',
      string $autocomplete = '', bool $obrigatorio = true, string $id = '',
      bool $alternarSenha = false, ?int $minimo = null, string $modoEntrada = ''): string
```

- `id` vazio usa o próprio `nome`.
- `alternarSenha: true` adiciona o olhinho de mostrar/ocultar senha (use com `tipo: 'password'`).
- `minimo` vira `minlength`.

```php
campo(nome: 'cpf', rotulo: 'CPF', icone: 'fa-solid fa-id-card', placeholder: 'Digite seu CPF');
campo(nome: 'senha', rotulo: 'Senha', tipo: 'password', icone: 'fa-solid fa-lock', alternarSenha: true);
```

### `formulario()`

```php
formulario(string $id, string $conteudo, string $classe = ''): string
```

Gera `<form method="post" novalidate>`. O envio normalmente é interceptado via JS (AJAX), então não há `action`.

### `alerta()`

```php
alerta(string $id, string $tipo = 'erro'): string   // tipo: 'erro' | 'sucesso'
```

Nasce **escondido**. Controle pelo JS: `mostrarAlerta('#id', 'texto')` e `ocultarAlerta('#id')`.

### `modal()`

```php
modal(string $id, string $titulo, string $conteudo, string $etiqueta = '', bool $fechavel = true,
      bool $aberto = false, string $rodape = '', string $tamanho = 'md'): string  // tamanho: 'md' | 'lg'
```

- `fechavel: false` remove o X e impede fechar clicando fora (ex.: troca de senha obrigatória).
- `aberto: true` já nasce visível.
- Abrir: qualquer elemento com `data-abrir-modal="idDoModal"` ou `abrirModal('id')` no JS.
- Fechar: qualquer elemento com `data-fechar-modal` dentro do modal ou `fecharModal('id')`.

### `cartaoIndicador()` e `cabecalhoPortal()`

```php
cartaoIndicador(string $rotulo, string $valor, string $detalhe = ''): string
cabecalhoPortal(string $nome, string $cpf): string   // topo do portal com botão Sair
```

### Utilitários (`base.php`)

| Função | Uso |
|---|---|
| `escapar($texto)` | **Use sempre** em qualquer dado vindo do banco/API/usuário antes de colocar em HTML. |
| `montarAtributos(['id' => 'x', 'required' => true])` | Gera ` id="x" required`. `false` e `null` são omitidos. |
| `imagem($arquivo, $alt, $classe)` | `<img>` de um arquivo da pasta de imagens. |

> Os componentes já escapam os próprios parâmetros. Já o HTML manual (heredoc) **não**: escape com `escapar()` ao interpolar dados.

## 6. HTML manual

Quando não houver componente, escreva HTML e passe para `html()`. Para interpolar, use heredoc com variáveis simples:

```php
$nome = escapar($nomeUsuario);

$pagina->html(<<<HTML
    <div class="rounded-3xl bg-marca-primaria p-8 text-white">
        <h2 class="text-3xl font-bold">Bem-vindo, {$nome}.</h2>
    </div>
HTML);
```

Em JS use **nowdoc** (`<<<'JS'`, com aspas simples) para o PHP não tentar interpretar o `$` do jQuery:

```php
$pagina->js(<<<'JS'
    $('#botao').on('click', function () { /* ... */ });
JS);
```

## 7. Funções JS disponíveis em toda página

| Função | Descrição |
|---|---|
| `URL_BASE` | Prefixo do projeto. Sempre use: `URL_BASE + '/dashboard/'`. |
| `postarJson(url, dados)` | POST AJAX. Retorna promise (`.done` com o JSON se `sucesso`; `.fail` com a mensagem de erro). |
| `mostrarAlerta(seletor, texto)` / `ocultarAlerta(seletor)` | Controla um `alerta()`. |
| `definirCarregando(seletorBotao, bool, textoCarregando)` | Liga/desliga spinner e desabilita o botão (precisa de `carregando: true` no `botao()`). |
| `abrirModal(id)` / `fecharModal(id)` | Controla modais. |

Padrão de envio de formulário:

```php
$pagina->js(<<<'JS'
    $('#meuForm').on('submit', function (evento) {
        evento.preventDefault();
        definirCarregando('#botaoEnviar', true, 'Enviando...');

        postarJson(URL_BASE + '/pagina/endpoint.php', $(this).serialize())
            .done(function (retorno) { window.location.href = retorno.redirecionar; })
            .fail(function (mensagem) {
                mostrarAlerta('#mensagem', mensagem);
                definirCarregando('#botaoEnviar', false);
            });
    });
JS);
```

## 8. Endpoints (JSON)

Arquivos auxiliares na pasta da página. Padrão de resposta: `sucesso` (bool), `mensagem` (string) e, opcionalmente, `redirecionar`.

```php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['sucesso' => true, 'mensagem' => 'Salvo.', 'redirecionar' => APP_URL_BASE . '/dashboard/']);
```

Consultas ao banco: `dbQuery($sql, ['parametro' => $valor])`. SELECT devolve array de linhas; INSERT/UPDATE/DELETE devolvem `true`/`false`. Sempre use parâmetros nomeados, nunca concatene valores no SQL.

## 9. Criando um componente novo

1. Escolha o arquivo em `includes/componentes/` (ou crie um e inclua-o em `includes/framework.php`).
2. Escreva uma função em português que **devolve string**.
3. Use `escapar()` e `montarAtributos()`.
4. Se precisar de JS, registre com chave para não duplicar:

```php
function etiqueta(string $texto, string $cor = 'verde'): string
{
    $cores = [
        'verde' => 'bg-green-100 text-green-800',
        'vermelho' => 'bg-red-100 text-red-800',
    ];

    return '<span class="rounded-full px-3 py-1 text-xs font-semibold ' . ($cores[$cor] ?? $cores['verde']) . '">'
        . escapar($texto) . '</span>';
}

// com JS:
Pagina::atual()?->js("$(document).on('click', '[data-x]', function () { ... });", 'componente-x');
```

---

## 10. Dicas de Tailwind

O Tailwind é carregado via CDN: basta escrever as classes no HTML.

### Regra de ouro: classes por extenso

Escreva sempre a classe completa. **Não monte classes por concatenação.**

```php
// ERRADO - o Tailwind não encontra a classe se um dia for compilado
$classe = 'bg-' . $cor . '-100';

// CERTO
$cores = ['verde' => 'bg-green-100', 'vermelho' => 'bg-red-100'];
$classe = $cores[$cor];
```

### Cores do projeto (`marca`)

As cores são definidas em português, uma única vez, em `includes/pagina.php` (`tailwind.config`, chave `marca`).

| Classe | Cor | Uso típico |
|---|---|---|
| `bg-marca-escura` / `text-marca-escura` | `#0f2418` | Fundos de overlay |
| `bg-marca-primaria` | `#183724` | Botões, títulos, destaque principal |
| `bg-marca-secundaria` | `#234e34` | Hover do primário, gradientes |
| `bg-marca-destaque` | `#b69d5e` | Dourado, detalhes |
| `bg-marca-destaque-hover` | `#9e864b` | Hover do dourado |
| `bg-marca-clara` | `#f2f4ee` | Fundo de página |

Funcionam com qualquer prefixo: `text-`, `border-`, `ring-`, `from-`, `to-`, `shadow-`. Transparência com `/`: `bg-marca-primaria/10`, `border-marca-primaria/20`.

Para criar uma cor nova, acrescente uma chave em `marca` no `tailwind.config` (ex.: `aviso: '#d97706'`) e use `bg-marca-aviso`. Para trocar uma cor do portal inteiro, altere o hexadecimal ali.

### Espaçamento (escala de 4px)

| Classe | Valor |
|---|---|
| `p-2` / `m-2` | 8px |
| `p-4` / `m-4` | 16px |
| `p-6` / `m-6` | 24px |
| `p-8` / `m-8` | 32px |

Direções: `px-` (horizontal), `py-` (vertical), `pt-`, `pb-`, `pl-`, `pr-`. Espaço entre filhos: `space-y-4` (vertical) e `gap-4` (em flex/grid).

### Layout

```html
<!-- Centralizar conteúdo com largura máxima -->
<main class="mx-auto max-w-7xl px-6 py-10">

<!-- Linha com itens alinhados e espaço entre eles -->
<div class="flex items-center justify-between gap-3">

<!-- Grade: 1 coluna no celular, 3 a partir de md -->
<div class="grid gap-5 md:grid-cols-3">

<!-- Centralizar na tela inteira -->
<body class="min-h-screen flex items-center justify-center">
```

### Responsividade (mobile first)

Classe sem prefixo vale para tudo. Com prefixo, vale **a partir** daquela largura.

| Prefixo | A partir de |
|---|---|
| `sm:` | 640px |
| `md:` | 768px |
| `lg:` | 1024px |
| `xl:` | 1280px |

Exemplo: `flex-col sm:flex-row` (empilha no celular, lado a lado em telas maiores).

### Texto

| Classe | Efeito |
|---|---|
| `text-sm` / `text-base` / `text-xl` / `text-3xl` | Tamanho |
| `font-medium` / `font-semibold` / `font-bold` | Peso |
| `uppercase tracking-[0.2em]` | Caixa alta com espaçamento entre letras |
| `text-gray-500` / `text-gray-700` | Tons de cinza (mais claro → mais escuro) |

### Bordas, sombras e cantos

`rounded-xl`, `rounded-2xl`, `rounded-3xl`, `rounded-full` · `border border-gray-200` · `shadow-sm`, `shadow-lg`, `shadow-2xl`.

Valores arbitrários entre colchetes: `rounded-[28px]`, `tracking-[0.25em]`, `w-[320px]`.

### Estados

| Prefixo | Quando |
|---|---|
| `hover:` | Mouse em cima: `hover:bg-marca-secundaria` |
| `focus:` | Campo focado: `focus:ring-2 focus:ring-marca-primaria/20` |
| `disabled:` | Desabilitado: `disabled:opacity-60` |
| `transition` | Suaviza mudanças de cor/sombra |

### Esconder e mostrar com JS

A classe `hidden` esconde (`display: none`). Com jQuery:

```js
$('#caixa').addClass('hidden');
$('#caixa').removeClass('hidden');
$('#caixa').toggleClass('hidden');
```

Cuidado: um elemento com `hidden` e `flex` ao mesmo tempo pode se comportar de forma inesperada. Troque uma pela outra (é o que `abrirModal`/`fecharModal` fazem).

### Quando usar `$pagina->css()` em vez de Tailwind

Para efeitos que não cabem bem em classes, como gradientes específicos ou sombras personalizadas:

```php
$pagina->css(<<<'CSS'
    body { background: linear-gradient(135deg, rgba(24,55,36,0.08), rgba(182,157,94,0.12)); }
    .brilho-marca { box-shadow: 0 18px 45px rgba(24, 55, 36, 0.18); }
CSS);
```

Documentação oficial para consulta: https://tailwindcss.com/docs

---

## 11. Checklist para criar uma página nova

- [ ] Criar a pasta `nome_da_pagina/` com `index.php`.
- [ ] Incluir `includes/framework.php` (e `database.php` se usar banco).
- [ ] Validar sessão e fazer as consultas com `dbQuery` no topo.
- [ ] Criar `new Pagina(...)` **antes** de qualquer `campo()`/`modal()`.
- [ ] Escapar com `escapar()` todo dado dinâmico em HTML manual.
- [ ] Nomes de funções, variáveis e classes PHP em português.
- [ ] Links e `fetch` sempre com `APP_URL_BASE` / `URL_BASE`.
- [ ] Classes Tailwind escritas por extenso.
- [ ] Terminar com `$pagina->exibir()`.

## 12. Exemplos reais

- [login/index.php](../login/index.php): formulário, campos, alerta, botão com spinner, modal informativo, CSS e JS próprios.
- [dashboard/index.php](../dashboard/index.php): cabeçalho, cartões, HTML manual e modal obrigatório (não fechável).
