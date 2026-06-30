<?php
/**
 * Log de atividades — somente administrador.
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/LogModel.php';
require_once __DIR__ . '/models/ProcessoModel.php';

Auth::requerAdmin();

$model = new LogModel();
$acoes = LogModel::acoes();
$usuariosLog = $model->usuariosNoLog();

$dataInicioRaw = trim($_GET['data_inicio'] ?? '');
$dataFimRaw    = trim($_GET['data_fim'] ?? '');
$usuarioId     = (int) ($_GET['usuario_id'] ?? 0);
$acaoFiltro    = trim($_GET['acao'] ?? '');
$busca         = trim($_GET['busca'] ?? '');

$dataInicio = ProcessoModel::parseDataFiltro($dataInicioRaw);
$dataFim    = ProcessoModel::parseDataFiltro($dataFimRaw);

if ($dataInicio && $dataFim && $dataInicio > $dataFim) {
    [$dataInicio, $dataFim] = [$dataFim, $dataInicio];
    [$dataInicioRaw, $dataFimRaw] = [$dataFimRaw, $dataInicioRaw];
}

$filtroAtivo = ($dataInicio || $dataFim || $usuarioId > 0 || $acaoFiltro !== '' || $busca !== '');

$total = $model->contar(
    $dataInicio,
    $dataFim,
    $usuarioId > 0 ? $usuarioId : null,
    $acaoFiltro !== '' ? $acaoFiltro : null,
    $busca !== '' ? $busca : null
);

$registros = $model->listar(
    $dataInicio,
    $dataFim,
    $usuarioId > 0 ? $usuarioId : null,
    $acaoFiltro !== '' ? $acaoFiltro : null,
    $busca !== '' ? $busca : null
);

$resumoUsuarios = $model->resumoPorUsuario($dataInicio, $dataFim);

$periodoTexto = '';
if ($dataInicio && $dataFim) {
    $periodoTexto = ProcessoModel::formatarDataFiltro($dataInicio)
        . ' a ' . ProcessoModel::formatarDataFiltro($dataFim);
} elseif ($dataInicio) {
    $periodoTexto = 'A partir de ' . ProcessoModel::formatarDataFiltro($dataInicio);
} elseif ($dataFim) {
    $periodoTexto = 'Até ' . ProcessoModel::formatarDataFiltro($dataFim);
} else {
    $periodoTexto = 'Todos os registros';
}

$pagina_atual = 'log';

function badgeAcao(string $acao): string
{
    $mapa = [
        'login'              => 'log-badge-login',
        'logout'             => 'log-badge-logout',
        'cadastro_criar'     => 'log-badge-criar',
        'cadastro_editar'    => 'log-badge-editar',
        'cadastro_foto'      => 'log-badge-anexo',
        'cadastro_documento' => 'log-badge-anexo',
        'cadastro_documento_excluir' => 'log-badge-excluir',
        'pericia_criar'      => 'log-badge-criar',
        'pericia_editar'     => 'log-badge-editar',
        'usuario_criar'      => 'log-badge-criar',
        'usuario_editar'     => 'log-badge-editar',
        'usuario_excluir'    => 'log-badge-excluir',
    ];

    return $mapa[$acao] ?? 'log-badge-default';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log de Atividades · Moura Galvão</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet" media="screen">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>
<body class="relatorio page-log">
    <?php include __DIR__ . '/views/partials/header.php'; ?>

    <div class="relatorio-container log-container">
        <header class="relatorio-header">
            <img src="assets/img/logo.png" alt="Moura Galvão" class="relatorio-logo">
            <div class="relatorio-header-text">
                <h1>Moura Galvão Advogados Associados</h1>
                <h2>Log de atividades</h2>
                <p><?= htmlspecialchars($periodoTexto) ?> · <?= number_format($total, 0, ',', '.') ?> registros</p>
            </div>
        </header>

        <section class="log-resumo no-print">
            <div class="log-resumo-card">
                <span class="log-resumo-num"><?= number_format($total, 0, ',', '.') ?></span>
                <span class="log-resumo-label">Eventos no período</span>
            </div>
            <div class="log-resumo-card">
                <span class="log-resumo-num"><?= count($resumoUsuarios) ?></span>
                <span class="log-resumo-label">Usuários ativos</span>
            </div>
            <div class="log-resumo-card log-resumo-wide">
                <span class="log-resumo-label">Mais atividades</span>
                <div class="log-resumo-usuarios">
                    <?php if (empty($resumoUsuarios)): ?>
                    <span class="log-resumo-vazio">Nenhum registro no período</span>
                    <?php else: ?>
                    <?php foreach ($resumoUsuarios as $item): ?>
                    <span class="log-resumo-user-chip">
                        <?= htmlspecialchars($item['nome']) ?> (<?= (int) $item['total'] ?>)
                    </span>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <form class="relatorio-filtro log-filtro no-print" method="get" action="log.php">
            <div class="filtro-grid log-filtro-grid">
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
                <div class="filtro-field">
                    <label for="usuario_id">Usuário</label>
                    <select id="usuario_id" name="usuario_id">
                        <option value="">Todos</option>
                        <?php foreach ($usuariosLog as $u): ?>
                        <option value="<?= (int) $u['id'] ?>"
                            <?= $usuarioId === (int) $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nome']) ?> (<?= htmlspecialchars($u['login']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-field">
                    <label for="acao">Tipo de ação</label>
                    <select id="acao" name="acao">
                        <option value="">Todas</option>
                        <?php foreach ($acoes as $chave => $rotulo): ?>
                        <option value="<?= htmlspecialchars($chave) ?>"
                            <?= $acaoFiltro === $chave ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rotulo) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-field filtro-field-wide">
                    <label for="busca">Buscar</label>
                    <input type="text" id="busca" name="busca"
                           value="<?= htmlspecialchars($busca) ?>"
                           placeholder="Descrição, referência, nome...">
                </div>
                <div class="filtro-botoes">
                    <button type="submit" class="btn-filtro btn-filtro-primary">Filtrar</button>
                    <a href="log.php" class="btn-filtro btn-filtro-secondary">Limpar</a>
                </div>
            </div>
            <p class="filtro-dica">Filtre por período, usuário ou tipo de ação para ver quem alterou o quê no sistema.</p>
        </form>

        <div class="relatorio-actions no-print">
            <button type="button" onclick="imprimirRelatorio()">Imprimir log</button>
            <a href="index.php">← Voltar ao menu</a>
        </div>

        <div class="log-lista">
            <?php if (empty($registros)): ?>
            <div class="log-vazio">
                <?= $filtroAtivo
                    ? 'Nenhuma atividade encontrada para os filtros informados.'
                    : 'Nenhuma atividade registrada ainda.' ?>
            </div>
            <?php else: ?>
            <?php foreach ($registros as $reg): ?>
            <article class="log-item">
                <div class="log-item-top">
                    <time class="log-item-data"><?= htmlspecialchars($reg['criado_em']) ?></time>
                    <span class="log-badge <?= badgeAcao($reg['acao']) ?>">
                        <?= htmlspecialchars($reg['acao_label']) ?>
                    </span>
                </div>
                <div class="log-item-corpo">
                    <div class="log-item-usuario">
                        <strong><?= htmlspecialchars($reg['usuario']) ?></strong>
                        <span>@<?= htmlspecialchars($reg['login']) ?></span>
                        <?php if ($reg['referencia']): ?>
                        <span class="log-ref"><?= htmlspecialchars($reg['referencia']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="log-item-desc"><?= htmlspecialchars($reg['descricao']) ?></p>
                    <?php if (!empty($reg['detalhes']['alteracoes'])): ?>
                    <div class="log-alteracoes">
                        <span class="log-alteracoes-titulo">Campos alterados:</span>
                        <ul>
                            <?php foreach ($reg['detalhes']['alteracoes'] as $alt): ?>
                            <li>
                                <strong><?= htmlspecialchars($alt['rotulo']) ?>:</strong>
                                <span class="log-valor-antes"><?= htmlspecialchars($alt['antes']) ?></span>
                                <span class="log-seta">→</span>
                                <span class="log-valor-depois"><?= htmlspecialchars($alt['depois']) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($reg['ip']): ?>
                <div class="log-item-meta no-print">IP: <?= htmlspecialchars($reg['ip']) ?></div>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
            <?php if ($total > count($registros)): ?>
            <p class="log-limite-aviso no-print">
                Exibindo os <?= count($registros) ?> registros mais recentes de <?= number_format($total, 0, ',', '.') ?>.
                Refine o período para ver resultados mais específicos.
            </p>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/views/partials/footer.php'; ?>
    <script src="assets/js/print.js?v=2"></script>
</body>
</html>
