<?php
// backend/update-perfil-admin.php

header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

$response = ['success' => false, 'message' => ''];

// 1. Verificación de Sesión y Rol (ADMIN_LINEA=4 o SUPER_ADMIN=5)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !in_array((int)($_SESSION['tipo_usuario_id'] ?? 0), [4, 5], true)) {
    $response['message'] = 'Acceso denegado o sesión no válida.';
    echo json_encode($response);
    exit;
}

// Check CSRF token for POST mutation
if (!verifyCsrfToken($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o ausente.']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];

// 2. Recepción de Datos (POST)
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$email = trim($_POST['email'] ?? '');
$documento_identidad = trim($_POST['documento_identidad'] ?? '');

// 3. Validación de datos
if (empty($nombre_completo) || empty($email) || empty($documento_identidad)) {
    $response['message'] = 'Todos los campos requeridos deben estar llenos.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'El formato del correo electrónico no es válido.';
    echo json_encode($response);
    exit;
}

$ci_limpia = str_replace(' LP', '', $documento_identidad);

try {
    // 4. Preparación y Ejecución con PDO
    $sql_update = "
        UPDATE USUARIO 
        SET 
            nombre_completo = :nombre, 
            email = :email, 
            documento_identidad = :ci
        WHERE 
            usuario_id = :uid
    ";

    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([
        ':nombre' => $nombre_completo,
        ':email' => $email,
        ':ci' => $ci_limpia,
        ':uid' => $usuario_id
    ]);

    if ($stmt_update->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Perfil actualizado con éxito.';
    } else {
        $response['message'] = 'No se realizaron cambios (datos iguales o usuario no encontrado).';
    }
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Error al actualizar el perfil en la base de datos.';
}

echo json_encode($response);
?>