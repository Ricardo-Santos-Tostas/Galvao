-- Estrutura do banco SQLite: sistema.db
-- Tabela única importada da planilha Excel original

CREATE TABLE IF NOT EXISTS "Planilha1" (
    "CADASTRO"          INTEGER,
    "RECLAMANTE"        TEXT,
    "DATA_NASC"         TEXT,
    "ENDERE_O"          TEXT,
    "FONE_RTE"          TEXT,
    "FONE_RTE_2_"       TEXT,
    "FONE_RTE_3_"       TEXT,
    "FONE_RTE_4_"       TEXT,
    "FALAR_COM_FONE_1_" TEXT,
    "FALAR_COM_FONE_2_" TEXT,
    "FALAR_COM_FONE_3_" TEXT,
    "FALAR_COM_FONE_4_" TEXT,
    "RECLAMADA"         TEXT,
    "END_RDA"           TEXT,
    "JUNTA"             TEXT,
    "PROC"              TEXT,
    "DIA_AUD"           TEXT,
    "HORA_AUD"          TEXT,
    "PRA_A_DIA"         TEXT,
    "PRA_A_HORA"        TEXT,
    "ANDAMENTO"         TEXT,
    "CTPS"              TEXT,
    "IDENTIDADE"        TEXT,
    "CPF"               TEXT,
    "COL_2__RECLAMADA"  TEXT,
    "END_RDA_1"         TEXT,
    "cxpra_a"           TEXT
);

-- Índices recomendados para melhorar performance da busca
CREATE INDEX IF NOT EXISTS idx_reclamante ON "Planilha1" ("RECLAMANTE");
CREATE INDEX IF NOT EXISTS idx_reclamada  ON "Planilha1" ("RECLAMADA");
CREATE INDEX IF NOT EXISTS idx_proc       ON "Planilha1" ("PROC");
