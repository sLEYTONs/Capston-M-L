-- Script para modificar la tabla solicitudes_repuestos
-- Permite que AsignacionID sea NULL para que el Asistente de Repuestos pueda crear solicitudes sin asignación

-- Paso 1: Eliminar la foreign key constraint existente
ALTER TABLE solicitudes_repuestos DROP FOREIGN KEY fk_solicitudes_repuestos_asignacion;

-- Paso 2: Modificar la columna AsignacionID para permitir NULL
ALTER TABLE solicitudes_repuestos MODIFY AsignacionID INT(11) NULL;

-- Paso 3: Crear índice en AsignacionID (necesario para la foreign key)
-- Nota: Si el índice ya existe, este comando puede generar un error que puedes ignorar
ALTER TABLE solicitudes_repuestos ADD INDEX idx_asignacionid (AsignacionID);

-- Paso 4: Recrear la foreign key constraint con ON DELETE SET NULL para permitir NULL
ALTER TABLE solicitudes_repuestos ADD CONSTRAINT fk_solicitudes_repuestos_asignacion FOREIGN KEY (AsignacionID) REFERENCES asignaciones_mecanico (ID) ON DELETE SET NULL ON UPDATE CASCADE;

