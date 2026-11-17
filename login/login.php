<?php
/**
 * Controlador de Inicio de Sesión
 * Sistema de Ventas "Gusto Marino"
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/Auth.php';
require_once __DIR__ . '/../config/ApiResponse.php';

Auth::initSession();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    ApiResponse::error("Método no permitido. Solo se aceptan peticiones POST.", 405);
}

// Obtener datos vía POST estándar o JSON body
if (isset($_POST["usuario"])) {
    $usuario = trim($_POST["usuario"] ?? "");
    $password = trim($_POST["password"] ?? "");
} else {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    $usuario = trim($input["usuario"] ?? "");
    $password = trim($input["password"] ?? "");
}

$result = Auth::login($conn, $usuario, $password);

if ($result['success']) {
    ApiResponse::raw($result, 200);
} else {
    ApiResponse::raw($result, 401);
}
