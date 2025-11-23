-- Script para crear las tablas de solicitudes de agendamiento y agenda del taller
-- Nueva lógica: El chofer envía una solicitud, el supervisor verifica disponibilidad y aprueba/rechaza

-- Tabla para gestionar la agenda del taller (horas disponibles)
CREATE TABLE IF NOT EXISTS `agenda_taller` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `Fecha` DATE NOT NULL,
    `HoraInicio` TIME NOT NULL,
    `HoraFin` TIME NOT NULL,
    `Disponible` TINYINT(1) NOT NULL DEFAULT 1,
    `Observaciones` TEXT DEFAULT NULL,
    `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `FechaActualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    KEY `Fecha` (`Fecha`),
    KEY `Disponible` (`Disponible`),
    KEY `Fecha_Hora` (`Fecha`, `HoraInicio`, `HoraFin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para solicitudes de agendamiento del chofer
CREATE TABLE IF NOT EXISTS `solicitudes_agendamiento` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `ChoferID` INT(11) NOT NULL,
    `Placa` VARCHAR(20) NOT NULL,
    `TipoVehiculo` VARCHAR(50) NOT NULL,
    `Marca` VARCHAR(100) NOT NULL,
    `Modelo` VARCHAR(100) NOT NULL,
    `Anio` INT(4) DEFAULT NULL,
    `ConductorNombre` VARCHAR(255) NOT NULL,
    `ConductorCedula` VARCHAR(20) NOT NULL,
    `ConductorTelefono` VARCHAR(20) DEFAULT NULL,
    `Licencia` VARCHAR(50) DEFAULT NULL,
    `EmpresaCodigo` VARCHAR(50) NOT NULL,
    `EmpresaNombre` VARCHAR(255) NOT NULL,
    `Proposito` VARCHAR(255) NOT NULL,
    `Area` VARCHAR(100) DEFAULT NULL,
    `PersonaContacto` VARCHAR(255) DEFAULT NULL,
    `Observaciones` TEXT DEFAULT NULL,
    `Estado` ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Cancelada') NOT NULL DEFAULT 'Pendiente',
    `AgendaID` INT(11) DEFAULT NULL COMMENT 'ID de la hora asignada en agenda_taller',
    `SupervisorID` INT(11) DEFAULT NULL COMMENT 'ID del supervisor que aprobó/rechazó',
    `MotivoRechazo` TEXT DEFAULT NULL,
    `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `FechaActualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    KEY `ChoferID` (`ChoferID`),
    KEY `Estado` (`Estado`),
    KEY `AgendaID` (`AgendaID`),
    KEY `SupervisorID` (`SupervisorID`),
    CONSTRAINT `fk_solicitudes_chofer` FOREIGN KEY (`ChoferID`) 
        REFERENCES `usuarios` (`UsuarioID`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_solicitudes_agenda` FOREIGN KEY (`AgendaID`) 
        REFERENCES `agenda_taller` (`ID`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_solicitudes_supervisor` FOREIGN KEY (`SupervisorID`) 
        REFERENCES `usuarios` (`UsuarioID`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice compuesto para búsquedas por estado y fecha de creación
CREATE INDEX `idx_estado_fecha` ON `solicitudes_agendamiento` (`Estado`, `FechaCreacion`);

