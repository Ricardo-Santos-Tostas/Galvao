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

    /** Corrige formatos antigos do Access (ex.: 71(098) → 71(98), (0 )392 → (71) 3392). */
    public static function normalizar(?string $telefone, string $ddd = '71'): ?string
    {
        if ($telefone === null) {
            return null;
        }

        $tel = trim($telefone);
        if ($tel === '' || $tel === '(0 ) - 0') {
            return null;
        }

        // 71(098)501-9440 → 71(98)501-9440 | 71(018)399 → 71(18)399
        $tel = preg_replace('/^' . preg_quote($ddd, '/') . '\(0(\d{2,3})\)/', $ddd . '($1)', $tel);

        // (0 8)166-8309 → (71) 98166-8309 | (0 9)928-0118 → (71) 9928-0118
        $tel = preg_replace('/^\(0 8\)/', "($ddd) 98", $tel);
        $tel = preg_replace('/^\(0 9\)/', "($ddd) 99", $tel);

        // (0 3)315-1302 → (71) 3315-1302
        $tel = preg_replace('/^\(0 (\d)\)/', "($ddd) 3$1", $tel);

        // (0 )397-1091 ou (0 ) 24-9426 → (71) 3397-1091 / (71) 324-9426
        $tel = preg_replace('/^\(0 \)\s*(\d+)-(\d+)$/', "($ddd) 3$1-$2", $tel);

        if (preg_match('/^7\(0/', $tel)) {
            $tel = preg_replace('/^7\(0/', $ddd . '(', $tel);
        }

        $digits = preg_replace('/\D/', '', $tel);
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

        $digits = self::removerZeroExtraAposDdd($digits, $ddd);

        if (str_starts_with($digits, '55')) {
            return strlen($digits) >= 12 ? $digits : null;
        }

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            return '55' . $digits;
        }

        return strlen($digits) >= 12 ? $digits : null;
    }

    /** Remove zero indevido após o DDD (ex.: 5571098... → 557198...). */
    private static function removerZeroExtraAposDdd(string $digits, string $ddd): string
    {
        $dddLen = strlen($ddd);

        // Com país: 55 + 71 + 0 + 9xxxxxxxx
        if (str_starts_with($digits, '55' . $ddd . '0')) {
            $resto = substr($digits, 2 + $dddLen + 1);
            if ($resto !== '' && in_array($resto[0], ['8', '9'], true)) {
                return '55' . $ddd . $resto;
            }
        }

        // Sem país: 71 + 0 + 9xxxxxxxx
        if (str_starts_with($digits, $ddd . '0')) {
            $resto = substr($digits, $dddLen + 1);
            if ($resto !== '' && in_array($resto[0], ['8', '9'], true)) {
                return $ddd . $resto;
            }
        }

        return $digits;
    }
}
