<?php
/**
 * DIGITAL TRANSPORT - PÁGINA DE INICIO (REFACTORIZADA)
 */
$page_title = "Inicio - Digital Transport";
$active_page = "inicio";

// 1. Iniciar sesión y lógica de balance (Incluida en el header pero necesitamos el balance aquí)
require_once '../backend/includes/db.php'; // Nueva conexión PDO

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = [1, 2, 5, 6];
$user_is_logged_in = (
    isset($_SESSION['usuario_id']) && 
    in_array($_SESSION['tipo_usuario_id'], $allowed_roles)
);

// Actualización de saldo en tiempo real con PDO
if ($user_is_logged_in) {
    try {
        $stmt = $pdo->prepare("SELECT saldo FROM USUARIO WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $user_data = $stmt->fetch();
        if ($user_data) {
            $_SESSION['saldo'] = floatval($user_data['saldo']);
        }
    } catch (PDOException $e) {
        error_log("Error al actualizar saldo: " . $e->getMessage());
    }
}

$success_message = '';
if (isset($_SESSION['login_success_message'])) {
    $success_message = $_SESSION['login_success_message'];
    unset($_SESSION['login_success_message']); 
}

include 'includes/header.php';
?>

<!-- Sección Hero / Banner -->
<section class="animate-fade-in" style="margin-bottom: 60px;">
    <div class="glass-card" style="padding: 80px 60px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: white; border: none;">
        <div style="max-width: 700px;">
            <p style="text-transform: uppercase; letter-spacing: 2px; font-weight: 600; font-size: 0.85rem; margin-bottom: 16px; opacity: 0.9;">Moderno • Seguro • Digital</p>
            <h1 style="font-size: 3.5rem; line-height: 1.1; margin-bottom: 24px;">El futuro del transporte en tus manos.</h1>
            <p style="font-size: 1.2rem; margin-bottom: 40px; opacity: 0.85;">Olvida el efectivo. Recarga tu cuenta, gestiona tus pases y viaja con total comodidad usando solo tu teléfono.</p>
            
            <div style="display: flex; gap: 16px;">
                <?php if ($user_is_logged_in): ?>
                    <button class="btn btn-primary" id="btn-show-qr" style="background-color: white; color: var(--primary); padding: 16px 32px; font-size: 1.1rem;">
                        <i class="fas fa-qrcode"></i> Mostrar mi Pase Digital
                    </button>
                    <a href="recarga-digital.php" class="btn btn-secondary" style="border-color: white; color: white;">Recargar Saldo</a>
                <?php else: ?>
                    <a href="inicio-sesion-usuarios.php" class="btn btn-primary" style="background-color: white; color: var(--primary); padding: 16px 32px; font-size: 1.1rem;">Comenzar Ahora</a>
                    <a href="registro-usuarios.php" class="btn btn-secondary" style="border-color: white; color: white;">Crear Cuenta Gratis</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Sección de Tarifas -->
<section style="margin-bottom: 80px;">
    <div style="text-align: center; margin-bottom: 48px;">
        <h2 style="font-size: 2rem; margin-bottom: 8px;">Tarifas Flexibles</h2>
        <p style="color: var(--text-muted);">Precios adaptados a tus necesidades</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px;">
        <!-- Tarifa Estándar -->
        <div class="card">
            <div style="color: var(--secondary); font-size: 2.5rem; margin-bottom: 20px;"><i class="fas fa-user"></i></div>
            <h3 style="margin-bottom: 12px;">Pasajero Estándar</h3>
            <p style="color: var(--text-muted); margin-bottom: 24px;">Acceso ilimitado a todas las rutas del sistema para adultos y viajeros frecuentes.</p>
            <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 24px;">Bs. 2.50 <span style="font-size: 1rem; font-weight: 400; color: var(--text-muted);">/viaje</span></div>
            <ul style="list-style: none; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 32px;">
                <li style="margin-bottom: 8px;"><i class="fas fa-check" style="color: var(--accent); margin-right: 8px;"></i> Todas las rutas</li>
                <li style="margin-bottom: 8px;"><i class="fas fa-check" style="color: var(--accent); margin-right: 8px;"></i> Transferencias (2h)</li>
                <li><i class="fas fa-check" style="color: var(--accent); margin-right: 8px;"></i> Soporte 24/7</li>
            </ul>
        </div>

        <!-- Tarifa Estudiante -->
        <div class="card" style="border: 2px solid var(--accent); position: relative;">
            <div style="position: absolute; top: -15px; right: 20px; background: var(--accent); color: white; padding: 4px 16px; border-radius: 50px; font-size: 0.8rem; font-weight: 600;">POPULAR</div>
            <div style="color: var(--accent); font-size: 2.5rem; margin-bottom: 20px;"><i class="fas fa-graduation-cap"></i></div>
            <h3 style="margin-bottom: 12px;">Pasajero Estudiante</h3>
            <p style="color: var(--text-muted); margin-bottom: 24px;">Tarifa preferencial para estudiantes con credencial vigente. ¡Ahorra hasta un 60%!</p>
            <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 24px;">Bs. 1.00 <span style="font-size: 1rem; font-weight: 400; color: var(--text-muted);">/viaje</span></div>
            <ul style="list-style: none; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 32px;">
                <li style="margin-bottom: 8px;"><i class="fas fa-check" style="color: var(--accent); margin-right: 8px;"></i> Descuento del 60%</li>
                <li style="margin-bottom: 8px;"><i class="fas fa-check" style="color: var(--accent); margin-right: 8px;"></i> Rutas escolares</li>
                <li><i class="fas fa-check" style="color: var(--accent); margin-right: 8px;"></i> Recargas prioritarias</li>
            </ul>
            <a href="registro-usuarios.php" class="btn btn-secondary" style="width: 100%; border-color: var(--accent); color: var(--accent);">Registrarse como Estudiante</a>
        </div>
    </div>
</section>

<!-- Características -->
<section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 32px; margin-bottom: 80px;">
    <div class="glass-card" style="padding: 32px; text-align: center;">
        <div style="width: 60px; height: 60px; background: rgba(30, 136, 229, 0.1); color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.5rem;"><i class="fas fa-bolt"></i></div>
        <h4>Instantáneo</h4>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Recargas al instante con QR o tarjeta de crédito sin esperas.</p>
    </div>
    <div class="glass-card" style="padding: 32px; text-align: center;">
        <div style="width: 60px; height: 60px; background: rgba(76, 175, 80, 0.1); color: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.5rem;"><i class="fas fa-lock"></i></div>
        <h4>Seguro</h4>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Tus datos y saldo están protegidos con encriptación bancaria.</p>
    </div>
    <div class="glass-card" style="padding: 32px; text-align: center;">
        <div style="width: 60px; height: 60px; background: rgba(255, 152, 0, 0.1); color: var(--warning); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 1.5rem;"><i class="fas fa-map-marked-alt"></i></div>
        <h4>Mapa en Vivo</h4>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Ubica puntos de recarga y rutas en tiempo real desde la app.</p>
    </div>
</section>

<!-- Modal de Pase Digital -->
<?php if ($user_is_logged_in): ?>
<div id="qr-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 2000; align-items: center; justify-content: center;">
    <div class="glass-card" style="max-width: 400px; width: 90%; padding: 40px; text-align: center; position: relative; border-radius: 30px;">
        <button id="btn-close-qr" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;"><i class="fas fa-times"></i></button>
        <h2 style="margin-bottom: 10px; color: var(--primary);">Tu Pase Digital</h2>
        <p style="color: var(--text-muted); margin-bottom: 30px;">Escanea este código al subir al vehículo</p>
        <div style="background: white; padding: 20px; border-radius: 20px; display: inline-block; margin-bottom: 30px; box-shadow: var(--shadow-lg);">
            <div id="qr-code"></div>
        </div>
        <div style="background: rgba(var(--primary-h), var(--primary-s), var(--primary-l), 0.05); padding: 20px; border-radius: 15px;">
            <p style="font-weight: 700; margin-bottom: 4px;"><?php echo $_SESSION['nombre_completo']; ?></p>
            <p style="color: var(--secondary); font-weight: 600;">Saldo: Bs. <?php echo number_format($_SESSION['saldo'], 2); ?></p>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    const btnShowQr = document.getElementById('btn-show-qr');
    const btnCloseQr = document.getElementById('btn-close-qr');
    const qrModal = document.getElementById('qr-modal');
    const qrCodeContainer = document.getElementById('qr-code');
    
    let qrGenerated = false;

    if (btnShowQr) {
        btnShowQr.addEventListener('click', () => {
            qrModal.style.display = 'flex';
            if (!qrGenerated) {
                new QRCode(qrCodeContainer, {
                    text: "DT-USER-<?php echo $_SESSION['usuario_id']; ?>-" + Date.now(),
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

    btnCloseQr.addEventListener('click', () => qrModal.style.display = 'none');
    qrModal.addEventListener('click', (e) => { if(e.target === qrModal) qrModal.style.display = 'none'; });
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>