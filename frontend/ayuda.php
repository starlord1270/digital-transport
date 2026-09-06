<?php
/**
 * DIGITAL TRANSPORT - CENTRO DE AYUDA (RENDERIZADOR DE MANUALES)
 */
$page_title = "Centro de Ayuda - Digital Transport";
$active_page = "ayuda";

require_once '../backend/includes/db.php';
include 'includes/header.php';

// Determinar qué manual cargar según el rol
$rol = $_SESSION['tipo_usuario_id'] ?? 1;
if ($rol == 3) {
    $manual_file = 'chofer.md';
} elseif ($rol == 4) {
    $manual_file = 'admin_linea.md';
} elseif ($rol == 5) {
    $manual_file = 'super_admin.md';
} else {
    $manual_file = 'pasajero.md';
}
$manual_path = "../docs/manuales/$manual_file";

$content = "No se pudo cargar el manual.";

if (file_exists($manual_path)) {
    $content = file_get_contents($manual_path);
}

// Convertir Markdown simple a HTML (Solo encabezados y listas para no depender de librerías externas)
$html_content = htmlspecialchars($content);
$html_content = preg_replace('/^# (.*)$/m', '<h1 style="color:var(--secondary); font-size:2.5rem; margin-bottom:20px;">$1</h1>', $html_content);
$html_content = preg_replace('/^## (.*)$/m', '<h2 style="color:var(--primary); margin-top:40px; margin-bottom:15px;">$1</h2>', $html_content);
$html_content = preg_replace('/^### (.*)$/m', '<h3 style="margin-top:20px;">$1</h3>', $html_content);
$html_content = preg_replace('/---/', '<hr style="border:none; border-top:1px solid var(--bg-main); margin:30px 0;">', $html_content);
$html_content = preg_replace('/^\* (.*)$/m', '<li style="margin-bottom:8px;">$1</li>', $html_content);
$html_content = preg_replace('/^\d\. (.*)$/m', '<li style="margin-bottom:8px;">$1</li>', $html_content);
$html_content = preg_replace('/\*\*(.*)\*\*/', '<strong>$1</strong>', $html_content);

?>

<div class="animate-fade-in" style="max-width: 900px; margin: 0 auto;">
    <div class="card" style="padding: 60px; line-height: 1.8;">
        <?php echo nl2br($html_content); ?>
        
        <div style="margin-top: 60px; padding: 30px; background: var(--bg-main); border-radius: 12px; text-align: center;">
            <p style="margin-bottom: 20px; font-weight: 600;">¿Aún tienes dudas?</p>
            <button class="btn btn-primary" onclick="window.location.href='soporte.php'">
                <i class="fas fa-headset"></i> Contactar a Soporte
            </button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
