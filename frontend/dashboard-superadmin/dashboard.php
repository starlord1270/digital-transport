<?php
/**
 * DIGITAL TRANSPORT - PANEL DE CONTROL MASTER (SUPER ADMIN)
 */
$page_title = "Panel de Control Master - Digital Transport";
$active_page = "dashboard";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad: Solo Super Admin (5)
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 5) {
    header("Location: ../inicio-sesion-usuarios.php");
    exit();
}

require_once '../../backend/includes/db.php';
include '../includes/header.php';
?>

<div class="animate-fade-in">
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 2.8rem; margin-bottom: 8px;">¡Bienvenido, Master Admin!</h1>
        <p style="color: var(--text-muted); font-size: 1.1rem;">Tienes el control total de la infraestructura de transporte.</p>
    </div>

    <!-- Grid de Herramientas Maestras -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 40px;">
        
        <!-- 1. Gestión de Líneas -->
        <a href="lineas.php" class="card tool-card">
            <i class="fas fa-bus fa-2x"></i>
            <h3>Líneas y Rutas</h3>
            <p>Control de empresas de transporte.</p>
        </a>

        <!-- 2. Usuarios Globales -->
        <a href="usuarios.php" class="card tool-card">
            <i class="fas fa-users-cog fa-2x"></i>
            <h3>Usuarios Globales</h3>
            <p>Supervisión de todas las cuentas.</p>
        </a>

        <!-- 3. Finanzas Globales -->
        <a href="finanzas.php" class="card tool-card">
            <i class="fas fa-chart-line fa-2x"></i>
            <h3>Monitor Financiero</h3>
            <p>Recaudación total en tiempo real.</p>
        </a>

        <!-- 4. Tarifas Maestras -->
        <a href="tarifas.php" class="card tool-card">
            <i class="fas fa-coins fa-2x"></i>
            <h3>Tarifas Maestras</h3>
            <p>Ajuste de precios del pasaje.</p>
        </a>

        <!-- 5. Auditoría -->
        <a href="auditoria.php" class="card tool-card">
            <i class="fas fa-shield-alt fa-2x"></i>
            <h3>Registro Auditoría</h3>
            <p>Historial de acciones críticas.</p>
        </a>

        <!-- 6. Comunicados -->
        <a href="notificaciones.php" class="card tool-card">
            <i class="fas fa-bullhorn fa-2x"></i>
            <h3>Comunicación</h3>
            <p>Emisión de avisos globales.</p>
        </a>

        <!-- 7. Puntos de Recarga -->
        <a href="puntos-recarga.php" class="card tool-card">
            <i class="fas fa-store fa-2x"></i>
            <h3>Red de Recargas</h3>
            <p>Gestión de puntos de venta.</p>
        </a>

        <!-- 8. Soporte -->
        <a href="soporte.php" class="card tool-card">
            <i class="fas fa-headset fa-2x"></i>
            <h3>Soporte y Disputas</h3>
            <p>Resolución de problemas.</p>
        </a>

    </div>

    <style>
        .tool-card {
            text-decoration: none;
            padding: 40px 24px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .tool-card:hover {
            transform: translateY(-10px);
            background: rgba(30, 136, 229, 0.05);
            border-color: var(--secondary);
        }
        .tool-card i {
            margin-bottom: 20px;
            color: var(--secondary);
        }
        .tool-card h3 {
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: var(--text-main);
        }
        .tool-card p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
    </style>
</div>

<?php include '../includes/footer.php'; ?>
