<?php
/**
 * Serve foto ou documento armazenado no banco de dados.
 */
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/ProcessoModel.php';

Auth::requerLogin();

if (!Auth::podeVer('cadastro')
    && !Auth::podeVer('consulta_processo')
    && !Auth::podeVer('consulta_reclamante')
    && !Auth::podeVer('consulta_reclamada')) {
    http_response_code(403);
    exit('Acesso negado');
}

$id = (int) ($_GET['id'] ?? 0);
$tipo = $_GET['tipo'] ?? '';

if ($id <= 0 || !in_array($tipo, ['foto', 'documento'], true)) {
    http_response_code(400);
    exit('Requisição inválida');
}

$model = new ProcessoModel();
$arquivo = $tipo === 'foto' ? $model->obterFoto($id) : $model->obterDocumento($id);

if (!$arquivo) {
    http_response_code(404);
    exit('Arquivo não encontrado');
}

header('Content-Type: ' . $arquivo['tipo']);
header('Content-Length: ' . strlen($arquivo['conteudo']));
if ($tipo === 'documento' && !empty($arquivo['nome'])) {
    header('Content-Disposition: inline; filename="' . rawurlencode($arquivo['nome']) . '"');
}
echo $arquivo['conteudo'];
