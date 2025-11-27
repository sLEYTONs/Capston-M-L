-- =====================================================
-- Soluciones para el problema de Foreign Keys con ingreso_vehiculos
-- =====================================================

-- OPCIÓN 1: Si ingreso_vehiculos.ID no es PRIMARY KEY, crear índice único primero
-- Descomenta y ejecuta si es necesario:

-- ALTER TABLE `ingreso_vehiculos` ADD PRIMARY KEY (`ID`);
-- O si ya tiene otro PRIMARY KEY:
-- CREATE UNIQUE INDEX `idx_id_unique` ON `ingreso_vehiculos` (`ID`);

-- OPCIÓN 2: Si la tabla usa MyISAM, convertirla a InnoDB
-- Descomenta y ejecuta si es necesario:

-- ALTER TABLE `ingreso_vehiculos` ENGINE=InnoDB;

-- OPCIÓN 3: Verificar y corregir el tipo de dato
-- Si VehiculoID es INT pero ingreso_vehiculos.ID es otro tipo, ajustar:

-- Verificar tipos:
-- SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = DATABASE() 
--   AND TABLE_NAME IN ('asignaciones_vehiculos', 'reportes_fallas_vehiculos', 'ingreso_vehiculos')
--   AND COLUMN_NAME IN ('ID', 'VehiculoID');

-- OPCIÓN 4: Crear las foreign keys con verificación previa
-- Ejecuta estas queries UNA POR UNA y revisa los mensajes:

-- Primero, eliminar las foreign keys si ya existen (opcional)
-- ALTER TABLE `asignaciones_vehiculos` DROP FOREIGN KEY IF EXISTS `fk_asignaciones_vehiculo`;
-- ALTER TABLE `reportes_fallas_vehiculos` DROP FOREIGN KEY IF EXISTS `fk_reportes_fallas_vehiculo`;

-- Verificar que ingreso_vehiculos.ID existe y es PRIMARY KEY
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'OK: Columna ID existe'
        ELSE 'ERROR: Columna ID no existe'
    END as verificacion_id
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'ingreso_vehiculos' 
  AND COLUMN_NAME = 'ID'
  AND COLUMN_KEY = 'PRI';

-- Verificar que el motor es InnoDB
SELECT 
    CASE 
        WHEN ENGINE = 'InnoDB' THEN 'OK: Motor InnoDB'
        ELSE CONCAT('ADVERTENCIA: Motor es ', ENGINE, ' - debería ser InnoDB')
    END as verificacion_engine
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'ingreso_vehiculos';

-- OPCIÓN 5: Si todo lo anterior falla, crear las foreign keys sin verificación estricta
-- (Solo si estás seguro de que los datos son correctos)

-- Intentar crear con IGNORE (si tu versión de MySQL lo soporta)
-- ALTER TABLE `asignaciones_vehiculos` 
-- ADD CONSTRAINT `fk_asignaciones_vehiculo` 
-- FOREIGN KEY (`VehiculoID`) 
-- REFERENCES `ingreso_vehiculos` (`ID`) 
-- ON DELETE RESTRICT 
-- ON UPDATE CASCADE;

-- ALTER TABLE `reportes_fallas_vehiculos` 
-- ADD CONSTRAINT `fk_reportes_fallas_vehiculo` 
-- FOREIGN KEY (`VehiculoID`) 
-- REFERENCES `ingreso_vehiculos` (`ID`) 
-- ON DELETE RESTRICT 
-- ON UPDATE CASCADE;

