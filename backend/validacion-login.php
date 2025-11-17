<?php
// backend/validacion-login.php
session_start(); // Iniciar la sesión de PHP

// 1. GESTIÓN DE ERRORES Y CONFIGURACIÓN INICIAL
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

// Función de respuesta centralizada
function sendResponse($success, $message, $redirect = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'redirect' => $redirect]);
    exit;
}

// --- CONFIGURACIÓN DE LA BASE DE DATOS (PDO) ---
$dbHost = 'localhost';
$dbName = 'digital-transport'; 
$dbUser = 'root'; 
$dbPass = ''; 

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    sendResponse(false, 'Error de conexión a BD. Revise la configuración de conexión.');
}
// --- FIN CONFIGURACIÓN DE LA BASE DE DATOS ---

// 2. RECEPCIÓN Y VALIDACIÓN DE DATOS
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    sendResponse(false, 'Debe ingresar email y contraseña.');
}

// 3. CONSULTAR USUARIO POR EMAIL
try {
    // 🔑 CORRECCIÓN FINAL: Usar T.descripcion para obtener el nombre del rol.
    $sql = "SELECT 
                U.usuario_id, U.password_hash, U.tipo_usuario_id, T.descripcion AS rol_nombre 
            FROM USUARIO U
            JOIN TIPO_USUARIO T ON U.tipo_usuario_id = T.tipo_usuario_id
            WHERE U.email = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse(false, 'Email o contraseña incorrectos.');
    }

    // 4. VERIFICAR CONTRASEÑA
    if (password_verify($password, $user['password_hash'])) {
        
        // --- INICIO DE SESIÓN EXITOSO ---
        
        // 5. ASIGNAR VARIABLES DE SESIÓN
        $_SESSION['user_id'] = $user['usuario_id'];
        $_SESSION['tipo_usuario_id'] = $user['tipo_usuario_id'];
        $_SESSION['rol_nombre'] = $user['rol_nombre']; // Contiene el valor de 'descripcion'
        
        // 6. DETERMINAR LA RUTA DE REDIRECCIÓN (Relativa a login.php)
        $redirect_path = '';
        
        switch ($user['tipo_usuario_id']) {
            case 4: // Administrador de Línea
                $redirect_path = '../admin/dashboard.php'; 
                break;
            case 3: // Chofer
                $redirect_path = '../chofer/cobro.php'; 
                break;
            case 1: // Cliente
                $redirect_path = '../cliente/perfil.php'; 
                break;
            case 2: // Punto de Recarga
                $redirect_path = '../punto-recarga/dashboard.php';
                break;
            default:
                $redirect_path = '../error_rol.php';
        }
        
        // 7. ENVIAR RESPUESTA DE ÉXITO CON LA RUTA DE REDIRECCIÓN
        sendResponse(true, "Bienvenido, " . $user['rol_nombre'] . ". Ingresando al sistema...", $redirect_path);

    } else {
        // Contraseña incorrecta
        sendResponse(false, 'Email o contraseña incorrectos.');
    }

} catch (PDOException $e) {
    // Mantener la depuración hasta confirmar que funciona, luego se puede cambiar a un mensaje genérico.
    sendResponse(false, 'Error interno del servidor (BD): ' . $e->getMessage()); 
}
?>