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
    session_unset();
    session_destroy();
    
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
            position: relative;
        }

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
        }

        .logout-btn:hover {
            background-color: var(--color-error);
            color: var(--color-card-bg);
        }

        /* BOTÓN TEMA */
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.4em;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            color: var(--text-primary);
            transition: color 0.3s;
        }

        h1 {
            color: var(--color-primary);
        }

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

        .gestion-choferes-box {
            background-color: var(--color-card-bg);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--color-border);
        }

        .choferes-table {
            width: 100%;
            border-collapse: collapse;
        }

        .choferes-table th, .choferes-table td {
            padding: 12px;
            border-bottom: 1px solid var(--color-border);
        }

        .choferes-table th {
            background-color: var(--color-background-light);
        }

        /* Estado */
        .status-tag {
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-activo { background-color: var(--color-success); }
        .status-inactivo { background-color: var(--color-error); }
        .status-licencia { background-color: var(--color-secondary); }

        .btn-detalle {
            background-color: var(--color-secondary);
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        #loading-message {
            text-align: center;
            padding: 20px;
            font-size: 1.1em;
        }
    </style>
</head>
<body>

    <div class="admin-container">

        <button class="theme-toggle" id="theme-toggle" title="Cambiar Tema">
            <i class="fas fa-moon"></i>
        </button>

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
                        <th>Boletos Hoy</th>
                        <th>Monto a Canjear</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="choferes-table-body"></tbody>
            </table>
        </div>

    </div>

    <script>
        const API_CHOFERES = '../../backend/fetch_choferes_data.php';
        const LINEA_ID = <?php echo $linea_id_sesion; ?>;

        async function fetchChoferesData() {
            const loading = document.getElementById("loading-message");
            const table = document.getElementById("choferes-table");
            const body = document.getElementById("choferes-table-body");

            loading.style.display = "block";
            table.style.display = "none";
            body.innerHTML = "";

            try {
                const res = await fetch(`${API_CHOFERES}?linea_id=${LINEA_ID}`);

                if (!res.ok) throw new Error("Error en la API");

                const data = await res.json();
                if (!data.success) throw new Error(data.error);

                renderChoferesTable(data.choferes);

            } catch (err) {
                loading.innerHTML = `<i class="fas fa-times-circle"></i> Error: ${err.message}`;
            } finally {
                loading.style.display = "none";
                table.style.display = body.children.length > 0 ? "table" : "none";
            }
        }

        function renderChoferesTable(choferes) {
            const body = document.getElementById("choferes-table-body");

            if (choferes.length === 0) {
                body.innerHTML = `<tr><td colspan="6" style="text-align:center;">No hay choferes registrados.</td></tr>`;
                return;
            }

            choferes.forEach(c => {
                let estado = (c.estado_servicio || "INACTIVO").toUpperCase();
                let clase =
                    estado === "ACTIVO" ? "status-activo" :
                    estado === "LICENCIA" ? "status-licencia" : "status-inactivo";

                body.innerHTML += `
                <tr>
                    <td>${c.nombre_completo}</td>
                    <td>${c.vehiculo_placa || "N/A"}</td>
                    <td><span class="status-tag ${clase}">${estado}</span></td>
                    <td>${c.boletos_cobrados_hoy || 0}</td>
                    <td>${parseFloat(c.monto_a_canjear || 0).toFixed(2)} Bs</td>
                    <td><button class="btn-detalle">Ver Detalle</button></td>
                </tr>`;
            });
        }

        document.addEventListener("DOMContentLoaded", fetchChoferesData);

        // 🌙 TEMA OSCURO - COMPLETO
        const themeBtn = document.getElementById("theme-toggle");
        const root = document.documentElement;

        const savedTheme = localStorage.getItem("tema") || "light";
        root.setAttribute("data-theme", savedTheme);
        updateIcon(savedTheme);

        themeBtn.addEventListener("click", () => {
            const current = root.getAttribute("data-theme");
            const newTheme = current === "light" ? "dark" : "light";

            root.setAttribute("data-theme", newTheme);
            localStorage.setItem("tema", newTheme);

            updateIcon(newTheme);
        });

        function updateIcon(theme) {
            const icon = themeBtn.querySelector("i");
            icon.classList.toggle("fa-sun", theme === "dark");
            icon.classList.toggle("fa-moon", theme === "light");
        }
    </script>

</body>
</html>
