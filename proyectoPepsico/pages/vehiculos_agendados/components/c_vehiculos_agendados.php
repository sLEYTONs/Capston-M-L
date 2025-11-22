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
<div id="detalles-modal" class="modal" style="display: none;">
    <div class="modal-content modal-lg">
        <span class="close" onclick="document.getElementById('detalles-modal').style.display='none'">&times;</span>
        <h3><i class="fas fa-info-circle me-2"></i>Detalles del Vehículo Agendado</h3>
        <div id="detalles-content">
            <!-- Contenido se cargará aquí -->
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="document.getElementById('detalles-modal').style.display='none'">Cerrar</button>
        </div>
    </div>
</div>

