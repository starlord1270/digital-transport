<?php
/**
 * DIGITAL TRANSPORT - MONITOR FINANCIERO GLOBAL (SUPER ADMIN)
 */
$page_title = "Monitor Financiero - Digital Transport";
$active_page = "finanzas";

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
            <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Monitor Financiero Global</h1>
            <p style="color: var(--text-muted);">Seguimiento en tiempo real de la recaudación en toda la red de transporte.</p>
        </div>
        <div class="glass-card" style="padding: 12px 24px; border-left: 5px solid var(--accent);">
            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">TOTAL ACUMULADO</span>
            <div id="total-global" style="font-size: 1.8rem; font-weight: 800; color: var(--text-main);">Bs. 0.00</div>
        </div>
    </div>

    <!-- Grid de Stats Rápidos -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px;">
        <div class="card" style="padding: 24px; text-align: center;">
            <i class="fas fa-ticket-alt fa-2x" style="color: var(--secondary); margin-bottom: 12px;"></i>
            <h4 style="color: var(--text-muted); font-size: 0.8rem;">BOLETOS VENDIDOS</h4>
            <div id="total-boletos" style="font-size: 1.5rem; font-weight: 800;">0</div>
        </div>
        <div class="card" style="padding: 24px; text-align: center;">
            <i class="fas fa-bus fa-2x" style="color: var(--accent); margin-bottom: 12px;"></i>
            <h4 style="color: var(--text-muted); font-size: 0.8rem;">LÍNEA LÍDER</h4>
            <div id="linea-lider" style="font-size: 1.5rem; font-weight: 800;">---</div>
        </div>
        <div class="card" style="padding: 24px; text-align: center;">
            <i class="fas fa-users fa-2x" style="color: var(--warning); margin-bottom: 12px;"></i>
            <h4 style="color: var(--text-muted); font-size: 0.8rem;">PASAJEROS ACTIVOS</h4>
            <div id="pasajeros-activos" style="font-size: 1.5rem; font-weight: 800;">0</div>
        </div>
    </div>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 32px; margin-bottom: 40px;">
        <div class="card" style="padding: 32px;">
            <h3 style="margin-bottom: 24px;">Recaudación por Línea</h3>
            <canvas id="chart-recaudacion-lineas" height="250"></canvas>
        </div>
        <div class="card" style="padding: 32px;">
            <h3 style="margin-bottom: 24px;">Distribución de Tarifas</h3>
            <canvas id="chart-tarifas" height="250"></canvas>
        </div>
    </div>

    <!-- Detalle por Línea -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 24px; border-bottom: 1px solid var(--bg-main);">
            <h3 style="margin: 0;">Detalle de Ingresos por Empresa</h3>
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: rgba(0,0,0,0.02); text-align: left;">
                    <th style="padding: 20px;">Línea</th>
                    <th style="padding: 20px;">Pasajes</th>
                    <th style="padding: 20px;">Recaudación</th>
                    <th style="padding: 20px;">% del Mercado</th>
                </tr>
            </thead>
            <tbody id="tabla-finanzas">
                <!-- Dinámico -->
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    async function loadFinanzas() {
        try {
            const response = await fetch('../../backend/superadmin/fetch_finanzas_global.php');
            const data = await response.json();
            
            if(data.success) {
                renderFinanzas(data.stats);
            }
        } catch (e) { console.error(e); }
    }

    function renderFinanzas(data) {
        document.getElementById('total-global').textContent = `Bs. ${parseFloat(data.total_general).toFixed(2)}`;
        document.getElementById('total-boletos').textContent = data.total_boletos;
        document.getElementById('linea-lider').textContent = data.linea_lider || '---';
        document.getElementById('pasajeros-activos').textContent = data.total_pasajeros;

        // Tabla
        const tbody = document.getElementById('tabla-finanzas');
        tbody.innerHTML = '';
        data.detalle_lineas.forEach(l => {
            const porc = ((l.recaudacion / data.total_general) * 100).toFixed(1);
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--bg-main)';
            tr.innerHTML = `
                <td style="padding: 20px; font-weight: 700;">${l.nombre}</td>
                <td style="padding: 20px;">${l.boletos}</td>
                <td style="padding: 20px; font-weight: 800; color: var(--accent);">Bs. ${parseFloat(l.recaudacion).toFixed(2)}</td>
                <td style="padding: 20px;">
                    <div style="width: 100%; height: 8px; background: var(--bg-main); border-radius: 4px; overflow: hidden;">
                        <div style="width: ${porc}%; height: 100%; background: var(--secondary);"></div>
                    </div>
                    <span style="font-size: 0.7rem; color: var(--text-muted);">${porc}%</span>
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Gráfico Líneas
        new Chart(document.getElementById('chart-recaudacion-lineas'), {
            type: 'bar',
            data: {
                labels: data.detalle_lineas.map(l => l.nombre),
                datasets: [{
                    label: 'Recaudación (Bs.)',
                    data: data.detalle_lineas.map(l => l.recaudacion),
                    backgroundColor: 'rgba(30, 136, 229, 0.7)',
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // Gráfico Tarifas
        new Chart(document.getElementById('chart-tarifas'), {
            type: 'doughnut',
            data: {
                labels: ['Estándar', 'Estudiante', '3ra Edad'],
                data: [data.tarifas.estandar, data.tarifas.estudiante, data.tarifas.tercera_edad],
                datasets: [{
                    data: [data.tarifas.estandar, data.tarifas.estudiante, data.tarifas.tercera_edad],
                    backgroundColor: ['#1e88e5', '#4caf50', '#ff9800']
                }]
            },
            options: { responsive: true }
        });
    }

    loadFinanzas();
</script>

<?php include '../includes/footer.php'; ?>
