/**
 * Abre página leve de impressão.
 */
function imprimirRelatorio() {
    const atual = new URLSearchParams(window.location.search);
    const tipo = document.body.classList.contains('page-pericias')
        ? 'pericias'
        : document.body.classList.contains('page-log')
            ? 'log'
            : (atual.get('tipo') || 'audiencias');

    if ((tipo === 'audiencias' || tipo === 'reclamante')
        && !atual.get('data_inicio')
        && !atual.get('data_fim')) {
        alert('Informe a data inicial e/ou final antes de imprimir.\n\nIsso evita travamento do navegador.');
        return;
    }

    const url = new URL('imprimir.php', window.location.href);
    url.searchParams.set('tipo', tipo);

    ['data_inicio', 'data_fim', 'usuario_id', 'acao', 'busca'].forEach((chave) => {
        const valor = atual.get(chave);
        if (valor) {
            url.searchParams.set(chave, valor);
        }
    });

    window.open(url.toString(), '_blank', 'noopener,noreferrer');
}
