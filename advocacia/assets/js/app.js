/**
 * Autocomplete estilo Access: ao digitar, completa o nome e preenche o formulário.
 */
const App = (() => {
    const API_BASE = 'api/index.php';
    const CAMPOS = [
        'CADASTRO', 'RECLAMANTE', 'DATA_NASC', 'ENDERE_O',
        'FONE_RTE', 'FONE_RTE_2_', 'FONE_RTE_3_', 'FONE_RTE_4_',
        'FALAR_COM_FONE_1_', 'FALAR_COM_FONE_2_', 'FALAR_COM_FONE_3_', 'FALAR_COM_FONE_4_',
        'RECLAMADA', 'END_RDA', 'JUNTA', 'PROC',
        'DIA_AUD', 'HORA_AUD', 'PRA_A_DIA', 'PRA_A_HORA',
        'ANDAMENTO', 'CTPS', 'IDENTIDADE', 'CPF',
        'COL_2__RECLAMADA', 'END_RDA_1', 'cxpra_a'
    ];

    let modoAtual = 'cadastro';
    let somenteLeitura = false;
    let debounceTimer = null;
    let indiceSelecionado = 0;
    let sugestoesAtuais = [];
    let idCarregado = null;
    let buscando = false;

    const tipoBuscaMap = {
        cadastro: 'geral',
        consulta_processo: 'geral',
        consulta_reclamante: 'geral',
        consulta_reclamada: 'geral'
    };

    function initFormulario(modo) {
        modoAtual = modo;
        const formBody = document.querySelector('.form-body');
        somenteLeitura = formBody?.dataset.readonly === '1';

        const inputBusca = document.getElementById('busca');
        if (!inputBusca) return;

        inputBusca.addEventListener('input', onBuscaInput);
        inputBusca.addEventListener('keydown', onBuscaKeydown);
        inputBusca.addEventListener('blur', () => {
            setTimeout(fecharSugestoes, 200);
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.autocomplete-wrapper')) {
                fecharSugestoes();
            }
        });

        if (modo === 'cadastro') {
            document.getElementById('btnSalvar')?.addEventListener('click', salvarRegistro);
            document.getElementById('btnNovo')?.addEventListener('click', novoRegistro);
        }
    }

    function onBuscaInput(e) {
        const termo = e.target.value;
        clearTimeout(debounceTimer);
        removerOverlay();

        if (termo.trim().length < 2) {
            fecharSugestoes();
            if (termo.trim().length === 0) {
                limparCamposFormulario();
                idCarregado = null;
            }
            return;
        }

        debounceTimer = setTimeout(() => buscarSugestoes(termo.trim()), 150);
    }

    function onBuscaKeydown(e) {
        const lista = document.getElementById('sugestoes');
        const itens = lista?.querySelectorAll('li') || [];

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (sugestoesAtuais.length === 0) return;
            indiceSelecionado = Math.min(indiceSelecionado + 1, sugestoesAtuais.length - 1);
            atualizarSelecao(itens);
            aplicarSugestaoAtiva();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (sugestoesAtuais.length === 0) return;
            indiceSelecionado = Math.max(indiceSelecionado - 1, 0);
            atualizarSelecao(itens);
            aplicarSugestaoAtiva();
        } else if (e.key === 'Enter' || e.key === 'Tab') {
            if (sugestoesAtuais.length > 0) {
                e.preventDefault();
                confirmarSugestaoAtiva();
            }
        } else if (e.key === 'Escape') {
            fecharSugestoes();
            removerOverlay();
        }
    }

    function atualizarSelecao(itens) {
        itens.forEach((li, i) => {
            li.classList.toggle('selecionado', i === indiceSelecionado);
        });
    }

    async function buscarSugestoes(termo) {
        if (buscando) return;
        buscando = true;

        const tipo = tipoBuscaMap[modoAtual] || 'geral';
        const input = document.getElementById('busca');

        try {
            const resp = await fetch(`${API_BASE}?acao=buscar&q=${encodeURIComponent(termo)}&tipo=${tipo}`);
            const data = await resp.json();

            if (!data.resultados || data.resultados.length === 0) {
                sugestoesAtuais = [];
                indiceSelecionado = 0;
                fecharSugestoes();
                removerOverlay();
                return;
            }

            // Só aplica se o termo ainda for o mesmo (usuário não digitou mais)
            if (input && input.value.trim() !== termo) return;

            sugestoesAtuais = data.resultados;
            indiceSelecionado = 0;
            renderizarSugestoes(data.resultados, termo);
            aplicarSugestaoAtiva(termo);
        } catch (err) {
            console.error('Erro na busca:', err);
        } finally {
            buscando = false;
        }
    }

    function aplicarSugestaoAtiva(termoDigitado) {
        const item = sugestoesAtuais[indiceSelecionado];
        if (!item) return;

        const input = document.getElementById('busca');
        const termo = termoDigitado ?? input?.value.trim() ?? '';
        const texto = item.display || item.reclamante || item.label;

        aplicarTypeahead(termo, texto);
        carregarRegistro(item.id);
    }

    function confirmarSugestaoAtiva() {
        const item = sugestoesAtuais[indiceSelecionado];
        if (!item) return;

        const input = document.getElementById('busca');
        const texto = item.display || item.reclamante || item.label;

        if (input) {
            input.value = texto;
            removerOverlay();
        }

        fecharSugestoes();
        carregarRegistro(item.id);
    }

    /**
     * Typeahead estilo Access: texto digitado + restante em destaque (fundo preto).
     */
    function aplicarTypeahead(termo, textoCompleto) {
        const input = document.getElementById('busca');
        if (!input || !textoCompleto || !termo) return;

        const lower = textoCompleto.toLowerCase();
        const termoLower = termo.toLowerCase();
        const idx = lower.indexOf(termoLower);

        if (idx === -1) {
            removerOverlay();
            return;
        }

        const antes = textoCompleto.substring(0, idx);
        const match = textoCompleto.substring(idx, idx + termo.length);
        const restante = textoCompleto.substring(idx + termo.length);

        input.style.color = 'transparent';
        input.style.caretColor = '#1e293b';

        let overlay = input.parentElement.querySelector('.busca-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'busca-overlay';
            input.parentElement.style.position = 'relative';
            input.parentElement.appendChild(overlay);
        }

        overlay.innerHTML =
            `<span class="typeahead-antes">${escapeHtml(antes)}</span>` +
            `<span class="typeahead-digitado">${escapeHtml(match)}</span>` +
            `<span class="typeahead-sugestao">${escapeHtml(restante)}</span>`;
    }

    function removerOverlay() {
        const input = document.getElementById('busca');
        if (!input) return;
        input.style.color = '';
        input.style.caretColor = '';
        input.parentElement?.querySelector('.busca-overlay')?.remove();
    }

    function renderizarSugestoes(resultados, termo) {
        const lista = document.getElementById('sugestoes');
        if (!lista) return;

        lista.innerHTML = '';

        resultados.forEach((item, index) => {
            const li = document.createElement('li');
            const texto = item.display || item.label;
            li.innerHTML = destacarTermo(texto, termo);
            li.addEventListener('mousedown', (e) => {
                e.preventDefault();
                indiceSelecionado = index;
                confirmarSugestaoAtiva();
            });
            li.addEventListener('mouseenter', () => {
                indiceSelecionado = index;
                atualizarSelecao(lista.querySelectorAll('li'));
                aplicarSugestaoAtiva();
            });
            lista.appendChild(li);
        });

        lista.classList.add('ativo');
        atualizarSelecao(lista.querySelectorAll('li'));
    }

    function destacarTermo(texto, termo) {
        if (!termo) return escapeHtml(texto);

        const lower = texto.toLowerCase();
        const termoLower = termo.toLowerCase();
        const idx = lower.indexOf(termoLower);

        if (idx === -1) return escapeHtml(texto);

        const antes = texto.substring(0, idx);
        const match = texto.substring(idx, idx + termo.length);
        const depois = texto.substring(idx + termo.length);

        return `${escapeHtml(antes)}<strong>${escapeHtml(match)}</strong>${escapeHtml(depois)}`;
    }

    async function carregarRegistro(id) {
        if (idCarregado === id) return;
        idCarregado = id;

        try {
            const resp = await fetch(`${API_BASE}?acao=registro&id=${id}`);
            const data = await resp.json();

            if (data.registro) {
                preencherFormulario(data.registro);
            }
        } catch (err) {
            console.error('Erro ao carregar registro:', err);
        }
    }

    function preencherFormulario(registro) {
        CAMPOS.forEach(campo => {
            const el = document.getElementById(campo);
            if (el) {
                el.value = registro[campo] ?? '';
            }
        });
    }

    function limparCamposFormulario() {
        CAMPOS.forEach(campo => {
            const el = document.getElementById(campo);
            if (el && campo !== 'CADASTRO') {
                el.value = '';
            }
        });
    }

    function limparFormulario() {
        limparCamposFormulario();
        idCarregado = null;

        const input = document.getElementById('busca');
        if (input) {
            input.value = '';
            removerOverlay();
        }
        fecharSugestoes();
    }

    function fecharSugestoes() {
        const lista = document.getElementById('sugestoes');
        if (lista) {
            lista.classList.remove('ativo');
            lista.innerHTML = '';
        }
    }

    async function salvarRegistro() {
        const dados = {};
        CAMPOS.forEach(campo => {
            const el = document.getElementById(campo);
            if (el) dados[campo] = el.value;
        });

        try {
            const resp = await fetch(`${API_BASE}?acao=salvar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            const data = await resp.json();

            if (data.sucesso) {
                alert('Registro salvo com sucesso! Cadastro: ' + data.id);
                if (data.registro) {
                    preencherFormulario(data.registro);
                    idCarregado = data.id;
                }
            } else {
                alert('Erro ao salvar: ' + (data.erro || 'Erro desconhecido'));
            }
        } catch (err) {
            alert('Erro ao salvar registro.');
            console.error(err);
        }
    }

    async function novoRegistro() {
        limparFormulario();
        try {
            const resp = await fetch(`${API_BASE}?acao=proximo_id`);
            const data = await resp.json();
            const el = document.getElementById('CADASTRO');
            if (el && data.id) el.value = data.id;
        } catch (err) {
            console.error(err);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    return { initFormulario };
})();
