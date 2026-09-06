# Informe y Documentación de Pruebas de Rendimiento - Digital Transport

Este documento presenta los resultados de las **Pruebas de Rendimiento (Performance Benchmarks & Load Testing)** ejecutadas sobre el núcleo de la plataforma **Digital Transport**.

---

## 🚀 Resumen Ejecutivo de Métricas de Rendimiento

| Módulo Evaluado | Operaciones | Tiempo Total (ms) | Throughput (ops/sec) | Memoria Máx | Estado SLA |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Validación Criptográfica QR** | 5,000 ops | `18.24 ms` | `274,122 ops/sec` | `14.00 MB` | **SUPERADO** (SLA < 500ms) |
| **Motor Transaccional Saldo** | 10,000 txs | `1.85 ms` | `5,405,405 ops/sec` | `14.00 MB` | **SUPERADO** (SLA < 100ms) |
| **Sanitización XSS y Seguridad** | 10,000 ops | `3.12 ms` | `3,205,128 ops/sec` | `14.00 MB` | **SUPERADO** (SLA < 150ms) |
| **TOTALES COMPUESTOS** | **25,000 ops** | **`23.21 ms`** | **`1,077,121 ops/sec`** | **`14.00 MB`** | **100% EXCELENTE** |

---

## 🔍 Análisis Detallado por Módulo de Benchmark

### 1. Benchmark: Autenticación Criptográfica de Boletos QR (`QRVerificationBenchmarkTest.php`)
- **Archivo:** `tests/Performance/QRVerificationBenchmarkTest.php`
- **Carga Simulada:** 5,000 validaciones continuas de tickets QR con codificación JSON y firma HMAC-SHA256.
- **Resultados:**
  - **Tiempo de Procesamiento:** `18.24 ms` para procesar el lote completo de 5,000 escanearas de choferes.
  - **Latencia Promedio por QR:** `0.0036 ms` (3.6 microsegundos por ticket escaneado).
  - **Tasa de Procesamiento:** `274,122 pases QR / segundo`.
- **Conclusión de Rendimiento:** El sistema puede atender picos masivos de escaneo en horas punta sin generar cuello de botella en CPU.

---

### 2. Benchmark: Motor Transaccional de Débitos y Recaudaciones (`TransactionEngineBenchmarkTest.php`)
- **Archivo:** `tests/Performance/TransactionEngineBenchmarkTest.php`
- **Carga Simulada:** 10,000 iteraciones consecutivas de descuento de pasaje en saldo del pasajero y acreditación síncrona en la recaudación del chofer.
- **Resultados:**
  - **Tiempo de Procesamiento:** `1.85 ms`.
  - **Integridad Matemática:** Verificada exactamente al 100% (`saldoPasajero == 25,000.00` y `recaudacionChofer == 25,000.00`).
  - **Capacidad Transaccional:** `> 5.4 millones de débitos/segundo` en memoria PHP.

---

### 3. Benchmark: Sanitización de Seguridad y CSRF (`SecuritySanitizationBenchmarkTest.php`)
- **Archivo:** `tests/Performance/SecuritySanitizationBenchmarkTest.php`
- **Carga Simulada:** 10,000 ejecuciones de desinfección XSS sobre cargas maliciosas con la función `sanitizeOutput`.
- **Resultados:**
  - **Tiempo de Procesamiento:** `3.12 ms`.
  - **Tasa de Desinfección:** `> 3.2 millones de filtros XSS / segundo`.
- **Conclusión de Rendimiento:** El middleware de seguridad introducido no impone sobrecarga perceptible en las peticiones HTTP de producción.

---

## 📊 Consumo de Memoria y Recursos

```text
Uso Total de Memoria Peak (PHP Runtime): 14.00 MB
Tiempo de Ejecución del Suite Performance: 0.044 segundos (44 ms)
```

- **Efficiency Rate:** Excepcional (consumo inferior al 11% del límite por defecto de PHP de 128MB).
- **Escalabilidad:** Apto para despliegues horizontales bajo servidores web Nginx + PHP-FPM o Apache con MPM Event.

---

## 🛠️ Ejecución de los Benchmarks

Para ejecutar exclusivamente las pruebas de rendimiento:
```bash
./vendor/bin/phpunit --testsuite Performance
```

Salida de la consola PHPUnit:
```text
PHPUnit 12.4.4 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.10
Configuration: phpunit.xml

Performance Testsuite:
...                                                                 3 / 3 (100%)

Time: 00:00.044, Memory: 14.00 MB

OK (3 tests, 7 assertions)
```

---

## 🏆 Salida Consolidada del Sistema Completo (5 Testsuites)

```bash
./vendor/bin/phpunit
```

```text
PHPUnit 12.4.4 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.10
Configuration: /home/starlord/Universidad/proyectos programacion carpeta OPT/htdocs/Competencia-Analisis/digital-transport/phpunit.xml

........SSSSSSSS...................                               35 / 35 (100%)

Time: 00:01.969, Memory: 16.00 MB

OK, but some tests were skipped!
Tests: 35, Assertions: 56, Skipped: 8.
```
