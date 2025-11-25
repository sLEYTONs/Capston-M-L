<main class="main">
    <div class="container">
        <!-- Sección de Mis Solicitudes -->
        <section id="mis-solicitudes-section" class="section active">
            <div class="form-container">
                <h2>
                    <i class="fas fa-list me-2"></i>
                    Mis Solicitudes de Agendamiento
                </h2>
                <div class="results-container">
                    <table id="mis-solicitudes-table" class="results-table display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Placa</th>
                                <th>Vehículo</th>
                                <th>Fecha/Hora Asignada</th>
                                <th>Estado</th>
                                <th>Fecha Respuesta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán via Ajax -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>

<!-- Modal para ver motivo de rechazo -->
<div class="modal fade" id="motivo-rechazo-modal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg">
            <div class="modal-header" id="motivo-rechazo-header">
                <h5 class="modal-title" id="motivo-rechazo-titulo">
                    <i class="fas fa-info-circle me-2"></i>Motivo de Rechazo / Cancelación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Icono y título principal -->
                <div class="text-center mb-4">
                    <div id="motivo-rechazo-icono" class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-4x text-warning"></i>
                    </div>
                    <h4 class="fw-bold mb-2" id="motivo-rechazo-titulo-principal">Información del Estado</h4>
                    <p class="text-muted mb-0" id="motivo-rechazo-subtitulo">Detalles sobre el motivo de rechazo o cancelación</p>
                </div>

                <!-- Alerta principal con motivo -->
                <div class="alert mb-4" id="motivo-rechazo-alerta">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0 me-3">
                            <i class="fas fa-info-circle fa-2x" id="motivo-rechazo-icono-alerta"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading fw-bold mb-3">
                                <i class="fas fa-file-alt me-2"></i>Motivo de Rechazo
                            </h6>
                            <div id="motivo-rechazo-texto" class="motivo-rechazo-contenido">
                                <!-- El contenido se llenará dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información adicional si es atrasado o no llegó -->
                <div class="card border-0 bg-light mb-3" id="motivo-rechazo-info-adicional" style="display: none;">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0 me-3">
                                <i class="fas fa-info-circle fa-lg text-info"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-dark" id="motivo-rechazo-info-texto">
                                    <!-- Información adicional -->
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver seguimiento del vehículo -->
<div id="seguimiento-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close" id="close-seguimiento-modal">&times;</span>
        <h3>
            <i class="fas fa-tools me-2"></i>
            Seguimiento del Vehículo
        </h3>
        <div id="seguimiento-content" style="max-height: 600px; overflow-y: auto;">
            <div class="text-center" style="padding: 2rem;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando información del seguimiento...</p>
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" id="cerrar-seguimiento-modal">Cerrar</button>
        </div>
    </div>
</div>

