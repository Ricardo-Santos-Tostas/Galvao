<?php
/**
 * Normalização de telefones brasileiros (legado Access → formato correto).
 */

class TelefoneBr
{
    public static function campos(): array
    {
        return ['FONE_RTE', 'FONE_RTE_2_', 'FONE_RTE_3_', 'FONE_RTE_4_'];
    }

    /** Corrige formatos antigos e exibe como (71) 98772-7998 ou (71) 3392-1234. */
    public static function normalizar(?string $telefone, string $ddd = '71'): ?string
    {
        if ($telefone === null) {
            return null;
        }

        $tel = trim($telefone);
        if ($tel === '' || $tel === '(0 ) - 0') {
            return null;
        }

        $tel = self::corrigirLegadoAccess($tel, $ddd);

        $digits = preg_replace('/\D/', '', $tel);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        $digits = self::removerZeroExtraAposDdd($digits, $ddd);

        $formatado = self::formatarDigitos($digits, $ddd);
        if ($formatado !== null) {
            return $formatado;
        }

        if (strlen($digits) < 8) {
            return null;
        }

        return $tel;
    }

    /** Retorna número no formato internacional para WhatsApp (ex.: 5571985019440). */
    public static function paraWhatsApp(?string $telefone, string $ddd = '71'): ?string
    {
        $tel = self::normalizar($telefone, $ddd);
        if ($tel === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $tel);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '55')) {
            return strlen($digits) >= 12 ? $digits : null;
        }

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            return '55' . $digits;
        }

        return strlen($digits) >= 12 ? $digits : null;
    }

    private static function corrigirLegadoAccess(string $tel, string $ddd): string
    {
        // 71(098)501-9440 → 71(98)501-9440
        $tel = preg_replace('/^' . preg_quote($ddd, '/') . '\(0(\d{2,3})\)/', $ddd . '($1)', $tel);

        // (0 8)166-8309 → (71) 98166-8309
        $tel = preg_replace('/^\(0 8\)/', "($ddd) 98", $tel);
        $tel = preg_replace('/^\(0 9\)/', "($ddd) 99", $tel);

        // (0 3)315-1302 → (71) 3315-1302
        $tel = preg_replace('/^\(0 (\d)\)/', "($ddd) 3$1", $tel);

        // (0 )397-1091 → (71) 3397-1091
        $tel = preg_replace('/^\(0 \)\s*(\d+)-(\d+)$/', "($ddd) 3$1-$2", $tel);

        if (preg_match('/^7\(0/', $tel)) {
            $tel = preg_replace('/^7\(0/', $ddd . '(', $tel);
        }

        return $tel;
    }

    private static function formatarDigitos(string $digits, string $ddd): ?string
    {
        $len = strlen($digits);

        // Celular com DDD: 71987727998 → (71) 98772-7998
        if ($len === 11) {
            return sprintf(
                '(%s) %s-%s',
                substr($digits, 0, 2),
                substr($digits, 2, 5),
                substr($digits, 7, 4)
            );
        }

        // Fixo com DDD: 7133921234 → (71) 3392-1234
        if ($len === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($digits, 0, 2),
                substr($digits, 2, 4),
                substr($digits, 6, 4)
            );
        }

        // Celular sem DDD: 987727998
        if ($len === 9 && $digits[0] === '9') {
            return sprintf('(%s) %s-%s', $ddd, substr($digits, 0, 5), substr($digits, 5, 4));
        }

        // Fixo sem DDD: 33921234
        if ($len === 8) {
            return sprintf('(%s) %s-%s', $ddd, substr($digits, 0, 4), substr($digits, 4, 4));
        }

        return null;
    }

    /** Remove zero indevido após o DDD (ex.: 5571098... → 557198...). */
    private static function removerZeroExtraAposDdd(string $digits, string $ddd): string
    {
        $dddLen = strlen($ddd);

        if (str_starts_with($digits, '55' . $ddd . '0')) {
            $resto = substr($digits, 2 + $dddLen + 1);
            if ($resto !== '' && in_array($resto[0], ['8', '9'], true)) {
                return '55' . $ddd . $resto;
            }
        }

        if (str_starts_with($digits, $ddd . '0')) {
            $resto = substr($digits, $dddLen + 1);
            if ($resto !== '' && in_array($resto[0], ['8', '9'], true)) {
                return $ddd . $resto;
            }
        }

        return $digits;
    }
}
