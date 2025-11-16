<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Mis Boletos</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <script src="qrcode.min.js"></script>

    <style>
        :root {
            --color-primary: #0b2e88;
            --color-secondary: #1e88e5;
            --color-text-dark: #333;
            --color-background-light: #f4f7f9;
            --color-border: #eee;
            --color-active: #4caf50; /* Verde para activo */
            --color-qr-placeholder: #ccc;
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
            max-width: 600px; /* Tamaño ideal para un boleto */
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        /* --- Título de la Sección --- */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 20px;
        }
        .section-header h1 {
            font-size: 1.4em;
            color: var(--color-text-dark);
            margin: 0;
        }
        .section-header p {
            font-size: 0.85em;
            color: #666;
            margin: 0;
        }
        .active-count {
            font-weight: 600;
            color: var(--color-text-dark);
            font-size: 1em;
        }
        
        /* --- Estilo de Filtros --- */
        .filter-controls button {
            padding: 8px 15px; 
            border: 1px solid #ccc; 
            background-color: white; 
            border-radius: 5px; 
            cursor: pointer; 
            transition: all 0.2s;
            font-size: 0.9em;
            font-weight: 500;
        }
        .filter-controls button.active {
            background-color: #e3f2fd !important;
            border-color: var(--color-secondary) !important;
            color: var(--color-secondary);
        }

        /* --- Tarjeta de Boleto --- */
        .ticket-card {
            background-color: white;
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px dashed var(--color-border);
            padding-bottom: 15px;
        }

        .ticket-title h3 {
            font-size: 1.1em;
            color: var(--color-text-dark);
            margin: 0;
            font-weight: 600;
        }
        .ticket-title p {
            font-size: 0.9em;
            color: #666;
            margin: 3px 0 0 0;
        }

        .status-tag {
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8em;
            font-weight: 600;
        }
        .ticket-id {
            font-size: 0.8em;
            color: #999;
            margin-top: 5px;
            text-align: right;
        }

        .ticket-details p {
            font-size: 0.9em;
            color: #666;
            margin: 5px 0;
        }

        /* --- Barra de Progreso --- */
        .usage-bar {
            margin: 15px 0;
        }
        .usage-bar p {
            font-size: 0.85em;
            color: var(--color-text-dark);
            margin-bottom: 5px;
        }
        .progress-container {
            height: 8px;
            background-color: var(--color-border);
            border-radius: 4px;
            overflow: hidden;
        }
        
        /* --- Área de QR --- */
        .qr-toggle {
            text-align: center;
            margin: 20px 0;
        }
        .qr-toggle button {
            background: none;
            border: none;
            color: var(--color-secondary);
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 500;
            padding: 5px;
        }

        .qr-display {
            text-align: center;
            padding: 20px 0;
            border-top: 1px dashed var(--color-border);
        }
        
        /* Contenedor del QR (DIV) */
        .qr-code {
            width: 150px;
            height: 150px;
            margin: 10px auto;
        }
        
        /* 🛑 REGLA ELIMINADA: Ya no se aplica estilos al canvas interno. */

        .qr-code-verification {
            font-size: 0.8em;
            color: #999;
            margin-top: 10px;
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

        @media (max-width: 600px) {
            .main-content {
                padding: 10px;
            }
            .ticket-card {
                padding: 15px;
            }
            .filter-controls {
                flex-wrap: wrap;
            }
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
        </nav>
        <div class="saldo">
            Saldo: **$125.50** A
        </div>
    </header>

    <div class="page-content-wrapper">
        <div class="main-content">
            
            <header class="section-header">
                <div>
                    <h1>Mis Boletos</h1>
                    <p>Boletos activos y disponibles</p>
                </div>
                <div class="active-count">
                    </div>
            </header>

            </div>
    </div>

    <footer class="footer">
        © 2025 Digital Transport - Sistema de Boletos Digital
    </footer>

    <script>
        // ----------------------------------------------------
        // I. DATOS DINÁMICOS: FETCH
        // ----------------------------------------------------

        // 🛑 RUTA CORREGIDA 🛑
        // Si el archivo actual está en 'frontend/' y el PHP está en 'backend/', 
        // necesitamos ir un nivel hacia atrás (..) y luego entrar en 'backend/'.
        const API_URL = '../backend/fetch_tickets.php'; 
        
        let allTicketsData = []; // Variable para almacenar los datos de la BD una vez cargados

        // Contenedor principal para renderizar los boletos
        const ticketListContainer = document.createElement('div');
        ticketListContainer.id = 'ticket-list';
        document.querySelector('.main-content').appendChild(ticketListContainer);

        /**
         * Obtiene los datos de los boletos del backend y luego renderiza.
         */
        async function fetchAndRenderTickets(filterStatus = 'Activos') {
            const container = document.getElementById('ticket-list');
            if (allTicketsData.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: var(--color-secondary);"><i class="fas fa-spinner fa-spin"></i> Cargando boletos...</p>';
                try {
                    // 🛑 AQUÍ SE HACE LA LLAMADA AL NUEVO API_URL 🛑
                    const response = await fetch(API_URL);
                    if (!response.ok) {
                        // Si el archivo existe pero da un error 500 o 404
                        throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
                    }
                    allTicketsData = await response.json();
                } catch (error) {
                    console.error("No se pudieron cargar los boletos:", error);
                    container.innerHTML = `<p style="color: red; text-align: center;">Error al cargar boletos del servidor. Verifique la ruta (${API_URL}) y el script PHP.</p>`;
                    allTicketsData = []; 
                    return;
                }
            }
            
            // Llama a renderTickets con los datos dinámicos
            renderTickets(filterStatus, allTicketsData); 
        }

        // ----------------------------------------------------
        // II. LÓGICA DE RENDERIZADO Y UTILIDADES
        // ----------------------------------------------------
        
        // ... (el resto de las funciones: formatDate, getStatusInfo, generateQRCode, createTicketHTML) ...

        /**
         * Formatea la fecha de vencimiento. 
         */
        function formatDate(isoString) {
            const date = new Date(isoString);
            const now = new Date();
            if (date.toDateString() === now.toDateString()) {
                return 'Hoy ' + date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            }
            return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        /**
         * Determina el color y texto de la etiqueta de estado.
         */
        function getStatusInfo(status) {
            let color = '';
            let text = status;
            switch (status) {
                case 'Activo': color = '#4caf50'; break;
                case 'Próximo': color = '#ff9800'; text = 'Disponible'; break;
                case 'Usado':
                case 'Vencido': color = '#999'; break;
                default: color = '#333';
            }
            return { color, text };
        }

        /**
         * Dibuja un código QR real en un elemento div usando qrcode.js.
         */
        function generateQRCode(elementId, data) {
            const container = document.getElementById(elementId);
            if (typeof QRCode === 'undefined' || !container) {
                if (container) container.innerHTML = '<div style="color:red; font-size: 0.8em; line-height: 150px;">Error: QR Library Missing</div>';
                return;
            }
            if (container) container.innerHTML = '';
            
            new QRCode(container, {
                text: data,
                width: 150,
                height: 150,
                colorDark: 'var(--color-primary)', 
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        }
        
        /**
         * Crea el HTML para un solo boleto. 
         */
        function createTicketHTML(ticket) {
             const statusInfo = getStatusInfo(ticket.status);
            const isFinished = ticket.usedUses >= ticket.totalUses || ticket.status === 'Usado' || ticket.status === 'Vencido';
            const progress = (ticket.usedUses / ticket.totalUses) * 100;
            const toggleId = `toggle-qr-button-${ticket.id}`;
            const qrAreaId = `qr-area-${ticket.id}`;
            const qrCanvasId = `qr-canvas-${ticket.id}`;
            const opacityStyle = isFinished ? 'opacity: 0.6;' : '';

            const html = `
                <div class="ticket-card" style="${opacityStyle}">
                    <div class="ticket-header">
                        <div class="ticket-title">
                            <h3>${ticket.line}</h3>
                            <p>${ticket.type}</p>
                        </div>
                        <div>
                            <span class="status-tag" style="background-color: ${statusInfo.color};">${statusInfo.text}</span>
                            <div class="ticket-id">${ticket.id}</div>
                        </div>
                    </div>

                    <div class="ticket-details">
                        <p>Válido hasta: **${formatDate(ticket.expires)}**</p>
                    </div>

                    <div class="usage-bar">
                        <p>Viajes utilizados</p>
                        <div class="progress-container">
                            <div class="progress-fill" style="width: ${progress}%; background-color: ${statusInfo.color};"></div>
                        </div>
                        <p style="text-align: right; color: var(--color-text-dark);">**${ticket.usedUses}** / ${ticket.totalUses}</p>
                    </div>
                    
                    ${!isFinished ? `
                    <div class="qr-toggle">
                        <button id="${toggleId}">
                            Ocultar Código QR <i class="fas fa-angle-up"></i>
                        </button>
                    </div>

                    <div id="${qrAreaId}" class="qr-display" style="display: none;">
                        <div id="${qrCanvasId}" class="qr-code" title="Código QR para validación"></div>
                        
                        <div class="qr-code-verification">
                            Código de verificación
                            <span style="font-weight: 600; color: var(--color-secondary); display: block;">${ticket.qrData.slice(-12).match(/.{1,4}/g).join(' ')}</span>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
            return html;
        }


        /**
         * Renderiza la lista completa de boletos.
         */
        function renderTickets(filterStatus, ticketsData) {
            const container = document.getElementById('ticket-list');
            
            // 1. Filtrar los datos
            const filteredTickets = ticketsData.filter(ticket => {
                const isActiveOrUpcoming = ticket.status === 'Activo' || ticket.status === 'Próximo';
                const isUsedOrExpired = ticket.status === 'Usado' || ticket.status === 'Vencido';
                
                if (filterStatus === 'Todos') return true;
                if (filterStatus === 'Activos' && isActiveOrUpcoming) return true;
                if (filterStatus === 'Usados' && isUsedOrExpired) return true;
                return false;
            });

            // Ordenar: Activos/Próximos primero, luego Usados/Vencidos
            filteredTickets.sort((a, b) => {
                const statusA = (a.status === 'Activo' || a.status === 'Próximo') ? 0 : 1;
                const statusB = (b.status === 'Activo' || b.status === 'Próximo') ? 0 : 1;
                return statusA - statusB;
            });


            // 2. Generar el HTML
            let ticketsHTML = '';
            if (filteredTickets.length === 0) {
                 ticketsHTML = '<p style="text-align: center; color: #666; padding: 20px;">No tienes boletos disponibles en este filtro.</p>';
            } else {
                 filteredTickets.forEach((ticket) => {
                    ticketsHTML += createTicketHTML(ticket); 
                });
            }

            container.innerHTML = ticketsHTML;
            
            // 3. Actualizar el contador de boletos activos (usando todos los datos)
            const activeCount = ticketsData.filter(t => t.status === 'Activo' || t.status === 'Próximo').length;
            document.querySelector('.active-count').textContent = `${activeCount} activos`;

            // 4. Adjuntar event listeners y generar QRs
            filteredTickets.forEach((ticket) => {
                const toggleId = `toggle-qr-button-${ticket.id}`;
                const qrAreaId = `qr-area-${ticket.id}`;
                const qrCanvasId = `qr-canvas-${ticket.id}`;
                const isFinished = ticket.usedUses >= ticket.totalUses || ticket.status === 'Usado' || ticket.status === 'Vencido';

                if (!isFinished) {
                    const toggleButton = document.getElementById(toggleId);
                    const qrArea = document.getElementById(qrAreaId);
                    const qrElement = document.getElementById(qrCanvasId);

                    if (qrArea && toggleButton && qrElement) {
                        
                        // Generar el código QR 
                        generateQRCode(qrCanvasId, ticket.qrData);

                        let isQrVisible = false; 
                        qrArea.style.display = 'none';
                        toggleButton.innerHTML = 'Mostrar Código QR <i class="fas fa-angle-down"></i>';

                        toggleButton.addEventListener('click', function() {
                            if (isQrVisible) {
                                qrArea.style.display = 'none';
                                toggleButton.innerHTML = 'Mostrar Código QR <i class="fas fa-angle-down"></i>';
                            } else {
                                qrArea.style.display = 'block';
                                toggleButton.innerHTML = 'Ocultar Código QR <i class="fas fa-angle-up"></i>';
                            }
                            isQrVisible = !isQrVisible;
                        });
                    }
                }
            });
        }
        
        // ----------------------------------------------------
        // III. IMPLEMENTACIÓN DE FILTROS
        // ----------------------------------------------------

        document.addEventListener('DOMContentLoaded', () => {
            
            // Insertamos el área de filtros
            const filterHTML = `
                <div class="filter-controls" style="margin-bottom: 20px; display: flex; gap: 10px;">
                    <button class="filter-btn" data-filter="Activos">Activos/Próximos</button>
                    <button class="filter-btn" data-filter="Usados">Usados/Vencidos</button>
                    <button class="filter-btn" data-filter="Todos">Todos</button>
                </div>
            `;
            const mainContent = document.querySelector('.main-content');
            // Asegura que el contenedor de lista exista antes de intentar agregar filtros
            if (mainContent && document.querySelector('.section-header')) {
                document.querySelector('.section-header').insertAdjacentHTML('afterend', filterHTML);
            }

            // Lógica de filtros
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(button => {
                
                // Establecer el filtro inicial y su estilo
                if(button.dataset.filter === 'Activos') {
                    button.classList.add('active'); 
                }

                button.addEventListener('click', function() {
                    // Limpiar estilos y estado de todos los botones
                    filterButtons.forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    // Establecer estilo activo en el botón clickeado
                    this.classList.add('active');
                    
                    // Llamada al nuevo FETCH 
                    fetchAndRenderTickets(this.dataset.filter);
                });
            });

            // Llamada inicial para cargar el contenido de boletos 'Activos'
            fetchAndRenderTickets('Activos'); 
        });
        
    </script>
</body>
</html>