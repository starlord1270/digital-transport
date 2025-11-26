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
    <title>Gestión de Choferes - Digital Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
            width: 95%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: var(--color-card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* --- ESTILOS AGREGADOS PARA EL BOTÓN DE LOGOUT Y EL ENCABEZADO --- */
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
        .logout-btn {
            text-decoration: none;
            color: var(--color-error);
            font-weight: 600;
            padding: 8px 15px;
            border: 1px solid var(--color-error);
            border-radius: 4px;
            transition: background-color 0.3s, color 0.3s;
            white-space: nowrap; 
        }
        .logout-btn:hover {
            background-color: var(--color-error);
            color: var(--color-card-bg);
        }
        /* --- FIN DE ESTILOS AGREGADOS --- */

        h1 {
            color: var(--color-primary);
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
        
        /* --- Contenedor de Gestión --- */
        .gestion-choferes-box {
            background-color: var(--color-card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--color-border);
        }
        
        /* --- Tabla de Choferes --- */
        .choferes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .choferes-table th, .choferes-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--color-border);
        }
        .choferes-table th {
            background-color: var(--color-background-light);
            font-weight: 600;
            color: #555;
            font-size: 0.9em;
        }
        .choferes-table tr:hover {
            background-color: #f9f9f9;
        }
        
        /* Estilos específicos para el estado */
        .status-tag {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
        }
        .status-activo { background-color: var(--color-success); }
        .status-inactivo { background-color: var(--color-error); }
        .status-licencia { background-color: var(--color-secondary); }
        
        /* Estilo del botón de acción */
        .btn-detalle {
            background-color: var(--color-secondary);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-detalle:hover {
            background-color: #1a6fb2;
        }

        /* Mensaje de carga/error */
        #loading-message {
            text-align: center;
            padding: 20px;
            font-size: 1.1em;
            color: #777;
        }

    </style>
</head>
<body>

    <div class="admin-container">
        
        <div class="header-utility">
            <h1>Panel Administrativo - Línea de Transporte</h1>
            <a href="../../backend/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
        <div class="tabs">
            <button class="tab-btn" onclick="window.location.href='dashboard-admin.php'">Dashboard</button>
            <button class="tab-btn active">Choferes</button>
            <button class="tab-btn" onclick="window.location.href='reportes.php'">Reportes</button>
        </div>

        <div class="gestion-choferes-box">
            <h2>Gestión de Choferes</h2>
            
            <div id="loading-message">
                <i class="fas fa-spinner fa-spin"></i> Cargando datos de choferes...
            </div>

            <table class="choferes-table" id="choferes-table" style="display: none;">
                <thead>
                    <tr>
                        <th>Chofer</th>
                        <th>Vehículo</th>
                        <th>Estado</th>
                        <th>Boletos Cobrados Hoy</th>
                        <th>Monto a Canjear</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="choferes-table-body">
                    </tbody>
            </table>
        </div>
        
    </div>

    <script>
        const API_CHOFERES = '../../backend/fetch_choferes_data.php'; 
        
        // El ID de la línea inyectado desde el PHP
        // ⭐ IMPORTANTE: Ahora la variable está protegida por la lógica PHP de arriba.
        const LINEA_ID = <?php echo $linea_id_sesion; ?>; 

        

        async function fetchChoferesData() {
            const loadingMessage = document.getElementById('loading-message');
            const choferesTable = document.getElementById('choferes-table');
            const tableBody = document.getElementById('choferes-table-body');
            
            loadingMessage.style.display = 'block';
            choferesTable.style.display = 'none';
            tableBody.innerHTML = ''; 

            // Manejo de error si no hay ID de línea en la sesión
            // (Aunque el PHP ya lo gestiona, es una segunda línea de defensa en JS)
            if (LINEA_ID === 0) {
                 loadingMessage.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Error de sesión: Vuelva a iniciar sesión para ver los choferes de su línea.`;
                 return;
            }

            try {
                // Se envía el ID de la línea correcto
                const response = await fetch(`${API_CHOFERES}?linea_id=${LINEA_ID}`);
                
                // Si el servidor responde con error 500, esto lanza una excepción y muestra el error.
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Error en la API (${response.status} ${response.statusText}). Respuesta del servidor: ${errorText.substring(0, 100)}...`);
                }
                
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || "No se pudieron cargar los datos de los choferes.");
                }

                renderChoferesTable(data.choferes);
                
            } catch (error) {
                console.error("Error al cargar choferes:", error);
                loadingMessage.innerHTML = `<i class="fas fa-times-circle"></i> Error al cargar datos: ${error.message}`;
            } finally {
                loadingMessage.style.display = 'none';
                const hasData = tableBody.children.length > 0 && tableBody.children[0].colSpan !== 6;
                choferesTable.style.display = hasData ? 'table' : 'none';
            }
        }

        function renderChoferesTable(choferes) {
            const tableBody = document.getElementById('choferes-table-body');
            
            if (choferes.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No hay choferes registrados en esta línea.</td></tr>';
                return;
            }

            choferes.forEach(chofer => {
                const row = document.createElement('tr');
                
                let statusClass;
                // Normalizamos el valor a mayúsculas para la comparación
                const estado = (chofer.estado_servicio || 'INACTIVO').toUpperCase();

                switch(estado) {
                    case 'ACTIVO':
                        statusClass = 'status-activo';
                        break;
                    case 'INACTIVO':
                        statusClass = 'status-inactivo';
                        break;
                    case 'LICENCIA':
                        statusClass = 'status-licencia';
                        break;
                    default:
                        statusClass = 'status-inactivo'; 
                }

                const boletosCobrados = chofer.boletos_cobrados_hoy || 0;
                const montoCanjear = parseFloat(chofer.monto_a_canjear || 0).toFixed(2);

                row.innerHTML = `
                    <td>${chofer.nombre_completo}</td>
                    <td>${chofer.vehiculo_placa || 'N/A'}</td>
                    <td><span class="status-tag ${statusClass}">${estado}</span></td>
                    <td>${boletosCobrados}</td>
                    <td>${montoCanjear} Bs</td>
                    <td>
                        <button class="btn-detalle" onclick="verDetalleChofer(${chofer.chofer_id})">Ver Detalle</button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }
        
        function verDetalleChofer(choferId) {
            alert(`Funcionalidad para ver detalle del Chofer ID: ${choferId} (Pendiente de implementación)`);
        }

        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', fetchChoferesData);

    </script>
</body>
</html>