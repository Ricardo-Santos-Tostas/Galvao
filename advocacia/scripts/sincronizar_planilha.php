<?php
/**
 * Sincroniza dados do Access (CSV ou SQLite) com o MySQL sem apagar anexos.
 *
 * - Atualiza registros existentes pelo número CADASTRO
 * - Insere cadastros novos do Access
 * - Preserva FOTO, DOCUMENTO e ÁREA já gravados no sistema novo
 *
 * Uso:
 *   php scripts/sincronizar_planilha.php --fonte=csv --arquivo=import/planilha_access.csv
 *   php scripts/sincronizar_planilha.php --fonte=sqlite --arquivo=../sistema.db
 *   php scripts/sincronizar_planilha.php --fonte=csv --arquivo=import/planilha_access.csv --dry-run
 */

declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/config/database.php';
require_once $baseDir . '/models/TelefoneBr.php';

const COLUNAS_PRESERVAR = [
    'AREA',
    'FOTO',
    'FOTO_TIPO',
    'DOCUMENTO',
    'DOCUMENTO_NOME',
    'DOCUMENTO_TIPO',
];

function uso(): void
{
    fwrite(STDERR, <<<TXT
Sincronização Access → MySQL (sem apagar fotos/documentos)

Opções:
  --fonte=csv|sqlite     Origem dos dados (obrigatório)
  --arquivo=CAMINHO      Arquivo de entrada (obrigatório)
  --dry-run              Só simula, não grava no MySQL
  --confirmar            Grava no MySQL (obrigatório sem --dry-run)

Exemplos:
  php scripts/sincronizar_planilha.php --fonte=csv --arquivo=import/planilha_access.csv --dry-run
  php scripts/sincronizar_planilha.php --fonte=csv --arquivo=import/planilha_access.csv --confirmar
  php scripts/sincronizar_planilha.php --fonte=sqlite --arquivo=../sistema.db --confirmar

TXT);
}

/** @return array<string, string> */
function parseArgs(array $argv): array
{
    $args = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (!str_starts_with($arg, '--')) {
            continue;
        }
        $partes = explode('=', substr($arg, 2), 2);
        $args[$partes[0]] = $partes[1] ?? '1';
    }

    return $args;
}

function colunasSincronizaveis(): array
{
    return array_values(array_filter(
        [
            'CADASTRO', 'RECLAMANTE', 'DATA_NASC', 'ENDERE_O',
            'FONE_RTE', 'FONE_RTE_2_', 'FONE_RTE_3_', 'FONE_RTE_4_',
            'FALAR_COM_FONE_1_', 'FALAR_COM_FONE_2_', 'FALAR_COM_FONE_3_', 'FALAR_COM_FONE_4_',
            'RECLAMADA', 'END_RDA', 'JUNTA', 'PROC',
            'DIA_AUD', 'HORA_AUD', 'PRA_A_DIA', 'PRA_A_HORA',
            'ANDAMENTO', 'CTPS', 'IDENTIDADE', 'CPF',
            'COL_2__RECLAMADA', 'END_RDA_1', 'cxpra_a',
        ],
        static fn(string $col): bool => !in_array($col, COLUNAS_PRESERVAR, true)
    ));
}

function normalizarChaveColuna(string $chave): string
{
    $chave = trim($chave);
    $chave = preg_replace('/^\xEF\xBB\xBF/', '', $chave) ?? $chave;
    $chave = trim($chave, " \t\n\r\0\x0B\"'");

    $mapa = [
        'cadastro' => 'CADASTRO',
        'reclamante' => 'RECLAMANTE',
        'data_nasc' => 'DATA_NASC',
        'endere_o' => 'ENDERE_O',
        'fone_rte' => 'FONE_RTE',
        'fone_rte_2_' => 'FONE_RTE_2_',
        'fone_rte_3_' => 'FONE_RTE_3_',
        'fone_rte_4_' => 'FONE_RTE_4_',
        'falar_com_fone_1_' => 'FALAR_COM_FONE_1_',
        'falar_com_fone_2_' => 'FALAR_COM_FONE_2_',
        'falar_com_fone_3_' => 'FALAR_COM_FONE_3_',
        'falar_com_fone_4_' => 'FALAR_COM_FONE_4_',
        'reclamada' => 'RECLAMADA',
        'end_rda' => 'END_RDA',
        'junta' => 'JUNTA',
        'proc' => 'PROC',
        'dia_aud' => 'DIA_AUD',
        'hora_aud' => 'HORA_AUD',
        'pra_a_dia' => 'PRA_A_DIA',
        'pra_a_hora' => 'PRA_A_HORA',
        'andamento' => 'ANDAMENTO',
        'ctps' => 'CTPS',
        'identidade' => 'IDENTIDADE',
        'cpf' => 'CPF',
        'col_2__reclamada' => 'COL_2__RECLAMADA',
        'end_rda_1' => 'END_RDA_1',
        'cxpra_a' => 'cxpra_a',
    ];

    $lower = mb_strtolower($chave, 'UTF-8');

    if (isset($mapa[$lower])) {
        return $mapa[$lower];
    }

    return strtoupper($chave);
}

