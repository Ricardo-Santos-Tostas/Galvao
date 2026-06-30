<?php
/**
 * Página leve só para impressão.
 */

require_once __DIR__ . '/models/ProcessoModel.php';
require_once __DIR__ . '/models/PericiaModel.php';
require_once __DIR__ . '/models/LogModel.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/views/imprimir/pauta_helpers.php';

Auth::requerLogin();

$tipo = $_GET['tipo'] ?? 'pericias';

$dataInicioRaw = trim($_GET['data_inicio'] ?? '');
$dataFimRaw    = trim($_GET['data_fim'] ?? '');
$dataInicio    = ProcessoModel::parseDataFiltro($dataInicioRaw);
$dataFim       = ProcessoModel::parseDataFiltro($dataFimRaw);

if ($dataInicio && $dataFim && $dataInicio > $dataFim) {
    [$dataInicio, $dataFim] = [$dataFim, $dataInicio];
}

if ($tipo === 'audiencias') {
    Auth::requerModulo('pauta_audiencias', 'ver');

    if (!$dataInicio && !$dataFim) {
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head><meta charset="UTF-8"><title>Imprimir pauta</title></head>
        <body style="font-family:Arial;padding:40px;text-align:center;">
            <h2>Informe a data antes de imprimir</h2>
            <p>Para imprimir a pauta, use o filtro de <strong>data inicial</strong> e/ou <strong>data final</strong> na tela anterior.</p>
            <p>Isso evita travamento do navegador com muitos registros.</p>
            <button type="button" onclick="window.close()">Fechar</button>
        </body>
        </html>
        <?php
        exit;
    }

    $model = new ProcessoModel();
    $registros = $model->pautaAudiencias($dataInicio, $dataFim);
    $gruposPorData = pautaEnriquecerComFotos($model, pautaAgruparPorData($registros));

    include __DIR__ . '/views/imprimir/pauta_audiencias.php';
    exit;
}

$periodoTexto = 'Todos os registros';
if ($dataInicio && $dataFim) {
    $periodoTexto = ProcessoModel::formatarDataFiltro($dataInicio)
        . ' a ' . ProcessoModel::formatarDataFiltro($dataFim);
} elseif ($dataInicio) {
    $periodoTexto = 'A partir de ' . ProcessoModel::formatarDataFiltro($dataInicio);
} elseif ($dataFim) {
    $periodoTexto = 'Até ' . ProcessoModel::formatarDataFiltro($dataFim);
}

$titulo = 'Relatório';
$colunas = [];
$rotulos = [];
$registros = [];

