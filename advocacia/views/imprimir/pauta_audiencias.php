<?php
/** @var array $gruposPorData */

$tituloPagina = 'Audiências · Impressão';
$dicaImpressao = 'Preencha as observações antes de imprimir. Na janela de impressão, desmarque <strong>Cabeçalhos e rodapés</strong> do navegador.';
$estilosExtras = <<<'CSS'
    .pauta-bloco { margin-bottom: 10px; }
    .pauta-bloco:last-child { margin-bottom: 0; }
    .pauta-item {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        padding: 3px 0;
        border-bottom: 1px solid #000;
        font-size: 6.5pt;
        line-height: 1.15;
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .pauta-foto {
        width: 34px;
        height: 42px;
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
    .pauta-item-esq { flex: 1; min-width: 0; }
    .pauta-item-linha { display: block; margin-top: 0; }
    .pauta-item-topo {
        display: flex;
        align-items: flex-start;
        gap: 4px;
        margin-bottom: 1px;
    }
    .pauta-check-box {
        display: block;
        width: 8px;
        height: 8px;
        border: 1px solid #000;
        flex-shrink: 0;
        margin-top: 0;
    }
    .pauta-processo { font-weight: bold; }
    .pauta-obs {
        flex: 0 0 140px;
        width: 140px;
        min-height: 38px;
        border: 1px dashed #888;
        border-radius: 1px;
        padding: 2px 4px;
        font-family: inherit;
        font-size: 6.5pt;
        line-height: 1.15;
        resize: vertical;
        background: #fffef5;
    }
    .pauta-obs:focus {
        outline: 1px solid #c9a227;
        border-color: #c9a227;
        background: #fff;
    }
    .pauta-obs::placeholder { color: #999; font-size: 6pt; }
    .pauta-bloco-titulo {
        break-after: avoid;
        page-break-after: avoid;
    }
    @media print {
        .pauta-foto img {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .pauta-obs {
            border: none;
            background: transparent;
            padding: 0;
            resize: none;
            overflow: visible;
            min-height: 0;
            flex: 0 0 130px;
            width: 130px;
        }
    }
CSS;

include __DIR__ . '/layout_impressao_inicio.php';
?>

<?php if (empty($gruposPorData)): ?>
<p class="imp-vazio">Nenhuma audiência encontrada para o período informado.</p>
<?php else: ?>
    <?php foreach ($gruposPorData as $data => $itens): ?>
    <section class="pauta-bloco">
        <div class="pauta-bloco-titulo">
            <h1 class="imp-titulo">Audiências</h1>
            <p class="imp-subtitulo"><?= htmlspecialchars(pautaFormatarDataPorExtenso($data)) ?></p>
            <hr class="imp-linha-grossa">
        </div>

        <?php foreach ($itens as $reg): ?>
        <article class="pauta-item">
            <div class="pauta-foto">
                <?php if (!empty($reg['foto_url'])): ?>
                <img src="<?= htmlspecialchars($reg['foto_url']) ?>" alt="Foto do reclamante">
                <?php endif; ?>
            </div>
            <div class="pauta-item-esq">
                <div class="pauta-item-topo">
                    <span class="pauta-check-box" aria-hidden="true"></span>
                    <div>
                        <?php if (pautaFormatarJunta($reg['JUNTA'] ?? '')): ?>
                        <div class="pauta-item-linha"><?= htmlspecialchars(pautaFormatarJunta($reg['JUNTA'] ?? '')) ?></div>
                        <?php endif; ?>
                        <?php if (pautaFormatarHora($reg['HORA_AUD'] ?? '')): ?>
                        <div class="pauta-item-linha"><?= htmlspecialchars(pautaFormatarHora($reg['HORA_AUD'] ?? '')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (trim((string) ($reg['RECLAMANTE'] ?? '')) !== ''): ?>
                <div class="pauta-item-linha">Rte: <?= htmlspecialchars(pautaTextoMaiusculo($reg['RECLAMANTE'] ?? '')) ?></div>
                <?php endif; ?>
                <?php if (trim((string) ($reg['RECLAMADA'] ?? '')) !== ''): ?>
                <div class="pauta-item-linha">Rda: <?= htmlspecialchars(pautaTextoMaiusculo($reg['RECLAMADA'] ?? '')) ?></div>
                <?php endif; ?>
                <?php if (trim((string) ($reg['PROC'] ?? '')) !== ''): ?>
                <div class="pauta-item-linha pauta-processo">Processo: <?= htmlspecialchars(trim((string) ($reg['PROC'] ?? ''))) ?></div>
                <?php endif; ?>
            </div>
            <textarea class="pauta-obs" rows="3" spellcheck="false"
                      placeholder="Ex.: INICIAL (audiencia17vtssa) (ZOOM)"></textarea>
        </article>
        <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
<?php endif; ?>

<script>
    document.querySelectorAll('.pauta-obs').forEach((campo) => {
        const ajustar = () => {
            campo.style.height = 'auto';
            campo.style.height = Math.max(38, campo.scrollHeight) + 'px';
        };
        ajustar();
        campo.addEventListener('input', ajustar);
    });
</script>

<?php include __DIR__ . '/layout_impressao_fim.php'; ?>
