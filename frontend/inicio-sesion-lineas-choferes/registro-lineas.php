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
            
            <label>Registro de Chofer (Sujeto a Validación de Línea):</label>
            <input type="hidden" name="tipo_usuario_id" id="tipo_usuario_id" value="3">
 
            
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

            <div id="choferFields">
                <div class="form-group">
                    <label for="licencia">Número de Licencia:</label>
                    <input type="text" id="licencia" name="licencia" placeholder="Ej: 456789 B" required>
                </div>
                <div class="form-group">
                    <label for="vehiculo_placa">Placa del Vehículo:</label>
                    <input type="text" id="vehiculo_placa" name="vehiculo_placa" placeholder="Ej: 2568-XYZ" required> 
                </div>
                <div class="form-group">
                    <label for="linea_id_chofer">Línea Solicitada:</label>
                    <select id="linea_id_chofer" name="linea_id" required> 
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
            const form = document.getElementById('registroForm');
            const messageElement = document.getElementById('message');

            form.addEventListener('submit', function(e) {
                e.preventDefault(); 

                const targetUrl = '../../backend/validacion-registro.php'; 

                messageElement.classList.remove('hidden');
                messageElement.style.backgroundColor = '#ffffcc';
                messageElement.style.color = '#333';
                messageElement.textContent = 'Procesando registro de chofer...';

                const formData = new FormData(form);

                fetch(targetUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                             throw new Error("Respuesta no JSON: " + text);
                        });
                    }
                })
                .then(data => {
                    messageElement.textContent = data.message;

                    if (data.success) {
                        messageElement.style.backgroundColor = '#d4edda'; 
                        messageElement.style.color = '#155724';
                        
                        setTimeout(() => {
                            window.location.href = 'login.php'; 
                        }, 2000); 

                    } else {
                        messageElement.style.backgroundColor = '#f8d7da'; 
                        messageElement.style.color = '#721c24';
                    }
                })
                .catch(error => {
                    console.error('Error de comunicación con el servidor:', error);
                    messageElement.textContent = 'Error al procesar el registro.';
                    messageElement.style.backgroundColor = '#f8d7da';
                    messageElement.style.color = '#721c24';
                });
            });
        });
    </script>
</body>
</html>