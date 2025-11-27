-- =====================================================
-- Script ALTERNATIVO: Crear tablas SIN Foreign Keys
-- Usa este script si las foreign keys siguen fallando
-- Las tablas funcionarán igual, solo sin integridad referencial
-- =====================================================

-- 1. Crear tabla asignaciones_vehiculos (SIN foreign keys)
CREATE TABLE IF NOT EXISTS `asignaciones_vehiculos` (
    `ID` INT AUTO_INCREMENT PRIMARY KEY,
    `VehiculoID` INT NOT NULL,
    `UsuarioID` INT NOT NULL,
    `TipoOperacion` ENUM('Recepcion', 'Devolucion') DEFAULT 'Recepcion',
    `FechaAsignacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `FechaDevolucion` DATETIME NULL,
    `KilometrajeInicial` DECIMAL(10,2) DEFAULT 0,
    `KilometrajeFinal` DECIMAL(10,2) NULL,
    `ObservacionesAsignacion` TEXT NULL,
    `EstadoVehiculo` VARCHAR(50) DEFAULT 'Bueno',
    `FotosRecepcion` TEXT NULL,
    `FotosDevolucion` TEXT NULL,
    INDEX `idx_vehiculo` (`VehiculoID`),
    INDEX `idx_usuario` (`UsuarioID`),
    INDEX `idx_fecha_asignacion` (`FechaAsignacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Crear tabla reportes_fallas_vehiculos (SIN foreign keys)
CREATE TABLE IF NOT EXISTS `reportes_fallas_vehiculos` (
    `ID` INT AUTO_INCREMENT PRIMARY KEY,
    `VehiculoID` INT NOT NULL,
    `UsuarioID` INT NOT NULL,
    `TipoFalla` VARCHAR(100) NOT NULL,
    `Descripcion` TEXT NOT NULL,
    `Prioridad` ENUM('Baja', 'Media', 'Alta', 'Urgente') DEFAULT 'Media',
    `Kilometraje` DECIMAL(10,2) DEFAULT 0,
    `Fotos` TEXT NULL,
    `Estado` ENUM('Pendiente', 'En Revisión', 'En Reparación', 'Resuelto', 'Cancelado') DEFAULT 'Pendiente',
    `FechaReporte` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `FechaResolucion` DATETIME NULL,
    `RespuestaTaller` TEXT NULL,
    INDEX `idx_vehiculo` (`VehiculoID`),
    INDEX `idx_usuario` (`UsuarioID`),
    INDEX `idx_estado` (`Estado`),
    INDEX `idx_fecha_reporte` (`FechaReporte`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTA: Estas tablas funcionarán correctamente sin foreign keys
-- La aplicación PHP manejará la integridad referencial
-- =====================================================

