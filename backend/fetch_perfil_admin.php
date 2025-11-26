<?php
// backend/fetch-perfil-admin.php

header('Content-Type: application/json');
session_start();

// Inicialización de la respuesta con un error genérico (mejorado)
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
// El ID 4 corresponde a ADMIN_LINEA (Ver tabla TIPO_USUARIO)
if ($_SESSION['tipo_usuario_id'] != 4) {
    $response['message'] = 'No tienes permisos de Administrador de Línea.';
    echo json_encode($response);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// 2. Conexión a la base de datos
// *Asegúrate que 'bd.php' esté en el mismo directorio (backend/).
require_once 'bd.php'; 

if ($conn->connect_error) {
    // Mensaje de error más detallado de conexión
    $response['message'] = 'Error grave de conexión a la base de datos. Código: ' . $conn->connect_errno;
    echo json_encode($response);
    exit;
}

// 3. Consulta Principal: Datos del Administrador y Línea
// Une USUARIO, ADMIN_LINEA y LINEA
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
        U.usuario_id = ?
";

$stmt_admin = $conn->prepare($sql_datos_admin);

// ⭐ DEBUG: Captura errores de SQL al preparar
if ($stmt_admin === false) {
    $response['message'] = 'Error al preparar la consulta de datos del administrador: ' . $conn->error;
    $conn->close();
    echo json_encode($response);
    exit;
}

$stmt_admin->bind_param("i", $usuario_id);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$datos_admin = $result_admin->fetch_assoc();
$stmt_admin->close();

if (!$datos_admin) {
    // Esto ocurre si el usuario está en la sesión, pero no existe en la tabla ADMIN_LINEA.
    $response['message'] = 'Error: Administrador encontrado pero sin asignación de línea. ID de Sesión: ' . $usuario_id;
    $conn->close();
    echo json_encode($response);
    exit;
}

$linea_id = $datos_admin['linea_id'];

// 4. Consulta de Conteo: Choferes por Línea
$sql_choferes = "
    SELECT 
        COUNT(C.chofer_id) AS total_choferes
    FROM 
        CHOFER C
    WHERE 
        C.linea_id = ?
";
$stmt_choferes = $conn->prepare($sql_choferes);
if ($stmt_choferes === false) {
    $response['message'] = 'Error al preparar la consulta de choferes: ' . $conn->error;
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_choferes->bind_param("i", $linea_id);
$stmt_choferes->execute();
$result_choferes = $stmt_choferes->get_result();
$conteo_choferes = $result_choferes->fetch_assoc()['total_choferes'];
$stmt_choferes->close();


// 5. Consulta de Conteo: Vehículos por Línea
$sql_vehiculos = "
    SELECT 
        COUNT(V.placa) AS total_vehiculos
    FROM 
        VEHICULO V
    WHERE 
        V.linea_id = ? AND V.placa != 'PENDIENTE'
";
$stmt_vehiculos = $conn->prepare($sql_vehiculos);
if ($stmt_vehiculos === false) {
    $response['message'] = 'Error al preparar la consulta de vehículos: ' . $conn->error;
    $conn->close();
    echo json_encode($response);
    exit;
}
$stmt_vehiculos->bind_param("i", $linea_id);
$stmt_vehiculos->execute();
$result_vehiculos = $stmt_vehiculos->get_result();
$conteo_vehiculos = $result_vehiculos->fetch_assoc()['total_vehiculos'];
$stmt_vehiculos->close();


// 6. Formato Final y Respuesta Exitosa
// Usamos DateTime para formatear la fecha a 'Mes Año'
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
    'telefono' => '+591 76543210', // Valor estático para simular el diseño
    'linea_administrada' => $datos_admin['nombre_linea'],
    'miembro_desde' => $fecha_formateada,
    'total_choferes' => $conteo_choferes,
    'total_vehiculos' => $conteo_vehiculos
];

$conn->close();
echo json_encode($response);
?>