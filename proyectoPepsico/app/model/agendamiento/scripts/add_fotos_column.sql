-- Script para agregar la columna Fotos a la tabla solicitudes_agendamiento
-- Esta columna almacenará las rutas de las imágenes subidas por el chofer en formato JSON

ALTER TABLE `solicitudes_agendamiento` 
ADD COLUMN `Fotos` TEXT DEFAULT NULL COMMENT 'Rutas de las imágenes subidas en formato JSON' 
AFTER `Observaciones`;

