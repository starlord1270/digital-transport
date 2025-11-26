<?php
// backend/update-perfil-admin.php

header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];

// 1. Verificación de Sesión y Rol
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['tipo_usuario_id'] != 4) {
    $response['message'] = 'Acceso denegado o sesión no válida.';
    echo json_encode($response);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
require_once 'bd.php'; // Incluir la conexión a la base de datos

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos: ' . $conn->connect_error;
    echo json_encode($response);
    exit;
}

// 2. Recepción de Datos (POST)
$nombre_completo = $_POST['nombre_completo'] ?? '';
$email = $_POST['email'] ?? '';
$documento_identidad = $_POST['documento_identidad'] ?? '';

// 3. Validación de datos (básica)
if (empty($nombre_completo) || empty($email) || empty($documento_identidad)) {
    $response['message'] = 'Todos los campos requeridos deben estar llenos.';
    $conn->close();
    echo json_encode($response);
    exit;
}

// 4. Preparación de la Consulta de Actualización
$sql_update = "
    UPDATE USUARIO 
    SET 
        nombre_completo = ?, 
        email = ?, 
        documento_identidad = ?
    WHERE 
        usuario_id = ?
";

$stmt_update = $conn->prepare($sql_update);

if ($stmt_update === false) {
    $response['message'] = 'Error al preparar la consulta: ' . $conn->error;
    $conn->close();
    echo json_encode($response);
    exit;
}

// La tabla USUARIO no tiene campo de teléfono, por lo que este campo se ignora por ahora.
// Se asume que 'documento_identidad' no incluye ' LP', ya que eso se agrega en el frontend.
$ci_limpia = str_replace(' LP', '', $documento_identidad);

$stmt_update->bind_param("sssi", $nombre_completo, $email, $ci_limpia, $usuario_id);

if ($stmt_update->execute()) {
    if ($stmt_update->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Perfil actualizado con éxito.';
    } else {
        $response['message'] = 'No se realizaron cambios (datos iguales o usuario no encontrado).';
    }
} else {
    // Error al ejecutar (ej. email duplicado)
    $response['message'] = 'Error al actualizar el perfil: ' . $stmt_update->error;
}

$stmt_update->close();
$conn->close();
echo json_encode($response);
?>