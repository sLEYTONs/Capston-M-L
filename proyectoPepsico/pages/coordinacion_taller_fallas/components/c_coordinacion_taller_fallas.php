<?php
// Obtener rol del usuario para controlar visibilidad
$usuario_rol = $_SESSION['usuario']['rol'] ?? '';
$usuario_actual = $_SESSION['usuario']['nombre'] ?? '';
?>
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Coordinación con Taller - Comunicación de Flota</h5>
                    <button class="btn btn-primary btn-sm" id="btn-nueva-comunicacion-taller" data-bs-toggle="modal" data-bs-target="#modalNuevaComunicacionTaller">
                        <i class="fas fa-plus me-2"></i>Nueva Comunicación al Taller
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="filtro-placa-taller" class="form-label">Buscar por Placa</label>
                        <input type="text" class="form-control" id="filtro-placa-taller" placeholder="Ej: ABCD60">
                    </div>
                    <div class="col-md-3">
                        <label for="filtro-tipo-taller" class="form-label">Tipo</label>
                        <select class="form-select" id="filtro-tipo-taller">
                            <option value="">Todos</option>
                            <option value="Solicitud">Solicitud</option>
                            <option value="Notificación">Notificación</option>
                            <option value="Consulta">Consulta</option>
                            <option value="Urgente">Urgente</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filtro-estado-taller" class="form-label">Estado</label>
                        <select class="form-select" id="filtro-estado-taller">
                            <option value="">Todos</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Respondida">Respondida</option>
                            <option value="Cerrada">Cerrada</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button class="btn btn-secondary" id="btn-aplicar-filtros-taller">
                                <i class="fas fa-search me-2"></i>Buscar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Comunicaciones con Taller -->
                <div class="table-responsive">
                    <table id="tabla-comunicaciones-taller" class="table table-striped table-hover" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha</th>
                                <th>Placa</th>
                                <th>Tipo</th>
                                <th>Asunto</th>
                                <th>Estado</th>
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

<!-- Modal Nueva Comunicación al Taller -->
<div class="modal fade" id="modalNuevaComunicacionTaller" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-tools me-2"></i>Nueva Comunicación al Taller
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-nueva-comunicacion-taller">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="placa-comunicacion-taller" class="form-label">Placa del Vehículo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="placa-comunicacion-taller" 
                               placeholder="Ej: ABCD60" required>
                        <small class="form-text text-muted">Placa del vehículo con la falla</small>
                    </div>
                    <div class="mb-3">
                        <label for="tipo-comunicacion-taller" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipo-comunicacion-taller" required>
                            <option value="">Seleccionar...</option>
                            <option value="Urgente">Urgente</option>
                            <option value="Solicitud">Solicitud</option>
                            <option value="Notificación">Notificación</option>
                            <option value="Consulta">Consulta</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="asunto-comunicacion-taller" class="form-label">Asunto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="asunto-comunicacion-taller" 
                               placeholder="Ej: Falla en sistema de frenos" required>
                    </div>
                    <div class="mb-3">
                        <label for="mensaje-comunicacion-taller" class="form-label">Mensaje/Descripción de la Falla <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="mensaje-comunicacion-taller" rows="5" 
                                  placeholder="Describe la falla del vehículo..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Comunicación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Detalles de Comunicación -->
<div class="modal fade" id="modalDetallesComunicacionTaller" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Detalles de Comunicación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalles-comunicacion-taller">
                    <!-- Se cargarán dinámicamente -->
                </div>
            </div>
            <div class="modal-footer" id="footer-detalles-comunicacion-taller">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Responder Comunicación -->
<div class="modal fade" id="modalResponderComunicacionTaller" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-reply me-2"></i>Responder Comunicación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-responder-comunicacion-taller">
                <input type="hidden" id="comunicacion-id-respuesta-taller">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Respondiendo a:</strong> <span id="asunto-comunicacion-respuesta-taller"></span>
                    </div>
                    <div class="mb-3">
                        <label for="mensaje-respuesta-taller" class="form-label">Mensaje de Respuesta <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="mensaje-respuesta-taller" rows="6" 
                                  placeholder="Escriba su respuesta..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Respuesta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
