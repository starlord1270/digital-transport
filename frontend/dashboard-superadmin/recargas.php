<?php
/**
 * DIGITAL TRANSPORT - CONSOLA DE CONFIRMACIÓN DE RECARGAS (SUPERADMIN)
 */
$page_title = "Consola de Recargas - SuperAdmin";
$active_page = "recargas";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || !in_array((int)($_SESSION['tipo_usuario_id'] ?? 0), [2, 5], true)) {
    header("Location: ../inicio-sesion-usuarios.php");
    exit();
}

require_once '../../backend/includes/db.php';
require_once '../../backend/includes/security.php';

include '../includes/header.php';
?>

<div class="animate-fade-in" style="max-width: 1000px; margin: 0 auto; padding: 20px;">
    <div class="card" style="padding: 32px; border-top: 5px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2><i class="fas fa-hand-holding-usd"></i> Solicitudes de Recarga Pendientes</h2>
            <button class="btn btn-secondary" onclick="cargarRecargasPendientes()"><i class="fas fa-sync-alt"></i> Actualizar</button>
        </div>

        <p style="color: var(--text-muted); margin-bottom: 24px;">Verifica la referencia bancaria/QR y confirma la acreditación de saldo en la cuenta del pasajero.</p>

        <div id="tabla-container" style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #ccc; text-align: left;">
                        <th style="padding: 12px;">ID Recarga</th>
                        <th style="padding: 12px;">Pasajero</th>
                        <th style="padding: 12px;">Documento</th>
                        <th style="padding: 12px;">Referencia</th>
                        <th style="padding: 12px;">Monto</th>
                        <th style="padding: 12px;">Fecha</th>
                        <th style="padding: 12px; text-align: center;">Acción</th>
                    </tr>
                </thead>
                <tbody id="lista-recargas-body">
                    <tr><td colspan="7" style="text-align: center; padding: 24px;">Cargando solicitudes...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function cargarRecargasPendientes() {
    const tbody = document.getElementById('lista-recargas-body');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 24px;">Cargando solicitudes...</td></tr>';

    try {
        const response = await fetch('../../backend/fetch_recargas_pendientes.php');
        const data = await response.json();

        if (!data.success || !data.recargas || data.recargas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">No hay solicitudes de recarga pendientes.</td></tr>';
            return;
        }

        tbody.innerHTML = data.recargas.map(r => `
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 12px; font-weight: 700;">#${r.u_recarga_id}</td>
                <td style="padding: 12px;">${escapeHtml(r.usuario_nombre)}</td>
                <td style="padding: 12px;">${escapeHtml(r.documento_identidad)}</td>
                <td style="padding: 12px;"><code style="background: #f4f4f4; padding: 4px 8px; border-radius: 4px;">${escapeHtml(r.referencia || 'N/A')}</code></td>
                <td style="padding: 12px; font-weight: 800; color: var(--accent);">Bs. ${parseFloat(r.monto).toFixed(2)}</td>
                <td style="padding: 12px; font-size: 0.85rem; color: var(--text-muted);">${r.fecha_recarga}</td>
                <td style="padding: 12px; text-align: center;">
                    <button class="btn btn-success" style="padding: 6px 12px; margin-right: 4px;" onclick="procesarRecarga(${r.u_recarga_id}, 'CONFIRMAR', ${r.monto})">
                        <i class="fas fa-check"></i> Aprobar
                    </button>
                    <button class="btn btn-danger" style="padding: 6px 12px;" onclick="procesarRecarga(${r.u_recarga_id}, 'RECHAZAR', ${r.monto})">
                        <i class="fas fa-times"></i> Rechazar
                    </button>
                </td>
            </tr>
        `).join('');

    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 24px; color: red;">Error de conexión con el servidor.</td></tr>';
    }
}

async function procesarRecarga(recargaId, accion, monto) {
    const accionTexto = accion === 'CONFIRMAR' ? 'confirmar y acreditar' : 'rechazar';
    if (!confirm(`¿Estás seguro de que deseas ${accionTexto} la recarga #${recargaId} por Bs. ${parseFloat(monto).toFixed(2)}?`)) return;

    const formData = new FormData();
    formData.append('recarga_id', recargaId);
    formData.append('accion', accion);

    try {
        const response = await fetch('../../backend/confirmar_recarga.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert(data.message);
            cargarRecargasPendientes();
        } else {
            alert('Error: ' + (data.error || 'No se pudo procesar la acción.'));
        }
    } catch (err) {
        alert('Error de conexión al procesar la recarga.');
    }
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', cargarRecargasPendientes);
</script>

<?php include '../includes/footer.php'; ?>
