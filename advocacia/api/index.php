<?php
/**
 * Ponto de entrada da API AJAX.
 */

require_once __DIR__ . '/../controllers/ApiController.php';

$controller = new ApiController();
$controller->handle();
