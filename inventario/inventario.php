<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/Auth.php';

Auth::requireRole('administrador');
header("Content-Type: application/json");

$action = $_GET["action"] ?? "";

// Listar los insumos
if ($action === "listar") {
    $categoria = $_GET['categoria'] ?? ''; 
    $sql = "SELECT * FROM inventario";

    if ($categoria) {
        $sql .= " WHERE categoria = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $categoria);  
    } else {
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $insumos = [];
    while ($row = $result->fetch_assoc()) {
        $insumos[] = $row;
    }
    echo json_encode($insumos);
}

// Guardar o actualizar un insumo
if ($action === "guardar") {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['nombre']) || !isset($data['cantidad']) || !isset($data['unidad']) || !isset($data['categoria'])) {
        echo json_encode(["success" => false, "error" => "Faltan datos"]);
        exit;
    }

    $nombre = $data['nombre'];
    $cantidad = $data['cantidad'];
    $unidad = $data['unidad'];
    $categoria = $data['categoria'];
    $id = $data['id'] ?? "";

    if ($id == "") {
        // Insertar nuevo insumo
        $stmt = $conn->prepare("INSERT INTO inventario (nombre, cantidad, unidad, categoria) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $nombre, $cantidad, $unidad, $categoria);
    } else {
        // Actualizar insumo existente
        $stmt = $conn->prepare("UPDATE inventario SET nombre=?, cantidad=?, unidad=?, categoria=? WHERE id=?");
        $stmt->bind_param("sissi", $nombre, $cantidad, $unidad, $categoria, $id);
    }

    // Ejecutar la consulta y devolver la respuesta
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Error al guardar"]);
    }
}

// Eliminar un insumo
if ($action === "eliminar") {
    $id = $_GET["id"];
    $stmt = $conn->prepare("DELETE FROM inventario WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["success" => true]);
}

$conn->close();
?>
