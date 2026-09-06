-- =====================================================
-- SCRIPT DE REINICIO COMPLETO - Digital Transport
-- Elimina y recrea la base de datos desde cero
-- =====================================================

-- Eliminar la base de datos si existe
DROP DATABASE IF EXISTS `digital-transport`;

-- Crear la base de datos
CREATE DATABASE `digital-transport` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `digital-transport`;

-- =====================================================
-- TABLAS PRINCIPALES
-- =====================================================

-- TIPO_USUARIO
CREATE TABLE `TIPO_USUARIO` (
    `tipo_usuario_id` int(11) NOT NULL AUTO_INCREMENT,
    `descripcion` varchar(50) NOT NULL,
    PRIMARY KEY (`tipo_usuario_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `TIPO_USUARIO`
VALUES (1, 'PASAJERO'),
    (2, 'PUNTO_RECARGA_ADMIN'),
    (3, 'CHOFER'),
    (4, 'ADMIN_LINEA');

-- USUARIO
CREATE TABLE `USUARIO` (
    `usuario_id` int(11) NOT NULL AUTO_INCREMENT,
    `tipo_usuario_id` int(11) NOT NULL,
    `documento_identidad` varchar(20) NOT NULL,
    `nombre_completo` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `saldo` decimal(10, 2) DEFAULT 0.00,
    `fecha_registro` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`usuario_id`),
    UNIQUE KEY `documento_identidad` (`documento_identidad`),
    UNIQUE KEY `email` (`email`),
    KEY `tipo_usuario_id` (`tipo_usuario_id`),
    CONSTRAINT `USUARIO_ibfk_1` FOREIGN KEY (`tipo_usuario_id`) REFERENCES `TIPO_USUARIO` (`tipo_usuario_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- LINEA
CREATE TABLE `LINEA` (
    `linea_id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    PRIMARY KEY (`linea_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `LINEA`
VALUES (
        1,
        'Línea Central (Administración Maestra)'
    ),
    (2, 'Línea 1 - Ruta Norte'),
    (103, 'Línea 103'),
    (106, 'Línea 106'),
    (108, 'Línea 108'),
    (110, 'Línea 110'),
    (115, 'Línea 115'),
    (123, 'Línea 123'),
    (130, 'Línea 130'),
    (209, 'Línea 209'),
    (224, 'Línea 224'),
    (240, 'Línea 240'),
    (244, 'Línea 244'),
    (260, 'Línea 260'),
    (270, 'Línea 270'),
    (290, 'Línea 290');

-- VEHICULO
CREATE TABLE `VEHICULO` (
    `placa` varchar(10) NOT NULL,
    `modelo` varchar(50) DEFAULT NULL,
    `capacidad` int(11) DEFAULT NULL,
    `linea_id` int(11) NOT NULL,
    PRIMARY KEY (`placa`),
    KEY `linea_id` (`linea_id`),
    CONSTRAINT `VEHICULO_ibfk_1` FOREIGN KEY (`linea_id`) REFERENCES `LINEA` (`linea_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `VEHICULO`
VALUES (
        'ABC-1234',
        'Bus Grande Modelo A',
        45,
        1
    ),
    (
        'PENDIENTE',
        'Sin asignar',
        0,
        1
    );

-- ADMIN_LINEA
CREATE TABLE `ADMIN_LINEA` (
    `adm_linea_id` int(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` int(11) NOT NULL,
    `linea_id` int(11) NOT NULL,
    `cargo` varchar(50) DEFAULT NULL,
    PRIMARY KEY (`adm_linea_id`),
    UNIQUE KEY `usuario_id` (`usuario_id`),
    KEY `ADMIN_LINEA_ibfk_2` (`linea_id`),
    CONSTRAINT `ADMIN_LINEA_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `USUARIO` (`usuario_id`),
    CONSTRAINT `ADMIN_LINEA_ibfk_2` FOREIGN KEY (`linea_id`) REFERENCES `LINEA` (`linea_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- CHOFER
CREATE TABLE `CHOFER` (
    `chofer_id` int(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` int(11) NOT NULL,
    `licencia` varchar(50) DEFAULT NULL,
    `linea_id` int(11) NOT NULL,
    `vehiculo_placa` varchar(10) DEFAULT 'PENDIENTE',
    `estado_servicio` enum(
        'ACTIVO',
        'INACTIVO',
        'LICENCIA'
    ) DEFAULT 'INACTIVO',
    PRIMARY KEY (`chofer_id`),
    UNIQUE KEY `usuario_id` (`usuario_id`),
    KEY `linea_id` (`linea_id`),
    KEY `vehiculo_placa` (`vehiculo_placa`),
    CONSTRAINT `CHOFER_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `USUARIO` (`usuario_id`),
    CONSTRAINT `CHOFER_ibfk_2` FOREIGN KEY (`linea_id`) REFERENCES `LINEA` (`linea_id`),
    CONSTRAINT `CHOFER_ibfk_3` FOREIGN KEY (`vehiculo_placa`) REFERENCES `VEHICULO` (`placa`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- PUNTO_RECARGA
CREATE TABLE `PUNTO_RECARGA` (
    `punto_id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `ubicacion` varchar(255) DEFAULT NULL,
    `usuario_id` int(11) NOT NULL,
    PRIMARY KEY (`punto_id`),
    UNIQUE KEY `usuario_id` (`usuario_id`),
    CONSTRAINT `PUNTO_RECARGA_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `USUARIO` (`usuario_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- TARJETA
CREATE TABLE `TARJETA` (
    `tarjeta_id` int(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` int(11) NOT NULL,
    `saldo_actual` decimal(10, 2) DEFAULT 0.00,
    `codigo_seguridad` varchar(10) NOT NULL,
    `estado` enum(
        'Activo',
        'Inactivo',
        'Usado',
        'Vencido',
        'BLOQUEADA',
        'PERDIDA'
    ) DEFAULT 'Activo',
    `codigo_nfc` varchar(50) DEFAULT NULL,
    `fecha_emision` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`tarjeta_id`),
    UNIQUE KEY `codigo_seguridad` (`codigo_seguridad`),
    KEY `usuario_id` (`usuario_id`),
    CONSTRAINT `TARJETA_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `USUARIO` (`usuario_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- TRANSACCION (ACTUALIZADA)
CREATE TABLE `TRANSACCION` (
    `transaccion_id` int(11) NOT NULL AUTO_INCREMENT,
    `tipo` enum('COBRO', 'RECARGA', 'CANJE') NOT NULL,
    `monto` decimal(10, 2) NOT NULL,
    `tarjeta_id` int(11) DEFAULT NULL,
    `usuario_id` int(11) DEFAULT NULL COMMENT 'Para recargas directas a cuenta',
    `chofer_id_cobro` int(11) DEFAULT NULL,
    `punto_id_recarga` int(11) DEFAULT NULL,
    `fecha_hora` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`transaccion_id`),
    KEY `tarjeta_id` (`tarjeta_id`),
    KEY `chofer_id_cobro` (`chofer_id_cobro`),
    KEY `punto_id_recarga` (`punto_id_recarga`),
    KEY `idx_transaccion_usuario` (`usuario_id`),
    CONSTRAINT `TRANSACCION_ibfk_1` FOREIGN KEY (`tarjeta_id`) REFERENCES `TARJETA` (`tarjeta_id`),
    CONSTRAINT `TRANSACCION_ibfk_2` FOREIGN KEY (`chofer_id_cobro`) REFERENCES `CHOFER` (`chofer_id`),
    CONSTRAINT `TRANSACCION_ibfk_3` FOREIGN KEY (`punto_id_recarga`) REFERENCES `PUNTO_RECARGA` (`punto_id`),
    CONSTRAINT `fk_transaccion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `USUARIO` (`usuario_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- TIPO_DESCUENTO
CREATE TABLE `TIPO_DESCUENTO` (
    `tipo_desc_id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(50) NOT NULL,
    `porcentaje` decimal(5, 2) NOT NULL,
    PRIMARY KEY (`tipo_desc_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `TIPO_DESCUENTO`
VALUES (1, 'Tarifa Estándar', 0.00),
    (
        2,
        'Estudiante/3ra Edad',
        60.00
    );

-- TARIFA
CREATE TABLE `TARIFA` (
    `tarifa_id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `monto` decimal(10, 2) NOT NULL,
    `tipo_desc_id` int(11) DEFAULT NULL,
    `fecha_vigencia` date DEFAULT NULL,
    PRIMARY KEY (`tarifa_id`),
    KEY `tipo_desc_id` (`tipo_desc_id`),
    CONSTRAINT `TARIFA_ibfk_1` FOREIGN KEY (`tipo_desc_id`) REFERENCES `TIPO_DESCUENTO` (`tipo_desc_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `TARIFA`
VALUES (
        1,
        'Adulto Estándar',
        2.50,
        1,
        '2099-12-31'
    ),
    (
        2,
        'Estudiante',
        1.00,
        2,
        '2099-12-31'
    );

-- RUTA
CREATE TABLE `RUTA` (
    `ruta_id` int(11) NOT NULL AUTO_INCREMENT,
    `linea_id` int(11) NOT NULL,
    `nombre_ruta` varchar(100) NOT NULL,
    `descripcion` text DEFAULT NULL,
    PRIMARY KEY (`ruta_id`),
    KEY `linea_id` (`linea_id`),
    CONSTRAINT `RUTA_ibfk_1` FOREIGN KEY (`linea_id`) REFERENCES `LINEA` (`linea_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- TIPO_RECARGA
CREATE TABLE `TIPO_RECARGA` (
    `tipo_recarga_id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(50) NOT NULL,
    PRIMARY KEY (`tipo_recarga_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- U_RECARGA
CREATE TABLE `U_RECARGA` (
    `u_recarga_id` int(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` int(11) NOT NULL,
    `punto_id` int(11) NOT NULL,
    `tipo_recarga_id` int(11) NOT NULL,
    `monto` decimal(10, 2) NOT NULL,
    `fecha_recarga` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`u_recarga_id`),
    KEY `punto_id` (`punto_id`),
    KEY `usuario_id` (`usuario_id`),
    KEY `tipo_recarga_id` (`tipo_recarga_id`),
    CONSTRAINT `U_RECARGA_ibfk_1` FOREIGN KEY (`punto_id`) REFERENCES `PUNTO_RECARGA` (`punto_id`),
    CONSTRAINT `U_RECARGA_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `USUARIO` (`usuario_id`),
    CONSTRAINT `U_RECARGA_ibfk_3` FOREIGN KEY (`tipo_recarga_id`) REFERENCES `TIPO_RECARGA` (`tipo_recarga_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- VALIDACION_ESPECIAL
CREATE TABLE `VALIDACION_ESPECIAL` (
    `validacion_id` int(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` int(11) NOT NULL,
    `tipo_desc_id` int(11) NOT NULL,
    `estado_validacion` enum(
        'PENDIENTE',
        'APROBADA',
        'RECHAZADA'
    ) DEFAULT 'PENDIENTE',
    `fecha_solicitud` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`validacion_id`),
    KEY `usuario_id` (`usuario_id`),
    KEY `tipo_desc_id` (`tipo_desc_id`),
    CONSTRAINT `VALIDACION_ESPECIAL_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `USUARIO` (`usuario_id`),
    CONSTRAINT `VALIDACION_ESPECIAL_ibfk_2` FOREIGN KEY (`tipo_desc_id`) REFERENCES `TIPO_DESCUENTO` (`tipo_desc_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- CANJE_CHOFER
CREATE TABLE `CANJE_CHOFER` (
    `canje_id` int(11) NOT NULL AUTO_INCREMENT,
    `chofer_id` int(11) NOT NULL,
    `monto` decimal(10, 2) NOT NULL,
    `fecha_canje` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`canje_id`),
    KEY `chofer_id` (`chofer_id`),
    CONSTRAINT `CANJE_CHOFER_ibfk_1` FOREIGN KEY (`chofer_id`) REFERENCES `CHOFER` (`chofer_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- =====================================================
-- DATOS DE PRUEBA
-- =====================================================

-- Usuario admin maestro (password: admin123)
INSERT INTO
    `USUARIO`
VALUES (
        1,
        4,
        '100000000',
        'Admin Maestro Global',
        'admin@transporte.com',
        '$2y$10$meCIypmQFr22SpiYtS4CS.YUNwAi0.Rppcu..W1AvNCqtknh7PJgm',
        0.00,
        NOW()
    );

-- Usuario pasajero de prueba (password: 123456)
INSERT INTO
    `USUARIO`
VALUES (
        2,
        1,
        '546763',
        'Pasajero de Prueba',
        'pasajero@gmail.com',
        '$2y$10$bjSA42gEWIMcI3UDAVtToO6jzCUTXg72Sg7lk1pWNw/xE1v3VsTZW',
        0.00,
        NOW()
    );

-- Usuario estudiante (password: 123456)
INSERT INTO
    `USUARIO`
VALUES (
        3,
        1,
        '1234567876543',
        'Estudiante de Prueba',
        'estudiante@gmail.com',
        '$2y$10$ETSymsQKUbUXiSVEB7OOEeElhMK1lkDsqR8wRThSVM/vKFaTJU5u6',
        0.00,
        NOW()
    );

-- Admin de línea
INSERT INTO `ADMIN_LINEA` VALUES ( 1, 1, 1, 'Administrador Global' );

COMMIT;