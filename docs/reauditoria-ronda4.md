# Re-Auditoría Ronda 4 — Digital Transport 🚍 (estado actual)

**Veredicto:** la ronda 3 dejó el sistema casi completo (CSRF integral, QR firmado, seeds válidos, sesiones, superadmin con CSRF + UI, `.env` fuera de git, docker-compose con servicio web, políticas de contraseña unificadas, sin fugas de error casi en ningún lado). **Quedan 2 bloqueantes reales:** (1) un **código viejo de auto-acreditación quedó olvidado** en `frontend/recarga-digital.php` y permite "recargar" saldo sin pago ni confirmación, anulando el flujo seguro nuevo; y (2) `confirmar_recarga.php` **revienta con error fatal** por usar `registrarAuditoria()` sin incluir `functions.php`, así que la consola de recargas no puede confirmar/rechazar nada. Además quedan 2 mejoras de autorización/despliegue.

> **✅ ACTUALIZACIÓN (aplicado):** ambos bloqueantes quedaron corregidos — se eliminó el bloque de acreditación directa de `frontend/recarga-digital.php` (el archivo solo muestra el formulario y delega en `procesar_recarga.php`) y se añadió `require_once includes/functions.php` en `backend/confirmar_recarga.php` antes de usar `registrarAuditoria()`. `php -l` sin errores y Unit 8/8 OK. Los únicos puntos de escritura de saldo que quedan son los 3 legítimos (`confirmar_recarga`, `procesar_cobro` débito, modo local de `procesar_recarga`).
>
> **✅ ACTUALIZACIÓN 2 (M-1/M-2/M-3 aplicados):** `confirmar_recarga.php` ahora valida que un operador rol 2 confirme solo solicitudes de SU punto (403 en otro caso); `fetch_recargas_pendientes.php` ya no hace fallback al punto 1 (403 si el operador no tiene punto asignado); el catch de `procesar_cobro.php` separa errores PDO (genéricos + log) de excepciones de negocio; la UI de `recarga-digital.php` quedó simplificada (sin inputs de tarjeta decorativos ni QR estático, solo referencia de pago) y sin JS huérfano; `docker-compose.yml` expone `CSRF_SECRET`/`QR_SECRET` vía entorno (vacíos ⇒ fail-closed en QR) y se añadió `.dockerignore` (excluye `.env`, secretos locales, uploads de usuarios, vendor, tests, docs). Verificado: `php -l` y Unit 8/8 OK.

---

## 1. ✅ Verificado como corregido en esta ronda
- `.env` eliminado del índice de git (deletion staged) y sigue en `.gitignore`.
- `docker-compose.yml` ahora incluye servicio **web** (php-apache) con `DB_HOST=db`, credenciales acordes y arranque tras el healthcheck de MySQL.
- Recarga rediseñada: `U_RECARGA` con `estado (PENDIENTE/CONFIRMADA/RECHAZADA)` y `referencia`; seeds de `PUNTO_RECARGA (id=1)` y `TIPO_RECARGA (1..2)`; `procesar_recarga.php` crea solicitudes y solo auto-confirma con `APP_ENV=local`; consola `frontend/dashboard-superadmin/recargas.php` + `fetch_recargas_pendientes.php` (superadmin ve todas, operador rol 2 solo su punto).
- `confirmar_recarga.php`: bloquea auto-confirmación del operador sobre sí mismo (salvo superadmin), es **idempotente** (CONFIRMADA/RECHAZADA no se re-procesan), acredita+actualiza+registra transacción y auditoría en una transacción.
- `backend/superadmin/crear_admin_linea.php` corregido (lee token CSRF, incluye `functions.php`, mín. 8 caracteres) y con UI `frontend/dashboard-superadmin/crear-admin-linea.php`.
- `frontend/dashboard-admin-linea/perfil-admin.php` migrado al layout con `header.php` → queda dentro del parche CSRF.
- Política de contraseña mínima 8 en registro y en los 3 `change-password-*`.
- Fugas de `$e->getMessage()` eliminadas de casi todos los endpoints (queda `procesar_cobro.php` y el bloque muerto de `recarga-digital.php`, ver abajo).
- Lint completo sin errores · PHPUnit Unit 8/8 OK.
- `tests/Security/SmokeHttpTest.php` añadido (aunque sigue siendo lógica de helpers, no HTTP real).

---

## 2. 🔴 Bloqueantes pendientes

