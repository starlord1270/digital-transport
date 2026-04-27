<?php
/**
 * DIGITAL TRANSPORT - INICIAR SESIÓN (REFACTORIZADA)
 */
$page_title = "Iniciar Sesión - Digital Transport";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            animation: slideUp 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="card login-card glass-card">
    <div style="text-align: center; margin-bottom: 32px;">
        <div style="width: 64px; height: 64px; background: var(--primary); color: white; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 16px; box-shadow: var(--shadow-md);">
            <i class="fas fa-bus"></i>
        </div>
        <h2 style="font-size: 1.8rem; margin-bottom: 8px;">Bienvenido</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Ingresa a tu cuenta de transporte digital</p>
    </div>

    <div id="alert-login" style="display: none; margin-bottom: 24px;"></div>

    <form id="login-form">
        <div class="form-group">
            <label class="form-label">Correo Electrónico</label>
            <div style="position: relative;">
                <i class="fas fa-envelope" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="email" name="email" class="form-input" style="padding-left: 45px;" placeholder="tu@correo.com" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Contraseña</label>
            <div style="position: relative;">
                <i class="fas fa-lock" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="password" name="password" class="form-input" style="padding-left: 45px;" placeholder="••••••••" required>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; font-size: 0.85rem;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="remember"> Recordarme
            </label>
            <a href="#" style="color: var(--secondary); text-decoration: none; font-weight: 500;">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1.1rem;">
            Iniciar Sesión <i class="fas fa-sign-in-alt" style="margin-left: 8px;"></i>
        </button>
    </form>

    <div style="text-align: center; margin-top: 32px; font-size: 0.9rem; color: var(--text-muted);">
        ¿No tienes una cuenta? <a href="registro-usuarios.php" style="color: var(--secondary); font-weight: 700; text-decoration: none;">Regístrate ahora</a>
    </div>
</div>

<script>
    const form = document.getElementById('login-form');
    const alertBox = document.getElementById('alert-login');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        
        try {
            const response = await fetch('../backend/validacion-login.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                showAlert(result.message, 'success');
                setTimeout(() => window.location.href = result.redirect, 1000);
            } else {
                showAlert(result.message, 'danger');
            }
        } catch (err) {
            showAlert('Error de conexión.', 'danger');
        }
    });

    function showAlert(msg, type) {
        alertBox.style.display = 'block';
        alertBox.innerHTML = `
            <div class="glass-card" style="padding: 12px; border-color: var(--${type}); color: var(--${type}); font-size: 0.85rem; background: rgba(0,0,0,0.01);">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}
            </div>
        `;
    }
</script>

</body>
</html>