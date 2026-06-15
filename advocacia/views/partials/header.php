<?php
/**
 * Cabeçalho compartilhado — identidade Moura Galvão Advogados.
 *
 * @var string $pagina_atual  menu|cadastro|consulta|relatorio
 */
$pagina_atual = $pagina_atual ?? '';
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
        <?php if ($pagina_atual !== 'menu'): ?>
        <nav class="header-nav">
            <a href="index.php" class="nav-link">Início</a>
            <a href="cadastro.php" class="nav-link<?= $pagina_atual === 'cadastro' ? ' active' : '' ?>">Cadastro</a>
            <a href="consulta.php?tipo=processo" class="nav-link<?= $pagina_atual === 'consulta' ? ' active' : '' ?>">Consultas</a>
        </nav>
        <?php endif; ?>
    </div>
</header>
