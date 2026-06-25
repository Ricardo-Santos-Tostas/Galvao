(() => {
    const API = 'api/index.php';
    const modal = document.getElementById('modalUsuario');
    const form = document.getElementById('formUsuario');
    const wrapPermissoes = document.getElementById('wrapPermissoes');
    const chkAdmin = document.getElementById('usuarioAdmin');
    const usuarios = window.USUARIOS_DATA || [];

    document.getElementById('btnNovoUsuario')?.addEventListener('click', () => abrirModal());
    document.getElementById('btnFecharUsuario')?.addEventListener('click', fecharModal);
    document.getElementById('btnCancelarUsuario')?.addEventListener('click', fecharModal);
    document.getElementById('modalUsuarioBackdrop')?.addEventListener('click', fecharModal);
    chkAdmin?.addEventListener('change', toggleAdmin);
    form?.addEventListener('submit', salvarUsuario);

    document.querySelectorAll('.btn-editar-usuario').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = parseInt(btn.dataset.id, 10);
            const usuario = usuarios.find((u) => u.id === id);
            if (usuario) abrirModal(usuario);
        });
    });

    document.querySelectorAll('.btn-excluir-usuario').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const id = parseInt(btn.dataset.id, 10);
            if (!confirm('Excluir este usuário?')) return;

            const resp = await fetch(`${API}?acao=usuario_excluir`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id }),
            });
            const data = await resp.json();
            if (data.sucesso) window.location.reload();
            else alert(data.erro || 'Erro ao excluir.');
        });
    });

    document.querySelectorAll('.perm-editar').forEach((chk) => {
        chk.addEventListener('change', () => {
            if (chk.checked) {
                const ver = document.querySelector(`.perm-ver[data-modulo="${chk.dataset.modulo}"]`);
                if (ver) ver.checked = true;
            }
        });
    });

    function abrirModal(usuario = null) {
        form.reset();
        limparPermissoes();

        if (usuario) {
            document.getElementById('modalUsuarioTitulo').textContent = 'Editar usuário';
            document.getElementById('usuarioId').value = usuario.id;
            document.getElementById('usuarioNome').value = usuario.nome;
            document.getElementById('usuarioLogin').value = usuario.login;
            document.getElementById('usuarioAtivo').checked = !!usuario.ativo;
            document.getElementById('usuarioAdmin').checked = !!usuario.is_admin;
            document.getElementById('usuarioSenhaDica').textContent = 'Deixe em branco para manter a senha atual.';
            preencherPermissoes(usuario.permissoes || {});
        } else {
            document.getElementById('modalUsuarioTitulo').textContent = 'Novo usuário';
            document.getElementById('usuarioId').value = '';
            document.getElementById('usuarioSenhaDica').textContent = 'Obrigatória para novos usuários.';
        }

        toggleAdmin();
        modal.hidden = false;
    }

    function fecharModal() {
        modal.hidden = true;
    }

    function toggleAdmin() {
        const admin = chkAdmin.checked;
        wrapPermissoes.hidden = admin;
    }

    function limparPermissoes() {
        document.querySelectorAll('.perm-ver, .perm-editar, .perm-aniv').forEach((el) => {
            el.checked = false;
        });
    }

    function preencherPermissoes(permissoes) {
        Object.entries(permissoes).forEach(([modulo, cfg]) => {
            const ver = document.querySelector(`.perm-ver[data-modulo="${modulo}"]`);
            const editar = document.querySelector(`.perm-editar[data-modulo="${modulo}"]`);
            const aniv = document.querySelector(`.perm-aniv[data-modulo="${modulo}"]`);

            if (ver) ver.checked = !!cfg.ver || !!cfg.editar;
            if (editar) editar.checked = !!cfg.editar;
            if (aniv) aniv.checked = !!cfg.ver;
        });
    }

    function coletarPermissoes() {
        const permissoes = {};

        document.querySelectorAll('.perm-ver').forEach((chk) => {
            const modulo = chk.dataset.modulo;
            permissoes[modulo] = permissoes[modulo] || { ver: false, editar: false };
            permissoes[modulo].ver = chk.checked;
        });

        document.querySelectorAll('.perm-editar').forEach((chk) => {
            const modulo = chk.dataset.modulo;
            permissoes[modulo] = permissoes[modulo] || { ver: false, editar: false };
            permissoes[modulo].editar = chk.checked;
            if (chk.checked) permissoes[modulo].ver = true;
        });

        document.querySelectorAll('.perm-aniv').forEach((chk) => {
            permissoes[chk.dataset.modulo] = { ver: chk.checked, editar: false };
        });

        return permissoes;
    }

    async function salvarUsuario(e) {
        e.preventDefault();

        const payload = {
            id: document.getElementById('usuarioId').value || null,
            nome: document.getElementById('usuarioNome').value.trim(),
            login: document.getElementById('usuarioLogin').value.trim(),
            senha: document.getElementById('usuarioSenha').value,
            ativo: document.getElementById('usuarioAtivo').checked,
            is_admin: document.getElementById('usuarioAdmin').checked,
            permissoes: coletarPermissoes(),
        };

        const resp = await fetch(`${API}?acao=usuario_salvar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await resp.json();

        if (data.sucesso) {
            window.location.reload();
            return;
        }

        alert(data.erro || 'Erro ao salvar usuário.');
    }
})();
