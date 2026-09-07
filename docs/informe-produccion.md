# Informe de Auditoría — Digital Transport 🚍

**Fecha:** — · **Alcance:** análisis estático completo del repositorio (backend PHP, frontend, SQL, tests, configuración, docs).
**Resultado global: NO está listo para producción.** Es un prototipo/demo funcional con buena base (PDO, hashing, sesiones, dashboard por rol) pero con vulnerabilidades críticas, código roto por una migración a medias y esquema de BD desincronizado con el código.

---

## 1. Resumen ejecutivo

| Área | Estado |
|---|---|
| Sintaxis PHP | ✅ Sin errores (`php -l` en todo el árbol) |
| Pruebas unitarias | ✅ Pasan (8/8) — pero **no prueban el código real** |
| Migración mysqli → PDO | ❌ A medias: 4 endpoints aún usan API mysqli contra un objeto PDO → **fallan con error fatal** |
| Esquema SQL (`database_reset.sql`) | ❌ Desincronizado: faltan tablas/columnas/enums que el código usa |
| Modelo de dinero | ❌ **Se puede crear saldo gratis** (recarga sin pasarela de pago) |
| Control de acceso | ❌ **Cualquiera puede auto-registrarse como Admin de Línea** |
| Protección QR/pagos | ❌ Sin firma criptográfica, sin anti-replay, confía en input del cliente |
| CSRF | ❌ Helpers existen pero **ningún endpoint los valida** |
| Higiene del repo | ❌ `.env`, scripts de depuración y subidas comprometidas versionados en git |
| Docs de tests | ⚠️ Reportan "100% éxito" pero describen capacidades que el código no tiene (HMAC, CSRF, RBAC por ID) |

**Conclusión:** si esto se despliega tal cual, un atacante anónimo puede: crearse una cuenta de administrador de línea, acreditarse saldo ilimitado, leer recaudación de cualquier chofer/línea, ejecutar XSS persistente contra choferes y (según configuración del servidor) potencialmente subir un PHP y tomar control del servidor. Además, varias pantallas "oficiales" están rotas (perfil/cambio de contraseña del admin, notificaciones, auditoría, soporte, superadmin completo).

---

## 2. Arquitectura del sistema

- **Backend:** PHP 7.4+/8 (procedural + JSON), conexión PDO centralizada en `backend/includes/db.php`, `.env` en raíz cargado manualmente.
- **Roles esperados por el código:** 1=PASAJERO, 2=PUNTO_RECARGA_ADMIN, 3=CHOFER, 4=ADMIN_LINEA, 5=SUPER_ADMIN (el esquema SQL solo define del 1 al 4).
- **Frontend:** PHP + JS nativo + Leaflet + QR (`api.qrserver.com`, `html5-qrcode` desde CDN).
- **Flujos principales:** registro/login por rol → pasajero recarga saldo → pago por QR (pasajero escanea QR del bus) → chofer acumula cobros → solicita canje/liquidación → admin de línea aprueba → superadmin (global) gestiona líneas/tarifas/usuarios/auditoría.
- **Tests:** PHPUnit (`composer require-dev phpunit ^12.4`), suites Unit/Integration/System/Acceptance/Performance/Load/Stress/Security.

---

## 3. Hallazgos por severidad

### 🔴 CRÍTICOS

#### C1. Registro público como Administrador de Línea (privilegio total por registro)
- `frontend/inicio-sesion-lineas-choferes/registro-lineas.php` es accesible sin autenticación y permite elegir rol **ADMIN_LINEA (ID 4)** o CHOFER (3). El `tipo_usuario_id` viaja como campo oculto del formulario (default `4`).
- `backend/validacion-registro.php` (líneas 61–64) inserta en `ADMIN_LINEA` con la `linea_id` que envíe el cliente, sin token de invitación, sin verificación ni aprobación.
- **Impacto:** un atacante anónimo elige la línea 1 (o cualquiera) y obtiene un panel administrativo completo: ver choferes y emails, cambiar estados, aprobar canjes, aprobar validaciones de tarifa. Es el fin del modelo de confianza. (En la demo servía para que el jurado se registrara rápido; en producción es inaceptable.)

#### C2. Recarga de saldo sin pago real (dinero gratis)
- `frontend/recarga-digital.php` (líneas 41–78): cualquier usuario autenticado hace `POST amount=999999` y el servidor **acredita el saldo directamente** (`UPDATE USUARIO SET saldo`). Los campos de tarjeta/QR del formulario son decorativos: no se envían ni validan en el servidor, no existe pasarela, ni `U_RECARGA`/`PUNTO_RECARGA` se usan.
- **Impacto:** monetización inexistente; cualquier usuario puede viajar gratis y un atacante puede llenar de saldo cuentas, inflar recaudaciones de choferes cómplices y retirar saldo vía canjes.

