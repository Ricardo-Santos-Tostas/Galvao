<?php
/**
 * Cria a tabela de log de atividades.
 */
require_once dirname(__DIR__) . '/config/database.php';

$db = getConnection();

$sql = '
CREATE TABLE IF NOT EXISTS ' . sqlId('log_atividades') . ' (
    ID            INT AUTO_INCREMENT PRIMARY KEY,
    USUARIO_ID    INT           NULL,
    USUARIO_LOGIN VARCHAR(50)   NULL,
    USUARIO_NOME  VARCHAR(100)  NULL,
    ACAO          VARCHAR(50)   NOT NULL,
    MODULO        VARCHAR(50)   NULL,
    REFERENCIA    VARCHAR(100)  NULL,
    DESCRICAO     TEXT          NOT NULL,
    DETALHES      TEXT          NULL,
    IP            VARCHAR(45)   NULL,
    CRIADO_EM     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_data (CRIADO_EM),
    INDEX idx_log_usuario (USUARIO_ID),
    INDEX idx_log_acao (ACAO)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

echo "Criando tabela log_atividades...\n";
$db->exec($sql);
echo "  [OK] Tabela log_atividades pronta\n";
echo "\nBanco de log atualizado com sucesso!\n";
