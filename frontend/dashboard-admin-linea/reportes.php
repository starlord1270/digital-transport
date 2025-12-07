<?php 
// 1. ASEGURAR SESIÓN INICIADA
if (session_status() == PHP_SESSION_NONE) {
    session_start();
} 

// 🛑 INICIO DE VERIFICACIÓN DE SEGURIDAD Y ROL (RBAC) 🛑

// Tipo de usuario esperado para esta página: 4 (ADMIN_LINEA)
$user_type_expected = 4;
$user_type_actual = isset($_SESSION['tipo_usuario_id']) ? $_SESSION['tipo_usuario_id'] : 0;
// Obtenemos el ID de la línea guardado en la sesión
$linea_id_sesion = isset($_SESSION['linea_id']) ? $_SESSION['linea_id'] : 0; 

// Si el usuario no está logueado, O el tipo de usuario no es 4, O no tiene linea_id asignado, redirigir.
if ($user_type_actual != $user_type_expected || $linea_id_sesion == 0) {
    // Limpiar y destruir la sesión actual para evitar conflictos futuros
    session_unset();
    session_destroy();
    
    // Redirigir al inicio de sesión
    $login_url = '/Competencia-Analisis/digital-transport/frontend/inicio-sesion-lineas-choferes/login.php';
    header("Location: " . $login_url . "?error=acceso_denegado");
    exit;
}

