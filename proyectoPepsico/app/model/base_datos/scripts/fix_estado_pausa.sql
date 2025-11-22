-- Script para corregir asignaciones que tienen MotivoPausa pero no tienen Estado
-- Actualiza el Estado a 'En Pausa' para todas las asignaciones que tienen MotivoPausa pero Estado está vacío o NULL

UPDATE asignaciones_mecanico 
SET Estado = 'En Pausa'
WHERE (Estado IS NULL OR Estado = '')
AND MotivoPausa IS NOT NULL 
AND MotivoPausa != '';

-- Verificar el resultado
SELECT 
    ID,
    MecanicoID,
    VehiculoID,
    Estado,
    MotivoPausa,
    Observaciones
FROM asignaciones_mecanico
WHERE MotivoPausa IS NOT NULL 
AND MotivoPausa != '';

