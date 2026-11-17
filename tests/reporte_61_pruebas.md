# Reporte Oficial de 61 Pruebas Reales - Sistema de Ventas Rústica

**Fecha y hora de ejecución:** 2026-11-17 20:02:47

**Entorno técnico:** PHP 8.0.30 | MySQL 10.4.32-MariaDB | Base: gusto_marino

## Resumen Consolidado de la Evaluación

| Nivel de Prueba | Pruebas Planificadas | Pruebas Ejecutadas | Pruebas Aprobadas | Tasa de Éxito |
| :--- | :--- | :--- | :--- | :--- |
| Pruebas Unitarias | 28 | 28 | 28 | 100.00 % |
| Pruebas Integracion | 15 | 15 | 15 | 100.00 % |
| Pruebas Sistema | 8 | 8 | 8 | 100.00 % |
| Pruebas Aceptacion | 10 | 10 | 10 | 100.00 % |
| **Total Consolidado** | **61** | **61** | **61** | **100.00 %** |

## Pruebas Unitarias (28 Casos)

| ID | Nombre de la Prueba | Estado | Tiempo ms | Detalles Técnicos |
| :--- | :--- | :--- | :--- | :--- |
| CPU-01 | Verificación de clave bcrypt para usuario admin | SUPERADO | 46.72 | Hash bcrypt de admin verificado |
| CPU-02 | Rechazo de acceso con contraseña incorrecta de admin | SUPERADO | 45.73 | Contraseña inválida rechazada correctamente |
| CPU-03 | Verificación de clave bcrypt para usuario mesero | SUPERADO | 46.15 | Hash bcrypt de mesero verificado |
| CPU-04 | Rechazo de acceso con contraseña incorrecta de mesero | SUPERADO | 46.95 | Contraseña inválida de mesero rechazada |
| CPU-05 | Validación de usuario no vacío en formulario de login | SUPERADO | 0.01 | Usuario en blanco detectado como inválido |
| CPU-06 | Disponibilidad del método ApiResponse::success | SUPERADO | 0.01 | Método ApiResponse::success disponible |
| CPU-07 | Disponibilidad del método ApiResponse::error | SUPERADO | 0 | Método ApiResponse::error disponible |
| CPU-08 | Código HTTP 400 para solicitudes incorrectas | SUPERADO | 0 | Código de cliente 400 verificado |
| CPU-09 | Disponibilidad del método ApiResponse::unauthorized | SUPERADO | 0 | Método ApiResponse::unauthorized disponible |
| CPU-10 | Disponibilidad del método ApiResponse::forbidden | SUPERADO | 0 | Método ApiResponse::forbidden disponible |
| CPU-11 | Cálculo de precio individual de Ceviche Clásico | SUPERADO | 0 | Total individual 38.00 correcto |
| CPU-12 | Cálculo de total para 2 porciones de Lomo Saltado | SUPERADO | 0 | Total 2 porciones 70.00 correcto |
| CPU-13 | Cálculo de comanda mixta de plato y bebida | SUPERADO | 0 | Total comanda mixta 52.00 correcto |
| CPU-14 | Cálculo de importe para 3 bebidas Inka Kola | SUPERADO | 0 | Total 3 gaseosas 18.00 correcto |
| CPU-15 | Cálculo de comanda completa de 3 tiempos | SUPERADO | 0 | Total mesa grande 136.00 correcto |
| CPU-16 | Deducción de pescado para 1 ceviche: 0.25 kg | SUPERADO | 0 | Dosis de pescado 0.25 kg |
| CPU-17 | Deducción de pescado para 4 ceviches: 1.00 kg | SUPERADO | 0 | Dosis acumulada 1.00 kg |
| CPU-18 | Deducción de limón sutil para 2 ceviches: 0.30 kg | SUPERADO | 0 | Dosis de limón 0.30 kg |
| CPU-19 | Deducción de lomo fino para 2 lomos: 0.50 kg | SUPERADO | 0 | Dosis de carne 0.50 kg |
| CPU-20 | Deducción de pisco quebranta para 2 pisco sour: 0.16 botellas | SUPERADO | 0 | Dosis de pisco 0.16 botellas |
| CPU-21 | Aceptación de cantidad numérica entera positiva | SUPERADO | 0 | Cantidad 1 aceptada |
| CPU-22 | Rechazo de cantidad igual a cero en comanda | SUPERADO | 0 | Cantidad 0 bloqueada |
| CPU-23 | Rechazo de cantidad negativa en comanda | SUPERADO | 0 | Cantidad negativa bloqueada |
| CPU-24 | Rechazo de cantidad alfanumérica no válida | SUPERADO | 0 | Cadena de texto bloqueada |
| CPU-25 | Rechazo de comanda sin mesa asignada | SUPERADO | 0 | Mesas vacías detectadas |
| CPU-26 | Aceptación de fecha de reserva futura | SUPERADO | 1.29 | Fecha 2026-11-20 aceptada |
| CPU-27 | Rechazo de fecha de reserva anterior a hoy | SUPERADO | 0.01 | Fecha pasada 2026-11-16 rechazada |
| CPU-28 | Validación de rango de personas para reserva | SUPERADO | 0 | Capacidad para 4 comensales válida |

## Pruebas Integracion (15 Casos)

