-- =====================================================
-- Script para agregar columna FechaSalida a ingreso_vehiculos
-- Ejecutar en DBeaver
-- IMPORTANTE: Ejecutar cada bloque SQL por separado (seleccionar y ejecutar)
-- =====================================================

-- =====================================================
-- BLOQUE 1: Agregar columna FechaSalida
-- Selecciona y ejecuta solo este bloque primero
-- =====================================================
ALTER TABLE ingreso_vehiculos 
ADD COLUMN FechaSalida DATETIME NULL DEFAULT NULL 
AFTER FechaIngreso;

-- =====================================================
-- BLOQUE 2: Agregar índice (ejecutar después del bloque 1)
-- Selecciona y ejecuta solo este bloque
-- =====================================================
ALTER TABLE ingreso_vehiculos 
ADD INDEX idx_fecha_salida (FechaSalida);

-- =====================================================
-- BLOQUE 3: Verificar que la columna se agregó (opcional)
-- Selecciona y ejecuta para verificar
-- =====================================================
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'ingreso_vehiculos' 
  AND COLUMN_NAME = 'FechaSalida';

