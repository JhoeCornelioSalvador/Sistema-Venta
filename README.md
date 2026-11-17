# Sistema de Ventas y Gestión para Restaurante - Gusto Marino

Sistema web desarrollado para digitalizar y gestionar las operaciones de salón, toma de comandas, catálogo de menú, inventario de insumos, reservas y facturación en restaurantes.

---

## Requisitos del Sistema

* Servidor web Apache (incluido en XAMPP o similar).
* Motor de base de datos MySQL o MariaDB.
* PHP versión 8.0 o superior.
* Navegador web moderno (Chrome, Edge o Firefox).

---

## Instalación y Puesta en Marcha

1. **Clonar o ubicar el proyecto:**
   Ubicar la carpeta del proyecto dentro del directorio de publicación del servidor web, por ejemplo en `C:\xampp\htdocs\sistema_ventas`.

2. **Base de datos:**
   * Iniciar los servicios de Apache y MySQL.
   * Acceder al gestor de base de datos (por ejemplo phpMyAdmin).
   * Crear una base de datos llamada `gusto_marino`.
   * Importar el archivo `gusto_marino.sql` ubicado en la raíz del proyecto.

3. **Variables de entorno:**
   * Crear o verificar el archivo `.env` en la raíz del proyecto basándose en `.env.example`:
     ```ini
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_NAME=gusto_marino
     DB_USER=root
     DB_PASS=
     ```

4. **Acceso al aplicativo:**
   Abrir el navegador web e ingresar a:
   ```text
   http://localhost/sistema_ventas/
   ```

---

## Cuentas de Acceso para Pruebas

El sistema cuenta con dos perfiles de usuario predeterminados con contraseñas protegidas mediante cifrado seguro:

| Perfil | Usuario | Contraseña | Funcionalidad principal |
| :--- | :--- | :--- | :--- |
| Administrador | admin | admin123 | Gestión global de menú, inventario, reportes y ventas |
| Mesero | mesero | mesero123 | Visualización del salón de mesas y toma de comandas |

---

## Módulos Principales

* **Control de Acceso:** Inicio y cierre de sesión con validación de roles y protección contra accesos indebidos.
* **Mesas y Comandas:** Mapa interactivo de mesas organizadas por pisos y toma de pedidos en tiempo real.
* **Catálogo de Menú:** Registro y organización de platillos, bebidas y postres, enlazados a recetas con insumos específicos.
* **Inventario:** Control de existencias en almacén con descuento automático de insumos al ingresar comandas.
* **Facturación y Caja:** Liquidación de consumos de mesas, emisión de comprobantes y liberación automática de mesas atendidas.
* **Reservas:** Registro, consulta y asignación de mesas para reservas de comensales.
* **Reportes y Estadísticas:** Resumen de ingresos diarios, platos de mayor rotación y filtros de consulta por rango de fechas.

---

## Ejecución de Pruebas Automatizadas

El proyecto incluye una suite de pruebas para verificar la lógica de negocio, cálculos de importes, descuentos de inventario y respuestas de endpoints.

Para ejecutar la batería de pruebas:

1. Abrir una terminal o consola de comandos.
2. Navegar hasta la carpeta de pruebas:
   ```bash
   cd tests
   ```
3. Ejecutar el comprobador:
   ```bash
   php run_all_tests.php
   ```
El resultado mostrará el estado de aprobación de cada caso de prueba en pantalla y exportará los resultados en formato JSON y Markdown.
