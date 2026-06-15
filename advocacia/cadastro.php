<?php
/**
 * Tela de cadastro de clientes — equivalente ao formulário FRM_CADASTRO do Access.
 */

require_once __DIR__ . '/models/ProcessoModel.php';

$model = new ProcessoModel();
$proximoId = $model->proximoId();

ob_start();
include __DIR__ . '/views/partials/form_fields.php';
$conteudo = ob_get_clean();

$titulo = 'Cadastro de Clientes';
$titulo_form = 'Cadastro de Clientes';
$modo = 'cadastro';
$somente_leitura = false;
$pagina_atual = 'cadastro';

$script_extra = "document.addEventListener('DOMContentLoaded', () => {
    App.initFormulario('cadastro');
    document.getElementById('CADASTRO').value = " . json_encode((string) $proximoId) . ";
});";

include __DIR__ . '/views/layout.php';
