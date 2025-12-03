<!-- Sección de Recepción de Repuestos -->
<section id="recepcion-repuestos-section" class="section">
    <div class="repuestos-container">
        <div class="repuestos-layout">
            <!-- Contenido principal -->
            <div class="repuestos-main-content">
                <div class="card h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-box-open me-2"></i>
                            Recepción de Repuestos
                        </h4>
                        <div class="card-actions">
                            <button class="btn btn-outline-light btn-sm" id="btn-refresh" title="Actualizar">
                                <i class="fas fa-sync-alt me-1"></i> Actualizar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="form-recepcion">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="proveedor" class="form-label">Proveedor *</label>
                                        <select class="form-select" id="proveedor" required>
                                            <option value="">Seleccionar proveedor...</option>
                                            <!-- Se cargarán dinámicamente -->
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="factura" class="form-label">Número de Factura *</label>
                                        <input type="text" class="form-control" id="factura" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fecha-recepcion" class="form-label">Fecha de Recepción *</label>
                                        <input type="datetime-local" class="form-control" id="fecha-recepcion" required>
                                        <script>
                                            // Establecer fecha actual por defecto
                                            document.addEventListener('DOMContentLoaded', function() {
                                                const fechaInput = document.getElementById('fecha-recepcion');
                                                if (fechaInput && !fechaInput.value) {
                                                    const now = new Date();
                                                    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                                                    fechaInput.value = now.toISOString().slice(0, 16);
                                                }
                                            });
                                        </script>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="repuestos-recepcion" class="form-label">Repuestos Recibidos *</label>
                                <button type="button" class="btn btn-primary btn-sm" id="btn-abrir-modal-repuestos" data-bs-toggle="modal" data-bs-target="#modal-seleccionar-repuestos">
                                    <i class="fas fa-plus me-2"></i>Agregar Repuestos
                                </button>
                                <div id="repuestos-seleccionados-recepcion" class="mt-2" style="display: none;">
                                    <!-- Los repuestos seleccionados se mostrarán solo en el modal -->
                                </div>
                                <small class="text-muted d-block mt-1" id="contador-repuestos">No hay repuestos agregados</small>
                            </div>
                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-2"></i>Registrar Recepción
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal para seleccionar repuestos -->
<div class="modal fade" id="modal-seleccionar-repuestos" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-boxes me-2"></i>
                    Seleccionar Repuestos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="select-repuesto-modal" class="form-label">Seleccionar Repuesto *</label>
                        <select class="form-select" id="select-repuesto-modal">
                            <option value="">Seleccionar repuesto...</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="cantidad-repuesto-modal" class="form-label">Cantidad *</label>
                        <input type="number" class="form-control" id="cantidad-repuesto-modal" min="1" value="1" placeholder="Cantidad">
                    </div>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" id="btn-agregar-repuesto-modal">
                        <i class="fas fa-plus me-2"></i>Agregar Repuesto
                    </button>
                </div>
                <hr>
                <div>
                    <h6 class="mb-3">Repuestos Agregados:</h6>
                    <div id="lista-repuestos-agregados-modal" class="list-group" style="max-height: 300px; overflow-y: auto;">
                        <p class="text-muted text-center py-3">No hay repuestos agregados</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de éxito -->
<div class="modal fade" id="modal-exito-recepcion" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>
                    Recepción Registrada
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <i class="fas fa-check-circle fa-4x text-success"></i>
                </div>
                <h5>¡Recepción registrada correctamente!</h5>
                <p class="text-muted mb-0" id="mensaje-exito-recepcion">Los repuestos han sido registrados y el stock ha sido actualizado.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="fas fa-check me-2"></i>Aceptar
                </button>
            </div>
        </div>
    </div>
</div>


