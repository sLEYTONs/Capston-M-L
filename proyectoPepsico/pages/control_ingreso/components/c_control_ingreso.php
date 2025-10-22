<div class="row">
    <!-- Panel de Búsqueda Rápida -->
    <div class="col-md-4">
        <div class="card guardia-card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Búsqueda Rápida</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Buscar por Placa</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="placaBusqueda" placeholder="Ej: ABC123" maxlength="10">
                        <button class="btn btn-primary" type="button" id="btnBuscarPlaca">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Buscar por Cédula Conductor</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="cedulaBusqueda" placeholder="Ej: 123456789" maxlength="15">
                        <button class="btn btn-primary" type="button" id="btnBuscarCedula">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Estado del Patio -->
        <div class="card guardia-card mt-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estado del Patio</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="stat-box">
                            <h3 id="vehiculosActivos">0</h3>
                            <small>Vehículos Activos</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-box">
                            <h3 id="ingresosHoy">0</h3>
                            <small>Ingresos Hoy</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Principal de Información -->
    <div class="col-md-8">
        <div class="card guardia-card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-car me-2"></i>Información del Vehículo</h5>
            </div>
            <div class="card-body">
                <div id="sinResultados" class="text-center py-4">
                    <i class="fas fa-car fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Ingrese una placa o cédula para buscar el vehículo</p>
                </div>

                <div id="conResultados" style="display: none;">
                    <!-- Información Básica -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="section-title">Información del Vehículo</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Placa:</strong></td>
                                    <td id="infoPlaca">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo:</strong></td>
                                    <td id="infoTipo">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Marca/Modelo:</strong></td>
                                    <td id="infoMarcaModelo">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Color:</strong></td>
                                    <td id="infoColor">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Año:</strong></td>
                                    <td id="infoAnio">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="section-title">Estado Actual</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Estado Ingreso:</strong></td>
                                    <td><span class="badge" id="badgeEstadoIngreso">-</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Combustible:</strong></td>
                                    <td id="infoCombustible">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Kilometraje:</strong></td>
                                    <td id="infoKilometraje">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Ingreso:</strong></td>
                                    <td id="infoFechaIngreso">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Información del Conductor -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="section-title">Información del Conductor</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Nombre:</strong></td>
                                        <td id="infoConductorNombre">-</td>
                                        <td><strong>Cédula:</strong></td>
                                        <td id="infoConductorCedula">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Teléfono:</strong></td>
                                        <td id="infoConductorTelefono">-</td>
                                        <td><strong>Licencia:</strong></td>
                                        <td id="infoLicencia">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Empresa:</strong></td>
                                        <td colspan="3" id="infoEmpresa">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Propósito y Observaciones -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="section-title">Propósito de Visita</h6>
                            <div class="p-3 bg-light rounded">
                                <strong id="infoProposito">-</strong>
                                <div class="mt-2">
                                    <small>Área: </small><span id="infoArea" class="badge bg-secondary">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="section-title">Observaciones</h6>
                            <div class="p-3 bg-light rounded" style="min-height: 80px;">
                                <span id="infoObservaciones" class="text-muted">Sin observaciones</span>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones del Guardia -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="section-title">Acciones</h6>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-success" id="btnRegistrarIngreso">
                                    <i class="fas fa-sign-in-alt me-2"></i>Registrar Ingreso
                                </button>
                                <button class="btn btn-warning" id="btnRegistrarSalida">
                                    <i class="fas fa-sign-out-alt me-2"></i>Registrar Salida
                                </button>
                                <button class="btn btn-info" id="btnTomarFoto">
                                    <i class="fas fa-camera me-2"></i>Tomar Fotografía
                                </button>
                                <button class="btn btn-danger" id="btnReportarNovedad">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Reportar Novedad
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Novedades Recientes -->
        <div class="card guardia-card mt-3">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Novedades Recientes</h5>
            </div>
            <div class="card-body">
                <div id="listaNovedades" class="novedades-list">
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-info-circle me-2"></i>No hay novedades recientes
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Tomar Fotografía -->
<div class="modal fade" id="modalFoto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registro Fotográfico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="camera-container">
                            <video id="video" width="100%" autoplay></video>
                            <canvas id="canvas" style="display: none;"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="fotoPreview" class="text-center">
                            <p class="text-muted">La foto aparecerá aquí</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnCapturar">Capturar Foto</button>
                <button type="button" class="btn btn-success" id="btnGuardarFoto" style="display: none;">Guardar Foto</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Reportar Novedad -->
<div class="modal fade" id="modalNovedad" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reportar Novedad/Incidente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tipo de Novedad</label>
                    <select class="form-select" id="tipoNovedad">
                        <option value="Daño vehiculo">Daño en Vehículo</option>
                        <option value="Documentacion">Problema con Documentación</option>
                        <option value="Comportamiento">Comportamiento Inadecuado</option>
                        <option value="Seguridad">Incidente de Seguridad</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcionNovedad" rows="4" placeholder="Describa la novedad o incidente..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Gravedad</label>
                    <select class="form-select" id="gravedadNovedad">
                        <option value="Baja">Baja</option>
                        <option value="Media">Media</option>
                        <option value="Alta">Alta</option>
                        <option value="Critica">Crítica</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnGuardarNovedad">Reportar Novedad</button>
            </div>
        </div>
    </div>
</div>