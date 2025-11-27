-- =====================================================
-- Verificar estructura de ingreso_vehiculos
-- Ejecuta estas queries para diagnosticar el problema
-- =====================================================

-- 1. Verificar que la tabla existe y su motor
SELECT 
    TABLE_NAME,
    ENGINE,
    TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'ingreso_vehiculos';

-- 2. Verificar estructura de la columna ID
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_TYPE,
    COLUMN_KEY,
    IS_NULLABLE,
    EXTRA,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'ingreso_vehiculos' 
  AND COLUMN_NAME = 'ID';

-- 3. Verificar índices en la columna ID
SHOW INDEX FROM `ingreso_vehiculos` WHERE Column_name = 'ID';

-- 4. Verificar si ID es PRIMARY KEY
SELECT 
    CONSTRAINT_NAME,
    CONSTRAINT_TYPE,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'ingreso_vehiculos'
  AND COLUMN_NAME = 'ID';

-- 5. Verificar si hay datos en las nuevas tablas que violen la integridad
-- (solo si ya insertaste datos)
SELECT COUNT(*) as total_asignaciones FROM `asignaciones_vehiculos`;
SELECT COUNT(*) as total_reportes FROM `reportes_fallas_vehiculos`;

-- Verificar si hay VehiculoID que no existan en ingreso_vehiculos
SELECT DISTINCT av.VehiculoID 
FROM asignaciones_vehiculos av
LEFT JOIN ingreso_vehiculos iv ON av.VehiculoID = iv.ID
WHERE iv.ID IS NULL;

SELECT DISTINCT rf.VehiculoID 
FROM reportes_fallas_vehiculos rf
LEFT JOIN ingreso_vehiculos iv ON rf.VehiculoID = iv.ID
WHERE iv.ID IS NULL;

