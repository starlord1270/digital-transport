<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil - Chofer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS BÁSICO Y ADAPTADO */
        body { font-family: Arial, sans-serif; background-color: #f4f5f7; padding: 20px; }
        .perfil-container { max-width: 900px; margin: auto; background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .header-perfil { background: #5540FF; color: white; padding: 25px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
        
        /* Contenedor de la información izquierda (foto/nombre/rating) */
        .header-info-wrapper { display: flex; align-items: center; }
        
        .rf-circle { width: 80px; height: 80px; background: #FFD700; color: #5540FF; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: bold; margin-right: 20px; position: relative;}
        .rf-circle .star { position: absolute; bottom: -5px; right: -5px; background: #FFD700; color: #5540FF; border-radius: 50%; width: 25px; height: 25px; display: flex; justify-content: center; align-items: center; font-size: 14px; }
        
        .info-basica { flex-grow: 1; }
        .info-basica h2 { margin: 0; }
        .rating { display: inline-block; background: #FFD700; color: #333; padding: 5px 10px; border-radius: 15px; font-weight: bold; margin-top: 5px; }
        
        .stats { margin-left: 20px; text-align: right; }
        .stats p { margin: 5px 0; font-size: 0.9em; opacity: 0.9; }
        .linea-tag { display: inline-block; background: rgba(255, 255, 255, 0.2); padding: 5px 10px; border-radius: 15px; font-size: 0.9em; margin-top: 10px; }
        
        /* Botón de Dashboard */
        .dashboard-btn {
            background-color: rgba(255, 255, 255, 0.1);
            color: white; 
            text-decoration: none; 
            padding: 8px 15px; 
            border: 1px solid white; 
            border-radius: 4px;
            transition: background-color 0.3s;
            align-self: flex-start; /* Alinea arriba junto a las stats */
            margin-top: -15px;
        }
        .dashboard-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .contenido-perfil { padding: 20px; }
        .seccion { margin-bottom: 25px; border: 1px solid #ddd; padding: 15px; border-radius: 6px; }
        .info-personal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-personal-item { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 10px; }

        /* Edición de Datos Personales (Igual que Admin) */
        .editable-input { 
            width: 100%; 
            padding: 8px; 
            margin-top: 5px; 
            border: 1px solid #ccc; 
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
            display: none; 
        }
        .editable-text { display: block; }
        .edit-mode .editable-input { display: block; }
        .edit-mode .editable-text { display: none; }
        
        .action-buttons button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        #btn-guardar { background-color: #4CAF50; color: white; margin-right: 10px; }
        #btn-cancelar { background-color: #f44336; color: white; }

        /* Cambio de Contraseña (Igual que Admin) */
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
        .password-buttons button {
            padding: 10px 20px;
            margin-right: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        #btn-change-password-submit { background-color: #2196F3; color: white; }
        #btn-change-password-cancel { background-color: #ddd; color: #333; }
        
        /* Zona Peligrosa */
        .zona-peligrosa {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #f44336;
            border-radius: 6px;
        }
        .zona-peligrosa button {
            background: none;
            color: #f44336;
            border: 1px solid #f44336;
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
        <div class="header-info-wrapper">
            <div class="rf-circle" id="user-initials">CM <span class="star"><i class="fas fa-star"></i></span></div>
            <div class="info-basica">
                <h2 id="user-name">Carlos Mamani Quispe</h2>
                <p style="font-size: 0.9em; opacity: 0.9;"><span id="user-licencia">Licencia: DRV-2024-001234</span> | Miembro desde <span id="user-member-since">Marzo 2023</span></p>
                <div class="rating"><i class="fas fa-star"></i> <span id="user-rating">4.8 (3420 viajes)</span></div>
            </div>
        </div>
        
        <a href="../choferes/cobro-chofer.php" class="dashboard-btn"><i class="fas fa-arrow-left"></i> Volver a la seccion de cobro</a>

        <div class="stats">
            <p><span class="linea-tag" id="linea-name">Línea 3 - Centro</span></p>
            <p>Placa: <span id="vehiculo-placa">1234-ABC</span></p>
            <p><span id="vehiculo-modelo">Mercedes-Benz Sprinter 2022</span></p>
            <p>Capacidad: <span id="vehiculo-capacidad">18 pasajeros</span></p>
        </div>
    </div>

    <div class="contenido-perfil">
        
        <div class="seccion" id="personal-info-section"> 
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h4>Información Personal</h4>
                <div class="action-buttons">
                    <button id="btn-guardar" style="display: none;">Guardar</button>
                    <button id="btn-cancelar" style="display: none;">Cancelar</button>
                    <button id="btn-editar">Editar</button>
                </div>
            </div>
            <div class="info-personal-grid">
                
                <div class="info-personal-item" id="container-nombre">
                    <p style="opacity: 0.7;">Nombre Completo</p>
                    <p class="editable-text" id="info-nombre">Carlos Mamani Quispe</p>
                    <input type="text" class="editable-input" data-field="nombre_completo" id="input-nombre" value="" required>
                </div>
                
                <div class="info-personal-item">
                    <p style="opacity: 0.7;">Cédula de Identidad</p>
                    <p id="info-ci">9876543 LP</p>
                    </div>
                
                <div class="info-personal-item" id="container-email">
                    <p style="opacity: 0.7;">Correo Electrónico</p>
                    <p class="editable-text" id="info-email">carlos.mamani@digitaltransport.bo</p>
                    <input type="email" class="editable-input" data-field="email" id="input-email" value="" required>
                </div>
                
                <div class="info-personal-item" id="container-licencia">
                    <p style="opacity: 0.7;">Licencia de Conducir</p>
                    <p class="editable-text" id="info-licencia">DRV-2024-001234</p>
                    <input type="text" class="editable-input" data-field="licencia" id="input-licencia" value="" required>
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
                <input type="password" id="new_password" name="new_password" placeholder="Nueva Contraseña (mín. 6 caracteres)" required>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar Nueva Contraseña" required>
                <div class="password-buttons">
                    <button type="submit" id="btn-change-password-submit">Guardar Contraseña</button>
                    <button type="button" id="btn-change-password-cancel">Cancelar</button>
                </div>
            </form>
        </div>
        
        <h3>Zona Peligrosa</h3>
        <div class="zona-peligrosa">
            <p style="margin-top: 0;"><i class="fas fa-exclamation-triangle"></i> Si tienes problemas con tu cuenta, puedes cerrar sesión de emergencia aquí.</p>
            <button onclick="window.location.href='../logout.php';"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</button>
        </div>
    </div>
</div>

<script>
    // Rutas de la API
    const API_FETCH = '../../backend/fetch-perfil-chofer.php'; 
    const API_UPDATE = '../../backend/update-perfil-chofer.php'; 
    const API_CHANGE_PASS = '../../backend/change-password-chofer.php'; 

    let initialData = {}; 
    const section = document.getElementById('personal-info-section');
    const btnEditar = document.getElementById('btn-editar');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const passForm = document.getElementById('password-change-form');
    const btnShowPassForm = document.getElementById('btn-show-password-form');
    const btnCancelPassForm = document.getElementById('btn-change-password-cancel');

    // Mapeo de IDs de texto a IDs de input (Añadimos 'licencia')
    const fieldsMap = {
        'info-nombre': 'input-nombre',
        'info-email': 'input-email',
        'info-licencia': 'input-licencia' 
    };
    
    // --- FUNCIONES UTILITARIAS ---

    function getInitials(name) {
        const parts = name.split(' ');
        let initials = '';
        if (parts.length >= 2) {
            initials = parts[0][0] + parts[1][0];
        } else if (parts.length === 1) {
            initials = parts[0][0];
        }
        return initials.toUpperCase();
    }

    // --- MANEJO DE EDICIÓN DE DATOS PERSONALES ---
    
    function setEditMode(isEditing) {
        if (isEditing) {
            passForm.style.display = 'none';
        }
        
        section.classList.toggle('edit-mode', isEditing);
        btnEditar.style.display = isEditing ? 'none' : 'block';
        btnGuardar.style.display = isEditing ? 'block' : 'none';
        btnCancelar.style.display = isEditing ? 'block' : 'none';

        if (isEditing) {
            // Copiar texto actual a los inputs y guardar datos originales
            for (const textId in fieldsMap) {
                const textElement = document.getElementById(textId);
                const inputElement = document.getElementById(fieldsMap[textId]);
                
                initialData[inputElement.dataset.field] = textElement.textContent.trim();
                inputElement.value = textElement.textContent.trim();
            }
        }
    }

    function cancelEdit() {
        // Restaurar textos originales
        for (const textId in fieldsMap) {
            const textElement = document.getElementById(textId);
            const inputElement = document.getElementById(fieldsMap[textId]);
            textElement.textContent = initialData[inputElement.dataset.field];
        }
        setEditMode(false);
    }

    // --- FETCH DE DATOS INICIALES ---
    
    async function fetchInitialData() {
        try {
            const response = await fetch(API_FETCH);
            const result = await response.json();

            if (result.success) {
                const data = result.data;
                
                // Header y Estadísticas (Carga de datos)
                document.getElementById('user-initials').textContent = getInitials(data.nombre_completo);
                document.getElementById('user-name').textContent = data.nombre_completo;
                document.getElementById('user-rating').textContent = data.rating;
                document.getElementById('user-licencia').textContent = 'Licencia: ' + data.licencia;
                document.getElementById('user-member-since').textContent = data.miembro_desde;
                document.getElementById('linea-name').textContent = data.linea_name;
                document.getElementById('vehiculo-placa').textContent = data.placa;
                document.getElementById('vehiculo-modelo').textContent = data.modelo;
                document.getElementById('vehiculo-capacidad').textContent = data.capacidad + ' pasajeros';


                // Información Personal (Carga de datos en los elementos de texto)
                document.getElementById('info-nombre').textContent = data.nombre_completo;
                document.getElementById('info-ci').textContent = data.documento_identidad;
                document.getElementById('info-email').textContent = data.email;
                document.getElementById('info-licencia').textContent = data.licencia;
                
            } else {
                console.error("Error al cargar datos:", result.message);
                alert("Error: " + result.message);
                if (result.message.includes('Acceso denegado')) {
                     window.location.href = '../inicio-sesion-lineas-choferes/login.php'; 
                }
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            alert('No se pudo conectar con el servidor para obtener los datos.');
        }
    }

    // --- FUNCIÓN DE GUARDAR DATOS PERSONALES ---
    
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
                
                // Actualizar los elementos de texto
                document.getElementById('info-nombre').textContent = formData.get('nombre_completo');
                document.getElementById('info-email').textContent = formData.get('email');
                document.getElementById('info-licencia').textContent = formData.get('licencia');
                
                // Actualiza el header
                document.getElementById('user-name').textContent = formData.get('nombre_completo');
                document.getElementById('user-initials').textContent = getInitials(formData.get('nombre_completo'));
                document.getElementById('user-licencia').textContent = 'Licencia: ' + formData.get('licencia');

                setEditMode(false);
                
            } else {
                alert("Error al guardar: " + result.message);
            }

        } catch (error) {
            console.error('Error de conexión al guardar:', error);
            alert('Error de red al intentar guardar los datos.');
        }
    }

    // --- MANEJO DE CAMBIO DE CONTRASEÑA ---

    function resetPasswordForm() {
        passForm.reset();
        passForm.style.display = 'none';
    }

    btnShowPassForm.addEventListener('click', () => {
        setEditMode(false); // Cierra el modo edición si está abierto
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

        if (newPass.length < 6) {
            alert('La nueva contraseña debe tener al menos 6 caracteres.');
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
                alert("Error al cambiar contraseña: " + result.message);
            }

        } catch (error) {
            console.error('Error de conexión al cambiar contraseña:', error);
            alert('Error de red al intentar cambiar la contraseña.');
        }
    });

    // --- EVENT LISTENERS ---
    
    btnEditar.addEventListener('click', () => setEditMode(true));
    btnCancelar.addEventListener('click', cancelEdit);
    btnGuardar.addEventListener('click', saveChanges);

    // Cargar datos al iniciar
    document.addEventListener('DOMContentLoaded', fetchInitialData);
</script>
</body>
</html>