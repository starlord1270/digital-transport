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

$status_message = ''; 
$is_success = false;

// LÓGICA DE PROCESAMIENTO (REFACTORIZADA A PDO)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = trim($_POST['payment_method'] ?? '');
    
    if ($amount === false || $amount <= 0) {
        $status_message = 'Por favor, ingresa un monto de recarga válido.';
    } else {
        $usuario_id = $_SESSION['usuario_id'];
        
        try {
            $pdo->beginTransaction();
            
            // 1. Obtener saldo actual
            $stmt = $pdo->prepare("SELECT saldo FROM USUARIO WHERE usuario_id = ? FOR UPDATE");
            $stmt->execute([$usuario_id]);
            $current_balance = $stmt->fetchColumn();
            
            $new_balance = $current_balance + $amount;
            
            // 2. Actualizar saldo
            $stmt = $pdo->prepare("UPDATE USUARIO SET saldo = ? WHERE usuario_id = ?");
            $stmt->execute([$new_balance, $usuario_id]);
            
            // 3. Registrar transacción
            $stmt = $pdo->prepare("INSERT INTO TRANSACCION (usuario_id, tipo, monto, fecha_hora) VALUES (?, 'RECARGA', ?, NOW())");
            $stmt->execute([$usuario_id, $amount]);
            
            $pdo->commit();
            
            $_SESSION['saldo'] = $new_balance;
            $is_success = true;
            $status_message = "¡Recarga exitosa! Se han añadido Bs. " . number_format($amount, 2) . " a tu cuenta.";
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $status_message = "Error en el procesamiento: " . $e->getMessage();
        }
    }
}

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
            <form action="recarga-digital.php" method="POST">
                <!-- 1. Selección del Monto -->
                <div style="margin-bottom: 40px;">
                    <h3 style="margin-bottom: 24px;">1. Selecciona el monto</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 16px; margin-bottom: 24px;">
                        <?php foreach([10, 20, 50, 100] as $m): ?>
                            <div class="amount-option" onclick="setAmount(<?php echo $m; ?>)" style="padding: 20px; border: 2px solid var(--bg-main); border-radius: var(--radius-sm); text-align: center; cursor: pointer; transition: var(--transition);">
                                <span style="font-weight: 700; font-size: 1.2rem;">Bs. <?php echo $m; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Monto personalizado (Bs.)</label>
                        <input type="number" name="amount" id="custom-amount" class="form-input" placeholder="0.00" step="0.01" min="1" required>
                    </div>
                </div>

                <!-- 2. Método de Pago -->
                <div style="margin-bottom: 40px;">
                    <h3 style="margin-bottom: 24px;">2. Método de pago</h3>
                    <div style="display: grid; gap: 16px; margin-bottom: 24px;">
                        <label class="payment-method-card glass-card" style="display: flex; align-items: center; padding: 20px; cursor: pointer; gap: 20px;">
                            <input type="radio" name="payment_method" value="tarjeta" checked onchange="togglePaymentDetails('tarjeta')">
                            <div style="font-size: 1.5rem; color: var(--secondary);"><i class="fas fa-credit-card"></i></div>
                            <div>
                                <p style="font-weight: 600; margin: 0;">Tarjeta de Crédito / Débito</p>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Visa, Mastercard, Maestro</p>
                            </div>
                        </label>
                        <label class="payment-method-card glass-card" style="display: flex; align-items: center; padding: 20px; cursor: pointer; gap: 20px;">
                            <input type="radio" name="payment_method" value="qr" onchange="togglePaymentDetails('qr')">
                            <div style="font-size: 1.5rem; color: var(--accent);"><i class="fas fa-qrcode"></i></div>
                            <div>
                                <p style="font-weight: 600; margin: 0;">Pago Simple (QR)</p>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Genera un código QR para pagar desde tu banco</p>
                            </div>
                        </label>
                    </div>

                    <!-- Detalles del Pago (Dinámicos) -->
                    <div id="details-tarjeta" class="payment-details glass-card animate-fade-in" style="padding: 24px; background: rgba(0,0,0,0.02);">
                        <div style="display: grid; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label">Número de Tarjeta</label>
                                <input type="text" class="form-input" placeholder="0000 0000 0000 0000">
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group">
                                    <label class="form-label">Fecha Expiración</label>
                                    <input type="text" class="form-input" placeholder="MM/YY">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-input" placeholder="123">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="details-qr" class="payment-details glass-card animate-fade-in" style="padding: 24px; text-align: center; display: none; background: rgba(0,0,0,0.02);">
                        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 16px;">Escanea el código QR desde tu aplicación bancaria:</p>
                        <div style="width: 280px; height: 280px; background: white; border-radius: 12px; padding: 12px; margin: 0 auto; box-shadow: var(--shadow-lg); overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <img src="../recarga.jpeg?v=3" alt="QR de Pago" style="width: 100%; height: 100%; object-fit: contain; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
                        </div>
                        <p style="margin-top: 20px; font-weight: 700; font-size: 1rem; color: var(--accent); letter-spacing: 1px;">Bs. <span id="qr-amount-display">0.00</span></p>
                        <p style="margin-top: 8px; font-weight: 600; font-size: 0.8rem; color: var(--danger);">Vence en: <span id="timer">05:00</span></p>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 1.1rem;">
                    Confirmar Recarga <i class="fas fa-arrow-right"></i>
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
        const qrAmount = document.getElementById('qr-amount-display');
        if (qrAmount) qrAmount.textContent = amount.toFixed(2);
    }

    function togglePaymentDetails(type) {
        document.querySelectorAll('.payment-details').forEach(el => el.style.display = 'none');
        document.getElementById('details-' + type).style.display = 'block';
        if (type === 'qr') startTimer();
    }

    let timerInterval;
    function startTimer() {
        clearInterval(timerInterval);
        let time = 300; // 5 minutos
        const display = document.getElementById('timer');
        timerInterval = setInterval(() => {
            const minutes = Math.floor(time / 60);
            const seconds = time % 60;
            display.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            if (--time < 0) clearInterval(timerInterval);
        }, 1000);
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