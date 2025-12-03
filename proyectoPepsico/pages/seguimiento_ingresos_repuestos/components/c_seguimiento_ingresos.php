<!-- Sección de Seguimiento de Ingresos de Repuestos -->
<section id="seguimiento-ingresos-section" class="section">
    <div class="repuestos-container">
        <div class="repuestos-layout">
            <!-- Contenido principal -->
            <div class="repuestos-main-content">
                <div class="card h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Seguimiento de Ingresos de Repuestos
                        </h4>
                        <div class="card-actions">
                            <button class="btn btn-outline-light btn-sm" id="btn-refresh" title="Actualizar">
                                <i class="fas fa-sync-alt me-1"></i> Actualizar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filtros -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="filtro-tipo" class="form-label">Filtrar por Tipo</label>
                                <select id="filtro-tipo" class="form-select">
                                    <option value="">Todos los tipos</option>
                                    <option value="Recepción">Recepción</option>
                                    <option value="Entrega">Entrega</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filtro-fecha-desde" class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" id="filtro-fecha-desde">
                            </div>
                            <div class="col-md-3">
                                <label for="filtro-fecha-hasta" class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" id="filtro-fecha-hasta">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="btn-filtrar">
                                    <i class="fas fa-filter me-2"></i>Filtrar
                                </button>
                            </div>
                        </div>

                        <!-- Historial de Recepciones y Entregas -->
                        <div class="tabla-container">
                            <div class="table-responsive">
                                <table id="tabla-historial" class="table table-striped table-hover table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Proveedor/Vehículo</th>
                                            <th>Cantidad Total</th>
                                            <th>Usuario</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Se cargarán dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal para ver detalles del historial -->
<div class="modal fade" id="modal-detalles-historial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    Detalles del Registro
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-detalles-historial-body">
                <!-- Se cargará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

