<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Iniciar Sesión</title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h1 {
            color: #0056b3;
            margin-bottom: 5px;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.2em;
        }

        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        #message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
            display: none;
        }

        #message.error {
            background-color: #fdd;
            color: #d00;
            display: block;
        }

        #message.success {
            background-color: #ddf;
            color: #00d;
            display: block;
        }
        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Digital Transport</h1>
        <h2>Iniciar Sesión</h2>

        <form id="loginForm">
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" id="email" required>
            </div>
            <div class="input-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" required>
            </div>
            <button type="submit">Acceder al Sistema</button>
        </form>

        <p id="message" class="hidden"></p>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // 1. URL del script PHP de backend
            // ESTA ES LA RUTA CORREGIDA: http://localhost/NOMBRE_PROYECTO/backend/login.php
            const API_URL = 'http://localhost/Competencia-Analisis/backend/login.php';
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const messageElement = document.getElementById('message');

            // Función para mostrar mensajes
            function showMessage(text, isError = true) {
                messageElement.textContent = text;
                messageElement.className = isError ? 'error' : 'success';
            }
            
            // Ocultar mensaje al inicio de cada intento
            messageElement.className = 'hidden';

            // 2. Enviar datos al servidor
            fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email: email, password: password })
            })
            .then(response => {
                if (!response.ok) {
                    // Este error se lanza si el servidor no responde (ej. 404 Not Found)
                    throw new Error('Error de red o servidor: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Éxito en el Login (respuesta positiva del login.php)
                    showMessage('Bienvenido, ' + data.user.nombre + '. ¡Acceso concedido!', false);
                    console.log('Datos del usuario:', data.user);
                    
                    // Lógica de redirección a la página del dashboard aquí
                    // window.location.href = '/Competencia-Analisis/dashboard.html'; 
                } else {
                    // Error de credenciales (respuesta de login.php)
                    showMessage(data.error || 'Fallo en el inicio de sesión.');
                }
            })
            .catch(error => {
                // Error de conexión (ej. servidor XAMPP apagado, ruta incorrecta, CORS)
                console.error('Error de conexión:', error);
                showMessage('No se pudo conectar al servidor. Verifique que XAMPP/WAMP esté activo y que la ruta del API sea correcta.', true);
            });
        });
    </script>
</body>
</html>