function normalizarValor(string $coluna, mixed $valor): ?string
{
    if ($valor === null) {
        return null;
    }

    $texto = trim((string) $valor);
    if ($texto === '') {
        return null;
    }

    if (in_array($coluna, TelefoneBr::campos(), true)) {
        return TelefoneBr::normalizar($texto);
    }

    return $texto;
}

/** @return Generator<int, array<string, ?string>> */
function lerCsv(string $caminho): Generator
{
    $handle = fopen($caminho, 'rb');
    if ($handle === false) {
        throw new RuntimeException("Não foi possível abrir o CSV: {$caminho}");
    }

    $primeiraLinha = fgets($handle);
    if ($primeiraLinha === false) {
        fclose($handle);
        throw new RuntimeException('CSV vazio.');
    }

    $delimitador = substr_count($primeiraLinha, ';') > substr_count($primeiraLinha, ',') ? ';' : ',';
    rewind($handle);

    $cabecalho = fgetcsv($handle, 0, $delimitador);
    if ($cabecalho === false) {
        fclose($handle);
        throw new RuntimeException('Cabeçalho do CSV inválido.');
    }

    $colunas = [];
    foreach ($cabecalho as $idx => $nome) {
        $colunas[$idx] = normalizarChaveColuna((string) $nome);
    }

    while (($linha = fgetcsv($handle, 0, $delimitador)) !== false) {
        if ($linha === [null] || $linha === []) {
            continue;
        }

        $registro = [];
        foreach ($colunas as $idx => $coluna) {
            $bruto = $linha[$idx] ?? null;
            if ($bruto !== null && !mb_check_encoding((string) $bruto, 'UTF-8')) {
                $bruto = mb_convert_encoding((string) $bruto, 'UTF-8', 'Windows-1252');
            }
            $registro[$coluna] = $bruto === null || $bruto === '' ? null : (string) $bruto;
        }

        yield $registro;
    }

    fclose($handle);
}

/** @return Generator<int, array<string, mixed>> */
function lerSqlite(string $caminho): Generator
{
    if (!file_exists($caminho)) {
        throw new RuntimeException("SQLite não encontrado: {$caminho}");
    }

    $sqlite = new PDO('sqlite:' . $caminho, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $tabelas = ['Planilha1', 'planilha1', TABELA];
    $tabela = null;
    foreach ($tabelas as $nome) {
        $existe = $sqlite->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name = " . $sqlite->quote($nome)
        )->fetchColumn();
        if ($existe) {
            $tabela = $nome;
            break;
        }
    }

    if ($tabela === null) {
        throw new RuntimeException('Tabela Planilha1 não encontrada no SQLite.');
    }

    $stmt = $sqlite->query('SELECT * FROM "' . str_replace('"', '""', $tabela) . '"');
    while ($row = $stmt->fetch()) {
        $registro = [];
        foreach ($row as $chave => $valor) {
            $registro[normalizarChaveColuna((string) $chave)] = $valor;
        }
        yield $registro;
    }
}

function prepararRegistro(array $bruto, array $colunasSync): ?array
{
    $cadastro = isset($bruto['CADASTRO']) ? (int) $bruto['CADASTRO'] : 0;
    if ($cadastro <= 0) {
        return null;
    }

    $registro = ['CADASTRO' => $cadastro];
    foreach ($colunasSync as $coluna) {
        if ($coluna === 'CADASTRO') {
            continue;
        }
        $registro[$coluna] = normalizarValor($coluna, $bruto[$coluna] ?? null);
    }

    return $registro;
}

function registroExiste(PDO $db, string $tabela, int $id): bool
{
    $sql = 'SELECT 1 FROM ' . sqlId($tabela) . ' WHERE ' . sqlId('CADASTRO') . ' = :id LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->execute(['id' => $id]);

    return (bool) $stmt->fetchColumn();
}

function inserirRegistro(PDO $db, string $tabela, array $registro, array $colunasSync): void
{
    $cols = array_map('sqlId', $colunasSync);
    $placeholders = implode(', ', array_map(static fn(string $c): string => ':' . $c, $colunasSync));
    $sql = 'INSERT INTO ' . sqlId($tabela) . ' (' . implode(', ', $cols) . ') VALUES (' . $placeholders . ')';
    $stmt = $db->prepare($sql);
    $stmt->execute($registro);
}

