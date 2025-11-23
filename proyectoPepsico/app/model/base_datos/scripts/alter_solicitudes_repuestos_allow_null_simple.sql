ALTER TABLE solicitudes_repuestos DROP FOREIGN KEY fk_solicitudes_repuestos_asignacion;
ALTER TABLE solicitudes_repuestos MODIFY AsignacionID INT(11) NULL;
ALTER TABLE solicitudes_repuestos ADD INDEX idx_asignacionid (AsignacionID);
ALTER TABLE solicitudes_repuestos ADD CONSTRAINT fk_solicitudes_repuestos_asignacion FOREIGN KEY (AsignacionID) REFERENCES asignaciones_mecanico (ID) ON DELETE SET NULL ON UPDATE CASCADE;

