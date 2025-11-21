<div class="row">
    <!-- Panel de Control Principal -->
    <div class="col-md-4">
        <!-- Tarjeta de Operación -->
        <div class="card guardia-card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-traffic-light me-2"></i>Control de Acceso</h5>
            </div>
            <div class="card-body">
                <!-- Selector de Operación -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Tipo de Operación</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="tipoOperacion" id="ingreso" value="ingreso" checked>
                        <label class="btn btn-outline-success" for="ingreso">
                            <i class="fas fa-sign-in-alt me-2"></i>Ingreso
                        </label>
                        
                        <input type="radio" class="btn-check" name="tipoOperacion" id="salida" value="salida">
                        <label class="btn btn-outline-warning" for="salida">
                            <i class="fas fa-sign-out-alt me-2"></i>Salida
                        </label>
                    </div>
                </div>

                <!-- Búsqueda por Placa -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Placa del Vehículo</label>
                    <div class="input-group">
                        <input type="text" class="form-control text-uppercase" id="placaBusqueda" 
                               placeholder="EJ: ABC123" maxlength="10" autocomplete="off">
                        <button class="btn btn-primary" type="button" id="btnBuscarPlaca">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Información Rápida del Estado -->
                <div class="alert alert-info py-2">
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="fw-bold">Vehículos Activos</small>
                            <div class="h5 mb-0" id="vehiculosActivos">0</div>
                        </div>
                        <div class="col-6">
                            <small class="fw-bold">Ingresos Hoy</small>
                            <div class="h5 mb-0" id="ingresosHoy">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Acciones Rápidas -->
        <div class="card guardia-card mt-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-success btn-action" id="btnProcesarIngreso" disabled>
                        <i class="fas fa-check-circle me-2"></i>Confirmar Ingreso
                    </button>
                    <button class="btn btn-warning btn-action" id="btnProcesarSalida" disabled>
                        <i class="fas fa-check-circle me-2"></i>Confirmar Salida
                    </button>
                    <button class="btn btn-danger btn-action" id="btnReportarNovedad" disabled>
                        <i class="fas fa-exclamation-triangle me-2"></i>Reportar Novedad
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de Información y Documentación -->
    <div class="col-md-8">
        <!-- Información del Vehículo -->
        <div class="card guardia-card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-car me-2"></i>Información del Vehículo</h5>
            </div>
            <div class="card-body">
                <div id="sinResultados" class="text-center py-5">
                    <i class="fas fa-car fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Busque un vehículo por placa</h5>
                    <p class="text-muted small">Seleccione tipo de operación (Ingreso/Salida) e ingrese la placa</p>
                </div>

                <div id="conResultados" style="display: none;">
                    <!-- Estado de Operación -->
                    <div class="alert alert-warning" id="alertOperacion">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="textoOperacion">Preparado para registrar INGRESO del vehículo</span>
                        </div>
                    </div>

                    <!-- Información Básica -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="section-title">Datos del Vehículo</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="fw-bold" width="40%">Placa:</td>
                                    <td id="infoPlaca" class="fw-bold text-primary">-</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Estado:</td>
                                    <td id="infoEstado">-</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Fecha Ingreso:</td>
                                    <td id="infoFechaIngreso">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="section-title">Información del Conductor</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="fw-bold" width="40%">Nombre:</td>
                                    <td id="infoConductorNombre">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Documentación Fotográfica -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="section-title">Registro Fotográfico <small class="text-muted">(Obligatorio)</small></h6>
                            <div class="alert alert-info py-2">
                                <small>
                                    <i class="fas fa-camera me-1"></i>
                                    <span id="textoDocumentacion">
                                        Documente el estado actual del vehículo con fotografías
                                    </span>
                                </small>
                            </div>
                            
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <button class="btn btn-outline-primary btn-sm" onclick="controlIngresoApp.iniciarCapturaFoto('frontal')">
                                    <i class="fas fa-camera me-1"></i>Vista Frontal
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="controlIngresoApp.iniciarCapturaFoto('lateral-izq')">
                                    <i class="fas fa-camera me-1"></i>Lateral Izquierdo
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="controlIngresoApp.iniciarCapturaFoto('lateral-der')">
                                    <i class="fas fa-camera me-1"></i>Lateral Derecho
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="controlIngresoApp.iniciarCapturaFoto('trasera')">
                                    <i class="fas fa-camera me-1"></i>Vista Trasera
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="controlIngresoApp.iniciarCapturaFoto('interior')">
                                    <i class="fas fa-camera me-1"></i>Interior
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="controlIngresoApp.iniciarCapturaFoto('daños')">
                                    <i class="fas fa-camera me-1"></i>Daños/Detalles
                                </button>
                            </div>

                            <!-- Galería de Fotos Capturadas -->
                            <div id="galeriaFotos" class="mt-3">
                                <div class="foto-vacia" id="sinFotos">
                                    <i class="fas fa-images fa-2x text-muted mb-2"></i>
                                    <p class="text-muted small">No hay fotos capturadas</p>
                                </div>
                                <div class="row g-2" id="listaFotos" style="display: none;">
                                    <!-- Las fotos capturadas aparecerán aquí -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones del Guardia -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="section-title">Observaciones del Guardia</h6>
                            <textarea class="form-control" id="observacionesGuardia" rows="2" 
                                      placeholder="Observaciones relevantes sobre el estado del vehículo..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Novedades Recientes -->
        <div class="card guardia-card mt-3">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Actividad Reciente</h5>
            </div>
            <div class="card-body p-0">
                <div id="listaNovedades" class="novedades-list">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-info-circle me-2"></i>No hay actividad reciente
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Captura de Fotos -->
<div class="modal fade" id="modalFoto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFotoTitulo">Capturar Fotografía</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="camera-container">
                            <video id="video" width="100%" autoplay></video>
                            <canvas id="canvas" style="display: none;"></canvas>
                        </div>
                        <div class="text-center mt-2">
                            <small class="text-muted" id="tipoFotoTexto">Vista Frontal</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="foto-preview-container">
                            <div id="fotoPreview" class="text-center mb-3">
                                <p class="text-muted">La foto aparecerá aquí después de capturar</p>
                            </div>
                            <div class="foto-info">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <span id="instruccionesFoto">
                                        Asegure que toda el área del vehículo sea visible en la foto
                                    </span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnCapturar">
                    <i class="fas fa-camera me-1"></i>Capturar Foto
                </button>
                <button type="button" class="btn btn-success" id="btnGuardarFoto" style="display: none;">
                    <i class="fas fa-save me-1"></i>Guardar Foto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Reportar Novedad -->
<div class="modal fade" id="modalNovedad" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Reportar Novedad</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo de Novedad</label>
                    <select class="form-select" id="tipoNovedad">
                        <option value="Daño vehiculo">Daño en Vehículo</option>
                        <option value="Documentacion">Documentación Incompleta</option>
                        <option value="Comportamiento">Comportamiento Inadecuado</option>
                        <option value="Seguridad">Incidente de Seguridad</option>
                        <option value="Accidente">Accidente en Patio</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Descripción Detallada</label>
                    <textarea class="form-control" id="descripcionNovedad" rows="4" 
                              placeholder="Describa la novedad o incidente con todos los detalles relevantes..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Nivel de Gravedad</label>
                    <select class="form-select" id="gravedadNovedad">
                        <option value="Baja">Baja - Sin afectación operativa</option>
                        <option value="Media" selected>Media - Afectación moderada</option>
                        <option value="Alta">Alta - Afectación significativa</option>
                        <option value="Critica">Crítica - Paro operativo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnGuardarNovedad">
                    <i class="fas fa-paper-plane me-1"></i>Reportar Novedad
                </button>
            </div>
        </div>
    </div>
</div>