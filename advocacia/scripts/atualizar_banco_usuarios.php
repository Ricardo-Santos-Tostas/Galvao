<?php
/**
 * Cria tabelas de usuários e permissões + admin padrão.
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/UsuarioModel.php';

$db = getConnection();

$sqlUsuarios = '
CREATE TABLE IF NOT EXISTS ' . sqlId('usuarios') . ' (
    ID          INT AUTO_INCREMENT PRIMARY KEY,
    LOGIN       VARCHAR(50)  NOT NULL,
    SENHA_HASH  VARCHAR(255) NOT NULL,
    NOME        VARCHAR(100) NOT NULL,
    IS_ADMIN    TINYINT(1)   NOT NULL DEFAULT 0,
    ATIVO       TINYINT(1)   NOT NULL DEFAULT 1,
    CRIADO_EM   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuarios_login (LOGIN)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

$sqlPermissoes = '
CREATE TABLE IF NOT EXISTS ' . sqlId('usuario_permissoes') . ' (
    ID          INT AUTO_INCREMENT PRIMARY KEY,
    USUARIO_ID  INT          NOT NULL,
    MODULO      VARCHAR(50)  NOT NULL,
    PODE_VER    TINYINT(1)   NOT NULL DEFAULT 0,
    PODE_EDITAR TINYINT(1)   NOT NULL DEFAULT 0,
    UNIQUE KEY uk_usuario_modulo (USUARIO_ID, MODULO),
    INDEX idx_perm_usuario (USUARIO_ID),
    CONSTRAINT fk_perm_usuario FOREIGN KEY (USUARIO_ID)
        REFERENCES ' . sqlId('usuarios') . ' (ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

echo "Criando tabelas de login...\n";
$db->exec($sqlUsuarios);
echo "  [OK] Tabela usuarios\n";
$db->exec($sqlPermissoes);
echo "  [OK] Tabela usuario_permissoes\n";

$model = new UsuarioModel();
$model->garantirAdminPadrao();
echo "  [OK] Administrador ricardo verificado\n";

echo "\nBanco de usuarios atualizado com sucesso!\n";
echo "Login admin: ricardo\n";
