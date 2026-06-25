<?php
/**
 * Cabeçalho compartilhado — identidade Moura Galvão Advogados.
 *
 * @var string $pagina_atual  menu|cadastro|consulta|relatorio|usuarios
 */
$pagina_atual = $pagina_atual ?? '';

if (!class_exists('Auth')) {
    require_once __DIR__ . '/../../config/auth.php';
}

$usuarioHeader = Auth::usuario();
?>
<header class="site-header">
    <div class="header-inner">
        <a href="index.php" class="brand">
            <img src="assets/img/logo.png" alt="Moura Galvão Advogados" class="brand-logo<?= ($pagina_atual ?? '') === 'menu' ? ' brand-logo-lg' : '' ?>">
            <div class="brand-text<?= ($pagina_atual ?? '') === 'menu' ? ' brand-text-lg' : '' ?>">
                <span class="brand-name">Moura Galvão</span>
                <span class="brand-sub">Advogados Associados</span>
            </div>
        </a>
        <div class="header-right">
            <?php if ($pagina_atual !== 'menu'): ?>
            <nav class="header-nav">
                <a href="index.php" class="nav-link">Início</a>
                <?php if (Auth::podeVer('cadastro')): ?>
                <a href="cadastro.php" class="nav-link<?= $pagina_atual === 'cadastro' ? ' active' : '' ?>">Cadastro</a>
                <?php endif; ?>
                <?php if (Auth::podeVer('consulta_processo') || Auth::podeVer('consulta_reclamante') || Auth::podeVer('consulta_reclamada')): ?>
                <a href="consulta.php?tipo=processo" class="nav-link<?= $pagina_atual === 'consulta' ? ' active' : '' ?>">Consultas</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
            <?php if ($usuarioHeader): ?>
            <div class="header-user">
                <span class="header-user-nome"><?= htmlspecialchars($usuarioHeader['nome']) ?></span>
                <a href="logout.php" class="header-logout">Sair</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>
