<?php
/**
 * DIGITAL TRANSPORT - GESTIÓN GLOBAL DE USUARIOS (SUPER ADMIN)
 */
$page_title = "Gestión de Usuarios - Digital Transport";
$active_page = "usuarios";

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
            <h1 style="font-size: 2.5rem; margin-bottom: 8px;">Usuarios del Sistema</h1>
            <p style="color: var(--text-muted);">Administra y supervisa todas las cuentas registradas en la plataforma.</p>
        </div>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="glass-card" style="padding: 24px; margin-bottom: 32px; display: flex; gap: 16px; align-items: flex-end;">
        <div style="flex: 2;">
            <label class="form-label">Buscar Usuario</label>
            <input type="text" id="search-input" class="form-input" placeholder="Nombre, Email o Cédula..." onkeyup="filterUsers()">
        </div>
        <div style="flex: 1;">
            <label class="form-label">Filtrar por Rol</label>
            <select id="role-filter" class="form-input" onchange="filterUsers()">
                <option value="todos">Todos los Roles</option>
                <option value="1">Pasajeros</option>
                <option value="3">Choferes</option>
                <option value="4">Admins de Línea</option>
                <option value="5">Super Admins</option>
            </select>
        </div>
        <button class="btn btn-secondary" onclick="loadUsers()">
            <i class="fas fa-sync-alt"></i> Actualizar
        </button>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: rgba(0,0,0,0.02); border-bottom: 2px solid var(--bg-main);">
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Usuario</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Rol</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Contacto / ID</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Estado</th>
                    <th style="padding: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-usuarios">
                <!-- Cargando dinámicamente -->
            </tbody>
        </table>
    </div>
</div>

<script>
    let allUsers = [];

    async function loadUsers() {
        try {
            const response = await fetch('../../backend/superadmin/fetch_usuarios_global.php');
            const data = await response.json();
            if(data.success) {
                allUsers = data.usuarios;
                renderUsers(allUsers);
            }
        } catch (e) { console.error(e); }
    }

    function renderUsers(users) {
        const tbody = document.getElementById('tabla-usuarios');
        tbody.innerHTML = '';
        users.forEach(u => {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--bg-main)';
            
            let rolBadge = '';
            switch(parseInt(u.tipo_usuario_id)) {
                case 1: rolBadge = '<span style="color: var(--accent);">Pasajero</span>'; break;
                case 3: rolBadge = '<span style="color: var(--secondary);">Chofer</span>'; break;
                case 4: rolBadge = '<span style="color: var(--warning);">Admin Línea</span>'; break;
                case 5: rolBadge = '<span style="color: #6200ea; font-weight: 800;">Super Admin</span>'; break;
            }

            tr.innerHTML = `
                <td style="padding: 20px;">
                    <div style="font-weight: 700;">${u.nombre_completo}</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Registrado: ${u.fecha_registro}</div>
                </td>
                <td style="padding: 20px; font-size: 0.85rem; font-weight: 600;">${rolBadge}</td>
                <td style="padding: 20px;">
                    <div style="font-size: 0.85rem;">${u.email}</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">CI: ${u.documento_identidad}</div>
                </td>
                <td style="padding: 20px;">
                    <span style="background: rgba(76, 175, 80, 0.1); color: var(--accent); padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700;">ACTIVO</span>
                </td>
                <td style="padding: 20px;">
                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.75rem;" onclick="resetPassword(${u.usuario_id})">
                        <i class="fas fa-key"></i> Pass
                    </button>
                    <button class="btn btn-danger" style="padding: 6px 12px; font-size: 0.75rem; background: #ff5252;" onclick="bloquearUsuario(${u.usuario_id})">
                        <i class="fas fa-ban"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function filterUsers() {
        const query = document.getElementById('search-input').value.toLowerCase();
        const role = document.getElementById('role-filter').value;
        
        const filtered = allUsers.filter(u => {
            const matchesQuery = u.nombre_completo.toLowerCase().includes(query) || 
                               u.email.toLowerCase().includes(query) || 
                               u.documento_identidad.includes(query);
            const matchesRole = role === 'todos' || u.tipo_usuario_id == role;
            return matchesQuery && matchesRole;
        });
        
        renderUsers(filtered);
    }

    async function resetPassword(id) {
        if(!confirm('¿Deseas resetear la contraseña de este usuario a 123456?')) return;
        // Lógica backend
    }

    async function bloquearUsuario(id) {
        if(!confirm('¿Estás seguro de bloquear este acceso? El usuario no podrá iniciar sesión.')) return;
        // Lógica backend
    }

    loadUsers();
</script>

<?php include '../includes/footer.php'; ?>
