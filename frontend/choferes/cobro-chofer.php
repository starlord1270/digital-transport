<?php
/**
 * DIGITAL TRANSPORT - MÓDULO DE RECEPCIÓN DE PAGOS (CHOFER)
 * MODO: El pasajero escanea al bus.
 */
$page_title = "Estación de Cobro - Digital Transport";
$active_page = "cobro";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de Rol
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 3) {
    header("Location: ../inicio-sesion-usuarios.php");
    exit();
}

require_once '../../backend/includes/db.php';
require_once '../includes/notificaciones_display.php';

// Obtener datos del vehículo del chofer
$stmt = $pdo->prepare("SELECT vehiculo_placa, chofer_id FROM CHOFER WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$chofer = $stmt->fetch();
$placa = $chofer['vehiculo_placa'] ?? 'S/P';
$chofer_id = $chofer['chofer_id'];

include '../includes/header.php';
?>

<div class="animate-fade-in">
    <?php mostrarNotificacionesGlobales($pdo, $_SESSION['tipo_usuario_id']); ?>

    <div style="display: grid; grid-template-columns: 1fr 450px; gap: 40px; align-items: start;">
        
        <!-- Lado Izquierdo: El QR del Bus -->
        <div class="card" style="padding: 40px; text-align: center; border-top: 5px solid var(--secondary);">
            <h2 style="margin-bottom: 8px;">Unidad: <?php echo $placa; ?></h2>
            <p style="color: var(--text-muted); margin-bottom: 32px;">Los pasajeros deben escanear este código para pagar.</p>
            
            <div id="bus-qr-container" style="background: white; padding: 24px; display: inline-block; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 32px;">
                <!-- Usaremos una API de QR para generar el código de la placa -->
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=BUS_PAY_<?php echo $placa; ?>_<?php echo $chofer_id; ?>" alt="QR BUS" style="width: 300px; height: 300px;">
            </div>
            
            <div style="display: flex; justify-content: center; gap: 24px;">
                <div class="glass-card" style="padding: 16px 32px; border-bottom: 3px solid var(--accent);">
                    <span style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Recaudación Hoy</span>
                    <div id="recaudacion-hoy" style="font-size: 1.5rem; font-weight: 800; color: var(--accent);">Bs. 0.00</div>
                </div>
                <div class="glass-card" style="padding: 16px 32px; border-bottom: 3px solid var(--secondary);">
                    <span style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Pasajeros</span>
                    <div id="total-pasajeros" style="font-size: 1.5rem; font-weight: 800; color: var(--secondary);">0</div>
                </div>
            </div>
        </div>

        <!-- Lado Derecho: Feed de Pagos en Tiempo Real -->
        <div style="display: grid; gap: 24px;">
            <div class="card" style="padding: 32px; min-height: 500px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h3 style="font-size: 1.1rem;"><i class="fas fa-satellite-dish" style="color: var(--accent);"></i> Pagos Recibidos</h3>
                    <span id="status-indicator" style="font-size: 0.65rem; color: var(--accent); font-weight: 800;"><i class="fas fa-circle animate-pulse"></i> EN LÍNEA</span>
                </div>
                
                <div id="pagos-feed" style="display: grid; gap: 12px;">
                    <p style="text-align: center; color: var(--text-muted); padding: 40px;">Esperando pasajeros...</p>
                </div>
            </div>
            
            <div class="glass-card" style="padding: 24px; background: rgba(0,0,0,0.02);">
                <button class="btn btn-secondary" style="width: 100%;" onclick="solicitarCanje()">
                    <i class="fas fa-hand-holding-usd"></i> Solicitar Liquidación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sonido de Notificación -->
<audio id="ding-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3"></audio>

<script>
    const CHOFER_ID = <?php echo $chofer_id; ?>;
    const API_STATS = '../../backend/fetch_recaudacion_chofer.php';
    const API_CHECK_PAYMENTS = '../../backend/check_new_payments.php';
    const ding = document.getElementById('ding-sound');
    
    let lastPaymentId = 0;

    async function updateDashboard() {
        try {
            // 1. Actualizar Stats
            const resStats = await fetch(API_STATS);
            const dataStats = await resStats.json();
            if(dataStats.success) {
                document.getElementById('recaudacion-hoy').textContent = 'Bs. ' + parseFloat(dataStats.total_recaudado).toFixed(2);
                let totalP = 0;
                dataStats.desglose.forEach(i => totalP += parseInt(i.cantidad));
                document.getElementById('total-pasajeros').textContent = totalP;
            }

            // 2. Revisar Nuevos Pagos
            const resPay = await fetch(`${API_CHECK_PAYMENTS}?chofer_id=${CHOFER_ID}&last_id=${lastPaymentId}`);
            const dataPay = await resPay.json();
            
            if(dataPay.success && dataPay.nuevos_pagos.length > 0) {
                renderNewPayments(dataPay.nuevos_pagos);
                if (lastPaymentId !== 0) ding.play(); // Sonar si no es la primera carga
                lastPaymentId = dataPay.nuevos_pagos[0].transaccion_id;
            }
        } catch (e) { console.error(e); }
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function renderNewPayments(pagos) {
        const feed = document.getElementById('pagos-feed');
        if (lastPaymentId === 0) feed.innerHTML = ''; // Limpiar placeholder inicial
        
        pagos.forEach(p => {
            const item = document.createElement('div');
            item.className = 'glass-card animate-fade-in';
            item.style.padding = '16px';
            item.style.borderLeft = '4px solid var(--accent)';
            item.style.display = 'flex';
            item.style.justifyContent = 'space-between';
            item.style.alignItems = 'center';
            
            const safePasajero = escapeHtml(p.pasajero);
            const safeHora = escapeHtml(p.hora);
            const safeMonto = parseFloat(p.monto).toFixed(2);

            item.innerHTML = `
                <div>
                    <div style="font-weight: 700; color: var(--text-main);">${safePasajero}</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);">${safeHora}</div>
                </div>
                <div style="font-weight: 800; color: var(--accent); font-size: 1.2rem;">+ ${safeMonto} Bs</div>
            `;
            feed.prepend(item);
            
            // Mantener solo los últimos 6 visibles
            if (feed.children.length > 6) feed.lastElementChild.remove();
        });
    }

    // Polling cada 3 segundos
    setInterval(updateDashboard, 3000);
    updateDashboard();

    async function solicitarCanje() {
        if (!confirm('¿Deseas solicitar la liquidación total de tu saldo acumulado?')) return;
        try {
            const response = await fetch('../../backend/solicitar_canje.php', { method: 'POST' });
            const result = await response.json();
            alert(result.message);
        } catch (err) { alert('Error al procesar la solicitud.'); }
    }
</script>

<?php include '../includes/footer.php'; ?>