// 🛑 FIN DE VERIFICACIÓN DE SEGURIDAD Y ROL 🛑
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Financieros - Digital Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <style>
        /* ... (CSS sin cambios) ... */
        :root {
            --color-primary: #0b2e88;
            --color-secondary: #1e88e5;
            --color-success: #4caf50;
            --color-error: #f44336;
            --color-text-dark: #333;
            --color-background-light: #f4f7f9;
            --color-card-bg: #fff;
            --color-border: #e0e0e0;
            
            --bg-primary: #ffffff;
            --bg-secondary: #f4f7f9;
            --text-primary: #333;
        }

        [data-theme="dark"] {
            --color-background-light: #1a1a1a;
            --bg-primary: #1e1e1e;
            --bg-secondary: #2a2a2a;
            --text-primary: #e0e0e0;
            --color-card-bg: #2a2a2a;
            --color-border: #404040;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
        }

        .admin-container {
            width: 95%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: var(--color-card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: var(--color-primary);
            border-bottom: 2px solid var(--color-border);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        /* --- Tabs de Navegación --- */
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--color-border);
            margin-bottom: 20px;
        }
        .tab-btn {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1em;
            font-weight: 600;
            color: var(--color-text-dark);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab-btn.active {
            color: var(--color-primary);
            border-bottom: 3px solid var(--color-primary);
        }

        /* --- Contenedor Principal de Reportes --- */
        .reporte-box {
            background-color: var(--color-card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--color-border);
        }
        .reporte-header {
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .reporte-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.2em;
        }
        
        /* --- Controles de Filtro --- */
        .filter-controls {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            align-items: flex-end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-size: 0.9em;
            color: #555;
            margin-bottom: 5px;
        }
        .filter-controls select, .filter-controls input {
            padding: 10px;
            border: 1px solid var(--color-border);
            border-radius: 5px;
            font-size: 1em;
            min-width: 150px;
            background-color: white;
        }
        .btn-generar {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-generar:hover {
            background-color: #081e5b;
        }
        
        /* 🛑 ESTILO NUEVO PARA EL BOTÓN DE EXPORTAR 🛑 */
        .btn-exportar {
            background-color: var(--color-error); /* Rojo para PDF */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-exportar:hover {
            background-color: #c42b20;
        }

        /* --- Tarjetas de Métricas --- */
        .metric-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .metric-card {
            background-color: var(--color-background-light);
            padding: 15px;
            border-radius: 8px;
            border-left: 5px solid var(--color-secondary);
        }
        .metric-card h4 {
            margin: 0 0 5px 0;
            font-size: 0.9em;
            color: #777;
        }
        .metric-card .value {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--color-text-dark);
            display: flex;
            align-items: center;
        }
        .metric-card .tendencia {
            font-size: 1em;
            margin-left: 10px;
            font-weight: 600;
        }
        .tendencia.positive { color: var(--color-success); }
        .tendencia.negative { color: var(--color-error); }
        .tendencia i { margin-right: 5px; }

        /* --- Gráfico --- */
        .chart-container {
            width: 100%;
            padding: 20px;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            background-color: white;
        }
    </style>
</head>
<body>

    <div class="admin-container" style="position: relative;">
        <button class="theme-toggle" id="theme-toggle" title="Cambiar tema" style="position: absolute; top: 20px; right: 20px; background: none; border: none; font-size: 1.3em; cursor: pointer; padding: 8px; border-radius: 50%; color: var(--text-primary); z-index: 10;">
            <i class="fas fa-moon"></i>
        </button>
        <h1>Panel Administrativo - Línea de Transporte</h1>
        
        <div class="tabs">
            <button class="tab-btn" onclick="window.location.href='dashboard-admin.php'">Dashboard</button>
            <button class="tab-btn" onclick="window.location.href='choferes.php'">Choferes</button>
            <button class="tab-btn active">Reportes</button>
        </div>

        <div class="reporte-box">
            <div class="reporte-header">
                <i class="fas fa-chart-line" style="margin-right: 10px; font-size: 1.5em; color: var(--color-secondary);"></i>
                <h2>Generación de Reportes de Flujo de Caja</h2>
            </div>
            
            <form id="report-filter-form" class="filter-controls">
                <div class="filter-group">
                    <label for="tipo_reporte">Tipo de Reporte</label>
                    <select id="tipo_reporte" name="tipo_reporte">
                        <option value="diario">Diario</option>
                        <option value="mensual">Mensual</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="fecha_desde">Fecha Desde</label>
                    <input type="date" id="fecha_desde" name="fecha_desde" required>
                </div>
                <div class="filter-group">
                    <label for="fecha_hasta">Fecha Hasta</label>
                    <input type="date" id="fecha_hasta" name="fecha_hasta" required>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn-generar" id="btn-generar">
                        <i class="fas fa-play"></i> Generar
                    </button>
                </div>
                
                <div class="filter-group" id="export-btn-group" style="display: none;">
                    <button type="button" class="btn-exportar" onclick="exportReporteToPDF()">
                        <i class="fas fa-file-pdf"></i> Exportar a PDF
                    </button>
                </div>
                
            </form>

            <div id="reporte-content">
                <p style="text-align: center; color: #777;">Selecciona un rango de fechas y haz clic en "Generar" para ver el reporte.</p>
            </div>
            
        </div>
        
    </div>

    <script>
        const API_REPORTES = '../../backend/fetch_reportes_flujo_caja.php'; 
        // ⭐ CORRECCIÓN CLAVE: Obtener el ID de la sesión del administrador
        const LINEA_ID = <?php echo $linea_id_sesion; ?>; 
        let myChart = null; // Variable para el gráfico de Chart.js
        
        // ----------------------------------------------------
        // I. FUNCIONES DE GENERACIÓN DE REPORTE
        // ----------------------------------------------------

        document.getElementById('report-filter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const tipoReporte = document.getElementById('tipo_reporte').value;
            const fechaDesde = document.getElementById('fecha_desde').value;
            const fechaHasta = document.getElementById('fecha_hasta').value;

            if (!fechaDesde || !fechaHasta) {
                alert("Por favor, selecciona un rango de fechas válido.");
                return;
            }

            fetchReporteData(tipoReporte, fechaDesde, fechaHasta);
        });

        async function fetchReporteData(tipo, desde, hasta) {
            
            if (LINEA_ID === 0) {
                 console.error("Error de Sesión: No se encontró LINEA_ID para generar el reporte.");
                 const reporteContent = document.getElementById('reporte-content');
                 reporteContent.innerHTML = `<p style="text-align: center; color: var(--color-error);"><i class="fas fa-exclamation-triangle"></i> Error de Sesión. Vuelva a iniciar sesión.</p>`;
                 return;
            }
            
            const reporteContent = document.getElementById('reporte-content');
            reporteContent.innerHTML = '<p style="text-align: center; color: var(--color-primary);"><i class="fas fa-spinner fa-spin"></i> Generando reporte...</p>';
            document.getElementById('btn-generar').disabled = true;
            document.getElementById('export-btn-group').style.display = 'none'; // Ocultar botón de exportar

            try {
                const url = `${API_REPORTES}?linea_id=${LINEA_ID}&tipo=${tipo}&desde=${desde}&hasta=${hasta}`;
                const response = await fetch(url);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || "No se pudo generar el reporte.");
                }

                renderReporte(data.reporte);
                
            } catch (error) {
                console.error("Error al generar reporte:", error);
                reporteContent.innerHTML = `<p style="text-align: center; color: var(--color-error);"><i class="fas fa-exclamation-triangle"></i> Error: ${error.message}</p>`;
            } finally {
                document.getElementById('btn-generar').disabled = false;
            }
        }

        function renderReporte(reporte) {
            const reporteContent = document.getElementById('reporte-content');
            
            // 1. Renderizar Métricas
            const metricCardsHtml = `
                <div class="metric-cards">
                    <div class="metric-card" style="border-left-color: var(--color-primary);">
                        <h4>Total Recaudado (Ingreso)</h4>
                        <div class="value">${parseFloat(reporte.total_ingreso).toFixed(2)} Bs</div>
                    </div>
                    <div class="metric-card" style="border-left-color: var(--color-error);">
                        <h4>Total Canjeado (Egreso)</h4>
                        <div class="value">${parseFloat(reporte.total_egreso).toFixed(2)} Bs</div>
                    </div>
                    <div class="metric-card" style="border-left-color: var(--color-success);">
                        <h4>Flujo Neto de Caja</h4>
                        <div class="value">
                            ${parseFloat(reporte.flujo_neto).toFixed(2)} Bs
                            <span class="tendencia ${reporte.flujo_neto >= 0 ? 'positive' : 'negative'}">
                                <i class="fas fa-arrow-${reporte.flujo_neto >= 0 ? 'up' : 'down'}"></i> ${Math.abs(reporte.porcentaje_crecimiento).toFixed(2)}% 
                            </span>
                        </div>
                    </div>
                </div>
            `;
            
            // 2. Contenedor del Gráfico
            const chartHtml = `
                <h3>Gráfico de Flujo de Caja por Día</h3>
                <div class="chart-container">
                    <canvas id="flujoCajaChart"></canvas>
                </div>
            `;
            
            reporteContent.innerHTML = metricCardsHtml + chartHtml;
            
            // 3. Renderizar Gráfico
            if (myChart) {
                myChart.destroy(); // Destruir instancia anterior
            }
            
            const ctx = document.getElementById('flujoCajaChart').getContext('2d');
            myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: reporte.grafico.map(item => item.fecha),
                    datasets: [{
                        label: 'Ingreso Diario (Bs)',
                        data: reporte.grafico.map(item => item.ingreso),
                        borderColor: '#0b2e88',  // Azul más oscuro y sólido
                        backgroundColor: 'rgba(11, 46, 136, 0.25)',  // Fill más visible
                        borderWidth: 3,  // Línea más gruesa
                        pointRadius: 5,  // Puntos más grandes
                        pointBackgroundColor: '#0b2e88',  // Puntos azules sólidos
                        pointBorderColor: '#fff',  // Borde blanco para contraste
                        pointBorderWidth: 2,
                        pointHoverRadius: 7,  // Puntos aún más grandes al hover
                        tension: 0.3  // Curva suave
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Monto (Bs)',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                color: '#333'
                            },
                            ticks: {
                                color: '#555',
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)',
                                lineWidth: 1
                            }
                        },
                        x: {
                            ticks: {
                                color: '#555',
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                lineWidth: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: '#333',
                                font: {
                                    size: 13,
                                    weight: 'bold'
                                },
                                padding: 15
                            }
                        },
                        title: {
                            display: true,
                            text: `Flujo de Caja ${reporte.tipo} (${reporte.fecha_inicio} a ${reporte.fecha_fin})`,
                            font: {
                                size: 16,
                                weight: 'bold'
                            },
                            color: '#0b2e88',
                            padding: 20
                        }
                    }
                }
            });
            
            // 4. Mostrar el botón de exportar después de generar el contenido
            document.getElementById('export-btn-group').style.display = 'flex';
        }

        // ----------------------------------------------------
        // II. FUNCIÓN DE EXPORTACIÓN A PDF
        // ----------------------------------------------------
        
        function exportReporteToPDF() {
            // Obtenemos la instancia de jsPDF de la ventana global (umd.min.js)
            const { jsPDF } = window.jspdf;
            const element = document.getElementById('reporte-content');
            
            // Temporalmente, ocultar el botón de exportar para que no aparezca en el PDF
            const exportBtnGroup = document.getElementById('export-btn-group');
            exportBtnGroup.style.display = 'none'; 

            // Temporalmente, ocultar el botón de generar (si está visible)
            document.getElementById('btn-generar').style.display = 'none';
            
            // 1. Usar html2canvas para capturar el contenido como imagen
            html2canvas(element, {
                scale: 2, // Mayor escala = mejor calidad de imagen
                useCORS: true, 
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');
                const imgWidth = 210; // Ancho A4 en mm
                const pageHeight = 295; // Alto A4 en mm
                const imgHeight = canvas.height * imgWidth / canvas.width;
                let heightLeft = imgHeight;
                let position = 0;

                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                // 2. Manejar múltiples páginas si el contenido es largo
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                
                // 3. Nombrar y guardar el archivo
                const tipoReporte = document.getElementById('tipo_reporte').value;
                const fechaDesde = document.getElementById('fecha_desde').value;
                const fechaHasta = document.getElementById('fecha_hasta').value;
                const filename = `Reporte_Flujo_Caja_${tipoReporte}_${fechaDesde}_a_${fechaHasta}.pdf`;

                pdf.save(filename);
                
                // 4. Volver a mostrar los botones
                exportBtnGroup.style.display = 'flex';
                document.getElementById('btn-generar').style.display = 'flex';
            }).catch(err => {
                alert("Error al exportar el reporte a PDF. Intente nuevamente.");
                console.error("PDF Export Error:", err);
                // Asegurar que los botones vuelvan a ser visibles en caso de error
                document.getElementById('export-btn-group').style.display = 'flex';
                document.getElementById('btn-generar').style.display = 'flex';
            });
        }


        // ----------------------------------------------------
        // III. INICIALIZACIÓN
        // ----------------------------------------------------
        
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const lastWeek = new Date(today);
            lastWeek.setDate(today.getDate() - 7);
            
            // Formatear fechas a YYYY-MM-DD
            const formatDate = (date) => date.toISOString().split('T')[0];

            document.getElementById('fecha_desde').value = formatDate(lastWeek);
            document.getElementById('fecha_hasta').value = formatDate(today);
            
            // TEMA OSCURO
            const themeToggle = document.getElementById('theme-toggle');
            const htmlElement = document.documentElement;
            const themeIcon = themeToggle.querySelector('i');
            const savedTheme = localStorage.getItem('theme') || 'light';
            htmlElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
            themeToggle.addEventListener('click', () => {
                const currentTheme = htmlElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                htmlElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
            });
            function updateThemeIcon(theme) {
                if (theme === 'dark') {
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                } else {
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                }
            }
            
            // Cargar el reporte inicial
            fetchReporteData('diario', formatDate(lastWeek), formatDate(today));
        });
    </script>
</body>
</html>