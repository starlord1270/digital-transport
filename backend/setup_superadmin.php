<?php
/**
 * DIGITAL TRANSPORT - SETUP SUPERADMIN CLI SCRIPT
 * 
 * Script CLI para registrar o actualizar la cuenta de SuperAdmin (Rol 5).
 * Uso: php backend/setup_superadmin.php
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ser ejecutado desde la línea de comandos (CLI).\n");
}

require_once __DIR__ . '/includes/db.php';

$email = getenv('SUPERADMIN_EMAIL') ?: 'superadmin@digitaltransport.bo';
$password = getenv('SUPERADMIN_PASSWORD') ?: 'AdminSecure2026!';
$nombre = getenv('SUPERADMIN_NAME') ?: 'Super Admin Sistema';
$ci = getenv('SUPERADMIN_CI') ?: '1000000';

echo "=========================================\n";
echo "   CONFIGURACIÓN DE SUPERADMINISTRADOR   \n";
echo "=========================================\n";

try {
    // 1. Asegurar que exista el Rol 5 (SUPER_ADMIN)
    $stmtRole = $pdo->prepare("INSERT INTO TIPO_USUARIO (tipo_usuario_id, descripcion) VALUES (5, 'SUPER_ADMIN') ON DUPLICATE KEY UPDATE descripcion = 'SUPER_ADMIN'");
    $stmtRole->execute();

    // 2. Verificar si el usuario ya existe por email o CI
    $stmtCheck = $pdo->prepare("SELECT usuario_id FROM USUARIO WHERE email = :email OR documento_identidad = :ci");
    $stmtCheck->execute([':email' => $email, ':ci' => $ci]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($existing) {
        $uid = (int)$existing['usuario_id'];
        $stmtUpdate = $pdo->prepare("UPDATE USUARIO SET tipo_usuario_id = 5, nombre_completo = :nombre, password_hash = :hash WHERE usuario_id = :uid");
        $stmtUpdate->execute([':nombre' => $nombre, ':hash' => $hash, ':uid' => $uid]);
        echo "✅ Cuenta SuperAdmin actualizada exitosamente (ID: $uid).\n";
    } else {
        $stmtInsert = $pdo->prepare("INSERT INTO USUARIO (tipo_usuario_id, documento_identidad, nombre_completo, email, password_hash, saldo, fecha_registro) VALUES (5, :ci, :nombre, :email, :hash, 0.00, NOW())");
        $stmtInsert->execute([
            ':ci' => $ci,
            ':nombre' => $nombre,
            ':email' => $email,
            ':hash' => $hash
        ]);
        $newId = $pdo->lastInsertId();
        echo "✅ Cuenta SuperAdmin creada exitosamente (ID: $newId).\n";
    }

    echo "-----------------------------------------\n";
    echo " Email: $email\n";
    echo " CI: $ci\n";
    echo " Rol: 5 (SUPER_ADMIN)\n";
    echo "=========================================\n";

} catch (PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage() . "\n";
    exit(1);
}
?>
