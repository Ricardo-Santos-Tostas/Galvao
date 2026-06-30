<?php
/**
 * Cria/atualiza tabela pericias e marca registros antigos como importacao.
 * Novos cadastros via formulário usam ORIGEM = cadastro.
 */
require_once dirname(__DIR__) . '/config/database.php';

$db = getConnection();
$tabelaPericias = 'pericias';

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
    ORIGEM        VARCHAR(20)   NOT NULL DEFAULT \'cadastro\',
    CRIADO_EM     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ATUALIZADO_EM DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pericias_cadastro (CADASTRO),
    INDEX idx_pericias_data (DATA_PERICIA(20)),
    INDEX idx_pericias_origem (ORIGEM)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

echo "Atualizando tabela pericias...\n";
$db->exec($sqlCreate);
echo "  [OK] Tabela pericias pronta\n";

$colunaOrigem = false;
$stmtCols = $db->query('SHOW COLUMNS FROM ' . sqlId($tabelaPericias));
while ($col = $stmtCols->fetch(PDO::FETCH_ASSOC)) {
    if (strcasecmp($col['Field'] ?? '', 'ORIGEM') === 0) {
        $colunaOrigem = true;
        break;
    }
}

if (!$colunaOrigem) {
    echo "Adicionando coluna ORIGEM...\n";
    $db->exec(
        'ALTER TABLE ' . sqlId($tabelaPericias)
        . " ADD COLUMN ORIGEM VARCHAR(20) NOT NULL DEFAULT 'importacao' AFTER ENDERECO"
    );
    $db->exec(
        'ALTER TABLE ' . sqlId($tabelaPericias)
        . ' ADD INDEX idx_pericias_origem (ORIGEM)'
    );

    $marcados = $db->exec(
        'UPDATE ' . sqlId($tabelaPericias) . " SET ORIGEM = 'importacao'"
    );
    echo "  [OK] {$marcados} registros antigos marcados como importacao (nao aparecem na aba)\n";
} else {
    echo "  [OK] Coluna ORIGEM ja existe\n";
}

echo "\nBanco de pericias atualizado. Apenas cadastros novos aparecerao na aba Pericias.\n";
