<?php
/**
 * Archivo: recarga_usuario.php
 * Descripción: Script unificado que maneja la vista de recarga (HTML/CSS/JS) y 
 * la lógica de inserción de la recarga a la BD.
 */

// 1. INCLUIR CONEXIÓN A LA BASE DE DATOS
// !!! RUTA ACTUALIZADA A bd.php !!!
require_once '../backend/bd.php'; 

$status_message = ''; 
$is_success = false;

// ----------------------------------------------------
// 2. LÓGICA DE PROCESAMIENTO DEL FORMULARIO (PHP)
// ----------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recolección y saneamiento de datos
    $card_id = trim($_POST['card_id'] ?? ''); // ID de la Tarjeta Digital Transport
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = trim($_POST['payment_method'] ?? '');

    // Campos de Tarjeta (opcional, solo si payment_method es 'tarjeta')
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // 2.1. Validación básica de campos
    if (empty($card_id) || $amount === false || $amount <= 0) {
        $status_message = 'Por favor, ingresa un ID de tarjeta válido y un monto de recarga positivo.';
    } else {
        
        // --- Paso 1: Verificar la Tarjeta ---
        $sql_check = "SELECT tarjeta_id, saldo_actual FROM TARJETA WHERE codigo_nfc = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $card_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows === 0) {
            $status_message = 'Error: El ID de tarjeta ingresado no existe en el sistema.';
        } else {
            // Tarjeta encontrada
            $tarjeta_data = $result_check->fetch_assoc();
            $tarjeta_id = $tarjeta_data['tarjeta_id'];
            $saldo_anterior = $tarjeta_data['saldo_actual'];
            $nuevo_saldo = $saldo_anterior + $amount;
            
            $stmt_check->close();

            // Detalles de pago para la base de datos
            $detalles_pago = "Pago Digital Vía Web - Método: " . strtoupper($payment_method);
            
            // Validaciones Adicionales según el método
            $valid_additional_fields = true;
            if ($payment_method === 'tarjeta') {
                 if (empty($dni) || empty($email)) {
                     $status_message = 'Error: Debes completar tu C.I. y Correo para la factura.';
                     $valid_additional_fields = false;
                 } else {
                     $detalles_pago .= " - Factura a C.I.: {$dni}, Correo: {$email}";
                 }
            }
            
            if (!$valid_additional_fields) {
                goto end_post_processing; // Saltar al final si fallan las validaciones específicas
            }

            // --- Paso 2: Iniciar Transacción (Actualización y Registro) ---
            $conn->begin_transaction();
            try {
                
                // a) Actualizar Saldo de la Tarjeta
                $sql_update = "UPDATE TARJETA SET saldo_actual = ? WHERE tarjeta_id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("di", $nuevo_saldo, $tarjeta_id);
                
                if (!$stmt_update->execute()) {
                    throw new Exception("Fallo al actualizar el saldo.");
                }
                $stmt_update->close();

                // b) Registrar la Transacción (Recarga)
                $tipo_movimiento = "Recarga Digital";
                $sql_insert = "INSERT INTO TRANSACCION (tarjeta_id, tipo_movimiento, monto, fecha_hora, punto_id_recarga, detalles_pago)
                               VALUES (?, ?, ?, NOW(), NULL, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("isds", $tarjeta_id, $tipo_movimiento, $amount, $detalles_pago);

                if (!$stmt_insert->execute()) {
                    throw new Exception("Fallo al registrar la transacción.");
                }
                $stmt_insert->close();

                // Paso 3: Confirmar Transacción
                $conn->commit();
                $is_success = true;
                $status_message = "¡Recarga exitosa! Se han añadido " . number_format($amount, 2) . " Bs a tu tarjeta. Saldo actual: " . number_format($nuevo_saldo, 2) . " Bs.";
                
            } catch (Exception $e) {
                // Paso 4: Revertir Transacción si hay error
                $conn->rollback();
                $status_message = "Error en el procesamiento de la recarga: Transacción revertida."; 
            }
        }
    }
}
end_post_processing: // Etiqueta para el goto

