<?php
// backend/fix_password.php
require_once 'bd.php';

header('Content-Type: text/plain');

$email_objetivo = 'pasajero@gmail.com'; // El correo que vimos en tu captura
$nueva_password = '123456';

echo "--- INICIO DE REPARACIÓN DE CONTRASEÑA ---\n";
echo "Objetivo: $email_objetivo\n";
echo "Nueva Contraseña: $nueva_password\n\n";

// 1. Verificar si el usuario existe
$sql_check = "SELECT usuario_id FROM USUARIO WHERE email = ?";
$stmt = $conn->prepare($sql_check);
if (!$stmt) die("Error SQL Check: " . $conn->error);
$stmt->bind_param("s", $email_objetivo);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "❌ ERROR: No existe ningún usuario con el correo '$email_objetivo'.\n";
    echo "Por favor, verifica que esté escrito correctamente en la base de datos.";
    exit;
}
$stmt->close();

// 2. Actualizar contraseña
$hash = password_hash($nueva_password, PASSWORD_DEFAULT);
$sql_update = "UPDATE USUARIO SET password_hash = ? WHERE email = ?";
$stmt_up = $conn->prepare($sql_update);
if (!$stmt_up) die("Error SQL Update: " . $conn->error);

$stmt_up->bind_param("ss", $hash, $email_objetivo);

if ($stmt_up->execute()) {
    echo "✅ ÉXITO: Contraseña actualizada correctamente en la Base de Datos.\n\n";
    
    // 3. Verificación inmediata
    echo "--- VERIFICACIÓN ---\n";
    $sql_verify = "SELECT password_hash FROM USUARIO WHERE email = ?";
    $stmt_v = $conn->prepare($sql_verify);
    $stmt_v->bind_param("s", $email_objetivo);
    $stmt_v->execute();
    $res_v = $stmt_v->get_result();
    $row = $res_v->fetch_assoc();
    
    if (password_verify($nueva_password, $row['password_hash'])) {
        echo "✅ VERIFICADO: El sistema confirma que la contraseña '123456' coincide con el nuevo hash.\n";
        echo ">> AHORA PUEDES INICIAR SESIÓN <<";
    } else {
        echo "❌ ERROR EXTRAÑO: La verificación del hash falló inmediatamente después de actualizar.";
    }
    
} else {
    echo "❌ ERROR AL ACTUALIZAR: " . $stmt_up->error;
}

$conn->close();
?>
