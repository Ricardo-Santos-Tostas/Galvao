<?php
/**
 * Leitor simples de arquivos .xlsx (sem dependências externas).
 */
declare(strict_types=1);

final class XlsxReader
{
    /** @return list<list<string>> */
    public static function readSheet(string $path, string $sheetName): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('Arquivo não encontrado.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Não foi possível abrir o arquivo Excel.');
        }

        try {
            $shared = self::lerSharedStrings($zip);
            $sheetPath = self::resolverPlanilha($zip, $sheetName);
            if ($sheetPath === null) {
                throw new RuntimeException("Planilha '{$sheetName}' não encontrada no arquivo.");
            }

            $xml = $zip->getFromName($sheetPath);
            if ($xml === false) {
                throw new RuntimeException('Não foi possível ler a planilha.');
            }

            return self::lerLinhas($xml, $shared);
        } finally {
            $zip->close();
        }
    }

    /** @return list<string> */
    private static function lerSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $strings = [];
        foreach ($doc->getElementsByTagName('si') as $si) {
            $texto = '';
            foreach ($si->getElementsByTagName('t') as $t) {
                $texto .= $t->textContent;
            }
            $strings[] = $texto;
        }

        return $strings;
    }

    private static function resolverPlanilha(ZipArchive $zip, string $sheetName): ?string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook === false || $rels === false) {
            return null;
        }

        $relsMap = [];
        $relsDoc = new DOMDocument();
        $relsDoc->loadXML($rels);
        foreach ($relsDoc->getElementsByTagName('Relationship') as $rel) {
            if ($rel instanceof DOMElement) {
                $relsMap[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
            }
        }

        $wb = new DOMDocument();
        $wb->loadXML($workbook);
        foreach ($wb->getElementsByTagName('sheet') as $sheet) {
            if (!$sheet instanceof DOMElement) {
                continue;
            }
            if (strcasecmp($sheet->getAttribute('name'), $sheetName) !== 0) {
                continue;
            }
            $rid = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            if ($rid === '' || !isset($relsMap[$rid])) {
                return null;
            }

            $target = str_replace('\\', '/', $relsMap[$rid]);
            $target = ltrim($target, '/');
            if (!str_starts_with($target, 'xl/')) {
                $target = 'xl/' . $target;
            }

            return $target;
        }

        return null;
    }

    /**
     * @param list<string> $shared
     * @return list<list<string>>
     */
    private static function lerLinhas(string $xml, array $shared): array
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $linhas = [];

        foreach ($doc->getElementsByTagName('row') as $row) {
            if (!$row instanceof DOMElement) {
                continue;
            }

            $cells = [];
            $maxCol = 0;
            foreach ($row->getElementsByTagName('c') as $cell) {
                if (!$cell instanceof DOMElement) {
                    continue;
                }
                $ref = $cell->getAttribute('r');
                $col = self::colunaParaIndice(preg_replace('/\d+/', '', $ref) ?? 'A');
                $maxCol = max($maxCol, $col);
                $cells[$col] = self::valorCelula($cell, $shared);
            }

            if ($cells === []) {
                $linhas[] = [];
                continue;
            }

            $linha = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $linha[$i] = $cells[$i] ?? '';
            }
            $linhas[] = $linha;
        }

        return $linhas;
    }

    private static function valorCelula(DOMElement $cell, array $shared): string
    {
        $type = $cell->getAttribute('t');
        $v = '';
        foreach ($cell->getElementsByTagName('v') as $node) {
            $v = $node->textContent ?? '';
            break;
        }

        if ($type === 's') {
            return $shared[(int) $v] ?? '';
        }

        if ($type === 'inlineStr') {
            foreach ($cell->getElementsByTagName('t') as $t) {
                return $t->textContent ?? '';
            }
        }

        if ($v !== '' && is_numeric($v)) {
            $formatado = self::serialParaTexto((float) $v);
            if ($formatado !== null) {
                return $formatado;
            }
        }

        return trim($v);
    }

    /**
     * Converte número serial do Excel (data/hora) para texto legível.
     */
    public static function serialParaTexto(float $serial): ?string
    {
        if ($serial > 0 && $serial < 1) {
            $minutos = (int) round($serial * 24 * 60);
            $h = intdiv($minutos, 60) % 24;
            $m = $minutos % 60;

            return sprintf('%02d:%02d', $h, $m);
        }

        if ($serial >= 30000 && $serial < 1000000) {
            $unix = (int) round(($serial - 25569) * 86400);
            $dt = (new DateTimeImmutable('@' . $unix));

            return $dt->format('d/m/Y H:i');
        }

        return null;
    }

    private static function colunaParaIndice(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }
}
