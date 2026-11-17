<?php
/**
 * Gestor de Conexión a Base de Datos - Patrón Singleton
 * Sistema de Ventas "Gusto Marino"
 * 
 * Implementa una arquitectura moderna con:
 * - Carga de variables de entorno (.env)
 * - Manejo seguro de excepciones (PHP 8.x compliant)
 * - Soporte nativo para UTF-8 completo (utf8mb4)
 * - Diagnóstico de salud y métricas de conexión
 */

class Database {
    private static ?mysqli $instance = null;
    private static array $config = [];

    /**
     * Carga y parsea el archivo .env si existe, o aplica valores por defecto
     */
    private static function loadEnv(): void {
        if (!empty(self::$config)) {
            return;
        }

        $envFile = dirname(__DIR__) . '/.env';
        $envVars = [];

        if (file_exists($envFile) && is_readable($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$key, $val] = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val);
                    // Remover comillas si existen
                    $val = trim($val, '"\'');
                    $envVars[$key] = $val;
                }
            }
        }

        self::$config = [
            'host'     => $envVars['DB_HOST'] ?? '127.0.0.1',
            'port'     => (int)($envVars['DB_PORT'] ?? 3306),
            'database' => $envVars['DB_NAME'] ?? 'gusto_marino',
            'username' => $envVars['DB_USER'] ?? 'root',
            'password' => $envVars['DB_PASS'] ?? '',
            'charset'  => $envVars['DB_CHARSET'] ?? 'utf8mb4',
            'timezone' => $envVars['DB_TIMEZONE'] ?? '-05:00',
            'debug'    => filter_var($envVars['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Obtiene la instancia única de conexión MySQLi (Singleton)
     *
     * @return mysqli
     * @throws RuntimeException
     */
    public static function getConnection(): mysqli {
        if (self::$instance !== null && @self::$instance->ping()) {
            return self::$instance;
        }

        self::loadEnv();
        $cfg = self::$config;

        // Configuración de reporte de errores para PHP 8.1+
        mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

        try {
            $mysqli = new mysqli();
            
            // Timeout de conexión de 3 segundos para evitar bloqueos
            $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);

            $mysqli->real_connect(
                $cfg['host'],
                $cfg['username'],
                $cfg['password'],
                $cfg['database'],
                $cfg['port']
            );

            // Forzar charset utf8mb4 para evitar problemas con tildes, caracteres especiales y emojis
            $mysqli->set_charset($cfg['charset']);

            // Configurar zona horaria de la sesión si fue provista
            if (!empty($cfg['timezone'])) {
                $mysqli->query("SET time_zone = '{$cfg['timezone']}'");
            }

            self::$instance = $mysqli;
            return self::$instance;

        } catch (mysqli_sql_exception $e) {
            error_log("Error de conexión a Base de Datos: " . $e->getMessage());

            if ($cfg['debug']) {
                $mensaje = "Error de conexión [{$cfg['host']}:{$cfg['port']} -> {$cfg['database']}]: " . $e->getMessage();
            } else {
                $mensaje = "No se pudo establecer la conexión con el servidor de datos.";
            }

            // Si la llamada espera JSON, responder en JSON limpio
            if (self::isJsonRequest()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error'   => $mensaje,
                    'code'    => 500
                ]);
                exit;
            }

            throw new RuntimeException($mensaje, (int)$e->getCode(), $e);
        }
    }

    /**
     * Verifica si la petición actual espera una respuesta JSON
     */
    private static function isJsonRequest(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        
        return str_contains($accept, 'application/json') || 
               str_contains($contentType, 'application/json') ||
               $isAjax;
    }

    /**
     * Diagnóstico y salud del motor de base de datos
     */
    public static function checkHealth(): array {
        self::loadEnv();
        $start = microtime(true);

        try {
            $conn = self::getConnection();
            $ping = $conn->ping();
            $latencyMs = round((microtime(true) - $start) * 1000, 2);

            // Obtener tablas existentes en gusto_marino
            $tablas = [];
            $res = $conn->query("SHOW TABLES");
            while ($row = $res->fetch_array()) {
                $tabla = $row[0];
                $countRes = $conn->query("SELECT COUNT(*) as total FROM `{$tabla}`");
                $total = $countRes ? $countRes->fetch_assoc()['total'] : 0;
                $tablas[$tabla] = (int)$total;
            }

            return [
                'status'        => 'OK',
                'connected'     => true,
                'host'          => self::$config['host'] . ':' . self::$config['port'],
                'database'      => self::$config['database'],
                'charset'       => $conn->character_set_name(),
                'server_info'   => $conn->server_info,
                'latency_ms'    => $latencyMs,
                'tables'        => $tablas,
                'tables_count'  => count($tablas),
                'timestamp'     => date('Y-m-d H:i:s')
            ];
        } catch (Throwable $e) {
            return [
                'status'     => 'ERROR',
                'connected'  => false,
                'host'       => self::$config['host'] . ':' . self::$config['port'],
                'database'   => self::$config['database'],
                'error'      => $e->getMessage(),
                'timestamp'  => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Cierra explícitamente la conexión si está activa
     */
    public static function close(): void {
        if (self::$instance !== null) {
            @self::$instance->close();
            self::$instance = null;
        }
    }
}
