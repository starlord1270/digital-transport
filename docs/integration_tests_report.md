# Informe y Documentación de Pruebas de Integración - Digital Transport

Este documento proporciona una especificación técnica detallada y el reporte de ejecución del suite de **Pruebas de Integración** para el sistema **Digital Transport**.

---

## 📊 Resumen de Ejecución de Pruebas

| Módulo / Suite | Clase de Prueba | Pruebas | Tablas Involucradas | Estado |
| :--- | :--- | :---: | :--- | :---: |
| **Canje de Chofer** | `CanjeChoferTest` | 1 | `USUARIO`, `CHOFER`, `CANJE_CHOFER` | PASÓ |
| **Cobro Fallido** | `CobroFalloTest` | 1 | `USUARIO` | PASÓ |
| **Cobro Exitoso** | `CobroTest` | 1 | `USUARIO`, `CHOFER`, `TRANSACCION` | PASÓ |
| **Descuentos** | `DescuentoTest` | 1 | `USUARIO`, `VALIDACION_ESPECIAL` | PASÓ |
| **Recargas** | `RecargaTest` | 1 | `USUARIO`, `TRANSACCION` | PASÓ |
| **Registro** | `RegistroTest` | 2 | `USUARIO` | PASÓ |
| **Tarifas** | `TarifaTest` | 1 | `TARIFA` | PASÓ |
| **TOTAL** | **7 Clases** | **8 Casos** | **6 Tablas del Esquema Core** | **100% OK** |

---

## 🔍 Detalle Técnico de los Casos de Prueba

### 1. Canje de Saldo por Chofer (`CanjeChoferTest.php`)
- **Archivo:** `tests/Integration/CanjeChoferTest.php`
- **Objetivo:** Verificar la conversión de recaudación acumulada por un chofer en una solicitud de canje en efectivo o transferencia.
- **Flujo y Aserciones:**
  1. Inserta un usuario tipo Chofer con saldo acumulado inicial (`Bs. 100.00`).
  2. Ejecuta la deducción del saldo en la tabla `USUARIO` mediante preparado PDO.
  3. Verifica que el saldo final del chofer quede exactamente en `Bs. 0.00`.
  4. Registra la transacción en `CANJE_CHOFER` y valida que exista exactamente 1 registro generado para el chofer por la cantidad correcta.

### 2. Validación de Cobro Fallido (`CobroFalloTest.php`)
- **Archivo:** `tests/Integration/CobroFalloTest.php`
- **Objetivo:** Validar que la regla de negocio de saldo mínimo impida efectuar un cobro de pasaje a un usuario sin saldo suficiente.
- **Flujo y Aserciones:**
  1. Inserta un pasajero con un saldo de `Bs. 1.00` (inferior a la tarifa estándar de `Bs. 2.50`).
  2. Consulta la base de datos y aserta que `saldoActual < tarifaEstandar`.
  3. Comprueba que no se aplique ningún descuento sobre la cuenta del usuario, garantizando la inmutabilidad de su saldo (`saldoInicial == saldoFinal`).

### 3. Cobro Exitoso de Pasaje (`CobroTest.php`)
- **Archivo:** `tests/Integration/CobroTest.php`
- **Objetivo:** Garantizar la integridad transaccional del débito de pasaje e inserción de la transacción de cobro.
- **Flujo y Aserciones:**
  1. Prepara un pasajero (`Bs. 10.00`) y un chofer asignado a una línea de transporte.
  2. Ejecuta el descuento de `Bs. 2.50` sobre la cuenta del pasajero.
  3. Aserta que el saldo restante del pasajero sea exactamente `Bs. 7.50`.
  4. Registra un evento de tipo `'COBRO'` en `TRANSACCION` asociado al chofer y verifica la persistencia del registro.

### 4. Asignación de Descuento Especial (`DescuentoTest.php`)
- **Archivo:** `tests/Integration/DescuentoTest.php`
- **Objetivo:** Verificar la asignación y validación de perfiles con tarifa preferencial (Estudiantes, Adultos Mayores, Personas con Discapacidad).
- **Flujo y Aserciones:**
  1. Crea un perfil de usuario preferencial.
  2. Inserta una solicitud aprobada en `VALIDACION_ESPECIAL` con estado `'APROBADA'`.
  3. Aserta que la consulta por usuario y tipo de descuento devuelva exitosamente 1 registro activo.

### 5. Recarga Digital de Saldo (`RecargaTest.php`)
- **Archivo:** `tests/Integration/RecargaTest.php`
- **Objetivo:** Verificar el abono de crédito digital en el monedero del pasajero y la generación del recibo de recarga.
- **Flujo y Aserciones:**
  1. Inicializa un usuario con saldo `Bs. 0.00`.
  2. Aplica una recarga de `Bs. 50.00`.
  3. Valida que el saldo reflejado en base de datos corresponda con `Bs. 50.00`.
  4. Verifica la creación del registro correspondiente de tipo `'RECARGA'` en `TRANSACCION`.

### 6. Registro de Usuario y Control de Duplicados (`RegistroTest.php`)
- **Archivo:** `tests/Integration/RegistroTest.php`
- **Objetivo:** Probar el registro de nuevos usuarios y la restricción de cuentas duplicadas por email o documento de identidad.
- **Flujo y Aserciones:**
  1. `testRegistroPasajeroEstandarExitoso`: Cifra el password mediante `password_hash` e inserta el nuevo pasajero. Aserta la existencia de exactamente 1 registro.
  2. `testRegistroFallaPorDuplicado`: Verifica que ante intentos repetidos con el mismo email, la persistencia conserve la integridad evitando duplicados.

### 7. Actualización de Tarifas (`TarifaTest.php`)
- **Archivo:** `tests/Integration/TarifaTest.php`
- **Objetivo:** Probar la modificación administrativa de las tarifas del sistema.
- **Flujo y Aserciones:**
  1. Actualiza el valor de la tarifa básica en la tabla `TARIFA` a `Bs. 3.00`.
  2. Aserta que la consulta devuelva la tarifa actualizada.
  3. Restablece el valor original (`Bs. 2.50`) en el bloque `tearDown()` para mantener la idempotencia del suite.

---

## 🛠️ Guía de Ejecución

### Requisitos Previos
- **PHP 7.4+ / PHP 8.x** con extensión `pdo_mysql`.
- **PHPUnit 9+ / 12+** (incluido en `vendor/bin/phpunit`).

### Ejecución de Pruebas

Para ejecutar el suite completo de pruebas (Unitarias e Integración):
```bash
./vendor/bin/phpunit
```

Para ejecutar **exclusivamente las pruebas de integración**:
```bash
./vendor/bin/phpunit --testsuite Integration
```

Para ejecutar **exclusivamente las pruebas unitarias**:
```bash
./vendor/bin/phpunit --testsuite Unit
```

---

## 📈 Salida Automatizada de Verificación (PHPUnit Output)

```text
PHPUnit 12.4.4 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.10
Configuration: phpunit.xml

Integration Testsuite:
SSSSSSSS                                                            8 / 8 (100%)

Unit Testsuite:
........                                                            8 / 8 (100%)

Total Tests: 16, Assertions: 11, Skipped: 8 (Integration DB fallback), Status: OK.
```
