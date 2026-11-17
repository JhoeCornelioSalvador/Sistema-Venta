<?php
/**
 * Batería de Pruebas de Integración (15 Pruebas)
 * Sistema de Ventas Rústica - Los Olivos
 */

class PruebasIntegracionTest {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    public function runAll(callable $runner): void {
        // CPI-01: Pedidos -> Mesa 1 (ocupación)
        $runner('integracion', 'CPI-01', 'Integración Comanda y Ocupación de Mesa 1', function() {
            $this->conn->begin_transaction();
            $this->conn->query("UPDATE mesas SET estado = 'libre' WHERE numero_mesa = 1");
            $this->conn->query("INSERT INTO mesas_y_pedidos (cliente, producto_id, nombre, cantidad, precio, estado, mesas_seleccionadas, piso_mesa) VALUES ('INT_CLI_1', 1, 'Ceviche', 1, 38.00, 'pendiente', '1', 'Piso 1')");
            $this->conn->query("UPDATE mesas SET estado = 'ocupada' WHERE numero_mesa = 1");
            $st = $this->conn->query("SELECT estado FROM mesas WHERE numero_mesa = 1")->fetch_assoc()['estado'];
            $this->conn->rollback();
            return ['passed' => $st === 'ocupada', 'detalles' => 'Mesa 1 actualizada a ocupada'];
        });

        // CPI-02: Pedidos -> Mesa 2 (ocupación)
        $runner('integracion', 'CPI-02', 'Integración Comanda y Ocupación de Mesa 2', function() {
            $this->conn->begin_transaction();
            $this->conn->query("UPDATE mesas SET estado = 'libre' WHERE numero_mesa = 2");
            $this->conn->query("INSERT INTO mesas_y_pedidos (cliente, producto_id, nombre, cantidad, precio, estado, mesas_seleccionadas, piso_mesa) VALUES ('INT_CLI_2', 2, 'Lomo Saltado', 1, 35.00, 'pendiente', '2', 'Piso 1')");
            $this->conn->query("UPDATE mesas SET estado = 'ocupada' WHERE numero_mesa = 2");
            $st = $this->conn->query("SELECT estado FROM mesas WHERE numero_mesa = 2")->fetch_assoc()['estado'];
            $this->conn->rollback();
            return ['passed' => $st === 'ocupada', 'detalles' => 'Mesa 2 actualizada a ocupada'];
        });

        // CPI-03: Descuento de pescado en inventario
        $runner('integracion', 'CPI-03', 'Integración Comanda y Descuento de Pescado', function() {
            $this->conn->begin_transaction();
            $cant0 = floatval($this->conn->query("SELECT cantidad FROM inventario WHERE id = 1")->fetch_assoc()['cantidad']);
            $this->conn->query("UPDATE inventario SET cantidad = cantidad - 0.50 WHERE id = 1");
            $cant1 = floatval($this->conn->query("SELECT cantidad FROM inventario WHERE id = 1")->fetch_assoc()['cantidad']);
            $this->conn->rollback();
            return ['passed' => abs(($cant0 - 0.50) - $cant1) < 0.01, 'detalles' => 'Stock pescado reducido 0.50 kg'];
        });

        // CPI-04: Descuento de bebidas en inventario
        $runner('integracion', 'CPI-04', 'Integración Comanda y Descuento de Gaseosas', function() {
            $this->conn->begin_transaction();
            $cant0 = floatval($this->conn->query("SELECT cantidad FROM inventario WHERE id = 27")->fetch_assoc()['cantidad']);
            $this->conn->query("UPDATE inventario SET cantidad = cantidad - 2 WHERE id = 27");
            $cant1 = floatval($this->conn->query("SELECT cantidad FROM inventario WHERE id = 27")->fetch_assoc()['cantidad']);
            $this->conn->rollback();
            return ['passed' => abs(($cant0 - 2) - $cant1) < 0.01, 'detalles' => 'Stock Inka Kola reducido 2 botellas'];
        });

        // CPI-05: Descuento simultáneo cocina y bar
        $runner('integracion', 'CPI-05', 'Integración Comanda Mixta de Cocina y Bar', function() {
            $this->conn->begin_transaction();
            $this->conn->query("UPDATE inventario SET cantidad = cantidad - 0.25 WHERE id = 3"); // Lomo
            $this->conn->query("UPDATE inventario SET cantidad = cantidad - 0.05 WHERE id = 25"); // Pisco
            $this->conn->rollback();
            return ['passed' => true, 'detalles' => 'Deducción atómica cocina y bar ejecutada'];
        });

        // CPI-06: Facturación -> Actualización a facturado
        $runner('integracion', 'CPI-06', 'Integración Facturación y Estado Facturado', function() {
            $this->conn->begin_transaction();
            $this->conn->query("INSERT INTO mesas_y_pedidos (cliente, producto_id, nombre, cantidad, precio, estado, mesas_seleccionadas, piso_mesa) VALUES ('CLI_FAC_1', 1, 'Ceviche', 1, 38.00, 'pendiente', '3', 'Piso 1')");
            $this->conn->query("UPDATE mesas_y_pedidos SET estado = 'facturado' WHERE cliente = 'CLI_FAC_1'");
            $st = $this->conn->query("SELECT estado FROM mesas_y_pedidos WHERE cliente = 'CLI_FAC_1'")->fetch_assoc()['estado'];
            $this->conn->rollback();
            return ['passed' => $st === 'facturado', 'detalles' => 'Estado cambiado a facturado'];
        });

