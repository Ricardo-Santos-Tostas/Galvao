<?php
/**
 * Conexão MySQL via PDO.
 * Credenciais em config.local.php (copie de config.local.php.example).
 */

define('TABELA', 'planilha1');

$appTimezone = 'America/Bahia';
if (file_exists(__DIR__ . '/config.local.php')) {
    $localCfg = require __DIR__ . '/config.local.php';
    if (is_array($localCfg) && !empty($localCfg['timezone'])) {
        $appTimezone = $localCfg['timezone'];
    }
}
date_default_timezone_set($appTimezone);

function getDbConfig(): array
{
    $defaults = [
        'host'     => 'localhost',
        'port'     => 3306,
        'database' => 'advocacia',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ];

    $localFile = __DIR__ . '/config.local.php';
    if (file_exists($localFile)) {
        $local = require $localFile;
        if (is_array($local)) {
            return array_merge($defaults, $local);
        }
    }

    return $defaults;
}

function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $cfg = getDbConfig();

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            (int) $cfg['port'],
            $cfg['database'],
            $cfg['charset']
        );

        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}

/** Escapa identificador SQL (nome de tabela/coluna). */
function sqlId(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}
