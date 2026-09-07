# Re-Auditoría — Digital Transport 🚍 (tras la primera ronda de correcciones)

**Veredicto:** las correcciones **van en la dirección correcta** y se eliminaron riesgos importantes (backdoors, scratch, subidas sin validar, registro abierto de admins, esquema sin tablas). **Sin embargo, la aplicación NO es desplegable todavía**: la ronda introdujo **regresiones que la dejan inoperante** (login muere con error fatal, paneles devuelven 401/403 por sesiones y CSRF mal conectados) y **varias correcciones quedaron a medias** (QR sin firma en los flujos reales, recarga sin verificación real de pago, esquema aún desalineado en columnas).

---

## 1. Qué se corrigió bien ✅

| Hallazgo anterior | Estado ahora |
|---|---|
| Registro público como Admin de Línea (C1) | ✅ `backend/validacion-registro.php` solo permite rol 4/5 con sesión de SuperAdmin (rol 5) |
| Backdoors/scratch (C4/fix_password) | ✅ Eliminados `backend/fix_password.php`, `scratch/`, `backend/notificaciones_table.sql` |
| Subida de comprobantes (C4) | ✅ Extensión + MIME real + tamaño 5 MB + nombre aleatorio + `.htaccess` en `uploads/documentos/` que desactiva ejecución PHP y bloquea ejecutables |
| IDOR entre líneas (A2) | ✅ `fetch_dashboard_data.php`, `fetch_choferes_data.php`, `fetch_reportes_flujo_caja.php` derivan `linea_id` de `ADMIN_LINEA` (rol 4); GET `linea_id` solo para superadmin |
| IDOR `check_new_payments.php` (A3) | ✅ Requiere rol chofer y deriva `chofer_id` de la sesión |
| Migración mysqli→PDO en endpoints de admin (A1) | ✅ `fetch_perfil_admin.php`, `update-perfil-admin.php`, `change-password-admin.php` reescritos con PDO y columna `password_hash` |
| Superadmin de fábrica (A5) | ✅ Rol 5 en `TIPO_USUARIO` + seed + script CLI `setup_superadmin.php` |
| Esquema base (A6) | ✅ Consolidado: `estado_servicio` con `PENDIENTE/RECHAZADO`, `rating`, `comprobante_url`, `CANJE_CHOFER.estado`, `PUNTO_RECARGA.estado`, tablas `NOTIFICACION_GLOBAL`, `AUDITORIA`, `TICKET_SOPORTE` |
| CSRF (M1) | ⚠️ Añadido a 11 endpoints mutadores (pero el frontend no envía el token → ver R2) |
| Rate-limit login (M2) | ⚠️ Añadido pero inefectivo (por sesión, no por IP/cuenta) |
| `.gitignore` | ✅ Creado (pero `.env` sigue trackeado → ver P3) |
| Contraseña mínima | ✅ 8 caracteres en registro |

---

## 2. Regresiones NUEVAS introducidas (bloqueantes) 🔴

### R1. El LOGIN ya no funciona: error fatal `generateCsrfToken()`
- `backend/validacion-login.php` (línea 63) llama a **`generateCsrfToken()`**, función que **no existe** en ningún archivo (`backend/includes/security.php` solo define `getCsrfToken()` y `verifyCsrfToken()`).
- **Demostrado:** `function_exists('generateCsrfToken') → false` y la llamada lanza `Call to undefined function generateCsrfToken()`.
- **Impacto:** todo login con credenciales válidas (pasajero, chofer, admin, superadmin) termina en error fatal 500 → **nadie puede entrar a la aplicación**. El resto de hallazgos queda subordinado a este: hasta corregirlo no se puede operar ni probar nada.
- Fix mínimo: renombrar a `getCsrfToken()` (o definir `generateCsrfToken` como alias en security.php y generarlo al iniciar sesión).

### R2. CSRF validado en servidor… pero el frontend no envía el token → 403 en todos los flujos
- Los 11 endpoints con `verifyCsrfToken()` (`change-password-*`, `update-perfil-*`, `update_chofer_status`, `procesar_canje`, `procesar_validacion_pasajero`, `solicitar_canje`, `procesar_recarga`) exigen `csrf_token` o cabecera `X-CSRF-TOKEN`.
- El frontend **solo inyecta token en `recarga-digital.php`**. Ningún otro JS añade el campo ni la cabecera (verificado con grep: `csrf`/`X-CSRF` solo aparece en ese archivo).
- **Impacto:** desde la UI quedan rotos: cambio de contraseña y perfil (pasajero/chofer/admin), aprobar/rechazar validación de pasajero, aprobar canje, cambiar estado de chofer y **solicitar liquidación del chofer** → todos devuelven **403 "Token CSRF inválido o ausente"**.
- Fix: inyectar el token de forma central (p. ej. `<meta name="csrf-token">` en `header.php` y cabecera `X-CSRF-TOKEN` en todos los `fetch` POST, o campo oculto en cada formulario) y quitar la protección solo donde no aplique.

