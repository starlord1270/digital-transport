<?php
/**
 * DIGITAL TRANSPORT - DASHBOARD ADMINISTRADOR DE LÍNEA (PREMIUM)
 */
$page_title = "Panel Administrativo - Digital Transport";
$active_page = "dashboard";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de Seguridad y Rol (RBAC)
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 4 || !isset($_SESSION['linea_id'])) {
    header("Location: ../inicio-sesion-usuarios.php?error=acceso_denegado");
    exit;
}

$linea_id_sesion = $_SESSION['linea_id'];

include '../includes/header.php';
?>

<div class="animate-fade-in">
    <!-- Header con Resumen Rápido -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
        <div>
            <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Panel de Control</h1>
            <p style="color: var(--text-muted);">Bienvenido al centro de gestión de tu línea de transporte.</p>
        </div>
        <div style="display: flex; gap: 16px;">
            <div class="glass-card" style="padding: 12px 24px; text-align: center; border-bottom: 4px solid var(--secondary);">
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Choferes Activos</span>
                <div id="choferes-activos" style="font-size: 1.5rem; font-weight: 800; color: var(--secondary);">0</div>
            </div>
            <div class="glass-card" style="padding: 12px 24px; text-align: center; border-bottom: 4px solid var(--accent);">
                <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Recaudación Hoy</span>
                <div id="total-recaudado" style="font-size: 1.5rem; font-weight: 800; color: var(--accent);">Bs. 0.00</div>
            </div>
            <div id="alert-pendientes" class="glass-card" style="padding: 12px 24px; text-align: center; border-bottom: 4px solid var(--danger); display: none; cursor: pointer;" onclick="window.location.href='choferes.php'">
                <span style="font-size: 0.75rem; color: var(--danger); text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Por Validar</span>
                <div id="choferes-pendientes-val" style="font-size: 1.5rem; font-weight: 800; color: var(--danger);">0</div>
            </div>
            <div id="alert-canjes" class="glass-card" style="padding: 12px 24px; text-align: center; border-bottom: 4px solid var(--warning); display: none;">
                <span style="font-size: 0.75rem; color: #856404; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Canjes Pendientes</span>
                <div id="canjes-count-badge" style="font-size: 1.5rem; font-weight: 800; color: #856404;">0</div>
            </div>
        </div>
    </div>

    <!-- Grid Principal -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start;">
        
        <!-- Sección Izquierda: Resumen de Choferes -->
        <div class="card" style="padding: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 2px solid var(--bg-main);">
                <h3 style="display: flex; align-items: center; gap: 12px;"><i class="fas fa-users" style="color: var(--primary);"></i> Actividad de Choferes</h3>
                <a href="choferes.php" style="font-size: 0.8rem; color: var(--secondary); text-decoration: none; font-weight: 600;">Ver todos <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div id="resumen-choferes" style="display: grid; gap: 12px;">
                <p style="text-align: center; color: var(--text-muted); padding: 20px;">Cargando datos...</p>
            </div>
        </div>

        <!-- Sección Derecha: Validaciones Especiales -->
        <div style="display: grid; gap: 32px;">
            <div class="glass-card" style="padding: 32px; background: rgba(255,255,255,0.02);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h3 style="display: flex; align-items: center; gap: 12px;"><i class="fas fa-id-card-alt" style="color: var(--warning);"></i> Validación de Pasajeros (Tarifa Especial)</h3>
                    <div id="count-pendientes" style="background: var(--danger); color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 800;">0</div>
                </div>
                
                <!-- Contenedor para el listado de validaciones -->
                <div id="validaciones-pendientes" style="display: grid; gap: 12px; margin-bottom: 20px;">
                    <p style="text-align: center; color: var(--text-muted); padding: 10px;">Cargando...</p>
                </div>
                
                <button class="btn btn-secondary" style="width: 100%; margin-top: 24px; font-size: 0.8rem;" onclick="window.location.href='reportes.php'">
                    <i class="fas fa-file-invoice-dollar"></i> Generar Reporte de Canje
                </button>
            </div>

            <!-- Liquidaciones de Choferes -->
            <div class="card" style="padding: 32px; border-left: 5px solid var(--secondary);">
                <h3 style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;"><i class="fas fa-hand-holding-usd" style="color: var(--secondary);"></i> Liquidaciones por Pagar</h3>
                <div id="lista-canjes" style="display: grid; gap: 12px;">
                    <p style="text-align: center; color: var(--text-muted); padding: 10px;">No hay liquidaciones solicitadas.</p>
                </div>
            </div>

            <!-- Stats Box -->
            <div class="card" style="padding: 32px; background: var(--primary); color: white;">
                <h4 style="margin-bottom: 16px;">Recordatorio Semanal</h4>
                <p style="font-size: 0.85rem; opacity: 0.8; line-height: 1.6;">Recuerda que el cierre administrativo se realiza todos los viernes a las 18:00. Asegúrate de que todos los choferes hayan canjeado sus boletos.</p>
            </div>
        </div>
    </div>
</div>