### B-1 (CRÍTICO). `frontend/recarga-digital.php` conserva el bloque antiguo que acredita saldo sin pago
- Líneas 40–79: si la petición es `POST` (con `amount` > 0), el propio archivo **acredita el saldo directamente** (`UPDATE USUARIO SET saldo`), registra `TRANSACCION` y hace commit, **sin** pasarela, referencia, confirmación ni límite superior.
- El formulario HTML apunta a `backend/procesar_recarga.php`, pero eso no protege: un atacante (o un pasajero cualquiera) puede hacer **POST directo a `frontend/recarga-digital.php?amount=99999`** (o con FormData) con su sesión iniciada y obtener saldo al instante. Es el mismo agujero de "dinero gratis" de la auditoría 1, aún vivo.
- **Fix:** eliminar el bloque de las líneas 40–79 (o dejarlo SOLO si `APP_ENV=local`, con guarda explícita y sin duplicar el flujo seguro). Debe quedar un único camino de recarga: `procesar_recarga.php` (solicitud) → `confirmar_recarga.php` (acreditación).

### B-2 (ALTO). `backend/confirmar_recarga.php` falla con error fatal (función sin incluir)
- Usa `registrarAuditoria()` (líneas 79 y 110) pero **solo incluye `includes/db.php` y `includes/security.php`**; `registrarAuditoria()` vive en `includes/functions.php`, que no está cargado (verificado: `function_exists` → false).
- Consecuencia: al confirmar o rechazar una recarga se lanza `Call to undefined function registrarAuditoria()` (un `Error`, no un `Exception`), no se llega al `commit` y la operación termina en 500 con rollback.
- **Fix:** añadir `require_once __DIR__ . '/includes/functions.php';` (o centralizar la carga de helpers en `db.php`).

---

## 3. 🟠 Mejoras recomendadas (no bloqueantes)

### M-1. Autorización cruzada en confirmación de recargas
- `fetch_recargas_pendientes.php` filtra por el punto del operador (rol 2), pero `confirmar_recarga.php` **no valida que la solicitud pertenezca al punto del operador**: un operador del punto A puede confirmar (adivinando/recorriendo `recarga_id`) solicitudes del punto B. Añadir en `confirmar_recarga.php` para rol 2 la verificación `recData.punto_id === punto del operador` (como hace `update_chofer_status.php` con la línea).

### M-2. Secretos y QR en despliegue Docker
- El servicio `web` del compose **no define `QR_SECRET` ni `CSRF_SECRET`**. En un clon limpio (sin `.env`), `generar_qr_pago.php` y `procesar_cobro.php` responden 500 ("clave no definida"); si existe `.env` local, el `Dockerfile` (`COPY .`) lo hornea en la imagen con el secreto de desarrollo conocido → QR forjable.
- Añadir `QR_SECRET`/`CSRF_SECRET` (largos y aleatorios) vía entorno del compose y un `.dockerignore` que excluya `.env`, `uploads/`, `tests/`, `.git`, etc.

### M-3. Menores
- `procesar_cobro.php` (catch) aún expone `$e->getMessage()` al cliente → mensaje genérico + `error_log`.
- `recarga-digital.php` mantiene campos de tarjeta decorativos (sin `name`) y el QR bancario estático `recarga.jpeg`: si no hay pasarela real, simplificar la UI a "referencia de pago" para no sugerir procesamiento de tarjeta.
- `tests/Security/SmokeHttpTest.php` valida helpers/hashes pero no hace llamadas HTTP reales; ideal: arrancar el stack y verificar login → 200, POST sin token → 403, confirmar dos veces la misma recarga → una sola acreditación.
- `frontend/recarga-digital.php` permite roles `[1,2,5,6]` (el 6 no existe en el esquema; solo 1–5). Revisar máscara de roles.

---

## 4. Verificación ejecutada en esta ronda
- `git diff`/lectura de todos los archivos tocados; `php -l` en todo el árbol (sin errores); PHPUnit Unit 8/8.
- Rastreo de todos los puntos de acreditación de saldo: solo deben quedar `confirmar_recarga.php` (operador) y el modo local de `procesar_recarga.php`; **`frontend/recarga-digital.php` (B-1) es el único acreditador sin control**.
- Rastreo de usos de `registrarAuditoria()`: único archivo que lo usa sin incluir `functions.php` es `confirmar_recarga.php` (B-2).
