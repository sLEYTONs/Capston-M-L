<div class="row">
    <!-- Panel de Estadísticas -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estadísticas de Calidad</h5>
            </div>
            <div class="card-body">
                <div class="row" id="estadisticas-container">
                    <div class="col-md-3">
                        <div class="stat-card bg-info text-white p-3 rounded">
                            <h6 class="mb-1">Total Revisiones</h6>
                            <h3 id="total-revisiones">-</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success text-white p-3 rounded">
                            <h6 class="mb-1">Aprobadas</h6>
                            <h3 id="aprobadas">-</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-danger text-white p-3 rounded">
                            <h6 class="mb-1">Rechazadas</h6>
                            <h3 id="rechazadas">-</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning text-white p-3 rounded">
                            <h6 class="mb-1">Pendientes</h6>
                            <h3 id="pendientes-revision">-</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
            </div>
            <div class="card-body">
                <form id="filtros-form">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="filtro-fecha-inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="filtro-fecha-inicio">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="filtro-fecha-fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="filtro-fecha-fin">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="filtro-estado" class="form-label">Estado Asignación</label>
                                <select class="form-select" id="filtro-estado">
                                    <option value="">Todos</option>
                                    <option value="Asignado">Asignado</option>
                                    <option value="En Proceso">En Proceso</option>
                                    <option value="Completado">Completado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="filtro-estado-calidad" class="form-label">Estado Calidad</label>
                                <select class="form-select" id="filtro-estado-calidad">
                                    <option value="">Todos</option>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Aprobado">Aprobado</option>
                                    <option value="Rechazado">Rechazado</option>
                                    <option value="En Revisión">En Revisión</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" id="btn-aplicar-filtros">
                                <i class="fas fa-search me-2"></i>Aplicar Filtros
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btn-limpiar-filtros">
                                <i class="fas fa-redo me-2"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla de Asignaciones -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Asignaciones para Revisión</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabla-asignaciones" class="table table-striped table-hover" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Placa</th>
                                <th>Vehículo</th>
                                <th>Mecánico</th>
                                <th>Fecha Asignación</th>
                                <th>Estado</th>
                                <th>Estado Calidad</th>
                                <th>Diagnóstico</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Detalles y Revisar Calidad -->
<div class="modal fade" id="modalRevisarCalidad" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Diagnóstico de Fallas y Control de Calidad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Información del Vehículo -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-car me-2"></i>Información del Vehículo</h6>
                            </div>
                            <div class="card-body" id="info-vehiculo">
                                <!-- Se cargará dinámicamente -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-user-cog me-2"></i>Mecánico Asignado</h6>
                            </div>
                            <div class="card-body" id="info-mecanico">
                                <!-- Se cargará dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historial de Avances -->
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Avances</h6>
                    </div>
                    <div class="card-body">
                        <div id="historial-avances">
                            <!-- Se cargará dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- Formulario de Revisión de Calidad -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i>Revisión de Calidad</h6>
                    </div>
                    <div class="card-body">
                        <form id="form-revision-calidad">
                            <input type="hidden" id="revision-asignacion-id">
                            
                            <div class="mb-3">
                                <label for="diagnostico-falla" class="form-label">Diagnóstico de Falla <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="diagnostico-falla" rows="3" 
                                          placeholder="Describa el diagnóstico de la falla encontrada..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="estado-calidad" class="form-label">Estado de Calidad <span class="text-danger">*</span></label>
                                <select class="form-select" id="estado-calidad" required>
                                    <option value="">Seleccione un estado</option>
                                    <option value="Aprobado">Aprobado</option>
                                    <option value="Rechazado">Rechazado</option>
                                    <option value="En Revisión">En Revisión</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="observaciones-calidad" class="form-label">Observaciones de Calidad</label>
                                <textarea class="form-control" id="observaciones-calidad" rows="3" 
                                          placeholder="Observaciones sobre la calidad del trabajo realizado..."></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Guardar Revisión
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

