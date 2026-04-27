<?php
/**
 * DIGITAL TRANSPORT - GESTIÓN DE CHOFERES (PREMIUM)
 */
$page_title = "Gestión de Choferes - Digital Transport";
$active_page = "choferes";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de Seguridad y Rol
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 4 || !isset($_SESSION['linea_id'])) {
    header("Location: ../inicio-sesion-usuarios.php?error=acceso_denegado");
    exit;
}

$linea_id_sesion = $_SESSION['linea_id'];

include '../includes/header.php';
?>

<div class="animate-fade-in">
    <div style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Gestión de Choferes</h1>
            <p style="color: var(--text-muted);">Administra y valida el personal de tu línea de transporte.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <div class="glass-card" style="padding: 10px 20px; border-left: 4px solid var(--warning);">
                <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">POR VALIDAR</span>
                <div id="count-pending" style="font-size: 1.2rem; font-weight: 800;">0</div>
            </div>
            <div class="glass-card" style="padding: 10px 20px; border-left: 4px solid var(--accent);">
                <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">TOTAL</span>
                <div id="count-total" style="font-size: 1.2rem; font-weight: 800;">0</div>
            </div>
        </div>
    </div>

    <!-- Tabla de Choferes -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 24px; border-bottom: 1px solid var(--bg-main); background: rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.1rem;"><i class="fas fa-list-ul"></i> Lista de Personal</h3>
            <div style="display: flex; gap: 8px;">
                <span class="status-tag status-activo" style="font-size: 0.6rem;">ACTIVO</span>
                <span class="status-tag status-inactivo" style="font-size: 0.6rem;">INACTIVO</span>
                <span class="status-tag status-pending" style="background: var(--warning); font-size: 0.6rem;">PENDIENTE</span>
            </div>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: var(--bg-main); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">
                        <th style="padding: 16px 24px;">Nombre y Contacto</th>
                        <th style="padding: 16px 24px;">Vehículo / Licencia</th>
                        <th style="padding: 16px 24px;">Estado</th>
                        <th style="padding: 16px 24px;">Actividad Hoy</th>
                        <th style="padding: 16px 24px; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="choferes-table-body">
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);">
                            <i class="fas fa-spinner fa-spin"></i> Cargando personal...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .status-tag {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        color: white;
    }
    .status-activo { background: var(--accent); }
    .status-inactivo { background: var(--text-muted); }
    .status-licencia { background: var(--secondary); }
    .status-pending { background: var(--warning); color: #000; }
    .status-rechazado { background: var(--danger); }
    
    .action-btn {
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: white;
        cursor: pointer;
        transition: var(--transition);
        color: var(--text-main);
    }
    .action-btn:hover {
        background: var(--bg-main);
        color: var(--secondary);
        border-color: var(--secondary);
    }
    .btn-approve {
        background: var(--accent);
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 700;
        font-size: 0.75rem;
        cursor: pointer;
    }
</style>

<script>
    const LINEA_ID = <?php echo $linea_id_sesion; ?>;
    const API_CHOFERES = '../../backend/fetch_choferes_data.php';

    async function loadChoferes() {
        try {
            const response = await fetch(`${API_CHOFERES}?linea_id=${LINEA_ID}`);
            const result = await response.json();
            if (result.success) {
                renderChoferes(result.choferes);
            }
        } catch (err) {
            console.error(err);
        }
    }

    function renderChoferes(choferes) {
        const body = document.getElementById('choferes-table-body');
        body.innerHTML = '';
        
        let pending = 0;
        document.getElementById('count-total').textContent = choferes.length;

        choferes.forEach(c => {
            if (c.estado_servicio === 'PENDIENTE') pending++;
            
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--bg-main)';
            tr.style.transition = 'var(--transition)';
            
            let statusClass = 'status-' + (c.estado_servicio || 'inactivo').toLowerCase();
            
            tr.innerHTML = `
                <td style="padding: 20px 24px;">
                    <div style="font-weight: 700;">${c.nombre_completo}</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">${c.email}</div>
                </td>
                <td style="padding: 20px 24px;">
                    <div style="font-family: monospace; font-weight: 700; color: var(--secondary);">${c.vehiculo_placa}</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Lic: ${c.licencia}</div>
                </td>
                <td style="padding: 20px 24px;">
                    <span class="status-tag ${statusClass}">${c.estado_servicio}</span>
                </td>
                <td style="padding: 20px 24px;">
                    <div style="font-weight: 700;">${c.boletos_hoy} validados</div>
                    <div style="font-size: 0.75rem; color: var(--accent); font-weight: 600;">Bs. ${parseFloat(c.total_recaudado).toFixed(2)} total</div>
                </td>
                <td style="padding: 20px 24px; text-align: right;">
                    ${c.estado_servicio === 'PENDIENTE' ? `
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button class="btn-approve" onclick="updateStatus(${c.chofer_id}, 'ACTIVO')">VALIDAR</button>
                            <button class="btn-approve" style="background: var(--danger);" onclick="updateStatus(${c.chofer_id}, 'RECHAZADO')">RECHAZAR</button>
                        </div>
                    ` : `
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button class="action-btn" title="Editar Estado" onclick="promptStatus(${c.chofer_id})">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button class="action-btn" title="Ver Historial">
                                <i class="fas fa-history"></i>
                            </button>
                        </div>
                    `}
                </td>
            `;
            body.appendChild(tr);
        });
        
        document.getElementById('count-pending').textContent = pending;
    }

    async function updateStatus(id, status) {
        if (!confirm(`¿Confirmas cambiar el estado del chofer a ${status}?`)) return;
        
        try {
            const formData = new FormData();
            formData.append('chofer_id', id);
            formData.append('estado', status);
            
            const response = await fetch('../../backend/update_chofer_status.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            if (result.success) loadChoferes();
        } catch (err) {
            alert('Error al actualizar estado');
        }
    }

    function promptStatus(id) {
        const newStatus = prompt("Cambiar estado a: (ACTIVO, INACTIVO, LICENCIA)");
        if (newStatus && ['ACTIVO', 'INACTIVO', 'LICENCIA'].includes(newStatus.toUpperCase())) {
            updateStatus(id, newStatus.toUpperCase());
        }
    }

    loadChoferes();
</script>

<?php include '../includes/footer.php'; ?>