### R3. Endpoints que leen `$_SESSION` sin iniciar sesión → 401 permanente
Estos archivos fueron "arreglados" (cambiaron a `$_SESSION['logged_in']`) pero **nunca llaman `session_start()` ni incluyen `security.php`**, así que en cada petición HTTP `$_SESSION` está vacío y responden siempre 401:
- `backend/fetch_dashboard_data.php`, `backend/fetch_choferes_data.php`, `backend/fetch_reportes_flujo_caja.php` → **dashboard del admin de línea vacío/roto**.
- `backend/check_new_payments.php` → **el panel del chofer nunca ve pagos**.
- `backend/fetch_perfil_admin.php` → perfil del admin roto.
- `backend/fetch_tickets.php` → 401 (además sigue con credenciales hardcodeadas y `display_errors`, ver P2).
- Fix: añadir `session_start()` (o incluir `security.php`) en la cabecera de cada endpoint que use `$_SESSION`.

### R4. Seed de SuperAdmin y "Admin Maestro" con hash inválido → no se puede entrar tras importar la BD
- El hash del superadmin en `database_reset.sql` es un **placeholder fabricado**: `password_verify('AdminSecure2026!', hash) === false` (verificado). Igual ocurre con el "Admin Maestro Global" comentado como `admin123` (`password_verify` → false).
- **Impacto:** tras `php backend/setup_database.php`, los seeds admin/superadmin no aceptan su contraseña documentada. Única vía de entrada: ejecutar `php backend/setup_superadmin.php` (que regenerea el hash correcto) y registrar cuentas nuevas. Pero como el login está roto (R1), tampoco sirve hasta corregirlo.
- Fix: generar hashes reales (p. ej. `password_hash()` en un script) y documentar contraseñas, o exigir `setup_superadmin.php` como paso obligatorio de despliegue.

---

## 3. Correcciones a medias / pendientes 🟠

### P1. QR: la firma HMAC es "código muerto"; el flujo real sigue sin firma
- `procesar_cobro.php` implementa verificación de token JSON `{u,ts,sig,nonce}` con expiración de 5 min y anti-replay **pero el frontend no genera ese formato**: `frontend/index.php` sigue emitiendo `USER_<id>` y `cobro-chofer.php` sigue emitiendo `BUS_PAY_<placa>_<chofer_id>` sin firmar.
- Peor: en `procesar_cobro.php` (líneas 68–72) **se conserva el fallback a los formatos legados sin firma** (`DT-USER-`, `USER_`), de modo que un chofer malicioso puede seguir fabricando QRs de cualquier usuario (o iterar IDs) y debitar pasajes.
- Además, la clave HMAC usa **fallback hardcodeado** `'digital_transport_qr_secret_key'` si falta `CSRF_SECRET` (y el `.env` con el secreto está versionado) → la firma es forjable por cualquiera que lea el repo.
- El modo `bus_payment` (pasajero escanea bus) sigue sin verificar que el `chofer_id` exista/esté ACTIVO, y sin límite anti-repetición (un pasajero puede pagar N veces seguidas).
- Fix: (1) generar en el frontend el token firmado (o mejor, servirlos desde un endpoint PHP que firme y expire); (2) **eliminar los formatos legados**; (3) exigir `CSRF_SECRET` desde entorno sin valor por defecto; (4) validar chofer ACTIVO/existente también en `bus_payment` + ventana anti-doble-pago.

### P2. Recarga: menos trivial, pero sigue acreditando saldo sin pago verificado
- `procesar_recarga.php` exige sesión, CSRF, monto 1–5000 y una `referencia_pago` de ≥6 caracteres, y evita repetir mismo monto en 5 minutos.
- **Pero la referencia no se valida contra ninguna pasarela/banco**: el usuario inventa una cadena y el saldo se acredita igual → sigue siendo "dinero gratis con fricción".
- Fix real: estado "PENDIENTE de confirmación" hasta que un punto de recarga/pasarela confirme la operación (hook/cola), o integración con API de pago.

