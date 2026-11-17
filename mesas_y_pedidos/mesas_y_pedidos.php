<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// HEADERS PARA JSON
header('Content-Type: application/json; charset=utf-8');

// MANEJADOR DE ERRORES PARA JSON
function sendJsonResponse($success, $data = [], $error = '') {
    $response = [
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response);
    exit;
}

// OBTENER ACCIÓN
$action = isset($_GET['action']) ? $_GET['action'] : '';
if (empty($action)) {
    sendJsonResponse(false, [], 'No se especificó una acción');
}
require_once __DIR__ . '/../config/Auth.php';
Auth::requireRole(['mesero', 'administrador']);

$conn = null;
try {
    $conexion_path = dirname(__FILE__) . '/../config/conexion.php';
    
    if (!file_exists($conexion_path)) {
        sendJsonResponse(false, [], 'Archivo de conexión no encontrado');
    }
    include_once $conexion_path;
    
    if (!isset($conn) || !($conn instanceof mysqli)) {
        sendJsonResponse(false, [], 'Error de configuración de base de datos');
    }
    
    if ($conn->connect_error) {
        sendJsonResponse(false, [], 'Error de conexión a la base de datos');
    }
    
} catch (Exception $e) {
    sendJsonResponse(false, [], 'Error al conectar: ' . $e->getMessage());
}

try {
    // ===== CARGAR MESAS =====
    if ($action === 'cargar_mesas') {
        $sql = "SELECT id, numero_mesa, piso, ubicacion_especial, estado FROM mesas ORDER BY piso, numero_mesa";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            sendJsonResponse(false, [], 'Error en consulta SQL: ' . $conn->error);
        }
        
        $mesas = [];
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            $row['numero_mesa'] = (int)$row['numero_mesa'];
            $row['piso'] = (int)$row['piso'];
            $mesas[] = $row;
        }
        
        sendJsonResponse(true, $mesas);
    }
    
    // ===== LISTAR PRODUCTOS =====
    elseif ($action === 'listar') {
        $categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'platillo';
        $categorias_validas = ['platillo', 'bebida', 'postre'];
        if (!in_array($categoria, $categorias_validas)) {
            $categoria = 'platillo';
        }
        
        $sql = "SELECT id, nombre, descripcion, precio, imagen, categoria 
                FROM menu 
                WHERE categoria = ? 
                ORDER BY nombre";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            sendJsonResponse(false, [], 'Error al preparar consulta: ' . $conn->error);
        }
        
        $stmt->bind_param('s', $categoria);
        
        if (!$stmt->execute()) {
            sendJsonResponse(false, [], 'Error al ejecutar consulta: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $productos = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['precio'] = floatval($row['precio']);
            $productos[] = $row;
        }
        
        $stmt->close();
        sendJsonResponse(true, $productos);
    }
    
    // ===== GUARDAR PEDIDO =====
    elseif ($action === 'guardar') {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            sendJsonResponse(false, [], 'No se recibieron datos');
        }
        
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendJsonResponse(false, [], 'JSON inválido: ' . json_last_error_msg());
        }
        if (empty($data['nombreCliente'])) {
            sendJsonResponse(false, [], 'Nombre del cliente requerido');
        }
        
        if (empty($data['pedido']) || !is_array($data['pedido'])) {
            sendJsonResponse(false, [], 'Pedido vacío o inválido');
        }
        
        if (empty($data['mesas']) || !is_array($data['mesas'])) {
            sendJsonResponse(false, [], 'No se seleccionaron mesas');
        }
        
        $nombreCliente = $conn->real_escape_string(trim($data['nombreCliente']));
        $pedido = $data['pedido'];
        $mesas = $data['mesas'];
        $numerosMesas = array_map(function($m) {
            return intval($m['numero']);
        }, $mesas);
        $numerosMesasStr = implode(', ', $numerosMesas);
        $piso = 'Piso 1';
        if (count($mesas) > 1) {
            $piso = 'Múltiple';
        } else {
            $mesa_id = $mesas[0]['id'];
            $query = $conn->query("SELECT piso FROM mesas WHERE id = $mesa_id");
            if ($query && $row = $query->fetch_assoc()) {
                $piso = 'Piso ' . $row['piso'];
            }
        }
        $conn->begin_transaction();
        
        try {
            $pedidosInsertados = 0;
            
            foreach ($pedido as $item) {
                $producto_id = intval($item['id']);
                $nombre = $conn->real_escape_string($item['nombre']);
                $cantidad = intval($item['cantidad']);
                $precio = floatval($item['precio']);
                $sql = "INSERT INTO mesas_y_pedidos 
                        (cliente, producto_id, nombre, cantidad, precio, estado, mesas_seleccionadas, piso_mesa) 
                        VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?)";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Error al preparar inserción: ' . $conn->error);
                }
                
                $stmt->bind_param('sisidss', $nombreCliente, $producto_id, $nombre, $cantidad, $precio, $numerosMesasStr, $piso);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al ejecutar inserción: ' . $stmt->error);
                }
                
                $pedido_id = $stmt->insert_id;
                $pedidosInsertados++;
                
                // DESCONTAR INSUMOS 
                try {
                    $sql_insumos = "SELECT insumo_id, cantidad_usada 
                                    FROM menu_insumos 
                                    WHERE menu_id = ?";
                    $stmt_insumos = $conn->prepare($sql_insumos);
                    
                    if ($stmt_insumos) {
                        $stmt_insumos->bind_param("i", $producto_id);
                        $stmt_insumos->execute();
                        $result_insumos = $stmt_insumos->get_result();
                        
                        while ($insumo = $result_insumos->fetch_assoc()) {
                            $descontar = $insumo['cantidad_usada'] * $cantidad;
                            
                            $update_sql = "UPDATE inventario 
                                          SET cantidad = GREATEST(cantidad - ?, 0) 
                                          WHERE id = ?";
                            $update_stmt = $conn->prepare($update_sql);
                            
                            if ($update_stmt) {
                                $update_stmt->bind_param("di", $descontar, $insumo['insumo_id']);
                                $update_stmt->execute();
                                $update_stmt->close();
                            }
                        }
                        $stmt_insumos->close();
                    }
                } catch (Exception $e) {
                    error_log("Error al descontar insumos: " . $e->getMessage());
                }
                
                $stmt->close();
            }
            $mesasIds = array_map(function($m) {
                return intval($m['id']);
            }, $mesas);
            
            $mesasIdsStr = implode(',', $mesasIds);
            $updateMesas = "UPDATE mesas SET estado = 'ocupada' WHERE id IN ($mesasIdsStr)";
            
            if (!$conn->query($updateMesas)) {
                error_log("Error al actualizar mesas (no crítico): " . $conn->error);
            }
            
            // Confirmar transacción
            $conn->commit();
            
            sendJsonResponse(true, [
                'cliente' => $nombreCliente,
                'total_productos' => $pedidosInsertados,
                'mesas' => $numerosMesasStr,
                'piso' => $piso,
                'pedido_id' => $pedido_id
            ], "✅ Pedido guardado exitosamente para $nombreCliente");
            
        } catch (Exception $e) {
            $conn->rollback();
            sendJsonResponse(false, [], 'Error al guardar pedido: ' . $e->getMessage());
        }
    }
    
    // ===== ACCIÓN NO RECONOCIDA =====
    else {
        sendJsonResponse(false, [], 'Acción no reconocida: ' . $action);
    }
    
} catch (Exception $e) {
    sendJsonResponse(false, [], 'Error general: ' . $e->getMessage());
}
if ($conn instanceof mysqli) {
    $conn->close();
}
?>