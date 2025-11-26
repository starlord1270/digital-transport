<?php
// backend/fetch-perfil-chofer.php

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
require_once 'bd.php'; // Incluir la conexión a la base de datos

if ($conn->connect_error) {
    $response['message'] = 'Error de conexión a la base de datos: ' . $conn->connect_error;
    echo json_encode($response);
    exit;
}

// 2. Consulta para obtener todos los datos del chofer, su vehículo y línea
$sql = "
    SELECT
        U.nombre_completo,
        U.documento_identidad,
        U.email,
        U.fecha_registro,
        C.licencia,
        C.vehiculo_placa,
        L.nombre AS linea_administrada,
        V.modelo AS vehiculo_modelo,
        V.capacidad
    FROM
        USUARIO U
    INNER JOIN
        CHOFER C ON U.usuario_id = C.usuario_id
    INNER JOIN
        LINEA L ON C.linea_id = L.linea_id
    INNER JOIN
        VEHICULO V ON C.vehiculo_placa = V.placa
    WHERE
        U.usuario_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();
$conn->close();

if ($data) {
    // Formatear la fecha de registro
    $fecha_registro = new DateTime($data['fecha_registro']);
    $miembro_desde = $fecha_registro->format('F Y'); // Ejemplo: Marzo 2023

    // Simular un rating (debería venir de una tabla de calificaciones)
    $rating = '4.8 (3420 viajes)';
    
    // Preparar la respuesta
    $response['success'] = true;
    $response['data'] = [
        'nombre_completo' => $data['nombre_completo'],
        'documento_identidad' => $data['documento_identidad'] . ' LP', // Añadir el sufijo de región
        'email' => $data['email'],
        'licencia' => $data['licencia'],
        'miembro_desde' => $miembro_desde,
        'linea_name' => $data['linea_administrada'],
        'placa' => $data['vehiculo_placa'],
        'modelo' => $data['vehiculo_modelo'],
        'capacidad' => $data['capacidad'],
        'rating' => $rating
    ];
} else {
    $response['message'] = 'No se encontraron datos de chofer para este usuario.';
}

echo json_encode($response);
?>