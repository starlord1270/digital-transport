<?php
/**
 * DIGITAL TRANSPORT - HISTORIAL DE VIAJES (REFACTORIZADA)
 */
$page_title = "Historial - Digital Transport";
$active_page = "historial";

require_once '../backend/includes/db.php'; // Conexión PDO

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_is_logged_in = (
    isset($_SESSION['usuario_id']) && 
    in_array($_SESSION['tipo_usuario_id'], [1, 2, 5, 6])
);

if (!$user_is_logged_in) {
    header("Location: inicio-sesion-usuarios.php");
    exit();
}

include 'includes/header.php';
?>

<div class="animate-fade-in">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
        <div>
            <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Tu Actividad</h1>
            <p style="color: var(--text-muted);">Revisa tus viajes y transacciones recientes.</p>
        </div>
        <button class="btn btn-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimir Reporte
        </button>
    </div>

    <!-- Estadísticas Rápidas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; margin-bottom: 40px;">
        <div class="glass-card" style="padding: 24px; border-left: 4px solid var(--secondary);">
            <p style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 600;">Total Viajes</p>
            <h2 id="stat-trips" style="font-size: 2rem; margin: 8px 0;">--</h2>
            <p style="font-size: 0.8rem; color: var(--accent);"><i class="fas fa-chart-line"></i> Actividad este mes</p>
        </div>
        <div class="glass-card" style="padding: 24px; border-left: 4px solid var(--accent);">
            <p style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 600;">Total Gastado</p>
            <h2 id="stat-spent" style="font-size: 2rem; margin: 8px 0;">--</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted);">Bolivianos (Bs.)</p>
        </div>
        <div class="glass-card" style="padding: 24px; border-left: 4px solid var(--warning);">
            <p style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 600;">Saldo Actual</p>
            <h2 style="font-size: 2rem; margin: 8px 0;">Bs. <?php echo number_format($_SESSION['saldo'], 2); ?></h2>
            <p style="font-size: 0.8rem; color: var(--secondary); cursor: pointer;" onclick="window.location.href='recarga-digital.php'">+ Recargar ahora</p>
        </div>
    </div>

    <!-- Lista de Historial -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 24px; background: var(--bg-main); border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Historial Reciente</h3>
            <div style="display: flex; gap: 10px;">
                <select class="form-input" style="padding: 6px 12px; width: auto;" onchange="filterHistory(this.value)">
                    <option value="all">Todos los registros</option>
                    <option value="COBRO">Viajes (Cobros)</option>
                    <option value="RECARGA">Recargas</option>
                </select>
            </div>
        </div>
        
        <div id="history-container">
            <div style="padding: 60px; text-align: center; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin fa-2x" style="margin-bottom: 16px;"></i>
                <p>Cargando tu historial...</p>
            </div>
        </div>
    </div>
</div>

<script>
    let allHistory = [];

    async function loadHistory() {
        try {
            const response = await fetch('../backend/fetch_history.php');
            const data = await response.json();
            
            if (data.error) throw new Error(data.error);
            
            allHistory = data.history;
            document.getElementById('stat-trips').textContent = data.total_trips;
            document.getElementById('stat-spent').textContent = 'Bs. ' + parseFloat(data.total_spent).toFixed(2);
            
            renderHistory(allHistory);
        } catch (err) {
            document.getElementById('history-container').innerHTML = `
                <div style="padding: 40px; text-align: center; color: var(--danger);">
                    <i class="fas fa-exclamation-triangle fa-2x" style="margin-bottom: 16px;"></i>
                    <p>No se pudo cargar el historial: ${err.message}</p>
                </div>
            `;
        }
    }

    function renderHistory(items) {
        const container = document.getElementById('history-container');
        if (items.length === 0) {
            container.innerHTML = '<div style="padding: 40px; text-align: center; color: var(--text-muted);">No hay actividad registrada.</div>';
            return;
        }

        let html = '<table style="width: 100%; border-collapse: collapse;">';
        html += `
            <thead>
                <tr style="text-align: left; background: var(--bg-main); font-size: 0.85rem; color: var(--text-muted);">
                    <th style="padding: 16px 24px;">Fecha y Hora</th>
                    <th style="padding: 16px 24px;">Concepto / Línea</th>
                    <th style="padding: 16px 24px;">Tipo</th>
                    <th style="padding: 16px 24px; text-align: right;">Monto</th>
                    <th style="padding: 16px 24px;">Estado</th>
                </tr>
            </thead>
            <tbody>
        `;

        items.forEach(item => {
            const isRecarga = item.type === 'RECARGA';
            html += `
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.05); transition: var(--transition);" onmouseover="this.style.background='var(--bg-main)'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 16px 24px;">
                        <div style="font-weight: 500;">${item.date}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${item.time}</div>
                    </td>
                    <td style="padding: 16px 24px;">
                        <div style="font-weight: 600;">${item.line || 'Sistema Digital'}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${item.details}</div>
                    </td>
                    <td style="padding: 16px 24px;">
                        <span style="padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; background: ${isRecarga ? 'rgba(76, 175, 80, 0.1)' : 'rgba(30, 136, 229, 0.1)'}; color: ${isRecarga ? 'var(--accent)' : 'var(--secondary)'};">
                            ${isRecarga ? 'RECARGA' : 'VIAJE'}
                        </span>
                    </td>
                    <td style="padding: 16px 24px; text-align: right; font-weight: 700; color: ${isRecarga ? 'var(--accent)' : 'var(--text-main)'};">
                        ${isRecarga ? '+' : '-'} Bs. ${parseFloat(item.amount).toFixed(2)}
                    </td>
                    <td style="padding: 16px 24px;">
                        <div style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem;">
                            <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--accent);"></div>
                            ${item.status}
                        </div>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function filterHistory(type) {
        if (type === 'all') {
            renderHistory(allHistory);
        } else {
            renderHistory(allHistory.filter(h => h.type === type));
        }
    }

    loadHistory();
</script>

<?php include 'includes/footer.php'; ?>