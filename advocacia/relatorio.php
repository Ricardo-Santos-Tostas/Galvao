<?php
/**
 * Relatórios — Pauta de Audiências e Pauta Reclamante com filtro por período.
 */

require_once __DIR__ . '/models/ProcessoModel.php';
require_once __DIR__ . '/config/auth.php';

Auth::requerLogin();

$tipo = $_GET['tipo'] ?? 'audiencias';
$model = new ProcessoModel();

$dataInicioRaw = $_GET['data_inicio'] ?? '';
$dataFimRaw    = $_GET['data_fim'] ?? '';

$dataInicio = ProcessoModel::parseDataFiltro($dataInicioRaw);
$dataFim    = ProcessoModel::parseDataFiltro($dataFimRaw);

if ($dataInicio && $dataFim && $dataInicio > $dataFim) {
    [$dataInicio, $dataFim] = [$dataFim, $dataInicio];
    [$dataInicioRaw, $dataFimRaw] = [$dataFimRaw, $dataInicioRaw];
}

$filtroAtivo = ($dataInicio || $dataFim);
$rotulosColunas = [];

if ($tipo === 'pericias') {
    $params = $_GET;
    unset($params['tipo']);
    $qs = http_build_query($params);
    header('Location: pericias.php' . ($qs ? '?' . $qs : ''));
    exit;
} elseif ($tipo === 'reclamante') {
    Auth::requerModulo('pauta_reclamante', 'ver');
    $titulo = 'Pauta Reclamante';
    $registros = $model->pautaReclamante($dataInicio, $dataFim);
    $colunas = ['CADASTRO', 'RECLAMANTE', 'CPF', 'RECLAMADA', 'PROC', 'DIA_AUD', 'HORA_AUD', 'ANDAMENTO'];
    $filtroDica = 'Informe a data inicial e/ou final para filtrar as audiências do período desejado.';
} else {
    $tipo = 'audiencias';
    Auth::requerModulo('pauta_audiencias', 'ver');
    $titulo = 'Pauta de Audiências';
    $registros = $model->pautaAudiencias($dataInicio, $dataFim);
    $colunas = ['DIA_AUD', 'HORA_AUD', 'RECLAMANTE', 'CPF', 'RECLAMADA', 'PROC', 'JUNTA', 'ANDAMENTO'];
    $filtroDica = 'Informe a data inicial e/ou final para filtrar as audiências. Obrigatório para imprimir.';
}

$periodoTexto = '';
if ($dataInicio && $dataFim) {
    $periodoTexto = 'Período: ' . ProcessoModel::formatarDataFiltro($dataInicio)
        . ' a ' . ProcessoModel::formatarDataFiltro($dataFim);
} elseif ($dataInicio) {
    $periodoTexto = 'A partir de ' . ProcessoModel::formatarDataFiltro($dataInicio);
} elseif ($dataFim) {
    $periodoTexto = 'Até ' . ProcessoModel::formatarDataFiltro($dataFim);
} else {
    $periodoTexto = 'Todos os registros';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> · Moura Galvão</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet" media="screen">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>
<body class="relatorio">
    <div class="relatorio-container">
        <header class="relatorio-header">
            <img src="assets/img/logo.png" alt="Moura Galvão" class="relatorio-logo">
            <div class="relatorio-header-text">
                <h1>Moura Galvão Advogados Associados</h1>
                <h2><?= htmlspecialchars($titulo) ?></h2>
                <p><?= htmlspecialchars($periodoTexto) ?> · <?= count($registros) ?> registros · Emitido em <?= date('d/m/Y H:i') ?></p>
            </div>
        </header>

        <form class="relatorio-filtro no-print" method="get" action="relatorio.php">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
            <div class="filtro-grid">
                <div class="filtro-field">
                    <label for="data_inicio">Data inicial</label>
                    <input type="date" id="data_inicio" name="data_inicio"
                           value="<?= htmlspecialchars($dataInicioRaw) ?>">
                </div>
                <div class="filtro-field">
                    <label for="data_fim">Data final</label>
                    <input type="date" id="data_fim" name="data_fim"
                           value="<?= htmlspecialchars($dataFimRaw) ?>">
                </div>
                <div class="filtro-botoes">
                    <button type="submit" class="btn-filtro btn-filtro-primary">Pesquisar</button>
                    <a href="relatorio.php?tipo=<?= urlencode($tipo) ?>" class="btn-filtro btn-filtro-secondary">Limpar</a>
                </div>
            </div>
            <p class="filtro-dica"><?= htmlspecialchars($filtroDica) ?></p>
        </form>

        <div class="relatorio-actions no-print">
            <button type="button" onclick="imprimirRelatorio()">Imprimir relatório</button>
            <a href="index.php">← Voltar ao menu</a>
        </div>

        <table class="relatorio-tabela">
            <thead>
                <tr>
                    <?php foreach ($colunas as $col): ?>
                    <th><?= htmlspecialchars($rotulosColunas[$col] ?? str_replace('_', ' ', $col)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="<?= count($colunas) ?>">
                        <?= $filtroAtivo
                            ? 'Nenhum registro encontrado para o período informado.'
                            : 'Nenhum registro encontrado.' ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($registros as $reg): ?>
                <tr>
                    <?php foreach ($colunas as $col): ?>
                    <td><?= htmlspecialchars($reg[$col] ?? '') ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="assets/js/print.js?v=3"></script>
</body>
</html>
