<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/Auth.php';

Auth::requireRole(['mesero', 'administrador']);
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Guardar nueva reserva
if ($action === "guardar") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $nombres = $conn->real_escape_string($data['nombres']);
    $apellidos = $conn->real_escape_string($data['apellidos']);
    $telefono = $conn->real_escape_string($data['telefono'] ?? '');
    $fecha_reserva = $conn->real_escape_string($data['fecha_reserva']);
    $hora_reserva = $conn->real_escape_string($data['hora_reserva']);
    $cantidad_personas = intval($data['cantidad_personas']);
    $cantidad_mesas = intval($data['cantidad_mesas']);
    $mesas_seleccionadas = $conn->real_escape_string($data['mesas_seleccionadas']);
    $observaciones = $conn->real_escape_string($data['observaciones'] ?? '');

    // Insertar reserva
    $stmt = $conn->prepare("INSERT INTO reservas (nombres, apellidos, telefono, fecha_reserva, hora_reserva, cantidad_personas, cantidad_mesas, mesas_seleccionadas, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiiss", $nombres, $apellidos, $telefono, $fecha_reserva, $hora_reserva, $cantidad_personas, $cantidad_mesas, $mesas_seleccionadas, $observaciones);
    
    if ($stmt->execute()) {
        $numeros_mesas = array_map('intval', explode(',', $mesas_seleccionadas));
        if (!empty($numeros_mesas)) {
            $placeholders = implode(',', $numeros_mesas);
            $conn->query("UPDATE mesas SET estado = 'reservada' WHERE numero_mesa IN ($placeholders)");
        }
        
        echo json_encode(["success" => true, "message" => "Reserva guardada correctamente"]);
    } else {
        echo json_encode(["success" => false, "error" => "Error al guardar la reserva: " . $conn->error]);
    }
    exit;
}

// Listar reservas
if ($action === "listar") {
    $filtro_estado = $_GET['estado'] ?? 'todas';
    $filtro_fecha = $_GET['fecha'] ?? '';
    $filtro_busqueda = $_GET['busqueda'] ?? '';
    
    $sql = "SELECT * FROM reservas WHERE 1=1";
    
    if ($filtro_estado !== 'todas') {
        $sql .= " AND estado = '" . $conn->real_escape_string($filtro_estado) . "'";
    }
    
    if (!empty($filtro_fecha)) {
        $sql .= " AND fecha_reserva = '" . $conn->real_escape_string($filtro_fecha) . "'";
    }
    
    // Búsqueda por nombre, apellido o teléfono
    if (!empty($filtro_busqueda)) {
        $busqueda = $conn->real_escape_string($filtro_busqueda);
        $sql .= " AND (nombres LIKE '%$busqueda%' OR 
                      apellidos LIKE '%$busqueda%' OR 
                      CONCAT(nombres, ' ', apellidos) LIKE '%$busqueda%' OR
                      telefono LIKE '%$busqueda%')";
    }
    
    $sql .= " ORDER BY fecha_reserva ASC, hora_reserva ASC";
    
    $result = $conn->query($sql);
    $reservas = [];
    
    while ($row = $result->fetch_assoc()) {
        $reservas[] = $row;
    }
    
    echo json_encode($reservas);
    exit;
}

// Cargar mesas disponibles para reservas
if ($action === "cargar_mesas") {
    $sql = "SELECT * FROM mesas ORDER BY piso, numero_mesa";
    $result = $conn->query($sql);
    $mesas = [];
    
    while ($row = $result->fetch_assoc()) {
        $mesas[] = $row;
    }
    
    echo json_encode($mesas);
    exit;
}

// Actualizar estado de reserva
if ($action === "actualizar_estado") {
    $data = json_decode(file_get_contents("php://input"), true);
    $reserva_id = intval($data['id']);
    $nuevo_estado = $conn->real_escape_string($data['estado']);
    $reserva_actual = $conn->query("SELECT mesas_seleccionadas FROM reservas WHERE id = $reserva_id")->fetch_assoc();
    
    if (!$reserva_actual) {
        echo json_encode(["success" => false, "error" => "Reserva no encontrada"]);
        exit;
    }
    $sql = "UPDATE reservas SET estado = '$nuevo_estado' WHERE id = $reserva_id";
    
    if ($conn->query($sql)) {
        $mesas_seleccionadas = $reserva_actual['mesas_seleccionadas'];
        $numeros_mesas = array_map('intval', explode(',', $mesas_seleccionadas));
        
        if (!empty($numeros_mesas)) {
            $placeholders = implode(',', $numeros_mesas);
            
            if ($nuevo_estado === 'cancelada' || $nuevo_estado === 'cumplida') {
                $conn->query("UPDATE mesas SET estado = 'libre' WHERE numero_mesa IN ($placeholders) AND estado = 'reservada'");
            } elseif ($nuevo_estado === 'confirmada') {
            }
        }
        
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Error al actualizar: " . $conn->error]);
    }
    exit;
}

// Obtener detalles de reserva
if ($action === "detalles") {
    $reserva_id = intval($_GET['id']);
    
    $sql = "SELECT * FROM reservas WHERE id = $reserva_id";
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(["error" => "Reserva no encontrada"]);
    }
    exit;
}

// Eliminar reserva (opcional)
if ($action === "eliminar") {
    $data = json_decode(file_get_contents("php://input"), true);
    $reserva_id = intval($data['id']);
    $reserva = $conn->query("SELECT mesas_seleccionadas FROM reservas WHERE id = $reserva_id")->fetch_assoc();
    
    if ($reserva) {
        $numeros_mesas = array_map('intval', explode(',', $reserva['mesas_seleccionadas']));
        if (!empty($numeros_mesas)) {
            $placeholders = implode(',', $numeros_mesas);
            $conn->query("UPDATE mesas SET estado = 'libre' WHERE numero_mesa IN ($placeholders) AND estado = 'reservada'");
        }
    }
    
    $sql = "DELETE FROM reservas WHERE id = $reserva_id";
    
    if ($conn->query($sql)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Error al eliminar"]);
    }
    exit;
}
if ($action === "verificar_disponibilidad") {
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $hora = $_GET['hora'] ?? date('H:i:s');
    $mesas_solicitadas = $_GET['mesas'] ?? '';
    
    $mesas_array = array_map('intval', explode(',', $mesas_solicitadas));
    $disponibles = [];
    
    if (!empty($mesas_array)) {
        $placeholders = implode(',', $mesas_array);
        $sql = "SELECT numero_mesa, estado FROM mesas WHERE numero_mesa IN ($placeholders)";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $disponibles[$row['numero_mesa']] = $row['estado'] === 'libre';
        }
    }
    
    echo json_encode(["disponibles" => $disponibles]);
    exit;
}

$conn->close();
function logReserva($mensaje) {
    error_log("[RESERVAS] " . $mensaje);
}
?>