#### C3. Firma/validación de pagos QR inexistente
- `backend/procesar_cobro.php`:
  - Modo pasajero-escanea-bus (líneas 25–28): confía en `chofer_id` enviado por el cliente. Cualquier usuario logueado puede cargar dinero a la recaudación de **cualquier** `chofer_id` (fraude colusivo para inflar liquidaciones) o pagar múltiples veces sin límite/anti-replay.
  - Modo chofer-escanea-pasajero (líneas 36–41): el QR del pasajero es solo `USER_<id>` / `DT-USER-<id>`, **sin firma HMAC, sin expiración, sin nonce** (la doc afirma "Firmas HMAC-SHA256", pero no existe ningún HMAC en el código). Un chofer malicioso puede fabricar un QR de cualquier `usuario_id` y debitar el pasaje de la víctima, o iterar IDs y drenar saldos.
- **Impacto:** fraude directo sobre el saldo de terceros y falsificación de recaudación. Requiere rediseño del esquema QR (token firmado por sesión de viaje, expiración corta, un solo uso, vinculado a la unidad física).

#### C4. Subida de archivos sin validación (riesgo RCE)
- `backend/validacion-registro.php` (líneas 85–101): el comprobante se guarda como `time() . "_" . nombre_original_del_cliente` en `uploads/documentos/` **sin validar extensión ni MIME**, y no hay `.htaccess` en `uploads/` que desactive la ejecución de PHP.
- **Impacto:** si Apache ejecuta PHP en esa ruta, subir `x.php` = ejecución remota de código. Como mínimo, permite alojar malware/HTML en el dominio y estafar (phishing).

### 🟠 ALTOS

#### A1. Migración mysqli→PDO incompleta → endpoints de Admin rotos (500)
`backend/bd.php` expone `$conn = $pdo` (objeto PDO), pero estos archivos siguen usando API mysqli (`bind_param`, `get_result`, `$conn->error`, `$conn->close()`):
- `backend/fetch_perfil_admin.php`
- `backend/update-perfil-admin.php`
- `backend/change-password-admin.php` — además consulta la columna `password` que no existe (la tabla usa `password_hash`) → nunca funcionará ni siquiera en mysqli.
- `backend/fix_password.php` — idem + es un **backdoor sin autenticación** que resetea la contraseña de `pasajero@gmail.com`.
**Impacto:** la página de perfil del admin de línea (incluido "cambiar contraseña") está rota en producción. `fix_password.php` debe **eliminarse** del repositorio y del servidor.

#### A2. IDOR / escalado horizontal entre líneas
Los endpoints de admin de línea aceptan `linea_id` por GET sin comprobar que pertenezca a la sesión (`$_SESSION['linea_id']`):
- `backend/fetch_dashboard_data.php` (línea 14), `backend/fetch_choferes_data.php` (línea 15), `backend/fetch_reportes_flujo_caja.php`.
**Impacto:** el admin de la línea A consulta choferes, emails, licencias, placas y recaudación/finanzas de la línea B cambiando `?linea_id=B`.

#### A3. IDOR en notificaciones de pagos y datos de pasajeros
- `backend/check_new_payments.php`: toma `chofer_id` por GET con cualquier rol de sesión → enumera pagos recientes (nombre del pasajero, monto, hora) de **cualquier** chofer del sistema. Es la API que el panel del chofer consulta cada 3 s.

#### A4. XSS almacenado en el feed del chofer
- El `nombre_completo` se acepta sin saneo al registrar/perfilar. `frontend/choferes/cobro-chofer.php` (línea 130) inserta `p.pasajero` con `innerHTML` sin escapar → un pasajero llamado `<img src=x onerror=alert(document.cookie)>` ejecuta script en el panel del chofer al pagar (robo de sesión si la cookie no es HttpOnly — ver M3).

#### A5. Superadmin inexistente de fábrica
- El código espera `tipo_usuario_id=5` (login, dashboards, todos los endpoints `superadmin/`), pero `database_reset.sql` **no define el rol 5 ni crea ninguna cuenta superadmin** (solo siembra admin de línea 1 y 2 pasajeros). No hay UI ni script para crearlo.
**Impacto:** tras un despliegue limpio, todo el "Master Panel" (usuarios, líneas, tarifas, finanzas, auditoría, soporte, notificaciones) es inaccesible/inutilizable.

