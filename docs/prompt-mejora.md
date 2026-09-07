# Prompt de mejora — Digital Transport 🚍 · VERSIÓN 4 (cierre final)

> El sistema ya pasó por 3 rondas (riesgos críticos corregidos, regresiones arregladas, CSRF/QR/sesiones/esquema en orden). Quedan 2 bloqueantes concretos y mejoras de endurecimiento, detallados en `docs/reauditoria-ronda4.md`.

---

```text
Eres un ingeniero senior PHP/seguridad. Proyecto "Digital Transport" (PHP 8
procedural + PDO + MySQL + JS vanilla, sin framework). Ya están resueltos:
registro de admins solo por superadmin, QR de pago HMAC sin fallback, CSRF de
extremo a extremo (meta + wrapper fetch en header.php), sesiones desde db.php,
seeds con hash válidos, esquema consolidado, rate-limit por IP+email, consola
de recargas superadmin/operador, crear admin de línea funcional con UI,
perfil-admin dentro del parche CSRF, contraseña mín. 8, .env fuera de git,
docker-compose con web+db. Quedan 2 bloqueantes y 3 endurecimientos; resuélvelos
en orden y verifica cada uno:

1) [CRÍTICO] ELIMINA el bypass de recarga en frontend/recarga-digital.php:
   las líneas ~40-79 aún acreditan saldo directo (UPDATE USUARIO SET saldo +
   INSERT TRANSACCION + commit) cuando la petición es POST con amount>0, sin
   pasarela, referencia ni confirmación. Cualquier usuario logueado puede
   hacer POST a frontend/recarga-digital.php y obtener saldo gratis, anulando
   el flujo seguro nuevo (procesar_recarga -> confirmar_recarga). Borra ese
   bloque por completo (o déjalo estrictamente tras if (getenv('APP_ENV')===
   'local') y fuera del flujo de producción). Verifica con curl que un POST a
   recarga-digital.php NO modifique el saldo y que solo queden 3 puntos de
   escritura de saldo: procesar_cobro (débito), confirmar_recarga
   (acreditación de operador/superadmin), y el modo local de procesar_recarga.

2) [ALTO] CORRIGE confirmar_recarga.php: usa registrarAuditoria() (líneas ~79
   y ~110) pero no incluye backend/includes/functions.php (solo db.php y
   security.php), así que al confirmar/rechazar lanza "Call to undefined
   function registrarAuditoria()" (Error, no Exception) y la transacción se
   revierte (500). Añade require_once __DIR__.'/includes/functions.php' (o
   carga los helpers desde db.php) y prueba confirmar y rechazar una recarga
   reales.

3) [AUTORIZACIÓN] confirmar_recarga.php: para operadores rol 2 valida que la
   solicitud pertenezca a SU punto de recarga (compara punto_id del registro
   con el punto del operador en sesión) antes de acreditar; rechaza si no
   coincide (403). Mantén superadmin sin esa restricción.

4) [DESPLIEGUE/CLAVE QR] docker-compose (servicio web) no define QR_SECRET ni
   CSRF_SECRET: en un clon limpio los endpoints de QR firman/validan con 500
   ("clave no definida"), y si hay .env local el Dockerfile lo copia a la
   imagen con el secreto de desarrollo conocido. Define QR_SECRET y
   CSRF_SECRET fuertes en el entorno del compose (o exige .env) y añade un
   .dockerignore que excluya .env, uploads/, tests/, docs/, .git/ y vendor/ de
   la imagen. Verifica que con la clave ausente el sistema falle cerrado y con
   clave presente el QR firmado se valide.

5) [MENORES] 
   - procesar_cobro.php: sustituye el catch que devuelve $e->getMessage() por
     mensaje genérico + error_log.
   - frontend/recarga-digital.php: quita los inputs de tarjeta decorativos
     (sin name) y el QR bancario estático si no hay pasarela real; simplifica
     a "referencia de comprobante". Revisa la máscara de roles [1,2,5,6] (el 6
     no existe; el esquema usa 1-5).
   - Convierte tests/Security/SmokeHttpTest.php en pruebas HTTP reales contra
     el stack (login por rol 200, POST mutador sin token 403 y con token 200,
     recarga PENDIENTE + doble confirmación = una sola acreditación, QR
     forjado/vencido rechazado, operador de punto A no confirma punto B).

ENTREGABLES: lista de archivos tocados con antes/después por punto, evidencia
de las verificaciones (curl / suite), php -l sin errores y resultado de la
suite. Prueba manual final del flujo completo: registro pasajero -> solicitud
de recarga -> confirmación por superadmin (consola) -> QR firmado -> cobro ->
feed del chofer -> canje -> aprobación admin de línea -> superadmin crea admin
de línea -> auditoría.
```

---

Usa junto con `docs/reauditoria-ronda4.md`. Pide verificación por cada punto 1–5 antes de dar el trabajo por cerrado.
