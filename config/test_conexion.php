<?php
/**
 * Herramienta de Diagnóstico y Verificación de Conexión
 * Sistema de Ventas "Gusto Marino"
 */

require_once __DIR__ . '/Database.php';

$health = Database::checkHealth();
$isCli = (php_sapi_name() === 'cli');
$isJson = isset($_GET['format']) && $_GET['format'] === 'json';

if ($isCli || $isJson) {
    if (!$isCli) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Base de Datos - Gusto Marino</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b132b;
            --card-bg: #1c2541;
            --accent: #48cae4;
            --text-main: #f0f3f8;
            --text-muted: #8d99ae;
            --success: #2ec4b6;
            --danger: #e63946;
            --border: #3a506b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0b132b 0%, #1c2541 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 780px;
            background: rgba(28, 37, 65, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
        }
        .header p {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 4px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
        }
        .status-badge.ok {
            background: rgba(46, 196, 182, 0.15);
            color: var(--success);
            border: 1px solid var(--success);
        }
        .status-badge.error {
            background: rgba(230, 57, 70, 0.15);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        .grid-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .info-card {
            background: rgba(11, 19, 43, 0.6);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
        }
        .info-card .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .info-card .value {
            font-size: 17px;
            font-weight: 600;
            color: var(--accent);
            word-break: break-all;
        }
        .table-section {
            margin-top: 24px;
        }
        .table-section h2 {
            font-size: 16px;
            margin-bottom: 14px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
        }
        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        .table-chip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(11, 19, 43, 0.5);
            border: 1px solid var(--border);
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
        }
        .table-chip .count {
            background: var(--accent);
            color: #0b132b;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .actions {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary {
            background: var(--accent);
            color: #0b132b;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            border-color: var(--accent);
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Diagnóstico de Base de Datos</h1>
                <p>Sistema de Ventas &bull; Gusto Marino</p>
            </div>
            <?php if ($health['connected']): ?>
                <span class="status-badge ok">&#10004; Conectado</span>
            <?php else: ?>
                <span class="status-badge error">&#10008; Desconectado</span>
            <?php endif; ?>
        </div>

        <?php if ($health['connected']): ?>
            <div class="grid-info">
                <div class="info-card">
                    <div class="label">Servidor</div>
                    <div class="value"><?= htmlspecialchars($health['host']) ?></div>
                </div>
                <div class="info-card">
                    <div class="label">Base de Datos</div>
                    <div class="value"><?= htmlspecialchars($health['database']) ?></div>
                </div>
                <div class="info-card">
                    <div class="label">Latencia (Ping)</div>
                    <div class="value"><?= $health['latency_ms'] ?> ms</div>
                </div>
                <div class="info-card">
                    <div class="label">Charset</div>
                    <div class="value"><?= htmlspecialchars($health['charset']) ?></div>
                </div>
                <div class="info-card">
                    <div class="label">Versión Motor</div>
                    <div class="value"><?= htmlspecialchars($health['server_info']) ?></div>
                </div>
                <div class="info-card">
                    <div class="label">Tablas Registradas</div>
                    <div class="value"><?= $health['tables_count'] ?> tablas</div>
                </div>
            </div>

            <div class="table-section">
                <h2>
                    <span>Tablas y Registros Activos</span>
                    <small style="color: var(--text-muted); font-size: 13px; font-weight: normal;">Verificación de integridad</small>
                </h2>
                <div class="table-grid">
                    <?php foreach ($health['tables'] as $tabla => $cant): ?>
                        <div class="table-chip">
                            <span><?= htmlspecialchars($tabla) ?></span>
                            <span class="count"><?= $cant ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div style="background: rgba(230, 57, 70, 0.1); border: 1px solid var(--danger); padding: 18px; border-radius: 8px; margin-bottom: 24px;">
                <h3 style="color: var(--danger); margin-bottom: 8px;">Detalle del Error:</h3>
                <p style="font-family: monospace; font-size: 13px;"><?= htmlspecialchars($health['error'] ?? 'Error desconocido') ?></p>
            </div>
        <?php endif; ?>

        <div class="actions">
            <a href="../login/login.html" class="btn btn-primary">&#128274; Ir al Login</a>
            <a href="?format=json" class="btn btn-secondary">&#128196; Ver como JSON</a>
            <a href="../Dashboard/dashboard_admin.html" class="btn btn-secondary">&#128202; Panel Dashboard</a>
        </div>
    </div>
</body>
</html>
