<?php
/**
 * Normalização e comparação de números de processo CNJ (Trabalhista).
 *
 * Formato completo (Excel):  0000284-74.2026.5.05.0020
 * Formato abreviado (sistema): 284-74.26.0020
 * Chave interna (comparação):  284-74-26-0020
 */
declare(strict_types=1);

final class ProcessoCNJ
{
    public static function normalizar(?string $numero): ?string
    {
        $numero = self::limparEntrada($numero);
        if ($numero === null) {
            return null;
        }

        $extraido = self::extrair($numero);
        if ($extraido !== null) {
            $numero = self::limparEntrada($extraido) ?? $numero;
        }

        // CNJ completo: NNNNNNN-DD.AAAA.J.TT.OOOO
        if (preg_match('/^(\d+)-(\d{2})\.(\d{4})\.(\d)\.(\d{2})\.(\d{2,4})$/', $numero, $m)) {
            return self::montarChave($m[1], $m[2], substr($m[3], 2), $m[6]);
        }

        // Abreviado do sistema: NNN-DD.AA.OOOO (vara com 2 a 4 dígitos)
        if (preg_match('/^(\d+)-(\d{2})\.(\d{2})\.(\d{2,4})$/', $numero, $m)) {
            return self::montarChave($m[1], $m[2], $m[3], $m[4]);
        }

        $somenteNumeros = preg_replace('/\D/', '', $numero) ?? '';
        if (strlen($somenteNumeros) === 20) {
            return self::montarChave(
                substr($somenteNumeros, 0, 7),
                substr($somenteNumeros, 7, 2),
                substr($somenteNumeros, 9, 2),
                substr($somenteNumeros, -4)
            );
        }

        return null;
    }

    public static function abreviado(?string $numero): ?string
    {
        $n = self::normalizar($numero);
        if ($n === null) {
            return null;
        }

        [$seq, $dv, $ano, $vara] = explode('-', $n);

        return "{$seq}-{$dv}.{$ano}.{$vara}";
    }

    public static function completo(?string $numero): ?string
    {
        $n = self::normalizar($numero);
        if ($n === null) {
            return null;
        }

        [$seq, $dv, $ano, $vara] = explode('-', $n);

        return str_pad($seq, 7, '0', STR_PAD_LEFT) . "-{$dv}.20{$ano}.5.05.{$vara}";
    }

    public static function iguais(?string $numero1, ?string $numero2): bool
    {
        $a = self::normalizar($numero1);
        $b = self::normalizar($numero2);

        return $a !== null && $b !== null && $a === $b;
    }

    /**
     * Localiza um número CNJ dentro de um texto (célula Excel, cadastro legado, etc.).
     */
    public static function extrair(?string $texto): ?string
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return null;
        }

        $compacto = preg_replace('/\s+/', '', $texto) ?? $texto;

        if (preg_match(
            '/\d{1,7}-\d{2}\.\d{4}\.\d\.\d{2}\.\d{2,4}/',
            $compacto,
            $m
        )) {
            return $m[0];
        }

        if (preg_match(
            '/\d+-\d{2}\.\d{2}\.\d{2,4}/',
            $compacto,
            $m
        )) {
            return $m[0];
        }

        if (preg_match('/\d{20}/', $compacto, $m)) {
            return $m[0];
        }

        return null;
    }

    public static function pareceNumeroProcesso(string $texto): bool
    {
        return self::extrair($texto) !== null;
    }

    private static function limparEntrada(?string $numero): ?string
    {
        if ($numero === null) {
            return null;
        }

        $numero = trim($numero);
        if ($numero === '') {
            return null;
        }

        return preg_replace('/\s+/', '', $numero) ?? null;
    }

    private static function montarChave(string $sequencial, string $dv, string $ano, string $vara): string
    {
        $sequencial = (string) (int) $sequencial;
        $ano = str_pad($ano, 2, '0', STR_PAD_LEFT);
        $vara = str_pad($vara, 4, '0', STR_PAD_LEFT);

        return "{$sequencial}-{$dv}-{$ano}-{$vara}";
    }
}
