-- Script para crear la tabla repuestos_asignacion
-- Esta tabla relaciona las asignaciones de mantenimiento con los repuestos utilizados

-- Primero, verificar y crear la tabla repuestos si no existe
-- (Ajusta los campos según tu estructura actual de la tabla repuestos)
CREATE TABLE IF NOT EXISTS `repuestos` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `Codigo` VARCHAR(50) NOT NULL,
    `Nombre` VARCHAR(255) NOT NULL,
    `Categoria` VARCHAR(100) DEFAULT NULL,
    `Stock` INT(11) DEFAULT 0,
    `Precio` DECIMAL(10,2) DEFAULT 0.00,
    `StockMinimo` INT(11) DEFAULT 5,
    `Descripcion` TEXT DEFAULT NULL,
    `Estado` VARCHAR(20) DEFAULT 'Activo',
    `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `FechaActualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `Codigo` (`Codigo`),
    KEY `Estado` (`Estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear la tabla repuestos_asignacion
CREATE TABLE IF NOT EXISTS `repuestos_asignacion` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `AsignacionID` INT(11) NOT NULL,
    `RepuestoID` INT(11) NOT NULL,
    `Cantidad` INT(11) NOT NULL DEFAULT 1,
    `PrecioUnitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `Total` DECIMAL(10,2) GENERATED ALWAYS AS (Cantidad * PrecioUnitario) STORED,
    `FechaRegistro` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `Observaciones` TEXT DEFAULT NULL,
    PRIMARY KEY (`ID`),
    KEY `AsignacionID` (`AsignacionID`),
    KEY `RepuestoID` (`RepuestoID`),
    CONSTRAINT `fk_repuestos_asignacion_asignacion` FOREIGN KEY (`AsignacionID`) 
        REFERENCES `asignaciones_mecanico` (`ID`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_repuestos_asignacion_repuesto` FOREIGN KEY (`RepuestoID`) 
        REFERENCES `repuestos` (`ID`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices adicionales para mejorar el rendimiento
-- Nota: IF NOT EXISTS no está soportado en todas las versiones de MariaDB/MySQL
-- Si los índices ya existen, estos comandos pueden generar un warning pero no un error
CREATE INDEX `idx_repuestos_asignacion_fecha` ON `repuestos_asignacion` (`FechaRegistro`);
CREATE INDEX `idx_repuestos_asignacion_asignacion` ON `repuestos_asignacion` (`AsignacionID`, `RepuestoID`);

