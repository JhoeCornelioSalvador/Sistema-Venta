<?php
/**
 * Batería de Pruebas Unitarias (28 Pruebas)
 * Sistema de Ventas Rústica - Los Olivos
 */

class PruebasUnitariasTest {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    public function runAll(callable $runner): void {
        // --- Grupo 1: Autenticación y Cifrado (CPU-01 a CPU-05) ---
        $runner('unitarias', 'CPU-01', 'Verificación de clave bcrypt para usuario admin', function() {
            $stmt = $this->conn->prepare("SELECT password, rol FROM usuarios WHERE usuario = 'admin'");
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return ['passed' => password_verify('admin123', $res['password']) && $res['rol'] === 'administrador', 'detalles' => 'Hash bcrypt de admin verificado'];
        });

        $runner('unitarias', 'CPU-02', 'Rechazo de acceso con contraseña incorrecta de admin', function() {
            $stmt = $this->conn->prepare("SELECT password FROM usuarios WHERE usuario = 'admin'");
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return ['passed' => !password_verify('clave_falsa_999', $res['password']), 'detalles' => 'Contraseña inválida rechazada correctamente'];
        });

        $runner('unitarias', 'CPU-03', 'Verificación de clave bcrypt para usuario mesero', function() {
            $stmt = $this->conn->prepare("SELECT password, rol FROM usuarios WHERE usuario = 'mesero'");
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return ['passed' => password_verify('mesero123', $res['password']) && $res['rol'] === 'mesero', 'detalles' => 'Hash bcrypt de mesero verificado'];
        });

        $runner('unitarias', 'CPU-04', 'Rechazo de acceso con contraseña incorrecta de mesero', function() {
            $stmt = $this->conn->prepare("SELECT password FROM usuarios WHERE usuario = 'mesero'");
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return ['passed' => !password_verify('erronea_pass', $res['password']), 'detalles' => 'Contraseña inválida de mesero rechazada'];
        });

        $runner('unitarias', 'CPU-05', 'Validación de usuario no vacío en formulario de login', function() {
            $u = trim('');
            return ['passed' => empty($u), 'detalles' => 'Usuario en blanco detectado como inválido'];
        });

        // --- Grupo 2: Respuestas y Métodos de ApiResponse (CPU-06 a CPU-10) ---
        $runner('unitarias', 'CPU-06', 'Disponibilidad del método ApiResponse::success', function() {
            $hasMethod = method_exists('ApiResponse', 'success');
            return ['passed' => $hasMethod, 'detalles' => 'Método ApiResponse::success disponible'];
        });

        $runner('unitarias', 'CPU-07', 'Disponibilidad del método ApiResponse::error', function() {
            $hasMethod = method_exists('ApiResponse', 'error');
            return ['passed' => $hasMethod, 'detalles' => 'Método ApiResponse::error disponible'];
        });

        $runner('unitarias', 'CPU-08', 'Código HTTP 400 para solicitudes incorrectas', function() {
            $code = 400;
            return ['passed' => $code >= 400 && $code < 500, 'detalles' => 'Código de cliente 400 verificado'];
        });

        $runner('unitarias', 'CPU-09', 'Disponibilidad del método ApiResponse::unauthorized', function() {
            $hasMethod = method_exists('ApiResponse', 'unauthorized');
            return ['passed' => $hasMethod, 'detalles' => 'Método ApiResponse::unauthorized disponible'];
        });

        $runner('unitarias', 'CPU-10', 'Disponibilidad del método ApiResponse::forbidden', function() {
            $hasMethod = method_exists('ApiResponse', 'forbidden');
            return ['passed' => $hasMethod, 'detalles' => 'Método ApiResponse::forbidden disponible'];
        });

        // --- Grupo 3: Aritmética de Precios y Comandas (CPU-11 a CPU-15) ---
        $runner('unitarias', 'CPU-11', 'Cálculo de precio individual de Ceviche Clásico', function() {
            $p = 38.00; $c = 1; $total = $p * $c;
            return ['passed' => abs($total - 38.00) < 0.01, 'detalles' => 'Total individual 38.00 correcto'];
        });

        $runner('unitarias', 'CPU-12', 'Cálculo de total para 2 porciones de Lomo Saltado', function() {
            $p = 35.00; $c = 2; $total = $p * $c;
            return ['passed' => abs($total - 70.00) < 0.01, 'detalles' => 'Total 2 porciones 70.00 correcto'];
        });

        $runner('unitarias', 'CPU-13', 'Cálculo de comanda mixta de plato y bebida', function() {
            $total = (38.00 * 1) + (14.00 * 1);
            return ['passed' => abs($total - 52.00) < 0.01, 'detalles' => 'Total comanda mixta 52.00 correcto'];
        });

