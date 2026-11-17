<?php
/**
 * Master Test Runner - 61 Pruebas Reales
 * Sistema de Ventas Rústica - Los Olivos
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$BASE_DIR = dirname(__DIR__);
require_once $BASE_DIR . '/config/Database.php';
require_once $BASE_DIR . '/config/ApiResponse.php';
require_once $BASE_DIR . '/config/Auth.php';

require_once __DIR__ . '/Unitarias/PruebasUnitariasTest.php';
require_once __DIR__ . '/Integracion/PruebasIntegracionTest.php';
require_once __DIR__ . '/Sistema/PruebasSistemaTest.php';
require_once __DIR__ . '/Aceptacion/PruebasAceptacionTest.php';

$conn = Database::getConnection();

$report = [
    'fecha_ejecucion' => date('Y-m-d H:i:s'),
    'entorno' => [
        'php_version' => PHP_VERSION,
        'mysql_version' => $conn->server_info,
        'database' => 'gusto_marino'
    ],
    'resumen' => [
        'total' => 0,
        'aprobadas' => 0,
        'fallidas' => 0,
        'tiempo_total_ms' => 0.0
    ],
    'por_tipo' => [
        'Unitarias' => ['total' => 0, 'aprobadas' => 0, 'fallidas' => 0, 'items' => []],
        'Integracion' => ['total' => 0, 'aprobadas' => 0, 'fallidas' => 0, 'items' => []],
        'Sistema' => ['total' => 0, 'aprobadas' => 0, 'fallidas' => 0, 'items' => []],
        'Aceptacion' => ['total' => 0, 'aprobadas' => 0, 'fallidas' => 0, 'items' => []],
    ]
];

$runner = function(string $nivel, string $id, string $nombre, callable $testFunc) use (&$report) {
    $start = microtime(true);
    $estado = 'FALLO';
    $detalles = '';
    
    try {
        $res = $testFunc();
        if ($res['passed']) {
            $estado = 'SUPERADO';
            $report['resumen']['aprobadas']++;
            $report['por_tipo'][ucfirst($nivel)]['aprobadas']++;
        } else {
            $estado = 'FALLO';
            $report['resumen']['fallidas']++;
            $report['por_tipo'][ucfirst($nivel)]['fallidas']++;
        }
        $detalles = $res['detalles'];
    } catch (Exception $e) {
        $estado = 'ERROR';
        $report['resumen']['fallidas']++;
        $report['por_tipo'][ucfirst($nivel)]['fallidas']++;
        $detalles = $e->getMessage();
    }
    
    $duration = round((microtime(true) - $start) * 1000, 2);
    $report['resumen']['total']++;
    $report['resumen']['tiempo_total_ms'] += $duration;
    $report['por_tipo'][ucfirst($nivel)]['total']++;
    
    $item = [
        'id' => $id,
        'nombre' => $nombre,
        'estado' => $estado,
        'tiempo_ms' => $duration,
        'detalles' => $detalles
    ];
    $report['por_tipo'][ucfirst($nivel)]['items'][] = $item;
    
    $color = ($estado === 'SUPERADO') ? "\033[32m[PASS]\033[0m" : "\033[31m[FAIL]\033[0m";
    echo sprintf(" %s %-8s %-48s (%5.2f ms) %s\n", $color, $id, substr($nombre, 0, 48), $duration, substr($detalles, 0, 40));
};

echo "\n========================================================================================\n";
echo "   EJECUTANDO SUITE COMPLETA DE 61 PRUEBAS REALES - SISTEMA DE VENTAS RUSTICA          \n";
echo "========================================================================================\n";

echo "\n>>> 1. PRUEBAS UNITARIAS (28 Pruebas)\n";
$uTest = new PruebasUnitariasTest($conn);
$uTest->runAll($runner);

echo "\n>>> 2. PRUEBAS DE INTEGRACION (15 Pruebas)\n";
$iTest = new PruebasIntegracionTest($conn);
$iTest->runAll($runner);

echo "\n>>> 3. PRUEBAS DE SISTEMA (8 Pruebas)\n";
$sTest = new PruebasSistemaTest($conn);
$sTest->runAll($runner);

echo "\n>>> 4. PRUEBAS DE ACEPTACION (10 Pruebas)\n";
$aTest = new PruebasAceptacionTest($conn);
$aTest->runAll($runner);

$tasa_global = ($report['resumen']['total'] > 0) ? ($report['resumen']['aprobadas'] / $report['resumen']['total']) * 100 : 0;

echo "\n========================================================================================\n";
echo sprintf(" RESUMEN GLOBAL: %d Pruebas | %d Aprobadas | %d Fallidas\n", 
    $report['resumen']['total'], 
    $report['resumen']['aprobadas'], 
    $report['resumen']['fallidas']);
echo sprintf(" TASA DE EXITO REAL: %.2f%%\n", $tasa_global);
echo sprintf(" TIEMPO TOTAL ACUMULADO: %.2f ms\n", $report['resumen']['tiempo_total_ms']);
echo "========================================================================================\n";

foreach ($report['por_tipo'] as $tipo => $data) {
    $tasa = ($data['total'] > 0) ? ($data['aprobadas'] / $data['total']) * 100 : 0;
    echo sprintf("  - %-14s: %2d / %2d aprobadas (%.2f%%)\n", $tipo, $data['aprobadas'], $data['total'], $tasa);
}
echo "========================================================================================\n\n";

// Guardar reportes
$dest_json_sys = __DIR__ . '/reporte_61_pruebas.json';
$dest_md_sys = __DIR__ . '/reporte_61_pruebas.md';

$dest_json_docs = 'C:/Users/JuanPerezma/Desktop/Jhoe/docs/Calidad de software/reporte_61_pruebas.json';
$dest_md_docs = 'C:/Users/JuanPerezma/Desktop/Jhoe/docs/Calidad de software/reporte_61_pruebas.md';

$jsonData = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Generar Markdown ordenado por tipo de prueba
$md = "# Reporte Oficial de 61 Pruebas Reales - Sistema de Ventas Rústica\n\n";
$md .= "**Fecha y hora de ejecución:** " . $report['fecha_ejecucion'] . "\n\n";
$md .= "**Entorno técnico:** PHP " . $report['entorno']['php_version'] . " | MySQL " . $report['entorno']['mysql_version'] . " | Base: " . $report['entorno']['database'] . "\n\n";

$md .= "## Resumen Consolidado de la Evaluación\n\n";
$md .= "| Nivel de Prueba | Pruebas Planificadas | Pruebas Ejecutadas | Pruebas Aprobadas | Tasa de Éxito |\n";
$md .= "| :--- | :--- | :--- | :--- | :--- |\n";
foreach ($report['por_tipo'] as $tipo => $data) {
    $tasa = ($data['total'] > 0) ? ($data['aprobadas'] / $data['total']) * 100 : 0;
    $md .= "| Pruebas " . $tipo . " | " . $data['total'] . " | " . $data['total'] . " | " . $data['aprobadas'] . " | " . number_format($tasa, 2) . " % |\n";
}
$md .= "| **Total Consolidado** | **" . $report['resumen']['total'] . "** | **" . $report['resumen']['total'] . "** | **" . $report['resumen']['aprobadas'] . "** | **" . number_format($tasa_global, 2) . " %** |\n\n";

foreach ($report['por_tipo'] as $tipo => $data) {
    $md .= "## Pruebas " . $tipo . " (" . $data['total'] . " Casos)\n\n";
    $md .= "| ID | Nombre de la Prueba | Estado | Tiempo ms | Detalles Técnicos |\n";
    $md .= "| :--- | :--- | :--- | :--- | :--- |\n";
    foreach ($data['items'] as $it) {
        $md .= "| " . $it['id'] . " | " . $it['nombre'] . " | " . $it['estado'] . " | " . $it['tiempo_ms'] . " | " . $it['detalles'] . " |\n";
    }
    $md .= "\n";
}

file_put_contents($dest_json_sys, $jsonData);
file_put_contents($dest_md_sys, $md);

file_put_contents($dest_json_docs, $jsonData);
file_put_contents($dest_md_docs, $md);

echo "Evidencias exportadas correctamente a:\n";
echo " - $dest_json_sys\n";
echo " - $dest_md_sys\n";
echo " - $dest_json_docs\n";
echo " - $dest_md_docs\n";
