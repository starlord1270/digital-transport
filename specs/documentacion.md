# Documentación Técnica - Digital Transport

## 1. Descripción General
**Digital Transport** es una plataforma integral diseñada para la modernización del transporte público. Permite la gestión de pasajeros, conductores, líneas de transporte y puntos de recarga, eliminando la necesidad de efectivo mediante el uso de boletos digitales y tarjetas inteligentes (NFC/QR).

## 2. Arquitectura del Sistema
El sistema sigue una arquitectura monolítica modular basada en:
- **Backend**: PHP (Lógica de negocio y API).
- **Frontend**: PHP/HTML5 con CSS3 (Diseño responsivo) y JavaScript (Interactividad).
- **Base de Datos**: MySQL/MariaDB (Modelo relacional).
- **Seguridad**: Hashing de contraseñas con `bcrypt`, sesiones seguras y validación de transacciones.

## 3. Modelo de Datos
La base de datos se compone de varias entidades clave interconectadas:

### Usuarios y Roles
- **TIPO_USUARIO**: Define los roles del sistema (PASAJERO, CHOFER, ADMIN_LINEA, PUNTO_RECARGA_ADMIN).
- **USUARIO**: Información centralizada de todos los usuarios registrados.

### Gestión de Transporte
- **LINEA**: Empresas o líneas de transporte público.
- **VEHICULO**: Unidades de transporte vinculadas a una línea.
- **RUTA**: Trayectos definidos para cada línea.
- **CHOFER**: Conductores asignados a vehículos y líneas específicas.

### Sistema de Pagos y Boletos
- **TARJETA**: Gestión de tarjetas físicas (NFC) o digitales asociadas a un usuario.
- **TRANSACCION**: Historial completo de cobros, recargas y canjes.
- **TARIFA**: Precios configurables según el tipo de pasajero (Estándar, Estudiante, etc.).
- **TIPO_DESCUENTO**: Definición de porcentajes de descuento aplicables.

### Recargas y Puntos de Venta
- **PUNTO_RECARGA**: Ubicaciones físicas autorizadas para recargar saldo.
- **U_RECARGA**: Registro detallado de cada operación de recarga.

## 4. Funcionalidades Principales

### Para Pasajeros
- **Pase Digital**: Generación dinámica de códigos QR para el pago en vehículos.
- **Recarga en Línea/Física**: Gestión de saldo mediante puntos autorizados o pasarelas digitales.
- **Historial de Viajes**: Consulta detallada de trayectos y gastos realizados.
- **Perfiles Especiales**: Validación de documentos para tarifas de estudiante o adulto mayor.

### Para Conductores
- **Cobro Digital**: Interfaz para validar el pase digital de los pasajeros.
- **Recaudación**: Seguimiento en tiempo real de los cobros realizados durante el turno.
- **Estado de Servicio**: Gestión de disponibilidad (Activo/Inactivo).

### Para Administradores de Línea
- **Reportes de Operación**: Estadísticas de recaudación y flujo de pasajeros.
- **Gestión de Flota**: Control de vehículos y conductores asignados.

## 5. Próximas Mejoras (Roadmap)
- [ ] Refactorización a una interfaz **Premium** con Glassmorphism.
- [ ] Implementación de un mapa interactivo avanzado con Leaflet.js para seguimiento de rutas.
- [ ] Optimización de la base de datos con procedimientos almacenados para transacciones financieras.
- [ ] Integración de notificaciones push para alertas de saldo bajo.
