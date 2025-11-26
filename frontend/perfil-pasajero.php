<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil - Pasajero</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primary: #5540FF;
            --color-secondary: #1e88e5;
            --color-success: #4CAF50;
            --color-error: #f44336;
        }

        body { font-family: Arial, sans-serif; background-color: #f4f5f7; padding: 20px; }
        .perfil-container { max-width: 650px; margin: auto; background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        
        /* HEADER AZUL CLARO (PASAJERO) */
        .header-perfil { 
            background: #1e88e5; 
            color: white; 
            padding: 25px; 
            border-radius: 8px 8px 0 0; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        
        /* Contenedor Izquierdo */
        .user-info { display: flex; align-items: flex-start; }
        .user-avatar { width: 80px; height: 80px; background: white; color: #1e88e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: bold; margin-right: 20px; position: relative;}
        
        .user-details h2 { margin: 0; }
        .user-details p { font-size: 0.9em; opacity: 0.9; margin: 2px 0; }
        .tag-estudiante { display: inline-block; background: var(--color-success); color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.8em; margin-left: 10px; font-weight: bold; }
        
        /* Saldo y Botones */
        .saldo-area { text-align: right; }
        .saldo-area h3 { margin: 0; font-size: 1.2em; opacity: 0.8; }
        .saldo-amount { font-size: 2em; font-weight: bold; margin-top: 5px; }
        
        .action-buttons-header { margin-top: 10px; display: flex; gap: 10px; }
        .action-buttons-header button {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid white;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8em;
            transition: background-color 0.2s;
        }
        .action-buttons-header button:hover { background-color: rgba(255, 255, 255, 0.3); }

        /* Contenido Principal */
        .contenido-perfil { padding: 20px; }
        .seccion { margin-bottom: 25px; padding: 10px 0; }
        .seccion h4 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 5px; }

        /* Estilo de Edición (Nombre y Email) */
        .info-personal-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
            border: 1px solid #ddd; 
            padding: 15px; 
            border-radius: 6px;
        }
        .info-item { padding-bottom: 10px; }
        .info-item label { display: block; opacity: 0.7; font-size: 0.9em; margin-bottom: 3px; }

        .editable-text, .editable-input { margin-bottom: 10px; }
        .editable-input { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ccc; 
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
            display: none; 
        }
        .edit-mode .editable-input { display: block; }
        .edit-mode .editable-text { display: none; }
        
        /* Botones de Guardar/Cancelar */
        .action-buttons-edit { margin-top: 15px; text-align: right; }
        .action-buttons-edit button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        #btn-guardar-personal { background-color: var(--color-success); color: white; margin-right: 10px; }
        #btn-cancelar-personal { background-color: #f0f0f0; color: #333; }
        #btn-editar-personal { background-color: #f0f0f0; color: #333; }

        /* Notificaciones y Preferencias (Checkboxes y Listas) */
        .config-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .config-item p { margin: 0; font-weight: 500; }
        .config-item small { display: block; opacity: 0.7; font-size: 0.8em; }

        /* Seguridad */
        .security-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-top: 1px solid #eee; }
        .security-item i { margin-right: 10px; opacity: 0.7; }
        .security-item button { 
            background: none; 
            border: none; 
            color: var(--color-secondary); 
            cursor: pointer; 
            font-weight: bold;
        }
        
        .password-form { 
            display: none; 
            margin-top: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fafafa;
        }
        .password-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        /* Zona Peligrosa - Ahora Contiene Volver a Inicio */
        .zona-peligrosa {
            margin-top: 30px;
            padding: 15px;
            border-radius: 6px;
        }
        .zona-peligrosa button {
            background: #1e88e5; /* Color secundario para botón de inicio */
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="perfil-container">
    <div class="header-perfil">
        <div class="user-info">
            <div class="user-avatar" id="user-initials">--</div>
            <div class="user-details">
                <h2 id="user-name">Cargando Nombre <span id="user-type-tag" class="tag-estudiante">Cargando...</span></h2>
                <p>Miembro desde <span id="user-member-since">...</span></p>
                <button style="background: none; border: none; color: white; opacity: 0.8; padding: 0; margin-top: 5px; cursor: pointer;" id="btn-edit-header-info">
                     <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
        
        <div class="saldo-area">
            <h3>Saldo Disponible</h3>
            <div class="saldo-amount" id="user-saldo">--.-- Bs</div>
            <div class="action-buttons-header">
                <a href="recarga-digital.php"> <button id="btn-recharge"><i class="fas fa-wallet"></i> Recargar</button> </a>
                </div>
        </div>
    </div>

    <div class="contenido-perfil">
        
        <div class="seccion" id="personal-info-section">
            <h4>Información Personal</h4>
            <div style="display: flex; justify-content: flex-end;">
                <button id="btn-editar-personal">Editar</button>
            </div>
            
            <div class="info-personal-grid">
                
                <div class="info-item">
                    <label>Nombre Completo</label>
                    <p class="editable-text" id="info-nombre">Cargando...</p>
                    <input type="text" class="editable-input" data-field="nombre_completo" id="input-nombre" value="" required>
                </div>
                
                <div class="info-item">
                    <label>Cédula de Identidad</label>
                    <p id="info-ci">Cargando...</p>
                </div>
                
                <div class="info-item">
                    <label>Correo Electrónico</label>
                    <p class="editable-text" id="info-email">Cargando...</p>
                    <input type="email" class="editable-input" data-field="email" id="input-email" value="" required>
                </div>
            </div>
             <div class="action-buttons-edit">
                <button id="btn-guardar-personal" style="display: none;">Guardar Cambios</button>
                <button id="btn-cancelar-personal" style="display: none;">Cancelar</button>
            </div>
        </div>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <div class="seccion">
            <h4><i class="fas fa-cog"></i> Configuración de Cuenta</h4>
            
            <div class="config-item">
                <div>
                    <p>Alertas de Saldo Bajo</p>
                    <small>Notificar cuando el saldo sea menor a 10 Bs</small>
                </div>
                <input type="checkbox" checked>
            </div>
            
            <div class="config-item">
                <div>
                    <p>Confirmación de Viajes</p>
                    <small>Recibir notificación después de cada viaje</small>
                </div>
                <input type="checkbox" checked>
            </div>
            
            <div class="config-item">
                <div>
                    <p>Promociones y Ofertas</p>
                    <small>Información sobre descuentos especiales</small>
                </div>
                <input type="checkbox">
            </div>
        </div>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <div class="seccion">
            <h4><i class="fas fa-shield-alt"></i> Seguridad</h4>
            
            <div class="security-item">
                <div><i class="fas fa-lock"></i> Cambiar Contraseña</div>
                <button id="btn-show-password-form">Cambiar</button>
            </div>
             <form id="password-change-form" class="password-form">
                <input type="password" id="current_password" name="current_password" placeholder="Contraseña Actual" required>
                <input type="password" id="new_password" name="new_password" placeholder="Nueva Contraseña (mín. 6 caracteres)" required>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar Nueva Contraseña" required>
                <div style="text-align: right;">
                    <button type="submit" style="background-color: var(--color-secondary); color: white; padding: 8px 15px; border-radius: 4px;">Guardar Contraseña</button>
                    <button type="button" id="btn-change-password-cancel" style="background-color: #ddd; color: #333; padding: 8px 15px; border-radius: 4px;">Cancelar</button>
                </div>
            </form>
            
            </div>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <div class="zona-peligrosa">
            <p style="margin-top: 0; font-weight: bold;"><i class="fas fa-home"></i> Navegación</p>
            <button onclick="window.location.href='index.php';"><i class="fas fa-arrow-left"></i> Volver a la Página Principal</button>
        </div>
    </div>
</div>

<script>
    // ----------------------------------------------------
    // I. CONFIGURACIÓN Y RUTAS DE API
    // ----------------------------------------------------
    const API_FETCH = '../backend/fetch-perfil-pasajero.php';
    const API_UPDATE = '../backend/update-perfil-pasajero.php'; 
    const API_CHANGE_PASS = '../backend/change-password-pasajero.php'; 

    let initialData = {}; 
    
    // Asignación de elementos del DOM
    const personalInfoSection = document.getElementById('personal-info-section');
    const btnEditarPersonal = document.getElementById('btn-editar-personal');
    const btnGuardarPersonal = document.getElementById('btn-guardar-personal');
    const btnCancelarPersonal = document.getElementById('btn-cancelar-personal');
    const btnShowQR = document.getElementById('btn-show-qr'); 

    const passForm = document.getElementById('password-change-form');
    const btnShowPassForm = document.getElementById('btn-show-password-form');
    const btnCancelPassForm = document.getElementById('btn-change-password-cancel');

    const fieldsMap = {
        'info-nombre': 'input-nombre',
        'info-email': 'input-email',
    };
    
    // ----------------------------------------------------
    // II. FUNCIONES UTILITARIAS Y DE VISTA
    // ----------------------------------------------------
    
    function getInitials(name) {
        if (!name) return '--';
        const parts = name.trim().split(' ').filter(p => p.length > 0);
        let initials = '';
        if (parts.length > 0) {
            initials += parts[0][0];
        }
        if (parts.length >= 2) {
            initials += parts[1][0];
        }
        return initials.toUpperCase();
    }
    
    // Función auxiliar para asignar valores solo si el ID existe
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };


    function setEditMode(isEditing) {
        if (!personalInfoSection) return;
        personalInfoSection.classList.toggle('edit-mode', isEditing);
        
        if(btnEditarPersonal) btnEditarPersonal.style.display = isEditing ? 'none' : 'block';
        if(btnGuardarPersonal) btnGuardarPersonal.style.display = isEditing ? 'block' : 'none';
        if(btnCancelarPersonal) btnCancelarPersonal.style.display = isEditing ? 'block' : 'none';

        if (isEditing) {
            for (const textId in fieldsMap) {
                const textElement = document.getElementById(textId);
                const inputElement = document.getElementById(fieldsMap[textId]);
                
                if(textElement && inputElement) {
                    initialData[inputElement.dataset.field] = textElement.textContent.trim();
                    inputElement.value = textElement.textContent.trim();
                }
            }
        }
    }

    function cancelEdit() {
        for (const textId in fieldsMap) {
            const textElement = document.getElementById(textId);
            const inputElement = document.getElementById(fieldsMap[textId]);
            if (textElement && inputElement) {
                textElement.textContent = initialData[inputElement.dataset.field];
            }
        }
        setEditMode(false);
    }

    function resetPasswordForm() {
        if(passForm) passForm.reset();
        if(passForm) passForm.style.display = 'none';
    }

    // ----------------------------------------------------
    // III. FETCH Y CARGA INICIAL DE DATOS
    // ----------------------------------------------------
    
    async function fetchInitialData() {
        try {
            const response = await fetch(API_FETCH);
            
            if (!response.ok) {
                 throw new Error(`Error de red HTTP: ${response.status} - El servidor no respondió con éxito.`);
            }

            const result = await response.json();

            if (result.success) {
                const data = result.data;
                
                // --- CARGA DE DATOS USANDO FUNCIONES SEGURAS ---
                
                // Header
                setText('user-initials', getInitials(data.nombre_completo));
                setText('user-name', data.nombre_completo);
                setText('user-member-since', data.miembro_desde);
                setText('user-saldo', parseFloat(data.saldo).toFixed(2) + ' Bs');
                
                // Tipo de Usuario
                const userTag = document.getElementById('user-type-tag');
                if (userTag) {
                    userTag.textContent = data.tipo_pasajero;
                    userTag.style.backgroundColor = (data.tipo_pasajero === 'Estudiante') ? 'var(--color-success)' : 'var(--color-secondary)';
                }
                
                // Información Personal
                setText('info-nombre', data.nombre_completo);
                setText('info-ci', data.documento_identidad);
                setText('info-email', data.email);
                
                // El campo initialData.qr_code ya no se necesita
                
            } else {
                console.error("Error al cargar datos:", result.message);
                alert("Error: " + result.message);
                
                if (result.message.includes('Acceso denegado') || result.message.includes('sesión no válida')) {
                     window.location.href = '../inicio-sesion-lineas-choferes/login.php'; 
                }
            }
        } catch (error) {
            console.error('Error de conexión o datos:', error);
            alert(`¡Alerta! No se pudo obtener la respuesta del servidor. Revise la consola.`); 
        }
    }

    // ----------------------------------------------------
    // IV. FUNCIONALIDAD DE GUARDAR Y SEGURIDAD
    // ----------------------------------------------------
    
    async function saveChanges() {
        const formData = new FormData();
        let hasChanges = false;
        
        for (const textId in fieldsMap) {
            const inputElement = document.getElementById(fieldsMap[textId]);
            if (!inputElement) continue; 

            const fieldName = inputElement.dataset.field;
            const newValue = inputElement.value.trim();

            if (initialData[fieldName] && initialData[fieldName].trim() !== newValue.trim()) {
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
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                setText('info-nombre', formData.get('nombre_completo'));
                setText('info-email', formData.get('email'));
                setText('user-name', formData.get('nombre_completo'));
                setText('user-initials', getInitials(formData.get('nombre_completo')));

                setEditMode(false);
                
            } else {
                alert("Error al guardar: " + result.message);
            }

        } catch (error) {
            console.error('Error de conexión al guardar:', error);
            alert(`Error de red al intentar guardar los datos: ${error.message}.`);
        }
    }
    
    // --- MANEJO DE CAMBIO DE CONTRASEÑA ---
    
    if (btnShowPassForm) btnShowPassForm.addEventListener('click', () => {
        if(passForm) passForm.style.display = 'block';
    });

    if (btnCancelPassForm) btnCancelPassForm.addEventListener('click', resetPasswordForm);

    if (passForm) passForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const newPass = document.getElementById('new_password').value;
        const confirmPass = document.getElementById('confirm_password').value;
        
        if (newPass !== confirmPass) {
            alert('La nueva contraseña y la confirmación no coinciden.');
            return;
        }
        
        try {
            const response = await fetch(API_CHANGE_PASS, { 
                method: 'POST', 
                body: new FormData(passForm) 
            });
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                alert(result.message);
                resetPasswordForm(); 
            } else {
                alert('Fallo al actualizar contraseña: ' + result.message);
            }
        } catch (error) {
            console.error('Error de red al cambiar contraseña:', error);
            alert(`Error de red. No se pudo cambiar la contraseña: ${error.message}.`);
        }
    });

    // ----------------------------------------------------
    // V. EVENT LISTENERS
    // ----------------------------------------------------
    
    // Edición de Datos Personales
    if (btnEditarPersonal) btnEditarPersonal.addEventListener('click', () => setEditMode(true));
    if (btnCancelarPersonal) btnCancelarPersonal.addEventListener('click', cancelEdit);
    if (btnGuardarPersonal) btnGuardarPersonal.addEventListener('click', saveChanges);
    
    const btnEditHeaderInfo = document.getElementById('btn-edit-header-info');
    if (btnEditHeaderInfo) btnEditHeaderInfo.addEventListener('click', () => setEditMode(true));

    // El listener del botón de recarga se eliminó porque el <a> se encarga de la redirección.
    
    // 🛑 LLAMADA DE CARGA INMEDIATA
    fetchInitialData();
    
</script>
</body>
</html>