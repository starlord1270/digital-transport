<?php
/**
 * DIGITAL TRANSPORT - CENTRO DE SOPORTE Y DISPUTAS (SUPER ADMIN)
 */
$page_title = "Centro de Soporte - Digital Transport";
$active_page = "soporte";

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
            <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Centro de Ayuda y Disputas</h1>
            <p style="color: var(--text-muted);">Resuelve problemas técnicos, disputas de cobro y quejas de los usuarios.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <div class="glass-card" style="padding: 10px 20px; border-left: 4px solid var(--danger);">
                <span style="font-size: 0.65rem; color: var(--text-muted); font-weight: 700;">PENDIENTES</span>
                <div id="count-tickets" style="font-size: 1.2rem; font-weight: 800;">0</div>
            </div>
        </div>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: rgba(0,0,0,0.02); border-bottom: 2px solid var(--bg-main);">
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase;">Ticket #</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase;">Usuario</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase;">Asunto / Mensaje</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase;">Prioridad</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase;">Estado</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase;">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-soporte">
                <!-- Dinámico -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Respuesta -->
<div id="modal-soporte" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
    <div class="card" style="max-width: 600px; margin: 80px auto; padding: 40px; position: relative;">
        <span onclick="closeModal()" style="position: absolute; right: 24px; top: 24px; cursor: pointer; font-size: 1.5rem; color: var(--text-muted);">&times;</span>
        <h2 style="margin-bottom: 8px;">Resolver Ticket</h2>
        <div id="ticket-info" style="background: var(--bg-main); padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 0.9rem;">
            <!-- Info del ticket -->
        </div>
        
        <form id="form-resolver">
            <input type="hidden" name="ticket_id" id="edit-ticket-id">
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Tu Respuesta al Usuario</label>
                <textarea name="respuesta" class="form-input" style="height: 150px;" required placeholder="Escribe la solución o respuesta oficial..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">Cerrar Ticket como Resuelto</button>
        </form>
    </div>
</div>

<script>
    async function loadTickets() {
        try {
            const res = await fetch('../../backend/superadmin/fetch_soporte.php');
            const data = await res.json();
            if(data.success) {
                document.getElementById('count-tickets').textContent = data.tickets.filter(t => t.estado != 'RESUELTO').length;
                const tbody = document.getElementById('tabla-soporte');
                tbody.innerHTML = '';
                data.tickets.forEach(t => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid var(--bg-main)';
                    
                    const prioColor = t.prioridad === 'ALTA' ? 'var(--danger)' : (t.prioridad === 'MEDIA' ? 'var(--warning)' : 'var(--accent)');
                    const estadoColor = t.estado === 'RESUELTO' ? 'var(--accent)' : (t.estado === 'EN_PROCESO' ? 'var(--warning)' : 'var(--danger)');

                    tr.innerHTML = `
                        <td style="padding: 20px; font-weight: 700; color: var(--text-muted);">#${t.ticket_id}</td>
                        <td style="padding: 20px;">
                            <div style="font-weight: 700;">${t.nombre}</div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">${t.fecha}</div>
                        </td>
                        <td style="padding: 20px;">
                            <div style="font-weight: 700; font-size: 0.9rem;">${t.asunto}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">${t.mensaje}</div>
                        </td>
                        <td style="padding: 20px;">
                            <span style="font-size: 0.7rem; font-weight: 800; color: ${prioColor};">${t.prioridad}</span>
                        </td>
                        <td style="padding: 20px;">
                            <span style="background: rgba(0,0,0,0.05); padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 700; color: ${estadoColor};">
                                ${t.estado}
                            </span>
                        </td>
                        <td style="padding: 20px;">
                            <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.75rem;" onclick="abrirResolver(${t.ticket_id}, '${t.nombre}', '${t.asunto}', '${t.mensaje.replace(/'/g, "\\'")}')">
                                <i class="fas fa-reply"></i> Responder
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        } catch(e) {}
    }

    function abrirResolver(id, nombre, asunto, mensaje) {
        document.getElementById('edit-ticket-id').value = id;
        document.getElementById('ticket-info').innerHTML = `
            <strong>Usuario:</strong> ${nombre}<br>
            <strong>Asunto:</strong> ${asunto}<br><br>
            <strong>Mensaje original:</strong><br>
            <span style="color: var(--text-muted)">${mensaje}</span>
        `;
        document.getElementById('modal-soporte').style.display = 'block';
    }

    function closeModal() { document.getElementById('modal-soporte').style.display = 'none'; }

    document.getElementById('form-resolver').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await fetch('../../backend/superadmin/resolver_ticket.php', { method: 'POST', body: formData });
        const result = await res.json();
        if(result.success) {
            alert('Ticket resuelto correctamente.');
            closeModal();
            loadTickets();
        } else { alert(result.error); }
    });

    loadTickets();
</script>

<?php include '../includes/footer.php'; ?>
