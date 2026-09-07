<?php
/**
 * DIGITAL TRANSPORT - PORTAL PRINCIPAL (LOGICA DE REDIRECCIÓN Y LANDING)
 */
$page_title = "Digital Transport - Tu Ciudad en Movimiento";
$active_page = "inicio";

require_once '../backend/includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_is_logged_in = isset($_SESSION['usuario_id']);
$user_role = $_SESSION['tipo_usuario_id'] ?? null;

// Lógica de Redirección Automática para Usuarios Logueados
if ($user_is_logged_in) {
    if ($user_role == 3) {
        header("Location: choferes/cobro-chofer.php");
        exit();
    } elseif ($user_role == 4) {
        header("Location: dashboard-admin-linea/dashboard-admin.php");
        exit();
    }
}

// Si es Pasajero logueado, le mostramos el "Pase Digital".
// 1. Verificar si tiene validación especial aprobada (solo si está logueado)
$validacion_aprobada = null;
if ($user_is_logged_in) {
    $stmt_v = $pdo->prepare("SELECT V.estado_validacion, TD.nombre FROM VALIDACION_ESPECIAL V JOIN TIPO_DESCUENTO TD ON V.tipo_desc_id = TD.tipo_desc_id WHERE V.usuario_id = ? AND V.estado_validacion = 'APROBADA' LIMIT 1");
    $stmt_v->execute([$_SESSION['usuario_id']]);
    $validacion_aprobada = $stmt_v->fetch();
}

require_once 'includes/notificaciones_display.php';

include 'includes/header.php';
?>

