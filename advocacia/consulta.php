<?php
/**
 * Telas de consulta — processo, reclamante ou reclamada.
 */

$tipos = [
    'processo' => [
        'titulo'      => 'Consulta por Processo',
        'titulo_form' => 'Consulta por Número de Processo',
        'label_busca' => 'Consulta por nome reclamante ou reclamada',
        'modo'        => 'consulta_processo',
    ],
    'reclamante' => [
        'titulo'      => 'Consulta por Reclamante',
        'titulo_form' => 'Consulta por Nome do Reclamante',
        'label_busca' => 'Consulta por nome reclamante ou reclamada',
        'modo'        => 'consulta_reclamante',
    ],
    'reclamada' => [
        'titulo'      => 'Consulta por Reclamada',
        'titulo_form' => 'Consulta por Nome da Reclamada',
        'label_busca' => 'Consulta por nome reclamante ou reclamada',
        'modo'        => 'consulta_reclamada',
    ],
];

$tipo = $_GET['tipo'] ?? 'processo';
if (!isset($tipos[$tipo])) {
    $tipo = 'processo';
}

$config = $tipos[$tipo];
$titulo = $config['titulo'];
$titulo_form = $config['titulo_form'];
$label_busca = $config['label_busca'];
$modo = $config['modo'];
$somente_leitura = true;
$pagina_atual = 'consulta';

ob_start();
include __DIR__ . '/views/partials/form_fields.php';
$conteudo = ob_get_clean();

$script_extra = "document.addEventListener('DOMContentLoaded', () => {
    App.initFormulario(" . json_encode($modo) . ");
});";

include __DIR__ . '/views/layout.php';
