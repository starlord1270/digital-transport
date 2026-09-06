<?php
/**
 * DIGITAL TRANSPORT - GESTIÓN DE TARIFAS MAESTRAS (SUPER ADMIN)
 */
$page_title = "Configuración de Tarifas - Digital Transport";
$active_page = "tarifas";

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
    <div style="text-align: center; margin-bottom: 50px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Configuración de Tarifas Maestras</h1>
        <p style="color: var(--text-muted);">Define los precios oficiales de los pasajes que se aplicarán en todo el sistema.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; max-width: 1000px; margin: 0 auto;">
        <!-- Card Tarifa Adulto -->
        <div class="card" style="padding: 40px; text-align: center; border-top: 5px solid var(--primary);">
            <i class="fas fa-user fa-3x" style="color: var(--primary); margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 8px;">Adulto Estándar</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 32px;">Tarifa general aplicada a la mayoría de los pasajeros.</p>
            
            <div style="margin-bottom: 32px;">
                <span style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Precio Actual</span>
                <div id="precio-adulto" style="font-size: 2.5rem; font-weight: 800; color: var(--text-main);">Bs. --</div>
            </div>
            
            <button class="btn btn-primary" style="width: 100%;" onclick="editTarifa(1, 'Adulto Estándar')">
                Editar Tarifa
            </button>
        </div>

        <!-- Card Tarifa Estudiante -->
        <div class="card" style="padding: 40px; text-align: center; border-top: 5px solid var(--secondary);">
            <i class="fas fa-graduation-cap fa-3x" style="color: var(--secondary); margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 8px;">Estudiante</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 32px;">Tarifa reducida para estudiantes con carnet validado.</p>
            
            <div style="margin-bottom: 32px;">
                <span style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Precio Actual</span>
                <div id="precio-estudiante" style="font-size: 2.5rem; font-weight: 800; color: var(--text-main);">Bs. --</div>
            </div>
            
            <button class="btn btn-secondary" style="width: 100%;" onclick="editTarifa(2, 'Estudiante')">
                Editar Tarifa
            </button>
        </div>

        <!-- Card Tarifa 3ra Edad -->
        <div class="card" style="padding: 40px; text-align: center; border-top: 5px solid var(--accent);">
            <i class="fas fa-blind fa-3x" style="color: var(--accent); margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 8px;">Tercera Edad</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 32px;">Tarifa especial para adultos mayores de 60 años.</p>
            
            <div style="margin-bottom: 32px;">
                <span style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Precio Actual</span>
                <div id="precio-senior" style="font-size: 2.5rem; font-weight: 800; color: var(--text-main);">Bs. --</div>
            </div>
            
            <button class="btn btn-primary" style="width: 100%; background: var(--accent); border-color: var(--accent);" onclick="editTarifa(3, 'Tercera Edad')">
                Editar Tarifa
            </button>
        </div>
    </div>
</div>

<!-- Modal Edición -->
<div id="modal-tarifa" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
    <div class="card" style="max-width: 400px; margin: 150px auto; padding: 40px; position: relative;">
        <span onclick="closeModal()" style="position: absolute; right: 24px; top: 24px; cursor: pointer; font-size: 1.5rem; color: var(--text-muted);">&times;</span>
        <h2 id="modal-title" style="margin-bottom: 8px;">Editar Tarifa</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 24px;">El cambio se aplicará a todos los buses de inmediato.</p>
        
        <form id="form-tarifa">
            <input type="hidden" name="tarifa_id" id="edit-id">
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Nuevo Monto (Bs.)</label>
                <input type="number" name="monto" id="edit-monto" step="0.50" class="form-input" required style="font-size: 1.5rem; text-align: center; font-weight: 800;">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px;">Guardar Cambios</button>
        </form>
    </div>
</div>

<script>
    async function loadTarifas() {
        try {
            const response = await fetch('../../backend/superadmin/fetch_tarifas_global.php');
            const data = await response.json();
            if(data.success) {
                data.tarifas.forEach(t => {
                    if(t.tarifa_id == 1) document.getElementById('precio-adulto').textContent = `Bs. ${parseFloat(t.monto).toFixed(2)}`;
                    if(t.tarifa_id == 2) document.getElementById('precio-estudiante').textContent = `Bs. ${parseFloat(t.monto).toFixed(2)}`;
                    if(t.tarifa_id == 3) document.getElementById('precio-senior').textContent = `Bs. ${parseFloat(t.monto).toFixed(2)}`;
                });
            }
        } catch (e) { console.error(e); }
    }

    function editTarifa(id, nombre) {
        document.getElementById('modal-title').textContent = 'Editar Tarifa ' + nombre;
        document.getElementById('edit-id').value = id;
        document.getElementById('modal-tarifa').style.display = 'block';
    }

    function closeModal() { document.getElementById('modal-tarifa').style.display = 'none'; }

    document.getElementById('form-tarifa').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const response = await fetch('../../backend/superadmin/actualizar_tarifa.php', { method: 'POST', body: formData });
            const result = await response.json();
            if(result.success) {
                alert('Tarifa actualizada correctamente.');
                closeModal();
                loadTarifas();
            }
        } catch (err) { alert('Error al actualizar.'); }
    });

    loadTarifas();
</script>

<?php include '../includes/footer.php'; ?>
