<?php
/**
 * DIGITAL TRANSPORT - HEADER COMPONENT (PREMIUM)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lógica de sesión (Simplificada para el componente)
$allowed_roles = [1, 2, 3, 4, 5, 6];
$user_is_logged_in = (
    isset($_SESSION['usuario_id']) && 
    in_array($_SESSION['tipo_usuario_id'], $allowed_roles)
);
$is_chofer = (isset($_SESSION['tipo_usuario_id']) && $_SESSION['tipo_usuario_id'] == 3);
$is_admin_linea = (isset($_SESSION['tipo_usuario_id']) && $_SESSION['tipo_usuario_id'] == 4);
$is_super_admin = (isset($_SESSION['tipo_usuario_id']) && $_SESSION['tipo_usuario_id'] == 5);
$nombre_usuario = $user_is_logged_in ? htmlspecialchars($_SESSION['nombre_completo']) : 'Invitado';
$user_balance = $user_is_logged_in ? ($_SESSION['saldo'] ?? 0.00) : 0.00;

// Configuración de Alerta de Saldo Bajo
$low_balance_threshold = 5.00;
$show_low_balance_alert = ($user_is_logged_in && !$is_chofer && !$is_admin_linea && !$is_super_admin && $user_balance < $low_balance_threshold);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Digital Transport'; ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php
        // Detectar profundidad de ruta para archivos estáticos
        $path_prefix = (file_exists('css/style.css')) ? '' : '../';
        if (!file_exists($path_prefix . 'css/style.css')) $path_prefix = '../../';
    ?>
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>css/style.css">
    <style>
        .low-balance-alert {
            background: linear-gradient(90deg, #ff9800, #f44336);
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.5s ease-out;
            border-bottom: 2px solid rgba(0,0,0,0.1);
        }
        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php if ($show_low_balance_alert): ?>
        <div class="low-balance-alert">
            <i class="fas fa-exclamation-triangle"></i>
            <span>¡Atención! Tu saldo es bajo (Bs. <?php echo number_format($user_balance, 2); ?>). Recarga pronto para evitar inconvenientes en tu próximo viaje.</span>
            <a href="<?php echo $path_prefix; ?>recarga-digital.php" style="color: white; text-decoration: underline; margin-left: 10px;">Recargar ahora</a>
        </div>
    <?php endif; ?>
    <header class="main-header">
        <div class="container nav-flex">
            <a href="index.php" style="text-decoration: none;">
                <div class="logo">
                    Digital Transport
                    <span>Sistema de Boletos Digitales</span>
                </div>
            </a>

            <nav>
                <ul class="nav-links">
                    <?php if ($user_is_logged_in): ?>
                        <?php if ($is_chofer): ?>
                            <!-- Menú exclusivo para CHOFER -->
                            <li><a href="<?php echo $path_prefix; ?>choferes/cobro-chofer.php" class="<?php echo ($active_page == 'cobro') ? 'active' : ''; ?>"><i class="fas fa-qrcode"></i> Panel de Cobro</a></li>
                            <li><a href="<?php echo $path_prefix; ?>ayuda.php" class="<?php echo ($active_page == 'ayuda') ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> Ayuda</a></li>
                            <li><a href="<?php echo $path_prefix; ?>choferes/perfil-chofer.php" class="<?php echo ($active_page == 'perfil') ? 'active' : ''; ?>"><i class="fas fa-user-circle"></i> Perfil</a></li>
                        <?php elseif ($is_admin_linea): ?>
                            <!-- Menú exclusivo para ADMIN LÍNEA -->
                            <li><a href="<?php echo $path_prefix; ?>dashboard-admin-linea/dashboard-admin.php" class="<?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                            <li><a href="<?php echo $path_prefix; ?>ayuda.php" class="<?php echo ($active_page == 'ayuda') ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> Ayuda</a></li>
                        <?php elseif ($is_super_admin): ?>
                            <!-- Menú exclusivo para SUPER ADMIN -->
                            <li><a href="<?php echo $path_prefix; ?>dashboard-superadmin/dashboard.php" class="<?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-crown"></i> Master Panel</a></li>
                            <li><a href="<?php echo $path_prefix; ?>ayuda.php" class="<?php echo ($active_page == 'ayuda') ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> Ayuda</a></li>
                            <li><a href="<?php echo $path_prefix; ?>dashboard-superadmin/lineas.php" class="<?php echo ($active_page == 'lineas') ? 'active' : ''; ?>">Líneas</a></li>
                            <li><a href="<?php echo $path_prefix; ?>dashboard-superadmin/finanzas.php" class="<?php echo ($active_page == 'finanzas') ? 'active' : ''; ?>">Finanzas</a></li>
                        <?php else: ?>
                            <!-- Menú para PASAJEROS -->
                            <li><a href="<?php echo $path_prefix; ?>index.php" class="<?php echo ($active_page == 'inicio') ? 'active' : ''; ?>">Inicio</a></li>
                            <li><a href="<?php echo $path_prefix; ?>recarga-digital.php" class="<?php echo ($active_page == 'recarga') ? 'active' : ''; ?>">Recarga</a></li>
                            <li><a href="<?php echo $path_prefix; ?>puntos-recarga.php" class="<?php echo ($active_page == 'puntos') ? 'active' : ''; ?>">Puntos PR</a></li>
                            <li><a href="<?php echo $path_prefix; ?>historial-viaje.php" class="<?php echo ($active_page == 'historial') ? 'active' : ''; ?>">Historial</a></li>
                            <li><a href="<?php echo $path_prefix; ?>ayuda.php" class="<?php echo ($active_page == 'ayuda') ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> Ayuda</a></li>
                            <li><a href="<?php echo $path_prefix; ?>perfil-pasajero.php" class="<?php echo ($active_page == 'perfil') ? 'active' : ''; ?>"><i class="fas fa-user-circle"></i> Perfil</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo $path_prefix; ?>../backend/logout.php?redirect=index.php" class="btn btn-secondary" style="padding: 8px 16px;">Salir</a></li>
                    <?php else: ?>
                        <!-- Menú para VISITANTES -->
                        <li><a href="<?php echo $path_prefix; ?>index.php" class="<?php echo ($active_page == 'inicio') ? 'active' : ''; ?>">Inicio</a></li>
                        <li><a href="<?php echo $path_prefix; ?>inicio-sesion-usuarios.php" class="<?php echo ($active_page == 'login') ? 'active' : ''; ?>">Iniciar Sesión</a></li>
                        <li><a href="<?php echo $path_prefix; ?>registro-usuarios.php" class="btn btn-primary" style="padding: 8px 16px;">Regístrate</a></li>
                    <?php endif; ?>
                    
                    <!-- Theme Toggle -->
                    <li>
                        <button class="btn" id="theme-toggle" style="background: none; border: none; padding: 8px; color: var(--text-main);">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                </ul>
            </nav>

            <?php if ($user_is_logged_in && !$is_chofer && !$is_admin_linea && !$is_super_admin): ?>
                <div class="balance-badge">
                    <i class="fas fa-wallet"></i>
                    <span>Bs. <?php echo number_format($user_balance, 2); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <main class="container" style="padding-top: 40px; min-height: 80vh;">
