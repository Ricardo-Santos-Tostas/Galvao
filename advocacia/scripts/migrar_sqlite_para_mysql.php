<?php
/**
 * Recria a tabela no MySQL e importa todos os dados do SQLite (sistema.db).
 *
 * Uso: php scripts/migrar_sqlite_para_mysql.php
 */

$baseDir = dirname(__DIR__);
$sqlitePath = dirname($baseDir) . DIRECTORY_SEPARATOR . 'sistema.db';

require_once $baseDir . '/config/database.php';

if (!file_exists($sqlitePath)) {
    fwrite(STDERR, "ERRO: SQLite não encontrado em {$sqlitePath}\n");
    exit(1);
}

$cfg = getDbConfig();

echo "============================================\n";
echo "  Migração SQLite → MySQL\n";
echo "============================================\n\n";

// Conectar sem database para criar o banco
$dsnServer = sprintf(
    'mysql:host=%s;port=%d;charset=%s',
    $cfg['host'],
    (int) $cfg['port'],
    $cfg['charset']
);

try {
    $server = new PDO($dsnServer, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "ERRO ao conectar no MySQL: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Verifique se o MySQL está ligado no painel do XAMPP.\n");
    exit(1);
}

$dbName = $cfg['database'];
$server->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "Banco '{$dbName}' criado/verificado.\n";

$mysql = getConnection();

// Recriar tabela do zero
$mysql->exec('DROP TABLE IF EXISTS ' . sqlId(TABELA));

$mysql->exec('
CREATE TABLE ' . sqlId(TABELA) . ' (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
');

$mysql->exec('CREATE INDEX idx_reclamante ON ' . sqlId(TABELA) . ' (RECLAMANTE(191))');
$mysql->exec('CREATE INDEX idx_reclamada  ON ' . sqlId(TABELA) . ' (RECLAMADA(191))');
$mysql->exec('CREATE INDEX idx_proc       ON ' . sqlId(TABELA) . ' (`PROC`(100))');

echo "Tabela '" . TABELA . "' recriada com sucesso.\n\n";

// Ler SQLite
$sqlite = new PDO('sqlite:' . $sqlitePath, null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$colunas = [
    'CADASTRO', 'RECLAMANTE', 'DATA_NASC', 'ENDERE_O',
    'FONE_RTE', 'FONE_RTE_2_', 'FONE_RTE_3_', 'FONE_RTE_4_',
    'FALAR_COM_FONE_1_', 'FALAR_COM_FONE_2_', 'FALAR_COM_FONE_3_', 'FALAR_COM_FONE_4_',
    'RECLAMADA', 'END_RDA', 'JUNTA', 'PROC',
    'DIA_AUD', 'HORA_AUD', 'PRA_A_DIA', 'PRA_A_HORA',
    'ANDAMENTO', 'CTPS', 'IDENTIDADE', 'CPF',
    'COL_2__RECLAMADA', 'END_RDA_1', 'cxpra_a',
];

$tabela = sqlId(TABELA);
$colsSql = implode(', ', array_map('sqlId', $colunas));
$placeholders = implode(', ', array_map(fn($c) => ':' . $c, $colunas));

$totalSqlite = (int) $sqlite->query('SELECT COUNT(*) FROM "Planilha1"')->fetchColumn();
echo "Registros no SQLite: {$totalSqlite}\n";
echo "Importando...\n";

$sqlInsert = 'INSERT INTO ' . $tabela . ' (' . $colsSql . ') VALUES (' . $placeholders . ')';
$stmtInsert = $mysql->prepare($sqlInsert);
$stmtSelect = $sqlite->query('SELECT * FROM "Planilha1"');

$inseridos = 0;
$erros = 0;
$mysql->beginTransaction();

while ($row = $stmtSelect->fetch()) {
    $params = [];
    foreach ($colunas as $col) {
        $params[$col] = $row[$col] ?? null;
    }

    try {
        $stmtInsert->execute($params);
        $inseridos++;
    } catch (PDOException $e) {
        $erros++;
        fwrite(STDERR, "  Erro CADASTRO {$params['CADASTRO']}: " . $e->getMessage() . "\n");
    }

    if ($inseridos > 0 && $inseridos % 1000 === 0) {
        $mysql->commit();
        $mysql->beginTransaction();
        echo "  ... {$inseridos} registros\n";
    }
}

$mysql->commit();

$totalMysql = (int) $mysql->query('SELECT COUNT(*) FROM ' . $tabela)->fetchColumn();

echo "\n============================================\n";
echo "  MIGRAÇÃO CONCLUÍDA\n";
echo "============================================\n";
echo "SQLite:  {$totalSqlite} registros\n";
echo "MySQL:   {$totalMysql} registros\n";
echo "Erros:   {$erros}\n";

if ($totalMysql === $totalSqlite) {
    echo "\nOK — Todos os dados foram transferidos!\n";
} else {
    echo "\nATENÇÃO — Contagem diferente. Verifique os erros acima.\n";
    exit(1);
}
