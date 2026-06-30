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

        'DIA_AUD', 'HORA_AUD',

        'ANDAMENTO', 'CTPS', 'IDENTIDADE', 'CPF', 'AREA',

        'COL_2__RECLAMADA', 'END_RDA_1', 'cxpra_a'

    ];

    const PERICIA_CAMPOS = [
        'PERICIA_ID',
        'DATA_PERICIA',
        'HORA_PERICIA',
        'NOME_PERITO',
        'ENDERECO_PERICIA',
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



        initAnexos();



        if (modo === 'cadastro') {

            document.getElementById('btnSalvar')?.addEventListener('click', salvarRegistro);

            document.getElementById('btnNovo')?.addEventListener('click', novoRegistro);

            document.getElementById('btnImporta')?.addEventListener('click', () => {

                document.getElementById('inputDocumento')?.click();

            });

            document.getElementById('inputDocumento')?.addEventListener('change', onDocumentoSelecionado);

            document.getElementById('btnExcluirDocumento')?.addEventListener('click', excluirDocumento);
            document.getElementById('btnExcluirDocumentoPainel')?.addEventListener('click', excluirDocumento);

            initModalDuplicado();

        }

    }



    function initAnexos() {

        const fotoBox = document.getElementById('fotoBox');

        const inputFoto = document.getElementById('inputFoto');

        const btnFotoImportar = document.getElementById('btnFotoImportar');

        const btnFotoWebcam = document.getElementById('btnFotoWebcam');



        if (!somenteLeitura) {

            btnFotoImportar?.addEventListener('click', () => inputFoto?.click());

            inputFoto?.addEventListener('change', onFotoSelecionada);

            btnFotoWebcam?.addEventListener('click', abrirWebcam);

            initWebcamModal();

        }



        if (fotoBox) {

            fotoBox.addEventListener('click', () => {

                const img = document.getElementById('fotoPreview');

                if (img?.src && !img.hidden) {

                    window.open(img.src, '_blank');

                }

            });

            fotoBox.style.cursor = 'zoom-in';

            fotoBox.title = 'Clique para ampliar a foto';

        }

    }



    let webcamStream = null;

    let duplicadoResolver = null;



    function initModalDuplicado() {

        document.getElementById('btnFecharDuplicado')?.addEventListener('click', () => fecharModalDuplicado('cancelar'));

        document.getElementById('btnDuplicadoCancelar')?.addEventListener('click', () => fecharModalDuplicado('cancelar'));

        document.getElementById('modalDuplicadoBackdrop')?.addEventListener('click', () => fecharModalDuplicado('cancelar'));

        document.getElementById('btnDuplicadoNovo')?.addEventListener('click', () => fecharModalDuplicado('novo'));

        document.getElementById('btnDuplicadoSubstituir')?.addEventListener('click', () => {

            const selecionado = document.querySelector('input[name="duplicado_escolha"]:checked');

            const id = selecionado ? parseInt(selecionado.value, 10) : 0;

            if (id > 0) {

                fecharModalDuplicado('substituir', id);

            }

        });

    }



    function fecharModalDuplicado(acao, id = 0) {

        const modal = document.getElementById('modalDuplicado');

        if (modal) {

            modal.hidden = true;

        }

        if (duplicadoResolver) {

            const resolver = duplicadoResolver;

            duplicadoResolver = null;

            if (acao === 'substituir') {

                resolver({ acao: 'substituir', id });

            } else if (acao === 'novo') {

                resolver({ acao: 'novo' });

            } else {

                resolver({ acao: 'cancelar' });

            }

        }

    }



    function textoMotivoDuplicado(existentes) {

        const porNome = existentes.some(item => item.por_nome);

        const porCpf = existentes.some(item => item.por_cpf);

        if (porNome && porCpf) {

            return 'Já existe(m) cadastro(s) com este nome ou CPF:';

        }

        if (porCpf) {

            return 'Já existe(m) cadastro(s) com este CPF:';

        }

        return 'Já existe(m) cadastro(s) com este nome:';

    }



    function abrirModalDuplicado(data) {

        return new Promise((resolve) => {

            const modal = document.getElementById('modalDuplicado');

            const lista = document.getElementById('duplicadoLista');

            const texto = document.getElementById('duplicadoTexto');

            if (!modal || !lista || !texto) {

                resolve({ acao: 'cancelar' });

                return;

            }

            texto.textContent = textoMotivoDuplicado(data.existentes);

            lista.innerHTML = '';

            const multiplos = data.existentes.length > 1;

            data.existentes.forEach((item, idx) => {

                const li = document.createElement('li');

                li.className = 'duplicado-item';

                const partes = [`Cadastro #${item.id}`];

                if (item.reclamante) partes.push(item.reclamante);

                if (item.cpf) partes.push(`CPF: ${item.cpf}`);

                if (item.reclamada) partes.push(item.reclamada);

                if (multiplos) {

                    const label = document.createElement('label');

                    label.className = 'duplicado-item-label';

                    const radio = document.createElement('input');

                    radio.type = 'radio';

                    radio.name = 'duplicado_escolha';

                    radio.value = String(item.id);

                    radio.checked = idx === 0;

                    label.appendChild(radio);

                    const span = document.createElement('span');

                    span.textContent = partes.join(' — ');

                    label.appendChild(span);

                    li.appendChild(label);

                } else {

                    li.textContent = partes.join(' — ');

                }

                lista.appendChild(li);

            });

            duplicadoResolver = resolve;

            modal.hidden = false;

        });

    }



    function coletarDadosFormulario() {

        const dados = {};

        CAMPOS.forEach(campo => {

            const el = document.getElementById(campo);

            if (el) dados[campo] = el.value;

        });

        dados.pericia = coletarPericia();

        return dados;

    }



    async function enviarSalvar(opcoes = {}) {

        const dados = coletarDadosFormulario();

        if (opcoes.forcarNovo) {

            dados._forcar_novo = true;

        }

        if (opcoes.substituirId) {

            dados._substituir_id = opcoes.substituirId;

        }

        const resp = await fetch(`${API_BASE}?acao=salvar`, {

            method: 'POST',

            headers: { 'Content-Type': 'application/json' },

            body: JSON.stringify(dados)

        });

        const data = await resp.json();

        if (data.duplicado && data.existentes?.length) {

            const escolha = await abrirModalDuplicado(data);

            if (escolha.acao === 'substituir') {

                return enviarSalvar({ substituirId: escolha.id });

            }

            if (escolha.acao === 'novo') {

                return enviarSalvar({ forcarNovo: true });

            }

            return { sucesso: false, cancelado: true };

        }

        if (!resp.ok && data.erro) {

            throw new Error(data.erro);

        }

        return data;

    }



    function initWebcamModal() {

        document.getElementById('btnFecharWebcam')?.addEventListener('click', fecharWebcam);

        document.getElementById('btnCancelarWebcam')?.addEventListener('click', fecharWebcam);

        document.getElementById('modalWebcamBackdrop')?.addEventListener('click', fecharWebcam);

        document.getElementById('btnCapturarWebcam')?.addEventListener('click', capturarWebcam);

    }



    function urlAcessoLocalhost() {
        const porta = window.location.port ? ':' + window.location.port : '';
        return window.location.protocol + '//localhost' + porta + window.location.pathname;
    }



    function obterGetUserMedia() {
        if (navigator.mediaDevices?.getUserMedia) {
            return navigator.mediaDevices.getUserMedia.bind(navigator.mediaDevices);
        }

        const legado = navigator.getUserMedia
            || navigator.webkitGetUserMedia
            || navigator.mozGetUserMedia;

        if (!legado) {
            return null;
        }

        return (constraints) => new Promise((resolve, reject) => {
            legado.call(navigator, constraints, resolve, reject);
        });
    }



    async function abrirWebcam() {

        const modal = document.getElementById('modalWebcam');

        const video = document.getElementById('webcamVideo');

        if (!modal || !video) return;



        if (!window.isSecureContext) {
            const host = window.location.hostname;
            const urlLocal = urlAcessoLocalhost();
            alert(
                'A webcam não funciona pelo endereço ' + host + ' (rede local sem HTTPS).\n\n'
                + 'Neste computador, use:\n' + urlLocal + '\n\n'
                + 'Ou clique em Importar para escolher uma foto do computador.'
            );
            return;
        }

        const getUserMedia = obterGetUserMedia();
        if (!getUserMedia) {
            alert('Seu navegador não suporta webcam. Use o botão Importar.');
            return;
        }



        try {

            webcamStream = await getUserMedia({

                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },

                audio: false

            });

            video.srcObject = webcamStream;

            modal.hidden = false;

        } catch (err) {

            console.error(err);

            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                alert('Permissão da câmera negada. Clique no ícone de cadeado/câmera na barra do navegador e permita o acesso.');
            } else {
                alert('Não foi possível acessar a webcam. Verifique se a câmera está conectada e permitida no navegador.');
            }

        }

    }



    function fecharWebcam() {

        const modal = document.getElementById('modalWebcam');

        const video = document.getElementById('webcamVideo');



        if (webcamStream) {

            webcamStream.getTracks().forEach(track => track.stop());

            webcamStream = null;

        }



        if (video) {

            video.srcObject = null;

        }



        if (modal) {

            modal.hidden = true;

        }

    }



    async function capturarWebcam() {

        const video = document.getElementById('webcamVideo');

        const canvas = document.getElementById('webcamCanvas');

        const btn = document.getElementById('btnCapturarWebcam');



        if (!video || !canvas || video.videoWidth === 0) {

            alert('Aguarde a câmera carregar.');

            return;

        }



        canvas.width = video.videoWidth;

        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');

        ctx.drawImage(video, 0, 0);



        if (btn) btn.disabled = true;



        try {

            const blob = await new Promise((resolve, reject) => {

                canvas.toBlob(b => (b ? resolve(b) : reject(new Error('Falha ao capturar'))), 'image/jpeg', 0.9);

            });

            const file = new File([blob], 'webcam.jpg', { type: 'image/jpeg' });

            fecharWebcam();

            await enviarFotoArquivo(file);

        } catch (err) {

            alert(err.message || 'Erro ao capturar foto.');

            console.error(err);

        } finally {

            if (btn) btn.disabled = false;

        }

    }



    async function enviarFotoArquivo(file) {

        if (!file.type.startsWith('image/')) {

            alert('Selecione uma imagem (JPG, PNG, etc.).');

            return;

        }



        const id = await garantirCadastroSalvo();

        const formData = new FormData();

        formData.append('acao', 'upload_foto');

        formData.append('id', id);

        formData.append('arquivo', file);



        const resp = await fetch(API_BASE, { method: 'POST', body: formData });

        const data = await resp.json();



        if (!data.sucesso) {

            throw new Error(data.erro || 'Erro desconhecido');

        }



        if (data.registro) {

            preencherFormulario(data.registro);

        }

        alert('Foto salva com sucesso!');

    }



    async function onFotoSelecionada(e) {

        const file = e.target.files?.[0];

        if (!file) return;



        try {

            await enviarFotoArquivo(file);

        } catch (err) {

            alert(err.message || 'Erro ao importar foto.');

            console.error(err);

        } finally {

            e.target.value = '';

        }

    }



    function obterIdCadastro() {

        const el = document.getElementById('CADASTRO');

        const id = parseInt(el?.value || '0', 10);

        return id > 0 ? id : null;

    }



    async function garantirCadastroSalvo() {

        let id = obterIdCadastro();

        if (id && idCarregado === id) {

            return id;

        }

        const data = await enviarSalvar();

        if (data.cancelado) {

            throw new Error('Salvamento cancelado.');

        }

        if (!data.sucesso) {

            throw new Error(data.erro || 'Não foi possível salvar o cadastro.');

        }

        if (data.registro) {

            preencherFormulario(data.registro);

            idCarregado = data.id;

        }

        return data.id;

    }



    async function onDocumentoSelecionado(e) {

        const file = e.target.files?.[0];

        if (!file) return;



        const permitidos = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!permitidos.includes(file.type)) {

            alert('Formato não permitido. Use PDF ou imagem.');

            e.target.value = '';

            return;

        }



        try {

            const id = await garantirCadastroSalvo();

            const formData = new FormData();

            formData.append('acao', 'upload_documento');

            formData.append('id', id);

            formData.append('arquivo', file);



            const resp = await fetch(API_BASE, { method: 'POST', body: formData });

            const data = await resp.json();



            if (!data.sucesso) {

                alert('Erro ao salvar documento: ' + (data.erro || 'Erro desconhecido'));

                return;

            }



            if (data.registro) {

                preencherFormulario(data.registro);

            }

            alert('Documento importado com sucesso!');

        } catch (err) {

            alert(err.message || 'Erro ao importar documento.');

            console.error(err);

        } finally {

            e.target.value = '';

        }

    }



    function atualizarFoto(registro) {

        const img = document.getElementById('fotoPreview');

        const placeholder = document.getElementById('fotoPlaceholder');



        if (!img || !placeholder) return;



        if (registro.tem_foto && registro.foto_url) {

            img.src = registro.foto_url + '&t=' + Date.now();

            img.hidden = false;

            placeholder.hidden = true;

        } else {

            img.removeAttribute('src');

            img.hidden = true;

            placeholder.hidden = false;

        }

    }



    function atualizarDocumento(registro) {

        const panel = document.getElementById('documentoPanel');

        const nomeEl = document.getElementById('documentoNome');

        const link = document.getElementById('linkDocumento');

        const btnExcluir = document.getElementById('btnExcluirDocumento');

        const btnExcluirPainel = document.getElementById('btnExcluirDocumentoPainel');



        if (!panel || !nomeEl || !link) return;



        const temDoc = !!(registro.tem_documento && registro.documento_url);

        if (temDoc) {

            panel.hidden = false;

            nomeEl.textContent = registro.documento_nome || 'Documento anexado';

            link.href = registro.documento_url;

        } else {

            panel.hidden = true;

            nomeEl.textContent = '—';

            link.href = '#';

        }

        if (btnExcluir) {
            btnExcluir.hidden = !temDoc || somenteLeitura;
        }

        if (btnExcluirPainel) {
            btnExcluirPainel.hidden = !temDoc || somenteLeitura;
        }

    }



    async function excluirDocumento() {

        const id = obterIdCadastro();

        if (!id) {

            alert('Abra ou salve um cadastro antes de excluir o documento.');

            return;

        }

        const nome = document.getElementById('documentoNome')?.textContent?.trim() || 'documento';

        if (!confirm('Excluir o documento "' + nome + '" deste cadastro?\n\nEsta ação não pode ser desfeita.')) {

            return;

        }

        try {

            const resp = await fetch(`${API_BASE}?acao=excluir_documento`, {

                method: 'POST',

                headers: { 'Content-Type': 'application/json' },

                body: JSON.stringify({ id })

            });

            const data = await resp.json();

            if (!data.sucesso) {

                alert('Erro ao excluir documento: ' + (data.erro || 'Erro desconhecido'));

                return;

            }

            if (data.registro) {

                preencherFormulario(data.registro);

            } else {

                atualizarDocumento({ tem_documento: false });

            }

            alert('Documento excluído com sucesso.');

        } catch (err) {

            alert('Erro ao excluir documento.');

            console.error(err);

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

            const texto = item.label || item.display || '';

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

        preencherPericia(registro.pericia || null);

        atualizarFoto(registro);

        atualizarDocumento(registro);

    }



    function preencherPericia(pericia) {

        const mapa = {
            PERICIA_ID: pericia?.ID ?? '',
            DATA_PERICIA: pericia?.DATA_PERICIA ?? '',
            HORA_PERICIA: pericia?.HORA_PERICIA ?? '',
            NOME_PERITO: pericia?.NOME_PERITO ?? '',
            ENDERECO_PERICIA: pericia?.ENDERECO ?? '',
        };

        PERICIA_CAMPOS.forEach(campo => {

            const el = document.getElementById(campo);

            if (el) {

                el.value = mapa[campo] ?? '';

            }

        });

    }



    function coletarPericia() {

        const pericia = {
            ID: document.getElementById('PERICIA_ID')?.value ?? '',
            DATA_PERICIA: document.getElementById('DATA_PERICIA')?.value ?? '',
            HORA_PERICIA: document.getElementById('HORA_PERICIA')?.value ?? '',
            NOME_PERITO: document.getElementById('NOME_PERITO')?.value ?? '',
            ENDERECO: document.getElementById('ENDERECO_PERICIA')?.value ?? '',
        };

        return pericia;

    }



    function limparCamposFormulario() {

        CAMPOS.forEach(campo => {

            const el = document.getElementById(campo);

            if (el && campo !== 'CADASTRO') {

                el.value = '';

            }

        });

        preencherPericia(null);

        atualizarFoto({ tem_foto: false });

        atualizarDocumento({ tem_documento: false });

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

        try {

            const data = await enviarSalvar();

            if (data.cancelado) {

                return;

            }

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

            alert('Erro ao salvar: ' + (err.message || 'Erro desconhecido'));

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


