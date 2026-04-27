<?php
/**
 * COMPONENTE: VISUALIZADOR DE NOTIFICACIONES GLOBALES
 */
require_once __DIR__ . '/../../backend/includes/db.php';

function mostrarNotificacionesGlobales($pdo, $tipo_usuario) {
    // Definir el filtro según el rol del usuario logueado
    $objetivo = 'TODOS';
    if ($tipo_usuario == 1) $objetivo = 'PASAJEROS';
    if ($tipo_usuario == 3) $objetivo = 'CHOFERES';

    try {
        $stmt = $pdo->prepare("
            SELECT titulo, mensaje, DATE_FORMAT(fecha_creacion, '%d %b, %H:%i') as fecha 
            FROM NOTIFICACION_GLOBAL 
            WHERE tipo_objetivo = 'TODOS' OR tipo_objetivo = ? 
            ORDER BY fecha_creacion DESC 
            LIMIT 1
        ");
        $stmt->execute([$objetivo]);
        $notif = $stmt->fetch();

        if ($notif) {
            ?>
            <div class="glass-card animate-slide-up" style="padding: 16px 24px; border-left: 5px solid var(--secondary); margin-bottom: 30px; background: rgba(30, 136, 229, 0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase;">Comunicado Oficial</span>
                    <span style="font-size: 0.65rem; color: var(--text-muted);"><?php echo $notif['fecha']; ?></span>
                </div>
                <h4 style="margin: 0 0 4px 0; color: var(--text-main);"><?php echo $notif['titulo']; ?></h4>
                <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);"><?php echo $notif['mensaje']; ?></p>
            </div>
            <?php
        }
    } catch (Exception $e) {}
}
?>
