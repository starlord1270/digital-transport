<?php 
// 1. ASEGURAR SESIÓN INICIADA (Debe ser lo primero)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
} 

// 🛑 INICIO DE VERIFICACIÓN DE SEGURIDAD Y ROL (RBAC) 🛑

// Tipo de usuario esperado para esta página: 4 (ADMIN_LINEA)
$user_type_expected = 4;
$user_type_actual = isset($_SESSION['tipo_usuario_id']) ? $_SESSION['tipo_usuario_id'] : 0;
$linea_id_sesion = isset($_SESSION['linea_id']) ? $_SESSION['linea_id'] : 0; 

// Si el usuario no está logueado, O el tipo de usuario no es 4, O no tiene linea_id asignado, redirigir.
if ($user_type_actual != $user_type_expected || $linea_id_sesion == 0) {
    // Limpiar y destruir la sesión actual para evitar conflictos futuros
    session_unset();
    session_destroy();
    
    // Redirigir al inicio de sesión (ruta corregida)
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
    <title>Panel Administrativo - Digital Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* [Se mantienen los estilos CSS para no repetirlos. Solo se añade el estilo del nuevo botón.] */

        :root {
            --color-primary: #0b2e88;
            --color-secondary: #1e88e5;
            --color-success: #4caf50;
            --color-error: #f44336;
            --color-text-dark: #333;
            --color-background-light: #f4f7f9;
            --color-card-bg: #fff;
            --color-border: #e0e0e0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--color-background-light);
            color: var(--color-text-dark);
        }

        .admin-container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: var(--color-card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* --- Estilos del Encabezado --- */
        .header-utility {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--color-border);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header-utility h1 {
            color: var(--color-primary);
            margin: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        /* --- Contenedor de Botones (Perfil y Logout) --- */
        .header-actions {
            display: flex; /* Permite alinear ambos botones horizontalmente */
            gap: 10px;
        }

        /* Estilo base para ambos botones de utilidad */
        .utility-btn {
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Estilo específico para el botón de PERFIL */
        .profile-btn {
            color: var(--color-primary);
            border: 1px solid var(--color-primary);
            background-color: transparent;
        }
        .profile-btn:hover {
            background-color: var(--color-primary);
            color: var(--color-card-bg);
        }

        /* Estilo específico para el botón de LOGOUT */
        .logout-btn {
            color: var(--color-error);
            border: 1px solid var(--color-error);
        }
        .logout-btn:hover {
            background-color: var(--color-error);
            color: var(--color-card-bg);
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

        /* --- Tarjetas de Resumen --- */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background-color: var(--color-card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--color-border);
        }
        .card h3 {
            margin-top: 0;
            font-size: 1.1em;
            color: #777;
        }
        .card .value {
            font-size: 2.2em;
            font-weight: bold;
            color: var(--color-primary);
            margin-top: 5px;
        }
        .card.active-choferes .value {
            color: var(--color-success);
        }

        /* --- Resumen Inferior (Choferes y Validaciones) --- */
        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .section-box {
            background-color: var(--color-card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--color-border);
        }
        .section-box h3 {
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 10px;
            margin-top: 0;
        }

        /* --- Lista de Resumen de Choferes --- */
        .chofer-list-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed var(--color-border);
            font-size: 0.9em;
        }
        .chofer-list-item:last-child {
            border-bottom: none;
        }
        .chofer-info {
            font-weight: 600;
        }
        .chofer-stats span {
            display: block;
            text-align: right;
            font-size: 0.9em;
        }
        .chofer-stats .monto {
            color: var(--color-error); /* Para destacar el monto pendiente de canje */
            font-weight: bold;
        }
        
        /* --- Lista de Validaciones --- */
        .validation-list-item {
            padding: 8px 0;
            border-bottom: 1px dashed var(--color-border);
            font-size: 0.9em;
        }
        .validation-list-item:last-child {
            border-bottom: none;
        }
        .validation-name {
            font-weight: 600;
            color: var(--color-secondary);
        }
        .validation-details {
            display: flex;
            justify-content: space-between;
            margin-top: 3px;
            color: #777;
        }
    </style>
</head>
<body>

    <div class="admin-container">
        
        <div class="header-utility">
            <h1>Panel Administrativo - Línea de Transporte</h1>
            <div class="header-actions">
                <a href="perfil-admin.php" class="profile-btn utility-btn">
                    <i class="fas fa-user"></i> Perfil
                </a>
                <a href="../../backend/logout.php" class="logout-btn utility-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
            </div>
        <div class="tabs">
            <button class="tab-btn active">Dashboard</button>
            <button class="tab-btn" onclick="window.location.href='choferes.php'">Choferes</button>
            <button class="tab-btn" onclick="window.location.href='reportes.php'">Reportes</button>
        </div>

        <div class="summary-cards">
            <div class="card">
                <h3>Total Recaudado Hoy</h3>
                <div class="value" id="total-recaudado">0.00 Bs</div>
            </div>
            <div class="card">
                <h3>Boletos Pendientes de Canje</h3>
                <div class="value" id="boletos-pendientes">0</div>
                <small class="text-muted">Boletos por canjear con choferes</small>
            </div>
            <div class="card active-choferes">
                <h3>Choferes Activos</h3>
                <div class="value" id="choferes-activos">0</div>
                <small class="text-muted">Choferes en servicio hoy</small>
            </div>
        </div>
        
        <div class="dashboard-sections">
            
            <div class="section-box">
                <h3>Resumen de Choferes</h3>
                <div id="resumen-choferes">
                    <p style="text-align: center; color: #777;">Cargando datos...</p>
                </div>
            </div>

            <div class="section-box">
                <h3>Validaciones Pendientes</h3>
                <div id="validaciones-pendientes">
                     <p style="text-align: center; color: #777;">No hay validaciones pendientes.</p>
                </div>
            </div>
            
        </div>
        
    </div>

    <script>
        const API_DASHBOARD = '../../backend/fetch_dashboard_data.php'; 
        // Obtener el ID de la línea del administrador desde PHP
        const LINEA_ID = <?php echo $linea_id_sesion; ?>; 

        async function fetchDashboardData() {
            // Chequeo de seguridad en JS
            if (LINEA_ID === 0) {
                 console.error("Error de Sesión: No se encontró LINEA_ID. La verificación PHP debería haber redirigido.");
                 return;
            }

            try {
                // Se envía el ID de la línea del administrador logueado
                const response = await fetch(`${API_DASHBOARD}?linea_id=${LINEA_ID}`);
                
                // Manejo de errores HTTP (404, 500, etc.)
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Error en la API (${response.status}). Respuesta: ${errorText.substring(0, 100)}...`);
                }
                
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || "No se pudieron cargar los datos del dashboard.");
                }

                renderData(data.data);
                
            } catch (error) {
                console.error("Error al cargar el dashboard:", error);
                // Aquí podrías mostrar un mensaje de error en la interfaz
            }
        }

        function renderData(data) {
            // 1. Tarjetas Superiores
            document.getElementById('total-recaudado').textContent = `${parseFloat(data.total_recaudado).toFixed(2)} Bs`;
            document.getElementById('boletos-pendientes').textContent = data.boletos_pendientes;
            document.getElementById('choferes-activos').textContent = data.choferes_activos;

            // 2. Resumen de Choferes
            const resumenChoferesDiv = document.getElementById('resumen-choferes');
            resumenChoferesDiv.innerHTML = ''; // Limpiar
            if (data.resumen_choferes && data.resumen_choferes.length > 0) {
                data.resumen_choferes.forEach(chofer => {
                    const item = document.createElement('div');
                    item.className = 'chofer-list-item';
                    item.innerHTML = `
                        <div class="chofer-info">${chofer.nombre_completo}</div>
                        <div class="chofer-stats">
                            <span>${chofer.boletos_cobrados} boletos</span>
                            <span class="monto">${parseFloat(chofer.monto_canje).toFixed(2)} Bs</span>
                        </div>
                    `;
                    resumenChoferesDiv.appendChild(item);
                });
            } else {
                resumenChoferesDiv.innerHTML = '<p style="text-align: center; color: #777;">No hay choferes con actividad hoy.</p>';
            }

            // 3. Validaciones Pendientes
            const validacionesDiv = document.getElementById('validaciones-pendientes');
            validacionesDiv.innerHTML = ''; // Limpiar
            if (data.validaciones_pendientes && data.validaciones_pendientes.length > 0) {
                data.validaciones_pendientes.forEach(val => {
                    const item = document.createElement('div');
                    item.className = 'validation-list-item';
                    item.innerHTML = `
                        <div class="validation-name">${val.nombre_completo}</div>
                        <div class="validation-details">
                            <span>${val.tipo_descuento}</span>
                            <span>${val.fecha_solicitud}</span>
                        </div>
                    `;
                    validacionesDiv.appendChild(item);
                });
            } else {
                 validacionesDiv.innerHTML = '<p style="text-align: center; color: #777;">No hay validaciones pendientes.</p>';
            }
        }

        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', fetchDashboardData);

    </script>
</body>
</html>