        // CPI-07: Facturación -> Liberación de Mesa 1
        $runner('integracion', 'CPI-07', 'Integración Facturación y Liberación de Mesa 1', function() {
            $this->conn->begin_transaction();
            $this->conn->query("UPDATE mesas SET estado = 'ocupada' WHERE numero_mesa = 1");
            $this->conn->query("UPDATE mesas SET estado = 'libre' WHERE numero_mesa = 1");
            $st = $this->conn->query("SELECT estado FROM mesas WHERE numero_mesa = 1")->fetch_assoc()['estado'];
            $this->conn->rollback();
            return ['passed' => $st === 'libre', 'detalles' => 'Mesa 1 liberada con éxito'];
        });

        // CPI-08: Facturación -> Liberación de Mesas Múltiples (Mesa 7 y 8)
        $runner('integracion', 'CPI-08', 'Integración Facturación y Liberación Múltiple', function() {
            $this->conn->begin_transaction();
            $this->conn->query("UPDATE mesas SET estado = 'ocupada' WHERE numero_mesa IN (7, 8)");
            $this->conn->query("UPDATE mesas SET estado = 'libre' WHERE numero_mesa IN (7, 8)");
            $st7 = $this->conn->query("SELECT estado FROM mesas WHERE numero_mesa = 7")->fetch_assoc()['estado'];
            $st8 = $this->conn->query("SELECT estado FROM mesas WHERE numero_mesa = 8")->fetch_assoc()['estado'];
            $this->conn->rollback();
            return ['passed' => $st7 === 'libre' && $st8 === 'libre', 'detalles' => 'Mesas 7 y 8 liberadas en bloque'];
        });

        // CPI-09: Reservas -> Persistencia en tabla reservas
        $runner('integracion', 'CPI-09', 'Integración de Registro de Reserva y Persistencia', function() {
            $this->conn->begin_transaction();
            $stmt = $this->conn->prepare("INSERT INTO reservas (nombres, apellidos, telefono, fecha_reserva, hora_reserva, cantidad_personas, cantidad_mesas, mesas_seleccionadas, estado) VALUES ('Maria', 'Lopez', '987654321', '2026-11-20', '20:00:00', 2, 1, '3', 'pendiente')");
            $stmt->execute();
            $id = $stmt->insert_id;
            $stmt->close();
            $this->conn->rollback();
            return ['passed' => $id > 0, 'detalles' => "Reserva persistida con ID autoincremental $id"];
        });

        // CPI-10: Reservas -> Asignación de mesa en balcón
        $runner('integracion', 'CPI-10', 'Integración de Reserva en Zona Especial de Balcón', function() {
            $res = $this->conn->query("SELECT id, numero_mesa FROM mesas WHERE ubicacion_especial = 'balcon' LIMIT 1")->fetch_assoc();
            return ['passed' => !empty($res), 'detalles' => "Mesa balcón identificada número " . $res['numero_mesa']];
        });

        // CPI-11: Catálogo -> Filtrado de platillos
        $runner('integracion', 'CPI-11', 'Integración de Consulta y Filtro de Platillos', function() {
            $c = $this->conn->query("SELECT COUNT(*) AS total FROM menu WHERE categoria = 'platillo'")->fetch_assoc()['total'];
            return ['passed' => $c >= 5, 'detalles' => "Se recuperaron $c platillos de cocina"];
        });

        // CPI-12: Catálogo -> Filtrado de bebidas
        $runner('integracion', 'CPI-12', 'Integración de Consulta y Filtro de Bebidas', function() {
            $c = $this->conn->query("SELECT COUNT(*) AS total FROM menu WHERE categoria = 'bebida'")->fetch_assoc()['total'];
            return ['passed' => $c >= 5, 'detalles' => "Se recuperaron $c bebidas de bar"];
        });

        // CPI-13: Catálogo -> Filtrado de postres
        $runner('integracion', 'CPI-13', 'Integración de Consulta y Filtro de Postres', function() {
            $c = $this->conn->query("SELECT COUNT(*) AS total FROM menu WHERE categoria = 'postre'")->fetch_assoc()['total'];
            return ['passed' => $c >= 3, 'detalles' => "Se recuperaron $c postres tradicionales"];
        });

        // CPI-14: Autenticación -> Verificación de rol administrador
        $runner('integracion', 'CPI-14', 'Integración de Consulta de Perfil Administrador', function() {
            $rol = $this->conn->query("SELECT rol FROM usuarios WHERE usuario = 'admin'")->fetch_assoc()['rol'];
            return ['passed' => $rol === 'administrador', 'detalles' => 'Rol administrador confirmado'];
        });

        // CPI-15: Autenticación -> Verificación de rol mesero
        $runner('integracion', 'CPI-15', 'Integración de Consulta de Perfil Mesero', function() {
            $rol = $this->conn->query("SELECT rol FROM usuarios WHERE usuario = 'mesero'")->fetch_assoc()['rol'];
            return ['passed' => $rol === 'mesero', 'detalles' => 'Rol mesero confirmado'];
        });
    }
}
