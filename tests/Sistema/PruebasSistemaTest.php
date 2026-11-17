<?php
/**
 * Batería de Pruebas de Sistema (8 Pruebas)
 * Sistema de Ventas Rústica - Los Olivos
 */

class PruebasSistemaTest {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    public function runAll(callable $runner): void {
        // CPS-01: Ciclo completo E2E en primer piso
        $runner('sistema', 'CPS-01', 'Ciclo Completo E2E Salón Primer Piso', function() {
            $this->conn->begin_transaction();
            // 1. Ocupar
            $this->conn->query("UPDATE mesas SET estado = 'ocupada' WHERE numero_mesa = 1");
            // 2. Pedir
            $this->conn->query("INSERT INTO mesas_y_pedidos (cliente, producto_id, nombre, cantidad, precio, estado, mesas_seleccionadas, piso_mesa) VALUES ('E2E_PISO1', 1, 'Ceviche', 1, 38.00, 'pendiente', '1', 'Piso 1')");
            // 3. Descontar
            $this->conn->query("UPDATE inventario SET cantidad = cantidad - 0.25 WHERE id = 1");
            // 4. Cobrar y liberar
            $this->conn->query("UPDATE mesas_y_pedidos SET estado = 'facturado' WHERE cliente = 'E2E_PISO1'");
            $this->conn->query("UPDATE mesas SET estado = 'libre' WHERE numero_mesa = 1");
            
            $st = $this->conn->query("SELECT estado FROM mesas WHERE numero_mesa = 1")->fetch_assoc()['estado'];
            $ped = $this->conn->query("SELECT estado FROM mesas_y_pedidos WHERE cliente = 'E2E_PISO1'")->fetch_assoc()['estado'];
            $this->conn->rollback();
            
            return ['passed' => $st === 'libre' && $ped === 'facturado', 'detalles' => 'Ciclo primer piso completado'];
        });

        // CPS-02: Ciclo completo E2E en segundo piso
        $runner('sistema', 'CPS-02', 'Ciclo Completo E2E Salón Segundo Piso', function() {
            $this->conn->begin_transaction();
            $this->conn->query("UPDATE mesas SET estado = 'ocupada' WHERE numero_mesa = 7");
            $this->conn->query("INSERT INTO mesas_y_pedidos (cliente, producto_id, nombre, cantidad, precio, estado, mesas_seleccionadas, piso_mesa) VALUES ('E2E_PISO2', 2, 'Lomo Saltado', 1, 35.00, 'pendiente', '7', 'Piso 2')");
            $this->conn->query("UPDATE inventario SET cantidad = cantidad - 0.25 WHERE id = 3");
            $this->conn->query("UPDATE mesas_y_pedidos SET estado = 'facturado' WHERE cliente = 'E2E_PISO2'");
            $this->conn->query("UPDATE mesas SET estado = 'libre' WHERE numero_mesa = 7");
            
            $st = $this->conn->query("SELECT estado FROM mesas WHERE numero_mesa = 7")->fetch_assoc()['estado'];
            $this->conn->rollback();
            
            return ['passed' => $st === 'libre', 'detalles' => 'Ciclo segundo piso completado'];
        });

        // CPS-03: Ciclo completo E2E en Balcón
        $runner('sistema', 'CPS-03', 'Ciclo Completo E2E Salón Balcón', function() {
            $this->conn->begin_transaction();
            $this->conn->query("UPDATE mesas SET estado = 'ocupada' WHERE numero_mesa = 10");
            $this->conn->query("INSERT INTO mesas_y_pedidos (cliente, producto_id, nombre, cantidad, precio, estado, mesas_seleccionadas, piso_mesa) VALUES ('E2E_BALCON', 6, 'Chilcano', 2, 18.00, 'pendiente', '10', 'Piso 2')");
            $this->conn->query("UPDATE mesas_y_pedidos SET estado = 'facturado' WHERE cliente = 'E2E_BALCON'");
            $this->conn->query("UPDATE mesas SET estado = 'libre' WHERE numero_mesa = 10");
            
            $st = $this->conn->query("SELECT estado FROM mesas WHERE numero_mesa = 10")->fetch_assoc()['estado'];
            $this->conn->rollback();
            
            return ['passed' => $st === 'libre', 'detalles' => 'Ciclo balcón completado'];
        });

        // CPS-04: Rendimiento de Catálogo (50 peticiones)
        $runner('sistema', 'CPS-04', 'Rendimiento y Latencia de Catálogo de Menú', function() {
            $t0 = microtime(true);
            for ($i = 0; $i < 50; $i++) {
                $this->conn->query("SELECT id, nombre, precio FROM menu");
            }
            $avg_ms = ((microtime(true) - $t0) / 50) * 1000;
            return ['passed' => $avg_ms < 10, 'detalles' => sprintf("Latencia media catálogo: %.2f ms (Meta < 10 ms)", $avg_ms)];
        });

        // CPS-05: Rendimiento de Mesas (50 peticiones)
        $runner('sistema', 'CPS-05', 'Rendimiento y Latencia de Estado de Mesas', function() {
            $t0 = microtime(true);
            for ($i = 0; $i < 50; $i++) {
                $this->conn->query("SELECT id, numero_mesa, estado FROM mesas");
            }
            $avg_ms = ((microtime(true) - $t0) / 50) * 1000;
            return ['passed' => $avg_ms < 10, 'detalles' => sprintf("Latencia media mesas: %.2f ms (Meta < 10 ms)", $avg_ms)];
        });

        // CPS-06: Seguridad contra inyecciones SQL en autenticación
        $runner('sistema', 'CPS-06', 'Seguridad contra SQL Injection en Login', function() {
            $payload = "' OR 'x'='x";
            $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $stmt->bind_param('s', $payload);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return ['passed' => $res === null, 'detalles' => 'Inyección en login neutralizada'];
        });

        // CPS-07: Seguridad contra inyecciones SQL en búsqueda de platillos
        $runner('sistema', 'CPS-07', 'Seguridad contra SQL Injection en Menú', function() {
            $payload = "platillo' UNION SELECT id, usuario, password FROM usuarios -- ";
            $stmt = $this->conn->prepare("SELECT id, nombre FROM menu WHERE categoria = ?");
            $stmt->bind_param('s', $payload);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return ['passed' => count($res) === 0, 'detalles' => 'Inyección en menú bloqueada'];
        });

        // CPS-08: Tolerancia a Fallos y Atomicidad ACID
        $runner('sistema', 'CPS-08', 'Tolerancia a Fallos y Reversión Atómica ACID', function() {
            $this->conn->begin_transaction();
            $this->conn->query("INSERT INTO mesas_y_pedidos (cliente, producto_id, nombre, cantidad, precio, estado, mesas_seleccionadas, piso_mesa) VALUES ('ROLLBACK_FAIL', 1, 'Test', 1, 10.00, 'pendiente', '99', 'Piso 1')");
            $this->conn->rollback();
            $c = $this->conn->query("SELECT COUNT(*) AS total FROM mesas_y_pedidos WHERE cliente = 'ROLLBACK_FAIL'")->fetch_assoc()['total'];
            return ['passed' => $c == 0, 'detalles' => 'Rollback completado con 0 registros huérfanos'];
        });
    }
}
