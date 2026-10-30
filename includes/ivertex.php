<?php

require_once __DIR__ . '/config.php';

function montarCabecalhosIvertex(): array
{
    $cabecalhos = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    $token = trim((string) IVERTEX_TOKEN);
    if ($token !== '') {
        $nomeCabecalho = trim((string) IVERTEX_CABECALHO_TOKEN);
        $nomeCabecalho = $nomeCabecalho !== '' ? $nomeCabecalho : 'token';
        $cabecalhos[] = $nomeCabecalho . ': ' . $token;
    }

    return $cabecalhos;
}

function extrairColecaoIvertex(array $resposta, array $chaves): array
{
    foreach ($chaves as $chave) {
        if (isset($resposta[$chave]) && is_array($resposta[$chave])) {
            return $resposta[$chave];
        }
    }

    foreach (['data', 'result', 'resultado', 'response', 'items', 'beneficiarios', 'carteirinhas', 'dependentes', 'contratos', 'documentos'] as $chave) {
        if (isset($resposta[$chave]) && is_array($resposta[$chave])) {
            return $resposta[$chave];
        }
    }

    if ($resposta === []) {
        return [];
    }

    if (array_is_list($resposta)) {
        return $resposta;
    }

    $chavesDeObjeto = ['status', 'contract_id', 'cpf', 'name', 'nome', 'nome_completo', 'data_nascimento', 'birthdate', 'documento', 'id', 'Id'];
    foreach ($chavesDeObjeto as $chave) {
        if (array_key_exists($chave, $resposta)) {
            return [$resposta];
        }
    }

    foreach ($resposta as $valor) {
        if (is_array($valor)) {
            return array_is_list($valor) ? $valor : [$valor];
        }
    }

    return [];
}

function requisicaoIvertex(string $endpoint, array $dados = [], string $metodo = 'GET'): ?array
{
    $urlBase = trim((string) IVERTEX_URL_BASE);
    if ($urlBase === '') {
        return null;
    }

    $url = rtrim($urlBase, '/') . '/' . ltrim($endpoint, '/');

    if ($metodo === 'GET' && $dados !== []) {
        $url .= '?' . http_build_query($dados);
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => IVERTEX_TIMEOUT_SEGUNDOS,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => montarCabecalhosIvertex(),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    if ($metodo !== 'GET') {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $metodo);
        if ($dados !== []) {
            $json = json_encode($dados, JSON_UNESCAPED_UNICODE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $json ?: '');
        }
    }

    $resposta = curl_exec($curl);
    $codigoHttp = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $erro = curl_error($curl);

    if ($resposta === false || $codigoHttp >= 400) {
        $detalhes = $resposta !== false ? substr((string) $resposta, 0, 500) : ($erro ?: 'HTTP ' . $codigoHttp);
        error_log('Falha na requisição iVertex para ' . $endpoint . ' | HTTP ' . $codigoHttp . ' | ' . $detalhes);
        return null;
    }

    $decodificada = json_decode($resposta, true);
    if (!is_array($decodificada)) {
        error_log('Resposta JSON inválida do iVertex para ' . $endpoint . ': ' . substr((string) $resposta, 0, 1000));
        return null;
    }

    error_log('Resposta iVertex para ' . $endpoint . ': ' . substr(json_encode($decodificada, JSON_UNESCAPED_UNICODE), 0, 1500));
    return $decodificada;
}

function buscarClienteNoIvertex(string $cpf): ?array
{
    $cpfNormalizado = normalizarCpf($cpf);
    if ($cpfNormalizado === '') {
        return null;
    }

    $resposta = requisicaoIvertex(IVERTEX_ENDPOINT_CLIENTE, ['cpf_cnpj_cliente' => $cpfNormalizado], 'GET');
    if ($resposta === null) {
        return null;
    }

    $cliente = $resposta['cliente'] ?? $resposta['data'] ?? $resposta['result'] ?? $resposta;
    if (!is_array($cliente)) {
        return null;
    }

    $nome = $cliente['NomeCompleto'] ?? $cliente['nome_completo'] ?? $cliente['nome'] ?? null;
    if (!$nome) {
        return null;
    }

    return [
        'id_ivertex' => $cliente['Id'] ?? $cliente['id'] ?? $cliente['id_ivertex'] ?? null,
        'cpf' => $cpfNormalizado,
        'nome_completo' => $nome,
        'email' => $cliente['Email'] ?? $cliente['email'] ?? null,
        'telefone' => $cliente['Celular'] ?? $cliente['telefone'] ?? $cliente['celular'] ?? null,
        'data_nascimento' => $cliente['DataNascimento'] ?? $cliente['data_nascimento'] ?? null,
    ];
}

function buscarContratosNoIvertex(string $cpf): array
{
    $cpfNormalizado = normalizarCpf($cpf);
    if ($cpfNormalizado === '') {
        return [];
    }

    $resposta = requisicaoIvertex(IVERTEX_ENDPOINT_CONTRATOS, ['cpf_cnpj_cliente' => $cpfNormalizado], 'GET');
    if ($resposta === null) {
        return [];
    }

    return extrairColecaoIvertex($resposta, ['contratos', 'listaContratos', 'result', 'data', 'resultado']);
}

function buscarDocumentosNoIvertex(string $idContrato): array
{
    if ($idContrato === '') {
        return [];
    }

    $dados = [
        'filtros' => [
            'cpf_cnpj' => '',
            'id_contrato' => (int) $idContrato,
        ],
    ];

    $resposta = requisicaoIvertex(IVERTEX_ENDPOINT_DOCUMENTOS . '?id_contrato=' . (int) $idContrato, $dados, 'POST');
    if ($resposta === null) {
        return [];
    }

    return extrairColecaoIvertex($resposta, ['documentos', 'listaDocumentos', 'result', 'data', 'resultado']);
}

function buscarDependentesNoIvertex(string $cpf): array
{
    $cpfNormalizado = normalizarCpf($cpf);
    if ($cpfNormalizado === '') {
        return [];
    }

    $resposta = requisicaoIvertex(IVERTEX_ENDPOINT_DEPENDENTES, ['criteria' => ['cpf' => $cpfNormalizado]], 'POST');
    if ($resposta === null) {
        return [];
    }

    $dependentes = extrairColecaoIvertex($resposta, ['dependentes', 'beneficiarios', 'carteirinhas', 'listaBeneficiarios', 'listaDependentes', 'result', 'data', 'resultado']);

    $filtrados = [];
    foreach ($dependentes as $dependente) {
        if (!is_array($dependente)) {
            continue;
        }

        $cpfDependente = normalizarCpf((string) (
            $dependente['cpf']
            ?? $dependente['CPF']
            ?? $dependente['document']
            ?? $dependente['documento']
            ?? $dependente['inscricao_ativa']
            ?? $dependente['InscricaoAtiva']
            ?? ''
        ));

        if ($cpfDependente === $cpfNormalizado) {
            continue;
        }

        $filtrados[] = $dependente;
    }

    return $filtrados;
}
