<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Historial de Viajes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--color-background-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* --- Header / Menú Superior (Común) --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            width: 100%;
            box-sizing: border-box;
        }
        .logo { font-size: 1.2em; font-weight: bold; color: var(--color-primary); }
        .nav-menu { display: flex; gap: 20px; }
        .nav-item { color: #666; text-decoration: none; padding: 5px 10px; border-radius: 5px; font-size: 0.95em; transition: background-color 0.2s, color 0.2s; }
        .nav-item.active { background-color: #f0f0f0; color: var(--color-text-dark); font-weight: 500; }
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
            <a href="registro-usuarios.php" class="nav-item">Registro</a>
            <a href="recarga-digital.php" class="nav-item">Recarga</a>
            <a href="puntos-recarga.php" class="nav-item">Puntos PR</a>
            <a href="mis-boletos.php" class="nav-item">Boletos</a>
            <a href="historial-viaje.php" class="nav-item active">Historial</a> 
        <div class="saldo">
            Saldo: **$125.50** A
        </div>
    </header>

    <div class="page-content-wrapper">
        <div class="main-content">
            
            <div class="history-header">
                <div>
                    <h1>Historial de Viajes</h1>
                    <p>Últimos 30 días</p>
                </div>
                <button class="btn-export">
                    <i class="fas fa-file-export"></i> Exportar
                </button>
            </div>

            <div class="summary-cards">
                <div class="summary-card">
                    <h4>Total de Viajes</h4>
                    <h2 class="total-trips-value">0</h2> <div class="change">
                        <i class="fas fa-arrow-up"></i> +12% vs mes anterior
                    </div>
                </div>
                <div class="summary-card">
                    <h4>Gasto Total</h4>
                    <h2 class="total-spent-value">Bs. 0.00</h2> <p style="font-size: 0.8em; color: #999;" class="total-spent-detail">Últimos 30 días</p>
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
        // Asumiendo que historial_viajes.php está en frontend/
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
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            const formattedDate = date.toLocaleDateString('es-ES', options);
            const countText = `${tripCount} ${tripCount === 1 ? 'viaje' : 'viajes'}`;
            return `${formattedDate} <span style="font-weight: 400; color: #999;">(${countText})</span>`;
        }

        /** Crea el HTML para un ítem de viaje. */
        function createTripItemHTML(trip) {
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
                        Bs. ${trip.amount.toFixed(2)}
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
            totalSpentElement.textContent = `Bs. ${data.total_spent}`;
            
            // 2. Generar HTML agrupado
            const sortedDates = Object.keys(historyGroups).sort((a, b) => new Date(b) - new Date(a));
            
            if (sortedDates.length === 0) {
                tripListContainer.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">No se encontraron viajes en los últimos 30 días.</p>';
                return;
            }

            sortedDates.forEach(date => {
                const trips = historyGroups[date];
                const formattedDate = formatGroupDate(date, trips.length);
                
                let itemsHTML = '';
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
                const response = await fetch(API_URL);
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
                }
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                renderHistory(data);
                
            } catch (error) {
                console.error("Error al cargar el historial:", error);
                // Mostrar un mensaje de error más claro en la interfaz
                tripListContainer.innerHTML = `<p style="color: red; text-align: center;">Error al cargar el historial del servidor. Verifique ${API_URL}.</p>`;
                // Resetear sumarios a cero en caso de fallo
                totalTripsElement.textContent = 0;
                totalSpentElement.textContent = 'Bs. 0.00';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchAndRenderHistory();
        });
        
    </script>

</body>
</html>