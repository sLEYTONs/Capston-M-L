<!-- Sección de Mantenimiento -->
<section id="mantenimiento-section" class="section">
    <div class="mantenimiento-container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Programación de Mantenimientos
                        </h4>
                        <div class="card-actions">
                            <button class="btn btn-primary btn-sm" id="btn-nuevo-mantenimiento">
                                <i class="fas fa-plus me-1"></i> Nuevo Mantenimiento
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" id="btn-refresh">
                                <i class="fas fa-sync-alt me-1"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="filtros-mantenimiento mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="filtro-estado" class="form-label">Estado</label>
                                    <select class="form-select" id="filtro-estado">
                                        <option value="">Todos</option>
                                        <option value="Pendiente">Pendiente</option>
                                        <option value="Programado">Programado</option>
                                        <option value="En progreso">En progreso</option>
                                        <option value="Completado">Completado</option>
                                        <option value="Cancelado">Cancelado</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filtro-tipo" class="form-label">Tipo</label>
                                    <select class="form-select" id="filtro-tipo">
                                        <option value="">Todos</option>
                                        <option value="Preventivo">Preventivo</option>
                                        <option value="Correctivo">Correctivo</option>
                                        <option value="Predictivo">Predictivo</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filtro-fecha-desde" class="form-label">Desde</label>
                                    <input type="date" class="form-control" id="filtro-fecha-desde">
                                </div>
                                <div class="col-md-3">
                                    <label for="filtro-fecha-hasta" class="form-label">Hasta</label>
                                    <input type="date" class="form-control" id="filtro-fecha-hasta">
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table id="mantenimiento-table" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Vehículo</th>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th>Fecha Programada</th>
                                        <th>Estado</th>
                                        <th>Mecánico</th>
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
        </div>
    </div>
</section>

<!-- Modal para Gestión de Mantenimiento -->
<div class="modal fade" id="mantenimientoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mantenimientoModalTitle">
                    <i class="fas fa-tools me-2"></i>
                    Nuevo Mantenimiento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="mantenimiento-form">
                    <input type="hidden" id="mantenimiento-id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mantenimiento-vehiculo" class="form-label">Vehículo *</label>
                                <select class="form-select" id="mantenimiento-vehiculo" required>
                                    <option value="">Seleccionar vehículo...</option>
                                    <!-- Se cargarán los vehículos -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mantenimiento-tipo" class="form-label">Tipo de Mantenimiento *</label>
                                <select class="form-select" id="mantenimiento-tipo" required>
                                    <option value="Preventivo">Preventivo</option>
                                    <option value="Correctivo">Correctivo</option>
                                    <option value="Predictivo">Predictivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mantenimiento-fecha" class="form-label">Fecha Programada *</label>
                                <input type="datetime-local" class="form-control" id="mantenimiento-fecha" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mantenimiento-mecanico" class="form-label">Mecánico Asignado</label>
                                <select class="form-select" id="mantenimiento-mecanico">
                                    <option value="">Sin asignar</option>
                                    <!-- Se cargarán los mecánicos -->
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="mantenimiento-descripcion" class="form-label">Descripción *</label>
                        <textarea class="form-control" id="mantenimiento-descripcion" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="mantenimiento-observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="mantenimiento-observaciones" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardar-mantenimiento">
                    <i class="fas fa-save me-2"></i>Guardar Mantenimiento
                </button>
            </div>
        </div>
    </div>
</div>