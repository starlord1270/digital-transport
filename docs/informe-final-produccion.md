# Informe Final de Producción — Digital Transport 🚍

**Fecha:** · **Alcance:** auditoría completa (5.ª pasada) del código, seguridad, esquema, configuración y despliegue.
**Veredicto:** ✅ **El código está listo para un despliegue piloto/productivo controlado.** No quedan bloqueantes de código ni vulnerabilidades críticas conocidas tras 4 rondas de correcciones verificadas. Lo que falta es **configuración externa** (pasarela bancaria real, servidor, secretos, correo) y **deuda técnica menor** — ambas diferibles, detalladas abajo.

---

## 1. Qué se validó en esta pasada (todo OK)

- **Lint PHP:** `php -l` en todo el árbol sin errores.
- **Pruebas:** Unit 8/8 · Security 14 tests OK. (Siguen siendo pruebas de lógica/helpers; ver deuda.)
- **Puntos de escritura de saldo:** solo 3 legítimos — `procesar_cobro` (débito de tarifa), `confirmar_recarga` (acreditación por operador/superadmin con bloqueo de fila, idempotencia por estado y anti auto-confirmación), y modo local (`APP_ENV=local`) de `procesar_recarga`. **Sin bypass de recarga** (eliminado el bloque huérfano de `recarga-digital.php`).
- **CSRF:** todos los endpoints mutadores validan token (`verifyCsrfToken`), y el frontend lo inyecta globalmente (meta + wrapper `fetch` en `header.php`). Exentos por diseño: login/registro (públicos) y `procesar_cobro` (JSON, mitigado por SameSite=Lax + QR firmado).
- **Autenticación/autorización:** contraseñas con `password_hash`, sesión con cookie endurecida iniciada desde `db.php`, `session_regenerate_id`, rate-limit por IP+email (`INTENTOS_LOGIN`, 5/15 min), roles estrictos (1–5), `linea_id`/punto siempre derivados de la sesión/BD (sin IDOR entre líneas ni puntos), choferes pendientes bloqueados, superadmin solo rol 5.
- **QR de pago:** firmado HMAC-SHA256 `{u,ts,nonce,sig}` con expiración 5 min y anti-replay; sin formatos legados; la clave se exige por entorno (sin secretos por defecto) y **falla cerrado** si falta.
- **Subidas:** extensión+MIME real+5 MB+nombre aleatorio, y `uploads/documentos/.htaccess` desactiva ejecución PHP.
- **Esquema:** `database_reset.sql` consolidado y alineado con el código (roles 1–5, enums `PENDIENTE/RECHAZADO`, `rating`, `comprobante_url`, `U_RECARGA.estado/referencia`, `NOTIFICACION_GLOBAL`, `AUDITORIA`, `TICKET_SOPORTE`, `INTENTOS_LOGIN`, seeds de punto/tipo de recarga y usuarios con hashes válidos verificados).
- **Manejo de errores:** sin `display_errors`; errores de BD genéricos + `error_log`; sin credenciales hardcodeadas; sin backdoors (`scratch/`, `fix_password.php` eliminados).
- **Higiene:** `.env` fuera de git; `.gitignore` y `.dockerignore` presentes; `.htaccess` raíz con `Options -Indexes`, bloqueo de `.env/.git/composer/*.sql`, HTTPS y cabeceras de seguridad.

---

## 2. Pendiente de CONFIGURACIÓN EXTERNA (no es código; corregir en despliegue)

### A. Pasarela / API de transacciones bancarias (dinero real)
Hoy el pago de recargas es un **flujo simulado por referencia + confirmación manual del operador** (`procesar_recarga` → consola `recargas.php` → `confirmar_recarga`). Para operar con dinero real se debe:
1. Elegir proveedor (QR bancario / Simple QR / pago móvil / pasarela).
2. Obtener credenciales (merchant/terminal, llaves API, certificados) y **configurarlas como variables de entorno** (nunca en código).
3. Integrar la llamada de creación de cobro y la **verificación/notificación (webhook)** de pago que acredite saldo automáticamente (hoy la acreditación es manual vía operador).
4. Manejar reembolsos/errores de la pasarela y una **reconciliación** (conciliar TRANSACCION/U_RECARGA contra los reportes del banco).

### B. Servidor / subida a producción
1. Dominio + DNS + **SSL** (existe `setup_ssl_certbot.sh` para Apache; en Nginx configurar a mano, ya que `.htaccess` no aplica: cabeceras de seguridad, bloqueo de ejecución en `uploads/`, redirección HTTPS, reglas de denegación de `includes/` y archivos sensibles).
2. Crear `.env` real en el servidor (no versionado): `APP_ENV=production`, `APP_DEBUG=false`, credenciales de **un usuario de BD dedicado** (no `root`), y `CSRF_SECRET`/`QR_SECRET` aleatorios (`openssl rand -hex 32`). En Docker: `export` antes de `docker-compose up`.
3. Base de datos: importar `database_reset.sql` (o `php backend/setup_database.php`) y **verificar/crear el superadmin** (`php backend/setup_superadmin.php`) y cambiar las contraseñas de los seeds (`admin123`, `123456`, `AdminSecure2026!`) antes del primer uso real.
4. **Servidor de correo/SMTP** configurado (hoy `update_chofer_status` usa `@mail()` suprimido; sin SMTP no llegan notificaciones por correo).
5. Permisos de escritura en `uploads/documentos/`; copias de seguridad (BD + uploads) programadas y restauración probada; rotación de logs; `frontend` como DocumentRoot o redirección raíz como la actual `index.php`.
6. Si el despliegue es en red cerrada/sin internet: alojar localmente fuentes/iconos (Font Awesome), `leaflet.*`, `qrcode.min.js`, el generador de QR (hoy usa `api.qrserver.com`) y `html5-qrcode` (CDN).

---

## 3. Deuda técnica menor (post-lanzamiento, no bloquea)
- Pruebas de endpoints reales (HTTP) — hoy Unit/Security/Smoke son de lógica y no detectan regresiones de integración.
- Menú/UX para operadores de punto (rol 2): la consola `recargas.php` se abre por URL; no hay enlace en el header.
- Página de login legacy `inicio-sesion-lineas-choferes/login.php` con redirecciones relativas rotas desde esa subcarpeta.
- `guardar_punto_recarga.php` no exige contraseña mínima (el resto sí, 8+).
- `procesar_cobro` (modo pasajero escanea bus) no exige token CSRF (mitigado por SameSite) — aceptable, pero puede endurecerse.
- Limpieza: `recarga.jpeg` ya no se referencia; carpeta duplicada `test/` vs `tests/`; reportes en `docs/*_report.md` desactualizados respecto al código real.

---

## 4. Conclusión
**El proyecto está en condiciones de pasar a producción** (piloto controlado) desde el punto de vista de código y seguridad. Antes de operar dinero real: completar la **integración con la pasarela bancaria** (A) y la **configuración del servidor/secretos/correo** (B). La deuda técnica de la sección 3 puede atenderse después. No se detectaron bloqueantes de código en esta auditoría final.
