<?php
// Obtener el rol del usuario desde la sesión
$usuario_rol = isset($_SESSION['usuario']['rol']) ? trim($_SESSION['usuario']['rol']) : '';
$esGuardia = (strcasecmp($usuario_rol, 'Guardia') === 0);
?>
<div class="vehiculos-agendados-container">
    <!-- Historial de Vehículos Agendados -->
    <div class="card">
        <div class="card-header historial-header">
            <h4 class="mb-0">
                <i class="fas fa-history me-2"></i>
                Historial de Vehículos Agendados
            </h4>
            <div class="header-actions">
                <?php if (!$esGuardia): ?>
                <div class="date-filter">
                    <label for="fecha-filtro-historial" class="form-label">Filtrar por fecha:</label>
                    <input type="date" id="fecha-filtro-historial" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <?php endif; ?>
                <button class="btn btn-primary" id="btn-refrescar-historial">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="historial-vehiculos-table" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Vehículo</th>
                            <th>Conductor</th>
                            <th>Fecha Agenda</th>
                            <th>Hora</th>
                            <th>Propósito</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los datos se cargarán via Ajax -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Pasar el rol del usuario al JavaScript
    window.usuarioRol = '<?php echo $usuario_rol; ?>';
    window.esGuardia = <?php echo $esGuardia ? 'true' : 'false'; ?>;
</script>

<!-- Modal para ver detalles -->
<div class="modal fade" id="detalles-modal" tabindex="-1" aria-labelledby="detalles-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detalles-modal-label">
                    <i class="fas fa-info-circle me-2"></i>Detalles del Vehículo Agendado
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="detalles-content">
                    <!-- Contenido se cargará aquí -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

