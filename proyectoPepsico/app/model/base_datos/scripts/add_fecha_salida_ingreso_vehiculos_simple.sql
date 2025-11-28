-- =====================================================
-- Script SIMPLE para agregar columna FechaSalida
-- Para DBeaver: Ejecutar cada comando por separado
-- =====================================================

-- COMANDO 1: Ejecutar primero este (seleccionar solo estas 3 líneas y ejecutar)
ALTER TABLE ingreso_vehiculos 
ADD COLUMN FechaSalida DATETIME NULL DEFAULT NULL 
AFTER FechaIngreso;

-- COMANDO 2: Ejecutar después del comando 1 (seleccionar solo estas 2 líneas y ejecutar)
ALTER TABLE ingreso_vehiculos 
ADD INDEX idx_fecha_salida (FechaSalida);

