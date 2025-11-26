<?php
/**
 * Archivo: logout.php
 * Descripción: Cierra la sesión de cualquier usuario y redirige condicionalmente.
 */

// 1. Iniciar o reanudar la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. VERIFICAR DATOS NECESARIOS ANTES DE DESTRUIR LA SESIÓN
$tipo_usuario_id = $_SESSION['tipo_usuario_id'] ?? 0; 
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// 3. LÓGICA DE NEGOCIO: ACTUALIZAR ESTADO DE CHOFER
if ($tipo_usuario_id == 3 && $usuario_id > 0) {
    // Se asume que 'bd.php' está en el mismo directorio.
    require_once 'bd.php'; 

    if (isset($conn) && !$conn->connect_error) {
        $sql_update = "UPDATE CHOFER SET estado_servicio = 'INACTIVO' WHERE usuario_id = ?";
        $stmt_update = $conn->prepare($sql_update);

        if ($stmt_update) {
            $stmt_update->bind_param("i", $usuario_id);
            $stmt_update->execute();
            $stmt_update->close();
        }
        @$conn->close();
    }
}


// 4. Determinar la URL de Redirección

// Ruta para Choferes y Admins (confirmada por la estructura de la imagen)
$login_personal_path = '../frontend/inicio-sesion-lineas-choferes/login.php'; 
$redirect_url = $login_personal_path; 

if ($tipo_usuario_id == 1) {
    // ⭐ CORRECCIÓN CLAVE: Redirección simple a '../index.php' para el pasajero,
    // asumiendo que el index público está en la raíz del proyecto.
    $redirect_url = '../index.php'; 
} 

// Opcional: Manejar la redirección GET para pasajeros (ejemplo: a puntos-recarga.php)
if (isset($_GET['redirect']) && $tipo_usuario_id == 1) {
    $safe_redirect = basename($_GET['redirect']);
    // La ruta aquí apunta a la subcarpeta 'cliente'
    $redirect_url = '../frontend/' . $safe_redirect; 
}


// 5. DESTRUIR LA SESIÓN DE FORMA SEGURA
session_regenerate_id(true); // Previene Fijación de Sesión

// 6. Limpiar todas las variables de sesión
$_SESSION = array();

// 7. Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 8. Finalmente, destruir la sesión en el servidor.
session_destroy();

// 9. Redireccionar
header("Location: " . $redirect_url);
exit();
?>