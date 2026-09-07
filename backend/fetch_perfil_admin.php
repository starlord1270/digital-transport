<?php
// backend/fetch_perfil_admin.php

header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';

$response = [
    'success' => false,
    'message' => 'Error desconocido en el servidor.',
    'data' => null
];

// 1. Validación de Sesión y Rol
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $response['message'] = 'Acceso denegado. Sesión no iniciada.';
    echo json_encode($response);
    exit;
}
if (!in_array((int)$_SESSION['tipo_usuario_id'], [4, 5], true)) {
    $response['message'] = 'No tienes permisos de Administrador de Línea.';
    echo json_encode($response);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];

try {
    // 2. Consulta Principal: Datos del Administrador y Línea
    $sql_datos_admin = "
        SELECT 
            U.nombre_completo, 
            U.documento_identidad, 
            U.email, 
            U.fecha_registro,
            AL.linea_id, 
            L.nombre AS nombre_linea
        FROM 
            USUARIO U
        JOIN 
            ADMIN_LINEA AL ON U.usuario_id = AL.usuario_id
        JOIN 
            LINEA L ON AL.linea_id = L.linea_id
        WHERE 
            U.usuario_id = :uid
    ";

    $stmt_admin = $pdo->prepare($sql_datos_admin);
    $stmt_admin->execute([':uid' => $usuario_id]);
    $datos_admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);

    if (!$datos_admin) {
        $response['message'] = 'Error: Administrador encontrado pero sin asignación de línea.';
        echo json_encode($response);
        exit;
    }

    $linea_id = (int)$datos_admin['linea_id'];

    // 3. Consulta de Conteo: Choferes por Línea
    $stmt_choferes = $pdo->prepare("SELECT COUNT(chofer_id) AS total_choferes FROM CHOFER WHERE linea_id = :lid");
    $stmt_choferes->execute([':lid' => $linea_id]);
    $conteo_choferes = (int)$stmt_choferes->fetchColumn();

    // 4. Consulta de Conteo: Vehículos por Línea
    $stmt_vehiculos = $pdo->prepare("SELECT COUNT(placa) AS total_vehiculos FROM VEHICULO WHERE linea_id = :lid AND placa != 'PENDIENTE'");
    $stmt_vehiculos->execute([':lid' => $linea_id]);
    $conteo_vehiculos = (int)$stmt_vehiculos->fetchColumn();

    // 5. Formato Final y Respuesta Exitosa
    $fecha_registro = DateTime::createFromFormat('Y-m-d H:i:s', $datos_admin['fecha_registro']);
    $monthNames = [
        'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril',
        'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto',
        'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
    ];
    $fecha_formateada = $fecha_registro ? strtr($fecha_registro->format('F Y'), $monthNames) : 'Fecha desconocida';

    $response['success'] = true;
    $response['message'] = 'Datos del perfil cargados correctamente.';
    $response['data'] = [
        'nombre_completo' => $datos_admin['nombre_completo'],
        'documento_identidad' => $datos_admin['documento_identidad'] . ' LP', 
        'email' => $datos_admin['email'],
        'telefono' => '+591 76543210',
        'linea_administrada' => $datos_admin['nombre_linea'],
        'miembro_desde' => $fecha_formateada,
        'total_choferes' => $conteo_choferes,
        'total_vehiculos' => $conteo_vehiculos
    ];

    echo json_encode($response);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}
?>