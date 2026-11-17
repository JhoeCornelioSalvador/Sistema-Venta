<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/Auth.php';

Auth::requireRole(['mesero', 'administrador']);
header("Content-Type: application/json");

$action = $_GET["action"] ?? "";

/* LISTAR SOLO PEDIDOS PENDIENTES */
if ($action === "listar") {
    $sql = "SELECT 
                cliente,
                MIN(fecha_pedido) AS fecha_pedido,
                estado
            FROM mesas_y_pedidos
            WHERE estado = 'pendiente'
            GROUP BY cliente
            ORDER BY fecha_pedido DESC";

    $result = $conn->query($sql);
    $pedidos = [];

    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }

    echo json_encode($pedidos);
    exit;
}

/* MOSTRAR DETALLE DEL PEDIDO*/
if ($action === "detalle") {
    $cliente = $conn->real_escape_string($_GET["cliente"]);

    $sql = "SELECT nombre, precio 
            FROM mesas_y_pedidos
            WHERE cliente = '$cliente' AND estado = 'pendiente'";

    $result = $conn->query($sql);
    $detalle = [];

    while ($row = $result->fetch_assoc()) {
        $detalle[] = $row;
    }

    echo json_encode($detalle);
    exit;
}

/* MARCAR PEDIDO COMO FACTURADO */
if ($action === "facturar") {
    $data = json_decode(file_get_contents("php://input"), true);
    $cliente = $conn->real_escape_string($data["cliente"]);

    // Obtener las mesas 
    $sql_mesas = "SELECT mesas_seleccionadas FROM mesas_y_pedidos WHERE cliente = '$cliente' AND estado = 'pendiente' LIMIT 1";
    $result_mesas = $conn->query($sql_mesas);
    
    $mesas_a_liberar = [];
    if ($result_mesas && $row = $result_mesas->fetch_assoc()) {
        $mesas_seleccionadas = $row['mesas_seleccionadas'];
        $numeros_mesas = array_map('intval', array_map('trim', explode(',', $mesas_seleccionadas)));
        
        if (!empty($numeros_mesas)) {
            $placeholders = implode(',', $numeros_mesas);
            $sql_ids = "SELECT id FROM mesas WHERE numero_mesa IN ($placeholders)";
            $result_ids = $conn->query($sql_ids);
            
            while ($row_id = $result_ids->fetch_assoc()) {
                $mesas_a_liberar[] = $row_id['id'];
            }
        }
    }

    //  Marcar pedidos como facturados
    $sql = "UPDATE mesas_y_pedidos 
            SET estado = 'facturado' 
            WHERE cliente = '$cliente' AND estado = 'pendiente'";
    $conn->query($sql);

    // Liberar las mesas
    if (!empty($mesas_a_liberar)) {
        $ids = implode(',', $mesas_a_liberar);
        $conn->query("UPDATE mesas SET estado = 'libre' WHERE id IN ($ids)");
        error_log("✅ Mesas liberadas para cliente $cliente: " . $ids);
    } else {
        error_log("❌ No se encontraron mesas para liberar para cliente: $cliente");
    }

    echo json_encode(["success" => true, "mesas_liberadas" => $mesas_a_liberar]);
    exit;
}

$conn->close();
?>