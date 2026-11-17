<?php
/**
 * Endpoint de Sesión y Autenticación del Cliente
 * Sistema de Ventas "Gusto Marino"
 */

require_once __DIR__ . '/../config/Auth.php';
require_once __DIR__ . '/../config/ApiResponse.php';

Auth::initSession();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'check');

if ($action === 'logout') {
    Auth::logout();
    ApiResponse::success(null, 'Sesión finalizada exitosamente');
}

if ($action === 'check') {
    $user = Auth::user();
    if ($user) {
        ApiResponse::success([
            'authenticated' => true,
            'user'          => $user
        ], 'Usuario autenticado', 200, [
            'authenticated' => true,
            'user'          => $user
        ]);
    } else {
        ApiResponse::raw([
            'success'       => false,
            'authenticated' => false,
            'user'          => null,
            'message'       => 'No hay sesión activa'
        ], 200);
    }
}

ApiResponse::error('Acción no reconocida', 400);