#### A6. Esquema SQL desincronizado (roturas masivas con BD recién creada)
Faltan en `database_reset.sql` objetos que el código usa en producción:
| Objeto requerido por el código | Estado en `database_reset.sql` |
|---|---|
| `CHOFER.estado_servicio` con `PENDIENTE`/`RECHAZADO` | Solo enum `ACTIVO/INACTIVO/LICENCIA` (líneas 124–128) → el registro de chofer (`'PENDIENTE'`) **falla o guarda vacío** |
| `CHOFER.rating` | No existe (se añadió a mano con `scratch/add_rating.php`) |
| `CANJE_CHOFER.estado` | No existe (parche manual `scratch/update_canje_table.php`) → canjes rotos |
| `VALIDACION_ESPECIAL.comprobante_url` | No existe → registro de estudiante/3.ª edad **falla** |
| `PUNTO_RECARGA.estado` | No existe (se usa en `toggle_punto_status.php`, `fetch_puntos_recarga.php`) |
| `NOTIFICACION_GLOBAL` | No existe; el único SQL del repo crea `NOTIFICACION` con otra estructura (¡nombre distinto!) |
| `AUDITORIA` | No existe (la usan `registrarAuditoria()` y `fetch_auditoria.php`) |
| `TICKET_SOPORTE` | No existe (soporte superadmin roto) |
| Rol `SUPER_ADMIN` (5) + seed | No existe |
| `USUARIO.telefono`? | No existe, pero `fetch_perfil_admin` devuelve teléfono estático "decorativo" |

**Impacto:** levantar producción con `php backend/setup_database.php` produce una BD donde media aplicación falla. Los parches viven sueltos en `scratch/*.php` y jamás se consolidaron.

### 🟡 MEDIOS

- **M1. CSRF no aplicado.** `backend/includes/security.php` define `getCsrfToken/verifyCsrfToken` pero **ningún endpoint mutador los valida** (cambio de contraseñas, perfiles, canjes, estados, tarifas, notificaciones, resolución de tickets…). `SameSite=Lax` mitiga parcialmente, pero no sustituye la validación de token.
- **M2. Sin límite de intentos ni bloqueo en login** (`backend/validacion-login.php`): fuerza bruta directa contra cuentas de chofer/admin/superadmin.
- **M3. Hardening de sesión inefectivo por orden de ejecución.** Las páginas llaman `session_start()` al inicio y **después** incluyen `header.php` → `security.php` (ej. `cobro-chofer.php` líneas 9–11 vs 29), así que `session_set_cookie_params(httponly, samesite…)` (security.php líneas 29–38) **nunca se aplica** cuando la sesión ya empezó; queda a merced del `php.ini` (httponly suele estar en 0 → robo de sesión con el XSS de A4).
- **M4. Errores y credenciales filtrados al cliente.** `display_errors` y mensajes crudos de PDO (`$e->getMessage()`) llegan en casi todos los endpoints pese a `APP_DEBUG`. `backend/fetch_tickets.php` además **hardcodea credenciales** `root/''` y activa `display_errors`, y está accesible directamente por URL.
- **M5. `.env` y secretos versionados.** `.env` (credenciales, aunque sean locales) está commiteado; **no hay `.gitignore`**; también están en git `uploads/documentos/*` (documentos personales de usuarios) y todos los `scratch/*.php`, `backend/fix_password.php`.
- **M6. Dependencias externas en tiempo de ejecución.** QR por `api.qrserver.com`, lector `html5-qrcode` desde `unpkg.com`, audio y fuentes desde CDNs: la app entera depende de internet y de terceros (riesgo de supply-chain y caída en redes cerradas). CSP con `'unsafe-inline' 'unsafe-eval'` no mitiga.
- **M7. Contraseñas débiles.** Mínimo 6 caracteres en admin; los seed del SQL usan `admin123`/`123456`; `validacion-registro.php` no exige complejidad ni política; sin expiración ni reutilización.
- **M8. Redirecciones rotas de la página de login legacy.** `frontend/inicio-sesion-lineas-choferes/login.php` usa los `redirect` relativos devueltos por el backend (p. ej. `choferes/cobro-chofer.php`) que solo son válidos desde `frontend/`, no desde esa subcarpeta → 404.
- **M9. Saldo de sesión desactualizado.** `$_SESSION['saldo']` se fija en login y solo se refresca al entrar a recarga; tras pagar, la UI muestra saldo viejo.
- **M10. Falta de registro de auditoría en acciones de admin de línea** (canjes, cambios de estado, validaciones) — solo las acciones de superadmin llaman a `registrarAuditoria()` (y esa tabla ni existe en el SQL base).