if ($tipo === 'pericias') {
    Auth::requerModulo('pericias', 'ver');
    $titulo = 'Perícias';
    $model = new PericiaModel();
    $registros = $model->listar($dataInicio, $dataFim);
    $colunas = ['DATA_PERICIA', 'HORA_PERICIA', 'RECLAMANTE', 'CPF', 'RECLAMADA', 'PROC_NUM', 'NOME_PERITO', 'ENDERECO'];
    $rotulos = [
        'DATA_PERICIA' => 'Data-P',
        'HORA_PERICIA' => 'Hora',
        'RECLAMANTE'   => 'Reclamante',
        'CPF'          => 'CPF',
        'RECLAMADA'    => 'Reclamada',
        'PROC_NUM'     => 'Nº Processo',
        'NOME_PERITO'  => 'Nome do Perito',
        'ENDERECO'     => 'Endereço',
    ];
} elseif ($tipo === 'reclamante') {
    Auth::requerModulo('pauta_reclamante', 'ver');
    if (!$dataInicio && !$dataFim) {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="pt-BR"><body style="font-family:Arial;padding:40px;text-align:center;">';
        echo '<h2>Informe a data antes de imprimir</h2>';
        echo '<p>Use o filtro de data na tela anterior.</p>';
        echo '<button type="button" onclick="window.close()">Fechar</button></body></html>';
        exit;
    }
    $titulo = 'Pauta Reclamante';
    $model = new ProcessoModel();
    $registros = $model->pautaReclamante($dataInicio, $dataFim);
    $colunas = ['CADASTRO', 'RECLAMANTE', 'CPF', 'RECLAMADA', 'PROC', 'DIA_AUD', 'HORA_AUD'];
} elseif ($tipo === 'log') {
    Auth::requerAdmin();
    $titulo = 'Log de Atividades';
    $usuarioId  = (int) ($_GET['usuario_id'] ?? 0);
    $acaoFiltro = trim($_GET['acao'] ?? '');
    $busca      = trim($_GET['busca'] ?? '');
    $model = new LogModel();
    $registros = $model->listar(
        $dataInicio,
        $dataFim,
        $usuarioId > 0 ? $usuarioId : null,
        $acaoFiltro !== '' ? $acaoFiltro : null,
        $busca !== '' ? $busca : null,
        300
    );
    $colunas = ['criado_em', 'usuario', 'acao_label', 'descricao'];
    $rotulos = [
        'criado_em'   => 'Data/Hora',
        'usuario'     => 'Usuário',
        'acao_label'  => 'Ação',
        'descricao'   => 'Descrição',
    ];
} else {
    header('Location: imprimir.php?tipo=audiencias&' . http_build_query(array_filter([
        'data_inicio' => $dataInicioRaw,
        'data_fim'    => $dataFimRaw,
    ])));
    exit;
}

function rotuloColuna(string $col, array $rotulos): string
{
    if (isset($rotulos[$col])) {
        return $rotulos[$col];
    }

    return ucwords(strtolower(str_replace('_', ' ', $col)));
}

function valorCelula(array $reg, string $col): string
{
    $valor = (string) ($reg[$col] ?? '');
    if ($col === 'descricao' && mb_strlen($valor) > 120) {
        return mb_substr($valor, 0, 117) . '...';
    }

    return $valor;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Imprimir · <?= htmlspecialchars($titulo) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000;
            background: #fff;
            padding: 8mm;
        }
        .cabecalho { margin-bottom: 10px; border-bottom: 2px solid #1a2f4a; padding-bottom: 8px; }
        .cabecalho h1 { font-size: 14pt; color: #1a2f4a; }
        .cabecalho h2 { font-size: 11pt; font-weight: normal; margin-top: 2px; }
        .cabecalho p { font-size: 8pt; color: #444; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td {
            border: 1px solid #666;
            padding: 3px 4px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        th {
            background: #333;
            color: #fff;
            font-size: 7pt;
            text-transform: uppercase;
        }
        td { font-size: 8pt; }
        .vazio { padding: 16px; text-align: center; color: #666; }
        .botoes { margin-bottom: 12px; }
        .botoes button {
            font-size: 10pt;
            padding: 6px 14px;
            margin-right: 8px;
            cursor: pointer;
        }
        @media print { .botoes { display: none; } body { padding: 0; } }
        @page { size: A4 landscape; margin: 8mm; }
    </style>
</head>
<body>
    <div class="botoes">
        <button type="button" onclick="window.print()">Imprimir</button>
        <button type="button" onclick="window.close()">Fechar</button>
    </div>

    <div class="cabecalho">
        <h1>Moura Galvão Advogados Associados</h1>
        <h2><?= htmlspecialchars($titulo) ?></h2>
        <p><?= htmlspecialchars($periodoTexto) ?> · <?= count($registros) ?> registros · Emitido em <?= date('d/m/Y H:i') ?></p>
    </div>

    <?php if (empty($registros)): ?>
    <p class="vazio">Nenhum registro para imprimir.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <?php foreach ($colunas as $col): ?>
                <th><?= htmlspecialchars(rotuloColuna($col, $rotulos)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $reg): ?>
            <tr>
                <?php foreach ($colunas as $col): ?>
                <td><?= htmlspecialchars(valorCelula($reg, $col)) ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</body>
</html>
