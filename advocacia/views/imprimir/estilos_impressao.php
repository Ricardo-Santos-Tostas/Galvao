<?php
/**
 * Estilos compartilhados — impressão compacta (Arial 6.5pt, máximo por folha).
 * Cabeçalho/rodapé repetem via table thead/tfoot (sem sobrepor o conteúdo).
 *
 * @var string $estilosExtras CSS adicional por tipo de relatório
 */
$estilosExtras = $estilosExtras ?? '';
?>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 6.5pt;
        color: #000;
        background: #fff;
        padding: 8mm 10mm;
        line-height: 1.15;
    }
    .botoes {
        margin-bottom: 8px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 12px;
    }
    .botoes button {
        font-size: 10pt;
        padding: 5px 12px;
        cursor: pointer;
    }
    .botoes-dica {
        font-size: 9pt;
        color: #555;
        max-width: 560px;
        line-height: 1.35;
    }

    .imp-layout {
        width: 100%;
        border-collapse: collapse;
        border: none;
    }
    .imp-layout td {
        border: none;
        padding: 0;
        vertical-align: top;
    }
    .imp-layout-cabecalho {
        padding-bottom: 6px;
    }
    .imp-layout-cabecalho .imp-cabecalho {
        margin-bottom: 0;
    }
    .imp-layout-rodape {
        padding-top: 4px;
    }
    .imp-layout-rodape .imp-rodape {
        margin-top: 0;
    }

    .imp-cabecalho {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
    }
    .imp-cabecalho-marca {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
    }
    .imp-cabecalho-logo {
        width: 32px;
        height: 32px;
        object-fit: contain;
        flex-shrink: 0;
    }
    .imp-cabecalho-nome {
        font-family: 'Times New Roman', Times, serif;
        font-size: 10pt;
        font-weight: bold;
        color: #1a3a5c;
        letter-spacing: 0.3px;
        line-height: 1.05;
    }
    .imp-cabecalho-sub {
        margin-top: 1px;
        font-size: 5.5pt;
        letter-spacing: 0.2px;
        color: #444;
        text-transform: uppercase;
        line-height: 1.2;
    }
    .imp-socios {
        list-style: none;
        text-align: right;
        font-size: 6.5pt;
        line-height: 1.15;
        flex-shrink: 0;
    }
    .imp-socios em { font-style: italic; font-size: 6pt; }

    .imp-rodape {
        padding-top: 3px;
        border-top: 1.5px solid #1a3a5c;
        text-align: center;
    }
    .imp-rodape-linha {
        font-size: 6pt;
        line-height: 1.2;
        color: #1a3a5c;
    }
    .imp-rodape-linha + .imp-rodape-linha { margin-top: 1px; }

    .imp-titulo {
        text-align: center;
        font-family: 'Times New Roman', Times, serif;
        font-size: 11pt;
        font-weight: bold;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        margin-bottom: 3px;
    }
    .imp-subtitulo {
        text-align: center;
        font-size: 6.5pt;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .imp-meta {
        text-align: center;
        font-size: 6.5pt;
        color: #333;
        margin-bottom: 6px;
    }
    .imp-linha-grossa {
        border: none;
        border-top: 2px solid #000;
        margin: 0 0 4px;
    }
    .imp-vazio {
        text-align: center;
        padding: 20px 12px;
        color: #444;
        line-height: 1.3;
    }

    .imp-tabela {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 6.5pt;
    }
    .imp-tabela th,
    .imp-tabela td {
        border: 1px solid #000;
        padding: 2px 3px;
        vertical-align: top;
        word-wrap: break-word;
        overflow-wrap: break-word;
        line-height: 1.15;
    }
    .imp-tabela th {
        font-size: 6.5pt;
        font-weight: bold;
        text-transform: uppercase;
        background: #f0f0f0;
    }

    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    @media print {
        .botoes { display: none !important; }
        body { padding: 0; margin: 0; }

        .imp-layout thead { display: table-header-group; }
        .imp-layout tfoot { display: table-footer-group; }
        .imp-layout tbody { display: table-row-group; }

        .imp-layout-cabecalho {
            padding-bottom: 3px;
            border-bottom: 1px solid #ccc;
        }
        .imp-layout-rodape {
            padding-top: 2px;
        }

        .imp-cabecalho-logo {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }

    <?= $estilosExtras ?>
</style>
