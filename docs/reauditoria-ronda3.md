# Re-Auditoría Ronda 3 — Digital Transport 🚍 (estado actual)

**Fecha:** · **Veredicto:** la ronda 2 resolvió casi todas las regresiones que dejó la ronda 1 (login, sesiones en endpoints, CSRF de extremo a extremo, hashes de seed, QR firmado, esquema alineado). La aplicación vuelve a ser **usable en su configuración de demo/local**. **Aún NO está lista para producción** por: (1) el flujo de recarga quedó roto e inconcluso en una instalación limpia, (2) un endpoint de superadmin nuevo está mal construido, (3) una página de perfil de admin queda fuera del parche CSRF y (4) persisten fugas de error al cliente, entre otras deudas.

---

## 1. ✅ Corregido y verificado en esta ronda

| Ítem | Estado |
|---|---|
| **R1 – Login fatal** (`generateCsrfToken` inexistente) | ✅ `generateCsrfToken()` definido en `backend/includes/security.php` |
| **R2 – CSRF sin token en frontend** | ✅ `frontend/includes/header.php` expone `<meta name="csrf-token">` y un wrapper de `fetch()` que añade `X-CSRF-TOKEN` y `csrf_token` a todo POST/PUT/DELETE. Los formularios mutadores (perfil, contraseñas, canjes, validaciones, estados, superadmin) quedan cubiertos. |
| **R3 – Endpoints sin `session_start()`** | ✅ `backend/includes/db.php` ahora incluye `security.php` primero → cualquier archivo que cargue la BD inicia sesión con cookie endurecida. `fetch_dashboard_data`, `check_new_payments`, `fetch_perfil_admin`, etc. dejan de dar 401. |
| **R4 – Seeds con hash inválido** | ✅ Hashes regenerados (coste 12); `password_verify` pasa para `admin123` (admin), `123456` (pasajero/estudiante) y `AdminSecure2026!` (superadmin). |
| **QR firmado de extremo a extremo** | ✅ `backend/generar_qr_pago.php` firma `{u,ts,sig,nonce}` con `QR_SECRET`/`CSRF_SECRET` (falla si no hay clave). `frontend/index.php` carga ese QR y lo refresca cada 4 min. `procesar_cobro.php` **eliminó el fallback legado**: en modo chofer solo acepta el JSON firmado, valida expiración (5 min), HMAC y nonce anti-replay; verifica chofer ACTIVO. |
| **Recarga: separación solicitud/confirmación** | ⚠️ Parcial (ver pendientes): ya no acredita sola; crea solicitud `U_RECARGA` y la acreditación la hace `confirmar_recarga.php` (rol 2/5 + CSRF). |
| **Superadmin: CSRF + errores genéricos + esquema** | ✅ Todos los mutadores `backend/superadmin/*` validan CSRF y devuelven errores genéricos. Columnas alineadas: `NOTIFICACION_GLOBAL(usuario_id_emisor,titulo,mensaje,tipo_objetivo,fecha_creacion)`, `AUDITORIA(log_id,…)`, `TICKET_SOPORTE(respuesta_admin,fecha_resolucion)`, tabla `INTENTOS_LOGIN`. |
| **Rate-limit login** | ✅ Ahora por IP+email en BD (`INTENTOS_LOGIN`), 5 intentos/15 min. |
| **XSS feed del chofer** | ✅ `escapeHtml()` aplicado en `cobro-chofer.php` al renderizar pagos. |
| **Registro público** | ✅ `registro-lineas.php` quedó solo para choferes (rol 3); el rol 4 solo lo crea superadmin (rol 5). |
| **Subidas / backdoors / higiene** | ✅ Mantiene validación de archivos + `.htaccess` en uploads; `scratch/` y `fix_password.php` eliminados; sin `display_errors` en el código; lint completo sin errores; Unit/Security OK. |

---

## 2. 🔴 Pendientes que impiden producción

### P-A. Flujo de recarga roto en instalación limpia + sin cierre operativo
1. `database_reset.sql` **no siembra ningún `PUNTO_RECARGA` ni filas en `TIPO_RECARGA`**, pero `backend/procesar_recarga.php` inserta siempre `U_RECARGA (…, punto_id=1, tipo_recarga_id=1, …)` → en una BD recién importada la inserción **viola las FKs** y toda recarga (aún la de solicitud) falla con error.
2. Cuando la solicitud sí se guarda, **no existe ninguna pantalla** (operador de punto/superadmin) para listar solicitudes PENDIENTES y confirmarlas: `confirmar_recarga.php` no tiene UI que lo llame → el pasajero **nunca recibe saldo** en producción (a menos que se active `AUTO_CONFIRM_RECHARGE=true`, lo cual vuelve a acreditar saldo sin verificación real).
3. `confirmar_recarga.php` no es idempotente: `U_RECARGA` no tiene columna de estado y el endpoint no marca la solicitud como confirmada → la misma `recarga_id` puede acreditarse **dos veces** (doble acreditación), y además acepta un par arbitrario `usuario_id + monto` sin referenciar una solicitud (cualquier operador rol 2 puede acreditar cualquier monto a cualquiera).
- **Fix recomendado:** seed de un punto de recarga oficial y de `TIPO_RECARGA('QR/Online')`; columna `estado` en `U_RECARGA` con verificación `estado='PENDIENTE'` en `confirmar_recarga` (update a `CONFIRMADA` dentro de la misma transacción); UI de consola para puntos de recarga/superadmin; eliminar el auto-confirm por rol (solo superadmin o flujo real de pasarela).

