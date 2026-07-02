<?php
/**
 * Exporta cadastros do MySQL para CSV (sincronização com o cliente via Git).
 *
 * Uso: php scripts/exportar_para_sync.php
 * Saída: import/dados_servidor.csv
 */
declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/config/database.php';

$saida = $baseDir . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'dados_servidor.csv';
$importDir = dirname($saida);
if (!is_dir($importDir)) {
    mkdir($importDir, 0755, true);
}

$colunas = [
    'CADASTRO', 'RECLAMANTE', 'DATA_NASC', 'ENDERE_O',
    'FONE_RTE', 'FONE_RTE_2_', 'FONE_RTE_3_', 'FONE_RTE_4_',
    'FALAR_COM_FONE_1_', 'FALAR_COM_FONE_2_', 'FALAR_COM_FONE_3_', 'FALAR_COM_FONE_4_',
    'RECLAMADA', 'END_RDA', 'JUNTA', 'PROC',
    'DIA_AUD', 'HORA_AUD', 'PRA_A_DIA', 'PRA_A_HORA',
    'ANDAMENTO', 'CTPS', 'IDENTIDADE', 'CPF',
    'COL_2__RECLAMADA', 'END_RDA_1', 'cxpra_a',
];

$tabela = TABELA;
$db = getConnection();
$colsSql = implode(', ', array_map('sqlId', $colunas));
$sql = 'SELECT ' . $colsSql . ' FROM ' . sqlId($tabela) . ' ORDER BY ' . sqlId('CADASTRO');
$stmt = $db->query($sql);

$handle = fopen($saida, 'wb');
if ($handle === false) {
    fwrite(STDERR, "Não foi possível criar: {$saida}\n");
    exit(1);
}

fwrite($handle, "\xEF\xBB\xBF");
fputcsv($handle, $colunas, ';');

$total = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $linha = [];
    foreach ($colunas as $col) {
        $valor = $row[$col] ?? '';
        $linha[] = $valor === null ? '' : (string) $valor;
    }
    fputcsv($handle, $linha, ';');
    $total++;
}

fclose($handle);

$tamanho = round(filesize($saida) / 1024 / 1024, 2);
echo "============================================\n";
echo " EXPORTAR DADOS PARA SINCRONIZACAO (Git)\n";
echo "============================================\n";
echo "Registros: {$total}\n";
echo "Arquivo:   {$saida}\n";
echo "Tamanho:   {$tamanho} MB\n";
echo "\nEnvie este arquivo no Git antes de atualizar o cliente.\n";