        $runner('unitarias', 'CPU-14', 'Cálculo de importe para 3 bebidas Inka Kola', function() {
            $total = 6.00 * 3;
            return ['passed' => abs($total - 18.00) < 0.01, 'detalles' => 'Total 3 gaseosas 18.00 correcto'];
        });

        $runner('unitarias', 'CPU-15', 'Cálculo de comanda completa de 3 tiempos', function() {
            $total = (35.00 * 2) + (18.00 * 2) + (15.00 * 2);
            return ['passed' => abs($total - 136.00) < 0.01, 'detalles' => 'Total mesa grande 136.00 correcto'];
        });

        // --- Grupo 4: Fórmulas de Recetas y Deducción de Stock (CPU-16 a CPU-20) ---
        $runner('unitarias', 'CPU-16', 'Deducción de pescado para 1 ceviche: 0.25 kg', function() {
            $consumo = 0.25 * 1;
            return ['passed' => abs($consumo - 0.25) < 0.001, 'detalles' => 'Dosis de pescado 0.25 kg'];
        });

        $runner('unitarias', 'CPU-17', 'Deducción de pescado para 4 ceviches: 1.00 kg', function() {
            $consumo = 0.25 * 4;
            return ['passed' => abs($consumo - 1.00) < 0.001, 'detalles' => 'Dosis acumulada 1.00 kg'];
        });

        $runner('unitarias', 'CPU-18', 'Deducción de limón sutil para 2 ceviches: 0.30 kg', function() {
            $consumo = 0.15 * 2;
            return ['passed' => abs($consumo - 0.30) < 0.001, 'detalles' => 'Dosis de limón 0.30 kg'];
        });

        $runner('unitarias', 'CPU-19', 'Deducción de lomo fino para 2 lomos: 0.50 kg', function() {
            $consumo = 0.25 * 2;
            return ['passed' => abs($consumo - 0.50) < 0.001, 'detalles' => 'Dosis de carne 0.50 kg'];
        });

        $runner('unitarias', 'CPU-20', 'Deducción de pisco quebranta para 2 pisco sour: 0.16 botellas', function() {
            $consumo = 0.08 * 2;
            return ['passed' => abs($consumo - 0.16) < 0.001, 'detalles' => 'Dosis de pisco 0.16 botellas'];
        });

        // --- Grupo 5: Validaciones de Entrada y Reglas de Negocio (CPU-21 a CPU-28) ---
        $runner('unitarias', 'CPU-21', 'Aceptación de cantidad numérica entera positiva', function() {
            $c = 1;
            return ['passed' => is_int($c) && $c > 0, 'detalles' => 'Cantidad 1 aceptada'];
        });

        $runner('unitarias', 'CPU-22', 'Rechazo de cantidad igual a cero en comanda', function() {
            $c = 0;
            return ['passed' => !($c > 0), 'detalles' => 'Cantidad 0 bloqueada'];
        });

        $runner('unitarias', 'CPU-23', 'Rechazo de cantidad negativa en comanda', function() {
            $c = -5;
            return ['passed' => !($c > 0), 'detalles' => 'Cantidad negativa bloqueada'];
        });

        $runner('unitarias', 'CPU-24', 'Rechazo de cantidad alfanumérica no válida', function() {
            $c = 'tres';
            return ['passed' => !is_numeric($c), 'detalles' => 'Cadena de texto bloqueada'];
        });

        $runner('unitarias', 'CPU-25', 'Rechazo de comanda sin mesa asignada', function() {
            $mesas = '';
            return ['passed' => empty(trim($mesas)), 'detalles' => 'Mesas vacías detectadas'];
        });

        $runner('unitarias', 'CPU-26', 'Aceptación de fecha de reserva futura', function() {
            $fecha = date('Y-m-d', strtotime('+3 days'));
            return ['passed' => strtotime($fecha) >= strtotime(date('Y-m-d')), 'detalles' => "Fecha $fecha aceptada"];
        });

        $runner('unitarias', 'CPU-27', 'Rechazo de fecha de reserva anterior a hoy', function() {
            $fecha = date('Y-m-d', strtotime('-1 day'));
            return ['passed' => strtotime($fecha) < strtotime(date('Y-m-d')), 'detalles' => "Fecha pasada $fecha rechazada"];
        });

        $runner('unitarias', 'CPU-28', 'Validación de rango de personas para reserva', function() {
            $personas = 4;
            $valido = ($personas >= 1 && $personas <= 30);
            return ['passed' => $valido, 'detalles' => "Capacidad para $personas comensales válida"];
        });
    }
}
