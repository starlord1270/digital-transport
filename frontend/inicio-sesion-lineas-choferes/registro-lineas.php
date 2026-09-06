<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Registro de Usuarios</title>
    
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .registro-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); width: 100%; max-width: 450px; text-align: center; }
        h1 { color: #0056b3; margin-bottom: 20px; }
        h2 { color: #333; margin-bottom: 20px; font-size: 1.5em; }
        .form-group { margin-bottom: 15px; text-align: left; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="email"], input[type="password"], select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; /* Asegura que el padding no desborde el ancho */
        }
        .btn-group { display: flex; justify-content: space-around; margin-bottom: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; width: 48%; position: relative;}
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-success { background-color: #28a745; color: white; }
        .hidden { display: none !important; }
        .debug-id-label { position: absolute; top: -10px; right: 5px; background: orange; color: white; padding: 2px 5px; border-radius: 3px; font-size: 0.7em; }
        /* Estilos para el mensaje de estado */
        #message { margin-top: 20px; padding: 10px; border-radius: 4px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="registro-container">
        <h1>Digital Transport</h1>
        <h2>Registro de Nuevo Usuario</h2>

        <form id="registroForm">
            
            <label>Selecciona tu Rol:</label>
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" id="btnChofer">
                    Chofer 
                    <span class="debug-id-label">ID: 3</span>
                </button>
                <button type="button" class="btn btn-success" id="btnAdmin">
                    Admin. Línea
                    <span class="debug-id-label">ID: 4</span>
                </button>
            </div>

            <input type="hidden" name="tipo_usuario_id" id="tipo_usuario_id" value="4"> 
            
            <div class="form-group">
                <label for="nombre_completo">Nombre Completo:</label>
                <input type="text" id="nombre_completo" name="nombre_completo" placeholder="Ej: Juan Pérez Morales" required>
            </div>
            <div class="form-group">
                <label for="documento_identidad">Documento de Identidad:</label>
                <input type="text" id="documento_identidad" name="documento_identidad" placeholder="Ej: 8976543 (Cédula de Identidad)" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Ej: juan.perez@lineaX.com" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required>
            </div>

            <div id="adminFields">
                <div class="form-group">
                    <label for="cargo">Cargo/Puesto:</label>
                    <input type="text" id="cargo" name="cargo" placeholder="Ej: Gerente de Operaciones" required>
                </div>
                <div class="form-group">
                    <label for="linea_id_admin">ID de Línea/Ruta:</label>
                    <select id="linea_id_admin" name="linea_id" required>
                        <option value="">-- Seleccione una Línea --</option>
                        <option value="1">Línea Central (Admin Maestro)</option>
                        <option value="2">Línea 1 - Ruta Norte</option>
                        <option value="103">Línea 103</option>
                        <option value="106">Línea 106</option>
                        <option value="108">Línea 108</option>
                        <option value="110">Línea 110</option>
                        <option value="115">Línea 115</option>
                        <option value="123">Línea 123</option>
                        <option value="130">Línea 130</option>
                        <option value="209">Línea 209</option>
                        <option value="224">Línea 224</option>
                        <option value="240">Línea 240</option>
                        <option value="244">Línea 244</option>
                        <option value="260">Línea 260</option>
                        <option value="270">Línea 270</option>
                        <option value="290">Línea 290</option>
                    </select>
                </div>
            </div>

            <div id="choferFields" class="hidden">
                <div class="form-group">
                    <label for="licencia">Número de Licencia:</label>
                    <input type="text" id="licencia" name="licencia_disabled" placeholder="Ej: 456789 B" required disabled>
                </div>
                <div class="form-group">
                    <label for="vehiculo_placa">Placa del Vehículo:</label>
                    <input type="text" id="vehiculo_placa" name="vehiculo_placa_disabled" placeholder="Ej: 2568-XYZ" required disabled> 
                </div>
                <div class="form-group">
                    <label for="linea_id_chofer">ID de Línea Asignada:</label>
                    <select id="linea_id_chofer" name="linea_id_chofer_input_disabled" required disabled> 
                        <option value="">-- Seleccione una Línea --</option>
                        <option value="1">Línea Central (Admin Maestro)</option>
                        <option value="2">Línea 1 - Ruta Norte</option>
                        <option value="103">Línea 103</option>
                        <option value="106">Línea 106</option>
                        <option value="108">Línea 108</option>
                        <option value="110">Línea 110</option>
                        <option value="115">Línea 115</option>
                        <option value="123">Línea 123</option>
                        <option value="130">Línea 130</option>
                        <option value="209">Línea 209</option>
                        <option value="224">Línea 224</option>
                        <option value="240">Línea 240</option>
                        <option value="244">Línea 244</option>
                        <option value="260">Línea 260</option>
                        <option value="270">Línea 270</option>
                        <option value="290">Línea 290</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-success" id="btnRegistrar">Registrar</button>
        </form>

        <p id="message" class="hidden"></p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables de Elementos y Constantes
            const btnChofer = document.getElementById('btnChofer');
            const btnAdmin = document.getElementById('btnAdmin');
            const inputTipoUsuarioId = document.getElementById('tipo_usuario_id');
            const adminFields = document.getElementById('adminFields');
            const choferFields = document.getElementById('choferFields');
            const cargoInput = document.getElementById('cargo');
            // Elementos SELECT para la línea (ahora son select, no inputs de texto)
            const lineaAdminSelect = document.getElementById('linea_id_admin'); 
            const licenciaInput = document.getElementById('licencia');
            const vehiculoPlacaInput = document.getElementById('vehiculo_placa'); 
            const lineaChoferSelect = document.getElementById('linea_id_chofer');
            
            const ROLE_CHOFER = 3;
            const ROLE_ADMIN = 4;
            const form = document.getElementById('registroForm');
            const messageElement = document.getElementById('message');

            // Función para alternar los campos de Chofer/Admin
            function setRole(roleId) {
                inputTipoUsuarioId.value = roleId;

                if (roleId === ROLE_ADMIN) { 
                    btnAdmin.classList.add('btn-success');
                    btnAdmin.classList.remove('btn-secondary');
                    btnChofer.classList.remove('btn-success');
                    btnChofer.classList.add('btn-secondary');

                    adminFields.classList.remove('hidden');
                    cargoInput.removeAttribute('disabled');
                    lineaAdminSelect.removeAttribute('disabled');
                    lineaAdminSelect.name = 'linea_id'; // Habilitar el select del Admin
                    cargoInput.name = 'cargo';
                    
                    choferFields.classList.add('hidden');
                    licenciaInput.setAttribute('disabled', 'disabled');
                    vehiculoPlacaInput.setAttribute('disabled', 'disabled'); 
                    lineaChoferSelect.setAttribute('disabled', 'disabled');
                    lineaChoferSelect.name = 'linea_id_chofer_input_disabled';
                    licenciaInput.name = 'licencia_disabled'; 
                    vehiculoPlacaInput.name = 'vehiculo_placa_disabled'; 
                } else if (roleId === ROLE_CHOFER) {
                    btnChofer.classList.add('btn-success');
                    btnChofer.classList.remove('btn-secondary');
                    btnAdmin.classList.remove('btn-success');
                    btnAdmin.classList.add('btn-secondary');
                    
                    choferFields.classList.remove('hidden');
                    licenciaInput.removeAttribute('disabled');
                    vehiculoPlacaInput.removeAttribute('disabled'); 
                    lineaChoferSelect.removeAttribute('disabled');
                    lineaChoferSelect.name = 'linea_id'; // Habilitar el select del Chofer
                    licenciaInput.name = 'licencia';
                    vehiculoPlacaInput.name = 'vehiculo_placa';

                    adminFields.classList.add('hidden');
                    cargoInput.setAttribute('disabled', 'disabled');
                    lineaAdminSelect.setAttribute('disabled', 'disabled');
                    lineaAdminSelect.name = 'linea_id_admin_input_disabled'; 
                    cargoInput.name = 'cargo_disabled';
                }
            }

            // Inicialización y Listeners de Rol
            setRole(ROLE_ADMIN);
            btnAdmin.addEventListener('click', function(e) { e.preventDefault(); setRole(ROLE_ADMIN); });
            btnChofer.addEventListener('click', function(e) { e.preventDefault(); setRole(ROLE_CHOFER); });
            
            
            // =======================================================
            // === MANEJO DE ENVÍO ASÍNCRONO DEL FORMULARIO (AJAX) ===
            // =======================================================
            form.addEventListener('submit', function(e) {
                e.preventDefault(); 

                // RUTA HACIA EL BACKEND (Ajusta si es necesario)
                const targetUrl = '../../backend/validacion-registro.php'; 

                // Mostrar estado de carga
                messageElement.classList.remove('hidden');
                messageElement.style.backgroundColor = '#ffffcc';
                messageElement.style.color = '#333';
                messageElement.textContent = 'Procesando registro...';

                const formData = new FormData(form);

                fetch(targetUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Verificar si la respuesta es JSON antes de parsear
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json();
                    } else {
                        // Si no es JSON (ej: error 500), intentar leer como texto
                        return response.text().then(text => {
                             throw new Error("Respuesta no JSON: " + text);
                        });
                    }
                })
                .then(data => {
                    // Mostrar el mensaje devuelto por el PHP
                    messageElement.textContent = data.message;

                    if (data.success) {
                        messageElement.style.backgroundColor = '#d4edda'; 
                        messageElement.style.color = '#155724';
                        
                        // Redirige al login
                        setTimeout(() => {
                            window.location.href = 'login.php'; 
                        }, 2000); 

                    } else {
                        // En caso de error
                        messageElement.style.backgroundColor = '#f8d7da'; 
                        messageElement.style.color = '#721c24';
                    }
                })
                .catch(error => {
                    // Manejar errores de red/servidor (incluyendo el error no JSON)
                    console.error('Error de comunicación con el servidor:', error);
                    messageElement.textContent = 'Error crítico de servidor. Revise la consola (F12) o la ruta del backend.';
                    messageElement.style.backgroundColor = '#f8d7da';
                    messageElement.style.color = '#721c24';
                });
            });
            
        });
    </script>
</body>
</html>