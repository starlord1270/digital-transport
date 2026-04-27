<?php
/**
 * DIGITAL TRANSPORT - REPORTES FINANCIEROS (PREMIUM)
 */
$page_title = "Reportes Financieros - Digital Transport";
$active_page = "reportes";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de Seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 4 || !isset($_SESSION['linea_id'])) {
    header("Location: ../inicio-sesion-usuarios.php?error=acceso_denegado");
    exit;
}

$linea_id_sesion = $_SESSION['linea_id'];

include '../includes/header.php';
?>

<!-- Librerías para PDF y Gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<div class="animate-fade-in">
    <div style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Reportes y Finanzas</h1>
            <p style="color: var(--text-muted);">Analiza el flujo de caja y genera reportes de liquidación.</p>
        </div>
        <button class="btn btn-secondary" onclick="exportReporteToPDF()">
            <i class="fas fa-file-pdf"></i> Exportar a PDF
        </button>
    </div>

    <!-- Filtros -->
    <div class="card" style="padding: 24px; margin-bottom: 32px;">
        <form id="report-filter-form" style="display: flex; gap: 20px; align-items: flex-end;">
            <div class="form-group" style="flex: 1;">
                <label class="form-label">Desde</label>
                <input type="date" id="fecha_desde" class="form-input" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label class="form-label">Hasta</label>
                <input type="date" id="fecha_hasta" class="form-input" required>
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 12px 32px;">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
        </form>
    </div>

    <!-- Contenido del Reporte (Capturado para PDF) -->
    <div id="reporte-content">
        <!-- Tarjetas de Resumen -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px;">
            <div class="glass-card" style="padding: 24px; border-bottom: 4px solid var(--secondary);">
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total Ingresos</span>
                <div id="total-ingreso" style="font-size: 1.8rem; font-weight: 800; color: var(--secondary); margin-top: 8px;">Bs. 0.00</div>
            </div>
            <div class="glass-card" style="padding: 24px; border-bottom: 4px solid var(--danger);">
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total Egresos</span>
                <div id="total-egreso" style="font-size: 1.8rem; font-weight: 800; color: var(--danger); margin-top: 8px;">Bs. 0.00</div>
            </div>
            <div class="glass-card" style="padding: 24px; border-bottom: 4px solid var(--accent);">
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Flujo Neto</span>
                <div id="total-neto" style="font-size: 1.8rem; font-weight: 800; color: var(--accent); margin-top: 8px;">Bs. 0.00</div>
            </div>
        </div>

        <!-- Gráfico -->
        <div class="card" style="padding: 32px;">
            <h3 style="margin-bottom: 24px;"><i class="fas fa-chart-area"></i> Flujo de Caja en el Tiempo</h3>
            <div style="height: 400px; width: 100%;">
                <canvas id="flujoCajaChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    const LINEA_ID = <?php echo $linea_id_sesion; ?>;
    const API_REPORTES = '../../backend/fetch_reportes_flujo_caja.php';
    let myChart = null;

    async function loadReporte() {
        const desde = document.getElementById('fecha_desde').value;
        const hasta = document.getElementById('fecha_hasta').value;
        
        try {
            const response = await fetch(`${API_REPORTES}?linea_id=${LINEA_ID}&desde=${desde}&hasta=${hasta}`);
            const result = await response.json();
            
            if (result.success) {
                renderReporte(result.reporte);
            }
        } catch (err) {
            console.error(err);
        }
    }

    function renderReporte(reporte) {
        document.getElementById('total-ingreso').textContent = `Bs. ${parseFloat(reporte.total_ingreso).toFixed(2)}`;
        document.getElementById('total-egreso').textContent = `Bs. ${parseFloat(reporte.total_egreso).toFixed(2)}`;
        document.getElementById('total-neto').textContent = `Bs. ${parseFloat(reporte.flujo_neto).toFixed(2)}`;

        if (myChart) myChart.destroy();

        const ctx = document.getElementById('flujoCajaChart').getContext('2d');
        myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: reporte.grafico.map(d => d.fecha),
                datasets: [{
                    label: 'Ingresos',
                    data: reporte.grafico.map(d => d.ingreso),
                    borderColor: '#1e88e5',
                    backgroundColor: 'rgba(30, 136, 229, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Egresos',
                    data: reporte.grafico.map(d => d.egreso),
                    borderColor: '#f44336',
                    backgroundColor: 'rgba(244, 67, 54, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    function exportReporteToPDF() {
        const { jsPDF } = window.jspdf;
        const element = document.getElementById('reporte-content');
        
        html2canvas(element, { scale: 2 }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgWidth = 190;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            pdf.setFontSize(20);
            pdf.text("Reporte de Liquidación Diaria", 10, 15);
            pdf.setFontSize(10);
            pdf.text(`Generado el: ${new Date().toLocaleString()}`, 10, 22);
            pdf.text(`Línea ID: ${LINEA_ID}`, 10, 27);
            
            pdf.addImage(imgData, 'PNG', 10, 35, imgWidth, imgHeight);
            pdf.save(`Reporte_Digital_Transport_${new Date().getTime()}.pdf`);
        });
    }

    document.getElementById('report-filter-form').addEventListener('submit', (e) => {
        e.preventDefault();
        loadReporte();
    });

    // Fechas por defecto: última semana
    const today = new Date();
    const lastWeek = new Date(today);
    lastWeek.setDate(today.getDate() - 7);
    document.getElementById('fecha_desde').value = lastWeek.toISOString().split('T')[0];
    document.getElementById('fecha_hasta').value = today.toISOString().split('T')[0];

    loadReporte();
</script>

<?php include '../includes/footer.php'; ?>