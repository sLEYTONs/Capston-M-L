-- Script para crear la tabla de solicitudes de repuestos
-- Esta tabla permite a los mecánicos solicitar repuestos cuando una tarea está en pausa

CREATE TABLE IF NOT EXISTS `solicitudes_repuestos` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `AsignacionID` INT(11) NOT NULL COMMENT 'ID de la asignación de mecánico que requiere el repuesto',
    `MecanicoID` INT(11) NOT NULL COMMENT 'ID del mecánico que solicita',
    `RepuestoID` INT(11) NOT NULL COMMENT 'ID del repuesto solicitado',
    `Cantidad` INT(11) NOT NULL DEFAULT 1,
    `Urgencia` ENUM('Baja', 'Media', 'Alta') NOT NULL DEFAULT 'Media',
    `Motivo` TEXT DEFAULT NULL COMMENT 'Motivo de la solicitud',
    `Estado` ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Entregada', 'Cancelada') NOT NULL DEFAULT 'Pendiente',
    `FechaSolicitud` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `FechaAprobacion` DATETIME DEFAULT NULL,
    `FechaEntrega` DATETIME DEFAULT NULL,
    `AprobadoPor` INT(11) DEFAULT NULL COMMENT 'ID del usuario que aprobó (Administrador/Jefe de Taller)',
    `Observaciones` TEXT DEFAULT NULL,
    PRIMARY KEY (`ID`),
    KEY `AsignacionID` (`AsignacionID`),
    KEY `MecanicoID` (`MecanicoID`),
    KEY `RepuestoID` (`RepuestoID`),
    KEY `Estado` (`Estado`),
    KEY `FechaSolicitud` (`FechaSolicitud`),
    CONSTRAINT `fk_solicitudes_repuestos_asignacion` FOREIGN KEY (`AsignacionID`) 
        REFERENCES `asignaciones_mecanico` (`ID`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_solicitudes_repuestos_mecanico` FOREIGN KEY (`MecanicoID`) 
        REFERENCES `usuarios` (`UsuarioID`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_solicitudes_repuestos_repuesto` FOREIGN KEY (`RepuestoID`) 
        REFERENCES `repuestos` (`ID`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_solicitudes_repuestos_aprobador` FOREIGN KEY (`AprobadoPor`) 
        REFERENCES `usuarios` (`UsuarioID`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columna MotivoPausa a asignaciones_mecanico si no existe
-- Nota: Ejecutar manualmente si la columna ya existe para evitar errores
-- ALTER TABLE `asignaciones_mecanico` 
-- ADD COLUMN `MotivoPausa` TEXT DEFAULT NULL COMMENT 'Motivo por el cual la tarea está en pausa' AFTER `Observaciones`;

-- Verificar si la columna existe antes de agregarla (ejecutar manualmente)
-- SELECT COUNT(*) FROM information_schema.COLUMNS 
-- WHERE TABLE_SCHEMA = 'Pepsico' 
-- AND TABLE_NAME = 'asignaciones_mecanico' 
-- AND COLUMN_NAME = 'MotivoPausa';

-- Si el resultado es 0, ejecutar:
-- ALTER TABLE `asignaciones_mecanico` 
-- ADD COLUMN `MotivoPausa` TEXT DEFAULT NULL COMMENT 'Motivo por el cual la tarea está en pausa' AFTER `Observaciones`;

-- Índice para mejorar búsquedas de tareas en pausa (ejecutar solo si no existe)
-- CREATE INDEX `idx_asignaciones_estado_pausa` ON `asignaciones_mecanico` (`Estado`, `MotivoPausa`);

