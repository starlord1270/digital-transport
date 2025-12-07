<?php
// 1. Iniciar la sesión (si no está iniciada)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🛑 LÓGICA DE VERIFICACIÓN DE SESIÓN PARA PASAJERO (tipo_usuario_id = 1) 🛑

// Verificar si el usuario ha iniciado sesión Y si es un PASAJERO válido
// Roles: 1 (Estándar), 2 (Estudiante), 5 (Adulto), 6 (Adulto Mayor)
$allowed_roles = [1, 2, 5, 6];
$user_is_logged_in = (
    isset($_SESSION['usuario_id']) && 
    in_array($_SESSION['tipo_usuario_id'], $allowed_roles)
);

$nombre_usuario = $user_is_logged_in ? htmlspecialchars($_SESSION['nombre_completo'] ?? 'Pasajero') : 'Invitado';

// El saldo DEBE ser cargado en la sesión cuando el usuario inicia sesión O actualizado después de una recarga.
// Si el saldo no está en la sesión, se usa 0.00.
$user_balance = $user_is_logged_in ? ($_SESSION['saldo'] ?? 0.00) : 0.00; 

// 🛑 2025-12-07: ACTUALIZACIÓN DE SALDO EN TIEMPO REAL 🛑
// Si el usuario está logueado, consultamos la BD para tener el saldo fresco (por si hubo cobros)
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
        // No cerramos $conn aquí porque se puede usar más abajo si index.php crece
    }
}
// 🛑 FIN ACTUALIZACIÓN 🛑 

// 🛑 FIN LÓGICA DE SESIÓN 🛑

