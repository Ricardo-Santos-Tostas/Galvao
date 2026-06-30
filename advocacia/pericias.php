<?php
/**
 * Perícias — listagem com filtro, impressão e edição.
 */

require_once __DIR__ . '/models/PericiaModel.php';
require_once __DIR__ . '/models/ProcessoModel.php';
require_once __DIR__ . '/config/auth.php';

Auth::requerModulo('pericias', 'ver');
$podeEditarPericias = Auth::podeEditar('pericias');

$model = new PericiaModel();

$dataInicioRaw = $_GET['data_inicio'] ?? '';
$dataFimRaw    = $_GET['data_fim'] ?? '';

$dataInicio = ProcessoModel::parseDataFiltro($dataInicioRaw);
$dataFim    = ProcessoModel::parseDataFiltro($dataFimRaw);

if ($dataInicio && $dataFim && $dataInicio > $dataFim) {
    [$dataInicio, $dataFim] = [$dataFim, $dataInicio];
    [$dataInicioRaw, $dataFimRaw] = [$dataFimRaw, $dataInicioRaw];
}

$filtroAtivo = ($dataInicio || $dataFim);
$registros = $model->listar($dataInicio, $dataFim);

$colunas = [
    'DATA_PERICIA' => ['label' => 'Data-P', 'class' => 'col-data-p'],
    'HORA_PERICIA' => ['label' => 'Hora', 'class' => 'col-hora'],
    'RECLAMANTE'   => ['label' => 'Reclamante', 'class' => 'col-reclamante'],
    'CPF'          => ['label' => 'CPF', 'class' => 'col-cpf'],
    'RECLAMADA'    => ['label' => 'Reclamada', 'class' => 'col-reclamada'],
    'PROC_NUM'     => ['label' => 'Nº Processo', 'class' => 'col-processo'],
    'NOME_PERITO'  => ['label' => 'Nome do Perito', 'class' => 'col-nome-perito'],
    'ENDERECO'     => ['label' => 'Endereço', 'class' => 'col-endereco'],
];

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
    <title>Perícias · Moura Galvão</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet" media="screen">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>
<body class="relatorio page-pericias">
    <div class="relatorio-container">
        <header class="relatorio-header">
            <img src="assets/img/logo.png" alt="Moura Galvão" class="relatorio-logo">
            <div class="relatorio-header-text">
                <h1>Moura Galvão Advogados Associados</h1>
                <h2>Perícias</h2>
                <p><?= htmlspecialchars($periodoTexto) ?> · <?= count($registros) ?> registros · Emitido em <?= date('d/m/Y H:i') ?></p>
            </div>
        </header>

        <form class="relatorio-filtro no-print" method="get" action="pericias.php">
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
                    <a href="pericias.php" class="btn-filtro btn-filtro-secondary">Limpar</a>
                </div>
            </div>
            <p class="filtro-dica">Informe a data inicial e/ou final para filtrar as perícias do período desejado.</p>
        </form>

        <div class="relatorio-actions no-print">
            <button type="button" class="btn-imprimir" onclick="imprimirRelatorio()">Imprimir relatório</button>
            <a href="index.php">← Voltar ao menu</a>
        </div>

        <table class="relatorio-tabela">
            <thead>
                <tr>
                    <?php foreach ($colunas as $col): ?>
                    <th class="<?= htmlspecialchars($col['class']) ?>"><?= htmlspecialchars($col['label']) ?></th>
                    <?php endforeach; ?>
                    <?php if ($podeEditarPericias): ?>
                    <th class="col-acoes no-print">Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="<?= count($colunas) + ($podeEditarPericias ? 1 : 0) ?>">
                        <?= $filtroAtivo
                            ? 'Nenhuma perícia encontrada para o período informado.'
                            : 'Nenhuma perícia cadastrada.' ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($registros as $reg): ?>
                <tr data-id="<?= (int) ($reg['ID'] ?? 0) ?>">
                    <?php foreach ($colunas as $campo => $col): ?>
                    <td class="<?= htmlspecialchars($col['class']) ?>"><?= htmlspecialchars($reg[$campo] ?? '') ?></td>
                    <?php endforeach; ?>
                    <?php if ($podeEditarPericias): ?>
                    <td class="col-acoes no-print">
                        <button type="button" class="btn-editar-pericia" data-id="<?= (int) ($reg['ID'] ?? 0) ?>">
                            Editar
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-pericia" id="modalPericia" hidden>
        <div class="modal-pericia-backdrop" id="modalPericiaBackdrop"></div>
        <div class="modal-pericia-panel" role="dialog" aria-labelledby="modalPericiaTitulo">
            <header class="modal-pericia-header">
                <h2 id="modalPericiaTitulo">Editar perícia</h2>
                <button type="button" class="modal-pericia-fechar" id="btnFecharPericia" aria-label="Fechar">&times;</button>
            </header>
            <form class="modal-pericia-form" id="formPericia">
                <input type="hidden" id="periciaId" name="ID">
                <input type="hidden" id="periciaCadastro" name="CADASTRO">

                <div class="pericia-form-grid">
                    <div class="filtro-field">
                        <label for="periciaData">Data-P</label>
                        <input type="text" id="periciaData" name="DATA_PERICIA" placeholder="dd/mm/aaaa">
                    </div>
                    <div class="filtro-field">
                        <label for="periciaHora">Hora</label>
                        <input type="text" id="periciaHora" name="HORA_PERICIA" placeholder="hh:mm">
                    </div>
                    <div class="filtro-field filtro-field-wide">
                        <label for="periciaReclamante">Reclamante</label>
                        <input type="text" id="periciaReclamante" name="RECLAMANTE">
                    </div>
                    <div class="filtro-field">
                        <label for="periciaCpf">CPF</label>
                        <input type="text" id="periciaCpf" name="CPF">
                    </div>
                    <div class="filtro-field filtro-field-wide">
                        <label for="periciaReclamada">Reclamada</label>
                        <input type="text" id="periciaReclamada" name="RECLAMADA">
                    </div>
                    <div class="filtro-field filtro-field-wide">
                        <label for="periciaProc">Nº Processo</label>
                        <input type="text" id="periciaProc" name="PROC_NUM">
                    </div>
                    <div class="filtro-field filtro-field-wide">
                        <label for="periciaPerito">Nome do Perito</label>
                        <input type="text" id="periciaPerito" name="NOME_PERITO">
                    </div>
                    <div class="filtro-field filtro-field-full">
                        <label for="periciaEndereco">Endereço</label>
                        <textarea id="periciaEndereco" name="ENDERECO" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-pericia-acoes">
                    <button type="button" class="btn-filtro btn-filtro-secondary" id="btnCancelarPericia">Cancelar</button>
                    <button type="submit" class="btn-filtro btn-filtro-primary" id="btnSalvarPericia">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/print.js?v=2"></script>
    <script src="assets/js/pericias.js?v=1"></script>
</body>
</html>