| ID | Nombre de la Prueba | Estado | Tiempo ms | Detalles Técnicos |
| :--- | :--- | :--- | :--- | :--- |
| CPI-01 | Integración Comanda y Ocupación de Mesa 1 | SUPERADO | 2.26 | Mesa 1 actualizada a ocupada |
| CPI-02 | Integración Comanda y Ocupación de Mesa 2 | SUPERADO | 2.55 | Mesa 2 actualizada a ocupada |
| CPI-03 | Integración Comanda y Descuento de Pescado | SUPERADO | 1.69 | Stock pescado reducido 0.50 kg |
| CPI-04 | Integración Comanda y Descuento de Gaseosas | SUPERADO | 1.4 | Stock Inka Kola reducido 2 botellas |
| CPI-05 | Integración Comanda Mixta de Cocina y Bar | SUPERADO | 1.21 | Deducción atómica cocina y bar ejecutada |
| CPI-06 | Integración Facturación y Estado Facturado | SUPERADO | 1.77 | Estado cambiado a facturado |
| CPI-07 | Integración Facturación y Liberación de Mesa 1 | SUPERADO | 1.19 | Mesa 1 liberada con éxito |
| CPI-08 | Integración Facturación y Liberación Múltiple | SUPERADO | 1.32 | Mesas 7 y 8 liberadas en bloque |
| CPI-09 | Integración de Registro de Reserva y Persistencia | SUPERADO | 1.41 | Reserva persistida con ID autoincremental 7 |
| CPI-10 | Integración de Reserva en Zona Especial de Balcón | SUPERADO | 0.25 | Mesa balcón identificada número 9 |
| CPI-11 | Integración de Consulta y Filtro de Platillos | SUPERADO | 0.2 | Se recuperaron 5 platillos de cocina |
| CPI-12 | Integración de Consulta y Filtro de Bebidas | SUPERADO | 0.15 | Se recuperaron 7 bebidas de bar |
| CPI-13 | Integración de Consulta y Filtro de Postres | SUPERADO | 0.17 | Se recuperaron 3 postres tradicionales |
| CPI-14 | Integración de Consulta de Perfil Administrador | SUPERADO | 0.18 | Rol administrador confirmado |
| CPI-15 | Integración de Consulta de Perfil Mesero | SUPERADO | 0.17 | Rol mesero confirmado |

## Pruebas Sistema (8 Casos)

| ID | Nombre de la Prueba | Estado | Tiempo ms | Detalles Técnicos |
| :--- | :--- | :--- | :--- | :--- |
| CPS-01 | Ciclo Completo E2E Salón Primer Piso | SUPERADO | 1.77 | Ciclo primer piso completado |
| CPS-02 | Ciclo Completo E2E Salón Segundo Piso | SUPERADO | 1.94 | Ciclo segundo piso completado |
| CPS-03 | Ciclo Completo E2E Salón Balcón | SUPERADO | 1.67 | Ciclo balcón completado |
| CPS-04 | Rendimiento y Latencia de Catálogo de Menú | SUPERADO | 6.82 | Latencia media catálogo: 0.14 ms (Meta < 10 ms) |
| CPS-05 | Rendimiento y Latencia de Estado de Mesas | SUPERADO | 5.71 | Latencia media mesas: 0.11 ms (Meta < 10 ms) |
| CPS-06 | Seguridad contra SQL Injection en Login | SUPERADO | 0.26 | Inyección en login neutralizada |
| CPS-07 | Seguridad contra SQL Injection en Menú | SUPERADO | 0.19 | Inyección en menú bloqueada |
| CPS-08 | Tolerancia a Fallos y Reversión Atómica ACID | SUPERADO | 1.15 | Rollback completado con 0 registros huérfanos |

## Pruebas Aceptacion (10 Casos)

| ID | Nombre de la Prueba | Estado | Tiempo ms | Detalles Técnicos |
| :--- | :--- | :--- | :--- | :--- |
| CPA-01 | Velocidad de Registro de Comanda de 4 Productos | SUPERADO | 1.31 | Registro en 0.52 ms (Meta < 50 ms) |
| CPA-02 | Velocidad de Consulta de Precuenta en Caja | SUPERADO | 0.25 | Precuenta devuelta en 0.24 ms |
| CPA-03 | Integridad de Catálogo con 15 Productos Válidos | SUPERADO | 0.16 | Catálogo verificado: 15 productos con precios mayores a cero |
| CPA-04 | Distribución de 6 Mesas en Salón Primer Piso | SUPERADO | 0.14 | Se confirmaron 6 mesas en Piso 1 |
| CPA-05 | Distribución de 6 Mesas en Salón Segundo Piso | SUPERADO | 0.13 | Se confirmaron 6 mesas en Piso 2 |
| CPA-06 | Disponibilidad de 3 Mesas en Balcón | SUPERADO | 0.14 | Se confirmaron 3 mesas en Balcón |
| CPA-07 | Disponibilidad de Insumos Críticos de Carnes | SUPERADO | 0.15 | Carnes en almacén verificadas con existencias positivas |
| CPA-08 | Disponibilidad de Insumos Críticos de Bebidas | SUPERADO | 0.13 | Bebidas en almacén verificadas con existencias positivas |
| CPA-09 | Integridad de Recetas Técnicas Asociadas | SUPERADO | 0.14 | Se confirmaron 6 platillos con recetas configuradas |
| CPA-10 | Consistencia Financiera en Consultas de Caja | SUPERADO | 0.2 | Balance de ventas procesado sin inconsistencias numéricas |

