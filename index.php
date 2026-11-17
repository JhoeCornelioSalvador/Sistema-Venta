<?php
/**
 * Punto de Entrada Principal (Root Router)
 * Sistema de Ventas "Gusto Marino"
 */

session_start();

if (isset($_SESSION['usuario']) && isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'administrador') {
        header('Location: Dashboard/dashboard_admin.html');
        exit;
    } elseif ($_SESSION['rol'] === 'mesero') {
        header('Location: Dashboard/dashboard_mesero.html');
        exit;
    }
}

// Por defecto dirigir a la pantalla de autenticación
header('Location: login/login.html');
exit;
