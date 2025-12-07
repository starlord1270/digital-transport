<?php
/**
 * Archivo: historial-viaje.php
 * Descripción: Muestra el historial de viajes del pasajero.
 * Incluye lógica de sesión para mostrar el menú dinámico y el saldo.
 * * NOTA: Para que esta página funcione correctamente, el backend
 * debe proporcionar un archivo en la ruta '../backend/fetch_history.php'
 * que devuelva un JSON con el siguiente formato:
 * {
 * "total_trips": 56,
 * "total_spent": 140.00,
 * "history": [
 * {
 * "date": "2025-11-23", 
 * "time": "14:15", 
 * "line": "Línea 207 (Av. América)", 
 * "details": "Cobro Tarifa Estándar", 
 * "amount": 2.50, 
 * "status": "Completado"
 * },
 * // ... más viajes ...
 * ]
 * }
 */

// 1. GESTIÓN DE SESIONES
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🛑 LÓGICA DE VERIFICACIÓN DE SESIÓN PARA PASAJERO 🛑
// Incluimos los roles de pasajero (asumiendo 1, 2, 5, 6)
$user_is_logged_in = (
    isset($_SESSION['usuario_id']) && 
    isset($_SESSION['tipo_usuario_id']) && 
    in_array($_SESSION['tipo_usuario_id'], [1, 2, 5, 6])
);

$nombre_usuario = $user_is_logged_in ? htmlspecialchars($_SESSION['nombre_completo'] ?? 'Pasajero') : 'Invitado';
$user_balance = $user_is_logged_in ? ($_SESSION['saldo'] ?? 0.00) : 0.00;

// 🛑 2025-12-07: ACTUALIZACIÓN DE SALDO EN TIEMPO REAL 🛑
if ($user_is_logged_in) {
    require_once '../backend/bd.php'; // Ajusta la ruta si es necesario
    
    if (isset($conn)) {
        $stmt_bal = $conn->prepare("SELECT saldo FROM USUARIO WHERE usuario_id = ?");
        $stmt_bal->bind_param("i", $_SESSION['usuario_id']);
        $stmt_bal->execute();
        $res_bal = $stmt_bal->get_result();
        
        if ($res_bal->num_rows > 0) {
            $row_bal = $res_bal->fetch_assoc();
            $real_balance = floatval($row_bal['saldo']);
            
            // Actualizamos sesión y variable local
            $_SESSION['saldo'] = $real_balance;
            $user_balance = $real_balance;
        }
        $stmt_bal->close();
    }
}
// 🛑 FIN ACTUALIZACIÓN 🛑

