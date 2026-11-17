<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/Auth.php';

Auth::requireRole('administrador');
header("Content-Type: application/json");

$action = $_GET["action"] ?? "";

if ($action === "cargar_reportes") {
    $fecha_desde = $_GET["fecha_desde"] ?? date('Y-m-d');
    $fecha_hasta = $_GET["fecha_hasta"] ?? date('Y-m-d');
    $categoria = $_GET["categoria"] ?? "todas";
    
    // Todos los pedidos realizados
    $sql_pedidos = "SELECT 
                        myp.cliente,
                        myp.nombre,
                        myp.cantidad,
                        myp.precio,
                        myp.fecha_pedido,
                        m.categoria
                    FROM mesas_y_pedidos myp
                    INNER JOIN menu m ON myp.producto_id = m.id
                    WHERE myp.estado = 'facturado'
                    AND DATE(myp.fecha_pedido) BETWEEN ? AND ?";
    
    // Aplicar filtro de categoría
    if ($categoria !== "todas") {
        $sql_pedidos .= " AND m.categoria = ?";
    }
    
    $sql_pedidos .= " ORDER BY myp.fecha_pedido DESC";
    
    $stmt_pedidos = $conn->prepare($sql_pedidos);
    
    if ($categoria !== "todas") {
        $stmt_pedidos->bind_param("sss", $fecha_desde, $fecha_hasta, $categoria);
    } else {
        $stmt_pedidos->bind_param("ss", $fecha_desde, $fecha_hasta);
    }
    
    $stmt_pedidos->execute();
    $result_pedidos = $stmt_pedidos->get_result();
    
    $pedidos = [];
    while ($row = $result_pedidos->fetch_assoc()) {
        $pedidos[] = $row;
    }
    
    // Platillos más vendidos
    $sql_populares = "SELECT 
                        m.nombre,
                        COUNT(myp.id) as veces_vendido,
                        SUM(myp.cantidad * myp.precio) as ingreso_total,
                        m.categoria
                    FROM mesas_y_pedidos myp
                    INNER JOIN menu m ON myp.producto_id = m.id
                    WHERE myp.estado = 'facturado'
                    AND DATE(myp.fecha_pedido) BETWEEN ? AND ?";
    
    // Aplicar filtro de categoría
    if ($categoria !== "todas") {
        $sql_populares .= " AND m.categoria = ?";
        $sql_populares .= " GROUP BY m.id, m.nombre, m.categoria 
                          ORDER BY veces_vendido DESC, ingreso_total DESC 
                          LIMIT 20";
        
        $stmt_populares = $conn->prepare($sql_populares);
        $stmt_populares->bind_param("sss", $fecha_desde, $fecha_hasta, $categoria);
    } else {
        $sql_populares .= " GROUP BY m.id, m.nombre, m.categoria 
                          ORDER BY veces_vendido DESC, ingreso_total DESC 
                          LIMIT 20";
        
        $stmt_populares = $conn->prepare($sql_populares);
        $stmt_populares->bind_param("ss", $fecha_desde, $fecha_hasta);
    }
    
    $stmt_populares->execute();
    $result_populares = $stmt_populares->get_result();
    
    $populares = [];
    while ($row = $result_populares->fetch_assoc()) {
        $populares[] = $row;
    }
    
    echo json_encode([
        "pedidos" => $pedidos,
        "populares" => $populares
    ]);
    
    $stmt_pedidos->close();
    $stmt_populares->close();
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Acción no válida o no especificada"
    ]);
}

$conn->close();
?>