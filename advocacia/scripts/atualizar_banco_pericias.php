<?php
/**
 * Cria a tabela pericias e importa registros iniciais a partir de audiências.
 */
require_once dirname(__DIR__) . '/config/database.php';

$db = getConnection();
$tabelaProcessos = TABELA;
$tabelaPericias = 'pericias';

function colRef(string $alias, string $column): string
{
    return sqlId($alias) . '.' . sqlId($column);
}

$sqlCreate = '
CREATE TABLE IF NOT EXISTS ' . sqlId($tabelaPericias) . ' (
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
    CRIADO_EM     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ATUALIZADO_EM DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pericias_cadastro (CADASTRO),
    INDEX idx_pericias_data (DATA_PERICIA(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

echo "Criando tabela pericias...\n";
$db->exec($sqlCreate);
echo "  [OK] Tabela pericias pronta\n";

$total = (int) $db->query('SELECT COUNT(*) FROM ' . sqlId($tabelaPericias))->fetchColumn();

if ($total === 0) {
    echo "Importando pericias a partir de audiencias cadastradas...\n";

    $sqlImport = '
        INSERT INTO ' . sqlId($tabelaPericias) . ' (
            CADASTRO, DATA_PERICIA, HORA_PERICIA, RECLAMANTE, CPF,
            RECLAMADA, PROC_NUM, NOME_PERITO, ENDERECO
        )
        SELECT
            ' . colRef('p', 'CADASTRO') . ',
            ' . colRef('p', 'DIA_AUD') . ',
            ' . colRef('p', 'HORA_AUD') . ',
            ' . colRef('p', 'RECLAMANTE') . ',
            ' . colRef('p', 'CPF') . ',
            ' . colRef('p', 'RECLAMADA') . ',
            ' . colRef('p', 'PROC') . ',
            ' . colRef('p', 'JUNTA') . ',
            ' . colRef('p', 'ANDAMENTO') . '
        FROM ' . sqlId($tabelaProcessos) . ' p
        WHERE ' . colRef('p', 'DIA_AUD') . " IS NOT NULL
          AND TRIM(" . colRef('p', 'DIA_AUD') . ") != ''";

    $inseridos = $db->exec($sqlImport);
    echo "  [+] {$inseridos} pericias importadas\n";
} else {
    echo "  [OK] Tabela ja possui {$total} registros — importacao inicial ignorada\n";
}

echo "\nBanco de pericias atualizado com sucesso!\n";
