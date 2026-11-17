<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/Auth.php';

Auth::requireRole(['mesero', 'administrador']);

$response = [
    'success' => false,
    'ventas_turno' => '0.00',
    'mesas_activas' => '0',
    'pedidos_pendientes' => '0',
    'propina_estimada' => '0.00',
    'mesas' => [],
    'pedidos_recientes' => [],
    'error' => null
];

if ($conn->connect_error) {
    $response['error'] = "Error de conexión a la base de datos: " . $conn->connect_error;
    echo json_encode($response);
    exit;
}

try {
    // VENTAS DEL TURNO
    $query = "SELECT COALESCE(SUM(cantidad * precio), 0) as total_turno 
              FROM mesas_y_pedidos 
              WHERE DATE(fecha_pedido) = CURDATE() 
              AND HOUR(fecha_pedido) >= 10 
              AND estado = 'facturado'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $response['ventas_turno'] = number_format($row['total_turno'], 2);
    }

    // MESAS ACTIVAS
    $query = "SELECT COUNT(*) as activas FROM mesas WHERE estado = 'ocupada'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $response['mesas_activas'] = $row['activas'];
    }

    // PEDIDOS PENDIENTES
    $query = "SELECT COUNT(*) as pendientes FROM mesas_y_pedidos WHERE estado = 'pendiente'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $response['pedidos_pendientes'] = $row['pendientes'];
    }

    // PROPINA
    $propina = floatval($response['ventas_turno']) * 0.10;
    $response['propina_estimada'] = number_format($propina, 2);

    // ESTADO DE MESAS
    $query = "SELECT numero_mesa, piso, ubicacion_especial, estado FROM mesas ORDER BY numero_mesa";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $response['mesas'][] = [
                'numero_mesa' => $row['numero_mesa'],
                'piso' => $row['piso'],
                'ubicacion_especial' => $row['ubicacion_especial'],
                'estado' => $row['estado']
            ];
        }
    }

    // PEDIDOS RECIENTES
    $query = "SELECT 
                myp.mesas_seleccionadas as mesa,
                m.nombre,
                myp.cantidad,
                myp.precio,
                myp.estado,
                myp.fecha_pedido,
                CONCAT(m.nombre, ' - Mesa ', myp.mesas_seleccionadas) as descripcion,
                ROUND(myp.cantidad * myp.precio, 2) as total
              FROM mesas_y_pedidos myp
              JOIN menu m ON myp.producto_id = m.id
              ORDER BY myp.fecha_pedido DESC 
              LIMIT 10";
    
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $response['pedidos_recientes'][] = [
                'mesa' => $row['mesa'],
                'descripcion' => $row['nombre'],
                'cantidad' => $row['cantidad'],
                'total' => $row['total'],
                'estado' => $row['estado'],
                'fecha_pedido' => $row['fecha_pedido']
            ];
        }
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['error'] = "Error: " . $e->getMessage();
}

$conn->close();
echo json_encode($response);
exit;
?>