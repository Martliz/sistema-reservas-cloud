<?php
// includes/auth.php
// Funciones de autenticación: JWT manual (sin librería externa) y sesiones PHP.

require_once __DIR__ . '/config.php';

// ── JWT manual (HS256) ────────────────────────────────────────────────────

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}

function jwt_create(array $payload): string {
    $header  = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['iat'] = time();
    $payload['exp'] = time() + (8 * 3600); // 8 horas
    $body    = base64url_encode(json_encode($payload));
    $sig     = base64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$sig";
}

function jwt_verify(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $body, $sig] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));

    if (!hash_equals($expected, $sig)) return null;

    $payload = json_decode(base64url_decode($body), true);
    if (!$payload || $payload['exp'] < time()) return null;

    return $payload;
}

// ── Sesión PHP ────────────────────────────────────────────────────────────

function session_init(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function usuario_autenticado(): bool {
    session_init();
    return isset($_SESSION['usuario_id'], $_SESSION['token'])
        && jwt_verify($_SESSION['token']) !== null;
}

function usuario_actual(): ?array {
    if (!usuario_autenticado()) return null;
    return [
        'id'     => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'],
        'email'  => $_SESSION['usuario_email'],
    ];
}

function redirigir_si_no_autenticado(): void {
    if (!usuario_autenticado()) {
        header('Location: /login.php');
        exit;
    }
}

function cerrar_sesion(): void {
    session_init();
    session_destroy();
    header('Location: /login.php');
    exit;
}
