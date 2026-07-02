<?php
/** @var array $gruposPorData */

$tituloPagina = 'Audiências · Impressão';
$dicaImpressao = 'Preencha as observações antes de imprimir. Cada dia de audiência será impresso em uma folha separada. Na janela de impressão, desmarque <strong>Cabeçalhos e rodapés</strong> do navegador.';
$estilosExtras = <<<'CSS'
    .pauta-dia-pagina {
        margin-bottom: 28px;
        min-height: calc(100vh - 16mm);
    }
    .pauta-dia-pagina:last-child { margin-bottom: 0; }
    .pauta-dia-pagina tbody tr { height: 100%; }
    .pauta-item {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        padding: 4px 0;
        border-bottom: 1px solid currentColor;
        font-size: 8pt;
        line-height: 1.2;
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .pauta-area-trabalhista { color: #000; }
    .pauta-area-consumidor { color: #0033A0; }
    .pauta-area-previdenciario { color: #C9A000; }
    .pauta-foto {
        width: 40px;
        height: 48px;
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
        gap: 5px;
        margin-bottom: 1px;
    }
    .pauta-check-box {
        display: block;
        width: 10px;
        height: 10px;
        border: 1px solid currentColor;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .pauta-processo { font-weight: bold; }
    .pauta-obs {
        flex: 0 0 150px;
        width: 150px;
        min-height: 44px;
        border: 1px dashed #888;
        border-radius: 1px;
        padding: 2px 4px;
        font-family: inherit;
        font-size: 8pt;
        line-height: 1.2;
        color: #000;
        resize: vertical;
        background: #fffef5;
    }
    .pauta-obs:focus {
        outline: 1px solid #c9a227;
        border-color: #c9a227;
        background: #fff;
    }
    .pauta-obs::placeholder { color: #999; font-size: 7pt; }
    .pauta-bloco-titulo {
        break-after: avoid;
        page-break-after: avoid;
    }
    .pauta-dia-pagina .imp-subtitulo { font-size: 8pt; }
    @media print {
        .pauta-dia-pagina {
            height: 281mm;
            min-height: 281mm;
            break-after: page;
            page-break-after: always;
        }
        .pauta-dia-pagina:last-child {
            break-after: auto;
            page-break-after: auto;
        }
        .pauta-dia-pagina tbody tr { height: 100%; }
        .pauta-area-trabalhista,
        .pauta-area-consumidor,
        .pauta-area-previdenciario {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
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
            flex: 0 0 140px;
            width: 140px;
            color: #000;
        }
    }
CSS;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tituloPagina) ?></title>
    <?php include __DIR__ . '/estilos_impressao.php'; ?>
</head>
<body>
    <div class="botoes">
        <button type="button" onclick="window.print()">Imprimir</button>
        <button type="button" onclick="window.close()">Fechar</button>
        <?php if ($dicaImpressao !== ''): ?>
        <p class="botoes-dica"><?= $dicaImpressao ?></p>
        <?php endif; ?>
    </div>

<?php if (empty($gruposPorData)): ?>
    <?php
    $classesExtras = '';
    ob_start();
    ?>
    <p class="imp-vazio">Nenhuma audiência encontrada para o período informado.</p>
    <?php
    $conteudoTabela = ob_get_clean();
    include __DIR__ . '/tabela_impressao.php';
    ?>
<?php else: ?>
    <?php foreach ($gruposPorData as $data => $itens): ?>
        <?php
        $classesExtras = 'pauta-dia-pagina';
        ob_start();
        ?>
        <div class="pauta-bloco-titulo">
            <h1 class="imp-titulo">Audiências</h1>
            <p class="imp-subtitulo"><?= htmlspecialchars(pautaFormatarDataPorExtenso($data)) ?></p>
            <hr class="imp-linha-grossa">
        </div>

        <?php foreach ($itens as $reg): ?>
        <article class="pauta-item <?= htmlspecialchars(pautaClasseArea($reg['AREA'] ?? '')) ?>">
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
        <?php
        $conteudoTabela = ob_get_clean();
        include __DIR__ . '/tabela_impressao.php';
        ?>
    <?php endforeach; ?>
<?php endif; ?>

<script>
    document.querySelectorAll('.pauta-obs').forEach((campo) => {
        const ajustar = () => {
            campo.style.height = 'auto';
            campo.style.height = Math.max(44, campo.scrollHeight) + 'px';
        };
        ajustar();
        campo.addEventListener('input', ajustar);
    });
</script>
</body>
</html>
