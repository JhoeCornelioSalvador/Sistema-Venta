<?php
/**
 * Controlador de Cierre de Sesión
 * Sistema de Ventas "Gusto Marino"
 */

require_once __DIR__ . '/../config/Auth.php';

Auth::logout();

// Si la petición vino vía AJAX/JSON responder con JSON
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if (str_contains($accept, 'application/json') || $isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'message' => 'Sesión cerrada']);
    exit;
}

header("Location: login.html");
exit;