### P-B. `backend/superadmin/crear_admin_linea.php` no funciona
- Llama a `verifyCsrfToken()` **sin argumento** → siempre devuelve false → **403 permanente** para la acción que crea admins de línea.
- Además usa `registrarAuditoria()` **sin incluir** `../includes/functions.php` → al corregir el CSRF, lanzaría error fatal de función indefinida.
- No existe página frontend que lo consuma (endpoint huérfano).
- **Fix:** leer token (`$_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN']`), incluir `functions.php`, y conectar una pantalla en `dashboard-superadmin` (envío de token cubierto por el wrapper de `header.php`).

### P-C. `frontend/dashboard-admin-linea/perfil-admin.php` queda fuera del parche CSRF
- Es una página HTML **independiente que no incluye `header.php`** → no tiene `<meta csrf-token>` ni el wrapper de `fetch` → sus llamadas a `update-perfil-admin.php` y `change-password-admin.php` (que ahora exigen token) responden **403**. La lectura de perfil (GET) sí funciona.
- **Fix:** migrar la página al layout con `header.php` (o añadir el meta + helper JS y `session_start()` con seguridad en esa página).

### P-D. Fuga de errores PDO al cliente (informativa, ~18 endpoints)
Siguen exponiendo `$e->getMessage()` en JSON (revelan estructura SQL/columnas): `fetch-perfil-chofer.php`, `fetch-perfil-pasajero.php`, `fetch_history.php`, `fetch_recaudacion_chofer.php`, `fetch_tarifas.php`, `procesar_cobro.php`, `procesar_recarga.php`, `validacion-registro.php`, la mayoría de `backend/superadmin/fetch_*`, etc. En producción deberían responder mensaje genérico + `error_log`.

---

## 3. 🟡 Menores / deuda de calidad

1. **`.env` sigue versionado** (el `.gitignore` no des-trackea archivos ya rastreados): `git ls-files` muestra `.env` y `uploads/documentos/1777241402_fondo.png`. Añadir `git rm --cached` y purgar historial si el repo es público.
2. **Deploy inconsistente:** `docker-compose.yml` solo levanta MySQL (`root/root`) pero el `.env` de la app apunta a `localhost/root/sin clave` y no hay servicio web en el compose pese a que el README lo sugiere; el `Dockerfile` copia todo (incluido `.env` con secretos de ejemplo).
3. **Firma del QR del BUS sin valor criptográfico** (`BUS_PAY_<placa>_<chofer_id>` en `cobro-chofer.php`): sirve solo para que el pasajero pague; el servidor ahora valida chofer ACTIVO y anti-doble-pago (2 min), pero un QR falso podría desviar pagos a otro chofer activo (estafa física/pasiva). Valorar vincular el pago al bus/placa real (token firmado también en el QR del bus o confirmación por ubicación).
4. **Políticas de contraseña inconsistentes:** registro exige 8+, pero `change-password-admin.php` sigue aceptando 6+.
5. **`procesar_cobro.php` modo pasajero-escanea-bus** no valida CSRF (por diseño JSON) pero tampoco valida que el `chofer_id` corresponda a la unidad mostrada; el doble pago queda limitado a 1 por pasajero+chofer cada 2 min.
6. **Tests siguen sin tocar endpoints reales** (unit y security prueban helpers/matrices): ninguna de las regresiones R1–R3 fue detectada por la suite. `phpunit.xml` se tocó pero no hay tests HTTP nuevos; conviene añadir pruebas de humo (login 200, POST sin token 403, recarga PENDIENTE, QR forjado rechazado).

---

## 4. Pruebas ejecutadas en esta ronda
- `php -l` en todo backend/frontend/tests: sin errores.
- PHPUnit Unit: 8 OK · Security: 8 OK (no cubren endpoints; sin servidor MySQL en este entorno para las suites de BD).
- Verificación funcional estática de cada hallazgo de la ronda anterior (listada en la tabla de la sección 1).
