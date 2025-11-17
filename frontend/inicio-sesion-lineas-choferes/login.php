<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Iniciar Sesión</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; text-align: center; }
        h2 { color: #0056b3; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; text-align: left; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-login { background-color: #28a745; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; width: 100%; transition: background-color 0.3s; }
        .btn-login:hover { background-color: #218838; }
        #message { margin-top: 20px; padding: 10px; border-radius: 4px; text-align: center; font-weight: bold; display: none; }
        .registro-link { margin-top: 15px; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        
        <form id="loginForm"> 
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login" id="btnLogin">Ingresar</button>
        </form>

        <div id="message"></div>
        
        <p class="registro-link">
            ¿No tienes cuenta? <a href="registro-lineas.php">Regístrate aquí</a>
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const messageElement = document.getElementById('message');

            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Evitar el envío de formulario tradicional
                
                // Ruta al script de validación de backend
                // Estructura: frontend/inicio-sesion-linea-choferes/ -> ../../backend/
                const targetUrl = '../../backend/validacion-login.php'; 

                // Mostrar estado de carga
                messageElement.style.display = 'block';
                messageElement.style.backgroundColor = '#ffffcc';
                messageElement.style.color = '#333';
                messageElement.textContent = 'Verificando credenciales...';

                const formData = new FormData(form);

                fetch(targetUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    messageElement.textContent = data.message;

                    if (data.success) {
                        messageElement.style.backgroundColor = '#d4edda'; // Éxito
                        messageElement.style.color = '#155724';
                        
                        // Redirigir al usuario a la interfaz correcta
                        setTimeout(() => {
                            window.location.href = data.redirect; // La URL la enviará el PHP
                        }, 1000); 

                    } else {
                        messageElement.style.backgroundColor = '#f8d7da'; // Error
                        messageElement.style.color = '#721c24';
                    }
                })
                .catch(error => {
                    console.error('Error de comunicación:', error);
                    messageElement.textContent = 'Error de red. Intente de nuevo.';
                    messageElement.style.backgroundColor = '#f8d7da';
                    messageElement.style.color = '#721c24';
                });
            });
        });
    </script>
</body>
</html>