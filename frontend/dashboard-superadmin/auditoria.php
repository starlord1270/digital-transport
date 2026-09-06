<?php
/**
 * DIGITAL TRANSPORT - AUDITORÍA DE ACCIONES (SUPER ADMIN)
 */
$page_title = "Registro de Actividad - Digital Transport";
$active_page = "auditoria";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad: Solo Super Admin (5)
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 5) {
    header("Location: ../inicio-sesion-usuarios.php");
    exit();
}

require_once '../../backend/includes/db.php';
include '../includes/header.php';
?>

<div class="animate-fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
        <div>
            <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Registro de Auditoría</h1>
            <p style="color: var(--text-muted);">Historial detallado de todas las acciones administrativas realizadas en el sistema.</p>
        </div>
        <button class="btn btn-secondary" onclick="loadAuditoria()">
            <i class="fas fa-sync-alt"></i> Actualizar Registro
        </button>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: rgba(0,0,0,0.02); border-bottom: 2px solid var(--bg-main);">
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Fecha / Hora</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Administrador</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Acción</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Detalles</th>
                </tr>
            </thead>
            <tbody id="tabla-auditoria">
                <!-- Cargando dinámicamente -->
            </tbody>
        </table>
    </div>
</div>

<script>
    async function loadAuditoria() {
        try {
            const response = await fetch('../../backend/superadmin/fetch_auditoria.php');
            const data = await response.json();
            if(data.success) {
                const tbody = document.getElementById('tabla-auditoria');
                tbody.innerHTML = '';
                if(data.logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="padding: 40px; text-align: center; color: var(--text-muted);">No hay acciones registradas aún.</td></tr>';
                    return;
                }
                data.logs.forEach(l => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid var(--bg-main)';
                    tr.innerHTML = `
                        <td style="padding: 20px;">
                            <div style="font-weight: 700; font-size: 0.9rem;">${l.fecha}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">${l.hora}</div>
                        </td>
                        <td style="padding: 20px;">
                            <div style="font-weight: 700;">${l.admin}</div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">${l.email}</div>
                        </td>
                        <td style="padding: 20px;">
                            <span style="background: rgba(30, 136, 229, 0.1); color: var(--secondary); padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">
                                ${l.accion}
                            </span>
                        </td>
                        <td style="padding: 20px; font-size: 0.85rem; color: var(--text-muted); max-width: 400px;">
                            ${l.detalles}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        } catch (e) { console.error(e); }
    }

    loadAuditoria();
</script>

<?php include '../includes/footer.php'; ?>
