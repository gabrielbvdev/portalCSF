<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/ivertex.php';

function mapearStatusPagamento(string $status): string
{
    foreach (['pago', 'pendente', 'vencido', 'Em Atraso', 'A Receber', 'Cancelado'] as $opcao) {
        if (strcasecmp($opcao, trim($status)) === 0) {
            return $opcao;
        }
    }

    return 'pendente';
}

/**
 * Sincroniza as faturas do cliente com o iVertex.
 * - Fatura retornada: é inserida, ou atualizada se já existir.
 * - Fatura do banco que deixou de ser retornada: é marcada como paga.
 * Contratos cuja consulta falhou são ignorados, para não marcar faturas como pagas por engano.
 *
 * @return array{adicionadas: int, pagas: int, falhas: int}
 */
function sincronizarDocumentosDoIvertex(int $idCliente): array
{
    $resumo = ['adicionadas' => 0, 'pagas' => 0, 'falhas' => 0];
    $contratos = dbQuery('SELECT id, codigo_contrato_ivertex FROM adm_clientes_produtos WHERE id_cliente = :id_cliente', ['id_cliente' => $idCliente]);

    foreach ($contratos ?: [] as $contrato) {
        $idContrato = (string) ($contrato['codigo_contrato_ivertex'] ?? '');
        if ($idContrato === '') {
            continue;
        }

        $documentos = consultarDocumentosNoIvertex($idContrato);
        if ($documentos === null) {
            $resumo['falhas']++;
            continue;
        }

        $idsRetornados = [];

        foreach ($documentos as $documento) {
            $idDocumento = (int) ($documento['IdDocumento'] ?? 0);
            if ($idDocumento <= 0) {
                continue;
            }
            $idsRetornados[$idDocumento] = true;

            $partes = array_filter([
                trim((string) ($documento['TipoParcela'] ?? '')),
                trim((string) ($documento['Identificacao'] ?? '')),
            ], fn ($parte) => $parte !== '');
            $descricao = $partes !== [] ? implode(' - ', $partes) : 'Fatura';

            $parametros = [
                'id_produto' => (int) $contrato['id'],
                'descricao' => mb_substr($descricao, 0, 255),
                'valor' => number_format((float) ($documento['ValorTotal'] ?? 0), 2, '.', ''),
                'vencimento' => (string) ($documento['DataVencimento'] ?? date('Y-m-d')),
                'status' => mapearStatusPagamento((string) ($documento['Status'] ?? '')),
            ];

            $existentes = dbQuery(
                'SELECT id FROM adm_pagamentos WHERE id_cliente = :id_cliente AND id_documento_ivertex = :id_documento LIMIT 1',
                ['id_cliente' => $idCliente, 'id_documento' => $idDocumento]
            );

            if (!empty($existentes)) {
                dbQuery(
                    'UPDATE adm_pagamentos SET id_produto_contratado = :id_produto, descricao_fatura = :descricao, valor = :valor, data_vencimento = :vencimento, status_pagamento = :status WHERE id = :id',
                    $parametros + ['id' => (int) $existentes[0]['id']]
                );
                continue;
            }

            dbQuery(
                'INSERT INTO adm_pagamentos (id_documento_ivertex, id_cliente, id_produto_contratado, descricao_fatura, valor, data_vencimento, status_pagamento) VALUES (:id_documento, :id_cliente, :id_produto, :descricao, :valor, :vencimento, :status)',
                $parametros + ['id_documento' => $idDocumento, 'id_cliente' => $idCliente]
            );
            $resumo['adicionadas']++;
        }

        $abertas = dbQuery(
            "SELECT id, id_documento_ivertex FROM adm_pagamentos WHERE id_cliente = :id_cliente AND id_produto_contratado = :id_produto AND id_documento_ivertex IS NOT NULL AND LOWER(status_pagamento) NOT IN ('pago', 'cancelado')",
            ['id_cliente' => $idCliente, 'id_produto' => (int) $contrato['id']]
        );

        foreach ($abertas ?: [] as $aberta) {
            if (isset($idsRetornados[(int) $aberta['id_documento_ivertex']])) {
                continue;
            }

            dbQuery("UPDATE adm_pagamentos SET status_pagamento = 'pago' WHERE id = :id", ['id' => (int) $aberta['id']]);
            $resumo['pagas']++;
        }
    }

    return $resumo;
}
