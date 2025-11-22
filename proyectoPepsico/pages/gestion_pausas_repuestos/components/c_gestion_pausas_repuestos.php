<div class="gestion-pausas-container">
    <!-- Resumen de Tareas en Pausa -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="summary-item">
                                <div class="summary-icon bg-warning">
                                    <i class="fas fa-pause-circle fa-2x"></i>
                                </div>
                                <div class="summary-content">
                                    <h3 id="total-pausadas" class="mb-0">0</h3>
                                    <p class="mb-0">Tareas en Pausa</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección 1: Tareas en Pausa -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-list-alt me-2"></i>
                        Tareas en Pausa
                    </h4>
                    <button class="btn btn-primary btn-sm" id="btn-refrescar-tareas">
                        <i class="fas fa-sync-alt me-1"></i> Actualizar
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tareas-pausadas-table" class="table table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID Tarea</th>
                                    <th>Vehículo</th>
                                    <th>Placa</th>
                                    <th>Conductor</th>
                                    <th>Motivo de Pausa</th>
                                    <th>Fecha Asignación</th>
                                    <th>Solicitudes</th>
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
<div id="detalles-solicitud-modal" class="modal" style="display: none;">
    <div class="modal-content modal-lg">
        <span class="close" onclick="document.getElementById('detalles-solicitud-modal').style.display='none'">&times;</span>
        <h3><i class="fas fa-info-circle me-2"></i>Detalles de la Solicitud</h3>
        <div id="detalles-solicitud-content">
            <!-- Contenido se cargará aquí -->
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="document.getElementById('detalles-solicitud-modal').style.display='none'">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Reanudar Tarea -->
<div class="modal fade" id="modalReanudarTarea" tabindex="-1" aria-labelledby="modalReanudarTareaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalReanudarTareaLabel">
                    <i class="fas fa-play-circle me-2"></i>
                    Confirmar Reanudación de Tarea
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="fas fa-play-circle fa-4x text-success"></i>
                    </div>
                    <h5 class="mb-3">¿Está seguro de que desea reanudar esta tarea?</h5>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>ID Tarea:</strong> <span id="reanudar-asignacion-id"></span><br>
                        <strong>Vehículo:</strong> <span id="reanudar-vehiculo-info"></span><br>
                        <strong>Placa:</strong> <span id="reanudar-placa"></span>
                    </div>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <small>Al reanudar, la tarea volverá al estado "En Proceso" y podrás continuar con el trabajo.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success" id="btn-confirmar-reanudar">
                    <i class="fas fa-check me-2"></i>Confirmar Reanudación
                </button>
            </div>
        </div>
    </div>
</div>

