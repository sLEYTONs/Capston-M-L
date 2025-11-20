-- Script simplificado para crear SOLO la tabla repuestos_asignacion
-- Asume que la tabla repuestos ya existe en tu base de datos

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

