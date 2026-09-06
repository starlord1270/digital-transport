<?php
/**
 * DIGITAL TRANSPORT - PUNTOS DE RECARGA (REFACTORIZADA CON MAPA REAL)
 */
$page_title = "Puntos de Recarga - Digital Transport";
$active_page = "puntos";

require_once '../backend/includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/header.php';

// Obtener puntos reales de la base de datos (Solo los ACTIVOS)
$stmt = $pdo->query("SELECT punto_id, nombre, ubicacion, lat, lng FROM PUNTO_RECARGA WHERE estado = 'ACTIVO' ORDER BY nombre ASC");
$puntos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

$puntos = [];
$iconos_demo = ['fa-store', 'fa-shopping-basket', 'fa-kiosk', 'fa-landmark'];

foreach ($puntos_db as $i => $p) {
    $puntos[] = [
        'punto_id' => $p['punto_id'],
        'nombre' => $p['nombre'],
        'lat' => $p['lat'] ?? -17.3895, // Fallback si no tiene
        'lng' => $p['lng'] ?? -66.1568,
        'dir' => $p['ubicacion'],
        'zona' => 'Punto Autorizado',
        'rating' => 4.5 + (rand(0, 5) / 10),
        'icon' => $iconos_demo[$i % count($iconos_demo)]
    ];
}
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<div class="animate-fade-in">
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Puntos de Recarga</h1>
        <p style="color: var(--text-muted);">Encuentra las ubicaciones físicas para recargar tu saldo o comprar tarjetas.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 450px; gap: 40px; align-items: start;">
        <!-- Lista de Puntos -->
        <div style="display: grid; gap: 20px;">
            <?php foreach ($puntos as $index => $p): ?>
            <div class="card punto-card" data-index="<?php echo $index; ?>" style="display: flex; gap: 24px; padding: 24px; align-items: center; cursor: pointer;">
                <div style="width: 60px; height: 60px; background: rgba(30, 136, 229, 0.1); color: var(--secondary); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="fas <?php echo $p['icon']; ?>"></i>
                </div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <h4 style="margin: 0; font-size: 1.1rem;"><?php echo $p['nombre']; ?></h4>
                        <span style="padding: 4px 10px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; background: var(--bg-main); color: var(--text-muted);"><?php echo $p['zona']; ?></span>
                    </div>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin: 4px 0 12px;"><?php echo $p['dir']; ?></p>
                    <div style="display: flex; gap: 16px; font-size: 0.8rem; color: var(--text-muted);">
                        <span><i class="fas fa-star" style="color: var(--warning);"></i> <?php echo $p['rating']; ?></span>
                        <span><i class="fas fa-clock"></i> 07:00 - 21:00</span>
                    </div>
                </div>
                <button class="btn btn-secondary" onclick="openDirections(<?php echo $p['lat']; ?>, <?php echo $p['lng']; ?>)" style="padding: 8px 16px; font-size: 0.85rem;">
                    <i class="fas fa-directions"></i> Cómo llegar
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Mapa -->
        <div style="position: sticky; top: 120px;">
            <div id="map" class="glass-card" style="height: 500px; padding: 0; overflow: hidden; border-radius: var(--radius-md); box-shadow: var(--shadow-md);"></div>
            
            <div class="card" style="margin-top: 24px;">
                <h4 style="margin-bottom: 16px;"><i class="fas fa-info-circle"></i> Info de Recarga</h4>
                <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5;">Presenta tu código QR en cualquiera de estos puntos para realizar una recarga en efectivo de forma inmediata.</p>
                <button class="btn btn-primary" style="width: 100%; margin-top: 20px;" onclick="window.location.href='index.php'">
                    Ver mi Código QR
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    // Inicializar Mapa
    const map = L.map('map').setView([-17.3895, -66.1568], 14);

    // Capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Datos de los puntos desde PHP a JS
    const puntos = <?php echo json_encode($puntos); ?>;
    const markers = [];

    // Añadir Marcadores
    puntos.forEach((p, index) => {
        const marker = L.marker([p.lat, p.lng]).addTo(map)
            .bindPopup(`<b>${p.nombre}</b><br>${p.dir}`);
        
        markers.push(marker);
    });

    // Función para navegar
    function openDirections(lat, lng) {
        window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
    }

    // Interacción con la lista
    document.querySelectorAll('.punto-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('button')) return; // No disparar si se clickea el botón
            
            const index = this.getAttribute('data-index');
            const p = puntos[index];
            
            map.flyTo([p.lat, p.lng], 16);
            markers[index].openPopup();
        });
    });
</script>

<style>
    .punto-card:hover {
        border-color: var(--secondary);
        transform: translateY(-2px);
    }
    /* Estilo para el mapa en modo oscuro si fuera necesario */
    [data-theme="dark"] .leaflet-tile {
        filter: brightness(0.6) invert(1) contrast(3) hue-rotate(200deg) saturate(0.3) brightness(0.7);
    }
</style>

<?php include 'includes/footer.php'; ?>