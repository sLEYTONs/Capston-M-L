<!-- Sección de Coordinación con Jefe de Taller -->
<?php 
// Obtener rol del usuario para controlar visibilidad
$usuario_rol = $_SESSION['usuario']['rol'] ?? '';
?>
<section id="coordinacion-jefe-taller-section" class="section">
    <div class="container-fluid">
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Solicitudes Pendientes</h6>
                        <h3 class="mb-0" id="solicitudes-pendientes">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">En Proceso</h6>
                        <h3 class="mb-0" id="en-proceso">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Completadas</h6>
                        <h3 class="mb-0" id="completadas">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Urgentes</h6>
                        <h3 class="mb-0" id="urgentes">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Nueva Solicitud/Comunicación (solo para Coordinador de Zona) -->
        <?php if ($usuario_rol !== 'Jefe de Taller'): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-paper-plane me-2"></i>
                            Nueva Solicitud/Comunicación al Jefe de Taller
                        </h4>
                    </div>
                    <div class="card-body">
                        <form id="form-comunicacion-jefe">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tipo-comunicacion" class="form-label">Tipo *</label>
                                        <select class="form-select" id="tipo-comunicacion" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="solicitud-aprobacion">Solicitud de Aprobación</option>
                                            <option value="consulta">Consulta</option>
                                            <option value="reporte">Solicitud de Reporte</option>
                                            <option value="coordinacion">Coordinación</option>
                                            <option value="urgencia">Urgencia</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="prioridad-comunicacion" class="form-label">Prioridad *</label>
                                        <select class="form-select" id="prioridad-comunicacion" required>
                                            <option value="baja">Baja</option>
                                            <option value="media" selected>Media</option>
                                            <option value="alta">Alta</option>
                                            <option value="urgente">Urgente</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="asunto-comunicacion" class="form-label">Asunto *</label>
                                <input type="text" class="form-control" id="asunto-comunicacion" required>
                            </div>
                            <div class="mb-3">
                                <label for="mensaje-comunicacion" class="form-label">Mensaje *</label>
                                <textarea class="form-control" id="mensaje-comunicacion" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Enviar Comunicación
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabla de Comunicaciones -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-comments me-2"></i>
                            Historial de Comunicaciones
                        </h4>
                        <button class="btn btn-sm btn-primary" id="btn-actualizar-comunicaciones">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabla-comunicaciones" class="table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <?php if ($usuario_rol === 'Jefe de Taller'): ?>
                                        <th>De</th>
                                        <?php endif; ?>
                                        <th>Tipo</th>
                                        <th>Asunto</th>
                                        <th>Prioridad</th>
                                        <th>Estado</th>
                                        <th>Respuesta</th>
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
</section>

<!-- Modal para Ver Detalles -->
<div class="modal fade" id="modal-detalles-comunicacion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    Detalles de Comunicación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalles-comunicacion">
                    <!-- Se cargarán dinámicamente -->
                </div>
            </div>
            <div class="modal-footer" id="footer-detalles-comunicacion">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Responder Comunicación -->
<div class="modal fade" id="modal-responder-comunicacion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-reply me-2"></i>
                    Responder Comunicación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-responder-comunicacion">
                    <input type="hidden" id="comunicacion-id-respuesta">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Respondiendo a:</strong> <span id="asunto-comunicacion-respuesta"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mensaje-respuesta-comunicacion" class="form-label">Mensaje de Respuesta <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="mensaje-respuesta-comunicacion" rows="6" 
                                  placeholder="Escriba su respuesta..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Respuesta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
