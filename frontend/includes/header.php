<?php
/**
 * DIGITAL TRANSPORT - HEADER COMPONENT (PREMIUM)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lógica de sesión (Simplificada para el componente)
$allowed_roles = [1, 2, 5, 6];
$user_is_logged_in = (
    isset($_SESSION['usuario_id']) && 
    in_array($_SESSION['tipo_usuario_id'], $allowed_roles)
);
$nombre_usuario = $user_is_logged_in ? htmlspecialchars($_SESSION['nombre_completo']) : 'Invitado';
$user_balance = $user_is_logged_in ? ($_SESSION['saldo'] ?? 0.00) : 0.00;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Digital Transport'; ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
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
                    <li><a href="index.php" class="<?php echo ($active_page == 'inicio') ? 'active' : ''; ?>">Inicio</a></li>
                    <?php if ($user_is_logged_in): ?>
                        <li><a href="recarga-digital.php" class="<?php echo ($active_page == 'recarga') ? 'active' : ''; ?>">Recarga</a></li>
                        <li><a href="puntos-recarga.php" class="<?php echo ($active_page == 'puntos') ? 'active' : ''; ?>">Puntos PR</a></li>
                        <li><a href="historial-viaje.php" class="<?php echo ($active_page == 'historial') ? 'active' : ''; ?>">Historial</a></li>
                        <li><a href="perfil-pasajero.php" class="<?php echo ($active_page == 'perfil') ? 'active' : ''; ?>"><i class="fas fa-user-circle"></i> Perfil</a></li>
                        <li><a href="../backend/logout.php?redirect=index.php" class="btn btn-secondary" style="padding: 8px 16px;">Salir</a></li>
                    <?php else: ?>
                        <li><a href="inicio-sesion-usuarios.php" class="<?php echo ($active_page == 'login') ? 'active' : ''; ?>">Iniciar Sesión</a></li>
                        <li><a href="registro-usuarios.php" class="btn btn-primary" style="padding: 8px 16px;">Regístrate</a></li>
                    <?php endif; ?>
                    
                    <!-- Theme Toggle -->
                    <li>
                        <button class="btn" id="theme-toggle" style="background: none; border: none; padding: 8px; color: var(--text-main);">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                </ul>
            </nav>

            <?php if ($user_is_logged_in): ?>
                <div class="balance-badge">
                    <i class="fas fa-wallet"></i>
                    <span>Bs. <?php echo number_format($user_balance, 2); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <main class="container" style="padding-top: 40px; min-height: 80vh;">
