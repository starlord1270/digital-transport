<?php
/**
 * DIGITAL TRANSPORT - CREAR ADMIN DE LÍNEA UI (SUPER ADMIN)
 */
$page_title = "Crear Admin de Línea - SuperAdmin";
$active_page = "crear_admin";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 5) {
    header("Location: ../inicio-sesion-usuarios.php");
    exit();
}

require_once '../../backend/includes/db.php';
require_once '../../backend/includes/security.php';

// Obtener líneas disponibles
$stmtLineas = $pdo->query("SELECT linea_id, nombre FROM LINEA ORDER BY nombre ASC");
$lineas = $stmtLineas->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="animate-fade-in" style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <div class="card" style="padding: 32px; border-top: 5px solid var(--primary);">
        <h2 style="margin-bottom: 8px;"><i class="fas fa-user-shield"></i> Registrar Administrador de Línea</h2>
        <p style="color: var(--text-muted); margin-bottom: 24px;">Exclusivo para SuperAdmin. Asigna cuentas administrativas a líneas de transporte.</p>

        <form id="crearAdminForm">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="nombre_completo" style="display: block; margin-bottom: 6px; font-weight: 600;">Nombre Completo:</label>
                <input type="text" id="nombre_completo" name="nombre_completo" class="input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="documento_identidad" style="display: block; margin-bottom: 6px; font-weight: 600;">Documento de Identidad:</label>
                <input type="text" id="documento_identidad" name="documento_identidad" class="input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="email" style="display: block; margin-bottom: 6px; font-weight: 600;">Correo Electrónico:</label>
                <input type="email" id="email" name="email" class="input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="password" style="display: block; margin-bottom: 6px; font-weight: 600;">Contraseña:</label>
                <input type="password" id="password" name="password" class="input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="linea_id" style="display: block; margin-bottom: 6px; font-weight: 600;">Línea de Transporte:</label>
                <select id="linea_id" name="linea_id" class="input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;" required>
                    <option value="">-- Seleccione una Línea --</option>
                    <?php foreach ($lineas as $l): ?>
                        <option value="<?= $l['linea_id'] ?>"><?= htmlspecialchars($l['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="cargo" style="display: block; margin-bottom: 6px; font-weight: 600;">Cargo / Rol:</label>
                <input type="text" id="cargo" name="cargo" value="Administrador de Línea" class="input" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;"><i class="fas fa-save"></i> Registrar Administrador</button>
        </form>

        <div id="resultado-msg" style="margin-top: 16px; display: none; padding: 12px; border-radius: 6px;"></div>
    </div>
</div>

<script>
document.getElementById('crearAdminForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const msgDiv = document.getElementById('resultado-msg');
    msgDiv.style.display = 'block';
    msgDiv.style.background = '#e2e3e5';
    msgDiv.style.color = '#383d41';
    msgDiv.textContent = 'Procesando...';

    const formData = new FormData(this);
    try {
        const response = await fetch('../../backend/superadmin/crear_admin_linea.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            msgDiv.style.background = '#d4edda';
            msgDiv.style.color = '#155724';
            msgDiv.textContent = data.message;
            this.reset();
        } else {
            msgDiv.style.background = '#f8d7da';
            msgDiv.style.color = '#721c24';
            msgDiv.textContent = data.error || 'Error al procesar registro.';
        }
    } catch (err) {
        msgDiv.style.background = '#f8d7da';
        msgDiv.style.color = '#721c24';
        msgDiv.textContent = 'Error de conexión con el servidor.';
    }
});
</script>

<?php include '../includes/footer.php'; ?>
