<?php
/**
 * Archivo: puntos-recarga.php
 * Descripción: Muestra la lista y el mapa de Puntos de Recarga.
 * Incluye lógica de sesión para mostrar el menú dinámico.
 */

// 1. GESTIÓN DE SESIONES
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lógica simplificada de verificación de sesión (Para el Header)
$user_is_logged_in = isset($_SESSION['usuario_id']);

$nombre_usuario = $user_is_logged_in ? htmlspecialchars($_SESSION['nombre_completo'] ?? 'Pasajero') : 'Invitado';
$user_balance = $user_is_logged_in ? ($_SESSION['saldo'] ?? 0.00) : 0.00;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Puntos de Recarga Detalle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        :root {
            --color-primary: #0b2e88;
            --color-secondary: #1e88e5;
            --color-text-dark: #333;
            --color-background-light: #f4f7f9;
            
            --bg-primary: #ffffff;
            --bg-secondary: #f4f7f9;
            --text-primary: #333;
            --text-secondary: #666;
            --border-color: #eee;
            --card-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            --color-rating-star: #ffc107;
            --color-distance: #0b2e88;
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        /* Título de la Sección */
        .section-header h1 { font-size: 1.8em; color: var(--color-text-dark); margin: 0 0 5px 0; }
        .section-header p { font-size: 0.95em; color: #666; margin-bottom: 30px; }

        /* --- Elementos de Información de Resultados (Ajustados para ir antes de la lista) --- */
        .results-info { 
            font-size: 0.9em; 
            color: #666; 
            margin-bottom: 20px; /* Espacio añadido aquí */
            display: flex; 
            align-items: center; 
            padding: 10px 0;
        }
        .results-info i { margin-right: 5px; color: var(--color-secondary); }

        /* --- LISTA DE PUNTOS DE RECARGA --- */
        .recharge-list-item {
            display: flex;
            align-items: flex-start;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        .recharge-list-item:last-child {
            border-bottom: none;
        }
        
        .list-icon {
            font-size: 1.5em;
            color: var(--color-secondary);
            margin-right: 20px;
            padding-top: 5px;
        }
        
        .list-details {
            flex-grow: 1;
        }
        
        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 5px;
        }
        
        .location-info h3 {
            font-size: 1.1em;
            color: var(--color-text-dark);
            margin: 0;
            font-weight: 600;
        }
        
        .location-info p {
            font-size: 0.9em;
            color: #666;
            margin: 2px 0;
        }
        
        .tags-and-rating {
            font-size: 0.8em;
            color: #999;
            margin-top: 5px;
        }
        
        .tag {
            background-color: #f0f0f0;
            padding: 3px 8px;
            border-radius: 3px;
            margin-right: 5px;
            font-weight: 500;
        }
        
        .tag.center { background-color: #e3f2fd; color: #1e88e5; }
        .tag.north { background-color: #e8f5e9; color: #4caf50; }
        .tag.south { background-color: #fcf4e8; color: #f90; }

        .rating {
            color: var(--color-rating-star);
            font-weight: 600;
            margin-left: 5px;
        }
        
        .distance-action {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            text-align: right;
            margin-left: 20px;
        }
        
        .distance {
            font-size: 1.0em; /* Reducido un poco al ser solo texto */
            font-weight: bold;
            color: var(--color-distance);
            margin-bottom: 10px;
        }
        
        .action-button {
            background: none;
            border: 1px solid #ccc;
            color: var(--color-secondary);
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.2s;
        }
        
        .action-button:hover {
            background-color: #f0f0f0;
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
            .search-row { flex-direction: column; }
            .filter-button { width: 100%; justify-content: center; }
            .recharge-list-item { flex-direction: column; align-items: flex-start; }
            .distance-action { margin-top: 10px; align-self: flex-end; }
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
            
            <?php if ($user_is_logged_in): ?>
                <a href="index.php" class="nav-item">Inicio</a>
                <a href="recarga-digital.php" class="nav-item">Recarga</a>
                <a href="puntos-recarga.php" class="nav-item active">Puntos PR</a>
                <a href="historial-viaje.php" class="nav-item">Historial</a> 
                <a href="perfil-pasajero.php" class="nav-item">
                    <i class="fas fa-user-circle"></i> Perfil
                </a>
                <a href="../backend/logout.php?redirect=puntos-recarga.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            <?php else: ?>
                 <a href="index.php" class="nav-item">Inicio</a>
                 <a href="registro-usuarios.php" class="nav-item">Regístrate</a>
                 <a href="puntos-recarga.php" class="nav-item active">Puntos PR</a>
                 <a href="inicio-sesion-usuarios.php" class="nav-item">Iniciar Sesión</a>
            <?php endif; ?>
        </nav>
        
        <button class="theme-toggle" id="theme-toggle" title="Cambiar tema">
            <i class="fas fa-moon"></i>
        </button>
        
        <div class="saldo">
            <?php if ($user_is_logged_in): ?>
                <span style="margin-right: 15px; font-weight: 500;">¡Hola, <?php echo $nombre_usuario; ?>!</span>
                Saldo: Bs. <?php echo number_format($user_balance, 2); ?>
            <?php else: ?>
                <span style="font-weight: 500;">Invitado</span>
            <?php endif; ?>
        </div>
    </header>

    <div class="page-content-wrapper">
        <div class="main-content">
            
            <header class="section-header">
                <h1>Puntos de Recarga</h1>
                <p>Encuentra el punto de recarga más cercano a tu ubicación</p>
            </header>
            
            <div class="results-info">
                <i class="fas fa-map-marker-alt"></i>
                Mostrando 6 puntos de recarga
            </div>

            <section class="recharge-list-section">
                
                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>Plaza Colon</h3>
                                <p>Calle Venezuela #1234, Centro</p>
                            </div>
                            <span class="tag center">Centro</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 6:00 AM - 10:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-41123456</span> |
                            <span class="rating">4.8 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                            <span class="tag">Venta de tarjetas</span>
                            <span class="tag">Información</span>
                        </div>
                    </div>
                    <div class="distance-action">
                        <div class="distance">Ver Ruta</div>
                        <button class="action-button" data-lat="-17.3892" data-lng="-66.1557"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>
                
                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>Plaza Principal</h3>
                                <p>Plaza 14 de Septiembre, Centro</p>
                            </div>
                            <span class="tag center">Centro</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 7:00 AM - 9:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-4234567</span> |
                            <span class="rating">4.6 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                            <span class="tag">Información</span>
                        </div>
                    </div>
                    <div class="distance-action">
                         <div class="distance">Ver Ruta</div>
                        <button class="action-button" data-lat="-17.3941" data-lng="-66.1565"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>

                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>Facultad de Medicina Universidad Mayor de San Simón</h3>
                                <p>Av.Aniceto Arce, Centro</p>
                            </div>
                            <span class="tag center">Centro</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 7:00 AM - 8:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-4345678</span> |
                            <span class="rating">4.9 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                            <span class="tag">Venta de tarjetas</span>
                            <span class="tag">Descuentos estudiantes</span>
                        </div>
                    </div>
                    <div class="distance-action">
                         <div class="distance">Ver Ruta</div>
                        <button class="action-button" data-lat="-17.3871" data-lng="-66.1491"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>

                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>AV. Ayacucho</h3>
                                <p>Ayacucho y Tototora, Centro</p>
                            </div>
                            <span class="tag south">Sur</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 6:00 AM - 6:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-4556677</span> |
                            <span class="rating">4.2 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                        </div>
                    </div>
                    <div class="distance-action">
                         <div class="distance">Ver Ruta</div>
                        <button class="action-button" data-lat="-17.4060" data-lng="-66.1580"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>

                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>Calle las Cucardas</h3>
                                <p> Zona Norte</p>
                            </div>
                            <span class="tag north">Norte</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 8:00 AM - 1:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-4667788</span> |
                            <span class="rating">4.7 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                        </div>
                    </div>
                    <div class="distance-action">
                         <div class="distance">Ver Ruta</div>
                        <button class="action-button" data-lat="-17.3670" data-lng="-66.1420"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>

                <div class="recharge-list-item">
                    <div class="list-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="list-details">
                        <div class="list-header">
                            <div class="location-info">
                                <h3>AV.Hernan Siles</h3>
                                <p>Zona Norte</p>
                            </div>
                            <span class="tag north">Norte</span>
                        </div>
                        <div class="tags-and-rating">
                            <span class="tag"><i class="far fa-clock"></i> 7:00 AM - 9:00 PM</span> | 
                            <span class="tag"><i class="fas fa-phone-alt"></i> +591 4-4778899</span> |
                            <span class="rating">4.5 <i class="fas fa-star"></i></span>
                        </div>
                        <div class="tags-and-rating" style="margin-top: 10px;">
                            <span class="tag">Recarga</span>
                            <span class="tag">Venta de tarjetas</span>
                        </div>
                    </div>
                    <div class="distance-action">
                         <div class="distance">Ver Ruta</div>
                        <button class="action-button" data-lat="-17.3550" data-lng="-66.1650"><i class="fas fa-route"></i> Cómo llegar</button>
                    </div>
                </div>
                
            </section>
            
        </div>
    </div>

    <footer class="footer">
        © 2025 Digital Transport - Sistema de Boletos Digital
    </footer>

    <script>
        // Datos estáticos: Nombres actualizados a los puntos nuevos.
        const rechargePointsData = [
            { name: "Plaza Colon" },
            { name: "Plaza Principal" },
            { name: "Facultad de Medicina Universidad Mayor de San Simón" },
            { name: "AV. Ayacucho" },
            { name: "Calle las Cucardas" },
            { name: "AV. Hernan Siles" }
        ];

        /**
         * Inicializa la funcionalidad del botón "Cómo llegar" con redirección a Google Maps.
         */
        function initButtons() {
            const buttons = document.querySelectorAll('.action-button');
            const totalPoints = buttons.length;

            buttons.forEach(button => {
                button.onclick = () => {
                    const lat = button.getAttribute('data-lat');
                    const lng = button.getAttribute('data-lng');
                    
                    // URL correcta de Google Maps Directions API
                    // Formato: https://www.google.com/maps/dir/?api=1&destination=lat,lng
                    const mapUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
                    
                    window.open(mapUrl, '_blank');
                };
            });
            
            // Actualizar el número total de resultados mostrados
            document.querySelector('.results-info').innerHTML = 
                `<i class="fas fa-map-marker-alt"></i> Mostrando ${totalPoints} puntos de recarga`;
        }
        
        // Ejecutar la inicialización de los botones al cargar el script
        initButtons();

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