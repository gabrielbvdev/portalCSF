-- =========================================================================
-- BANCO DE DADOS PRINCIPAL: portal_csf
-- =========================================================================
CREATE DATABASE portal_csf;
USE portal_csf;

-- Tabela de Cache/Administração de Clientes
CREATE TABLE adm_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    nome_completo VARCHAR(255) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    status ENUM('ativo', 'inadimplente', 'cancelado') DEFAULT 'ativo',
    plano_atual VARCHAR(100),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL
);

-- Tabela de Saldo de Gamificação
CREATE TABLE gamificacao_saldo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    saldo_moedas INT DEFAULT 0,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES adm_clientes(id) ON DELETE CASCADE
);

-- Tabela de Extrato de Gamificação
CREATE TABLE gamificacao_extrato (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    tipo_movimentacao ENUM('credito', 'debito') NOT NULL,
    quantidade_moedas INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES adm_clientes(id) ON DELETE CASCADE
);

-- Tabelas para Vitrine de Upsell
CREATE TABLE vitrine_produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_produto VARCHAR(255) NOT NULL,
    tipo_produto ENUM('plano_familiar', 'jazigo', 'ossuario', 'servico_adicional') NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE
);

CREATE TABLE vitrine_pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_vitrine_produto INT NOT NULL,
    status_pedido ENUM('pendente', 'handoff_iniciado', 'concluido', 'falha') DEFAULT 'pendente',
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES adm_clientes(id),
    FOREIGN KEY (id_vitrine_produto) REFERENCES vitrine_produtos(id)
);

-- Tabela de Produtos Contratados pelo Cliente
CREATE TABLE adm_clientes_produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_vitrine_produto INT NOT NULL,
    status ENUM('ativo', 'suspenso', 'cancelado') DEFAULT 'ativo',
    data_contratacao DATE NOT NULL,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES adm_clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_vitrine_produto) REFERENCES vitrine_produtos(id)
);

-- Tabela de Gestão de Pagamentos (Faturas)
CREATE TABLE adm_pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_produto_contratado INT,
    descricao_fatura VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE NULL,
    status_pagamento ENUM('pendente', 'pago', 'vencido', 'cancelado') DEFAULT 'pendente',
    metodo_pagamento ENUM('pix', 'boleto', 'cartao_credito', 'dinheiro') NULL,
    codigo_pix TEXT,
    link_boleto VARCHAR(255),
    data_geracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES adm_clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_produto_contratado) REFERENCES adm_clientes_produtos(id) ON DELETE SET NULL
);

-- =========================================================================
-- BANCO DE DADOS DEDICADO PARA LOGS: logs_csf
-- =========================================================================
CREATE DATABASE logs_csf;
USE logs_csf;

-- Tabela de Auditoria de Logs (Isolada para controle de armazenamento)
CREATE TABLE auditoria_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL, -- Referência lógica ao banco portal_csf.adm_clientes
    modulo_acessado VARCHAR(100) NOT NULL,
    acao_realizada VARCHAR(255) NOT NULL,
    ip_origem VARCHAR(45),
    dispositivo_info TEXT,
    data_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cliente (id_cliente),
    INDEX idx_data (data_log)
);

-- Tabela de Logs Operacionais do Portal
CREATE TABLE adm_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NULL, -- Referência lógica ao banco portal_csf.adm_clientes
    modulo VARCHAR(100) NOT NULL,
    acao VARCHAR(255) NOT NULL,
    detalhes TEXT,
    ip_origem VARCHAR(45),
    data_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cliente_adm (id_cliente),
    INDEX idx_data_adm (data_log)
);