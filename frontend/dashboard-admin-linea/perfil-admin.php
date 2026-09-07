<?php
/**
 * DIGITAL TRANSPORT - PERFIL ADMIN DE LÍNEA UI
 */
$page_title = "Perfil - Administrador de Línea";
$active_page = "perfil";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 4) {
    header("Location: ../inicio-sesion-lineas-choferes/login.php");
    exit();
}

require_once '../../backend/includes/db.php';
require_once '../../backend/includes/security.php';

include '../includes/header.php';
?>

<style>
    .perfil-container { max-width: 900px; margin: auto; background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .header-perfil { background: #5540FF; color: white; padding: 25px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
    .header-info-wrapper { display: flex; align-items: center; }
    .rf-circle { width: 60px; height: 60px; background: #FFD700; color: #5540FF; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; margin-right: 20px; }
    .info-basica { flex-grow: 1; }
    .linea-admin { display: inline-block; background: rgba(255, 255, 255, 0.2); padding: 5px 10px; border-radius: 15px; font-size: 0.9em; margin-top: 10px; }
    .header-actions { display: flex; align-items: center; gap: 15px; }
    .dashboard-btn { background-color: rgba(255, 255, 255, 0.1); color: white; text-decoration: none; padding: 8px 15px; border: 1px solid white; border-radius: 4px; transition: background-color 0.3s; display: flex; align-items: center; gap: 5px; }
    .dashboard-btn:hover { background-color: rgba(255, 255, 255, 0.2); }
    .stats { display: flex; margin-left: 20px;}
    .stat-item { padding: 0 15px; text-align: center; border-left: 1px solid rgba(255, 255, 255, 0.3); }
    .stat-item:first-child { border-left: none; }
    .stat-number { font-size: 1.8em; font-weight: bold; }
    .stat-label { font-size: 0.8em; opacity: 0.8; }
    .contenido-perfil { padding: 20px; }
    .seccion { margin-bottom: 25px; border: 1px solid #ddd; padding: 15px; border-radius: 6px; }
    .alerta { padding: 10px; margin-bottom: 10px; border-radius: 4px; display: flex; align-items: center; }
    .alerta-warning { border-left: 4px solid orange; background-color: #fff8e1; color: orange; }
    .alerta-info { border-left: 4px solid #2196F3; background-color: #e3f2fd; color: #2196F3; }
    .alerta-success { border-left: 4px solid green; background-color: #e8f5e9; color: green; }
    .icono { margin-right: 10px; }
    .info-personal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .info-personal-item { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 10px; }
    .editable-input { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1em; display: none; }
    .editable-text { display: block; }
    .edit-mode .editable-input { display: block; }
    .edit-mode .editable-text { display: none; }
    .action-buttons button { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: background-color 0.3s; }
    #btn-guardar { background-color: #4CAF50; color: white; margin-right: 10px; }
    #btn-cancelar { background-color: #f44336; color: white; }
    .password-form { display: none; margin-top: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background-color: #fafafa; }
    .password-form input { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .password-buttons button { padding: 10px 20px; margin-right: 10px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    #btn-change-password-submit { background-color: #2196F3; color: white; }
    #btn-change-password-cancel { background-color: #ddd; color: #333; }
</style>

<div class="perfil-container animate-fade-in">
    <div class="header-perfil">
        <div class="header-info-wrapper">
            <div class="rf-circle" id="user-initials">RF</div>
            <div class="info-basica">
                <h2 id="user-name">Roberto Flores Mendoza</h2>
                <p style="font-size: 0.9em; opacity: 0.9;">CI: <span id="user-ci">9876543 LP</span> | Miembro desde <span id="user-member-since">Enero 2022</span></p>
                <div class="linea-admin">
                    <span id="linea-name">Línea 3 - Centro</span>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <a href="dashboard-admin.php" class="dashboard-btn">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number" id="total-choferes">18</div>
                    <div class="stat-label">Choferes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="total-vehiculos">10</div>
                    <div class="stat-label">Vehículos</div>
                </div>
            </div>
        </div>
    </div>

    <div class="contenido-perfil">
        <h3>Alertas y Notificaciones</h3>
        <div class="seccion" id="alertas-simuladas">
            <div class="alerta alerta-warning"><span class="icono">⚠️</span> Vehículo 3456-JKL requiere mantenimiento <span style="margin-left: auto;">Hace 2 horas</span></div>
            <div class="alerta alerta-info"><span class="icono">ℹ️</span> Nuevo chofer registrado: Luis Condori <span style="margin-left: auto;">Hace 5 horas</span></div>
            <div class="alerta alerta-success"><span class="icono">✅</span> Canje completado: Carlos Mamani - 234.50 Bs <span style="margin-left: auto;">Hace 1 día</span></div>
        </div>

        <h3>Información Personal</h3>
        <div class="seccion" id="personal-info-section"> 
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h4>Datos del Administrador</h4>
                <div class="action-buttons">
                    <button id="btn-guardar" style="display: none;">Guardar</button>
                    <button id="btn-cancelar" style="display: none;">Cancelar</button>
                    <button id="btn-editar">Editar</button>
                </div>
            </div>
            <div class="info-personal-grid">
                
                <div class="info-personal-item" id="container-nombre">
                    <p style="opacity: 0.7;">Nombre Completo</p>
                    <p class="editable-text" id="info-nombre">Roberto Flores Mendoza</p>
                    <input type="text" class="editable-input" data-field="nombre_completo" id="input-nombre" value="" required>
                </div>
                
                <div class="info-personal-item" id="container-ci">
                    <p style="opacity: 0.7;">Cédula de Identidad</p>
                    <p class="editable-text" id="info-ci">9876543 LP</p>
                    <input type="text" class="editable-input" data-field="documento_identidad" id="input-ci" value="" required>
                </div>
                
                <div class="info-personal-item" id="container-email">
                    <p style="opacity: 0.7;">Correo Electrónico</p>
                    <p class="editable-text" id="info-email">roberto.flores@digitaltransport.bo</p>
                    <input type="email" class="editable-input" data-field="email" id="input-email" value="" required>
                </div>
                
                <div class="info-personal-item">
                    <p style="opacity: 0.7;">Teléfono</p>
                    <p id="info-telefono">+591 76543210</p>
                </div>
            </div>
        </div>

        <h3>Seguridad</h3>
        <div class="seccion">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                 <p>Contraseña</p>
                 <button id="btn-show-password-form">Cambiar Contraseña</button>
            </div>
            
            <form id="password-change-form" class="password-form">
                <input type="password" id="current_password" name="current_password" placeholder="Contraseña Actual" required>
                <input type="password" id="new_password" name="new_password" placeholder="Nueva Contraseña (mín. 8 caracteres)" required>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar Nueva Contraseña" required>
                <div class="password-buttons">
                    <button type="submit" id="btn-change-password-submit">Guardar Contraseña</button>
                    <button type="button" id="btn-change-password-cancel">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const API_FETCH = '../../backend/fetch_perfil_admin.php';
    const API_UPDATE = '../../backend/update-perfil-admin.php'; 
    const API_CHANGE_PASS = '../../backend/change-password-admin.php';

    let initialData = {}; 
    const section = document.getElementById('personal-info-section');
    const btnEditar = document.getElementById('btn-editar');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const passForm = document.getElementById('password-change-form');
    const btnShowPassForm = document.getElementById('btn-show-password-form');
    const btnCancelPassForm = document.getElementById('btn-change-password-cancel');

    const fieldsMap = {
        'info-nombre': 'input-nombre',
        'info-ci': 'input-ci',
        'info-email': 'input-email'
    };
    
    function getInitials(name) {
        const parts = String(name || '').trim().split(' ');
        let initials = '';
        if (parts.length >= 2) {
            initials = parts[0][0] + parts[1][0];
        } else if (parts.length === 1 && parts[0].length > 0) {
            initials = parts[0][0];
        }
        return initials.toUpperCase();
    }
    
    function setEditMode(isEditing) {
        if (isEditing) {
            passForm.style.display = 'none';
        }
        
        section.classList.toggle('edit-mode', isEditing);
        btnEditar.style.display = isEditing ? 'none' : 'block';
        btnGuardar.style.display = isEditing ? 'block' : 'none';
        btnCancelar.style.display = isEditing ? 'block' : 'none';

        if (isEditing) {
            for (const textId in fieldsMap) {
                const textElement = document.getElementById(textId);
                const inputElement = document.getElementById(fieldsMap[textId]);
                
                initialData[inputElement.dataset.field] = textElement.textContent;
                
                if (textId === 'info-ci') {
                    inputElement.value = textElement.textContent.replace(' LP', '').trim();
                } else {
                    inputElement.value = textElement.textContent.trim();
                }
            }
        }
    }

    function cancelEdit() {
        for (const textId in fieldsMap) {
            const textElement = document.getElementById(textId);
            const inputElement = document.getElementById(fieldsMap[textId]);
            textElement.textContent = initialData[inputElement.dataset.field];
        }
        setEditMode(false);
    }
    
    async function fetchInitialData() {
        try {
            const response = await fetch(API_FETCH);
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                
                document.getElementById('user-initials').textContent = getInitials(data.nombre_completo);
                document.getElementById('user-name').textContent = data.nombre_completo;
                document.getElementById('user-ci').textContent = data.documento_identidad;
                document.getElementById('user-member-since').textContent = data.miembro_desde;
                document.getElementById('linea-name').textContent = data.linea_administrada;
                document.getElementById('total-choferes').textContent = data.total_choferes;
                document.getElementById('total-vehiculos').textContent = data.total_vehiculos;

                document.getElementById('info-nombre').textContent = data.nombre_completo;
                document.getElementById('info-ci').textContent = data.documento_identidad;
                document.getElementById('info-email').textContent = data.email;
                document.getElementById('info-telefono').textContent = data.telefono; 
                
            } else {
                console.error("Error al cargar datos:", result.message);
                alert("Error: " + (result.message || 'Error de autenticación.'));
                if (result.message && result.message.includes('Acceso denegado')) {
                     window.location.href = '../inicio-sesion-lineas-choferes/login.php'; 
                }
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            alert('No se pudo conectar con el servidor para obtener los datos.');
        }
    }
    
    async function saveChanges() {
        const formData = new FormData();
        
        let hasChanges = false;
        for (const textId in fieldsMap) {
            const inputElement = document.getElementById(fieldsMap[textId]);
            const fieldName = inputElement.dataset.field;
            const newValue = inputElement.value.trim();

            if (initialData[fieldName].trim() !== newValue.trim()) {
                hasChanges = true;
            }
            formData.append(fieldName, newValue);
        }

        if (!hasChanges) {
             alert('No se detectaron cambios.');
             setEditMode(false);
             return;
        }

        if (!confirm('¿Está seguro de que desea guardar los cambios?')) {
            return;
        }

        try {
            const response = await fetch(API_UPDATE, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                
                document.getElementById('info-nombre').textContent = formData.get('nombre_completo');
                document.getElementById('info-ci').textContent = formData.get('documento_identidad') + ' LP'; 
                document.getElementById('info-email').textContent = formData.get('email');
                
                document.getElementById('user-name').textContent = formData.get('nombre_completo');
                document.getElementById('user-initials').textContent = getInitials(formData.get('nombre_completo'));

                setEditMode(false);
                
            } else {
                alert("Error al guardar: " + (result.error || result.message || 'Error en la solicitud.'));
            }

        } catch (error) {
            console.error('Error de conexión al guardar:', error);
            alert('Error de red al intentar guardar los datos.');
        }
    }

    function resetPasswordForm() {
        passForm.reset();
        passForm.style.display = 'none';
    }

    btnShowPassForm.addEventListener('click', () => {
        setEditMode(false);
        passForm.style.display = 'block';
    });

    btnCancelPassForm.addEventListener('click', resetPasswordForm);

    passForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(passForm);
        const newPass = formData.get('new_password');
        const confirmPass = formData.get('confirm_password');

        if (newPass !== confirmPass) {
            alert('La nueva contraseña y la confirmación no coinciden.');
            return;
        }

        if (newPass.length < 8) {
            alert('La nueva contraseña debe tener al menos 8 caracteres.');
            return;
        }

        try {
            const response = await fetch(API_CHANGE_PASS, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                resetPasswordForm();
            } else {
                alert("Error al cambiar contraseña: " + (result.error || result.message || 'Error al procesar la solicitud.'));
            }

        } catch (error) {
            console.error('Error de conexión al cambiar contraseña:', error);
            alert('Error de red al intentar cambiar la contraseña.');
        }
    });

    btnEditar.addEventListener('click', () => setEditMode(true));
    btnCancelar.addEventListener('click', cancelEdit);
    btnGuardar.addEventListener('click', saveChanges);

    document.addEventListener('DOMContentLoaded', fetchInitialData);
</script>

<?php include '../includes/footer.php'; ?>