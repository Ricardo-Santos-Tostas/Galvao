-- Tabela de perícias (independente da pauta de audiências)
USE advocacia;

CREATE TABLE IF NOT EXISTS pericias (
    ID            INT AUTO_INCREMENT PRIMARY KEY,
    CADASTRO      INT           NULL,
    DATA_PERICIA  VARCHAR(50)   NULL,
    HORA_PERICIA  VARCHAR(50)   NULL,
    RECLAMANTE    VARCHAR(500)  NULL,
    CPF           VARCHAR(20)   NULL,
    RECLAMADA     VARCHAR(500)  NULL,
    PROC_NUM      VARCHAR(100)  NULL,
    NOME_PERITO   VARCHAR(255)  NULL,
    ENDERECO      TEXT          NULL,
    ORIGEM        VARCHAR(20)   NOT NULL DEFAULT 'cadastro',
    CRIADO_EM     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ATUALIZADO_EM DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pericias_cadastro (CADASTRO),
    INDEX idx_pericias_data (DATA_PERICIA(20)),
    INDEX idx_pericias_origem (ORIGEM)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migração: marcar registros antigos (importados) para não aparecer na aba
-- ALTER TABLE pericias ADD COLUMN ORIGEM VARCHAR(20) NOT NULL DEFAULT 'importacao' AFTER ENDERECO;
-- UPDATE pericias SET ORIGEM = 'importacao';
