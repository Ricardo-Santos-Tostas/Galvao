<?php
/**
 * Helper para registrar atividades no log.
 */

require_once __DIR__ . '/../models/LogModel.php';
require_once __DIR__ . '/auth.php';

class Log
{
    public static function registrar(
        string $acao,
        string $descricao,
        ?string $modulo = null,
        ?string $referencia = null,
        ?array $detalhes = null,
        ?array $usuario = null
    ): void {
        try {
            $model = new LogModel();
            $model->registrar($acao, $descricao, $modulo, $referencia, $detalhes, $usuario);
        } catch (Throwable $e) {
            // Não interrompe a operação principal se o log falhar.
        }
    }
}
