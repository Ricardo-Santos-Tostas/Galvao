<?php
/** @var array $gruposPorData */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Audiências · Impressão</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #000;
            background: #fff;
            padding: 14mm 16mm;
        }
        .botoes { margin-bottom: 16px; }
        .botoes button {
            font-size: 10pt;
            padding: 6px 14px;
            margin-right: 8px;
            cursor: pointer;
        }
        .pauta-bloco { margin-bottom: 28px; }
        .pauta-bloco:last-child { margin-bottom: 0; }
        .pauta-titulo {
            text-align: center;
            font-family: 'Times New Roman', Times, serif;
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .pauta-data {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 14px;
        }
        .pauta-linha-grossa {
            border: none;
            border-top: 3px solid #000;
            margin: 0 0 0 0;
        }
        .pauta-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid #000;
        }
        .pauta-item:last-child { border-bottom: 1px solid #000; }
        .pauta-foto {
            width: 54px;
            height: 68px;
            flex-shrink: 0;
            border: 1px solid #999;
            background: #f5f5f5;
            overflow: hidden;
        }
        .pauta-foto img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .pauta-esq {
            width: 72px;
            flex-shrink: 0;
            line-height: 1.45;
            font-size: 11pt;
        }
        .pauta-dir {
            flex: 1;
            line-height: 1.55;
            font-size: 11pt;
        }
        .pauta-dir div + div { margin-top: 2px; }
        .pauta-vazio {
            text-align: center;
            padding: 40px 20px;
            color: #444;
            line-height: 1.6;
        }
        @media print {
            .botoes { display: none !important; }
            body { padding: 0; }
            .pauta-bloco { page-break-inside: avoid; }
            .pauta-foto img {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @page { size: A4 portrait; margin: 14mm 16mm; }
    </style>
</head>
<body>
    <div class="botoes">
        <button type="button" onclick="window.print()">Imprimir</button>
        <button type="button" onclick="window.close()">Fechar</button>
    </div>

    <?php if (empty($gruposPorData)): ?>
    <p class="pauta-vazio">Nenhuma audiência encontrada para o período informado.</p>
    <?php else: ?>
        <?php foreach ($gruposPorData as $data => $itens): ?>
        <section class="pauta-bloco">
            <h1 class="pauta-titulo">Audiências</h1>
            <p class="pauta-data"><?= htmlspecialchars(pautaFormatarDataPorExtenso($data)) ?></p>
            <hr class="pauta-linha-grossa">

            <?php foreach ($itens as $reg): ?>
            <article class="pauta-item">
                <div class="pauta-foto">
                    <?php if (!empty($reg['foto_url'])): ?>
                    <img src="<?= htmlspecialchars($reg['foto_url']) ?>" alt="Foto do reclamante">
                    <?php endif; ?>
                </div>
                <div class="pauta-esq">
                    <?php if (pautaFormatarJunta($reg['JUNTA'] ?? '')): ?>
                    <div><?= htmlspecialchars(pautaFormatarJunta($reg['JUNTA'] ?? '')) ?></div>
                    <?php endif; ?>
                    <?php if (pautaFormatarHora($reg['HORA_AUD'] ?? '')): ?>
                    <div><?= htmlspecialchars(pautaFormatarHora($reg['HORA_AUD'] ?? '')) ?></div>
                    <?php endif; ?>
                </div>
                <div class="pauta-dir">
                    <?php if (trim((string) ($reg['RECLAMANTE'] ?? '')) !== ''): ?>
                    <div>Rte: <?= htmlspecialchars(pautaTextoMaiusculo($reg['RECLAMANTE'] ?? '')) ?></div>
                    <?php endif; ?>
                    <?php if (trim((string) ($reg['RECLAMADA'] ?? '')) !== ''): ?>
                    <div>Rda: <?= htmlspecialchars(pautaTextoMaiusculo($reg['RECLAMADA'] ?? '')) ?></div>
                    <?php endif; ?>
                    <?php if (trim((string) ($reg['PROC'] ?? '')) !== ''): ?>
                    <div>Processo: <?= htmlspecialchars(trim((string) ($reg['PROC'] ?? ''))) ?></div>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </section>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
