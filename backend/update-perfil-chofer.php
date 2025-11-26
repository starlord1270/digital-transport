<?php
// backend/update-perfil-chofer.php

header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];

// 1. Verificación de Sesión y Rol (Debe ser CHOFER = 3)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['tipo_usuario_id'] != 3) {
    $response['message'] = 'Acceso denegado o sesión no válida.';
    echo json_encode($response);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
require_once 'bd.php'; 

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos: ' . $conn->connect_error;
    echo json_encode($response);
    exit;
}

// 2. Recepción de Datos
$nombre_completo = $_POST['nombre_completo'] ?? '';
$email = $_POST['email'] ?? '';
$licencia = $_POST['licencia'] ?? '';

// 3. Validación de datos
if (empty($nombre_completo) || empty($email) || empty($licencia)) {
    $response['message'] = 'Todos los campos requeridos deben estar llenos.';
    $conn->close();
    echo json_encode($response);
    exit;
}

$conn->begin_transaction(); // Iniciar transacción para asegurar ambas actualizaciones

try {
    // A. Actualizar USUARIO (nombre y email)
    $sql_update_user = "
        UPDATE USUARIO 
        SET 
            nombre_completo = ?, 
            email = ?
        WHERE 
            usuario_id = ?
    ";
    $stmt_user = $conn->prepare($sql_update_user);
    $stmt_user->bind_param("ssi", $nombre_completo, $email, $usuario_id);
    $stmt_user->execute();
    $stmt_user->close();
    
    // B. Actualizar CHOFER (licencia)
    $sql_update_chofer = "
        UPDATE CHOFER 
        SET 
            licencia = ?
        WHERE 
            usuario_id = ?
    ";
    $stmt_chofer = $conn->prepare($sql_update_chofer);
    $stmt_chofer->bind_param("si", $licencia, $usuario_id);
    $stmt_chofer->execute();
    $stmt_chofer->close();

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Perfil actualizado con éxito.';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Error al actualizar el perfil: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>