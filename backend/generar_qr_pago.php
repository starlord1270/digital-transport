<?php
/**
 * DIGITAL TRANSPORT - GENERAR QR DE PAGO FIRMADO (HMAC-SHA256)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$secret_key = getenv('QR_SECRET') ?: getenv('CSRF_SECRET');
if (empty($secret_key)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de configuración: Clave de firma QR no definida en el servidor.']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$ts = time();
$nonce = bin2hex(random_bytes(16));

$payloadToSign = "{$usuario_id}:{$ts}:{$nonce}";
$sig = hash_hmac('sha256', $payloadToSign, $secret_key);

$qrDataObj = [
    'u' => $usuario_id,
    'ts' => $ts,
    'sig' => $sig,
    'nonce' => $nonce
];

echo json_encode([
    'success' => true,
    'qr_payload' => json_encode($qrDataObj),
    'expires_in' => 300
]);
?>
