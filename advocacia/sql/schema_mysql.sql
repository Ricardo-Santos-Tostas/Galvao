-- MySQL — Moura Galvão · Sistema de Processos
-- Execute no phpMyAdmin ou: mysql -u root -p < schema_mysql.sql

CREATE DATABASE IF NOT EXISTS advocacia
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE advocacia;

CREATE TABLE IF NOT EXISTS planilha1 (
    CADASTRO          INT NOT NULL,
    RECLAMANTE        VARCHAR(500)  NULL,
    DATA_NASC         VARCHAR(50)   NULL,
    ENDERE_O          VARCHAR(500)  NULL,
    FONE_RTE          VARCHAR(100)  NULL,
    FONE_RTE_2_       VARCHAR(100)  NULL,
    FONE_RTE_3_       VARCHAR(100)  NULL,
    FONE_RTE_4_       VARCHAR(100)  NULL,
    FALAR_COM_FONE_1_ VARCHAR(200)  NULL,
    FALAR_COM_FONE_2_ VARCHAR(200)  NULL,
    FALAR_COM_FONE_3_ VARCHAR(200)  NULL,
    FALAR_COM_FONE_4_ VARCHAR(200)  NULL,
    RECLAMADA         VARCHAR(500)  NULL,
    END_RDA           VARCHAR(500)  NULL,
    JUNTA             VARCHAR(50)   NULL,
    `PROC`            VARCHAR(100)  NULL,
    DIA_AUD           VARCHAR(50)   NULL,
    HORA_AUD          VARCHAR(50)   NULL,
    PRA_A_DIA         VARCHAR(50)   NULL,
    PRA_A_HORA        VARCHAR(50)   NULL,
    ANDAMENTO         TEXT          NULL,
    CTPS              VARCHAR(100)  NULL,
    IDENTIDADE        VARCHAR(100)  NULL,
    CPF               VARCHAR(20)   NULL,
    COL_2__RECLAMADA  VARCHAR(500)  NULL,
    END_RDA_1         VARCHAR(500)  NULL,
    cxpra_a           VARCHAR(100)  NULL,
    PRIMARY KEY (CADASTRO)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_reclamante ON planilha1 (RECLAMANTE(191));
CREATE INDEX idx_reclamada  ON planilha1 (RECLAMADA(191));
CREATE INDEX idx_proc       ON planilha1 (`PROC`(100));
