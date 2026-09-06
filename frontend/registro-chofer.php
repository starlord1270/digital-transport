<?php
/**
 * DIGITAL TRANSPORT - REGISTRO DE CHOFER (PREMIUM)
 */
$page_title = "Únete como Chofer - Digital Transport";
$active_page = "registro";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../backend/includes/db.php';

// Obtener líneas para el selector
try {
    $stmt = $pdo->query("SELECT linea_id, nombre FROM LINEA ORDER BY nombre");
    $lineas = $stmt->fetchAll();
} catch (Exception $e) {
    $lineas = [];
}

include 'includes/header.php';
?>

<div class="animate-fade-in" style="max-width: 900px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Registro para Choferes</h1>
        <p style="color: var(--text-muted);">Forma parte de la red de transporte más moderna de la ciudad.</p>
    </div>

    <div id="alert-container" style="display: none; margin-bottom: 24px;"></div>

    <div class="card" style="padding: 40px;">
        <form id="driver-register-form">
            <input type="hidden" name="tipo_usuario_id" value="3">

            <!-- Sección 1: Datos Personales -->
            <div style="margin-bottom: 40px;">
                <h3 style="margin-bottom: 24px; padding-bottom: 12px; border-bottom: 2px solid var(--bg-main);"><i class="fas fa-user-edit"></i> Datos Personales</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="nombre_completo" class="form-input" placeholder="Ej. Pedro Pérez" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cédula de Identidad</label>
                        <input type="text" name="documento_identidad" class="form-input" placeholder="Ej. 7654321" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" class="form-input" placeholder="chofer@transporte.com" required>
                </div>
            </div>

            <!-- Sección 2: Datos de Conductor -->
            <div style="margin-bottom: 40px;">
                <h3 style="margin-bottom: 24px; padding-bottom: 12px; border-bottom: 2px solid var(--bg-main);"><i class="fas fa-id-card"></i> Datos del Conductor</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label">Número de Licencia</label>
                        <input type="text" name="licencia" class="form-input" placeholder="Número de licencia" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Línea de Transporte</label>
                        <select name="linea_id" class="form-input" required>
                            <option value="">Selecciona tu línea...</option>
                            <?php foreach ($lineas as $l): ?>
                                <option value="<?php echo $l['linea_id']; ?>"><?php echo $l['nombre']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Placa del Vehículo</label>
                    <input type="text" name="vehiculo_placa" class="form-input" placeholder="Ej. 1234-ABC" required>
                </div>
            </div>

            <!-- Sección 3: Seguridad -->
            <div style="margin-bottom: 40px;">
                <h3 style="margin-bottom: 24px; padding-bottom: 12px; border-bottom: 2px solid var(--bg-main);"><i class="fas fa-shield-alt"></i> Seguridad</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" id="pass" class="form-input" placeholder="Mín. 6 caracteres" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar Contraseña</label>
                        <input type="password" name="password_confirm" id="pass-confirm" class="form-input" placeholder="Repite tu contraseña" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-secondary" style="width: 100%; padding: 18px; font-size: 1.1rem;">
                Enviar Solicitud de Registro <i class="fas fa-paper-plane" style="margin-left: 8px;"></i>
            </button>

            <p style="text-align: center; margin-top: 24px; color: var(--text-muted); font-size: 0.9rem;">
                ¿Ya eres chofer registrado? <a href="inicio-sesion-usuarios.php" style="color: var(--secondary); font-weight: 700; text-decoration: none;">Inicia sesión aquí</a>
            </p>
        </form>
    </div>
</div>

<script>
    const form = document.getElementById('driver-register-form');
    const alertCont = document.getElementById('alert-container');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const pass = document.getElementById('pass').value;
        const confirm = document.getElementById('pass-confirm').value;

        if (pass !== confirm) {
            showAlert('Las contraseñas no coinciden.', 'danger');
            return;
        }

        const formData = new FormData(form);
        
        try {
            const response = await fetch('../backend/validacion-registro.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                showAlert(result.message, 'success');
                setTimeout(() => window.location.href = 'inicio-sesion-usuarios.php', 2500);
            } else {
                showAlert(result.message, 'danger');
            }
        } catch (err) {
            showAlert('Error de conexión.', 'danger');
        }
    });

    function showAlert(msg, type) {
        alertCont.style.display = 'block';
        alertCont.innerHTML = `
            <div class="glass-card" style="padding: 16px; border-color: var(--${type}); color: var(--${type}); background: rgba(0,0,0,0.01);">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}
            </div>
        `;
        alertCont.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
</script>

<?php include 'includes/footer.php'; ?>
