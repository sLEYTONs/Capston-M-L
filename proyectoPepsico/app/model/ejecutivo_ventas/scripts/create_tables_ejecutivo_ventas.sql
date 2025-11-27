-- =====================================================
-- Script para crear tablas de Ejecutivo de Ventas
-- Ejecutar en DBeaver o MySQL Workbench
-- =====================================================

-- PRIMERO: Verificar estructura de tablas existentes
-- Ejecuta estas queries para verificar antes de crear las foreign keys:

-- Verificar estructura de ingreso_vehiculos
-- SELECT COLUMN_NAME, DATA_TYPE, COLUMN_KEY, IS_NULLABLE 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'pepsico' 
--   AND TABLE_NAME = 'ingreso_vehiculos' 
--   AND COLUMN_NAME IN ('ID');

-- Verificar estructura de usuarios
-- SELECT COLUMN_NAME, DATA_TYPE, COLUMN_KEY, IS_NULLABLE 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'pepsico' 
--   AND TABLE_NAME = 'usuarios' 
--   AND COLUMN_NAME IN ('UsuarioID');

-- Verificar motor de almacenamiento
-- SELECT TABLE_NAME, ENGINE 
-- FROM INFORMATION_SCHEMA.TABLES 
-- WHERE TABLE_SCHEMA = 'pepsico' 
--   AND TABLE_NAME IN ('ingreso_vehiculos', 'usuarios');

-- =====================================================
-- 1. Crear tabla asignaciones_vehiculos (SIN foreign keys)
-- =====================================================
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

-- =====================================================
-- 2. Crear tabla reportes_fallas_vehiculos (SIN foreign keys)
-- =====================================================
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
-- 3. Agregar Foreign Keys para asignaciones_vehiculos
-- EJECUTAR SOLO SI LAS TABLAS EXISTEN Y LOS TIPOS COINCIDEN
-- =====================================================

-- IMPORTANTE: Si las foreign keys fallan, ejecuta primero el script verificar_ingreso_vehiculos.sql
-- para diagnosticar el problema.

-- Foreign Key: UsuarioID -> usuarios(UsuarioID)
-- Esta debería funcionar si la anterior funcionó
ALTER TABLE `asignaciones_vehiculos` 
ADD CONSTRAINT `fk_asignaciones_usuario` 
FOREIGN KEY (`UsuarioID`) 
REFERENCES `usuarios` (`UsuarioID`) 
ON DELETE RESTRICT 
ON UPDATE CASCADE;

-- Foreign Key: VehiculoID -> ingreso_vehiculos(ID)
-- NOTA: Si esta falla, probablemente ingreso_vehiculos.ID no es PRIMARY KEY o usa MyISAM
-- Soluciones:
-- 1. Verificar: SELECT COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='ingreso_vehiculos' AND COLUMN_NAME='ID';
-- 2. Si no es PRI: ALTER TABLE ingreso_vehiculos ADD PRIMARY KEY (ID);
-- 3. Si usa MyISAM: ALTER TABLE ingreso_vehiculos ENGINE=InnoDB;
-- 4. Si todo falla, omite esta foreign key (la aplicación funcionará igual)

-- COMENTADA TEMPORALMENTE - Descomenta después de verificar la estructura:
-- ALTER TABLE `asignaciones_vehiculos` 
-- ADD CONSTRAINT `fk_asignaciones_vehiculo` 
-- FOREIGN KEY (`VehiculoID`) 
-- REFERENCES `ingreso_vehiculos` (`ID`) 
-- ON DELETE RESTRICT 
-- ON UPDATE CASCADE;

-- =====================================================
-- 4. Agregar Foreign Keys para reportes_fallas_vehiculos
-- EJECUTAR SOLO SI LAS TABLAS EXISTEN Y LOS TIPOS COINCIDEN
-- =====================================================

-- Foreign Key: UsuarioID -> usuarios(UsuarioID)
-- Esta debería funcionar
ALTER TABLE `reportes_fallas_vehiculos` 
ADD CONSTRAINT `fk_reportes_fallas_usuario` 
FOREIGN KEY (`UsuarioID`) 
REFERENCES `usuarios` (`UsuarioID`) 
ON DELETE RESTRICT 
ON UPDATE CASCADE;

-- Foreign Key: VehiculoID -> ingreso_vehiculos(ID)
-- NOTA: Si esta falla, probablemente ingreso_vehiculos.ID no es PRIMARY KEY o usa MyISAM
-- Ver soluciones en la sección 3 arriba

-- COMENTADA TEMPORALMENTE - Descomenta después de verificar la estructura:
-- ALTER TABLE `reportes_fallas_vehiculos` 
-- ADD CONSTRAINT `fk_reportes_fallas_vehiculo` 
-- FOREIGN KEY (`VehiculoID`) 
-- REFERENCES `ingreso_vehiculos` (`ID`) 
-- ON DELETE RESTRICT 
-- ON UPDATE CASCADE;

-- =====================================================
-- ALTERNATIVA: Si las foreign keys fallan, crear sin ellas
-- Las tablas funcionarán igual, solo sin integridad referencial
-- =====================================================

-- Si necesitas eliminar las foreign keys existentes:
-- ALTER TABLE `asignaciones_vehiculos` DROP FOREIGN KEY `fk_asignaciones_vehiculo`;
-- ALTER TABLE `asignaciones_vehiculos` DROP FOREIGN KEY `fk_asignaciones_usuario`;
-- ALTER TABLE `reportes_fallas_vehiculos` DROP FOREIGN KEY `fk_reportes_fallas_vehiculo`;
-- ALTER TABLE `reportes_fallas_vehiculos` DROP FOREIGN KEY `fk_reportes_fallas_usuario`;