// 3. Manejo de mensajes de estado (después de POST)
if (isset($_GET['status']) && isset($_GET['msg'])) {
    $status = $_GET['status'];
    $message = htmlspecialchars(urldecode($_GET['msg']));
    
    if ($status === 'success') {
        $status_message = '<div class="alert success"><i class="fas fa-check-circle"></i> ' . $message . '</div>';
    } elseif ($status === 'error') {
        $status_message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> ' . $message . '</div>';
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($status_message)) {
    $status_type = $is_success ? 'success' : 'error';
    $icon = $is_success ? 'fa-check-circle' : 'fa-exclamation-circle';
    $status_message = '<div class="alert ' . $status_type . '"><i class="fas ' . $icon . '"></i> ' . $status_message . '</div>';
}

// Cerrar la conexión (Buena práctica)
if (isset($conn)) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Transport - Recarga Rápida</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primary: #0b2e88;
            --color-secondary: #1e88e5;
            --color-text-dark: #333;
            --color-background-light: #f4f7f9;
            --color-border: #eee;
            --color-input-border: #ccc;
            --color-safe-zone: #e3f2fd; 
            --color-safe-icon: #1e88e5;
            --color-error: #dc3545;
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
        .saldo { font-size: 1em; color: var(--color-text-dark); font-weight: 600; }

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
            font-size: 1.8em;
            color: var(--color-text-dark);
            margin: 0 0 5px 0;
        }
        .section-header p {
            font-size: 0.95em;
            color: #666;
            margin: 0;
        }

        /* --- Estilos de Formulario Comunes --- */
        .form-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            background-color: #f8f8f8;
        }
        .form-section h2 {
            font-size: 1.2em;
            color: var(--color-text-dark);
            margin: 0 0 20px 0;
            font-weight: 600;
        }

        /* --- Identificación de Tarjeta --- */
        .id-input-group label { display: block; font-size: 0.9em; color: #666; margin-bottom: 5px; }
        .id-input-group label span { color: red; margin-left: 2px; }
        .id-input-group input {
            width: 100%; padding: 10px; border: 1px solid var(--color-input-border);
            border-radius: 5px; font-size: 1em; box-sizing: border-box; background-color: white;
        }
        .id-input-group small { font-size: 0.8em; color: #999; margin-top: 5px; display: block; }

        /* --- Montos de Recarga --- */
        .amount-selection-cards { display: flex; gap: 20px; margin-bottom: 30px; }
        .amount-card {
            flex: 1; padding: 20px 15px; border: 2px solid var(--color-border);
            border-radius: 8px; text-align: center; cursor: pointer; background-color: white;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .amount-card.selected { border-color: var(--color-secondary); box-shadow: 0 0 8px rgba(30, 136, 229, 0.2); }
        .amount-card h3 { font-size: 1.5em; font-weight: 600; color: var(--color-text-dark); margin: 0; }
        .amount-card p { font-size: 0.8em; color: #999; margin: 5px 0 0 0; }

        /* --- Monto Personalizado --- */
        .custom-amount {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }
        .custom-amount h3 {
            font-size: 1em; color: var(--color-text-dark); font-weight: 500; margin: 0;
        }
        .custom-amount-input {
            position: relative;
            flex-grow: 1;
            max-width: 250px;
        }
        .custom-amount-input input {
            width: 100%;
            padding: 10px 10px 10px 45px; 
            border: 1px solid var(--color-input-border);
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .custom-amount-input .currency {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-weight: 600;
        }

        /* --- Sección de Métodos de Pago --- */
        .payment-methods {
            border: 1px solid var(--color-border);
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .payment-method-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid var(--color-border);
            cursor: pointer;
            transition: background-color 0.1s;
        }
        .payment-method-item:last-child {
            border-bottom: none;
        }
        .payment-method-item:hover {
            background-color: #f9f9f9;
        }
        .payment-method-item.selected {
             border: 2px solid var(--color-secondary);
             background-color: #eef7ff;
        }
        
        .method-icon {
            font-size: 1.4em;
            color: var(--color-secondary);
            margin-right: 15px;
        }
        .method-info h3 {
            font-size: 1em;
            color: var(--color-text-dark);
            margin: 0;
            font-weight: 500;
        }
        .method-info p {
            font-size: 0.85em;
            color: #999;
            margin: 2px 0 0 0;
        }
        .method-info .soon {
            color: #f90;
            font-weight: 600;
        }

        /* --- Contenedor de Detalles de Pago (NUEVO) --- */
        #payment-details-container {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fcfcfc;
            transition: all 0.3s ease-in-out;
        }
        
        /* --- Formulario de Pago con Tarjeta --- */
        .card-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 20px;
        }
        .card-form-group {
            margin-bottom: 15px;
        }
        .card-form-group.full-width {
            grid-column: 1 / 3;
        }
        .card-form-group label {
            display: block;
            font-size: 0.9em;
            color: #333;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .card-form-group input, .card-form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-input-border);
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .card-form-group .cvn-tip {
            font-size: 0.75em;
            color: #999;
            margin-top: 5px;
        }

        /* --- Estilos para Pago Móvil (QR) --- */
        .qr-display {
            text-align: center;
            padding: 15px;
        }
        .qr-display h3 {
            color: var(--color-primary);
            margin-bottom: 10px;
        }
        .qr-display p {
            font-size: 0.9em;
            color: #666;
            margin-top: 15px;
        }
        .qr-code-placeholder {
            width: 150px;
            height: 150px;
            background-color: #333;
            margin: 15px auto;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.8em;
            text-transform: uppercase;
        }

        /* --- Botón de Pago y Mensaje de Error --- */
        .action-area {
            margin-top: 30px;
        }
        .btn-pay {
            width: 100%;
            background-color: var(--color-secondary);
            color: white;
            border: none;
            padding: 12px 0;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-pay:hover {
            background-color: #1a76c3;
        }
        .error-message {
            font-size: 0.9em;
            color: var(--color-error);
            border-left: 3px solid var(--color-error);
            padding-left: 10px;
            margin-top: 15px;
            font-weight: 500;
            display: none; 
        }
        
        /* --- Mensajes de Alerta PHP --- */
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
            color: #4caf50;
            border: 1px solid #4caf50;
        }


        /* --- Transacción Segura Banner --- */
        .safe-zone-banner {
            background-color: var(--color-safe-zone);
            border: 1px solid var(--color-secondary);
            border-radius: 8px;
            padding: 20px;
            margin-top: 40px;
        }
        .safe-zone-banner h3 {
            font-size: 1.1em;
            color: var(--color-primary);
            margin: 0 0 15px 0;
            font-weight: 600;
        }
        .safe-zone-banner ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .safe-zone-banner ul li {
            font-size: 0.9em;
            color: var(--color-text-dark);
            margin-bottom: 8px;
        }
        .safe-zone-banner ul li i {
            color: var(--color-safe-icon);
            margin-right: 8px;
        }

        /* --- Footer --- */
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
            .amount-selection-cards { flex-wrap: wrap; }
            .amount-card { flex: 1 1 45%; }
            .header { flex-wrap: wrap; justify-content: center; gap: 10px; }
            .nav-menu { order: 3; width: 100%; justify-content: center; }
            .saldo { order: 2; }
            .custom-amount { flex-direction: column; align-items: flex-start; }
            .custom-amount-input { max-width: 100%; }
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
            <a href="registro-usuarios.php" class="nav-item">Registro</a>
            <a href="recarga-digital.php" class="nav-item">Recarga</a>
            <a href="puntos-recarga.php" class="nav-item">Puntos PR</a>
            <a href="mis-boletos.php" class="nav-item">Boletos</a>
            <a href="historial-viaje.php" class="nav-item active">Historial</a> 
        </nav>
        <div class="saldo">
            Saldo: **$125.50** A
        </div>
    </header>

    <div class="page-content-wrapper">
        <div class="main-content">
            
            <header class="section-header">
                <h1>Recarga Rápida</h1>
                <p>Recarga tu tarjeta Digital Transport de forma rápida y segura</p>
            </header>

            <?php echo $status_message; ?>

            <form action="recarga_usuario.php" method="POST" id="recarga-form">

                <section class="form-section" style="background-color: white; border: none; padding: 0;">
                    <p style="font-size: 0.95em; color: var(--color-text-dark); margin-bottom: 10px;">Ingresa el ID de tu tarjeta Digital Transport</p>
                    
                    <div class="id-input-group">
                        <label for="card-id">ID de Tarjeta Digital Transport <span>*</span></label>
                        <input type="text" id="card-id" name="card_id" placeholder="Ej: 1234567890" value="" required>
                        <small>El ID de 10 dígitos (Código NFC) se encuentra en el reverso de tu tarjeta</small>
                    </div>
                </section>

                <section class="form-section" style="background-color: white; border: none; padding: 0;">
                    <p style="font-size: 0.95em; color: var(--color-text-dark); margin-bottom: 20px;">Selecciona o ingresa el monto que deseas recargar</p>

                    <h3>Montos Predefinidos</h3>
                    <div class="amount-selection-cards">
                        <div class="amount-card" data-amount="20">
                            <h3>20 Bs</h3>
                            <p>8 viajes estándar</p>
                        </div>
                        <div class="amount-card selected" data-amount="50">
                            <h3>50 Bs</h3>
                            <p>20 viajes estándar</p>
                        </div>
                        <div class="amount-card" data-amount="100">
                            <h3>100 Bs</h3>
                            <p>40 viajes estándar</p>
                        </div>
                        <div class="amount-card" data-amount="200">
                            <h3>200 Bs</h3>
                            <p>80 viajes estándar</p>
                        </div>
                    </div>

                    <div class="custom-amount">
                        <h3>Monto Personalizado</h3>
                        <div class="custom-amount-input">
                            <span class="currency">Bs</span>
                            <input type="number" id="amount-input" name="amount" placeholder="Monto" min="1" value="50" required>
                        </div>
                    </div>
                </section>

                <section class="form-section" style="background-color: white; border: none; padding: 0;">
                    
                    <p style="font-size: 0.95em; color: var(--color-text-dark); margin-bottom: 20px;">Selecciona el Método de Pago</p>

                    <div class="payment-methods">
                        
                        <div class="payment-method-item" data-method="tarjeta">
                            <div class="method-icon"><i class="fas fa-credit-card"></i></div>
                            <div class="method-info">
                                <h3>Tarjeta de Crédito / Débito</h3>
                                <p>Visa, Mastercard, American Express</p>
                            </div>
                        </div>

                        <div class="payment-method-item selected" data-method="pago_movil">
                            <div class="method-icon"><i class="fas fa-mobile-alt"></i></div>
                            <div class="method-info">
                                <h3>Pago Móvil</h3>
                                <p>Tigo Money, QR Simple (Recomendado)</p>
                            </div>
                        </div>

                        <div class="payment-method-item disabled" data-method="billetera">
                            <div class="method-icon"><i class="fas fa-wallet"></i></div>
                            <div class="method-info">
                                <h3>Billetera Digital <span class="soon">Próximamente</span></h3>
                                <p>Paypal, Mercado Pago</p>
                            </div>
                        </div>
                        <input type="hidden" id="payment_method" name="payment_method" value="pago_movil">
                    </div>
                </section>
                
                <div id="payment-details-container">
                    </div>
                <div class="action-area">
                    <button type="submit" class="btn-pay">
                        <i class="fas fa-money-check-alt"></i> Finalizar Recarga y Pagar
                    </button>
                    
                    <div class="error-message" id="js-error-message">
                        <i class="fas fa-exclamation-circle"></i> Por favor, completa todos los campos requeridos para continuar.
                    </div>
                </div>
            </form>
            <div class="safe-zone-banner">
                <h3><i class="fas fa-shield-alt"></i> Transacción 100% Segura</h3>
                <ul>
                    <li><i class="fas fa-check-circle"></i> Conexión encriptada SSL de 256 bits</li>
                    <li><i class="fas fa-check-circle"></i> No almacenamos datos de la tarjeta</li>
                    <li><i class="fas fa-check-circle"></i> Procesamiento instantáneo de la recarga</li>
                    <li><i class="fas fa-check-circle"></i> Recibo digital enviado a tu correo</li>
                </ul>
            </div>
            
        </div>
    </div>

    <footer class="footer">
        © 2025 Digital Transport - Sistema de Boletos Digital
    </footer>

    <script>
        // JS para manejar la selección de montos y los detalles de pago dinámicos
        const amountCards = document.querySelectorAll('.amount-card');
        const customInput = document.getElementById('amount-input');
        const paymentMethods = document.querySelectorAll('.payment-method-item');
        const paymentMethodInput = document.getElementById('payment_method');
        const paymentDetailsContainer = document.getElementById('payment-details-container');
        const form = document.getElementById('recarga-form');
        const jsErrorMessage = document.getElementById('js-error-message');

        // --- Plantillas de Contenido Dinámico ---
        
        const CARD_PAYMENT_HTML = `
            <div>
                <h3><i class="fas fa-credit-card" style="color: var(--color-primary);"></i> Detalles de la Tarjeta</h3>
                <div class="card-form-group full-width">
                    <label for="card-number">Número de Tarjeta <span style="color: red;">*</span></label>
                    <input type="text" id="card-number" name="card_number_js" placeholder="XXXX XXXX XXXX XXXX" required>
                </div>

                <div class="card-form-grid">
                    <div class="card-form-group">
                        <label for="card-expiry">Fecha Vencimiento (MM/AA) <span style="color: red;">*</span></label>
                        <input type="text" id="card-expiry" name="card_expiry_js" placeholder="MM/AA" required pattern="(0[1-9]|1[0-2])\/?([0-9]{2})">
                    </div>
                    <div class="card-form-group">
                        <label for="card-cvn">CVN/CVC <span style="color: red;">*</span></label>
                        <input type="text" id="card-cvn" name="card_cvn_js" placeholder="123" required pattern="[0-9]{3,4}">
                        <div class="cvn-tip">Código de 3 o 4 dígitos al reverso.</div>
                    </div>
                </div>
                
                <div class="card-form-group full-width" style="margin-top: 15px;">
                    <label for="card-name">Nombre y Apellido del Titular <span style="color: red;">*</span></label>
                    <input type="text" id="card-name" name="card_name_js" placeholder="Como aparece en la tarjeta" required>
                </div>
                
                <h3 style="margin-top: 30px;"><i class="fas fa-receipt" style="color: var(--color-primary);"></i> Datos para Facturación</h3>
                
                <div class="card-form-grid">
                    <div class="card-form-group">
                        <label for="dni">Carnet de Identidad / NIT <span style="color: red;">*</span></label>
                        <input type="text" id="dni" name="dni" placeholder="C.I. o NIT" required>
                    </div>
                    <div class="card-form-group">
                        <label for="email">Correo Electrónico (Para factura) <span style="color: red;">*</span></label>
                        <input type="email" id="email" name="email" placeholder="correo@ejemplo.com" required>
                    </div>
                </div>
            </div>
        `;
        
        const PAGO_MOVIL_HTML = `
            <div class="qr-display">
                <h3>Paso 1: Escanea para Pagar</h3>
                <p style="font-weight: 600;">Monto a Pagar: <span id="display-amount-qr">50.00</span> Bs</p>
                <div class="qr-code-placeholder">
                    QR SIMULADO
                </div>
                <p>Escanea este QR desde tu aplicación bancaria o Tigo Money para completar la transacción.</p>
                <small style="color: var(--color-error);">⚠️ El pago debe coincidir exactamente con el monto de recarga.</small>
            </div>
        `;
        
        // --- Funciones de Lógica ---
        
        function updatePaymentDisplay() {
            const selectedMethod = paymentMethodInput.value;
            let displayHtml = '';

            // Limpiar nombres de campos de formularios no usados (solo para los que se inyectan)
            paymentDetailsContainer.querySelectorAll('input, select').forEach(el => {
                // Si el nombre del campo termina en '_js', lo eliminamos. 
                // Esto es para que los datos sensibles (card_number) no lleguen a PHP.
                if (el.name && el.name.endsWith('_js')) {
                    el.removeAttribute('name');
                }
                // Si el campo es 'dni' o 'email', también eliminamos su 'name' si no se va a usar
                if (el.name === 'dni' || el.name === 'email') {
                     el.removeAttribute('name');
                }
            });

            // Ocultar cualquier error JS anterior
            jsErrorMessage.style.display = 'none';

            if (selectedMethod === 'pago_movil') {
                displayHtml = PAGO_MOVIL_HTML;
            } else if (selectedMethod === 'tarjeta') {
                displayHtml = CARD_PAYMENT_HTML;
            } else {
                // Esto manejaría el caso de 'billetera' deshabilitada
                displayHtml = '<p style="text-align: center; color: #999;">Selecciona un método de pago para ver los detalles.</p>';
            }

            // Inyectar el HTML
            paymentDetailsContainer.innerHTML = displayHtml;
            
            // Re-agregar los atributos 'name' necesarios para PHP
            if (selectedMethod === 'tarjeta') {
                 // Solo necesitamos enviar DNI y Email al backend para el registro de la transacción/factura
                 document.getElementById('dni').setAttribute('name', 'dni');
                 document.getElementById('email').setAttribute('name', 'email');
                 
                 // Los campos sensibles de la tarjeta se quedan sin name (usamos el '_js' para referenciarlos en JS, 
                 // pero como el name no es 'dni' o 'email' y no termina en '_js', no se elimina en la limpieza inicial)
            }

            // Actualizar el monto dentro de la plantilla inyectada
            updateAmountInTemplates(customInput.value);
        }

        function updateAmountInTemplates(amount) {
            const formattedAmount = parseFloat(amount).toFixed(2);
            // Actualizar QR
            const displayQr = document.getElementById('display-amount-qr');
            if (displayQr) displayQr.textContent = formattedAmount;
        }


        // 1. Manejo de Tarjetas Predefinidas
        function syncAmountCards(currentAmount) {
            let foundMatch = false;
            amountCards.forEach(card => {
                const cardAmount = card.getAttribute('data-amount');
                if (cardAmount === currentAmount) {
                    card.classList.add('selected');
                    foundMatch = true;
                } else {
                    card.classList.remove('selected');
                }
            });
            if (!foundMatch) {
                 amountCards.forEach(c => c.classList.remove('selected'));
            }
            updateAmountInTemplates(currentAmount); // Actualizar los detalles de pago al cambiar el monto
        }

        amountCards.forEach(card => {
            card.addEventListener('click', function() {
                const amount = this.getAttribute('data-amount');
                customInput.value = amount;
                syncAmountCards(amount);
            });
        });
        
        // 2. Sincronización con el Monto Personalizado
        customInput.addEventListener('input', function() {
            syncAmountCards(this.value);
        });

        // 3. Manejo de la Selección de Método de Pago
        paymentMethods.forEach(method => {
            method.addEventListener('click', function() {
                if (this.classList.contains('disabled')) return; 

                paymentMethods.forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                
                const selectedMethod = this.getAttribute('data-method');
                paymentMethodInput.value = selectedMethod;
                
                // Mostrar la interfaz dinámica
                updatePaymentDisplay();
            });
        });
        
        // 4. Validación Final con JS antes de enviar a PHP
        form.addEventListener('submit', function(e) {
            const cardId = document.getElementById('card-id').value.trim();
            const amountValue = parseFloat(customInput.value);
            const selectedMethod = paymentMethodInput.value;
            let validationError = '';
            
            // Validación principal de ID y Monto
            if (cardId.length < 5) { validationError = 'Error: El ID de tarjeta es demasiado corto o inválido.'; }
            else if (isNaN(amountValue) || amountValue < 1) { validationError = 'Error: El monto de recarga debe ser de al menos 1 Bs.'; }
            
            // Validaciones específicas del método
            if (!validationError) {
                if (selectedMethod === 'tarjeta') {
                    // Validar campos sensibles (solo en frontend para este ejemplo)
                    const cardNumber = document.getElementById('card-number').value.replace(/\s/g, '');
                    const cardExpiry = document.getElementById('card-expiry').value;
                    const cardCvn = document.getElementById('card-cvn').value;
                    const cardName = document.getElementById('card-name').value.trim();
                    const dni = document.getElementById('dni').value.trim();
                    const email = document.getElementById('email').value.trim();

                    if (cardNumber.length < 15 || cardExpiry.length < 5 || cardCvn.length < 3 || cardName === "") {
                        validationError = 'Error: Por favor, verifica todos los datos de la tarjeta.';
                    } else if (dni === "" || email === "") {
                        validationError = 'Error: Debes ingresar el C.I. y el Correo para la factura.';
                    }
                }
            }

            if (validationError) {
                e.preventDefault();
                jsErrorMessage.textContent = validationError;
                jsErrorMessage.style.display = 'block';
                return;
            }
            
            // Si todo está bien, ocultar el error y permitir el envío
            jsErrorMessage.style.display = 'none';
        });

        // 5. Inicialización
        document.addEventListener('DOMContentLoaded', () => {
             syncAmountCards(customInput.value); 
             updatePaymentDisplay(); 
         });

    </script>

</body>
</html>