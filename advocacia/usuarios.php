<?php
/**
 * Gerenciamento de usuários e permissões — somente administrador.
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/UsuarioModel.php';

Auth::requerAdmin();

$model = new UsuarioModel();
$usuarios = $model->listar();
$modulos = UsuarioModel::modulosSistema();
$modulosAniv = UsuarioModel::modulosAniversario();
$pagina_atual = 'usuarios';
$usuarioLogado = Auth::usuario();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários · Moura Galvão</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>
<body class="page-usuarios">
    <?php include __DIR__ . '/views/partials/header.php'; ?>

    <main class="usuarios-page">
        <header class="usuarios-header">
            <div>
                <h1>Usuários e permissões</h1>
                <p>Crie usuários e defina quais abas cada um pode visualizar ou editar.</p>
            </div>
            <button type="button" class="btn btn-primary" id="btnNovoUsuario">+ Novo usuário</button>
        </header>

        <section class="usuarios-lista-card">
            <table class="usuarios-tabela">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Login</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                    <tr><td colspan="5">Nenhum usuário cadastrado.</td></tr>
                    <?php else: ?>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nome']) ?></td>
                        <td><?= htmlspecialchars($u['login']) ?></td>
                        <td><?= $u['is_admin'] ? 'Administrador' : 'Usuário' ?></td>
                        <td><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                        <td>
                            <button type="button" class="btn-editar-usuario" data-id="<?= (int) $u['id'] ?>">Editar</button>
                            <?php if ((int) $u['id'] !== (int) $usuarioLogado['id']): ?>
                            <button type="button" class="btn-excluir-usuario" data-id="<?= (int) $u['id'] ?>">Excluir</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <div class="modal-usuario" id="modalUsuario" hidden>
        <div class="modal-usuario-backdrop" id="modalUsuarioBackdrop"></div>
        <div class="modal-usuario-panel">
            <header class="modal-usuario-header">
                <h2 id="modalUsuarioTitulo">Novo usuário</h2>
                <button type="button" class="modal-pericia-fechar" id="btnFecharUsuario">&times;</button>
            </header>
            <form id="formUsuario" class="modal-usuario-form">
                <input type="hidden" id="usuarioId" name="id">

                <div class="usuario-form-grid">
                    <div class="login-field">
                        <label for="usuarioNome">Nome completo</label>
                        <input type="text" id="usuarioNome" name="nome" required>
                    </div>
                    <div class="login-field">
                        <label for="usuarioLogin">Usuário (login)</label>
                        <input type="text" id="usuarioLogin" name="login" required autocomplete="off">
                    </div>
                    <div class="login-field">
                        <label for="usuarioSenha">Senha</label>
                        <input type="password" id="usuarioSenha" name="senha" autocomplete="new-password">
                        <span class="field-hint" id="usuarioSenhaDica">Obrigatória para novos usuários.</span>
                    </div>
                    <div class="login-field login-field-checks">
                        <label class="perm-check">
                            <input type="checkbox" id="usuarioAtivo" name="ativo" checked>
                            Usuário ativo
                        </label>
                        <label class="perm-check">
                            <input type="checkbox" id="usuarioAdmin" name="is_admin">
                            Administrador (acesso total)
                        </label>
                    </div>
                </div>

                <div id="wrapPermissoes">
                    <h3 class="permissoes-titulo">Permissões por aba</h3>
                    <p class="permissoes-dica">Marque <strong>Visualizar</strong> para permitir acesso. Marque <strong>Editar</strong> para permitir alterações e salvamento.</p>

                    <table class="permissoes-tabela">
                        <thead>
                            <tr>
                                <th>Módulo</th>
                                <th>Visualizar</th>
                                <th>Editar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modulos as $chave => $rotulo): ?>
                            <tr data-modulo="<?= htmlspecialchars($chave) ?>">
                                <td><?= htmlspecialchars($rotulo) ?></td>
                                <td><input type="checkbox" class="perm-ver" data-modulo="<?= htmlspecialchars($chave) ?>"></td>
                                <td><input type="checkbox" class="perm-editar" data-modulo="<?= htmlspecialchars($chave) ?>"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h3 class="permissoes-titulo">Aniversariantes</h3>
                    <div class="permissoes-aniv">
                        <?php foreach ($modulosAniv as $chave => $rotulo): ?>
                        <label class="perm-check">
                            <input type="checkbox" class="perm-aniv" data-modulo="<?= htmlspecialchars($chave) ?>">
                            <?= htmlspecialchars($rotulo) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="modal-pericia-acoes">
                    <button type="button" class="btn-filtro btn-filtro-secondary" id="btnCancelarUsuario">Cancelar</button>
                    <button type="submit" class="btn-filtro btn-filtro-primary">Salvar usuário</button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/views/partials/footer.php'; ?>
    <script>
        window.USUARIOS_DATA = <?= json_encode($usuarios, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/js/usuarios.js?v=1"></script>
</body>
</html>
