<?php
/**
 * DIGITAL TRANSPORT - LOGOUT (REFACTORIZADO A PDO)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tipo_usuario_id = $_SESSION['tipo_usuario_id'] ?? 0; 
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// 1. ACTUALIZAR ESTADO DE CHOFER
if ($tipo_usuario_id == 3 && $usuario_id > 0) {
    require_once 'includes/db.php'; 
    try {
        $stmt = $pdo->prepare("UPDATE CHOFER SET estado_servicio = 'INACTIVO' WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
    } catch (PDOException $e) {
        error_log("Error en logout chofer: " . $e->getMessage());
    }
}

// 2. DETERMINAR REDIRECCIÓN
// Redirigir siempre a la página principal (Landing Page) en la raíz
$redirect_url = '../index.php'; 

// 3. DESTRUIR SESIÓN
session_regenerate_id(true);
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// 4. REDIRIGIR
header("Location: " . $redirect_url);
exit();
?>