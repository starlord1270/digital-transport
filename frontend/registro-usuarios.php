<?php
// Frontend de Registro - La lógica ahora es manejada vía AJAX por validacion-registro.php
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Registro de Usuario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primary: #0b2e88;
            --color-secondary: #1e88e5;
            --color-text-dark: #333;
            --color-background-light: #f4f7f9;
            --color-border: #eee;
            --color-input-border: #ccc;
            --color-discount: #4caf50; /* Verde para descuento */
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--color-background-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* --- Header / Menú Superior (Común) --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            width: 100%;
            box-sizing: border-box;
        }
        .logo { font-size: 1.2em; font-weight: bold; color: var(--color-primary); }
        .nav-menu { display: flex; gap: 20px; }
        .nav-item { color: #666; text-decoration: none; padding: 5px 10px; border-radius: 5px; font-size: 0.95em; transition: background-color 0.2s, color 0.2s; }
        .nav-item.active { background-color: #f0f0f0; color: var(--color-text-dark); font-weight: 500; }
        /* El elemento .saldo no se renderiza en el HTML de registro, por lo que no necesita CSS aquí. */

        /* --- Contenido Principal de la Vista --- */
        .page-content-wrapper {
            flex-grow: 1;
            padding: 20px 0 50px 0;
        }
        .main-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        /* --- Título de la Sección --- */
        .section-header {
            margin-bottom: 30px;
        }
        .section-header h1 {
            font-size: 1.4em;
            color: var(--color-text-dark);
            margin: 0 0 5px 0;
            font-weight: 600;
        }
        .section-header p {
            font-size: 0.95em;
            color: #666;
            margin: 0;
        }

        /* --- Tipo de Cuenta (Cards) --- */
        .account-type-selection {
            display: flex;
            gap: 25px;
            margin-bottom: 40px;
        }

        .account-card {
            flex: 1;
            padding: 20px;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            background-color: #fcfcfc;
            cursor: pointer;
            transition: border-color 0.2s, background-color 0.2s;
            position: relative;
        }
        .account-card.selected {
            border-color: var(--color-secondary);
            box-shadow: 0 0 8px rgba(30, 136, 229, 0.1);
            background-color: #eef7ff;
        }
        
        .radio-dot {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 16px;
            height: 16px;
            border: 2px solid #ccc;
            border-radius: 50%;
            background-color: white;
        }
        .account-card.selected .radio-dot {
            border-color: var(--color-secondary);
            background-color: var(--color-secondary);
            box-shadow: inset 0 0 0 4px white;
        }

        .card-content {
            padding-left: 25px; /* Espacio para el radio dot */
        }
        
        .card-content i {
            font-size: 1.8em;
            color: var(--color-primary);
            margin-bottom: 10px;
        }
        .card-content h3 {
            font-size: 1em;
            font-weight: 600;
            color: var(--color-text-dark);
            margin: 0 0 5px 0;
        }
        .card-content p {
            font-size: 0.85em;
            color: #666;
            margin: 0;
        }
        .card-content .tariff {
            font-size: 1.1em;
            font-weight: bold;
            color: var(--color-secondary);
            margin-top: 10px;
        }
        .card-content .discount-tag {
            font-size: 0.8em;
            font-weight: 600;
            color: var(--color-discount);
            margin-left: 10px;
        }

        /* --- Formulario de Información Personal --- */
        .form-section h2 {
            font-size: 1.2em;
            color: var(--color-text-dark);
            margin: 0 0 20px 0;
            font-weight: 600;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .input-group {
            flex: 1;
        }

        .input-group label {
            display: block;
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-input-border);
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
            background-color: white;
        }
        .input-group input:focus {
            border-color: var(--color-secondary);
            outline: none;
        }
        .input-group input::placeholder {
            color: #ccc;
        }

        /* --- Botón de Registro --- */
        .btn-register {
            width: 100%;
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 12px 0;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
            transition: background-color 0.2s;
        }
        .btn-register:hover {
            background-color: #082266;
        }

        /* --- Mensajes de Alerta --- */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 0.95em;
            font-weight: 500;
        }
        .alert.error {
            background-color: #fdeaea;
            color: #d9534f;
            border: 1px solid #d9534f;
        }
        .alert.success {
            background-color: #e6ffe6;
            color: var(--color-discount);
            border: 1px solid var(--color-discount);
        }

        /* --- Footer (Simulación) --- */
        .footer {
            text-align: center;
            padding: 20px;
            background-color: white;
            color: #666;
            font-size: 0.85em;
            margin-top: auto;
            border-top: 1px solid #eee;
        }

        @media (max-width: 768px) {
            .account-type-selection, .form-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">
            Digital Transport
            <span style="font-size: 0.7em; display: block; font-weight: normal; color: #666;">Sistema de Boletos Digitales</span>
        </div>
        <nav class="nav-menu">
            <a href="index.php" class="nav-item">Inicio</a>
            <a href="registro-usuarios.php" class="nav-item active">Registro</a> 
            <a href="inicio-sesion-usuarios.php" class="nav-item">Iniciar Sesión</a> <a href="recarga-digital.php" class="nav-item">Recarga</a>
            <a href="puntos-recarga.php" class="nav-item">Puntos PR</a>
            <a href="mis-boletos.php" class="nav-item">Boletos</a>
            <a href="historial-viaje.php" class="nav-item">Historial</a>
        </nav>
        </header>

    <div class="page-content-wrapper">
        <div class="main-content">
            
            <header class="section-header">
                <h1>Registro de Usuario</h1>
                <p>Crea tu cuenta para comenzar a usar Digital Transport</p>
            </header>

            <div id="js-message" style="display:none; margin-bottom: 20px;"></div>
            
            <section class="form-section" style="padding: 0; border: none; background: none;">
                <h2>Tipo de Cuenta</h2>
                <p style="font-size: 0.95em; color: var(--color-text-dark); margin-bottom: 20px;">Selecciona el tipo de usuario que deseas registrar</p>

                <form id="registerForm"> 
                    <input type="hidden" id="account_type" name="account_type" value="standard">

                    <div class="account-type-selection">
                        
                        <div class="account-card selected" data-type="standard">
                            <div class="radio-dot"></div>
                            <div class="card-content">
                                <i class="fas fa-user"></i>
                                <h3>Pasajero Estándar</h3>
                                <p>Registro estándar para usuarios adultos con acceso completo al sistema</p>
                                <div class="tariff">Tarifa: **2.50 Bs**</div>
                            </div>
                        </div>
                        
                        <div class="account-card" data-type="student">
                            <div class="radio-dot"></div>
                            <div class="card-content">
                                <i class="fas fa-graduation-cap"></i>
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <h3>Pasajero Estudiante</h3>
                                    <span class="discount-tag">50% Tarifa con descuento.</span>
                                </div>
                                <p>Requiere credencial estudiantil vigente</p>
                                <div class="tariff">Tarifa: **1.00 Bs**</div>
                            </div>
                        </div>
                    </div>
                
                </section>
                
                <section class="form-section" style="padding: 0; border: none; background: none;">
                    <h2>Información Personal</h2>
                    <p style="font-size: 0.95em; color: var(--color-text-dark); margin-bottom: 20px;">Completa tus datos para crear tu cuenta</p>
                    
                        <div class="form-row">
                            <div class="input-group">
                                <label for="full-name">Nombre Completo <span style="color: red;">*</span></label>
                                <input type="text" id="full-name" name="full_name" placeholder="Ej: Juan Pérez García" required>
                            </div>
                            <div class="input-group">
                                <label for="doc-id">Documento de Identidad <span style="color: red;">*</span></label>
                                <input type="text" id="doc-id" name="doc_id" placeholder="Ej: 12345678" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="input-group">
                                <label for="email">Correo Electrónico <span style="color: red;">*</span></label>
                                <input type="email" id="email" name="email" placeholder="ejemplo@correo.com" required>
                            </div>
                            <div class="input-group">
                                <label for="phone">Teléfono <span style="color: red;">*</span></label>
                                <input type="tel" id="phone" name="phone" placeholder="Ej: +591 70123456" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="input-group">
                                <label for="password">Contraseña <span style="color: red;">*</span></label>
                                <input type="password" id="password" name="password" placeholder="Define tu contraseña" required>
                            </div>
                            <div class="input-group">
                                <label for="password-confirm">Confirmar Contraseña <span style="color: red;">*</span></label>
                                <input type="password" id="password-confirm" name="password-confirm" placeholder="Repite tu contraseña" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-register">
                            <i class="fas fa-user-plus"></i> Registrarse
                        </button>
                    </form>
                </section>
            
        </div>
    </div>

    <footer class="footer">
        © 2025 Digital Transport - Sistema de Boletos Digital
    </footer>

    <script>
        // CONFIGURACIÓN DE TIPOS DE USUARIO PARA EL BACKEND
        const TIPO_ESTANDAR = 1; 
        const TIPO_ESTUDIANTE = 2; // Ajustar según tu tabla TIPO_USUARIO

        // JS para manejar la selección del tipo de cuenta y actualizar el campo oculto
        document.querySelectorAll('.account-card').forEach(card => {
            card.addEventListener('click', function() {
                // Deseleccionar todas
                document.querySelectorAll('.account-card').forEach(c => c.classList.remove('selected'));
                // Seleccionar la actual
                this.classList.add('selected');
                
                // ACTUALIZAR EL CAMPO OCULTO (hidden input)
                const selectedType = this.getAttribute('data-type');
                document.getElementById('account_type').value = selectedType;
            });
        });
        
        // Manejo del Formulario con AJAX
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageDiv = document.getElementById('js-message');
            messageDiv.style.display = 'none';
            messageDiv.innerHTML = '';
            
            // Recoger datos
            const formData = new FormData();
            
            // Mapeo de campos del formulario (HTML) a lo que espera validacion-registro.php
            formData.append('nombre_completo', document.getElementById('full-name').value);
            formData.append('documento_identidad', document.getElementById('doc-id').value);
            formData.append('email', document.getElementById('email').value);
            // El backend no usa 'phone' en USUARIO, pero si lo necesitas, debes añadirlo a la BD y al backend.
            // Por ahora lo ignoramos o lo enviamos si el backend lo soportara.
            
            const pass = document.getElementById('password').value;
            const passConfirm = document.getElementById('password-confirm').value;
            
            if (pass !== passConfirm) {
                showMessage('Error: Las contraseñas no coinciden.', 'error');
                return;
            }
            formData.append('password', pass);
            
            const accountType = document.getElementById('account_type').value;
            const tipoId = (accountType === 'student') ? TIPO_ESTUDIANTE : TIPO_ESTANDAR;
            formData.append('tipo_usuario_id', tipoId);
            
            // Enviar petición
            fetch('../backend/validacion-registro.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'inicio-sesion-usuarios.php';
                    }, 3000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Error de conexión con el servidor.', 'error');
            });
        });

        function showMessage(msg, type) {
            const el = document.getElementById('js-message');
            el.className = 'alert ' + type;
            el.textContent = msg; // Text content para seguridad
            el.style.display = 'block';
            el.scrollIntoView({ behavior: 'smooth' });
        }
    </script>

</body>
</html>