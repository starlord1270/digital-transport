<?php
/**
 * DIGITAL TRANSPORT - PERFIL DE CHOFER (REFACTORIZADA)
 */
$page_title = "Mi Perfil Chofer - Digital Transport";
$active_page = "perfil";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificación de Rol
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 3) {
    header("Location: ../inicio-sesion-usuarios.php");
    exit();
}

include '../includes/header.php';
?>

<div class="animate-fade-in">
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Perfil del Conductor</h1>
        <p style="color: var(--text-muted);">Gestiona tu información profesional y personal.</p>
    </div>

    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 40px; align-items: start;">
        <!-- Sidebar: Info del Vehículo y Rating -->
        <div style="display: grid; gap: 24px;">
            <div class="card" style="text-align: center; padding: 40px 24px;">
                <div id="user-initials" style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--secondary) 0%, #1a237e 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; margin: 0 auto 20px; box-shadow: var(--shadow-md);">
                    --
                </div>
                <h3 id="profile-name" style="margin-bottom: 4px;">Cargando...</h3>
                <p id="linea-display" style="font-size: 0.85rem; color: var(--secondary); font-weight: 700; margin-bottom: 12px;">--</p>
                <div style="display: inline-flex; align-items: center; gap: 8px; background: var(--bg-main); padding: 6px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 24px;">
                    <i class="fas fa-star" style="color: var(--warning);"></i> <span id="rating-display">0.0</span>
                </div>
                
                <div style="text-align: left; background: rgba(0,0,0,0.02); padding: 20px; border-radius: var(--radius-sm); border-left: 4px solid var(--secondary);">
                    <p style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 12px; letter-spacing: 1px;">Vehículo Asignado</p>
                    <div style="display: grid; gap: 12px;">
                        <div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Placa</span>
                            <div id="placa-display" style="font-weight: 700; font-family: monospace; font-size: 1.1rem;">--</div>
                        </div>
                        <div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Modelo</span>
                            <div id="modelo-display" style="font-weight: 600; font-size: 0.9rem;">--</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card" style="padding: 24px;">
                <h4 style="margin-bottom: 16px;">Atajos</h4>
                <button class="btn btn-primary" style="width: 100%;" onclick="window.location.href='cobro-chofer.php'">
                    <i class="fas fa-qrcode"></i> Panel de Cobro
                </button>
            </div>
        </div>

        <!-- Formularios Principal -->
        <div class="card" style="padding: 40px;">
            <div style="margin-bottom: 48px;">
                <h3 style="margin-bottom: 32px; padding-bottom: 12px; border-bottom: 2px solid var(--bg-main);"><i class="fas fa-user-circle"></i> Información del Conductor</h3>
                <form id="profile-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                        <div class="form-group">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" name="nombre_completo" id="field-name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cédula de Identidad</label>
                            <input type="text" id="field-ci" class="form-input" disabled style="opacity: 0.6; cursor: not-allowed;">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                        <div class="form-group">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" name="email" id="field-email" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Número de Licencia</label>
                            <input type="text" name="licencia" id="field-licencia" class="form-input" required>
                        </div>
                    </div>
                    <div style="text-align: right; margin-top: 32px;">
                        <button type="submit" class="btn btn-primary">Actualizar Mi Información</button>
                    </div>
                </form>
            </div>

            <div style="margin-top: 48px;">
                <h3 style="margin-bottom: 32px; padding-bottom: 12px; border-bottom: 2px solid var(--bg-main);"><i class="fas fa-key"></i> Seguridad</h3>
                <form id="password-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; align-items: flex-end;">
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
                        <button type="submit" class="btn btn-secondary">Cambiar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    async function loadProfile() {
        try {
            const response = await fetch('../../backend/fetch-perfil-chofer.php');
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            const data = result.data;
            document.getElementById('profile-name').textContent = data.nombre_completo;
            document.getElementById('linea-display').textContent = data.linea_name || 'Sin Línea';
            document.getElementById('rating-display').textContent = data.rating;
            document.getElementById('placa-display').textContent = data.placa || '---';
            document.getElementById('modelo-display').textContent = data.modelo || '---';
            
            document.getElementById('field-name').value = data.nombre_completo;
            document.getElementById('field-email').value = data.email;
            document.getElementById('field-ci').value = data.documento_identidad;
            document.getElementById('field-licencia').value = data.licencia;
            
            // Iniciales
            const names = data.nombre_completo.split(' ');
            document.getElementById('user-initials').textContent = (names[0][0] + (names[1] ? names[1][0] : '')).toUpperCase();
            
        } catch (err) { 
            console.error('Error cargando perfil:', err);
            // Mostrar un mensaje más descriptivo en la interfaz
            document.getElementById('profile-name').textContent = 'Error al cargar';
            document.getElementById('profile-name').style.color = 'var(--danger)';
            alert('Error al cargar el perfil: ' + err.message);
        }
    }

    document.getElementById('profile-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const response = await fetch('../../backend/update-perfil-chofer.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) loadProfile();
        } catch (err) { alert('Error al actualizar perfil'); }
    });

    document.getElementById('password-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const response = await fetch('../../backend/change-password-chofer.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) e.target.reset();
        } catch (err) { alert('Error al cambiar contraseña'); }
    });

    loadProfile();
</script>

<?php include '../includes/footer.php'; ?>
