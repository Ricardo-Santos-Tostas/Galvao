<?php
/**
 * Normalização de campos de texto multilinha (ex.: ANDAMENTO).
 * Corrige resíduos de exportação Access/Excel (_x000D_) e quebras de linha mistas.
 */
class TextoMultilinha
{
    public static function normalizar(?string $texto): ?string
    {
        if ($texto === null || $texto === '') {
            return null;
        }

        $t = str_replace(["\r\n", "\r", '_x000D_', '_x000A_'], "\n", $texto);
        $t = preg_replace("/\n{3,}/", "\n\n", $t);
        $t = trim($t);

        return $t === '' ? null : $t;
    }

    public static function iguais(?string $a, ?string $b): bool
    {
        return self::normalizar($a) === self::normalizar($b);
    }
}
