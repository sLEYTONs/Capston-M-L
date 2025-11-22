<div class="row">
    <!-- Panel de Estadísticas -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estadísticas Generales</h5>
            </div>
            <div class="card-body">
                <div class="row" id="estadisticas-container">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary text-white p-3 rounded">
                            <h6 class="mb-1">Total Mantenimientos</h6>
                            <h3 id="total-mantenimientos">-</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success text-white p-3 rounded">
                            <h6 class="mb-1">Completados</h6>
                            <h3 id="completados">-</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info text-white p-3 rounded">
                            <h6 class="mb-1">Costo Total</h6>
                            <h3 id="costo-total">$0.00</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning text-white p-3 rounded">
                            <h6 class="mb-1">Tiempo Promedio</h6>
                            <h3 id="tiempo-promedio">-</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros de Reporte -->
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
                                <label for="filtro-mecanico" class="form-label">Mecánico</label>
                                <select class="form-select" id="filtro-mecanico">
                                    <option value="">Todos</option>
                                    <!-- Se cargarán dinámicamente -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="filtro-estado" class="form-label">Estado</label>
                                <select class="form-select" id="filtro-estado">
                                    <option value="">Todos</option>
                                    <option value="Asignado">Asignado</option>
                                    <option value="En Proceso">En Proceso</option>
                                    <option value="Completado">Completado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" id="btn-generar-reporte">
                                <i class="fas fa-search me-2"></i>Generar Reporte
                            </button>
                            <button type="button" class="btn btn-secondary" id="btn-limpiar-filtros">
                                <i class="fas fa-times me-2"></i>Limpiar Filtros
                            </button>
                            <button type="button" class="btn btn-success" id="btn-exportar-excel">
                                <i class="fas fa-file-excel me-2"></i>Exportar a Excel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla de Reportes -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Reportes de Mantenimientos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reportes-table" class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Vehículo</th>
                                <th>Placa</th>
                                <th>Mecánico</th>
                                <th>Fecha Asignación</th>
                                <th>Estado</th>
                                <th>Tiempo</th>
                                <th>Costo Repuestos</th>
                                <th>Cant. Repuestos</th>
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

<!-- Modal para Detalles de Mantenimiento -->
<div class="modal fade" id="detallesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    Detalles del Mantenimiento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalles-content">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
