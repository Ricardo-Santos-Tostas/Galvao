<?php
/**
 * Formulário moderno de cadastro e consulta.
 */
$somente_leitura = $somente_leitura ?? false;
$readonly = $somente_leitura ? 'readonly' : '';
$modo = $modo ?? 'cadastro';
$label_busca = $label_busca ?? 'Consulta por nome, CPF, reclamante ou reclamada';
?>

<div class="form-card">
    <div class="form-card-header">
        <h1><?= htmlspecialchars($titulo_form ?? 'Cadastro de Clientes') ?></h1>
        <?php if ($modo === 'cadastro'): ?>
        <span class="form-badge<?= $somente_leitura ? ' form-badge-readonly' : '' ?>"><?= $somente_leitura ? 'Somente leitura' : 'Edição' ?></span>
        <?php else: ?>
        <span class="form-badge form-badge-readonly">Somente leitura</span>
        <?php endif; ?>
    </div>

    <div class="form-body" data-modo="<?= htmlspecialchars($modo) ?>" data-readonly="<?= $somente_leitura ? '1' : '0' ?>">

        <!-- Busca -->
        <section class="form-section form-section-busca">
            <div class="busca-top-row">
                <div class="busca-top-campos">
                    <h2 class="section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Busca rápida
                    </h2>
                    <div class="form-grid">
                        <?php if ($modo === 'cadastro'): ?>
                        <div class="field-group field-group-sm">
                            <label for="CADASTRO">Nº Cadastro</label>
                            <input type="text" id="CADASTRO" name="CADASTRO" readonly>
                        </div>
                        <?php endif; ?>
                        <div class="field-group field-group-full">
                            <label for="busca"><?= htmlspecialchars($label_busca) ?></label>
                            <div class="autocomplete-wrapper">
                                <input type="text" id="busca" autocomplete="off"
                                       placeholder="Digite nome, CPF, reclamada ou número do processo...">
                                <ul id="sugestoes" class="sugestoes-lista"></ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="foto-reclamante-wrap foto-topo-direita">
                    <label class="foto-reclamante-label">Foto do reclamante</label>
                    <button type="button" class="foto-reclamante-box" id="fotoBox" title="Visualizar foto">
                        <img id="fotoPreview" class="foto-preview" alt="Foto do reclamante" hidden>
                        <span class="foto-placeholder" id="fotoPlaceholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span>Sem foto</span>
                        </span>
                    </button>
                    <?php if ($modo === 'cadastro' && !$somente_leitura): ?>
                    <div class="foto-acoes">
                        <button type="button" class="btn-foto-acao" id="btnFotoImportar">Importar</button>
                        <button type="button" class="btn-foto-acao btn-foto-webcam" id="btnFotoWebcam">Tirar foto</button>
                    </div>
                    <p class="foto-webcam-dica">Webcam: use <strong>localhost</strong> neste PC (não o IP da rede).</p>
                    <input type="file" id="inputFoto" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Reclamante -->
        <section class="form-section">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Reclamante
            </h2>
            <div class="form-grid">
                <div class="field-group field-group-full">
                    <label for="RECLAMANTE">Nome</label>
                    <input type="text" id="RECLAMANTE" name="RECLAMANTE" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="CPF">CPF</label>
                    <input type="text" id="CPF" name="CPF" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="AREA">Área</label>
                    <select id="AREA" name="AREA" <?= $somente_leitura ? 'disabled' : '' ?>>
                        <option value="">Selecione...</option>
                        <option value="trabalhista">Trabalhista</option>
                        <option value="previdenciario">Previdenciário</option>
                        <option value="consumidor">Consumidor</option>
                    </select>
                </div>
                <?php if ($modo === 'cadastro'): ?>
                <div class="field-group">
                    <label for="DATA_NASC">Data de nascimento</label>
                    <input type="text" id="DATA_NASC" name="DATA_NASC" <?= $readonly ?>>
                </div>
                <div class="field-group field-group-lg">
                    <label for="CTPS">CTPS</label>
                    <input type="text" id="CTPS" name="CTPS" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="IDENTIDADE">Identidade</label>
                    <input type="text" id="IDENTIDADE" name="IDENTIDADE" <?= $readonly ?>>
                </div>
                <?php endif; ?>
                <div class="field-group field-group-full">
                    <label for="ENDERE_O">Endereço</label>
                    <input type="text" id="ENDERE_O" name="ENDERE_O" <?= $modo === 'cadastro' ? $readonly : 'readonly' ?>>
                </div>
            </div>
        </section>

        <?php if ($modo === 'cadastro'): ?>
        <!-- Contato -->
        <section class="form-section">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                Contato
            </h2>
            <div class="form-grid form-grid-2col">
                <div class="field-group">
                    <label for="FONE_RTE">Telefone 1</label>
                    <input type="text" id="FONE_RTE" name="FONE_RTE" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="FALAR_COM_FONE_1_">Falar com (fone 1)</label>
                    <input type="text" id="FALAR_COM_FONE_1_" name="FALAR_COM_FONE_1_" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="FONE_RTE_2_">Telefone 2</label>
                    <input type="text" id="FONE_RTE_2_" name="FONE_RTE_2_" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="FALAR_COM_FONE_2_">Falar com (fone 2)</label>
                    <input type="text" id="FALAR_COM_FONE_2_" name="FALAR_COM_FONE_2_" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="FONE_RTE_3_">Telefone 3</label>
                    <input type="text" id="FONE_RTE_3_" name="FONE_RTE_3_" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="FALAR_COM_FONE_3_">Falar com (fone 3)</label>
                    <input type="text" id="FALAR_COM_FONE_3_" name="FALAR_COM_FONE_3_" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="FONE_RTE_4_">Telefone 4</label>
                    <input type="text" id="FONE_RTE_4_" name="FONE_RTE_4_" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="FALAR_COM_FONE_4_">Falar com (fone 4)</label>
                    <input type="text" id="FALAR_COM_FONE_4_" name="FALAR_COM_FONE_4_" <?= $readonly ?>>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Reclamada -->
        <section class="form-section">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
                Reclamada
            </h2>
            <div class="form-grid">
                <div class="field-group field-group-full">
                    <label for="RECLAMADA">Nome</label>
                    <input type="text" id="RECLAMADA" name="RECLAMADA" <?= $readonly ?>>
                </div>
                <div class="field-group field-group-full">
                    <label for="END_RDA">Endereço</label>
                    <input type="text" id="END_RDA" name="END_RDA" <?= $readonly ?>>
                </div>
                <?php if ($modo === 'cadastro'): ?>
                <div class="field-group field-group-full">
                    <label for="COL_2__RECLAMADA">2ª Reclamada</label>
                    <input type="text" id="COL_2__RECLAMADA" name="COL_2__RECLAMADA" <?= $readonly ?>>
                </div>
                <div class="field-group field-group-full">
                    <label for="END_RDA_1">Endereço 2ª reclamada</label>
                    <input type="text" id="END_RDA_1" name="END_RDA_1" <?= $readonly ?>>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Processo -->
        <section class="form-section">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Dados do processo
            </h2>
            <div class="form-grid form-grid-processo">
                <div class="field-group field-group-sm">
                    <label for="JUNTA">Junta</label>
                    <input type="text" id="JUNTA" name="JUNTA" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="PROC">Nº Processo</label>
                    <input type="text" id="PROC" name="PROC" <?= $readonly ?>>
                </div>
                <div class="field-group field-group-data">
                    <label for="DIA_AUD">Data audiência</label>
                    <input type="text" id="DIA_AUD" name="DIA_AUD" <?= $readonly ?>>
                </div>
                <div class="field-group field-group-sm">
                    <label for="HORA_AUD">Hora</label>
                    <input type="text" id="HORA_AUD" name="HORA_AUD" <?= $readonly ?>>
                </div>
            </div>
        </section>

        <?php if ($modo === 'cadastro'): ?>
        <!-- Perícia -->
        <section class="form-section form-section-pericia">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Perícia
            </h2>
            <input type="hidden" id="PERICIA_ID" name="PERICIA_ID">
            <div class="form-grid form-grid-processo form-grid-pericia">
                <div class="field-group field-group-data">
                    <label for="DATA_PERICIA">Data-P</label>
                    <input type="text" id="DATA_PERICIA" name="DATA_PERICIA" placeholder="dd/mm/aaaa" <?= $readonly ?>>
                </div>
                <div class="field-group field-group-sm">
                    <label for="HORA_PERICIA">Hora</label>
                    <input type="text" id="HORA_PERICIA" name="HORA_PERICIA" placeholder="hh:mm" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="NOME_PERITO">Nome do Perito</label>
                    <input type="text" id="NOME_PERITO" name="NOME_PERITO" <?= $readonly ?>>
                </div>
                <div class="field-group">
                    <label for="ENDERECO_PERICIA">Endereço</label>
                    <input type="text" id="ENDERECO_PERICIA" name="ENDERECO_PERICIA" <?= $readonly ?>>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Andamento -->
        <section class="form-section form-section-andamento">
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>
                Andamento
            </h2>
            <div class="field-group field-group-full">
                <label for="ANDAMENTO">Histórico e status</label>
                <textarea id="ANDAMENTO" name="ANDAMENTO" rows="5" <?= $readonly ?>></textarea>
            </div>
        </section>

        <!-- Documento importado -->
        <section class="form-section documento-section" id="documentoPanel" hidden>
            <h2 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Documento importado
            </h2>
            <div class="documento-anexo-panel">
                <div class="documento-anexo-info">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <div>
                        <strong>Arquivo anexado</strong>
                        <span id="documentoNome">—</span>
                    </div>
                </div>
                <a href="#" id="linkDocumento" class="btn-documento-link" target="_blank" rel="noopener">Abrir documento</a>
                <?php if ($modo === 'cadastro' && !$somente_leitura): ?>
                <button type="button" id="btnExcluirDocumentoPainel" class="btn-documento-excluir" disabled>Excluir</button>
                <?php endif; ?>
            </div>
        </section>

        <!-- Ações -->
        <div class="form-actions-bar">
            <?php if ($modo === 'cadastro' && !$somente_leitura): ?>
            <button type="button" id="btnSalvar" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Salvar
            </button>
            <button type="button" id="btnNovo" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo registro
            </button>
            <button type="button" id="btnImporta" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Importa
            </button>
            <button type="button" id="btnExcluirDocumento" class="btn btn-secondary btn-excluir-doc" disabled
                    title="Nenhum documento anexado neste cadastro">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                Excluir doc.
            </button>
            <input type="file" id="inputDocumento" accept="application/pdf,image/jpeg,image/png,image/webp,image/gif" hidden>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Voltar ao menu
            </a>
        </div>
    </div>

    <?php if ($modo === 'cadastro' && !$somente_leitura): ?>
    <div class="modal-duplicado" id="modalDuplicado" hidden>
        <div class="modal-duplicado-backdrop" id="modalDuplicadoBackdrop"></div>
        <div class="modal-duplicado-panel" role="dialog" aria-labelledby="modalDuplicadoTitulo">
            <header class="modal-duplicado-header">
                <h2 id="modalDuplicadoTitulo">Cadastro já existente</h2>
                <button type="button" class="modal-duplicado-fechar" id="btnFecharDuplicado" aria-label="Fechar">&times;</button>
            </header>
            <div class="modal-duplicado-corpo">
                <p id="duplicadoTexto" class="duplicado-texto"></p>
                <ul id="duplicadoLista" class="duplicado-lista"></ul>
                <p class="duplicado-aviso">Substituir grava os dados do formulário no cadastro escolhido, sobrescrevendo as informações atuais.</p>
            </div>
            <div class="modal-duplicado-acoes">
                <button type="button" class="btn-filtro btn-filtro-secondary" id="btnDuplicadoCancelar">Cancelar</button>
                <button type="button" class="btn-filtro btn-filtro-secondary" id="btnDuplicadoNovo">Criar novo mesmo assim</button>
                <button type="button" class="btn-filtro btn-filtro-primary" id="btnDuplicadoSubstituir">Substituir cadastro existente</button>
            </div>
        </div>
    </div>
    <div class="modal-webcam" id="modalWebcam" hidden>
        <div class="modal-webcam-backdrop" id="modalWebcamBackdrop"></div>
        <div class="modal-webcam-panel" role="dialog" aria-labelledby="modalWebcamTitulo">
            <header class="modal-webcam-header">
                <h2 id="modalWebcamTitulo">Tirar foto do reclamante</h2>
                <button type="button" class="modal-webcam-fechar" id="btnFecharWebcam" aria-label="Fechar">&times;</button>
            </header>
            <div class="modal-webcam-corpo">
                <video id="webcamVideo" class="webcam-video" autoplay playsinline muted></video>
                <canvas id="webcamCanvas" hidden></canvas>
                <p class="webcam-dica">Posicione o rosto na câmera e clique em Capturar.</p>
            </div>
            <div class="modal-webcam-acoes">
                <button type="button" class="btn-filtro btn-filtro-secondary" id="btnCancelarWebcam">Cancelar</button>
                <button type="button" class="btn-filtro btn-filtro-primary" id="btnCapturarWebcam">Capturar e salvar</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
