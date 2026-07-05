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
    <link rel="stylesheet" href="assets/css/style.css?v=6">
    <?php include __DIR__ . '/views/partials/favicon.php'; ?>
</head>
<body class="relatorio">
    <div class="relatorio-container">
        <header class="relatorio-header">
            <img src="assets/img/logo.png" alt="Moura Galvão" width="1138" height="1096" decoding="async" class="relatorio-logo">
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
                    <?php if ($tipo === 'audiencias' && Auth::podeEditar('cadastro')): ?>
                    <button type="button" id="btnImportarPauta" class="btn-filtro btn-filtro-secondary">Importar</button>
                    <input type="file" id="inputImportarPauta" accept=".xlsx" hidden>
                    <?php endif; ?>
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

    <?php if ($tipo === 'audiencias' && Auth::podeEditar('cadastro')): ?>
    <div class="modal-duplicado" id="modalImportacao" hidden>
        <div class="modal-duplicado-backdrop" id="modalImportacaoBackdrop"></div>
        <div class="modal-duplicado-panel" role="dialog" aria-labelledby="modalImportacaoTitulo">
            <header class="modal-duplicado-header">
                <h2 id="modalImportacaoTitulo">Resultado da importação</h2>
                <button type="button" class="modal-duplicado-fechar" id="btnFecharImportacao" aria-label="Fechar">&times;</button>
            </header>
            <div class="modal-duplicado-corpo">
                <p id="importacaoResumo"></p>
                <div id="importacaoNaoEncontrados" hidden>
                    <h3>Processos não encontrados no cadastro</h3>
                    <div class="importacao-tabela-wrap">
                        <table class="relatorio-tabela importacao-tabela" id="tabelaNaoEncontrados">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Nº Processo</th>
                                    <th>Reclamante</th>
                                    <th>Reclamado</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <p class="importacao-pasta-dica">
                        O relatório também foi salvo em:
                        <strong>import/nao_encontrados/</strong>
                    </p>
                </div>
                <p id="importacaoErros" class="duplicado-aviso" hidden></p>
            </div>
            <footer class="modal-duplicado-acoes">
                <a href="#" id="btnBaixarRelatorio" class="btn-filtro btn-filtro-primary" hidden download>Baixar relatório Excel</a>
                <button type="button" class="btn-filtro btn-filtro-secondary" id="btnFecharImportacao2">Fechar</button>
            </footer>
        </div>
    </div>
    <?php endif; ?>

    <script src="assets/js/print.js?v=3"></script>
    <?php if ($tipo === 'audiencias' && Auth::podeEditar('cadastro')): ?>
    <script>
    (function () {
        const btn = document.getElementById('btnImportarPauta');
        const input = document.getElementById('inputImportarPauta');
        const modal = document.getElementById('modalImportacao');
        const resumo = document.getElementById('importacaoResumo');
        const blocoNaoEnc = document.getElementById('importacaoNaoEncontrados');
        const tbodyNaoEnc = document.querySelector('#tabelaNaoEncontrados tbody');
        const errosEl = document.getElementById('importacaoErros');
        const btnBaixar = document.getElementById('btnBaixarRelatorio');
        const fecharBtns = [document.getElementById('btnFecharImportacao'), document.getElementById('btnFecharImportacao2')];
        const backdrop = document.getElementById('modalImportacaoBackdrop');

        if (!btn || !input || !modal) return;

        const fecharModal = () => { modal.hidden = true; };
        fecharBtns.forEach((b) => b?.addEventListener('click', fecharModal));
        backdrop?.addEventListener('click', fecharModal);

        const escapar = (texto) => {
            const el = document.createElement('span');
            el.textContent = texto ?? '';
            return el.innerHTML;
        };

        const nomeReclamante = (item) => {
            const rte = (item.reclamante || '').trim();
            const rda = (item.reclamada || '').trim();
            const pareceNome = (t) => t && !/\b(zoom\.us|sala|impar|auxiliar|videoconfer)/i.test(t);
            if (pareceNome(rte)) return rte;
            if (pareceNome(rda)) return rda;
            return rte || rda;
        };

        const mostrarResultado = (dados) => {
            resumo.textContent = 'Registros atualizados: ' + (dados.atualizados || 0) + '.';

            const lista = dados.nao_encontrados || [];
            if (lista.length > 0) {
                blocoNaoEnc.hidden = false;
                tbodyNaoEnc.innerHTML = lista.map((item) => `
                    <tr>
                        <td>${escapar(item.data)}</td>
                        <td>${escapar(item.hora)}</td>
                        <td>${escapar(item.processo)}</td>
                        <td>${escapar(nomeReclamante(item))}</td>
                        <td>${escapar(item.reclamada)}</td>
                    </tr>
                `).join('');
            } else {
                blocoNaoEnc.hidden = true;
                tbodyNaoEnc.innerHTML = '';
            }

            if (dados.erros && dados.erros.length) {
                errosEl.hidden = false;
                errosEl.textContent = 'Avisos: ' + dados.erros.join(' | ');
            } else {
                errosEl.hidden = true;
                errosEl.textContent = '';
            }

            if (dados.relatorio && dados.relatorio.url) {
                btnBaixar.hidden = false;
                btnBaixar.href = dados.relatorio.url;
                btnBaixar.setAttribute('download', dados.relatorio.arquivo || 'nao_encontrados.xlsx');
                btnBaixar.click();
            } else {
                btnBaixar.hidden = true;
                btnBaixar.removeAttribute('href');
            }

            modal.hidden = false;
        };

        btn.addEventListener('click', () => input.click());

        input.addEventListener('change', async () => {
            const arquivo = input.files && input.files[0];
            input.value = '';
            if (!arquivo) return;

            if (!/\.xlsx$/i.test(arquivo.name)) {
                alert('Selecione um arquivo Excel (.xlsx) com a aba Trabalhista.');
                return;
            }

            const form = new FormData();
            form.append('arquivo', arquivo);

            btn.disabled = true;
            const textoOriginal = btn.textContent;
            btn.textContent = 'Importando...';

            try {
                const resp = await fetch('api/?acao=importar_pauta_trabalhista', {
                    method: 'POST',
                    body: form,
                });
                const dados = await resp.json();

                if (!resp.ok || dados.erro) {
                    throw new Error(dados.erro || 'Falha na importação.');
                }

                mostrarResultado(dados);

                if ((dados.atualizados || 0) > 0) {
                    setTimeout(() => window.location.reload(), 1500);
                }
            } catch (err) {
                alert(err.message || 'Erro ao importar a planilha.');
            } finally {
                btn.disabled = false;
                btn.textContent = textoOriginal;
            }
        });
    })();
    </script>
    <?php endif; ?>
</body>
</html>
