<?php
/**
 * Estandarizador de Respuestas JSON para APIs RESTful
 * Sistema de Ventas "Gusto Marino"
 */

class ApiResponse {
    /**
     * Envía una respuesta de éxito en formato JSON estructurado
     */
    public static function success($data = null, string $message = '', int $statusCode = 200, array $extra = []): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $response = array_merge([
            'success'   => true,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], $extra);

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Envía una respuesta de error con código de estado HTTP correspondiente
     */
    public static function error(string $message, int $statusCode = 400, $data = null): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'success'   => false,
            'error'     => $message,
            'message'   => $message,
            'code'      => $statusCode,
            'data'      => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Error 401 - No autenticado
     */
    public static function unauthorized(string $message = 'No autenticado. Por favor inicie sesión.'): void {
        self::error($message, 401);
    }

    /**
     * Error 403 - Permisos insuficientes
     */
    public static function forbidden(string $message = 'Acceso denegado: permisos insuficientes.'): void {
        self::error($message, 403);
    }

    /**
     * Error 404 - No encontrado
     */
    public static function notFound(string $message = 'Recurso no encontrado.'): void {
        self::error($message, 404);
    }

    /**
     * Envía datos crudos (Array u Objeto) para compatibilidad con código frontend existente
     */
    public static function raw($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
