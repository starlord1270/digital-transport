<?php
// fetch_tickets.php

// 🛑 CONFIGURACIÓN DE ERRORES: Muestra todos los errores de PHP directamente 🛑
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Limpieza de buffer y sesión
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Limpia cualquier salida accidental (espacios, BOM) antes de las cabeceras
ob_end_clean(); 


// 1. GESTIÓN DE SESIONES Y SEGURIDAD
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); // Unauthorized
    header('Content-Type: application/json');
    echo json_encode(["error" => "Usuario no autenticado. Inicie sesión para ver los boletos."]);
    exit();
}

// Establece el encabezado para que el navegador sepa que la respuesta es JSON
header('Content-Type: application/json');

// --- 2. CONFIGURACIÓN DE LA BASE DE DATOS ---
$dbHost = 'localhost';
$dbName = 'digital-transport'; 
$dbUser = 'root'; 
$dbPass = ''; // VERIFICA si tienes contraseña en tu entorno (XAMPP/LAMPP)

$current_user_id = $_SESSION['usuario_id']; 

try {
    // 3. CONEXIÓN USANDO PDO
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- 4. CONSULTA SQL ---
    // NOTA: Esta consulta asume que las columnas 'saldo_actual' y 'codigo_nfc' EXISTEN ahora en TARJETA.
    $sql = "
        SELECT
            T.tarjeta_id,
            T.saldo_actual,
            T.estado,
            T.codigo_nfc
        FROM 
            TARJETA T
        WHERE 
            T.usuario_id = :user_id 
            AND T.estado != 'Inactivo' 
            AND T.estado != 'BLOQUEADA'
            AND T.estado != 'PERDIDA'
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $raw_tickets = $stmt->fetchAll();

    // --- 5. FORMATO DE DATOS ---
    $formatted_tickets = [];
    
    foreach ($raw_tickets as $ticket) {
        $saldo = (float)$ticket['saldo_actual'];
        $totalUses = 999; 
        $usedUses = 0;   
        
        // Define el estado para el frontend: 'Usado' si el saldo es cero o menor
        $status = ($saldo <= 0) ? 'Usado' : 'Activo'; 

        $tipo_pase = 'Pase Saldo (Actual: ' . number_format($saldo, 2) . ' Bs)';
        $linea = 'Tarjeta de Saldo General';
        // Usa una fecha futura para simular que no expiran por tiempo
        $expiration_date = '2099-12-31T23:59:00'; 

        $formatted_tickets[] = [
            'id' => 'TRJ-' . $ticket['tarjeta_id'],
            'line' => $linea,
            'type' => $tipo_pase, 
            'expires' => $expiration_date,
            'totalUses' => $totalUses,
            'usedUses' => $usedUses,
            'status' => $status,
            // Genera el QR combinando ID de tarjeta y código NFC/QR de la BD
            'qrData' => 'DT-TRJ-' . $ticket['tarjeta_id'] . '-' . $ticket['codigo_nfc'] 
        ];
    }
    
    // 6. DEVOLVER JSON
    echo json_encode($formatted_tickets);

} catch (PDOException $e) {
    // Manejo de errores de conexión/consulta SQL
    http_response_code(500); 
    echo json_encode([
        "error" => "Error CRÍTICO de Base de Datos: " . $e->getMessage(),
        "hint" => "Verifique que MySQL/MariaDB esté activo y las credenciales de BD sean correctas. Revise si las columnas 'saldo_actual' y 'codigo_nfc' existen en la tabla TARJETA."
    ]);
    exit();
} catch (Exception $e) {
    // Para errores de lógica PHP que no son de BD
    http_response_code(500);
    echo json_encode(["error" => "Error de Procesamiento general (PHP): " . $e->getMessage()]);
    exit();
}
?>