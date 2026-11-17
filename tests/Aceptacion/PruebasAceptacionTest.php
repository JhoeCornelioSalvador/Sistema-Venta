<?php
/**
 * Batería de Pruebas de Aceptación (10 Pruebas)
 * Sistema de Ventas Rústica - Los Olivos
 */

class PruebasAceptacionTest {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    public function runAll(callable $runner): void {
        // CPA-01: Velocidad de inserción de comanda múltiple
        $runner('aceptacion', 'CPA-01', 'Velocidad de Registro de Comanda de 4 Productos', function() {
            $this->conn->begin_transaction();
            $t0 = microtime(true);
            for ($i = 1; $i <= 4; $i++) {
                $this->conn->query("INSERT INTO mesas_y_pedidos (cliente, producto_id, nombre, cantidad, precio, estado, mesas_seleccionadas, piso_mesa) VALUES ('ACP_CLI', $i, 'Producto $i', 1, 15.00, 'pendiente', '1', 'Piso 1')");
            }
            $elapsed_ms = (microtime(true) - $t0) * 1000;
            $this->conn->rollback();
            return ['passed' => $elapsed_ms < 50, 'detalles' => sprintf("Registro en %.2f ms (Meta < 50 ms)", $elapsed_ms)];
        });

        // CPA-02: Velocidad de consulta para precuenta en caja
        $runner('aceptacion', 'CPA-02', 'Velocidad de Consulta de Precuenta en Caja', function() {
            $t0 = microtime(true);
            $this->conn->query("SELECT nombre, precio, cantidad FROM mesas_y_pedidos WHERE estado = 'pendiente' LIMIT 10");
            $elapsed_ms = (microtime(true) - $t0) * 1000;
            return ['passed' => $elapsed_ms < 20, 'detalles' => sprintf("Precuenta devuelta en %.2f ms", $elapsed_ms)];
        });

        // CPA-03: Catálogo completo con precios positivos
        $runner('aceptacion', 'CPA-03', 'Integridad de Catálogo con 15 Productos Válidos', function() {
            $res = $this->conn->query("SELECT COUNT(*) AS total, MIN(precio) AS min_p FROM menu")->fetch_assoc();
            return ['passed' => $res['total'] >= 15 && $res['min_p'] > 0, 'detalles' => "Catálogo verificado: {$res['total']} productos con precios mayores a cero"];
        });

        // CPA-04: Distribución de mesas en primer piso
        $runner('aceptacion', 'CPA-04', 'Distribución de 6 Mesas en Salón Primer Piso', function() {
            $c = $this->conn->query("SELECT COUNT(*) AS total FROM mesas WHERE piso = 1")->fetch_assoc()['total'];
            return ['passed' => $c == 6, 'detalles' => "Se confirmaron $c mesas en Piso 1"];
        });

        // CPA-05: Distribución de mesas en segundo piso
        $runner('aceptacion', 'CPA-05', 'Distribución de 6 Mesas en Salón Segundo Piso', function() {
            $c = $this->conn->query("SELECT COUNT(*) AS total FROM mesas WHERE piso = 2")->fetch_assoc()['total'];
            return ['passed' => $c == 6, 'detalles' => "Se confirmaron $c mesas en Piso 2"];
        });

        // CPA-06: Asignación de mesas en zona de balcón
        $runner('aceptacion', 'CPA-06', 'Disponibilidad de 3 Mesas en Balcón', function() {
            $c = $this->conn->query("SELECT COUNT(*) AS total FROM mesas WHERE ubicacion_especial = 'balcon'")->fetch_assoc()['total'];
            return ['passed' => $c == 3, 'detalles' => "Se confirmaron $c mesas en Balcón"];
        });

        // CPA-07: Disponibilidad de carnes y mariscos en inventario
        $runner('aceptacion', 'CPA-07', 'Disponibilidad de Insumos Críticos de Carnes', function() {
            $res = $this->conn->query("SELECT COUNT(*) AS total, MIN(cantidad) AS min_c FROM inventario WHERE categoria = 'Carnes'")->fetch_assoc();
            return ['passed' => $res['total'] >= 4 && $res['min_c'] > 0, 'detalles' => "Carnes en almacén verificadas con existencias positivas"];
        });

        // CPA-08: Disponibilidad de bebidas en inventario
        $runner('aceptacion', 'CPA-08', 'Disponibilidad de Insumos Críticos de Bebidas', function() {
            $res = $this->conn->query("SELECT COUNT(*) AS total, MIN(cantidad) AS min_c FROM inventario WHERE categoria = 'Bebidas'")->fetch_assoc();
            return ['passed' => $res['total'] >= 4 && $res['min_c'] > 0, 'detalles' => "Bebidas en almacén verificadas con existencias positivas"];
        });

        // CPA-09: Integridad de recetas técnicas asociadas al menú
        $runner('aceptacion', 'CPA-09', 'Integridad de Recetas Técnicas Asociadas', function() {
            $c = $this->conn->query("SELECT COUNT(DISTINCT menu_id) AS total_platos FROM menu_insumos")->fetch_assoc()['total_platos'];
            return ['passed' => $c >= 5, 'detalles' => "Se confirmaron $c platillos con recetas configuradas"];
        });

        // CPA-10: Verificación de balance financiero no negativo
        $runner('aceptacion', 'CPA-10', 'Consistencia Financiera en Consultas de Caja', function() {
            $res = $this->conn->query("SELECT COALESCE(SUM(precio * cantidad), 0) AS total_ventas FROM mesas_y_pedidos WHERE estado = 'facturado'")->fetch_assoc();
            return ['passed' => floatval($res['total_ventas']) >= 0, 'detalles' => "Balance de ventas procesado sin inconsistencias numéricas"];
        });
    }
}
