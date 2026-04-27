<?php
/**
 * DIGITAL TRANSPORT - ESCANEAR BUS (PASAJERO)
 */
$page_title = "Pagar Pasaje - Digital Transport";
$active_page = "cobro";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de Sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio-sesion-usuarios.php");
    exit();
}

include 'includes/header.php';
?>

<div class="animate-fade-in" style="max-width: 600px; margin: 0 auto;">
    <div class="card" style="padding: 32px; text-align: center;">
        <h2 style="margin-bottom: 24px;">Escanea el QR del Bus</h2>
        
        <div id="scanner-container">
            <div id="qr-reader" style="width: 100%; border-radius: 12px; overflow: hidden; border: 2px solid var(--secondary); margin-bottom: 24px;"></div>
        </div>

        <div id="payment-step" style="display: none;">
            <div class="glass-card" style="padding: 24px; margin-bottom: 24px; border-left: 5px solid var(--accent);">
                <h3 id="bus-info">Detectando Bus...</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Selecciona tu tarifa para pagar</p>
            </div>

            <div id="tarifas-grid" style="display: none;">
                <!-- Oculto para modo automático -->
            </div>

            <div id="fare-info-box" style="margin-bottom: 24px; padding: 20px; background: rgba(30, 136, 229, 0.05); border-radius: 12px; border: 1px solid var(--secondary);">
                <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Tarifa Detectada</div>
                <div id="detected-fare-name" style="font-weight: 700; margin: 4px 0;">Cargando...</div>
                <div id="detected-fare-monto" style="font-size: 2rem; font-weight: 800; color: var(--secondary);">Bs. 0.00</div>
            </div>

            <button id="btn-confirmar-pago" class="btn btn-primary" style="width: 100%; padding: 16px;" disabled onclick="confirmarPago()">
                Confirmar y Pagar
            </button>
        </div>

        <div id="status-box" class="glass-card animate-fade-in" style="margin-top: 24px; padding: 20px; display: none;"></div>
        
        <a href="index.php" style="display: block; margin-top: 24px; color: var(--text-muted); text-decoration: none;">Cancelar y volver</a>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    let selectedTarifa = null;
    let busPlaca = "";
    let choferId = 0;
    let html5QrCode = null;

    async function startScanner() {
        html5QrCode = new Html5Qrcode("qr-reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess);
    }

    async function onScanSuccess(decodedText) {
        // Formato esperado: BUS_PAY_PLACA_CHOFERID
        if (decodedText.startsWith("BUS_PAY_")) {
            const parts = decodedText.split("_");
            busPlaca = parts[2];
            choferId = parts[3];
            
            await html5QrCode.stop();
            document.getElementById('scanner-container').style.display = 'none';
            showPaymentStep();
        } else {
            alert("Código QR no válido para pago de transporte.");
        }
    }

    async function showPaymentStep() {
        document.getElementById('payment-step').style.display = 'block';
        document.getElementById('bus-info').textContent = `Bus: ${busPlaca}`;
        
        // Detectar Tarifa Automática
        try {
            const res = await fetch('../backend/fetch_tarifas.php');
            const data = await res.json();
            if (data.success) {
                selectedTarifa = data.tarifa;
                document.getElementById('detected-fare-name').textContent = data.tarifa.nombre;
                document.getElementById('detected-fare-monto').textContent = `Bs. ${parseFloat(data.tarifa.monto).toFixed(2)}`;
                
                if (data.es_especial) {
                    document.getElementById('detected-fare-name').innerHTML += ' <span style="background: var(--accent); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.6rem;">VALIDADO</span>';
                }
                
                document.getElementById('btn-confirmar-pago').disabled = false;
            }
        } catch (e) { console.error(e); }
    }

    async function confirmarPago() {
        const btn = document.getElementById('btn-confirmar-pago');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        
        try {
            const response = await fetch('../backend/procesar_cobro.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bus_payment: true,
                    chofer_id: choferId,
                    tarifaId: selectedTarifa.tarifa_id,
                    monto: selectedTarifa.costo
                })
            });
            const result = await response.json();
            
            const status = document.getElementById('status-box');
            status.style.display = 'block';
            if (result.success) {
                status.style.color = 'var(--accent)';
                status.style.borderColor = 'var(--accent)';
                status.innerHTML = `<h3><i class="fas fa-check-circle"></i> ¡Pago Exitoso!</h3><p>Has pagado Bs. ${selectedTarifa.costo} a la unidad ${busPlaca}.</p>`;
                setTimeout(() => window.location.href = 'index.php', 3000);
            } else {
                status.style.color = 'var(--danger)';
                status.style.borderColor = 'var(--danger)';
                status.innerHTML = `<h3><i class="fas fa-times-circle"></i> Error</h3><p>${result.error}</p>`;
                btn.disabled = false;
                btn.textContent = 'Confirmar y Pagar';
            }
        } catch (err) {
            alert("Error de conexión");
            btn.disabled = false;
        }
    }

    startScanner();
</script>

<style>
    .tariff-option { border: 2px solid transparent; transition: all 0.3s; }
    .tariff-option:hover { background: rgba(30, 136, 229, 0.1); }
</style>

<?php include 'includes/footer.php'; ?>
