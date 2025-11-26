-- Script para crear la tabla de proveedores
-- Permite gestionar información de proveedores con validaciones de formato chileno

CREATE TABLE IF NOT EXISTS `proveedores` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `Nombre` VARCHAR(255) NOT NULL COMMENT 'Nombre del proveedor',
    `Contacto` VARCHAR(255) NOT NULL COMMENT 'Nombre de la persona de contacto',
    `Email` VARCHAR(255) NOT NULL COMMENT 'Correo electrónico del proveedor',
    `Telefono` VARCHAR(20) NOT NULL COMMENT 'Teléfono de contacto (formato chileno)',
    `RUT` VARCHAR(20) DEFAULT NULL COMMENT 'RUT del proveedor (formato chileno)',
    `Direccion` TEXT DEFAULT NULL COMMENT 'Dirección del proveedor',
    `Estado` ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `FechaActualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `Email` (`Email`),
    KEY `Estado` (`Estado`),
    KEY `Nombre` (`Nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

