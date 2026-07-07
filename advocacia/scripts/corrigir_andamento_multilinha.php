<?php
/**
 * Corrige ANDAMENTO com _x000D_ e quebras de linha mistas no banco.
 * Uso: php scripts/corrigir_andamento_multilinha.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/TextoMultilinha.php';

$db = getConnection();
$tabela = sqlId(TABELA);
$coluna = sqlId('ANDAMENTO');
$cadastro = sqlId('CADASTRO');

$sql = 'SELECT ' . $cadastro . ', ' . $coluna . ' FROM ' . $tabela
    . ' WHERE ' . $coluna . " IS NOT NULL AND TRIM(" . $coluna . ") != ''"
    . " AND (" . $coluna . " LIKE '%_x000D_%' OR " . $coluna . " LIKE '%\r%')";

$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$atualizados = 0;

$update = $db->prepare(
    'UPDATE ' . $tabela . ' SET ' . $coluna . ' = :valor WHERE ' . $cadastro . ' = :id'
);

foreach ($rows as $row) {
    $id = (int) $row['CADASTRO'];
    $original = (string) ($row['ANDAMENTO'] ?? '');
    $normalizado = TextoMultilinha::normalizar($original);

    if ($normalizado === null || $normalizado === $original) {
        continue;
    }

    $update->execute(['valor' => $normalizado, 'id' => $id]);
    $atualizados++;
}

echo "Registros corrigidos: {$atualizados}\n";
