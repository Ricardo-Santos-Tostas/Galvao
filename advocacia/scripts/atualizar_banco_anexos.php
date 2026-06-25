<?php
/**
 * Adiciona colunas de foto, documento e área jurídica na tabela.
 */
require_once dirname(__DIR__) . '/config/database.php';

$db = getConnection();
$tabela = TABELA;

$colunas = [
    'AREA'           => 'VARCHAR(30) NULL',
    'FOTO'           => 'LONGBLOB NULL',
    'FOTO_TIPO'      => 'VARCHAR(50) NULL',
    'DOCUMENTO'      => 'LONGBLOB NULL',
    'DOCUMENTO_NOME' => 'VARCHAR(255) NULL',
    'DOCUMENTO_TIPO' => 'VARCHAR(100) NULL',
];

echo "Atualizando tabela {$tabela}...\n";

$stmt = $db->query('SHOW COLUMNS FROM ' . sqlId($tabela));
$existentes = array_column($stmt->fetchAll(), 'Field');

foreach ($colunas as $nome => $definicao) {
    if (in_array($nome, $existentes, true)) {
        echo "  [OK] Coluna {$nome} ja existe\n";
        continue;
    }

    $sql = 'ALTER TABLE ' . sqlId($tabela) . ' ADD ' . sqlId($nome) . ' ' . $definicao;
    $db->exec($sql);
    echo "  [+] Coluna {$nome} criada\n";
}

echo "\nBanco atualizado com sucesso!\n";
