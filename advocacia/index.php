<?php
/**
 * Menu principal — painel de controle do sistema.
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/ProcessoModel.php';

Auth::requerLogin();

$pagina_atual = 'menu';
$model = new ProcessoModel();
$totalProcessos = $model->contarProcessos();
$totalFormatado = number_format($totalProcessos, 0, ',', '.');
$menuItens = Auth::linksMenu();
$podeAniversariantes = Auth::podeVerAniversariantes();
$podeEnviarAniv = Auth::podeEnviarAniversario();
$isAdmin = Auth::isAdmin();

function iconeMenu(string $tipo): string {
    $icones = [
        'user-plus' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>',
        'search'    => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'user'      => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'building'  => '<path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/>',
        'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'file'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'check'     => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
    ];
    return $icones[$tipo] ?? $icones['file'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moura Galvão · Sistema de Processos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/logo.png" type="image/png">
</head>
<body class="page-menu">
    <?php include __DIR__ . '/views/partials/header.php'; ?>

    <main class="dashboard">
        <section class="hero">
            <div class="hero-content">
                <h1>Sistema de Gestão de Processos</h1>
                <p>Direito Trabalhista · Consulta, cadastro e relatórios de processos jurídicos</p>
            </div>
            <div class="hero-side">
                <div class="hero-badge">
                    <span class="badge-number"><?= htmlspecialchars($totalFormatado) ?></span>
                    <span class="badge-label">Processos cadastrados</span>
                </div>
                <?php if ($podeAniversariantes): ?>
                <button type="button" class="hero-btn-aniv" id="btnAniversariantes">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8"/><path d="M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1"/><path d="M2 21h20"/><path d="M7 8v3"/><path d="M12 8v3"/><path d="M17 8v3"/><path d="M7 4h.01"/><path d="M12 4h.01"/><path d="M17 4h.01"/></svg>
                    Aniversariantes do dia
                </button>
                <?php endif; ?>
            </div>
        </section>

        <section class="menu-grid">
            <?php foreach ($menuItens as $item): ?>
            <a href="<?= htmlspecialchars($item['href']) ?>" class="menu-card">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><?= iconeMenu($item['icon']) ?></svg>
                </div>
                <h2><?= htmlspecialchars($item['titulo']) ?></h2>
                <p><?= htmlspecialchars($item['desc']) ?></p>
            </a>
            <?php endforeach; ?>

            <?php if ($isAdmin): ?>
            <a href="usuarios.php" class="menu-card menu-card-admin">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h2>Usuários</h2>
                <p>Criar usuários e definir permissões</p>
            </a>

            <a href="log.php" class="menu-card menu-card-admin">
                <div class="card-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
                </div>
                <h2>Log de atividades</h2>
                <p>Quem alterou o quê e quando</p>
            </a>
            <?php endif; ?>
        </section>

        <?php if (empty($menuItens) && !$isAdmin): ?>
        <p class="menu-vazio">Seu usuário não possui permissão para acessar nenhum módulo. Contate o administrador.</p>
        <?php endif; ?>
    </main>

    <?php if ($podeAniversariantes): ?>
    <!-- Modal Aniversariantes -->
    <div class="modal-aniv" id="modalAniversario" hidden>
        <div class="modal-aniv-backdrop" id="modalAnivBackdrop"></div>
        <div class="modal-aniv-panel" role="dialog" aria-labelledby="modalAnivTitulo">
            <header class="modal-aniv-header">
                <h2 id="modalAnivTitulo">Aniversariantes do dia</h2>
                <p class="modal-aniv-data" id="modalAnivData"></p>
                <button type="button" class="modal-aniv-fechar" id="btnFecharAniv" aria-label="Fechar">&times;</button>
            </header>
            <div class="modal-aniv-body">
                <div class="modal-aniv-lista-wrap" id="wrapListaAniv">
                    <?php if ($podeEnviarAniv): ?>
                    <div class="aniv-lista-toolbar no-print">
                        <label class="aniv-marcar-todos">
                            <input type="checkbox" id="anivMarcarTodos">
                            Marcar todos
                        </label>
                        <span class="aniv-contador" id="anivContador">0 selecionados</span>
                        <button type="button" class="btn-aniv-selecionados" id="btnEnviarSelecionados" disabled>
                            Enviar para selecionados
                        </button>
                    </div>
                    <?php endif; ?>
                    <div class="modal-aniv-lista" id="listaAniversariantes">
                        <p class="modal-aniv-loading">Carregando...</p>
                    </div>
                </div>
                <div class="modal-aniv-detalhe" id="detalheAniversariante" hidden>
                    <div class="aniv-detalhe-header">
                        <?php if ($podeEnviarAniv): ?>
                        <input type="checkbox" id="anivCheckDetalhe" class="aniv-check-detalhe" checked title="Marcar para envio">
                        <?php endif; ?>
                        <h3 id="anivNome"></h3>
                    </div>
                    <p class="aniv-info" id="anivInfo"></p>
                    <p class="aniv-enviando-para" id="anivEnviandoPara" hidden></p>
                    <?php if ($podeEnviarAniv): ?>
                    <div class="aniv-campo">
                        <label for="anivMensagem">Mensagem</label>
                        <textarea id="anivMensagem" rows="4" placeholder="Digite sua mensagem de parabéns..."></textarea>
                        <span class="aniv-dica-msg">Use <strong>{nome}</strong> para personalizar com o nome de cada aniversariante.</span>
                    </div>
                    <label class="aniv-check">
                        <input type="checkbox" id="anivComImagem">
                        Enviar também imagem de parabéns
                    </label>
                    <div class="aniv-imagem-area" id="anivImagemArea" hidden>
                        <label for="anivImagemInput" class="btn-importar-imagem">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            Importar imagem
                        </label>
                        <input type="file" id="anivImagemInput" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
                        <div class="aniv-preview" id="anivPreview" hidden>
                            <img src="" alt="Imagem selecionada" id="anivPreviewImg">
                            <button type="button" class="btn-remover-imagem" id="btnRemoverImagem">Remover imagem</button>
                        </div>
                        <p class="aniv-imagem-dica">A imagem será baixada para você anexar no WhatsApp Web.</p>
                    </div>
                    <div class="aniv-acoes">
                        <button type="button" class="btn-aniv-voltar" id="btnAnivVoltar">← Voltar</button>
                        <button type="button" class="btn-aniv-whatsapp" id="btnEnviarWhatsApp">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            <span id="btnEnviarTexto">Enviar mensagem</span>
                        </button>
                    </div>
                    <p class="aniv-aviso" id="anivAviso" hidden></p>
                    <?php else: ?>
                    <div class="aniv-acoes">
                        <button type="button" class="btn-aniv-voltar" id="btnAnivVoltar">← Voltar</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include __DIR__ . '/views/partials/footer.php'; ?>
    <script>
        window.APP_PERMISSOES = <?= json_encode(Auth::permissoesJson(), JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <?php if ($podeAniversariantes): ?>
    <script src="assets/js/dashboard.js?v=6"></script>
    <?php endif; ?>
</body>
</html>
