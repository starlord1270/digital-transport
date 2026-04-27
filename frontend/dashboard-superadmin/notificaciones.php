<?php
/**
 * DIGITAL TRANSPORT - COMUNICACIÓN GLOBAL (SUPER ADMIN)
 */
$page_title = "Notificaciones Globales - Digital Transport";
$active_page = "notificaciones";

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
            <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Comunicación Global</h1>
            <p style="color: var(--text-muted);">Envía anuncios y alertas masivas a todos los usuarios del sistema.</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 40px;">
        <!-- Formulario de Envío -->
        <div class="card" style="padding: 32px;">
            <h3 style="margin-bottom: 24px;">Redactar Anuncio</h3>
            <form id="form-notif">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Título del Mensaje</label>
                    <input type="text" name="titulo" class="form-input" required placeholder="Ej: Mantenimiento del Sistema">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Destinatarios</label>
                    <select name="tipo_objetivo" class="form-input">
                        <option value="TODOS">Todos los Usuarios</option>
                        <option value="PASAJEROS">Solo Pasajeros</option>
                        <option value="CHOFERES">Solo Choferes</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label">Contenido del Mensaje</label>
                    <textarea name="mensaje" class="form-input" style="height: 120px; padding: 12px;" required placeholder="Escribe aquí el anuncio..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">
                    <i class="fas fa-paper-plane"></i> Emitir Comunicado
                </button>
            </form>
        </div>

        <!-- Historial de Envíos -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: 24px; border-bottom: 1px solid var(--bg-main);">
                <h3 style="margin: 0;">Anuncios Recientes</h3>
            </div>
            <div id="historial-notif" style="max-height: 500px; overflow-y: auto; padding: 24px;">
                <!-- Dinámico -->
            </div>
        </div>
    </div>
</div>

<script>
    async function loadHistorial() {
        try {
            const res = await fetch('../../backend/superadmin/fetch_notificaciones_globales.php');
            const data = await res.json();
            if(data.success) {
                const container = document.getElementById('historial-notif');
                container.innerHTML = '';
                data.notificaciones.forEach(n => {
                    const item = document.createElement('div');
                    item.className = 'glass-card';
                    item.style.padding = '16px';
                    item.style.marginBottom = '16px';
                    item.style.borderLeft = `4px solid ${n.tipo_objetivo === 'CHOFERES' ? 'var(--secondary)' : (n.tipo_objetivo === 'PASAJEROS' ? 'var(--accent)' : 'var(--primary)')}`;
                    item.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted);">${n.tipo_objetivo}</span>
                            <span style="font-size: 0.65rem; color: var(--text-muted);">${n.fecha}</span>
                        </div>
                        <h4 style="margin-bottom: 4px;">${n.titulo}</h4>
                        <p style="font-size: 0.85rem; color: var(--text-muted);">${n.mensaje}</p>
                    `;
                    container.appendChild(item);
                });
            }
        } catch(e) {}
    }

    document.getElementById('form-notif').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await fetch('../../backend/superadmin/enviar_notificacion_global.php', { method: 'POST', body: formData });
        const result = await res.json();
        if(result.success) {
            alert('¡Comunicado emitido con éxito!');
            e.target.reset();
            loadHistorial();
        } else { alert(result.error); }
    });

    loadHistorial();
</script>

<?php include '../includes/footer.php'; ?>
