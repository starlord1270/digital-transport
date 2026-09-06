<?php
/**
 * DIGITAL TRANSPORT - REGISTRO DE USUARIO (REFACTORIZADA)
 */
$page_title = "Regístrate - Digital Transport";
$active_page = "registro";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
?>

<div class="animate-fade-in" style="max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Únete a Digital Transport</h1>
        <p style="color: var(--text-muted);">Crea tu cuenta y comienza a viajar de forma inteligente.</p>
    </div>

    <div id="alert-container" style="display: none; margin-bottom: 24px;"></div>

    <div class="card" style="padding: 40px;">
        <form id="register-form" enctype="multipart/form-data">
            <div style="margin-bottom: 40px;">
                <h3 style="margin-bottom: 20px; font-size: 1.2rem;">1. Selecciona tu perfil</h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    <label class="account-option glass-card" style="display: block; cursor: pointer; padding: 20px; position: relative; transition: var(--transition);">
                        <input type="radio" name="perfil_tipo" value="estandar" checked onclick="toggleDocs(false)" style="position: absolute; opacity: 0;">
                        <div class="option-content" style="text-align: center;">
                            <i class="fas fa-user fa-2x" style="color: var(--primary); margin-bottom: 12px;"></i>
                            <h4 style="margin: 0; font-size: 0.9rem;">Pasajero Estándar</h4>
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin: 8px 0 0;">Edad: 25 - 59 años</p>
                        </div>
                    </label>
                    <label class="account-option glass-card" style="display: block; cursor: pointer; padding: 20px; position: relative; transition: var(--transition);">
                        <input type="radio" name="perfil_tipo" value="estudiante" onclick="toggleDocs(true, 'Certificado de estudios o Carnet Universitario')" style="position: absolute; opacity: 0;">
                        <div class="option-content" style="text-align: center;">
                            <i class="fas fa-graduation-cap fa-2x" style="color: var(--secondary); margin-bottom: 12px;"></i>
                            <h4 style="margin: 0; font-size: 0.9rem;">Estudiante</h4>
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin: 8px 0 0;">Requiere comprobante</p>
                        </div>
                    </label>
                    <label class="account-option glass-card" style="display: block; cursor: pointer; padding: 20px; position: relative; transition: var(--transition);">
                        <input type="radio" name="perfil_tipo" value="tercera_edad" onclick="toggleDocs(true, 'Certificado de jubilación o Cédula (60+ años)')" style="position: absolute; opacity: 0;">
                        <div class="option-content" style="text-align: center;">
                            <i class="fas fa-blind fa-2x" style="color: var(--accent); margin-bottom: 12px;"></i>
                            <h4 style="margin: 0; font-size: 0.9rem;">Tercera Edad</h4>
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin: 8px 0 0;">60 años en adelante</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Sección de Documentación (Condicional) -->
            <div id="section-docs" style="display: none; margin-bottom: 40px; padding: 24px; background: rgba(30, 136, 229, 0.05); border-radius: 12px; border: 1px solid var(--secondary);">
                <h3 style="margin-bottom: 16px; font-size: 1.1rem;"><i class="fas fa-file-upload"></i> Documentación Requerida</h3>
                <p id="docs-help" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;"></p>
                <div class="form-group">
                    <label class="form-label">Subir Comprobante (PDF o Imagen)</label>
                    <input type="file" name="comprobante" class="form-input" style="padding: 8px;">
                </div>
            </div>

            <!-- Datos Personales -->
            <div style="margin-bottom: 40px;">
                <h3 style="margin-bottom: 20px; font-size: 1.2rem;">2. Información personal</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" name="nombre_completo" class="form-input" placeholder="Ej. Juan Pérez" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cédula de Identidad</label>
                        <input type="text" name="documento_identidad" class="form-input" placeholder="Ej. 1234567" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" class="form-input" placeholder="tu@correo.com" required>
                </div>
            </div>

            <!-- Seguridad -->
            <div style="margin-bottom: 40px;">
                <h3 style="margin-bottom: 20px; font-size: 1.2rem;">3. Seguridad</h3>
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

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 1.1rem;">
                Crear mi Cuenta <i class="fas fa-user-plus" style="margin-left: 8px;"></i>
            </button>

            <p style="text-align: center; margin-top: 24px; color: var(--text-muted); font-size: 0.9rem;">
                ¿Ya tienes una cuenta? <a href="inicio-sesion-usuarios.php" style="color: var(--secondary); font-weight: 600; text-decoration: none;">Inicia sesión aquí</a>
            </p>
        </form>
    </div>
</div>

<style>
    .account-option input:checked + .option-content {
        transform: scale(1.05);
    }
    .account-option:has(input:checked) {
        border-color: var(--secondary) !important;
        background: rgba(30, 136, 229, 0.05) !important;
        box-shadow: var(--shadow-md) !important;
    }
    .account-option:hover {
        border-color: var(--secondary);
    }
</style>

<script>
    function toggleDocs(show, helpText = '') {
        const section = document.getElementById('section-docs');
        const help = document.getElementById('docs-help');
        section.style.display = show ? 'block' : 'none';
        help.textContent = helpText;
        
        // El input file es obligatorio si se muestra la sección
        const inputFile = section.querySelector('input[type="file"]');
        if (show) inputFile.setAttribute('required', 'required');
        else inputFile.removeAttribute('required');
    }

    const form = document.getElementById('register-form');
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
            
            const text = await response.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error("Respuesta no-JSON recibida:", text);
                throw new Error('El servidor devolvió una respuesta inválida.');
            }
            
            if (result.success) {
                showAlert(result.message, 'success');
                setTimeout(() => window.location.href = 'inicio-sesion-usuarios.php', 2000);
            } else {
                showAlert(result.message, 'danger');
            }
        } catch (err) {
            showAlert('Error: ' + err.message, 'danger');
        }
    });

    function showAlert(msg, type) {
        alertCont.style.display = 'block';
        alertCont.innerHTML = `
            <div class="glass-card" style="padding: 16px; border-color: var(--${type}); color: var(--${type}); background: rgba(0,0,0,0.02);">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}
            </div>
        `;
        alertCont.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
</script>

<?php include 'includes/footer.php'; ?>