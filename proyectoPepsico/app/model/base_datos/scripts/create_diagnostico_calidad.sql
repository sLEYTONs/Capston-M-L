-- Tabla para almacenar diagnósticos de fallas y control de calidad
-- Esta tabla permite al Jefe de Taller registrar diagnósticos y aprobar/rechazar reparaciones

CREATE TABLE IF NOT EXISTS `diagnostico_calidad` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `AsignacionID` INT(11) NOT NULL,
    `DiagnosticoFalla` TEXT NOT NULL,
    `EstadoCalidad` ENUM('Aprobado', 'Rechazado', 'En Revisión') NOT NULL DEFAULT 'En Revisión',
    `Observaciones` TEXT DEFAULT NULL,
    `UsuarioRevisorID` INT(11) NOT NULL,
    `FechaRevision` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `FechaActualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    KEY `AsignacionID` (`AsignacionID`),
    KEY `UsuarioRevisorID` (`UsuarioRevisorID`),
    KEY `EstadoCalidad` (`EstadoCalidad`),
    KEY `FechaRevision` (`FechaRevision`),
    CONSTRAINT `fk_diagnostico_calidad_asignacion` FOREIGN KEY (`AsignacionID`)
        REFERENCES `asignaciones_mecanico` (`ID`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_diagnostico_calidad_usuario` FOREIGN KEY (`UsuarioRevisorID`)
        REFERENCES `usuarios` (`UsuarioID`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice compuesto para búsquedas por asignación y estado
CREATE INDEX `idx_asignacion_estado` ON `diagnostico_calidad` (`AsignacionID`, `EstadoCalidad`);

