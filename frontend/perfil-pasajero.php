<?php
/**
 * DIGITAL TRANSPORT - PERFIL DE USUARIO (REFACTORIZADA)
 */
$page_title = "Mi Perfil - Digital Transport";
$active_page = "perfil";

require_once '../backend/includes/db.php'; // Conexión PDO

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_is_logged_in = (
    isset($_SESSION['usuario_id']) && 
    in_array($_SESSION['tipo_usuario_id'], [1, 2, 5, 6])
);

if (!$user_is_logged_in) {
    header("Location: inicio-sesion-usuarios.php");
    exit();
}

include 'includes/header.php';
?>

<div class="animate-fade-in">
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Configuración de Perfil</h1>
        <p style="color: var(--text-muted);">Gestiona tu información personal y preferencias de cuenta.</p>
    </div>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 40px; align-items: start;">
        <!-- Avatar y Tarjeta Rápida -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <div class="card" style="text-align: center; padding: 40px 24px;">
                <div id="user-initials" style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; margin: 0 auto 20px; box-shadow: var(--shadow-md);">
                    --
                </div>
                <h3 id="profile-name" style="margin-bottom: 4px;">Cargando...</h3>
                <p id="profile-role" style="font-size: 0.85rem; color: var(--secondary); font-weight: 600; margin-bottom: 20px;">Pasajero</p>
                <div style="background: var(--bg-main); padding: 12px; border-radius: var(--radius-sm); font-size: 0.8rem; color: var(--text-muted);">
                    Miembro desde: <span id="profile-date" style="color: var(--text-main); font-weight: 500;">--</span>
                </div>
            </div>

            <div class="glass-card" style="padding: 24px;">
                <h4 style="margin-bottom: 16px;">Acciones Rápidas</h4>
                <div style="display: grid; gap: 12px;">
                    <button class="btn btn-secondary" style="width: 100%; justify-content: flex-start;" onclick="window.location.href='recarga-digital.php'">
                        <i class="fas fa-wallet"></i> Recargar Saldo
                    </button>
                    <button class="btn btn-secondary" style="width: 100%; justify-content: flex-start;" onclick="window.location.href='historial-viaje.php'">
                        <i class="fas fa-history"></i> Ver Historial
                    </button>
                </div>
            </div>
        </div>

        <!-- Formularios de Edición -->
        <div class="card">
            <div style="margin-bottom: 40px;">
                <h3 style="margin-bottom: 24px; padding-bottom: 12px; border-bottom: 2px solid var(--bg-main);">Información Personal</h3>
                <form id="profile-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                        <div class="form-group">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" name="nombre_completo" id="field-name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Documento de Identidad</label>
                            <input type="text" id="field-ci" class="form-input" disabled style="opacity: 0.6; cursor: not-allowed;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" id="field-email" class="form-input" required>
                    </div>
                    <div style="text-align: right; margin-top: 32px;">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>

            <div style="margin-top: 60px;">
                <h3 style="margin-bottom: 24px; padding-bottom: 12px; border-bottom: 2px solid var(--bg-main);">Seguridad</h3>
                <form id="password-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; align-items: end;">
                        <div class="form-group">
                            <label class="form-label">Contraseña Actual</label>
                            <input type="password" name="current_password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" name="new_password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirmar Nueva</label>
                            <input type="password" name="confirm_password" class="form-input" required>
                        </div>
                    </div>
                    <div style="text-align: right; margin-top: 24px;">
                        <button type="submit" class="btn btn-secondary">Actualizar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    async function loadProfile() {
        try {
            const response = await fetch('../backend/fetch-perfil-pasajero.php');
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            const data = result.data;
            document.getElementById('profile-name').textContent = data.nombre_completo;
            document.getElementById('profile-role').textContent = data.tipo_pasajero;
            document.getElementById('profile-date').textContent = data.miembro_desde;
            document.getElementById('field-name').value = data.nombre_completo;
            document.getElementById('field-email').value = data.email;
            document.getElementById('field-ci').value = data.documento_identidad;
            
            // Iniciales
            const names = data.nombre_completo.split(' ');
            document.getElementById('user-initials').textContent = (names[0][0] + (names[1] ? names[1][0] : '')).toUpperCase();
            
        } catch (err) {
            console.error(err);
        }
    }

    document.getElementById('profile-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const response = await fetch('../backend/update-perfil-pasajero.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) loadProfile();
        } catch (err) { alert('Error al actualizar perfil'); }
    });

    document.getElementById('password-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const response = await fetch('../backend/change-password-pasajero.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) e.target.reset();
        } catch (err) { alert('Error al cambiar contraseña'); }
    });

    loadProfile();
</script>

<?php include 'includes/footer.php'; ?>