// 🚀 LÓGICA PARA MOSTRAR MENSAJE DE ÉXITO POST-LOGIN (REQUIERE QUE login.php LO ESTABLEZCA)
$success_message = '';
if (isset($_SESSION['login_success_message'])) {
    // Si el mensaje existe, lo guardamos y lo limpiamos de la sesión para que no se muestre de nuevo
    $success_message = $_SESSION['login_success_message'];
    unset($_SESSION['login_success_message']); 
}
// 🚀 FIN LÓGICA MENSAJE 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Página de Inicio Completa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primary: #0b2e88;
            --color-secondary: #1e88e5;
            --color-text-dark: #333;
            --color-background-light: #f4f7f9;
            --color-student-green: #4caf50;
            --color-feature-icon-1: #1e88e5; 
            --color-feature-icon-2: #4caf50; 
            --color-feature-icon-3: #9c27b0;
            
            /* Tema claro (default) */
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
        
        /* Ajuste de color de fondo general para secciones */
        .page-content-wrapper {
            background-color: var(--color-background-light);
            padding-bottom: 50px; /* Espacio para el pie de página */
            flex-grow: 1;
        }

        /* --- Header / Menú Superior --- */
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
        /* Permite que el menú se envuelva en móviles */
        .nav-menu { display: flex; gap: 20px; flex-wrap: wrap; justify-content: flex-end; } 
        .nav-item { color: var(--text-primary); text-decoration: none; padding: 5px 10px; border-radius: 5px; font-size: 0.95em; transition: background-color 0.2s; }
        .nav-item.active { background-color: var(--bg-secondary); font-weight: 500; }
        
        /* Theme Toggle Button */
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
        
        /* Estilos del Saldo y Nombre */
        .saldo { 
            font-size: 1em; 
            color: var(--color-text-dark); 
            font-weight: 600; 
            display: flex; 
            align-items: center;
            margin-left: 20px; /* Espacio para separarlo del menú */
        }
        
        /* ⭐ Nuevo estilo para el mensaje de éxito */
        .alert-success {
            background-color: #e6ffe6;
            color: #4caf50;
            border: 1px solid #4caf50;
        }
        
        /* --- Contenido Principal --- */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* --- Banner de Bienvenida --- */
        .banner-container {
            background-color: var(--color-primary);
            color: white;
            padding: 60px;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .banner-container h1 { font-size: 2.2em; margin: 0 0 15px 0; }

        .btn { padding: 12px 25px; border-radius: 5px; font-size: 1em; text-decoration: none; font-weight: 600; display: inline-block; }
        .btn-primary { background-color: var(--color-secondary); color: white; border: none; margin-right: 15px; }
        .btn-secondary { background: none; color: white; border: 2px solid white; }

        /* --- SECCIÓN DE TARIFAS (C2) --- */
        .tariffs-section {
            text-align: center;
            padding: 60px 0 20px 0;
            background-color: white; /* Tarifa con fondo blanco */
        }

        .tariffs-section h2 { font-size: 1.8em; color: var(--color-text-dark); margin-bottom: 5px; }
        .tariffs-section > p { color: #666; margin-bottom: 30px; }

        .card-container { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; }

        .tariff-card {
            background-color: white;
            padding: 40px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            width: 45%; max-width: 500px;
            text-align: center;
            position: relative;
            border: 1px solid #eee; 
        }

        .card-icon {
            font-size: 2.5em; width: 80px; height: 80px; border-radius: 50%; display: flex;
            justify-content: center; align-items: center; margin: 0 auto 20px;
            color: var(--color-secondary); background-color: #e0f7fa;
        }

        .price-box { color: var(--color-text-dark); font-size: 2.2em; font-weight: bold; margin-bottom: 5px; }
        .price-box small { font-size: 0.4em; font-weight: normal; display: block; color: #777; margin-top: -10px; margin-bottom: 30px; }
        
        .benefits-list { list-style: none; padding: 0; text-align: left; margin-top: 30px; }
        .benefits-list li { font-size: 0.95em; color: #555; margin-bottom: 12px; line-height: 1.4; }
        .benefits-list li::before { content: "\2022"; color: var(--color-secondary); font-weight: bold; display: inline-block; width: 1em; margin-left: -1em; }

        .student { border: 2px solid var(--color-student-green); }
        .student .card-icon { color: var(--color-student-green); background-color: #e8f5e9; }
        .student .benefits-list li::before { color: var(--color-student-green); }

        .discount-badge {
            position: absolute; top: 0; right: 0; background-color: var(--color-student-green); 
            color: white; padding: 5px 10px; border-top-right-radius: 8px; border-bottom-left-radius: 10px;
            font-size: 0.8em; font-weight: bold;
        }
        
        .btn-student {
            background: none; color: var(--color-student-green); border: 2px solid var(--color-student-green);
            padding: 10px 20px; border-radius: 5px; font-weight: 600; text-decoration: none;
            display: block; width: 80%; margin: 30px auto 0; transition: background-color 0.2s;
        }
        .btn-student:hover { background-color: #f0fff0; }

        /* --- SECCIÓN DE CARACTERÍSTICAS (C3) --- */
        .features-section {
            display: flex; justify-content: space-between; gap: 20px;
            margin-top: 40px; 
            padding: 0 40px;
        }

        .feature-card {
            background-color: white; padding: 25px; border-radius: 8px; text-align: left;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); width: 33%;
        }

        .feature-card h4 { font-size: 1.1em; color: var(--color-text-dark); margin: 0; margin-top: 10px; font-weight: 600; }
        .feature-card p { font-size: 0.9em; color: #666; line-height: 1.5; }

        .icon-wrapper { font-size: 1.5em; width: 35px; height: 35px; border-radius: 5px; display: flex; align-items: center; justify-content: center; }

        .icon-recarga { background-color: rgba(30, 136, 229, 0.1); color: var(--color-feature-icon-1); }
        .icon-puntos { background-color: rgba(76, 175, 80, 0.1); color: var(--color-feature-icon-2); }
        .icon-seguro { background-color: rgba(156, 39, 176, 0.1); color: var(--color-feature-icon-3); }

        /* --- SECCIÓN DE ESTADÍSTICAS (C3) --- */
        .stats-section {
            display: flex; justify-content: space-around; text-align: center;
            padding: 60px 20px;
        }

        .stat-item h3 { font-size: 2.5em; font-weight: bold; margin: 0; }
        .stat-item p { font-size: 0.9em; color: #666; margin: 5px 0 0; }

        .stat-item:nth-child(1) h3 { color: var(--color-secondary); }
        .stat-item:nth-child(2) h3 { color: var(--color-feature-icon-2); }
        .stat-item:nth-child(3) h3 { color: var(--color-feature-icon-3); }
        .stat-item:nth-child(4) h3 { color: #f44336; } 
        
        /* --- BANNER DE REGISTRO (C3) --- */
        .register-banner {
            background-color: var(--color-primary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 40px;
            border-radius: 10px;
            margin-bottom: 40px;
            max-width: 1200px;
            margin: 0 auto 40px auto;
        }

        .register-banner h3 { font-size: 1.4em; margin: 0; font-weight: 600; }
        .register-banner p { font-size: 0.9em; opacity: 0.8; margin: 5px 0 0 0; }

        .btn-register-footer {
            background: none; color: white; border: 1px solid white;
            padding: 10px 20px; border-radius: 5px; text-decoration: none;
            font-weight: 500; transition: background-color 0.2s;
        }
        .btn-register-footer:hover { background-color: rgba(255, 255, 255, 0.1); }

        /* --- FOOTER (C3) --- */
        .footer {
            text-align: center;
            padding: 20px;
            background-color: white;
            color: #666;
            font-size: 0.85em;
            margin-top: auto;
            border-top: 1px solid #eee;
        }

        /* === MODAL DE PASE DIGITAL === */
        .qr-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .qr-modal-overlay.active {
            display: flex;
        }

        .qr-modal-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 40px;
            max-width: 450px;
            width: 90%;
            text-align: center;
            color: white;
            position: relative;
            animation: slideUp 0.4s ease;
        }

        .qr-modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.2s;
        }

        .qr-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .qr-modal-header {
            margin-bottom: 25px;
        }

        .qr-modal-header h2 {
            font-size: 1.8em;
            margin: 0 0 10px 0;
            font-weight: 600;
        }

        .qr-modal-header p {
            opacity: 0.9;
            font-size: 0.95em;
            margin: 0;
        }

        .qr-code-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        #qr-code {
            margin: 0 auto;
        }

        .user-info-qr {
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .user-info-qr p {
            margin: 8px 0;
            font-size: 0.95em;
        }

        .user-info-qr strong {
            font-weight: 600;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Media Query para responsividad básica */
        @media (max-width: 900px) {
            .header { flex-wrap: wrap; justify-content: space-between; gap: 10px; }
            .nav-menu { order: 3; width: 100%; justify-content: center; }
            .saldo { order: 2; margin-left: 0; }
            
            .card-container { flex-direction: column; align-items: center; }
            .tariff-card { width: 90%; }
            
            .features-section { flex-direction: column; align-items: center; padding: 0 10px; }
            .feature-card { width: 90%; margin-bottom: 20px; }
            
            .stats-section { flex-wrap: wrap; }
            .stat-item { width: 50%; margin-bottom: 20px; }

            .register-banner { flex-direction: column; text-align: center; }
            .register-banner .text { margin-bottom: 15px; }
            .btn-register-footer { width: 100%; }
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
                <a href="index.php" class="nav-item active">Inicio</a>
                <a href="recarga-digital.php" class="nav-item">Recarga</a>
                <a href="puntos-recarga.php" class="nav-item">Puntos PR</a>
                <a href="historial-viaje.php" class="nav-item">Historial</a>
                <a href="perfil-pasajero.php" class="nav-item">
                    <i class="fas fa-user-circle"></i> Perfil
                </a>
                <a href="../backend/logout.php?redirect=index.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            <?php else: ?>
                <a href="index.php" class="nav-item active">Inicio</a>
                <a href="inicio-sesion-usuarios.php" class="nav-item">Iniciar Sesión</a> 
                <a href="registro-usuarios.php" class="nav-item">Regístrate</a>
            <?php endif; ?>
        </nav>
        
        <!-- Theme Toggle -->
        <button class="theme-toggle" id="theme-toggle" title="Cambiar tema">
            <i class="fas fa-moon"></i>
        </button>
        
        <?php if ($user_is_logged_in): ?>
        <div class="saldo">
            <span style="margin-right: 15px; font-weight: 500;">¡Hola, <?php echo $nombre_usuario; ?>!</span>
            Saldo: <span style="font-weight: 700;">Bs. <?php echo number_format($user_balance, 2); ?></span>
        </div>
        <?php endif; ?>
    </header>

    <div class="page-content-wrapper">
        <div class="main-content">
        
            <?php if (!empty($success_message)): ?>
                <div class="alert-success" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
        
            <div class="banner-container">
                <p class="subtitle">Sistema de Boletos Digital</p>
                <h1>Bienvenido a Digital Transport</h1>
                <p>La forma más fácil y rápida de pagar tu transporte público. Recarga tu tarjeta, compra boletos y viaja sin efectivo.</p>
                <div class="buttons">
                    <?php if ($user_is_logged_in): ?>
                        <button class="btn btn-primary" id="btn-show-qr" style="font-size: 1.1em; padding: 15px 35px;">
                            <i class="fas fa-qrcode"></i> Mostrar mi Pase Digital
                        </button>
                        <a href="recarga-digital.php" class="btn btn-secondary">Recargar Saldo</a>
                    <?php else: ?>
                        <a href="recarga-digital.php" class="btn btn-primary">Recargar Ahora</a>
                        <a href="registro-usuarios.php" class="btn btn-secondary">Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>

        </div> 
        <section class="tariffs-section">
            <div class="main-content" style="padding-top: 0;">
                <h2>Tarifas del Sistema</h2>
                <p>Precios accesibles para todos los usuarios</p>
                
                <div class="card-container">
                    
                    <div class="tariff-card">
                        <div class="card-icon"><i class="fas fa-user-friends"></i></div>
                        <h3>Pasajero Estándar</h3>
                        <p>Tarifa regular para adultos</p>
                        
                        <div class="price-box">
                            2.50 Bs
                            <small>Por viaje sencillo</small>
                        </div>
                        
                        <ul class="benefits-list">
                            <li>Acceso a todas las rutas</li>
                            <li>Transferencias ilimitadas (2 horas)</li>
                            <li>Descuentos en pases mensuales</li>
                        </ul>
                    </div>
                    
                    <div class="tariff-card student">
                        <div class="discount-badge">60% Descuento</div>
                        <div class="card-icon"><i class="fas fa-graduation-cap"></i></div>
                        <h3>Pasajero Estudiante</h3>
                        <p>Tarifa especial con credencial válida</p>
                        
                        <div class="price-box">
                            1.00 Bs
                            <small>Por viaje sencillo</small>
                        </div>
                        
                        <ul class="benefits-list">
                            <li>Acceso a todas las rutas</li>
                            <li>Transferencias ilimitadas (2 horas)</li>
                            <li>Requiere credencial estudiantil vigente</li>
                        </ul>
                        
                        <a href="registro-usuarios.php" class="btn-student">Registrarse como Estudiante</a>
                    </div>
                    
                </div>
            </div>
        </section>
        
        <div class="main-content">
            <section class="features-section">
                
                <div class="feature-card">
                    <div class="icon-wrapper icon-recarga"><i class="fas fa-bolt"></i></div>
                    <h4>Recarga Rápida</h4>
                    <p>Recarga tu tarjeta en segundos con múltiples métodos de pago disponibles.</p>
                </div>
                
                <div class="feature-card">
                    <div class="icon-wrapper icon-puntos"><i class="fas fa-map-marker-alt"></i></div>
                    <h4>Puntos de Recarga</h4>
                    <p>Encuentra el punto de recarga más cercano con nuestro mapa interactivo.</p>
                </div>
                
                <div class="feature-card">
                    <div class="icon-wrapper icon-seguro"><i class="fas fa-lock"></i></div>
                    <h4>100% Seguro</h4>
                    <p>Todas las transacciones están protegidas con encriptación de última generación.</p>
                </div>
            </section>

            <section class="stats-section">
                <div class="stat-item">
                    <h3>10K+</h3>
                    <p>Usuarios Activos</p>
                </div>
                <div class="stat-item">
                    <h3>45</h3>
                    <p>Rutas Disponibles</p>
                </div>
                <div class="stat-item">
                    <h3>50+</h3>
                    <p>Puntos de Recarga</p>
                </div>
                <div class="stat-item">
                    <h3>24/7</h3>
                    <p>Soporte al Cliente</p>
                </div>
            </section>
        </div> 
        <section class="register-banner">
            <div class="text">
                <h3>¿Aún no tienes tu tarjeta?</h3>
                <p>Regístrate ahora y comienza a disfrutar de los beneficios del sistema digital</p>
            </div>
            <a href="registro-usuarios.php" class="btn-register-footer">
                Registrarse Gratis <i class="fas fa-arrow-right"></i>
            </a>
        </section>

    </div> 
    
    <!-- MODAL DE PASE DIGITAL -->
    <?php if ($user_is_logged_in): ?>
    <div class="qr-modal-overlay" id="qr-modal">
        <div class="qr-modal-content">
            <button class="qr-modal-close" id="btn-close-qr">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="qr-modal-header">
                <h2><i class="fas fa-ticket-alt"></i> Tu Pase Digital</h2>
                <p>Muestra este código al conductor</p>
            </div>
            
            <div class="qr-code-container">
                <div id="qr-code"></div>
            </div>
            
            <div class="user-info-qr">
                <p><strong><?php echo $nombre_usuario; ?></strong></p>
                <p>Saldo: <strong>Bs. <?php echo number_format($user_balance, 2); ?></strong></p>
                <p style="font-size: 0.85em; opacity: 0.8;">Usuario #<?php echo $_SESSION['usuario_id']; ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <footer class="footer">
        © 2025 Digital Transport - Sistema de Boletos Digital
    </footer>

    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <script>
        <?php if ($user_is_logged_in): ?>
        // === CONTROL DEL MODAL DE QR ===
        const btnShowQr = document.getElementById('btn-show-qr');
        const btnCloseQr = document.getElementById('btn-close-qr');
        const qrModal = document.getElementById('qr-modal');
        const qrCodeContainer = document.getElementById('qr-code');
        
        let qrGenerated = false;
        
        // Datos del usuario para el QR
        const userData = {
            usuario_id: <?php echo $_SESSION['usuario_id']; ?>,
            nombre: "<?php echo $nombre_usuario; ?>",
            tipo_usuario: <?php echo $_SESSION['tipo_usuario_id']; ?>,
            saldo: <?php echo $user_balance; ?>
        };
        
        // Generar código único para el QR
        const qrData = `DT-USER-${userData.usuario_id}-${Date.now()}`;
        
        // Mostrar modal
        if (btnShowQr) {
            btnShowQr.addEventListener('click', function() {
                qrModal.classList.add('active');
                
                // Generar QR solo la primera vez
                if (!qrGenerated) {
                    new QRCode(qrCodeContainer, {
                        text: qrData,
                        width: 200,
                        height: 200,
                        colorDark: "#0b2e88",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                    qrGenerated = true;
                }
            });
        }
        
        // Cerrar modal
        if (btnCloseQr) {
            btnCloseQr.addEventListener('click', function() {
                qrModal.classList.remove('active');
            });
        }
        
        // Cerrar al hacer clic fuera del modal
        qrModal.addEventListener('click', function(e) {
            if (e.target === qrModal) {
                qrModal.classList.remove('active');
            }
        });
        <?php endif; ?>
        
        // =====================================================
        // TEMA OSCURO - Toggle y persistencia
        // =====================================================
        const themeToggle = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;
        const themeIcon = themeToggle.querySelector('i');
        
        // Cargar tema guardado o default
        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        // Toggle al hacer click
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
```