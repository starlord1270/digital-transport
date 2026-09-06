<?php
/**
 * DIGITAL TRANSPORT - HELPER FUNCTIONS
 */

/**
 * Registra una acción en la tabla de auditoría
 */
function registrarAuditoria($pdo, $usuario_id, $accion, $detalles) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $pdo->prepare("INSERT INTO AUDITORIA (usuario_id, accion, detalles, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $accion, $detalles, $ip]);
        return true;
    } catch (Exception $e) {
        error_log("Error en auditoría: " . $e->getMessage());
        return false;
    }
}
?>
