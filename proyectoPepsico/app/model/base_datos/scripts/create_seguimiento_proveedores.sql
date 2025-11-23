-- Script para crear la tabla de seguimiento de proveedores
-- Permite al Asistente de Repuestos gestionar solicitudes a proveedores

CREATE TABLE IF NOT EXISTS `seguimiento_proveedores` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `SolicitudRepuestoID` INT(11) NOT NULL COMMENT 'ID de la solicitud de repuesto del mecánico',
    `ProveedorID` INT(11) DEFAULT NULL COMMENT 'ID del proveedor (si existe tabla proveedores)',
    `ProveedorNombre` VARCHAR(255) DEFAULT NULL COMMENT 'Nombre del proveedor',
    `ProveedorContacto` VARCHAR(255) DEFAULT NULL COMMENT 'Contacto del proveedor',
    `NumeroOrden` VARCHAR(100) DEFAULT NULL COMMENT 'Número de orden de compra',
    `FechaSolicitudProveedor` DATETIME DEFAULT NULL COMMENT 'Fecha en que se solicitó al proveedor',
    `EstadoPago` ENUM('Pendiente', 'Pagado', 'Cancelado') DEFAULT 'Pendiente',
    `FechaPago` DATETIME DEFAULT NULL,
    `MontoPago` DECIMAL(10,2) DEFAULT NULL,
    `EstadoEnvio` ENUM('No Enviado', 'En Tránsito', 'En Aduana', 'Recibido') DEFAULT 'No Enviado',
    `NumeroGuia` VARCHAR(100) DEFAULT NULL COMMENT 'Número de guía de envío',
    `Transportista` VARCHAR(255) DEFAULT NULL,
    `FechaEnvio` DATETIME DEFAULT NULL,
    `FechaEstimadaLlegada` DATETIME DEFAULT NULL,
    `FechaRealLlegada` DATETIME DEFAULT NULL,
    `EstadoRecepcion` ENUM('Pendiente', 'Recibido', 'Rechazado') DEFAULT 'Pendiente',
    `Observaciones` TEXT DEFAULT NULL,
    `GestionadoPor` INT(11) DEFAULT NULL COMMENT 'ID del asistente que gestiona',
    `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `FechaActualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    KEY `SolicitudRepuestoID` (`SolicitudRepuestoID`),
    KEY `ProveedorID` (`ProveedorID`),
    KEY `GestionadoPor` (`GestionadoPor`),
    KEY `EstadoPago` (`EstadoPago`),
    KEY `EstadoEnvio` (`EstadoEnvio`),
    KEY `EstadoRecepcion` (`EstadoRecepcion`),
    CONSTRAINT `fk_seguimiento_solicitud_repuesto` FOREIGN KEY (`SolicitudRepuestoID`) 
        REFERENCES `solicitudes_repuestos` (`ID`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_seguimiento_gestionado_por` FOREIGN KEY (`GestionadoPor`) 
        REFERENCES `usuarios` (`UsuarioID`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actualizar la tabla solicitudes_repuestos para agregar nuevos estados
ALTER TABLE `solicitudes_repuestos` 
MODIFY COLUMN `Estado` ENUM('Pendiente', 'En Proceso', 'En Tránsito', 'Recibido', 'Entregada', 'Rechazada', 'Cancelada') NOT NULL DEFAULT 'Pendiente' 
COMMENT 'Pendiente: Mecánico solicitó, En Proceso: Asistente gestiona con proveedor, En Tránsito: Proveedor envió, Recibido: Llegó al taller, Entregada: Se entregó al mecánico';

-- Agregar campos adicionales a solicitudes_repuestos para seguimiento
-- Nota: Si las columnas ya existen, estos comandos pueden generar errores que puedes ignorar
ALTER TABLE `solicitudes_repuestos` 
ADD COLUMN `FechaEnProceso` DATETIME DEFAULT NULL COMMENT 'Fecha en que el asistente comenzó a gestionar' AFTER `FechaAprobacion`;

ALTER TABLE `solicitudes_repuestos` 
ADD COLUMN `FechaEnTransito` DATETIME DEFAULT NULL COMMENT 'Fecha en que el proveedor envió' AFTER `FechaEnProceso`;

ALTER TABLE `solicitudes_repuestos` 
ADD COLUMN `FechaRecibido` DATETIME DEFAULT NULL COMMENT 'Fecha en que llegó al taller' AFTER `FechaEnTransito`;

ALTER TABLE `solicitudes_repuestos` 
ADD COLUMN `GestionadoPor` INT(11) DEFAULT NULL COMMENT 'ID del asistente que gestiona' AFTER `AprobadoPor`;

-- Agregar foreign key para GestionadoPor
ALTER TABLE `solicitudes_repuestos` 
ADD CONSTRAINT `fk_solicitudes_gestionado_por` FOREIGN KEY (`GestionadoPor`) 
REFERENCES `usuarios` (`UsuarioID`) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

