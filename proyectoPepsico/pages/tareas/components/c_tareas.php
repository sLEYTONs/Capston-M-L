<!-- Sección de Tareas Asignadas -->
<section id="tareas-section" class="section">
    <div class="tareas-container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-tasks me-2"></i>
                            Mis Vehículos Asignados
                        </h4>
                        <div class="card-actions">
                            <?php if (tiene_acceso('gestion_pausas_repuestos.php')): ?>
                            <a href="gestion_pausas_repuestos.php" class="btn btn-warning btn-sm me-2">
                                <i class="fas fa-pause-circle me-1"></i> Gestionar Pausas y Repuestos
                            </a>
                            <?php endif; ?>
                            <button class="btn btn-primary btn-sm" id="btn-refresh">
                                <i class="fas fa-sync-alt me-1"></i> Actualizar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Resumen de tareas -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0" id="total-asignados">0</h4>
                                                <span>Total Asignados</span>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-list fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0" id="en-progreso">0</h4>
                                                <span>En Progreso</span>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-tools fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0" id="pendientes">0</h4>
                                                <span>Pendientes</span>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-clock fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0" id="completados">0</h4>
                                                <span>Completados</span>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-check-circle fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="tareas-table" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Vehículo</th>
                                        <th>Placa</th>
                                        <th>Fecha Asignación</th>
                                        <th>Estado</th>
                                        <th>Observaciones</th>
                                        <th>Último Avance</th>
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

<!-- Modal para Registrar Avance -->
<div class="modal fade" id="avanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-tareas-custom">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Registrar Avance - <span id="modal-placa-avance"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="avance-form" enctype="multipart/form-data">
                    <input type="hidden" id="avance-asignacion-id">
                    <input type="hidden" id="avance-vehiculo-id">
                    
                    <!-- Información del vehículo -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-car me-2"></i>Información del Vehículo
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Placa:</strong> <span id="info-placa"></span></p>
                                    <p><strong>Vehículo:</strong> <span id="info-vehiculo"></span></p>
                                    <p><strong>Año:</strong> <span id="info-anio"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Conductor:</strong> <span id="info-conductor"></span></p>
                                    <p><strong>Estado:</strong> <span id="info-estado"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de avance -->
                    <div class="mb-3">
                        <label for="avance-descripcion" class="form-label">Descripción del Avance *</label>
                        <textarea class="form-control" id="avance-descripcion" rows="4" 
                                  placeholder="Describe el trabajo realizado, repuestos utilizados, horas trabajadas, etc..." required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="avance-estado" class="form-label">Estado del Trabajo</label>
                                <select class="form-select" id="avance-estado">
                                    <option value="En progreso">En progreso</option>
                                    <option value="Completado">Completado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="avance-fotos" class="form-label">Subir Fotos del Trabajo</label>
                                <input type="file" class="form-control" id="avance-fotos" name="avance_fotos[]" 
                                       multiple accept=".jpg,.jpeg,.png,.gif,.webp">
                                <small class="form-text text-muted">Máximo 5 fotos, 5MB cada una</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vista previa de fotos -->
                    <div class="mb-3" id="fotos-preview-container" style="display: none;">
                        <label class="form-label">Vista Previa de Fotos</label>
                        <div class="row g-2" id="fotos-preview">
                            <!-- Las fotos seleccionadas aparecerán aquí -->
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Al marcar como "Completado", la tarea se dará por finalizada.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardar-avance">
                    <i class="fas fa-save me-2"></i>Guardar Avance
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Historial de Avances -->
<div class="modal fade" id="historialModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-tareas-custom">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i>
                    Historial de Avances - <span id="modal-placa-historial"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="historial-avances" class="historial-scroll">
                    <!-- Los avances se cargarán aquí -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Información Completa del Vehículo -->
<div class="modal fade" id="infoVehiculoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-tareas-custom">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    Información Completa - <span id="modal-placa-info"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="info-vehiculo-completa">
                    <!-- La información se cargará aquí -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Fotos del Vehículo -->
<div class="modal fade" id="fotosVehiculoModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-fotos-custom modal-tareas-custom">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-images me-2"></i>
                    Fotos del Vehículo - <span id="modal-placa-fotos"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="galeria-fotos-vehiculo">
                    <!-- Las fotos se cargarán aquí -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Pausar Tarea -->
