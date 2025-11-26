<?php
// backend/login.php

// 1. GESTIÓN DE SESIONES
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuración inicial
if (ob_get_level() > 0) {
    ob_clean();
}
// Ocultar errores al usuario final (en producción)
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0);
error_reporting(0); 
header('Content-Type: application/json');

// --- CONFIGURACIÓN DE LA BASE DE DATOS (PDO) ---
$dbHost = 'localhost';
$dbName = 'digital-transport'; 
$dbUser = 'root'; 
$dbPass = ''; 

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    // Error de conexión a BD.
    echo json_encode(['success' => false, 'error' => 'Error de conexión a BD.']);
    exit;
}
// --- FIN CONFIGURACIÓN DE LA BASE DE DATOS ---

// Recibir datos (asume AJAX/Fetch en frontend)
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Por favor, ingrese email y contraseña.']);
    exit;
}

try {
    // Buscar usuario por email y obtener datos clave
    $sql = "SELECT usuario_id, password_hash, tipo_usuario_id, nombre_completo
            FROM USUARIO 
            WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Verificar credenciales
    if ($user && password_verify($password, $user['password_hash'])) {
        
        // --- AUTENTICACIÓN EXITOSA ---
        
        $tipo_id = (int)$user['tipo_usuario_id'];
        $redirect_url = '';
        
        // ⭐ Corregir nombres de sesión para consistencia con index.php
        $_SESSION['usuario_id'] = $user['usuario_id'];
        $_SESSION['nombre_completo'] = $user['nombre_completo']; 
        $_SESSION['tipo_usuario_id'] = $tipo_id;

        // ⚠️ IMPORTANTE: Añadir placeholder para saldo, DEBES BUSCAR EL VALOR REAL DE LA BD
        // por ejemplo: $user_balance = obtener_saldo_de_tarjeta($user['usuario_id']);
        $_SESSION['saldo'] = 0.00; 

        // 4. LÓGICA DE REDIRECCIÓN BASADA EN ROL
        switch ($tipo_id) {
            case 1: // ADMIN_MAESTRO GLOBAL
                $redirect_url = '/vistas/admin-global/dashboard.html';
                break;
            case 4: // ADMIN_LINEA
                $redirect_url = '/vistas/admin-linea/dashboard.html';
                break;
            case 3: // CHOFER
                $redirect_url = '/vistas/chofer/panel.html';
                break;
            case 2: // ESTUDIANTE (Pasajero)
            case 5: // ADULTO (Pasajero)
            case 6: // ADULTO_MAYOR (Pasajero)
                // ⭐ Establecer el mensaje de éxito para que index.php lo muestre (Flash message)
                $_SESSION['login_success_message'] = "¡Bienvenido, " . $user['nombre_completo'] . "! Has iniciado sesión correctamente.";
                
                // ⭐ Redirección para el pasajero a la raíz (index.php)
                $redirect_url = '/index.php'; 
                break;
            default:
                // Error de rol desconocido o inactivo
                $redirect_url = '/vistas/login.html?error=role_unknown';
                break;
        }

        // 5. Devolver la URL al frontend
        echo json_encode([
            'success' => true,
            'message' => 'Inicio de sesión exitoso. Redirigiendo...',
            'redirect_url' => $redirect_url,
        ]);
        
    } else {
        // Devuelve el error de credenciales
        echo json_encode(['success' => false, 'error' => 'Email o contraseña incorrectos.']);
    }

} catch (PDOException $e) {
    // Logging: registrar $e->getMessage() en un archivo
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
}
?>