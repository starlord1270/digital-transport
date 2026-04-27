<?php
/**
 * DIGITAL TRANSPORT - GESTIÓN DE PUNTOS DE RECARGA (SUPER ADMIN)
 */
$page_title = "Puntos de Recarga - Digital Transport";
$active_page = "puntos";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad: Solo Super Admin (5)
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario_id'] != 5) {
    header("Location: ../inicio-sesion-usuarios.php");
    exit();
}

require_once '../../backend/includes/db.php';
include '../includes/header.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="animate-fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
        <div>
            <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Red de Recargas</h1>
            <p style="color: var(--text-muted);">Administra los puntos físicos habilitados para la carga de saldo en efectivo.</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('modal-punto')">
            <i class="fas fa-store"></i> Nuevo Punto de Venta
        </button>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;" id="contenedor-puntos">
        <!-- Dinámico -->
    </div>
</div>

<!-- Modal Nuevo Punto -->
<div id="modal-punto" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
    <div class="card" style="max-width: 800px; margin: 20px auto; padding: 40px; position: relative; max-height: 90vh; overflow-y: auto;">
        <span onclick="closeModal('modal-punto')" style="position: absolute; right: 24px; top: 24px; cursor: pointer; font-size: 1.5rem; color: var(--text-muted);">&times;</span>
        <h2 style="margin-bottom: 8px;">Nuevo Punto de Recarga</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 24px;">Se creará una cuenta de operador automáticamente.</p>
        
        <form id="form-punto">
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Nombre del Negocio</label>
                <input type="text" name="nombre_punto" class="form-input" required placeholder="Ej: Tienda 'Doña María'">
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Dirección / Referencia</label>
                <input type="text" name="ubicacion" class="form-input" required placeholder="Ej: Av. Principal Esq. Calle 4">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label">Latitud</label>
                    <input type="number" name="lat" id="input-lat" step="any" class="form-input" required readonly style="background: rgba(0,0,0,0.05);">
                </div>
                <div class="form-group">
                    <label class="form-label">Longitud</label>
                    <input type="number" name="lng" id="input-lng" step="any" class="form-input" required readonly style="background: rgba(0,0,0,0.05);">
                </div>
            </div>
            
            <div id="map-selector" style="height: 450px; border-radius: 8px; margin-bottom: 24px; border: 1px solid var(--bg-main); box-shadow: inset 0 0 10px rgba(0,0,0,0.1);"></div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -16px; margin-bottom: 24px; font-weight: 700;"><i class="fas fa-search-location"></i> Haz clic en el mapa para marcar la ubicación exacta del punto de recarga.</p>

            <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--bg-main);">
            <h4 style="margin-bottom: 16px;">Datos del Operador</h4>
            <div class="form-group" style="margin-bottom: 16px;">
                <input type="text" name="nombre_operador" class="form-input" required placeholder="Nombre completo del encargado">
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <input type="email" name="email" class="form-input" required placeholder="Correo electrónico de acceso">
            </div>
            <div class="form-group" style="margin-bottom: 24px;">
                <input type="password" name="password" class="form-input" required placeholder="Contraseña temporal">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">Habilitar Punto de Venta</button>
        </form>
    </div>
</div>

