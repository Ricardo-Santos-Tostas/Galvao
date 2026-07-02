<?php
/**
 * @var string $tituloPagina  Título da aba do navegador
 * @var string $dicaImpressao Texto opcional acima dos botões
 */
$tituloPagina  = $tituloPagina ?? 'Impressão';
$dicaImpressao = $dicaImpressao ?? 'Na janela de impressão, desmarque <strong>Cabeçalhos e rodapés</strong> do navegador.';
$estilosExtras = $estilosExtras ?? '';
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

    <table class="imp-layout">
        <thead>
            <tr>
                <td class="imp-layout-cabecalho">
                    <?php include __DIR__ . '/cabecalho_impressao.php'; ?>
                </td>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td class="imp-layout-rodape">
                    <?php include __DIR__ . '/rodape_impressao.php'; ?>
                </td>
            </tr>
        </tfoot>
        <tbody>
            <tr>
                <td class="imp-corpo">
