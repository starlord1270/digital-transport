<?php
/**
 * DIGITAL TRANSPORT - RECARGA DIGITAL (REFACTORIZADA)
 */
$page_title = "Recarga Digital - Digital Transport";
$active_page = "recarga";

require_once '../backend/includes/db.php'; // Conexión PDO

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = [1, 2, 5, 6];
$user_is_logged_in = (
    isset($_SESSION['usuario_id']) && 
    in_array($_SESSION['tipo_usuario_id'], $allowed_roles)
);

if (!$user_is_logged_in) {
    header("Location: inicio-sesion-usuarios.php");
    exit();
}

// Actualización de saldo en tiempo real
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

// Las recargas se procesan EXCLUSIVAMENTE a través de backend/procesar_recarga.php
// (solicitud) y backend/confirmar_recarga.php (acreditación por operador/superadmin).
// Este archivo ya NO acredita saldo por sí mismo: solo muestra el formulario.
$status_message = '';
$is_success = false;

include 'includes/header.php';
?>

<div class="animate-fade-in">
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Recarga tu Saldo</h1>
        <p style="color: var(--text-muted);">Añade fondos a tu cuenta de forma instantánea y segura.</p>
    </div>

    <?php if ($status_message): ?>
        <div class="glass-card" style="padding: 20px; margin-bottom: 30px; background: <?php echo $is_success ? 'rgba(76, 175, 80, 0.1)' : 'rgba(244, 67, 54, 0.1)'; ?>; border-color: <?php echo $is_success ? 'var(--accent)' : 'var(--danger)'; ?>; color: <?php echo $is_success ? 'var(--accent)' : 'var(--danger)'; ?>;">
            <i class="fas <?php echo $is_success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>" style="margin-right: 10px;"></i>
            <?php echo $status_message; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 40px; align-items: start;">
        <!-- Formulario de Recarga -->
        <div class="card">
            <form id="recargaForm" action="../backend/procesar_recarga.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
                
                <!-- 1. Selección del Monto -->
                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 24px;">1. Selecciona el monto</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 16px; margin-bottom: 24px;">
                        <?php foreach([10, 20, 50, 100] as $m): ?>
                            <div class="amount-option" onclick="setAmount(<?php echo $m; ?>)" style="padding: 20px; border: 2px solid var(--bg-main); border-radius: var(--radius-sm); text-align: center; cursor: pointer; transition: var(--transition);">
                                <span style="font-weight: 700; font-size: 1.2rem;">Bs. <?php echo $m; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Monto a recargar (Bs.)</label>
                        <input type="number" name="amount" id="custom-amount" class="form-input" placeholder="0.00" step="0.01" min="1" max="5000" required>
                    </div>
                </div>

                <!-- 2. Método de Pago -->
                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 16px;">2. Método de pago</h3>
                    <div class="payment-method-card glass-card" style="display: flex; align-items: center; padding: 20px; gap: 16px;">
                        <div style="font-size: 1.5rem; color: var(--accent);"><i class="fas fa-qrcode"></i></div>
                        <div>
                            <p style="font-weight: 600; margin: 0;">Pago Bancario / Transferencia (QR)</p>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 4px 0 0;">Realiza la transferencia o el pago QR y registra el número de referencia o comprobante. El saldo se acredita tras la confirmación del operador del punto de recarga.</p>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 20px; margin-bottom: 24px;">
                        <label class="form-label">Número de Referencia de Pago / Comprobante Bancario</label>
                        <input type="text" name="referencia_pago" id="referencia_pago" class="form-input" placeholder="Ej: 894561230" required minlength="6">
                        <small style="color: var(--text-muted); display: block; margin-top: 4px;">Ingrese el nro. de transacción o comprobante emitido por su banco.</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 1.1rem;">
                    Confirmar y Procesar Recarga <i class="fas fa-shield-alt"></i>
                </button>
            </form>
        </div>


        <!-- Resumen de Cuenta -->
        <div class="glass-card" style="padding: 32px;">
            <h3 style="margin-bottom: 24px;">Resumen de Cuenta</h3>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--text-muted);">Usuario</span>
                    <span style="font-weight: 600;"><?php echo $_SESSION['nombre_completo']; ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--text-muted);">Saldo Actual</span>
                    <span style="font-weight: 700; color: var(--secondary);">Bs. <?php echo number_format($_SESSION['saldo'], 2); ?></span>
                </div>
                <hr style="border: none; border-top: 1px solid rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 1.2rem;">
                    <span style="font-weight: 600;">Total a pagar</span>
                    <span id="total-display" style="font-weight: 800; color: var(--primary);">Bs. 0.00</span>
                </div>
            </div>

            <div style="margin-top: 40px; font-size: 0.8rem; color: var(--text-muted); text-align: center;">
                <i class="fas fa-shield-alt"></i> Transacción protegida con encriptación SSL
            </div>
        </div>
    </div>
</div>

<script>
    const customAmount = document.getElementById('custom-amount');
    const totalDisplay = document.getElementById('total-display');
    const options = document.querySelectorAll('.amount-option');

    function setAmount(val) {
        customAmount.value = val;
        updateDisplay(val);
        
        options.forEach(opt => {
            opt.style.borderColor = 'var(--bg-main)';
            opt.style.backgroundColor = 'transparent';
            // Arreglado: Comparación exacta para evitar que "10" active el cuadro de "100"
            if(opt.textContent.trim() === 'Bs. ' + val) {
                opt.style.borderColor = 'var(--secondary)';
                opt.style.backgroundColor = 'rgba(30, 136, 229, 0.05)';
            }
        });
    }

    function updateDisplay(val) {
        const amount = parseFloat(val) || 0;
        totalDisplay.textContent = 'Bs. ' + amount.toFixed(2);
    }

    customAmount.addEventListener('input', (e) => {
        updateDisplay(e.target.value);
        options.forEach(opt => {
            opt.style.borderColor = 'var(--bg-main)';
            opt.style.backgroundColor = 'transparent';
        });
    });

    // Inicializar
    setAmount(50);
</script>

<?php include 'includes/footer.php'; ?>