<div class="modal fade" id="pausaModal" tabindex="-1">
    <div class="modal-dialog modal-tareas-custom modal-tareas-sm">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-pause-circle me-2"></i>
                    Pausar Tarea - <span id="modal-placa-pausa"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="pausa-form">
                    <input type="hidden" id="pausa-asignacion-id">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Al pausar esta tarea, se detendrá el trabajo hasta que se resuelva el motivo de la pausa.
                    </div>

                    <div class="mb-3">
                        <label for="pausa-motivo" class="form-label">Motivo de la Pausa *</label>
                        <select class="form-select" id="pausa-motivo" required>
                            <option value="">Selecciona un motivo</option>
                            <option value="Espera de repuestos">Espera de repuestos</option>
                            <option value="Sin stock de repuesto">Sin stock de repuesto</option>
                            <option value="Esperando autorización">Esperando autorización</option>
                            <option value="Problema técnico">Problema técnico</option>
                            <option value="Falta de herramientas">Falta de herramientas</option>
                            <option value="Esperando diagnóstico">Esperando diagnóstico</option>
                            <option value="Otro">Otro (especificar)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="pausa-motivo-custom-group" style="display: none;">
                        <label for="pausa-motivo-custom" class="form-label">Especificar motivo *</label>
                        <textarea class="form-control" id="pausa-motivo-custom" rows="3" 
                                  placeholder="Describe el motivo de la pausa..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota:</strong> Una vez pausada, podrás reanudar la tarea desde la sección "Gestión de Pausas y Repuestos" cuando el problema esté resuelto.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="confirmar-pausa">
                    <i class="fas fa-pause me-2"></i>Confirmar Pausa
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Gestionar Repuestos -->
<div class="modal fade" id="repuestosModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-repuestos-custom">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-boxes me-2"></i>
                    Gestión de Repuestos - <span id="modal-placa-repuestos"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs para navegar entre secciones -->
                <ul class="nav nav-tabs mb-3" id="repuestos-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-aprobados" data-bs-toggle="tab" data-bs-target="#pane-aprobados" type="button" role="tab">
                            <i class="fas fa-check-circle me-2"></i>Repuestos Aprobados
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-solicitar" data-bs-toggle="tab" data-bs-target="#pane-solicitar" type="button" role="tab">
                            <i class="fas fa-tools me-2"></i>Solicitar Nuevos Repuestos
                        </button>
                    </li>
                </ul>
                
                <!-- Contenido de los tabs -->
                <div class="tab-content" id="repuestos-tab-content">
                    <!-- Tab: Repuestos Aprobados -->
                    <div class="tab-pane fade show active" id="pane-aprobados" role="tabpanel">
                        <div id="repuestos-aprobados-lista">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                <p class="text-muted mt-2">Cargando repuestos aprobados...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Solicitar Nuevos Repuestos -->
                    <div class="tab-pane fade" id="pane-solicitar" role="tabpanel">
                        <form id="form-solicitar-repuestos-tareas">
                            <input type="hidden" id="solicitar-asignacion-id">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="solicitar-repuesto-select" class="form-label">Repuesto <span class="text-danger">*</span></label>
                                    <select class="form-select" id="solicitar-repuesto-select" name="repuesto_id" required>
                                        <option value="">Cargando repuestos...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="solicitar-cantidad" class="form-label">Cantidad <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="solicitar-cantidad" name="cantidad" min="1" value="1" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="solicitar-urgencia" class="form-label">Urgencia <span class="text-danger">*</span></label>
                                    <select class="form-select" id="solicitar-urgencia" name="urgencia" required>
                                        <option value="Baja">Baja</option>
                                        <option value="Media" selected>Media</option>
                                        <option value="Alta">Alta</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="solicitar-motivo" class="form-label">Motivo</label>
                                    <input type="text" class="form-control" id="solicitar-motivo" name="motivo" placeholder="Motivo de la solicitud">
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Nota:</strong> Si el repuesto solicitado no tiene stock disponible, la tarea se pausará automáticamente con el motivo "Sin stock de repuesto".
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-solicitar-repuesto-tareas" style="display: none;">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Registrar Uso/Devolución de Repuestos -->
<div class="modal fade" id="usoRepuestosModal" tabindex="-1">
    <div class="modal-dialog modal-tareas-custom modal-tareas-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white" id="modal-header-uso-repuestos">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-check me-2"></i>
                    <span id="modal-titulo-uso-repuestos">Registrar Uso de Repuestos</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-uso-repuestos">
                    <input type="hidden" id="uso-solicitud-id">
                    <input type="hidden" id="uso-tipo" value="uso"> <!-- uso o devolucion -->
                    
                    <div class="mb-3">
                        <label class="form-label">Repuesto</label>
                        <p class="form-control-plaintext fw-bold" id="info-repuesto-nombre"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cantidad Aprobada</label>
                        <p class="form-control-plaintext" id="info-cantidad-aprobada"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cantidad Usada</label>
                        <p class="form-control-plaintext text-success" id="info-cantidad-usada"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cantidad Devuelta</label>
                        <p class="form-control-plaintext text-info" id="info-cantidad-devuelta"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cantidad Disponible</label>
                        <p class="form-control-plaintext fw-bold text-primary" id="info-cantidad-disponible"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cantidad-accion" class="form-label">
                            <span id="label-cantidad-accion">Cantidad a Usar</span> *
                        </label>
                        <input type="number" class="form-control" id="cantidad-accion" min="1" required>
                        <small class="form-text text-muted" id="help-cantidad-accion">Ingrese la cantidad que desea usar</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones-uso" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones-uso" rows="3" placeholder="Observaciones sobre el uso/devolución..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardar-uso-repuestos">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Solicitud de Repuestos -->
<div class="modal fade" id="modalConfirmacionSolicitud" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-tareas-custom modal-tareas-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>
                    Solicitud Enviada
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 id="modal-confirmacion-titulo">Solicitud enviada correctamente</h5>
                    <p id="modal-confirmacion-mensaje" class="text-muted"></p>
                    <div id="modal-confirmacion-advertencia" class="alert alert-warning mt-3" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Nota:</strong> La tarea se ha pausado automáticamente debido a falta de stock.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="fas fa-check me-2"></i>Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Detalles de Avance -->
<div class="modal fade" id="detalleAvanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-tareas-custom modal-detalle-avance">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Detalles del Avance - <span id="modal-avance-numero"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalle-avance-contenido">
                    <!-- Los detalles se cargarán aquí -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>