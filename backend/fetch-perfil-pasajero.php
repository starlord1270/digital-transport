<?php
// backend/fetch-perfil-pasajero.php
// 🛑 LÍNEAS TEMPORALES PARA DIAGNÓSTICO
error_reporting(E_ALL);
ini_set('display_errors', 1);
// 🛑 FIN LÍNEAS TEMPORALES
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];

// 1. Incluir la conexión MySQLi (¡Usando el nombre correcto: bd.php!)
require_once 'bd.php'; 

// Si la conexión falla, el script se detiene en bd.php, por lo que no necesitamos un if aquí.

// 2. Verificación de Sesión y Rol (Pasajero)
// Basado en tu lógica de login, los tipos 1, 2, 5, 6 son pasajeros.
$allowed_pasajero_types = [1, 2, 5, 6]; 
$tipo_usuario_id = $_SESSION['tipo_usuario_id'] ?? 0;

if (!isset($_SESSION['usuario_id']) || !in_array($tipo_usuario_id, $allowed_pasajero_types)) {
    // Si la sesión no está establecida o el rol no es de pasajero
    $response['message'] = 'Acceso denegado o sesión no válida.';
    // ⚠️ Importante: Si esto ocurre, el frontend debe redirigir al login.
    echo json_encode($response);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// 3. Consulta para obtener datos de USUARIO, SALDO y CÓDIGO de Tarjeta
$sql = "
    SELECT
        U.nombre_completo,
        U.documento_identidad,
        U.email,
        U.saldo,
        U.fecha_registro,
        T.codigo_seguridad
    FROM
        USUARIO U
    LEFT JOIN
        TARJETA T ON U.usuario_id = T.usuario_id
    WHERE
        U.usuario_id = ?
";

// 🛑 USANDO MySQLi con $conn (tu objeto de conexión)
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $response['message'] = 'Error de preparación de consulta: ' . $conn->error;
    $conn->close();
    echo json_encode($response);
    exit;
}

$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();
$conn->close(); // Cerrar la conexión

if ($data) {
    // 4. Determinar Tipo de Pasajero para la etiqueta de la vista
    $tipo_pasajero = "Estándar";
    if ($tipo_usuario_id == 2) { 
        $tipo_pasajero = "Estudiante"; 
    } elseif ($tipo_usuario_id == 6) { 
        $tipo_pasajero = "Adulto Mayor"; 
    }
    
    $fecha_registro = new DateTime($data['fecha_registro']);
    $miembro_desde = $fecha_registro->format('F Y');

    // 5. Preparar respuesta
    $response['success'] = true;
    $response['data'] = [
        'nombre_completo' => $data['nombre_completo'],
        'documento_identidad' => $data['documento_identidad'],
        'email' => $data['email'],
        'saldo' => number_format($data['saldo'], 2, '.', ''),
        'miembro_desde' => $miembro_desde,
        'tipo_pasajero' => $tipo_pasajero,
        'codigo_qr' => $data['codigo_seguridad'] ?? 'NO_TARJETA'
    ];
} else {
    $response['message'] = 'No se encontraron datos de pasajero para este usuario.';
}

echo json_encode($response);
?>