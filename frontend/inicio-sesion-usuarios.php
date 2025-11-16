<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primary: #0b2e88; /* Azul oscuro principal */
            --color-secondary: #1e88e5; /* Azul para botones/énfasis */
            --color-text-dark: #333;
            --color-background-light: #f4f7f9;
            --color-input-border: #ccc;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--color-background-light);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* --- Contenedor Principal de Login --- */
        .login-container {
            max-width: 400px;
            width: 90%;
            background-color: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* --- Header de la Aplicación (Logotipo) --- */
        .app-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--color-primary);
        }
        .logo-subtitle {
            font-size: 0.9em;
            font-weight: normal;
            color: #666;
            display: block;
            margin-top: 5px;
        }

        /* --- Título del Formulario --- */
        .form-title {
            text-align: center;
            margin-bottom: 25px;
        }
        .form-title h2 {
            font-size: 1.4em;
            color: var(--color-text-dark);
            margin: 0;
        }
        .form-title p {
            font-size: 0.9em;
            color: #999;
            margin-top: 5px;
        }
        
        /* --- Grupos de Input --- */
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 0.9em;
            color: var(--color-text-dark);
            margin-bottom: 5px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--color-input-border);
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
            background-color: #f9f9f9;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-group input:focus {
            border-color: var(--color-secondary);
            background-color: white;
            box-shadow: 0 0 0 1px var(--color-secondary);
            outline: none;
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%; 
            transform: translateY(10px);
            color: #999;
        }

        /* --- Opciones Adicionales --- */
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.9em;
        }
        
        .forgot-password {
            color: var(--color-secondary);
            text-decoration: none;
            transition: color 0.2s;
        }
        .forgot-password:hover {
            color: var(--color-primary);
        }

        /* --- Botón de Iniciar Sesión --- */
        .btn-login {
            width: 100%;
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 12px 0;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-login:hover {
            background-color: #082266;
        }

        /* --- Enlace de Registro --- */
        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9em;
            color: #666;
        }
        .register-link a {
            color: var(--color-secondary);
            text-decoration: none;
            font-weight: 600;
        }
        
        /* --- Mensaje de Alerta JS --- */
        #message-area {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 0.9em;
            display: none; /* Oculto por defecto */
        }
        .alert-error {
            background-color: #fdeaea;
            color: #d9534f;
            border: 1px solid #d9534f;
        }
        .alert-success {
            background-color: #e6ffe6;
            color: #4caf50;
            border: 1px solid #4caf50;
        }
    </style>
</head>
<body>

    <div class="login-container">
        
        <header class="app-header">
            <div class="logo">
                Digital Transport
                <span class="logo-subtitle">Sistema de Boletos Digital</span>
            </div>
        </header>

        <div class="form-title">
            <h2>Acceso de Usuarios</h2>
            <p>Ingresa tus credenciales para continuar</p>
        </div>
        
        <div id="message-area" class="alert-error"></div>

        <form id="loginForm"> 
            
            <div class="input-group">
                <label for="email">Correo Electrónico o ID de Usuario</label>
                <input type="text" id="email" name="email" placeholder="ejemplo@correo.com o ID" required>
                <i class="input-icon fas fa-user"></i>
            </div>

            <div class="input-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                <i class="input-icon fas fa-lock"></i>
            </div>
            
            <div class="options-row">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Recordarme</label>
                </div>
                <a href="#" class="forgot-password">¿Olvidaste tu Contraseña?</a>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="register-link">
            ¿No tienes cuenta? <a href="registro_usuario.php">Regístrate aquí</a>
        </div>

    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Evita el envío tradicional del formulario
            
            const form = event.target;
            const messageArea = document.getElementById('message-area');
            messageArea.style.display = 'none'; // Ocultar mensajes anteriores

            const data = {
                email: form.email.value,
                password: form.password.value
            };

            // Intentar autenticar al usuario usando el endpoint PHP unificado
            fetch('../backend/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            })
            .then(response => {
                // Si la respuesta no es OK (ej. 500 Server Error), lanzar error
                if (!response.ok) {
                    throw new Error('Error de red o servidor.');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Muestra mensaje de éxito temporal
                    messageArea.textContent = data.message;
                    messageArea.classList.remove('alert-error');
                    messageArea.classList.add('alert-success');
                    messageArea.style.display = 'block';

                    // Redirección al dashboard específico del rol
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        // En caso de que PHP no envíe una URL por algún error
                        messageArea.textContent = 'Autenticación exitosa, pero la URL de redirección no se encontró.';
                        messageArea.classList.remove('alert-success');
                        messageArea.classList.add('alert-error');
                    }
                } else {
                    // Muestra el error de credenciales devuelto por PHP
                    messageArea.textContent = data.error || 'Credenciales no válidas.';
                    messageArea.classList.remove('alert-success');
                    messageArea.classList.add('alert-error');
                    messageArea.style.display = 'block';
                }
            })
            .catch(error => {
                // Error de conexión o JSON mal formado
                console.error('Error en la solicitud Fetch:', error);
                messageArea.textContent = 'Error de conexión con el servidor. Revise la consola.';
                messageArea.classList.remove('alert-success');
                messageArea.classList.add('alert-error');
                messageArea.style.display = 'block';
            });
        });
    </script>

</body>
</html>