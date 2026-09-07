<?php
/**
 * DIGITAL TRANSPORT - SECURITY HARDENING & HELPERS
 * 
 * Centraliza las cabeceras de seguridad, configuración segura de sesiones y mitigaciones CSRF/XSS.
 */

// 1. Detección Inteligente de HTTPS (Soporte directo, Proxy inverso y Cloudflare)
$isSecure = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
);

// Forzar redirección HTTPS en entorno de producción si la petición viene por HTTP
$appEnv = getenv('APP_ENV') ?: 'production';
if (!$isSecure && $appEnv === 'production' && php_sapi_name() !== 'cli' && !headers_sent()) {
    $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $redirectUrl, true, 301);
    exit();
}

// Configurar opciones de sesión seguras antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// 2. Cabeceras HTTP de Seguridad & HSTS (Strict-Transport-Security)
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\' \'unsafe-inline\' \'unsafe-eval\' https: data:;');
    
    if ($isSecure) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}


// 3. Generación y Validación de Token CSRF
if (!function_exists('getCsrfToken')) {
    function getCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

// 4. Helper de Sanitización HTML (XSS prevention)
if (!function_exists('sanitizeOutput')) {
    function sanitizeOutput($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>
