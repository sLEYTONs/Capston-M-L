<div class="estado-solicitudes-container">
    <!-- Resumen de Solicitudes -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="summary-item">
                                <div class="summary-icon bg-warning">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <div class="summary-content">
                                    <h3 id="solicitudes-pendientes" class="mb-0">0</h3>
                                    <p class="mb-0">Pendientes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-item">
                                <div class="summary-icon bg-info">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                                <div class="summary-content">
                                    <h3 id="solicitudes-aprobadas" class="mb-0">0</h3>
                                    <p class="mb-0">Aprobadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-item">
                                <div class="summary-icon bg-success">
                                    <i class="fas fa-truck fa-2x"></i>
                                </div>
                                <div class="summary-content">
                                    <h3 id="solicitudes-entregadas" class="mb-0">0</h3>
                                    <p class="mb-0">Entregadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="summary-item">
                                <div class="summary-icon bg-danger">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                                <div class="summary-content">
                                    <h3 id="solicitudes-rechazadas" class="mb-0">0</h3>
                                    <p class="mb-0">Rechazadas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Estado de Solicitudes -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>
                        Estado de Mis Solicitudes
                    </h4>
                    <button class="btn btn-primary btn-sm" id="btn-refrescar">
                        <i class="fas fa-sync-alt me-1"></i> Actualizar
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="solicitudes-table" class="table table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Repuesto</th>
                                    <th>Cantidad</th>
                                    <th>Urgencia</th>
                                    <th>Vehículo</th>
                                    <th>Estado</th>
                                    <th>Fecha Solicitud</th>
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

<!-- Modal para ver detalles de solicitud -->
<div class="modal fade" id="detalles-solicitud-modal" tabindex="-1" aria-labelledby="detallesSolicitudModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="detallesSolicitudModalLabel">
                    <i class="fas fa-info-circle me-2"></i>
                    Detalles de la Solicitud de Repuestos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="detalles-solicitud-content">
                <!-- Contenido se cargará aquí -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

