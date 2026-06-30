<?php
/**
 * Layout base das páginas internas (formulários e relatórios).
 */
if (!class_exists('Auth')) {
    require_once __DIR__ . '/../config/auth.php';
}
Auth::requerLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Moura Galvão · Sistema de Processos') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>
<body class="page-form">
    <?php include __DIR__ . '/partials/header.php'; ?>

    <main class="form-page">
        <div class="form-container">
            <?= $conteudo ?>
        </div>
    </main>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <script src="assets/js/app.js?v=4"></script>
    <script>
        window.APP_PERMISSOES = <?= json_encode(class_exists('Auth') ? Auth::permissoesJson() : [], JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <?php if (!empty($script_extra)): ?>
    <script><?= $script_extra ?></script>
    <?php endif; ?>
</body>
</html>
