<div class="vehiculos-agendados-container">
    <div class="card">
        <div class="card-header vehiculos-header">
            <h4 class="mb-0">
                <i class="fas fa-calendar-check me-2"></i>
                Vehículos con Horas Agendadas
            </h4>
            <div class="header-actions">
                <div class="date-filter">
                    <label for="fecha-filtro" class="form-label">Filtrar por fecha:</label>
                    <input type="date" id="fecha-filtro" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button class="btn btn-primary" id="btn-refrescar">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="vehiculos-agendados-table" class="table table-hover" style="width:100%">
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