<script>
    async function loadPuntos() {
        try {
            const res = await fetch('../../backend/superadmin/fetch_puntos_recarga.php');
            const data = await res.json();
            if(data.success) {
                const container = document.getElementById('contenedor-puntos');
                container.innerHTML = '';
                data.puntos.forEach(p => {
                    const card = document.createElement('div');
                    card.className = 'card animate-fade-in';
                    card.style.padding = '24px';
                    const statusBadge = p.estado === 'ACTIVO' 
                        ? '<span style="background: rgba(76, 175, 80, 0.1); color: var(--accent); padding: 4px 10px; border-radius: 12px; font-size: 0.65rem; font-weight: 800;">ACTIVO</span>'
                        : '<span style="background: rgba(244, 67, 54, 0.1); color: var(--danger); padding: 4px 10px; border-radius: 12px; font-size: 0.65rem; font-weight: 800;">INACTIVO</span>';

                    card.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                            <div style="display: flex; align-items: start; gap: 16px;">
                                <div style="background: rgba(76, 175, 80, 0.1); color: var(--accent); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 1.1rem;">${p.nombre}</h3>
                                    <p style="margin: 4px 0 0 0; font-size: 0.8rem; color: var(--text-muted);"><i class="fas fa-location-arrow"></i> ${p.ubicacion}</p>
                                </div>
                            </div>
                            ${statusBadge}
                        </div>
                        <div style="background: var(--bg-main); padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                            <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Operador Responsable</div>
                            <div style="font-weight: 700; font-size: 0.9rem;">${p.operador}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">${p.email}</div>
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <button class="btn ${p.estado === 'ACTIVO' ? 'btn-danger' : 'btn-primary'}" style="flex: 1; font-size: 0.75rem; ${p.estado === 'INACTIVO' ? 'background: var(--accent);' : ''}" onclick="toggleEstado(${p.punto_id}, '${p.estado}')">
                                <i class="fas ${p.estado === 'ACTIVO' ? 'fa-power-off' : 'fa-check-circle'}"></i> ${p.estado === 'ACTIVO' ? 'Inhabilitar' : 'Habilitar'}
                            </button>
                            <button class="btn btn-secondary" style="font-size: 0.75rem;" onclick="eliminarPunto(${p.punto_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    container.appendChild(card);
                });
            }
        } catch(e) {}
    }

    document.getElementById('form-punto').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await fetch('../../backend/superadmin/guardar_punto_recarga.php', { method: 'POST', body: formData });
        const result = await res.json();
        if(result.success) {
            alert('¡Punto de recarga habilitado!');
            closeModal('modal-punto');
            loadPuntos();
        } else { alert(result.error); }
    });

    function openModal(id) { document.getElementById(id).style.display = 'block'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    async function toggleEstado(id, estadoActual) {
        const nuevoEstado = estadoActual === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
        if(!confirm(`¿Deseas marcar este punto como ${nuevoEstado}?`)) return;
        
        const formData = new FormData();
        formData.append('punto_id', id);
        formData.append('estado', nuevoEstado);
        
        try {
            const res = await fetch('../../backend/superadmin/toggle_punto_status.php', { method: 'POST', body: formData });
            const result = await res.json();
            if(result.success) loadPuntos();
        } catch(e) {}
    }

    async function eliminarPunto(id) {
        if(!confirm('¿Estás seguro de eliminar este punto de recarga definitivamente?')) return;
        const formData = new FormData();
        formData.append('punto_id', id);
        try {
            const res = await fetch('../../backend/superadmin/eliminar_punto.php', { method: 'POST', body: formData });
            const result = await res.json();
            if(result.success) loadPuntos();
            else alert(result.error);
        } catch(e) {}
    }

    loadPuntos();
</script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let selectorMap;
    let selectorMarker;

    function initSelectorMap() {
        if (selectorMap) return;
        
        selectorMap = L.map('map-selector').setView([-17.3895, -66.1568], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(selectorMap);

        selectorMap.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            
            document.getElementById('input-lat').value = lat;
            document.getElementById('input-lng').value = lng;

            if (selectorMarker) {
                selectorMarker.setLatLng(e.latlng);
            } else {
                selectorMarker = L.marker(e.latlng).addTo(selectorMap);
            }
        });

        // Forzar renderizado correcto al abrir el modal
        setTimeout(() => { selectorMap.invalidateSize(); }, 200);
    }

    // Modificar openModal para inicializar el mapa
    const originalOpenModal = openModal;
    openModal = function(id) {
        originalOpenModal(id);
        if(id === 'modal-punto') initSelectorMap();
    };
</script>

<?php include '../includes/footer.php'; ?>
