-- Script para agregar campo de notificación al jefe de taller
-- Evita enviar múltiples notificaciones para la misma solicitud

ALTER TABLE `solicitudes_repuestos` 
ADD COLUMN `FechaNotificacionJefe` DATETIME DEFAULT NULL COMMENT 'Fecha en que se notificó al Jefe de Taller por stock insuficiente' AFTER `FechaEntrega`;

-- Crear índice para mejorar búsquedas
CREATE INDEX `idx_fecha_notificacion_jefe` ON `solicitudes_repuestos` (`FechaNotificacionJefe`);

