<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/Auth.php';

Auth::requireRole('administrador');

// respuesta
$response = [
    'success' => false,
    'ventas_hoy' => '0.00',
    'platillos_activos' => '0',
    'inventario_bajo' => '0',
    'utilidad' => '0.00',
    'mesas_ocupadas' => '0',
    'pedidos_pendientes' => '0',
    'reservas_hoy' => '0',
    'total_insumos' => '0',
    'actividad_reciente' => [],
    'error' => null
];

if ($conn->connect_error) {
    $response['error'] = "Error de conexión a la base de datos: " . $conn->connect_error;
    echo json_encode($response);
    exit;
}

try {
    // VENTAS DE HOY
    $query = "SELECT COALESCE(SUM(cantidad * precio), 0) as total_hoy 
              FROM mesas_y_pedidos 
              WHERE DATE(fecha_pedido) = CURDATE() AND estado = 'facturado'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $response['ventas_hoy'] = number_format($row['total_hoy'], 2);
    }

    // PLATILLOS ACTIVOS
    $query = "SELECT COUNT(*) as total FROM menu WHERE categoria = 'platillo'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $response['platillos_activos'] = $row['total'];
    }

    // INVENTARIO BAJO
    $query = "SELECT COUNT(*) as bajos FROM inventario WHERE cantidad < 10";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $response['inventario_bajo'] = $row['bajos'];
    }

    // UTILIDAD
    $query = "SELECT 
                CASE 
                    WHEN SUM(cantidad * precio) > 0 THEN 
                        ROUND((SUM(cantidad * precio) * 0.35), 2)
                    ELSE 0 
                END as utilidad_estimada
              FROM mesas_y_pedidos 
              WHERE DATE(fecha_pedido) = CURDATE() AND estado = 'facturado'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $response['utilidad'] = number_format($row['utilidad_estimada'], 2);
    }

    // MESAS OCUPADAS
    $query = "SELECT COUNT(*) as ocupadas FROM mesas WHERE estado = 'ocupada'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $response['mesas_ocupadas'] = $row['ocupadas'];
    }

    // PEDIDOS PENDIENTES
    $query = "SELECT COUNT(*) as pendientes FROM mesas_y_pedidos WHERE estado = 'pendiente'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $response['pedidos_pendientes'] = $row['pendientes'];
    }

    // RESERVAS PARA HOY
    $query = "SELECT COUNT(*) as reservas_hoy FROM reservas 
              WHERE fecha_reserva = CURDATE() AND estado IN ('pendiente', 'confirmada')";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $response['reservas_hoy'] = $row['reservas_hoy'];
    }

    // TOTAL DE INSUMOS
    $query = "SELECT COUNT(*) as total_insumos FROM inventario";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $response['total_insumos'] = $row['total_insumos'];
    }

    // ACTIVIDAD RECIENTE
    $query = "SELECT 
                'venta' as tipo,
                CONCAT('Venta en Mesa ', mesas_seleccionadas) as descripcion,
                CONCAT('$', FORMAT(cantidad * precio, 2)) as detalle,
                fecha_pedido as fecha
              FROM mesas_y_pedidos 
              WHERE estado = 'facturado'
              UNION ALL
              SELECT 
                'reserva' as tipo,
                CONCAT('Reserva para ', nombres, ' ', apellidos) as descripcion,
                CONCAT(cantidad_personas, ' personas') as detalle,
                fecha_creacion as fecha
              FROM reservas 
              WHERE estado IN ('confirmada', 'cumplida')
              UNION ALL
              SELECT 
                'menu' as tipo,
                CONCAT('Nuevo platillo: ', nombre) as descripcion,
                CONCAT('$', FORMAT(precio, 2)) as detalle,
                fecha_creacion as fecha
              FROM menu
              ORDER BY fecha DESC 
              LIMIT 5";
    
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $response['actividad_reciente'][] = [
                'tipo' => $row['tipo'],
                'descripcion' => $row['descripcion'],
                'detalle' => $row['detalle'],
                'fecha' => $row['fecha']
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