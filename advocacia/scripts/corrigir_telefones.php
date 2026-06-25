<?php
/**
 * Corrige telefones no banco de dados (remove zero extra do sistema antigo).
 *
 * Uso: php scripts/corrigir_telefones.php
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/TelefoneBr.php';

$tabela = sqlId(TABELA);
$db = getConnection();
$campos = TelefoneBr::campos();

echo "============================================\n";
echo " CORRIGIR TELEFONES NO BANCO DE DADOS\n";
echo "============================================\n\n";

$cols = implode(', ', array_map('sqlId', array_merge(['CADASTRO'], $campos)));
$stmt = $db->query("SELECT {$cols} FROM {$tabela}");
$registros = $stmt->fetchAll();

$totalRegistros = count($registros);
$totalAlterados = 0;
$totalCampos = 0;
$exemplos = [];

$updateSets = [];
foreach ($campos as $campo) {
    $updateSets[] = sqlId($campo) . ' = :' . $campo;
}
$sqlUpdate = 'UPDATE ' . $tabela . ' SET ' . implode(', ', $updateSets)
    . ' WHERE ' . sqlId('CADASTRO') . ' = :CADASTRO';
$stmtUpdate = $db->prepare($sqlUpdate);

foreach ($registros as $row) {
    $alterou = false;
    $params = ['CADASTRO' => $row['CADASTRO']];

    foreach ($campos as $campo) {
        $original = $row[$campo] ?? null;
        $normalizado = TelefoneBr::normalizar($original);

        if ($normalizado !== $original) {
            $alterou = true;
            $totalCampos++;

            if (count($exemplos) < 8) {
                $exemplos[] = [
                    'id' => $row['CADASTRO'],
                    'campo' => $campo,
                    'de' => $original,
                    'para' => $normalizado,
                ];
            }
        }

        $params[$campo] = $normalizado;
    }

    if ($alterou) {
        $stmtUpdate->execute($params);
        $totalAlterados++;
    }
}

echo "Registros analisados: {$totalRegistros}\n";
echo "Registros corrigidos: {$totalAlterados}\n";
echo "Campos alterados:     {$totalCampos}\n\n";

if (!empty($exemplos)) {
    echo "Exemplos de correção:\n";
    foreach ($exemplos as $ex) {
        echo "  [{$ex['id']}] {$ex['campo']}\n";
        echo "    Antes:  {$ex['de']}\n";
        echo "    Depois: {$ex['para']}\n";
    }
    echo "\n";
}

echo "============================================\n";
echo " CORRECAO CONCLUIDA\n";
echo "============================================\n";
