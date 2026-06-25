<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/log.php';

Auth::iniciarSessao();

if (Auth::usuario()) {
    header('Location: index.php');
    exit;
}

$erro = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');

    if (Auth::login($login, $senha)) {
        Log::registrar('login', 'Iniciou sessão no sistema', 'sistema');
        $redirect = $_POST['redirect'] ?? 'index.php';
        if (!is_string($redirect) || $redirect === '' || str_contains($redirect, '://')) {
            $redirect = 'index.php';
        }
        header('Location: ' . $redirect);
        exit;
    }

    $erro = 'Usuário ou senha incorretos.';
}

$redirect = $_GET['redirect'] ?? 'index.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · Moura Galvão</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>
<body class="page-login">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <img src="assets/img/logo.png" alt="Moura Galvão Advogados" class="login-logo">
                <h1>Moura Galvão</h1>
                <p>Advogados Associados</p>
                <span class="login-subtitulo">Sistema de Gestão de Processos</span>
            </div>

            <form method="post" class="login-form" autocomplete="off">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                <?php if ($erro): ?>
                <div class="login-erro" role="alert"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <div class="login-field">
                    <label for="login">Usuário</label>
                    <input type="text" id="login" name="login" value="<?= htmlspecialchars($login) ?>"
                           required autofocus placeholder="Digite seu usuário">
                </div>

                <div class="login-field">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required placeholder="Digite sua senha">
                </div>

                <button type="submit" class="btn-login">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>
