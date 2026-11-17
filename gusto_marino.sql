-- ==========================================================
-- BASE DE DATOS: GUSTO MARINO (Sistema de Ventas y Restaurante)
-- Generado automáticamente con base en el código fuente del proyecto
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `gusto_marino` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `gusto_marino`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- 1. TABLA: usuarios
-- Gestión de acceso al sistema (Administradores y Meseros)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `rol` ENUM('administrador', 'mesero') NOT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuarios iniciales por defecto (Contraseñas cifradas con bcrypt - Password: admin123 / mesero123)
INSERT INTO `usuarios` (`usuario`, `password`, `rol`) VALUES
('admin', '$2y$10$DhQQJlHc3LjDYBtcyye55ua.GbfaSq2DxUICPPSNXAKKkJOuE1jeG', 'administrador'),
('mesero', '$2y$10$l0e6sEb0LQveMwPgOvIeiOFcs4jnZtSv.Saurx3yOwTdUqlsvSX2a', 'mesero');

-- ----------------------------------------------------------
-- 2. TABLA: mesas
-- Control de mesas, pisos, ubicaciones y estado
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `mesas`;
CREATE TABLE `mesas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `numero_mesa` INT NOT NULL UNIQUE,
    `piso` INT NOT NULL DEFAULT 1,
    `ubicacion_especial` VARCHAR(50) NULL DEFAULT NULL,
    `estado` ENUM('libre', 'ocupada', 'reservada') NOT NULL DEFAULT 'libre'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mesas iniciales (Piso 1 y Piso 2, algunas con balcón)
INSERT INTO `mesas` (`numero_mesa`, `piso`, `ubicacion_especial`, `estado`) VALUES
(1, 1, NULL, 'libre'),
(2, 1, NULL, 'libre'),
(3, 1, NULL, 'libre'),
(4, 1, NULL, 'libre'),
(5, 1, NULL, 'libre'),
(6, 1, NULL, 'libre'),
(7, 2, NULL, 'libre'),
(8, 2, NULL, 'libre'),
(9, 2, 'balcon', 'libre'),
(10, 2, 'balcon', 'libre'),
(11, 2, 'balcon', 'libre'),
(12, 2, NULL, 'libre');

-- ----------------------------------------------------------
-- 3. TABLA: inventario
-- Control de insumos por categorías y unidades de medida
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `inventario`;
CREATE TABLE `inventario` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `cantidad` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `unidad` VARCHAR(50) NOT NULL,
    `categoria` VARCHAR(50) NOT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insumos iniciales representativos para cevichería / restaurante marino
INSERT INTO `inventario` (`nombre`, `cantidad`, `unidad`, `categoria`) VALUES
('Filete de Corvina / Pescado', 25.00, 'kg', 'Carnes'),
('Mixtura de Mariscos', 18.00, 'kg', 'Carnes'),
('Lomo Fino de Res', 15.00, 'kg', 'Carnes'),
('Pechuga de Pollo', 20.00, 'kg', 'Carnes'),
('Limón Sutil', 30.00, 'kg', 'Frutas y Verduras'),
('Cebolla Roja', 25.00, 'kg', 'Frutas y Verduras'),
('Ají Amarillo', 10.00, 'kg', 'Frutas y Verduras'),
('Culantro', 5.00, 'atados', 'Frutas y Verduras'),
('Papa Amarilla', 35.00, 'kg', 'Frutas y Verduras'),
('Camote Glaseado', 20.00, 'kg', 'Frutas y Verduras'),
('Choclo Desgranado', 15.00, 'kg', 'Frutas y Verduras'),
('Maracuyá Fresco', 12.00, 'kg', 'Frutas y Verduras'),
('Maíz Morado', 15.00, 'kg', 'Secos y Deshidratados'),
('Arroz Superior', 50.00, 'kg', 'Secos y Deshidratados'),
('Harina de Trigo', 20.00, 'kg', 'Secos y Deshidratados'),
('Canela y Clavo', 2.00, 'kg', 'Secos y Deshidratados'),
('Leche Evaporada', 24.00, 'tarros', 'Lácteos'),
('Leche Condensada', 20.00, 'tarros', 'Lácteos'),
('Mantequilla', 8.00, 'kg', 'Lácteos'),
('Aceite Vegetal', 30.00, 'L', 'Condimentos'),
('Sillao / Salsa de Soya', 10.00, 'L', 'Condimentos'),
('Vinagre Tinto', 8.00, 'L', 'Condimentos'),
('Ajo Molido', 5.00, 'kg', 'Condimentos'),
('Sal Yodada', 25.00, 'kg', 'Condimentos'),
('Pisco Quebranta', 15.00, 'botellas', 'Bebidas'),
('Jarabe de Goma', 10.00, 'botellas', 'Bebidas'),
('Inka Kola 500ml', 48.00, 'unidades', 'Bebidas'),
('Coca Cola 500ml', 48.00, 'unidades', 'Bebidas'),
('Harina de Camote', 10.00, 'kg', 'Panadería y Repostería'),
('Atún en Lata', 24.00, 'latas', 'Enlatados');

-- ----------------------------------------------------------
-- 4. TABLA: menu
-- Platillos, bebidas y postres ofrecidos en el restaurante
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `menu`;
CREATE TABLE `menu` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(150) NOT NULL,
    `categoria` ENUM('platillo', 'bebida', 'postre') NOT NULL,
    `precio` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `descripcion` TEXT NULL,
    `imagen` VARCHAR(255) NULL DEFAULT '',
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menú inicial mapeado con las imágenes reales en /menu/uploads/
INSERT INTO `menu` (`id`, `nombre`, `categoria`, `precio`, `descripcion`, `imagen`) VALUES
(1, 'Ceviche Clásico de Pescado', 'platillo', 38.00, 'Pescado fresco marinado en jugo de limón, ají limo, cebolla roja, camote y choclo.', '1765221369_ceviche.jpg'),
(2, 'Lomo Saltado', 'platillo', 35.00, 'Jugosos trozos de lomo fino salteados al wok con cebolla, tomate, ají y papas fritas.', '1765222261_lomo saltado.jpg'),
(3, 'Causa Rellena', 'platillo', 25.00, 'Suave masa de papa amarilla sazonada con ají amarillo y limón, rellena de pollo o atún.', '1765225101_Causa.jpg'),
(4, 'Chicharrón de Pescado y Mariscos', 'platillo', 36.00, 'Crujientes trozos de pescado y mariscos acompañados de yuca frita y salsa criolla.', '1765321849_Chicharron.jpg'),
(5, 'Seco de Carne', 'platillo', 32.00, 'Carne tierna cocinada a fuego lento en salsa de culantro y chicha de jora con frejoles.', '1765322604_seco de carne.jpg'),
(6, 'Chilcano de Pisco', 'bebida', 18.00, 'Refrescante cóctel a base de pisco quebranta, ginger ale, zumo de limón y gotas de amargo de angostura.', '1765225374_Chilcano.jpg'),
(7, 'Pisco Sour Tradicional', 'bebida', 22.00, 'El trago bandera peruano: pisco quebranta, zumo de limón fresco, jarabe de goma y clara de huevo.', '1765324162_Pisco sour.jpg'),
(8, 'Chicha Morada (Jarra 1L)', 'bebida', 14.00, 'Bebida tradicional elaborada con maíz morado, piña, manzana, canela y clavo de olor.', '1765323294_Chicha morada.jpg'),
(9, 'Refresco de Maracuyá (Jarra 1L)', 'bebida', 12.00, 'Deliciosa y refrescante bebida de pura pulpa de maracuyá natural.', '1765323903_Refresco de maracuya.png'),
(10, 'Inka Kola 500ml', 'bebida', 6.00, 'Gaseosa Inka Kola personal helada.', '1765323505_Inka kola.jpg'),
(11, 'Coca Cola 500ml', 'bebida', 6.00, 'Gaseosa Coca Cola personal helada.', '1765323626_Coca cola.jpg'),
(12, 'Emoliente Especial', 'bebida', 8.00, 'Infusión tradicional caliente a base de linaza, cebada, boldo y hierbas digestivas.', '1765324454_Emoliente.jpeg'),
(13, 'Suspiro a la Limeña', 'postre', 15.00, 'Delicado manjar blanco de yemas cubierto con merengue al oporto y canela.', '1765325098_Suspiro limeo.png'),
(14, 'Mazamorra Morada', 'postre', 10.00, 'Postre tradicional limeño a base de maíz morado, frutas secas y harina de camote.', '1765324787_Mazamorra morda.jpg'),
(15, 'Arroz con Leche', 'postre', 10.00, 'Clásico arroz con leche aromatizado con canela, clavo de olor y pasas.', '1765418569_Arroz con leche.jpeg');

-- ----------------------------------------------------------
-- 5. TABLA: menu_insumos
-- Relación de ingredientes utilizados por platillo para descuento de stock
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `menu_insumos`;
CREATE TABLE `menu_insumos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `menu_id` INT NOT NULL,
    `insumo_id` INT NOT NULL,
    `cantidad_usada` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT `fk_menuinsumo_menu` FOREIGN KEY (`menu_id`) REFERENCES `menu`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_menuinsumo_inventario` FOREIGN KEY (`insumo_id`) REFERENCES `inventario`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insumos asociados para descuento de stock automático
INSERT INTO `menu_insumos` (`menu_id`, `insumo_id`, `cantidad_usada`) VALUES
-- Ceviche Clásico (Pescado 0.25kg, Limón 0.15kg, Cebolla 0.1kg, Camote 0.1kg)
(1, 1, 0.25),
(1, 5, 0.15),
(1, 6, 0.10),
(1, 10, 0.10),
-- Lomo Saltado (Lomo 0.25kg, Cebolla 0.1kg, Papa 0.2kg, Arroz 0.15kg)
(2, 3, 0.25),
(2, 6, 0.10),
(2, 9, 0.20),
(2, 14, 0.15),
-- Chilcano (Pisco 0.05 botellas)
(6, 25, 0.05),
-- Pisco Sour (Pisco 0.08 botellas, Limón 0.05kg, Jarabe 0.03 botellas)
(7, 25, 0.08),
(7, 5, 0.05),
(7, 26, 0.03),
-- Inka Kola (1 unidad)
(10, 27, 1.00),
-- Coca Cola (1 unidad)
(11, 28, 1.00);

-- ----------------------------------------------------------
-- 6. TABLA: mesas_y_pedidos
-- Registro de comandas y pedidos por cliente y mesas
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `mesas_y_pedidos`;
CREATE TABLE `mesas_y_pedidos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cliente` VARCHAR(150) NOT NULL,
    `producto_id` INT NOT NULL,
    `nombre` VARCHAR(150) NOT NULL,
    `cantidad` INT NOT NULL DEFAULT 1,
    `precio` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `estado` ENUM('pendiente', 'facturado', 'cancelado') NOT NULL DEFAULT 'pendiente',
    `mesas_seleccionadas` VARCHAR(100) NOT NULL,
    `piso_mesa` VARCHAR(50) NOT NULL,
    `fecha_pedido` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pedidos_estado` (`estado`),
    INDEX `idx_pedidos_fecha` (`fecha_pedido`),
    CONSTRAINT `fk_pedidos_menu` FOREIGN KEY (`producto_id`) REFERENCES `menu`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. TABLA: reservas
-- Sistema de reservas de mesas por clientes
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `reservas`;
CREATE TABLE `reservas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombres` VARCHAR(100) NOT NULL,
    `apellidos` VARCHAR(100) NOT NULL,
    `telefono` VARCHAR(20) DEFAULT '',
    `fecha_reserva` DATE NOT NULL,
    `hora_reserva` TIME NOT NULL,
    `cantidad_personas` INT NOT NULL DEFAULT 1,
    `cantidad_mesas` INT NOT NULL DEFAULT 1,
    `mesas_seleccionadas` VARCHAR(100) NOT NULL,
    `observaciones` TEXT NULL,
    `estado` ENUM('pendiente', 'confirmada', 'cancelada', 'cumplida') NOT NULL DEFAULT 'pendiente',
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reservas_fecha` (`fecha_reserva`),
    INDEX `idx_reservas_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