<?php if ($user_is_logged_in): ?>
    <!-- VISTA: DASHBOARD PASAJERO (EL PASE DIGITAL YA REFACTORIZADO) -->
    <div class="animate-fade-in">
        <?php mostrarNotificacionesGlobales($pdo, $_SESSION['tipo_usuario_id']); ?>

        <?php if ($validacion_aprobada): ?>
            <div class="glass-card" style="padding: 12px 24px; background: rgba(76, 175, 80, 0.1); border: 1px solid var(--accent); border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 32px; height: 32px; background: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <span style="font-weight: 700; color: var(--accent); font-size: 0.9rem;">Beneficio Activo: <?php echo $validacion_aprobada['nombre']; ?></span>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;">Tu solicitud fue aprobada. Ahora pagas automáticamente la tarifa reducida.</p>
                </div>
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
            <div>
                <h1 style="font-size: 2.5rem; margin-bottom: 8px;">
                    ¡Hola, <?php echo explode(' ', $_SESSION['nombre_completo'])[0]; ?>!
                    <?php if ($validacion_aprobada): ?>
                        <span style="font-size: 0.8rem; background: var(--accent); color: white; padding: 4px 12px; border-radius: 20px; vertical-align: middle; margin-left: 12px;">VALIDADO</span>
                    <?php endif; ?>
                </h1>
                <p style="color: var(--text-muted);">Tu pase digital está listo para el siguiente viaje.</p>
            </div>
            <div class="glass-card" style="padding: 12px 24px; display: flex; align-items: center; gap: 12px; border-left: 4px solid var(--accent);">
                <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Saldo</span>
                <span style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">Bs. <?php echo number_format($_SESSION['saldo'], 2); ?></span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 400px; gap: 40px; align-items: start;">
            <!-- Pase Digital -->
            <div class="card" style="padding: 40px; text-align: center;">
                <h3 style="margin-bottom: 32px; font-weight: 700; letter-spacing: -0.5px;">Tu Pase de Abordaje</h3>
                
                <div class="glass-card" style="display: inline-block; padding: 24px; background: white; margin-bottom: 32px; box-shadow: var(--shadow-lg);">
                    <img id="passenger-qr-img" src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=Cargando..." alt="QR de Usuario" style="display: block; width: 250px; height: 250px;">
                </div>

                <div style="max-width: 300px; margin: 0 auto;">
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px;">
                        Escanea el código QR del bus al abordar o muestra tu pase para realizar el cobro.
                    </p>
                    <button class="btn btn-secondary" style="width: 100%; margin-bottom: 24px;" onclick="window.location.href='escanear-bus.php'">
                        <i class="fas fa-camera"></i> Pagar Pasaje (Escanear Bus)
                    </button>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <span style="padding: 6px 12px; background: rgba(30, 136, 229, 0.1); color: var(--secondary); border-radius: 20px; font-size: 0.75rem; font-weight: 700;">Seguro</span>
                        <span style="padding: 6px 12px; background: rgba(76, 175, 80, 0.1); color: var(--accent); border-radius: 20px; font-size: 0.75rem; font-weight: 700;">Sin Contacto</span>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div style="display: grid; gap: 24px;">
                <div class="glass-card" style="padding: 32px;">
                    <h4 style="margin-bottom: 16px;">¿Sin saldo?</h4>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 24px;">Recarga ahora mismo usando QR o tarjeta para no quedarte en el camino.</p>
                    <button class="btn btn-primary" style="width: 100%;" onclick="window.location.href='recarga-digital.php'">
                        <i class="fas fa-wallet" style="margin-right: 8px;"></i> Recargar Ahora
                    </button>
                </div>

                <div class="card" style="padding: 32px;">
                    <h4 style="margin-bottom: 16px;">Última Actividad</h4>
                    <div id="mini-history" style="font-size: 0.85rem; color: var(--text-muted);">
                        Cargando actividad reciente...
                    </div>
                    <a href="historial-viaje.php" style="display: block; margin-top: 20px; color: var(--secondary); text-decoration: none; font-weight: 600; font-size: 0.85rem;">Ver historial completo →</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Cargar QR Criptográfico Firmado (HMAC-SHA256)
        async function loadSignedQR() {
            try {
                const response = await fetch('../backend/generar_qr_pago.php');
                const data = await response.json();
                if (data.success && data.qr_payload) {
                    const qrImg = document.getElementById('passenger-qr-img');
                    if (qrImg) {
                        const encoded = encodeURIComponent(data.qr_payload);
                        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encoded}`;
                    }
                }
            } catch (e) {
                console.error("Error cargando QR de seguridad:", e);
            }
        }

        // Carga rápida del mini historial
        async function loadMiniHistory() {
            try {
                const response = await fetch('../backend/fetch_history.php');
                const data = await response.json();
                const container = document.getElementById('mini-history');
                if (data.history && data.history.length > 0) {
                    const last = data.history[0];
                    container.innerHTML = `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>${last.line}</span>
                            <span style="font-weight: 700; color: var(--text-main);">${last.type === 'RECARGA' ? '+' : '-'} Bs. ${last.amount}</span>
                        </div>
                        <div style="font-size: 0.75rem;">${last.date} - ${last.time}</div>
                    `;
                } else {
                    container.textContent = "No tienes actividad reciente.";
                }
            } catch (e) { console.error(e); }
        }
        loadSignedQR();
        setInterval(loadSignedQR, 240000); // Refrescar QR cada 4 minutos
        loadMiniHistory();
    </script>

<?php else: ?>
    <!-- VISTA: LANDING PAGE PROFESIONAL (PARA USUARIOS NO LOGUEADOS) -->
    <div class="animate-fade-in">
        <!-- Hero Section -->
        <div style="text-align: center; padding: 60px 0 100px;">
            <h1 style="font-size: 4rem; font-weight: 800; letter-spacing: -2px; margin-bottom: 24px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                El transporte del futuro, hoy.
            </h1>
            <p style="font-size: 1.25rem; color: var(--text-muted); max-width: 700px; margin: 0 auto 48px; line-height: 1.6;">
                Olvida las monedas y el efectivo. Paga tu pasaje con un simple escaneo, recarga desde tu banco y viaja con total seguridad.
            </p>
            
            <div style="display: flex; gap: 20px; justify-content: center;">
                <button class="btn btn-primary" style="padding: 18px 36px; font-size: 1.1rem;" onclick="window.location.href='registro-usuarios.php'">
                    Empezar como Pasajero
                </button>
                <button class="btn btn-secondary" style="padding: 18px 36px; font-size: 1.1rem;" onclick="window.location.href='inicio-sesion-usuarios.php'">
                    Iniciar Sesión
                </button>
            </div>
        </div>

        <!-- Role Selection Cards -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 100px;">
            <div class="card glass-card" style="padding: 60px 40px; text-align: center; transition: var(--transition); border-bottom: 6px solid var(--primary);">
                <div style="font-size: 3rem; color: var(--primary); margin-bottom: 24px;"><i class="fas fa-user-friends"></i></div>
                <h2 style="font-size: 2rem; margin-bottom: 16px;">Para Pasajeros</h2>
                <p style="color: var(--text-muted); margin-bottom: 40px; line-height: 1.6;">Únete a miles de personas que ya viajan sin efectivo. Recargas instantáneas, historial de viajes y pase digital seguro.</p>
                <button class="btn btn-primary" style="width: 100%;" onclick="window.location.href='registro-usuarios.php'">Regístrate Gratis</button>
            </div>

            <div class="card glass-card" style="padding: 60px 40px; text-align: center; transition: var(--transition); border-bottom: 6px solid var(--secondary);">
                <div style="font-size: 3rem; color: var(--secondary); margin-bottom: 24px;"><i class="fas fa-bus"></i></div>
                <h2 style="font-size: 2rem; margin-bottom: 16px;">Para Choferes</h2>
                <p style="color: var(--text-muted); margin-bottom: 40px; line-height: 1.6;">Moderniza tu herramienta de trabajo. Cobros rápidos, reportes de ingresos diarios y mayor seguridad para ti y tu vehículo.</p>
                <button class="btn btn-secondary" style="width: 100%;" onclick="window.location.href='registro-chofer.php'">Unirse como Chofer</button>
            </div>
        </div>

        <!-- Features Grid -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; margin-bottom: 80px;">
            <div style="text-align: center;">
                <div style="font-size: 2rem; color: var(--accent); margin-bottom: 16px;"><i class="fas fa-bolt"></i></div>
                <h4 style="margin-bottom: 8px;">Pagos Instantáneos</h4>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Sin esperas, sin cambios. Escanea y sube al bus en segundos.</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2rem; color: var(--accent); margin-bottom: 16px;"><i class="fas fa-shield-alt"></i></div>
                <h4 style="margin-bottom: 8px;">100% Seguro</h4>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Tus transacciones están protegidas por encriptación bancaria.</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2rem; color: var(--accent); margin-bottom: 16px;"><i class="fas fa-chart-pie"></i></div>
                <h4 style="margin-bottom: 8px;">Control Total</h4>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Revisa tus gastos o ingresos con reportes detallados y claros.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>