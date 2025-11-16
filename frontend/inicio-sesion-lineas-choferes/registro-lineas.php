<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Registro de Usuarios (DEBUG)</title>
    
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .registro-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); width: 100%; max-width: 450px; text-align: center; }
        h1 { color: #0056b3; margin-bottom: 20px; }
        h2 { color: #333; margin-bottom: 20px; font-size: 1.5em; }
        .form-group { margin-bottom: 15px; text-align: left; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-group { display: flex; justify-content: space-around; margin-bottom: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; width: 48%; position: relative;}
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-success { background-color: #28a745; color: white; }
        .hidden { display: none !important; }
        .debug-id-label { position: absolute; top: -10px; right: 5px; background: orange; color: white; padding: 2px 5px; border-radius: 3px; font-size: 0.7em; }
    </style>
</head>
<body>
    <div class="registro-container">
        <h1>Digital Transport</h1>
        <h2>Registro de Nuevo Usuario</h2>

        <form id="registroForm" action="/Competencia-Analisis/backend/validacion-registro.php" method="POST">
    </form>
            
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

            <div class="form-group" style="border: 1px dashed red; padding: 10px;">
                <label for="tipo_usuario_id" style="color: red; font-weight: bold;">ID de Tipo de Usuario (DEBUG):</label>
                <input type="text" name="tipo_usuario_id" id="tipo_usuario_id" value="4" readonly> 
            </div>
            
            <div class="form-group">
                <label for="nombre_completo">Nombre Completo:</label>
                <input type="text" id="nombre_completo" name="nombre_completo" required>
            </div>
            <div class="form-group">
                <label for="documento_identidad">Documento de Identidad:</label>
                <input type="text" id="documento_identidad" name="documento_identidad" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div id="adminFields">
                <div class="form-group">
                    <label for="cargo">Cargo/Puesto:</label>
                    <input type="text" id="cargo" name="cargo" required>
                </div>
                <div class="form-group">
                    <label for="linea_id_admin">ID de Línea/Ruta:</label>
                    <input type="text" id="linea_id_admin" name="linea_id" required>
                </div>
            </div>

            <div id="choferFields" class="hidden">
                <div class="form-group">
                    <label for="licencia">Número de Licencia:</label>
                    <input type="text" id="licencia" name="licencia" required disabled>
                </div>
                <div class="form-group">
                    <label for="linea_id_chofer">ID de Línea Asignada:</label>
                    <input type="text" id="linea_id_chofer" name="linea_id_chofer_input" required disabled> 
                </div>
            </div>
            
            <button type="submit" class="btn btn-success" id="btnRegistrar">Registrar</button>
        </form>

        <p id="message" class="hidden">Mensaje de estado</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnChofer = document.getElementById('btnChofer');
            const btnAdmin = document.getElementById('btnAdmin');
            const inputTipoUsuarioId = document.getElementById('tipo_usuario_id');
            const adminFields = document.getElementById('adminFields');
            const choferFields = document.getElementById('choferFields');
            
            // Campos específicos
            const cargoInput = document.getElementById('cargo');
            const lineaAdminInput = document.getElementById('linea_id_admin');
            const licenciaInput = document.getElementById('licencia');
            const lineaChoferInput = document.getElementById('linea_id_chofer');

            // Función para alternar la selección y campos
            function setRole(roleId) {
                // 1. ACTUALIZAR EL CAMPO TIPO_USUARIO_ID (VISIBILIDAD)
                inputTipoUsuarioId.value = roleId;

                if (roleId === 4) { // ADMIN_LINEA (ID 4)
                    // Estilos
                    btnAdmin.classList.add('btn-success');
                    btnAdmin.classList.remove('btn-secondary');
                    btnChofer.classList.remove('btn-success');
                    btnChofer.classList.add('btn-secondary');

                    // Visibilidad y obligatoriedad para ADMIN
                    adminFields.classList.remove('hidden');
                    cargoInput.removeAttribute('disabled');
                    lineaAdminInput.removeAttribute('disabled');
                    
                    // Ocultar y deshabilitar CHOFER
                    choferFields.classList.add('hidden');
                    licenciaInput.setAttribute('disabled', 'disabled');
                    lineaChoferInput.setAttribute('disabled', 'disabled');
                    // IMPORTANTE: Asegurar que solo el campo de línea de Admin envíe 'linea_id'
                    lineaAdminInput.name = 'linea_id';
                    lineaChoferInput.name = 'linea_id_chofer_input_disabled';


                } else if (roleId === 3) { // CHOFER (ID 3)
                    // Estilos
                    btnChofer.classList.add('btn-success');
                    btnChofer.classList.remove('btn-secondary');
                    btnAdmin.classList.remove('btn-success');
                    btnAdmin.classList.add('btn-secondary');
                    
                    // Visibilidad y obligatoriedad para CHOFER
                    choferFields.classList.remove('hidden');
                    licenciaInput.removeAttribute('disabled');
                    lineaChoferInput.removeAttribute('disabled');

                    // Ocultar y deshabilitar ADMIN
                    adminFields.classList.add('hidden');
                    cargoInput.setAttribute('disabled', 'disabled');
                    lineaAdminInput.setAttribute('disabled', 'disabled');
                    // IMPORTANTE: Asegurar que solo el campo de línea de Chofer envíe 'linea_id'
                    lineaChoferInput.name = 'linea_id';
                    lineaAdminInput.name = 'linea_id_admin_input_disabled'; 
                }
            }

            // Inicializar el formulario como Admin. Línea (ID 4)
            setRole(4);

            // Escuchadores de eventos para los botones
            btnAdmin.addEventListener('click', function(e) {
                e.preventDefault(); 
                setRole(4);
            });

            btnChofer.addEventListener('click', function(e) {
                e.preventDefault(); 
                setRole(3);
            });
            
        });
    </script>
</body>
</html>