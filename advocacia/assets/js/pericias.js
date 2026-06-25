(() => {
    const API_BASE = 'api/index.php';
    const modal = document.getElementById('modalPericia');
    const form = document.getElementById('formPericia');
    const campos = [
        'ID', 'CADASTRO', 'DATA_PERICIA', 'HORA_PERICIA', 'RECLAMANTE', 'CPF',
        'RECLAMADA', 'PROC_NUM', 'NOME_PERITO', 'ENDERECO',
    ];

    const mapIds = {
        ID: 'periciaId',
        CADASTRO: 'periciaCadastro',
        DATA_PERICIA: 'periciaData',
        HORA_PERICIA: 'periciaHora',
        RECLAMANTE: 'periciaReclamante',
        CPF: 'periciaCpf',
        RECLAMADA: 'periciaReclamada',
        PROC_NUM: 'periciaProc',
        NOME_PERITO: 'periciaPerito',
        ENDERECO: 'periciaEndereco',
    };

    document.querySelectorAll('.btn-editar-pericia').forEach((btn) => {
        btn.addEventListener('click', () => abrirEdicao(btn.dataset.id));
    });

    document.getElementById('btnFecharPericia')?.addEventListener('click', fecharModal);
    document.getElementById('btnCancelarPericia')?.addEventListener('click', fecharModal);
    document.getElementById('modalPericiaBackdrop')?.addEventListener('click', fecharModal);

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        await salvarPericia();
    });

    async function abrirEdicao(id) {
        if (!id) return;

        try {
            const resp = await fetch(`${API_BASE}?acao=pericia&id=${encodeURIComponent(id)}`);
            const data = await resp.json();

            if (!data.pericia) {
                alert(data.erro || 'Perícia não encontrada.');
                return;
            }

            preencherFormulario(data.pericia);
            modal.hidden = false;
        } catch (err) {
            alert('Erro ao carregar a perícia.');
        }
    }

    function preencherFormulario(pericia) {
        campos.forEach((campo) => {
            const el = document.getElementById(mapIds[campo]);
            if (el) {
                el.value = pericia[campo] ?? '';
            }
        });
    }

    function fecharModal() {
        modal.hidden = true;
    }

    async function salvarPericia() {
        const dados = {};
        campos.forEach((campo) => {
            const el = document.getElementById(mapIds[campo]);
            if (el) {
                dados[campo] = el.value;
            }
        });

        const btn = document.getElementById('btnSalvarPericia');
        btn.disabled = true;
        btn.textContent = 'Salvando...';

        try {
            const resp = await fetch(`${API_BASE}?acao=pericia_salvar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados),
            });
            const data = await resp.json();

            if (data.sucesso) {
                window.location.reload();
                return;
            }

            alert(data.erro || 'Não foi possível salvar a perícia.');
        } catch (err) {
            alert('Erro ao salvar a perícia.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Salvar';
        }
    }
})();
