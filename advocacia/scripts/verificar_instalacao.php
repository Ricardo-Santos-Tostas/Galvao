<?php
/**
 * Verifica se MySQL, tabela e registros estão OK após instalação.
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/ProcessoModel.php';

try {
    $model = new ProcessoModel();
    $total = $model->contarProcessos();
    $cfg = getDbConfig();

    echo "Conexao OK\n";
    echo "Banco: {$cfg['database']}\n";
    echo "Host: {$cfg['host']}\n";
    echo "Timezone: " . date_default_timezone_get() . "\n";
    echo "Registros na tabela planilha1: {$total}\n";

    if ($total <= 0) {
        fwrite(STDERR, "AVISO: Nenhum registro encontrado.\n");
        exit(1);
    }

    $teste = $model->autocomplete('A', 'geral', 1);
    echo "Teste busca: " . (count($teste) > 0 ? 'OK' : 'FALHOU') . "\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "ERRO: " . $e->getMessage() . "\n");
    exit(1);
}
