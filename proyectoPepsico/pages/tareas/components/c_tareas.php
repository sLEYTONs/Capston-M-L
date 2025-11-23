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
    <div class="modal-dialog modal-lg">
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i>
                    Historial de Avances - <span id="modal-placa-historial"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="historial-avances">
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
    <div class="modal-dialog modal-xl">
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
    <div class="modal-dialog modal-xl">
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
    <div class="modal-dialog">
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