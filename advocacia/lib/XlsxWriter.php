<?php
/**
 * Escritor simples de arquivos .xlsx (sem dependências externas).
 */
declare(strict_types=1);

final class XlsxWriter
{
    /**
     * @param list<string> $cabecalhos
     * @param list<list<string|int|float|null>> $linhas
     */
    public static function escrever(string $caminho, string $nomePlanilha, array $cabecalhos, array $linhas): void
    {
        $strings = [];
        $indiceString = static function (string $valor) use (&$strings): int {
            $valor = self::escaparXml($valor);
            $pos = array_search($valor, $strings, true);
            if ($pos === false) {
                $strings[] = $valor;
                return count($strings) - 1;
            }

            return (int) $pos;
        };

        $sheetRows = [];
        $sheetRows[] = self::linhaXml($cabecalhos, $indiceString, 1);

        $numeroLinha = 2;
        foreach ($linhas as $linha) {
            $sheetRows[] = self::linhaXml($linha, $indiceString, $numeroLinha);
            $numeroLinha++;
        }

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . implode('', $sheetRows) . '</sheetData>'
            . '</worksheet>';

        $sharedXml = self::montarSharedStrings($strings);
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::escaparXml($nomePlanilha) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '</Types>';

        $relsRoot = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $relsWorkbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '</Relationships>';

        $zip = new ZipArchive();
        if ($zip->open($caminho, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Não foi possível criar o arquivo Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $relsRoot);
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $relsWorkbook);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->addFromString('xl/sharedStrings.xml', $sharedXml);
        $zip->close();
    }

    /**
     * @param list<string|int|float|null> $valores
     * @param callable(string): int $indiceString
     */
    private static function linhaXml(array $valores, callable $indiceString, int $numeroLinha): string
    {
        $celulas = [];
        $coluna = 0;
        foreach ($valores as $valor) {
            $texto = trim((string) ($valor ?? ''));
            $ref = self::colunaLetra($coluna) . $numeroLinha;
            $idx = $indiceString($texto);
            $celulas[] = '<c r="' . $ref . '" t="s"><v>' . $idx . '</v></c>';
            $coluna++;
        }

        return '<row r="' . $numeroLinha . '">' . implode('', $celulas) . '</row>';
    }

    /** @param list<string> $strings */
    private static function montarSharedStrings(array $strings): string
    {
        $itens = '';
        foreach ($strings as $texto) {
            $itens .= '<si><t>' . $texto . '</t></si>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'
            . count($strings) . '" uniqueCount="' . count($strings) . '">'
            . $itens . '</sst>';
    }

    private static function colunaLetra(int $indice): string
    {
        $indice++;
        $letras = '';
        while ($indice > 0) {
            $resto = ($indice - 1) % 26;
            $letras = chr(65 + $resto) . $letras;
            $indice = intdiv($indice - 1, 26);
        }

        return $letras;
    }

    private static function escaparXml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
