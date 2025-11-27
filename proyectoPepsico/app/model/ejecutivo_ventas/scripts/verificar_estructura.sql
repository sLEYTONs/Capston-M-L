-- =====================================================
-- Script de Verificación de Estructura
-- Ejecuta estas queries ANTES de crear las foreign keys
-- =====================================================

-- 1. Verificar que las tablas existan
SELECT 
    TABLE_NAME,
    ENGINE,
    TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('ingreso_vehiculos', 'usuarios', 'asignaciones_vehiculos', 'reportes_fallas_vehiculos')
ORDER BY TABLE_NAME;

-- 2. Verificar estructura de ingreso_vehiculos.ID
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_TYPE,
    COLUMN_KEY,
    IS_NULLABLE,
    EXTRA
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'ingreso_vehiculos' 
  AND COLUMN_NAME = 'ID';

-- 3. Verificar estructura de usuarios.UsuarioID
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_TYPE,
    COLUMN_KEY,
    IS_NULLABLE,
    EXTRA
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'usuarios' 
  AND COLUMN_NAME = 'UsuarioID';

-- 4. Verificar índices en ingreso_vehiculos
SHOW INDEX FROM `ingreso_vehiculos` WHERE Column_name = 'ID';

-- 5. Verificar índices en usuarios
SHOW INDEX FROM `usuarios` WHERE Column_name = 'UsuarioID';

-- 6. Verificar si hay datos que puedan causar problemas
SELECT COUNT(*) as total_vehiculos FROM `ingreso_vehiculos`;
SELECT COUNT(*) as total_usuarios FROM `usuarios`;

