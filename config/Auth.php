<?php
/**
 * Gestor de Autenticación y Control de Acceso Basado en Roles (RBAC)
 * Sistema de Ventas "Gusto Marino"
 */

class Auth {
    /**
     * Inicia la sesión PHP con parámetros de cookies seguros
     */
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar cookies seguras de sesión
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                       (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_start();
        }
    }

    /**
     * Verifica si hay un usuario autenticado en la sesión actual
     */
    public static function isLoggedIn(): bool {
        self::initSession();
        return !empty($_SESSION['usuario']) && !empty($_SESSION['rol']) && !empty($_SESSION['user_id']);
    }

    /**
     * Obtiene los datos del usuario en sesión
     */
    public static function user(): ?array {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id'      => $_SESSION['user_id'],
            'usuario' => $_SESSION['usuario'],
            'rol'     => $_SESSION['rol']
        ];
    }

    /**
     * Verifica si el usuario actual posee uno de los roles autorizados
     * 
     * @param string|array $roles Rol o array de roles requeridos
     */
    public static function hasRole(string|array $roles): bool {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($user['rol'], $roles, true);
    }

    /**
     * Exige que el usuario esté autenticado. Si no lo está, bloquea el acceso.
     */
    public static function requireLogin(): array {
        self::initSession();

        if (!self::isLoggedIn()) {
            if (self::isJsonRequest()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error'   => 'No autenticado. Por favor inicie sesión.',
                    'code'    => 401
                ]);
                exit;
            }

            // Redirección en peticiones web normales
            header('Location: ../login/login.html');
            exit;
        }

        return self::user();
    }

    /**
     * Exige que el usuario esté autenticado y cuente con los roles autorizados.
     * 
     * @param string|array $roles Roles autorizados (ej: 'administrador' o ['administrador', 'mesero'])
     */
    public static function requireRole(string|array $roles): array {
        $user = self::requireLogin();
        $rolesList = is_array($roles) ? $roles : [$roles];

        if (!in_array($user['rol'], $rolesList, true)) {
            if (self::isJsonRequest()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error'   => 'Acceso denegado: se requieren permisos de ' . implode(' o ', $rolesList),
                    'code'    => 403
                ]);
                exit;
            }

            // En petición web redirigir al dashboard correspondiente
            if ($user['rol'] === 'mesero') {
                header('Location: ../Dashboard/dashboard_mesero.html');
            } else {
                header('Location: ../Dashboard/dashboard_admin.html');
            }
            exit;
        }

        return $user;
    }

    /**
     * Autentica un usuario con:
     * - Rate limiting anti fuerza bruta
     * - Cifrado seguro con password_verify() (bcrypt)
     * - Migración / auto-upgrade transparente de contraseñas legacy en texto plano
     * - Regeneración de ID de sesión anti Session Fixation
     */
    public static function login(mysqli $conn, string $usuario, string $password): array {
        self::initSession();

        // Control anti fuerza bruta: Máximo 5 intentos fallidos en 5 minutos
        $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'last_time' => 0];
        $cooldown = 300; // 5 minutos

        if ($attempts['count'] >= 5) {
            $tiempoRestante = $cooldown - (time() - $attempts['last_time']);
            if ($tiempoRestante > 0) {
                $minutos = ceil($tiempoRestante / 60);
                return [
                    'success' => false,
                    'message' => "Demasiados intentos fallidos. Intente de nuevo en {$minutos} minuto(s)."
                ];
            } else {
                // Reiniciar contador de intentos luego del cooldown
                $_SESSION['login_attempts'] = ['count' => 0, 'last_time' => 0];
            }
        }

        $usuario = trim($usuario);
        $password = trim($password);

        if (empty($usuario) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Por favor, completa todos los campos.'
            ];
        }

        $stmt = $conn->prepare("SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ? LIMIT 1");
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error interno al procesar la solicitud.'
            ];
        }

        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            $stmt->close();
            self::recordFailedAttempt();
            return [
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos.'
            ];
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        $passwordMatch = false;

        // 1. Verificación segura estándar con hash bcrypt / argon2
        if (password_verify($password, $user['password'])) {
            $passwordMatch = true;

            // Si el algoritmo de hash necesita actualizarse
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                self::updateUserPasswordHash($conn, (int)$user['id'], $password);
            }
        }
        // 2. Compatibilidad transparente: si la contraseña en BD seguía en texto plano
        elseif ($password === $user['password']) {
            $passwordMatch = true;
            // Migrar automáticamente en caliente a hash bcrypt seguro
            self::updateUserPasswordHash($conn, (int)$user['id'], $password);
        }

        if (!$passwordMatch) {
            self::recordFailedAttempt();
            return [
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos.'
            ];
        }

        // Login exitoso: Resetear contador de intentos fallidos
        unset($_SESSION['login_attempts']);

        // Regenerar ID de sesión para prevenir Session Fixation attacks
        session_regenerate_id(true);

        // Guardar variables de sesión
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['rol']     = $user['rol'];
        $_SESSION['user_id'] = (int)$user['id'];

        $redirect = ($user['rol'] === 'administrador')
            ? '../Dashboard/dashboard_admin.html'
            : '../Dashboard/dashboard_mesero.html';

        return [
            'success'  => true,
            'redirect' => $redirect,
            'user'     => [
                'id'      => (int)$user['id'],
                'usuario' => $user['usuario'],
                'rol'     => $user['rol']
            ]
        ];
    }

    /**
     * Registra un intento fallido de autenticación
     */
    private static function recordFailedAttempt(): void {
        $count = ($_SESSION['login_attempts']['count'] ?? 0) + 1;
        $_SESSION['login_attempts'] = [
            'count'     => $count,
            'last_time' => time()
        ];
    }

    /**
     * Actualiza la contraseña en la base de datos a un hash bcrypt moderno
     */
    private static function updateUserPasswordHash(mysqli $conn, int $userId, string $plainPassword): void {
        $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $upStmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        if ($upStmt) {
            $upStmt->bind_param("si", $newHash, $userId);
            $upStmt->execute();
            $upStmt->close();
        }
    }

    /**
     * Cierra la sesión activa de forma segura
     */
    public static function logout(): void {
        self::initSession();
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Detecta si la petición espera una respuesta en formato JSON
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
}
