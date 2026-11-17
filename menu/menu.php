<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/Auth.php';

Auth::requireLogin();
/** @var mysqli $conn */
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection not found']);
    exit;
}
header("Content-Type: application/json");

$action = $_GET["action"] ?? "";

if ($action === "listar") {
    $result = $conn->query("SELECT * FROM menu ORDER BY id DESC");
    $platos = [];
    while ($row = $result->fetch_assoc()) {
        $platos[] = $row;
    }
    echo json_encode($platos);
}

if ($action === "guardar") {
    Auth::requireRole('administrador');
    $id = $_POST["id"] ?? "";
    $nombre = $_POST["nombre"];
    $categoria = $_POST["categoria"];
    $precio = $_POST["precio"];
    $descripcion = $_POST["descripcion"];
    $imagen = "";

    if (!empty($_FILES["imagen"]["name"])) {
        $nombreArchivo = time() . "_" . basename($_FILES["imagen"]["name"]);
        $rutaDestino = "uploads/" . $nombreArchivo;
        move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaDestino);
        $imagen = $nombreArchivo;
    }

    if ($id == "") {
        $stmt = $conn->prepare("INSERT INTO menu (nombre, categoria, precio, descripcion, imagen) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $nombre, $categoria, $precio, $descripcion, $imagen);
        $stmt->execute();
        $menuId = $conn->insert_id;
    } else {
        if ($imagen != "") {
            $stmt = $conn->prepare("UPDATE menu SET nombre=?, categoria=?, precio=?, descripcion=?, imagen=? WHERE id=?");
            $stmt->bind_param("ssdssi", $nombre, $categoria, $precio, $descripcion, $imagen, $id);
        } else {
            $stmt = $conn->prepare("UPDATE menu SET nombre=?, categoria=?, precio=?, descripcion=? WHERE id=?");
            $stmt->bind_param("ssdsi", $nombre, $categoria, $precio, $descripcion, $id);
        }
        $stmt->execute();
        $menuId = $id;
    }

    // Guardar insumos asociados al platillo
    if (isset($_POST['insumos']) && isset($_POST['cantidades'])) {
        $conn->query("DELETE FROM menu_insumos WHERE menu_id = $menuId");
        $insumos = $_POST['insumos'];
        $cantidades = $_POST['cantidades'];
        $stmtInsumo = $conn->prepare("INSERT INTO menu_insumos (menu_id, insumo_id, cantidad_usada) VALUES (?, ?, ?)");
        for ($i = 0; $i < count($insumos); $i++) {
            if ($insumos[$i] != "" && $cantidades[$i] > 0) {
                $stmtInsumo->bind_param("iid", $menuId, $insumos[$i], $cantidades[$i]);
                $stmtInsumo->execute();
            }
        }
    }

    echo json_encode(["success" => true]);
}

if ($action === "eliminar") {
    Auth::requireRole('administrador');
    $id = $_GET["id"];
    $stmt = $conn->prepare("DELETE FROM menu WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["success" => true]);
}

$conn->close();
?>