### P3. Higiene de repo a medias
- `.gitignore` creado ✅, pero **`.env` sigue trackeado** (gitignore no afecta archivos ya versionados) y **`.env.example` sigue con `CSRF_SECRET=change_this_...`**; las credenciales locales y el secreto quedan en el historial. Falta `git rm --cached .env` (y purgar historial si es público). `uploads/documentos/1777241402_fondo.png` también sigue en git.
- `backend/fetch_tickets.php` persiste con **credenciales hardcodeadas** y `display_errors` (no está en la lista de regresión 401 — está, porque no inicia sesión; pero además sigue mal diseñado y alcanzable por URL).

### P4. Superadmin: sin CSRF y con columnas aún desalineadas
- `backend/superadmin/*` **no recibió CSRF ni cambios** (siguen con `session_start()` + rol 5 y sin token).
- El nuevo esquema quedó **desincronizado en nombres de columna** con el código:
  - `NOTIFICACION_GLOBAL`: el esquema define `tipo/destinatarios/enviado_por/fecha_envio`; el código usa `usuario_id_emisor/tipo_objetivo/fecha_creacion` → `enviar_notificacion_global.php` y `fetch_notificaciones_globales.php` fallan (columna desconocida); el banner de `notificaciones_display.php` falla en silencio.
  - `AUDITORIA`: el esquema usa `auditoria_id`; `fetch_auditoria.php` consulta `A.log_id` → falla.
  - `TICKET_SOPORTE`: el esquema usa `respuesta/fecha_actualizacion`; `resolver_ticket.php` actualiza `respuesta_admin/fecha_resolucion` → falla.
- Fix: alinear columnas (preferir un solo lado; recomiendo cambiar el esquema a los nombres del código o viceversa) y añadir CSRF también a los POST de superadmin.

### P5. Rate-limit de login inefectivo
- Los intentos se guardan en `$_SESSION['login_attempts']` → un atacante sin cookie (o borrándola/rotando IP) nunca acumula intentos; en cambio, un usuario legítimo que falle 5 veces queda bloqueado 15 min (DoS leve). Falta clave por IP y/o por cuenta con tabla en BD.

### P6. Estado inconsistente en `registro-lineas.php`
- La UI por defecto muestra el rol "Admin. Línea" activo, pero el campo oculto ahora dice `value="3"` (chofer): el formulario visible no coincide con lo que se envía (y al enviar como "admin" sin tocar botones se intenta registrar un chofer con campos vacíos). El servidor ya bloquea el rol 4 sin superadmin, pero la página queda confusa/rota y no existe una pantalla donde el superadmin cree admins de línea (el endpoint lo permite vía POST manual).

### P7. Persistencia del XSS almacenado en el feed del chofer
- No se vio saneo de salida en `cobro-chofer.php` (`innerHTML` con `p.pasajero`) ni en otros `innerHTML` con datos del usuario. Sigue pendiente escapar en el cliente (textContent o escape) además del server-side.

---

## 4. Estado de pruebas
- `php -l` (todos los archivos existentes): sin errores de sintaxis ✅.
- PHPUnit `Unit`: 8 tests OK — `Security`: 8 tests OK — pero siguen siendo tests de lógica simulada/helpers, no de endpoints; no detectaron ninguna regresión R1–R3.
- No se ejecutaron suites con BD (sin servidor MySQL en este entorno); además, con R4 los seeds no permiten login y con las columnas desalineadas (P4) las suites de integración realistas fallarían.

---

## 5. Checklist de la próxima iteración (orden recomendado)
1. Arreglar R1 (login) → validar manualmente el flujo completo de login/panel.
2. Arreglar R3 (session_start en los 6 endpoints) → paneles admin/chofer/perfil vuelven a cargar.
3. Arreglar R2 (inyectar CSRF en todos los POST del frontend o ajustar cabecera) → 403 desaparecen; probar cada acción mutadora.
4. Arreglar R4 (hashes de seed reales o despliegue documentado con `setup_superadmin.php`).
5. Terminar P1: QR firmado de extremo a extremo (generación server-side, sin formatos legados, secreto solo por entorno) y validación ACTIVO en `bus_payment`.
6. P2: recarga con confirmación (punto de recarga/pasarela) en vez de auto-acreditación por referencia inventada.
7. P4: alinear columnas NOTIFICACION_GLOBAL/AUDITORIA/TICKET_SOPORTE + CSRF en superadmin.
8. P5: rate-limit por IP/cuenta; P7: saneo de salida en JS; P3: `git rm --cached .env`, limpiar historial, eliminar/aislar `fetch_tickets.php`.
9. Reescribir tests para golpear endpoints reales (login, 403 CSRF, 401 sin sesión, QR firmado/forjado, recarga sin confirmación) y correrlos en CI con una BD de prueba alineada al esquema.