<script>
    const LINEA_ID = <?php echo $linea_id_sesion; ?>;
    const API_DASHBOARD = '../../backend/fetch_dashboard_data.php';

    async function loadDashboard() {
        try {
            const response = await fetch(`${API_DASHBOARD}?linea_id=${LINEA_ID}`);
            const result = await response.json();
            
            if (result.success) {
                renderDashboard(result.data);
            } else {
                console.error(result.error);
            }
        } catch (err) {
            console.error("Error cargando dashboard:", err);
        }
    }

    function renderDashboard(data) {
        // Stats Superiores
        document.getElementById('total-recaudado').textContent = `Bs. ${parseFloat(data.total_recaudado).toFixed(2)}`;
        document.getElementById('choferes-activos').textContent = data.choferes_activos;

        // Alerta de Choferes Pendientes
        const alertBox = document.getElementById('alert-pendientes');
        if (data.choferes_pendientes_count > 0) {
            alertBox.style.display = 'block';
            document.getElementById('choferes-pendientes-val').textContent = data.choferes_pendientes_count;
        } else {
            alertBox.style.display = 'none';
        }

        // Alerta de Canjes
        const canjeAlert = document.getElementById('alert-canjes');
        if (data.canjes_pendientes_count > 0) {
            canjeAlert.style.display = 'block';
            document.getElementById('canjes-count-badge').textContent = data.canjes_pendientes_count;
        } else {
            canjeAlert.style.display = 'none';
        }

        // Lista de Choferes
        const resumenDiv = document.getElementById('resumen-choferes');
        resumenDiv.innerHTML = '';
        if (data.resumen_choferes && data.resumen_choferes.length > 0) {
            data.resumen_choferes.forEach(c => {
                const item = document.createElement('div');
                item.className = 'glass-card';
                item.style.padding = '16px';
                item.style.display = 'flex';
                item.style.justifyContent = 'space-between';
                item.style.alignItems = 'center';
                item.innerHTML = `
                    <div>
                        <div style="font-weight: 700; font-size: 0.95rem;">${c.nombre_completo}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${c.boletos_cobrados} pasajes validados</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 800; color: var(--secondary);">Bs. ${parseFloat(c.monto_canje).toFixed(2)}</div>
                        <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600;">PENDIENTE</div>
                    </div>
                `;
                resumenDiv.appendChild(item);
            });
        } else {
            resumenDiv.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 20px;">Sin actividad hoy.</p>';
        }

        // Validaciones
        const validDiv = document.getElementById('validaciones-pendientes');
        validDiv.innerHTML = '';
        document.getElementById('count-pendientes').textContent = data.validaciones_pendientes.length;
        
        if (data.validaciones_pendientes && data.validaciones_pendientes.length > 0) {
            data.validaciones_pendientes.forEach(v => {
                const item = document.createElement('div');
                item.className = 'glass-card';
                item.style.padding = '16px';
                item.style.borderLeft = '4px solid var(--warning)';
                item.style.marginBottom = '12px';
                item.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <div style="font-size: 0.9rem; font-weight: 700;">${v.nombre_completo}</div>
                            <div style="font-size: 0.7rem; color: var(--text-muted); margin: 4px 0;">${v.tipo_descuento}</div>
                            <div style="font-size: 0.65rem; color: var(--text-muted);">${v.fecha_solicitud}</div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <a href="../../uploads/documentos/${v.comprobante_url}" target="_blank" class="btn" style="padding: 4px 8px; font-size: 0.65rem; background: var(--bg-main); text-decoration: none; text-align: center;">
                                <i class="fas fa-eye"></i> Ver Doc
                            </a>
                            <div style="display: flex; gap: 4px;">
                                <button class="btn btn-primary" style="padding: 4px 8px; font-size: 0.65rem;" onclick="procesarPasajero(${v.validacion_id}, 'APROBADA')">SI</button>
                                <button class="btn btn-danger" style="padding: 4px 8px; font-size: 0.65rem;" onclick="procesarPasajero(${v.validacion_id}, 'RECHAZADA')">NO</button>
                            </div>
                        </div>
                    </div>
                `;
                validDiv.appendChild(item);
            });
        } else {
            validDiv.innerHTML = '<p style="text-align: center; color: var(--text-muted);">Sin solicitudes nuevas.</p>';
        }

        // Lista de Canjes
        const canjesDiv = document.getElementById('lista-canjes');
        canjesDiv.innerHTML = '';
        if (data.canjes_pendientes && data.canjes_pendientes.length > 0) {
            data.canjes_pendientes.forEach(c => {
                const item = document.createElement('div');
                item.className = 'glass-card';
                item.style.padding = '16px';
                item.style.display = 'flex';
                item.style.justifyContent = 'space-between';
                item.style.alignItems = 'center';
                item.innerHTML = `
                    <div>
                        <div style="font-weight: 700; font-size: 0.9rem;">${c.nombre_completo}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Solicitado: ${c.fecha}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 800; color: var(--secondary); font-size: 1.1rem;">Bs. ${parseFloat(c.monto).toFixed(2)}</div>
                        <button class="btn btn-primary" style="padding: 4px 12px; font-size: 0.7rem; margin-top: 4px;" onclick="pagarCanje(${c.canje_id})">PAGAR</button>
                    </div>
                `;
                canjesDiv.appendChild(item);
            });
        } else {
            canjesDiv.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 10px;">No hay liquidaciones pendientes.</p>';
        }
    }

    async function procesarPasajero(id, estado) {
        if (!confirm(`¿Deseas marcar esta solicitud como ${estado}?`)) return;
        try {
            const formData = new FormData();
            formData.append('validacion_id', id);
            formData.append('estado', estado);
            const response = await fetch('../../backend/procesar_validacion_pasajero.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            if (result.success) loadDashboard();
        } catch (err) { alert('Error al procesar validación.'); }
    }

    async function pagarCanje(id) {
        if (!confirm('¿Confirmas que has entregado el dinero en efectivo al chofer?')) return;
        
        try {
            const formData = new FormData();
            formData.append('canje_id', id);
            const response = await fetch('../../backend/procesar_canje.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            if (result.success) loadDashboard();
        } catch (err) {
            alert('Error al procesar el pago.');
        }
    }

    loadDashboard();
</script>

<?php include '../includes/footer.php'; ?>