<?php
/**
 * Tabela de impressão com cabeçalho/rodapé repetidos em cada página.
 *
 * @var string $classesExtras Classes CSS adicionais na <table>
 * @var string $conteudoTabela HTML do corpo (td.imp-corpo)
 */
$classesExtras = trim($classesExtras ?? '');
?>
<table class="imp-layout<?= $classesExtras !== '' ? ' ' . htmlspecialchars($classesExtras) : '' ?>">
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
                <?= $conteudoTabela ?? '' ?>
            </td>
        </tr>
    </tbody>
</table>