// REDIRECCIÓN: Si el usuario no está logueado, no debería ver su historial.
if (!$user_is_logged_in) {
    // Guarda la URL actual para redirigir después del login
    $_SESSION['redirect_to'] = 'historial-viaje.php';
    header("Location: inicio-sesion-usuarios.php");
    exit();
}
// 🛑 FIN LÓGICA DE SESIÓN 🛑
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Historial de Viajes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Librerías para exportar PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        /* Estilos CSS (Se mantienen incrustados por simplicidad) */
        :root {
            --color-primary: #0b2e88;
            --color-secondary: #1e88e5;
            --color-text-dark: #333;
            --color-background-light: #f4f7f9;
            --color-success: #4caf50;
            --color-border: #eee;
            --color-completed: #1e88e5;
            
            --bg-primary: #ffffff;
            --bg-secondary: #f4f7f9;
            --text-primary: #333;
            --text-secondary: #666;
            --border-color: #eee;
            --card-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] {
            --color-background-light: #1a1a1a;
            --bg-primary: #1e1e1e;
            --bg-secondary: #2a2a2a;
            --text-primary: #e0e0e0;
            --text-secondary: #b0b0b0;
            --border-color: #404040;
            --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s, color 0.3s;
        }
        
        /* --- Header / Menú Superior (Común) --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background-color: var(--bg-primary);
            box-shadow: var(--card-shadow);
            border-bottom: 1px solid var(--border-color);
            width: 100%;
            box-sizing: border-box;
        }
        .logo { font-size: 1.2em; font-weight: bold; color: var(--color-primary); }
        .nav-menu { display: flex; gap: 20px; }
        .nav-item { color: var(--text-primary); text-decoration: none; padding: 5px 10px; border-radius: 5px; font-size: 0.95em; transition: background-color 0.2s; }
        .nav-item.active { background-color: var(--bg-secondary); font-weight: 500; }
        
        .theme-toggle {
            background: none;
            border: none;
            font-size: 1.3em;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.2s;
            color: var(--text-primary);
        }
        .theme-toggle:hover {
            background-color: var(--bg-secondary);
        }
        .saldo { font-size: 1em; color: var(--color-text-dark); font-weight: 600; }

        /* --- Contenido Principal de la Vista --- */
        .page-content-wrapper {
            flex-grow: 1;
            padding: 20px 0 50px 0;
        }
        .main-content {
            max-width: 900px; 
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        /* --- Encabezado y Botón de Exportar --- */
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .history-header h1 {
            font-size: 1.4em;
            color: var(--color-text-dark);
            margin: 0;
        }
        .history-header p {
            font-size: 0.85em;
            color: #666;
            margin: 5px 0 0 0;
        }
        .btn-export {
            background-color: var(--color-secondary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.2s;
        }
        .btn-export:hover {
            background-color: #1a76c3;
        }

        /* --- Tarjetas de Resumen (Estadísticas) --- */
        .summary-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            flex: 1;
            background-color: #f8f8f8;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 20px;
        }
        .summary-card h4 {
            font-size: 0.9em;
            color: #666;
            margin: 0 0 10px 0;
        }
        .summary-card h2 {
            font-size: 1.8em;
            font-weight: 600;
            color: var(--color-text-dark);
            margin: 0;
        }
        .summary-card .change {
            font-size: 0.8em;
            color: var(--color-success);
            margin-top: 5px;
        }
        .summary-card .change i {
            margin-right: 3px;
        }

        /* --- Lista de Viajes --- */
        .trip-group {
            margin-bottom: 25px;
        }
        .trip-date {
            font-size: 1em;
            font-weight: 600;
            color: var(--color-text-dark);
            margin-bottom: 10px;
            display: block;
        }
        
        .trip-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }
        
        .trip-details {
            display: flex;
            align-items: flex-start;
        }
        
        .trip-icon {
            font-size: 1.4em;
            color: var(--color-secondary);
            margin-right: 15px;
        }
        
        .trip-info h3 {
            font-size: 1em;
            font-weight: 500;
            color: var(--color-text-dark);
            margin: 0 0 5px 0;
        }
        
        .trip-info p {
            font-size: 0.85em;
            color: #999;
            margin: 0;
        }
        
        .trip-amount {
            font-size: 1.1em;
            font-weight: 600;
            color: var(--color-text-dark);
            text-align: right;
        }
        
        .trip-status {
            font-size: 0.8em;
            color: var(--color-completed);
            margin-top: 3px;
        }

        /* --- Footer (Simulación) --- */
        .footer {
            text-align: center;
            padding: 20px;
            background-color: white;
            color: #666;
            font-size: 0.85em;
            margin-top: auto;
            border-top: 1px solid #eee;
        }

        @media (max-width: 768px) {
            .summary-cards { flex-direction: column; }
            .history-header { flex-direction: column; align-items: flex-start; }
            .btn-export { margin-top: 15px; width: 100%; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">
            Digital Transport
            <span style="font-size: 0.7em; display: block; font-weight: normal; color: #666;">Sistema de Boletos Digitales</span>
        </div>
        
        <nav class="nav-menu">
            <a href="index.php" class="nav-item">Inicio</a>
            <a href="recarga-digital.php" class="nav-item">Recarga</a>
            <a href="puntos-recarga.php" class="nav-item">Puntos PR</a>
            <a href="historial-viaje.php" class="nav-item active">Historial</a> 
            <a href="perfil-pasajero.php" class="nav-item">
                <i class="fas fa-user-circle"></i> Perfil
            </a>
            <a href="../backend/logout.php?redirect=historial-viaje.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </nav>
        
        <button class="theme-toggle" id="theme-toggle" title="Cambiar tema">
            <i class="fas fa-moon"></i>
        </button>
        
        <div class="saldo">
            <span style="margin-right: 15px; font-weight: 500;">¡Hola, <?php echo $nombre_usuario; ?>!</span>
            Saldo: Bs. <?php echo number_format($user_balance, 2); ?>
        </div>
    </header>

    <div class="page-content-wrapper">
        <div class="main-content">
            
            <div class="history-header">
                <div>
                    <h1>Historial de Viajes</h1>
                    <p>Últimos 30 días</p>
                </div>
                <button class="btn-export" onclick="exportHistoryToPDF()">
                    <i class="fas fa-file-pdf"></i> Exportar a PDF
                </button>
            </div>

            <div class="summary-cards">
                <div class="summary-card">
                    <h4>Total de Viajes</h4>
                    <h2 class="total-trips-value">0</h2> 
                    <div class="change">
                        <i class="fas fa-arrow-up"></i> +12% vs mes anterior
                    </div>
                </div>
                <div class="summary-card">
                    <h4>Gasto Total</h4>
                    <h2 class="total-spent-value">Bs. 0.00</h2> 
                    <p style="font-size: 0.8em; color: #999;" class="total-spent-detail">Últimos 30 días</p>
                </div>
            </div>

            <section class="trip-list">
                <p style="text-align: center; color: var(--color-secondary);"><i class="fas fa-spinner fa-spin"></i> Cargando historial...</p>
            </section>
            
        </div>
    </div>

    <footer class="footer">
        © 2025 Digital Transport - Sistema de Boletos Digital
    </footer>

    <script>
        // ----------------------------------------------------
        // I. CONFIGURACIÓN Y REFERENCIAS
        // ----------------------------------------------------
        // 🛑 RUTA AL BACKEND 🛑
        // Este script depende de un archivo PHP en el backend para obtener los datos.
        const API_URL = '../backend/fetch_history.php'; 
        
        const tripListContainer = document.querySelector('.trip-list');
        const totalTripsElement = document.querySelector('.total-trips-value');
        const totalSpentElement = document.querySelector('.total-spent-value');
        
        // ----------------------------------------------------
        // II. UTILIDADES Y RENDERIZADO
        // ----------------------------------------------------
        
        /** Agrupa los viajes por fecha. */
        function groupTripsByDate(trips) {
            return trips.reduce((acc, trip) => {
                // Asume que la propiedad 'date' del objeto trip es la fecha (ej: "YYYY-MM-DD")
                const date = trip.date; 
                if (!acc[date]) {
                    acc[date] = [];
                }
                acc[date].push(trip);
                return acc;
            }, {});
        }

        /** Formatea la fecha para el encabezado del grupo. */
        function formatGroupDate(dateString, tripCount) {
            const date = new Date(dateString);
            // Mostrar fecha en formato legible
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            const formattedDate = date.toLocaleDateString('es-ES', options);
            
            const countText = `${tripCount} ${tripCount === 1 ? 'viaje' : 'viajes'}`;
            return `${formattedDate} <span style="font-weight: 400; color: #999;">(${countText})</span>`;
        }

        /** Crea el HTML para un ítem de viaje. */
        function createTripItemHTML(trip) {
            // Aseguramos que el monto tenga dos decimales
            const formattedAmount = parseFloat(trip.amount).toFixed(2);
            
            return `
                <div class="trip-item">
                    <div class="trip-details">
                        <div class="trip-icon"><i class="fas fa-bus-alt"></i></div>
                        <div class="trip-info">
                            <h3>${trip.line} <span style="font-weight: 400; color: #999;">${trip.time}</span></h3>
                            <p>${trip.details}</p>
                        </div>
                    </div>
                    <div class="trip-amount">
                        Bs. ${formattedAmount}
                        <div class="trip-status">${trip.status}</div>
                    </div>
                </div>
            `;
        }

        /** Renderiza el historial completo en la vista. */
        function renderHistory(data) {
            const historyGroups = groupTripsByDate(data.history);
            let historyHTML = '';
            
            // 1. Actualizar tarjetas de resumen
            totalTripsElement.textContent = data.total_trips;
            // Asegurar el formato de moneda en el total
            totalSpentElement.textContent = `Bs. ${parseFloat(data.total_spent).toFixed(2)}`;
            
            // 2. Generar HTML agrupado
            // Ordenar las fechas de forma descendente (más reciente primero)
            const sortedDates = Object.keys(historyGroups).sort((a, b) => new Date(b) - new Date(a));
            
            if (sortedDates.length === 0) {
                tripListContainer.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No se encontraron viajes en los últimos 30 días.</p>';
                return;
            }

            sortedDates.forEach(date => {
                const trips = historyGroups[date];
                const formattedDate = formatGroupDate(date, trips.length);
                
                let itemsHTML = '';
                // Los viajes dentro del grupo pueden ordenarse por hora para ser precisos
                trips.sort((a, b) => (new Date(`${a.date} ${a.time}`) < new Date(`${b.date} ${b.time}`) ? 1 : -1));
                
                trips.forEach(trip => {
                    itemsHTML += createTripItemHTML(trip);
                });
                
                historyHTML += `
                    <div class="trip-group">
                        <span class="trip-date">${formattedDate}</span>
                        ${itemsHTML}
                    </div>
                `;
            });
            
            tripListContainer.innerHTML = historyHTML;
        }

        // ----------------------------------------------------
        // III. FETCH DE DATOS
        // ----------------------------------------------------

        async function fetchAndRenderHistory() {
            tripListContainer.innerHTML = '<p style="text-align: center; color: var(--color-secondary);"><i class="fas fa-spinner fa-spin"></i> Cargando historial...</p>';
            try {
                // Se envía el request al archivo PHP en el backend
                const response = await fetch(API_URL);
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.error) {
                    // Si el backend devuelve un error explícito (ej. "No hay sesión")
                    throw new Error(data.error);
                }
                
                renderHistory(data);
                
            } catch (error) {
                console.error("Error al cargar el historial:", error);
                // Mostrar un mensaje de error más claro en la interfaz
                tripListContainer.innerHTML = `<p style="color: red; text-align: center;">Error al cargar el historial del servidor. Verifique <code>${API_URL}</code> y asegúrese de que la sesión del usuario está activa.</p>`;
                // Resetear sumarios a cero en caso de fallo
                totalTripsElement.textContent = 0;
                totalSpentElement.textContent = 'Bs. 0.00';
            }
        }

        // ----------------------------------------------------
        // IV. FUNCIÓN DE EXPORTACIÓN A PDF
        // ----------------------------------------------------
        
        function exportHistoryToPDF() {
            const { jsPDF } = window.jspdf;
            const contentToExport = document.querySelector('.main-content');
            const exportButton = document.querySelector('.btn-export');
            
            // Ocultar temporalmente el botón de exportar
            exportButton.style.display = 'none';
            
            // Capturar el contenido como imagen
            html2canvas(contentToExport, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');
                const imgWidth = 210; // A4 width in mm
                const pageHeight = 295; // A4 height in mm
                const imgHeight = canvas.height * imgWidth / canvas.width;
                let heightLeft = imgHeight;
                let position = 0;

                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                // Múltiples páginas si es necesario
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                // Generar nombre del archivo
                const today = new Date().toISOString().split('T')[0];
                const filename = `Historial_Viajes_<?php echo str_replace(' ', '_', $nombre_usuario); ?>_${today}.pdf`;
                
                pdf.save(filename);
                
                // Volver a mostrar el botón
                exportButton.style.display = 'flex';
            }).catch(err => {
                alert('Error al exportar el PDF. Intente nuevamente.');
                console.error('PDF Export Error:', err);
                exportButton.style.display = 'flex';
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchAndRenderHistory();
        });
        
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
        
    </script>

</body>
</html>