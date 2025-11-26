<?php
// cobro-chofer.php

// 1. Iniciar la sesión para acceder a los datos
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 🛑 INICIO DE VERIFICACIÓN DE SEGURIDAD Y ROL (RBAC) 🛑
// Tipo de usuario esperado para esta página: 3 (CHOFER)
$user_type_expected = 3;
$user_type_actual = isset($_SESSION['tipo_usuario_id']) ? $_SESSION['tipo_usuario_id'] : 0;

$es_chofer_logueado = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && $user_type_actual == $user_type_expected;

if (!$es_chofer_logueado) {
    // Si no es un Chofer logueado, destruir la sesión y redirigir.
    session_unset();
    session_destroy();
    
    $login_url = '/Competencia-Analisis/digital-transport/frontend/inicio-sesion-lineas-choferes/login.php';
    header("Location: " . $login_url . "?error=acceso_no_autorizado");
    exit;
}

// 2. Obtener el nombre
$nombre_chofer = "Chofer"; // Valor por defecto
if (isset($_SESSION['nombre_completo'])) {
    $nombre_completo = $_SESSION['nombre_completo'];
    
    // Obtener solo el nombre y el primer apellido
    $partes_nombre = explode(' ', $nombre_completo);
    
    // Tomar el primer elemento (Nombre) y el segundo (Primer Apellido)
    if (count($partes_nombre) >= 2) {
        $nombre_chofer = $partes_nombre[0] . ' ' . $partes_nombre[1];
    } else {
        $nombre_chofer = $partes_nombre[0];
    }
} 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Módulo de Cobro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        :root {
            --color-primary: #0b2e88;
            --color-secondary: #1e88e5;
            --color-student: #4caf50; /* Verde de Estudiante/3ra edad */
            --color-adulto: #1e88e5; /* Azul de Adulto */
            --color-success: #4caf50;
            --color-error: #f44336;
            --color-text-dark: #333;
            --color-background-dark: #222b40;
            --color-background-light: #f4f7f9;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--color-background-dark);
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 50px;
        }

        /* --- Header y Recaudación --- */
        .header {
            width: 80%;
            max-width: 600px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        /* Contenedor de logo, nombre y botones de utilidad */
        .utility-menu {
            display: flex;
            flex-direction: column; /* Apila el logo y los botones */
        }
        
        .logo h1 { font-size: 1.5em; margin: 0; color: white; }
        .logo span { font-size: 0.8em; opacity: 0.7; }
        
        /* ⭐ NUEVOS ESTILOS PARA LOS BOTONES DE ACCIÓN ⭐ */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .action-buttons a {
            font-size: 0.9em;
            text-decoration: none;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Botón Perfil */
        .profile-btn-chofer {
            background-color: var(--color-secondary);
        }
        .profile-btn-chofer:hover {
            background-color: #1a76c8;
        }

        /* Botón de Cerrar Sesión */
        .logout-btn-chofer {
            background-color: var(--color-error);
        }
        .logout-btn-chofer:hover {
            background-color: #d32f2f;
        }
        
        /* Recaudación */
        .recaudacion { 
            font-size: 1em; 
            font-weight: 600; 
            text-align: right;
            line-height: 1.2;
            color: #ccc;
        }
        .recaudacion span {
            font-size: 1.4em;
            display: block;
            color: var(--color-success);
        }
        /* --- Fin Header y Recaudación --- */

        /* --- Contenedor Principal --- */
        .cobro-container {
            background-color: var(--color-background-dark);
            color: white;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 450px;
            text-align: center;
        }

        /* --- Área de Escaneo (Placeholder) --- */
        .scan-area {
            background-color: #3e4860;
            padding: 40px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            cursor: pointer;
            border: none;
        }
        
        .scan-icon {
            font-size: 4em;
            color: #8894ab;
            margin-bottom: 10px;
        }
        .scan-area p {
            color: #b0b8c9;
            margin: 5px 0;
        }

        /* 🛑 Estilos para el contenedor del lector QR 🛑 */
        #qr-reader {
            margin-bottom: 30px;
            border-radius: 8px;
            overflow: hidden; 
            background-color: #3e4860;
            padding: 10px;
        }

        /* --- Botones de Tarifa (Estilo de la Captura) --- */
        .tariff-selection {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .tariff-btn {
            flex: 1;
            background-color: var(--color-adulto);
            color: white;
            border: none;
            padding: 15px 10px;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
            line-height: 1.2;
            height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .tariff-btn[data-tipo*="Adulto"] {
            background-color: var(--color-adulto);
        }

        .tariff-btn[data-tipo*="Estudiante"] {
            background-color: var(--color-student); 
        }

        .tariff-btn.active {
            opacity: 1;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5);
        }
        .tariff-btn:not(.active) {
            opacity: 0.8; 
        }
        .tariff-btn:not(.active):hover {
            opacity: 0.95;
        }

        .tariff-price {
            display: block;
            font-size: 1.4em;
            font-weight: bold;
            margin-top: 5px;
        }

        /* --- Panel de Estadísticas --- */
        .statistics-summary {
            background-color: var(--color-background-dark);
            color: white;
            padding: 15px 0;
            border-radius: 8px;
            margin-top: 30px;
            text-align: left;
        }
        .stat-line {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #3e4860;
        }
        .stat-line:last-child {
            border-bottom: none;
        }
        .stat-label { font-size: 0.9em; opacity: 0.7; }
        .stat-value { font-size: 1.1em; font-weight: bold; }

        .footer-recaudacion {
            font-size: 0.9em;
            opacity: 0.7;
            padding: 10px 0;
            text-align: center;
            border-top: 1px solid #3e4860;
        }
        
        /* --- Mensajes de Estado --- */
        .status-message {
            padding: 15px;
            border-radius: 5px;
            font-weight: 600;
            margin-top: 20px;
            text-align: left;
            display: none; 
            font-size: 0.9em;
        }
        .status-success { background-color: #e8f5e9; color: var(--color-success); border-left: 5px solid var(--color-success); }
        .status-error { background-color: #ffebee; color: var(--color-error); border-left: 5px solid var(--color-error); }
        .status-info { background-color: #e3f2fd; color: var(--color-secondary); border-left: 5px solid var(--color-secondary); }

    </style>
</head>
<body>

    <header class="header">
        <div class="utility-menu">
            <div class="logo">
                <h1>¡Hola, <?php echo htmlspecialchars($nombre_chofer); ?>!</h1>
                <span style="font-size: 0.8em; opacity: 0.7;">Vista Chofer - Listo a bordo</span>
            </div>
            
            <div class="action-buttons">
                <a href="../dashboard-admin-linea/perfil-chofer.php" class="profile-btn-chofer" title="Ir a mi Perfil">
                    <i class="fas fa-user-circle"></i> Perfil
                </a>
                <a href="../../backend/logout.php" class="logout-btn-chofer" title="Cerrar Sesión">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
        <div class="recaudacion">
            Recaudación Hoy: <span id="recaudacion-hoy">0.00 Bs</span>
        </div>
    </header>

    <div class="cobro-container">
        
        <div id="scan-area-placeholder" class="scan-area" onclick="startScanner()">
            <div class="scan-icon"><i class="fas fa-qrcode"></i></div>
            <p style="font-weight: 600;">Toca para Activar Escaneo QR</p>
            <p style="font-size: 0.8em;">Selecciona la tarifa y toca aquí para escanear</p>
        </div>

        <div id="qr-reader" style="width: 100%; display: none;"></div>
        
        <div class="tariff-selection" id="tariff-selection-container">
            <button class="tariff-btn">Cargando...</button>
            <button class="tariff-btn">Cargando...</button>
        </div>

        <div id="status-message" class="status-message status-info" style="display: block;">
            <i class="fas fa-info-circle"></i> Listo para comenzar la validación.
        </div>
        
        <div class="statistics-summary">
            <div class="stat-line">
                <span class="stat-label">Adultos Hoy</span>
                <span class="stat-value" id="count-adultos">0</span>
            </div>
             <div class="stat-line">
                <span class="stat-label">Estudiantes Hoy</span>
                <span class="stat-value" id="count-estudiantes">0</span>
            </div>
            <div class="stat-line" style="border-top: 1px solid #3e4860; margin-top: 10px; padding-top: 15px;">
                <span class="stat-label">Total Pasajeros</span>
                <span class="stat-value" id="total-pasajeros">0</span>
            </div>
        </div>

        <div class="footer-recaudacion">
            Canje Diario de Boletos: <span id="footer-recaudacion-monto">0.00 Bs</span>
        </div>

    </div>
    
    <button style="display: none;" id="btn-cobrar"></button>

    <script>
        // ----------------------------------------------------
        // I. CONFIGURACIÓN
        // ----------------------------------------------------
        const API_TARIFAS = '../../backend/fetch_tarifas.php'; 
        const API_PROCESAR_COBRO = '../../backend/procesar_cobro.php';
        
        const tariffContainer = document.getElementById('tariff-selection-container');
        const scanPlaceholder = document.getElementById('scan-area-placeholder'); 
        const qrReader = document.getElementById('qr-reader');
        const statusMessage = document.getElementById('status-message');
        
        // Elementos de Recaudación y Estadísticas
        const recaudacionHoySpan = document.getElementById('recaudacion-hoy');
        const footerRecaudacionSpan = document.getElementById('footer-recaudacion-monto');
        const countAdultosSpan = document.getElementById('count-adultos');
        const countEstudiantesSpan = document.getElementById('count-estudiantes');
        const totalPasajerosSpan = document.getElementById('total-pasajeros');

        let selectedTarifa = null;
        let tarifasData = []; 
        let recaudacionTotal = 0.0;
        let contadorAdultos = 0;
        let contadorEstudiantes = 0;
        
        // 🛑 NUEVAS VARIABLES PARA EL ESCÁNER 🛑
        const html5QrCode = new Html5Qrcode("qr-reader"); 
        let isScanning = false; 

        // ----------------------------------------------------
        // II. INICIALIZACIÓN (CARGA DE TARIFAS)
        // ----------------------------------------------------

        async function fetchTarifas() {
            try {
                const response = await fetch(API_TARIFAS);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || "No se pudieron cargar las tarifas.");
                }

                tarifasData = data.tarifas;
                renderTarifas(tarifasData);
                
            } catch (error) {
                console.error("Error al cargar tarifas:", error);
                displayMessage(`Error crítico al cargar tarifas: ${error.message}`, 'error');
            }
        }

        function renderTarifas(tarifas) {
            tariffContainer.innerHTML = '';
            tarifas.forEach(tarifa => {
                const button = document.createElement('button');
                button.className = 'tariff-btn';
                button.setAttribute('data-id', tarifa.tarifa_id);
                button.setAttribute('data-costo', tarifa.costo);
                button.setAttribute('data-tipo', tarifa.tipo_pasajero);
                button.innerHTML = `
                    ${tarifa.nombre.split('/')[0]}
                    <span class="tariff-price">${parseFloat(tarifa.costo).toFixed(2)} Bs</span>
                `;
                button.addEventListener('click', () => selectTarifa(tarifa));
                tariffContainer.appendChild(button);
            });

            // Seleccionar la primera tarifa por defecto
            if (tarifas.length > 0) {
                selectTarifa(tarifas[0]);
            }
        }

        function selectTarifa(tarifa) {
            // Desactivar todos los botones
            document.querySelectorAll('.tariff-btn').forEach(btn => btn.classList.remove('active'));
            
            // Activar el botón seleccionado
            const activeBtn = document.querySelector(`.tariff-btn[data-id="${tarifa.tarifa_id}"]`);
            if (activeBtn) {
                activeBtn.classList.add('active');
            }
            
            selectedTarifa = tarifa;
            statusMessage.style.display = 'none'; // Limpiar mensajes de estado
        }
        
        // ----------------------------------------------------
        // III. CONTROL DEL ESCÁNER Y PROCESAMIENTO
        // ----------------------------------------------------

        function startScanner() {
            if (isScanning) return;
            
            if (!selectedTarifa) {
                displayMessage("¡Alto! Seleccione un tipo de pasajero (tarifa) primero.", 'error');
                return;
            }

            isScanning = true;
            scanPlaceholder.style.display = 'none'; // Ocultar el placeholder de inicio
            qrReader.style.display = 'block'; // Mostrar el contenedor de la cámara
            displayMessage("Cargando cámara, escaneando...", 'info');
            
            const config = { 
                fps: 10, 
                qrbox: { width: 250, height: 250 }, 
                disableFlip: false 
            };
            
            html5QrCode.start(
                { facingMode: "environment" }, // Preferir la cámara trasera
                config,
                onScanSuccess,
                onScanError
            ).catch((err) => {
                isScanning = false;
                qrReader.style.display = 'none';
                scanPlaceholder.style.display = 'block';
                displayMessage(`Error al iniciar la cámara: ${err.message}. Asegúrese de tener permisos.`, 'error');
            });
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            // Detiene la cámara tan pronto como obtiene el código
            html5QrCode.stop().then(() => {
                isScanning = false;
                qrReader.style.display = 'none';
                scanPlaceholder.style.display = 'block';
                
                // Llama a la función de cobro con el código escaneado
                handleCobro(decodedText); 
            }).catch(err => {
                console.error("Error al detener el escáner:", err);
                // Continúa con el cobro incluso si detener falla
                handleCobro(decodedText); 
            });
        }

        function onScanError(errorMessage) {
            // Error de escaneo (p. ej., no se detectó ningún código, lo ignoramos para no saturar)
        }


        // 🛑 FUNCIÓN handleCobro MODIFICADA para recibir el código QR 🛑
        async function handleCobro(qrData) {
            if (!selectedTarifa) {
                displayMessage("Error: Tarifa no seleccionada.", 'error');
                return;
            }

            if (!qrData || qrData === '') {
                displayMessage("Error: Código QR inválido o vacío.", 'error');
                return;
            }
            
            // Simular el proceso (deshabilitar UI)
            document.querySelectorAll('.tariff-btn').forEach(btn => btn.disabled = true);
            displayMessage(`Procesando cobro para: ${qrData.substring(0, 10)}...`, 'info');

            const payload = {
                qrData: qrData,
                tarifaId: selectedTarifa.tarifa_id,
                monto: selectedTarifa.costo
            };

            try {
                const response = await fetch(API_PROCESAR_COBRO, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || "Fallo en el servidor al procesar el cobro.");
                }

                // Cobro exitoso
                updateRecaudacion(parseFloat(selectedTarifa.costo), selectedTarifa.tipo_pasajero);
                displayMessage(`Cobro de ${parseFloat(selectedTarifa.costo).toFixed(2)} Bs EXITOSO!`, 'success');
                
            } catch (error) {
                displayMessage(`Transacción fallida: ${error.message}`, 'error');
            } finally {
                // Habilitar la UI
                document.querySelectorAll('.tariff-btn').forEach(btn => btn.disabled = false);
            }
        }

        // ----------------------------------------------------
        // IV. UTILIDADES Y ESTADÍSTICAS
        // ----------------------------------------------------

        function displayMessage(message, type) {
            statusMessage.style.display = 'block';
            statusMessage.className = 'status-message';

            switch (type) {
                case 'success':
                    statusMessage.classList.add('status-success');
                    statusMessage.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
                    break;
                case 'error':
                    statusMessage.classList.add('status-error');
                    statusMessage.innerHTML = `<i class="fas fa-times-circle"></i> ${message}`;
                    break;
                case 'info':
                    statusMessage.classList.add('status-info');
                    statusMessage.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${message}`;
                    break;
            }
        }
        
        function updateRecaudacion(monto, tipo) {
            recaudacionTotal += monto;
            const formattedTotal = recaudacionTotal.toFixed(2) + ' Bs';
            
            // Actualizar header y footer de recaudación
            recaudacionHoySpan.textContent = formattedTotal;
            footerRecaudacionSpan.textContent = formattedTotal;

            // Actualizar contadores de pasajeros
            if (tipo && tipo.toLowerCase().includes('adulto')) {
                contadorAdultos++;
                countAdultosSpan.textContent = contadorAdultos;
            } else if (tipo && tipo.toLowerCase().includes('estudiante')) {
                contadorEstudiantes++;
                countEstudiantesSpan.textContent = contadorEstudiantes;
            }
            totalPasajerosSpan.textContent = contadorAdultos + contadorEstudiantes;
        }

        // ----------------------------------------------------
        // V. EVENT LISTENERS
        // ----------------------------------------------------
        
        // Cargar tarifas al inicio
        document.addEventListener('DOMContentLoaded', () => {
            fetchTarifas();
        });

    </script>

</body>
</html>