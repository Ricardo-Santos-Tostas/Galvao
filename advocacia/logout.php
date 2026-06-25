<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/log.php';

Auth::iniciarSessao();
$usuario = Auth::usuario();

if ($usuario) {
    Log::registrar('logout', 'Encerrou a sessão', 'sistema', null, null, $usuario);
}

Auth::logout();
header('Location: login.php');
exit;
