<?php
/**
 * Seeder de Usuarios con Contraseñas Cifradas
 * Sistema de Ventas "Gusto Marino"
 */

require_once __DIR__ . '/conexion.php';

$users = [
    [
        'usuario'  => 'admin',
        'password' => 'admin123',
        'rol'      => 'administrador'
    ],
    [
        'usuario'  => 'mesero',
        'password' => 'mesero123',
        'rol'      => 'mesero'
    ]
];

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    
    // Verificar si existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $u['usuario']);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $up = $conn->prepare("UPDATE usuarios SET password = ?, rol = ? WHERE id = ?");
        $up->bind_param("ssi", $hash, $u['rol'], $row['id']);
        $up->execute();
        $up->close();
        echo "Usuario '{$u['usuario']}' actualizado con hash bcrypt.\n";
    } else {
        $ins = $conn->prepare("INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, ?)");
        $ins->bind_param("sss", $u['usuario'], $hash, $u['rol']);
        $ins->execute();
        $ins->close();
        echo "Usuario '{$u['usuario']}' creado con hash bcrypt.\n";
    }
    $stmt->close();
}

echo "Seeding completado con éxito.\n";
