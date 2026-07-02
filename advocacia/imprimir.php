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

$tituloPagina = 'Imprimir · ' . $titulo;
$dicaImpressao = 'Na janela de impressão, desmarque <strong>Cabeçalhos e rodapés</strong> do navegador.';
$estilosExtras = <<<'CSS'
    .imp-tabela tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }
CSS;

include __DIR__ . '/views/imprimir/layout_impressao_inicio.php';
?>

<h1 class="imp-titulo"><?= htmlspecialchars($titulo) ?></h1>
<p class="imp-meta">
    <?= htmlspecialchars($periodoTexto) ?> · <?= count($registros) ?> registros · Emitido em <?= date('d/m/Y H:i') ?>
</p>
<hr class="imp-linha-grossa">

<?php if (empty($registros)): ?>
<p class="imp-vazio">Nenhum registro para imprimir.</p>
<?php else: ?>
<table class="imp-tabela">
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

<?php include __DIR__ . '/views/imprimir/layout_impressao_fim.php'; ?>
