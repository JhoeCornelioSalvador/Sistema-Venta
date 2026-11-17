<?php
/**
 * Adaptador de compatibilidad de conexión
 * Sistema de Ventas "Gusto Marino"
 * 
 * Provee la variable global $conn esperada por los módulos del sistema,
 * utilizando la clase moderna y segura Database (Singleton).
 */

require_once __DIR__ . '/Database.php';

try {
    $conn = Database::getConnection();
} catch (Exception $e) {
    // Si la conexión falla y no se capturó como JSON en Database
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
