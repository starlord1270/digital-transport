<?php
/**
 * DIGITAL TRANSPORT - GESTIÓN DE LÍNEAS (SUPER ADMIN)
 */
$page_title = "Gestión de Líneas - Digital Transport";
$active_page = "lineas";

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

<div class="animate-fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
        <div>
            <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Gestión de Líneas</h1>
            <p style="color: var(--text-muted);">Administra todas las líneas de transporte activas en el sistema.</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('modal-linea')">
            <i class="fas fa-plus"></i> Nueva Línea
        </button>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: rgba(0,0,0,0.02); border-bottom: 2px solid var(--bg-main);">
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">ID</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Nombre de la Línea</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Rutas</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-lineas">
                <!-- Cargando dinámicamente -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nueva Línea -->
<div id="modal-linea" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
    <div class="card" style="max-width: 500px; margin: 100px auto; padding: 40px; position: relative;">
        <span onclick="closeModal('modal-linea')" style="position: absolute; right: 24px; top: 24px; cursor: pointer; font-size: 1.5rem; color: var(--text-muted);">&times;</span>
        <h2 style="margin-bottom: 24px;">Configurar Nueva Línea</h2>
        <form id="form-linea">
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Nombre de la Línea (Ej. Línea 240)</label>
                <input type="text" name="nombre" class="form-input" required placeholder="Nombre descriptivo">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">Crear Línea</button>
        </form>
    </div>
</div>

<script>
    async function loadLineas() {
        try {
            const response = await fetch('../../backend/superadmin/fetch_lineas_global.php');
            const data = await response.json();
            if(data.success) {
                const tbody = document.getElementById('tabla-lineas');
                tbody.innerHTML = '';
                data.lineas.forEach(l => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid var(--bg-main)';
                    tr.innerHTML = `
                        <td style="padding: 20px; font-weight: 700; color: var(--text-muted);">#${l.linea_id}</td>
                        <td style="padding: 20px; font-weight: 700;">${l.nombre}</td>
                        <td style="padding: 20px;">
                            <span style="background: var(--bg-main); padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                                ${l.total_rutas} rutas activas
                            </span>
                        </td>
                        <td style="padding: 20px;">
                            <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.75rem;" onclick="gestionarRutas(${l.linea_id}, '${l.nombre}')">
                                <i class="fas fa-map-marked-alt"></i> Gestionar Rutas
                            </button>
                            <button class="btn btn-danger" style="padding: 6px 12px; font-size: 0.75rem; background: #ff5252;" onclick="eliminarLinea(${l.linea_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        } catch (e) { console.error(e); }
    }

    document.getElementById('form-linea').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await fetch('../../backend/superadmin/guardar_linea.php', { method: 'POST', body: formData });
        const result = await res.json();
        if(result.success) {
            closeModal('modal-linea');
            loadLineas();
        } else { alert(result.error); }
    });

    function openModal(id) { document.getElementById(id).style.display = 'block'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function gestionarRutas(id, nombre) {
        // Redirigir o abrir otro modal para rutas
        alert('Cargando editor de rutas para: ' + nombre);
    }

    async function eliminarLinea(id) {
        if(!confirm('¿Estás seguro? Se eliminarán todos los choferes y rutas asociados.')) return;
        // Implementar lógica de borrado
    }

    loadLineas();
</script>

<?php include '../includes/footer.php'; ?>