function atualizarRegistro(PDO $db, string $tabela, array $registro, array $colunasSync): void
{
    $sets = [];
    $params = ['CADASTRO' => $registro['CADASTRO']];
    foreach ($colunasSync as $coluna) {
        if ($coluna === 'CADASTRO' || in_array($coluna, COLUNAS_PRESERVAR, true)) {
            continue;
        }
        $sets[] = sqlId($coluna) . ' = :' . $coluna;
        $params[$coluna] = $registro[$coluna] ?? null;
    }

    if ($sets === []) {
        return;
    }

    $sql = 'UPDATE ' . sqlId($tabela) . ' SET ' . implode(', ', $sets)
        . ' WHERE ' . sqlId('CADASTRO') . ' = :CADASTRO';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
}

$args = parseArgs($argv);
$fonte = strtolower(trim($args['fonte'] ?? ''));
$arquivo = trim($args['arquivo'] ?? '');
$dryRun = isset($args['dry-run']);
$confirmar = isset($args['confirmar']);

if ($fonte === '' || $arquivo === '') {
    uso();
    exit(1);
}

if (!in_array($fonte, ['csv', 'sqlite'], true)) {
    fwrite(STDERR, "Fonte inválida: {$fonte}\n");
    uso();
    exit(1);
}

$caminho = $arquivo;
if (!preg_match('/^[a-zA-Z]:\\\\|^\\\\/', $arquivo) && !str_starts_with($arquivo, '/')) {
    $caminho = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $arquivo);
}

if (!file_exists($caminho)) {
    fwrite(STDERR, "Arquivo não encontrado: {$caminho}\n");
    exit(1);
}

if (!$dryRun && !$confirmar) {
    fwrite(STDERR, "Para gravar no MySQL, use --confirmar ou simule com --dry-run\n");
    exit(1);
}

$colunasSync = colunasSincronizaveis();
$tabela = TABELA;
$db = getConnection();

echo "============================================\n";
echo "  Sincronização Access → MySQL\n";
echo "============================================\n";
echo "Fonte:   {$fonte}\n";
echo "Arquivo: {$caminho}\n";
echo "Modo:    " . ($dryRun ? 'SIMULAÇÃO (dry-run)' : 'GRAVAR') . "\n\n";

$totalAntes = (int) $db->query('SELECT COUNT(*) FROM ' . sqlId($tabela))->fetchColumn();
echo "Registros no MySQL antes: {$totalAntes}\n\n";

$ler = $fonte === 'csv'
    ? lerCsv($caminho)
    : lerSqlite($caminho);

$stats = [
    'lidos' => 0,
    'ignorados' => 0,
    'novos' => 0,
    'atualizados' => 0,
    'erros' => 0,
];

if (!$dryRun) {
    $db->beginTransaction();
}

try {
    foreach ($ler as $bruto) {
        $stats['lidos']++;
        $registro = prepararRegistro($bruto, $colunasSync);
        if ($registro === null) {
            $stats['ignorados']++;
            continue;
        }

        $id = $registro['CADASTRO'];
        $existe = registroExiste($db, $tabela, $id);

        if ($dryRun) {
            if ($existe) {
                $stats['atualizados']++;
            } else {
                $stats['novos']++;
            }
            continue;
        }

        try {
            if ($existe) {
                atualizarRegistro($db, $tabela, $registro, $colunasSync);
                $stats['atualizados']++;
            } else {
                inserirRegistro($db, $tabela, $registro, $colunasSync);
                $stats['novos']++;
            }
        } catch (PDOException $e) {
            $stats['erros']++;
            fwrite(STDERR, "  Erro CADASTRO {$id}: " . $e->getMessage() . "\n");
        }

        if (($stats['novos'] + $stats['atualizados']) > 0
            && ($stats['novos'] + $stats['atualizados']) % 1000 === 0) {
            echo "  ... " . ($stats['novos'] + $stats['atualizados']) . " processados\n";
        }
    }

    if (!$dryRun) {
        $db->commit();
    }
} catch (Throwable $e) {
    if (!$dryRun && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . "\n");
    exit(1);
}

$totalDepois = $dryRun
    ? $totalAntes + $stats['novos']
    : (int) $db->query('SELECT COUNT(*) FROM ' . sqlId($tabela))->fetchColumn();

echo "\n============================================\n";
echo "  RESULTADO\n";
echo "============================================\n";
echo "Lidos no arquivo:     {$stats['lidos']}\n";
echo "Ignorados (sem ID):   {$stats['ignorados']}\n";
echo "Novos inseridos:      {$stats['novos']}\n";
echo "Atualizados:          {$stats['atualizados']}\n";
echo "Erros:                {$stats['erros']}\n";
echo "MySQL antes:          {$totalAntes}\n";
echo "MySQL depois:         {$totalDepois}\n";

if ($dryRun) {
    echo "\nSimulação concluída. Para aplicar, rode novamente com --confirmar\n";
} else {
    echo "\nSincronização concluída!\n";
    echo "Fotos, documentos e área jurídica do sistema novo foram preservados.\n";
}

exit($stats['erros'] > 0 ? 1 : 0);