### 🟢 BAJOS / CALIDAD
- Duplicados muertos: `test/` duplica `tests/Unit/SeguridadTest.php` e `tests/Integration/RegistroTest.php` y no está en el `phpunit.xml`; `frontend/registro-chofer.php` vs `registro-usuarios.php`; `fetch_tickets.php` sin página que lo consuma.
- `phpunit.xml` declara suites que requieren MySQL (`DB_TEST_NAME=digital_transport_test`) que el repo no crea ni documenta; los "reportes" en `docs/*` describen resultados que hoy no son reproducibles con el SQL versionado.
- Los **tests de seguridad no tocan los endpoints**: `OwaspBrokenAccessControlTest.php` valida matrices estáticas en el propio test, `OwaspCsrfXssSecurityTest.php` prueba los helpers aislados, no que un endpoint real rechace una petición sin token. Por eso pasan aunque las vulnerabilidades C1–A3 existan.
- Accesibilidad/UX menor: mensajes "Seguro"/"100% seguro"/"encriptación bancaria" en la UI no se corresponden con la implementación.

---

## 4. Lo que sí está bien (para mantener)
- Conexión PDO con `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES=false`, `utf8mb4`.
- Contraseñas con `password_hash`/`password_verify` (BCRYPT) y regeneración de ID de sesión en login.
- `FOR UPDATE` + transacciones en cobro y recarga (evita doble gasto concurrente) — aunque el modelo de dinero es falso (C2).
- Validación de rol básica por sesión en la mayoría de endpoints (aunque incompleta, A2/A3) y verificación de pertenencia de canjes a la línea del admin (`procesar_canje.php`).
- `.htaccess` con `Options -Indexes`, bloqueo de `.env/.git/composer/sql`, redirección HTTPS y cabeceras básicas (a nivel raíz).
- Separación de helpers de seguridad, funciones y conexión; códigos de estado HTTP razonables en algunos fetch.
- `database_reset.sql` versionado y scripts de despliegue SSL/BD presentes (aunque el SQL está desactualizado).

---

## 5. Checklist para llegar a producción (resumen de acciones)

1. **Cerrar el modelo de dinero:** pasarela/simulación real de pago + acreditación solo tras confirmación del proveedor; registrar en `TRANSACCION`/`U_RECARGA` con `punto_id`.
2. **Cerrar el registro:** público solo para pasajeros; choferes con aprobación de admin; **admins de línea y superadmin creados exclusivamente por superadmin** (endpoint protegido) o seed CLI.
3. **Firmar los QR** de pasajero y de bus (HMAC o JWT con expiración, nonce de un solo uso, ligado al viaje/unidad) y verificar servidor-lado del rol y del `estado_servicio='ACTIVO'` del chofer.
4. **Terminar la migración mysqli→PDO** en los 4 archivos; **eliminar** `fix_password.php`, `scratch/`, `fetch_tickets.php` o sanitizarlo.
5. **Consolidar el esquema** en un único `database_reset.sql` + migraciones versionadas (todas las tablas/columnas/enums/rol 5 + seed superadmin opcional).
6. **Autorización estricta:** ignorar `linea_id`/ids del cliente y derivarlos de la sesión; verificar pertenencia en todos los fetch; limitar `check_new_payments` al chofer autenticado.
7. **Sanear salida** (escapar todo lo que entra a `innerHTML`) y **validar subidas** (extensión blanca, MIME real, nombre generado, `.htaccess` en `uploads/`, fuera del docroot o con ejecución deshabilitada).
8. **Aplicar CSRF real** (token por sesión validado en todo POST), **rate-limiting** de login, y **hardening de sesión** antes de `session_start()` o vía `php.ini`.
9. **No versionar** `.env`, `uploads/`, `scratch/`; añadir `.gitignore`; centralizar credenciales en variables de entorno del servidor.
10. **Reescribir los tests** para que golpeen los endpoints reales (al menos: registro-rol, recarga, cobro, canje, IDOR, CSRF, subida) y que el CI falle al reintroducir estas regresiones; consolidar la documentación con lo que el código hace de verdad.

---

## 6. Nota metodológica
Esta auditoría es **estática** (lectura de código + lint + suite unitaria). No se ejecutaron las suites que requieren MySQL porque este entorno no tiene servidor de base de datos; además, los reportes de `docs/` (acceptance/load/stress/…) no son reproducibles con el SQL versionado por la desincronización del esquema (sección A6). Se recomienda una auditoría dinámica (OWASP ZAP/Burp) después de aplicar esta fase de corrección y con una BD de prueba consolidada.
