/**
 * Dashboard: aniversariantes do dia, seleção múltipla e envio via WhatsApp Web.
 */
const Dashboard = (() => {
    const API = 'api/index.php?acao=aniversariantes';
    let aniversariantes = [];
    let marcados = new Set();
    let modoEnvio = 'individual'; // individual | multiplo
    let filaEnvio = [];
    let indiceFila = 0;
    let imagemImportada = null; // { blob, url, nome }

    const perm = window.APP_PERMISSOES || {};
    const podeEnviar = !!perm.aniversariantes_enviar;

    function init() {
        document.getElementById('btnAniversariantes')?.addEventListener('click', abrirModal);
        document.getElementById('btnFecharAniv')?.addEventListener('click', fecharModal);
        document.getElementById('modalAnivBackdrop')?.addEventListener('click', fecharModal);
        document.getElementById('btnAnivVoltar')?.addEventListener('click', voltarLista);
        document.getElementById('btnEnviarWhatsApp')?.addEventListener('click', enviarWhatsApp);
        document.getElementById('anivComImagem')?.addEventListener('change', toggleAreaImagem);
        document.getElementById('anivImagemInput')?.addEventListener('change', importarImagem);
        document.getElementById('btnRemoverImagem')?.addEventListener('click', removerImagem);
        document.getElementById('anivMarcarTodos')?.addEventListener('change', marcarTodos);
        document.getElementById('btnEnviarSelecionados')?.addEventListener('click', abrirEnvioMultiplo);
        document.getElementById('anivCheckDetalhe')?.addEventListener('change', onCheckDetalheChange);
    }

    async function abrirModal() {
        document.getElementById('modalAniversario').hidden = false;
        document.body.style.overflow = 'hidden';
        marcados.clear();
        limparImagemImportada();
        voltarLista();
        await carregarAniversariantes();
    }

    function fecharModal() {
        document.getElementById('modalAniversario').hidden = true;
        document.body.style.overflow = '';
        limparImagemImportada();
    }

    async function carregarAniversariantes() {
        const lista = document.getElementById('listaAniversariantes');
        lista.innerHTML = '<p class="modal-aniv-loading">Carregando...</p>';
        document.getElementById('wrapListaAniv').hidden = false;
        document.getElementById('detalheAniversariante').hidden = true;

        try {
            const resp = await fetch(API);
            const data = await resp.json();

            if (data.erro) {
                lista.innerHTML = `<p class="modal-aniv-vazio">Erro: ${escapeHtml(data.erro)}</p>`;
                return;
            }

            aniversariantes = data.aniversariantes || [];
            document.getElementById('modalAnivData').textContent = data.data ? `Hoje: ${data.data}` : '';

            if (aniversariantes.length === 0) {
                lista.innerHTML = `<p class="modal-aniv-vazio">Nenhum aniversariante em ${data.data || 'hoje'}.</p>`;
                atualizarContador();
                return;
            }

            lista.innerHTML = aniversariantes.map((p, i) => {
                const semTel = !p.telefone;
                if (!podeEnviar) {
                    return `
                    <button type="button" class="aniv-item aniv-item-somente-ver${semTel ? ' aniv-sem-telefone' : ''}" data-index="${i}">
                        <span class="aniv-item-nome">${escapeHtml(p.nome)}</span>
                        <span class="aniv-item-meta">
                            ${p.cpf ? `<span class="aniv-item-cpf">CPF: ${escapeHtml(p.cpf)}</span>` : ''}
                            <span class="aniv-item-idade">${p.idade !== null ? p.idade + ' anos' : ''}</span>
                        </span>
                    </button>`;
                }
                return `
                <div class="aniv-item-row${semTel ? ' aniv-item-row-sem-tel' : ''}">
                    <input type="checkbox" class="aniv-check-item" id="aniv-chk-${i}" data-index="${i}"
                           ${marcados.has(i) ? 'checked' : ''}>
                    <label for="aniv-chk-${i}" class="aniv-check-label" title="Selecionar para envio"></label>
                    <button type="button" class="aniv-item${semTel ? ' aniv-sem-telefone' : ''}" data-index="${i}"
                            title="${semTel ? 'Sem telefone cadastrado' : ''}">
                        <span class="aniv-item-nome">${escapeHtml(p.nome)}</span>
                        <span class="aniv-item-meta">
                            ${p.cpf ? `<span class="aniv-item-cpf">CPF: ${escapeHtml(p.cpf)}</span>` : ''}
                            ${semTel ? '<span class="aniv-tag-sem-tel">Sem telefone</span>' : ''}
                            <span class="aniv-item-idade">${p.idade !== null ? p.idade + ' anos' : ''}</span>
                        </span>
                    </button>
                </div>
            `}).join('');

            lista.querySelectorAll('.aniv-check-item').forEach(chk => {
                chk.addEventListener('change', () => {
                    const idx = parseInt(chk.dataset.index, 10);
                    if (chk.checked) marcados.add(idx);
                    else marcados.delete(idx);
                    atualizarContador();
                });
            });

            lista.querySelectorAll('.aniv-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    abrirDetalhe(parseInt(btn.dataset.index, 10), podeEnviar ? 'individual' : 'visualizar');
                });
            });

            const chkTodos = document.getElementById('anivMarcarTodos');
            if (chkTodos) chkTodos.checked = false;
            atualizarContador();
        } catch (err) {
            lista.innerHTML = '<p class="modal-aniv-vazio">Erro ao carregar aniversariantes.</p>';
            console.error(err);
        }
    }

    function marcarTodos(e) {
        const marcar = e.target.checked;
        aniversariantes.forEach((_, i) => {
            if (marcar) marcados.add(i);
            else marcados.delete(i);
        });
        document.querySelectorAll('.aniv-check-item').forEach(chk => {
            chk.checked = marcar;
        });
        atualizarContador();
    }

    function atualizarContador() {
        const n = marcados.size;
        const contador = document.getElementById('anivContador');
        const btnSel = document.getElementById('btnEnviarSelecionados');
        if (contador) contador.textContent = n + ' selecionado' + (n !== 1 ? 's' : '');
        if (btnSel) btnSel.disabled = n === 0;

        const todos = aniversariantes.length > 0 && n === aniversariantes.length;
        const chkTodos = document.getElementById('anivMarcarTodos');
        if (chkTodos) {
            chkTodos.checked = todos;
            chkTodos.indeterminate = n > 0 && !todos;
        }
    }

    function abrirEnvioMultiplo() {
        if (marcados.size === 0) return;
        filaEnvio = Array.from(marcados).sort((a, b) => a - b).map(i => aniversariantes[i]);
        indiceFila = 0;
        abrirDetalhe(null, 'multiplo');
    }

    function abrirDetalhe(index, modo) {
        modoEnvio = modo;

        document.getElementById('wrapListaAniv').hidden = true;
        document.getElementById('detalheAniversariante').hidden = false;

        const pessoa = modo === 'individual' ? aniversariantes[index] : filaEnvio[indiceFila];
        const checkDetalhe = document.getElementById('anivCheckDetalhe');
        const enviandoPara = document.getElementById('anivEnviandoPara');
        const btnTexto = document.getElementById('btnEnviarTexto');

        if (modo === 'multiplo') {
            document.getElementById('anivNome').textContent = 'Envio para selecionados';
            enviandoPara.hidden = false;
            enviandoPara.textContent = `Enviando ${indiceFila + 1} de ${filaEnvio.length}: ${pessoa.nome}`;
            checkDetalhe.hidden = true;

            let info = filaEnvio.map(p => p.nome).join(', ');
            if (info.length > 120) info = info.substring(0, 120) + '...';
            document.getElementById('anivInfo').textContent = 'Selecionados: ' + info;
            btnTexto.textContent = filaEnvio.length > 1 ? 'Enviar e próximo' : 'Enviar mensagem';
        } else {
            document.getElementById('anivNome').textContent = pessoa.nome;
            if (enviandoPara) enviandoPara.hidden = true;
            if (checkDetalhe) {
                checkDetalhe.hidden = false;
                checkDetalhe.checked = marcados.has(index);
                checkDetalhe.dataset.index = index;
            }

            let info = '';
            if (pessoa.cpf) info += 'CPF: ' + pessoa.cpf;
            if (pessoa.data_nasc) info += (info ? ' · ' : '') + 'Nascimento: ' + pessoa.data_nasc;
            if (pessoa.idade !== null) info += (info ? ' · ' : '') + pessoa.idade + ' anos';
            if (pessoa.fone_display) info += (info ? ' · ' : '') + 'Tel: ' + pessoa.fone_display;
            document.getElementById('anivInfo').textContent = info;
            if (btnTexto) btnTexto.textContent = 'Enviar mensagem';
        }

        const msgEl = document.getElementById('anivMensagem');
        if (msgEl) {
            const msgPadrao = `Olá, {nome}! 🎂\n\nA equipe Moura Galvão Advogados Associados deseja a você um feliz aniversário, com muita saúde, paz e realizações!\n\nUm abraço.`;
            msgEl.value = msgPadrao;
        }

        atualizarAvisoTelefone(pessoa);
    }

    function onCheckDetalheChange(e) {
        const index = parseInt(e.target.dataset.index, 10);
        if (e.target.checked) marcados.add(index);
        else marcados.delete(index);
        document.querySelectorAll(`.aniv-check-item[data-index="${index}"]`).forEach(chk => {
            chk.checked = e.target.checked;
        });
        atualizarContador();
    }

    function atualizarAvisoTelefone(pessoa) {
        const aviso = document.getElementById('anivAviso');
        if (!pessoa.telefone) {
            aviso.hidden = false;
            aviso.textContent = 'Telefone não encontrado no cadastro. O WhatsApp abrirá sem número — escolha o contato manualmente.';
        } else {
            aviso.hidden = true;
        }
    }

    function voltarLista() {
        modoEnvio = 'individual';
        filaEnvio = [];
        indiceFila = 0;
        document.getElementById('detalheAniversariante').hidden = true;
        document.getElementById('wrapListaAniv').hidden = false;
    }

    function toggleAreaImagem() {
        const checked = document.getElementById('anivComImagem').checked;
        document.getElementById('anivImagemArea').hidden = !checked;
        if (!checked) removerImagem();
    }

    function importarImagem(e) {
        const file = e.target.files?.[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('Selecione um arquivo de imagem (JPG, PNG, GIF ou WebP).');
            return;
        }

        limparImagemImportada();

        const url = URL.createObjectURL(file);
        imagemImportada = { blob: file, url, nome: file.name };

        const preview = document.getElementById('anivPreview');
        const img = document.getElementById('anivPreviewImg');
        img.src = url;
        preview.hidden = false;
    }

    function removerImagem() {
        limparImagemImportada();
        document.getElementById('anivImagemInput').value = '';
        document.getElementById('anivPreview').hidden = true;
    }

    function limparImagemImportada() {
        if (imagemImportada?.url) {
            URL.revokeObjectURL(imagemImportada.url);
        }
        imagemImportada = null;
    }

    function personalizarMensagem(template, nome) {
        return template.replace(/\{nome\}/g, nome);
    }

    function baixarImagemAnexo() {
        if (!imagemImportada) return false;

        const link = document.createElement('a');
        link.href = imagemImportada.url;
        link.download = imagemImportada.nome || 'imagem-aniversario.jpg';
        link.click();
        return true;
    }

    function abrirWhatsApp(pessoa, mensagem) {
        let url = 'https://web.whatsapp.com/send?';
        if (pessoa.telefone) {
            url += 'phone=' + encodeURIComponent(pessoa.telefone) + '&';
        }
        url += 'text=' + encodeURIComponent(mensagem);
        window.open(url, '_blank');
    }

    function enviarWhatsApp() {
        if (!podeEnviar) return;

        const template = document.getElementById('anivMensagem').value.trim();
        const comImagem = document.getElementById('anivComImagem').checked;

        if (!template) {
            alert('Digite uma mensagem antes de enviar.');
            return;
        }

        if (comImagem && !imagemImportada) {
            alert('Marque "Enviar também imagem" e importe uma imagem, ou desmarque a opção.');
            return;
        }

        if (modoEnvio === 'multiplo') {
            const pessoa = filaEnvio[indiceFila];
            const mensagem = personalizarMensagem(template, pessoa.nome);
            abrirWhatsApp(pessoa, mensagem);

            if (comImagem) {
                baixarImagemAnexo();
                if (indiceFila === 0) {
                    setTimeout(() => {
                        alert('WhatsApp aberto! Anexe a imagem baixada na conversa.');
                    }, 400);
                }
            }

            indiceFila++;
            if (indiceFila < filaEnvio.length) {
                abrirDetalhe(null, 'multiplo');
                return;
            }

            alert('Todos os selecionados foram processados!');
            voltarLista();
            return;
        }

        const index = parseInt(document.getElementById('anivCheckDetalhe').dataset.index, 10);
        const pessoa = aniversariantes[index];
        if (!pessoa) return;

        const mensagem = personalizarMensagem(template, pessoa.nome);
        abrirWhatsApp(pessoa, mensagem);

        if (comImagem) {
            baixarImagemAnexo();
            setTimeout(() => {
                alert('WhatsApp aberto! Anexe a imagem baixada na conversa para enviar ao cliente.');
            }, 400);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', init);
    return { init };
})();
