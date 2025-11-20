<div class="row">
    <!-- Panel de Estadísticas -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Órdenes de Trabajo (OTs)</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12 text-end">
                        <button class="btn btn-primary" id="btn-nueva-ot" data-bs-toggle="modal" data-bs-target="#modalNuevaOT">
                            <i class="fas fa-plus me-2"></i>Nueva Orden de Trabajo
                        </button>
                    </div>
                </div>

                <!-- Filtros de Búsqueda -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="filtro-placa" class="form-label">Buscar por Placa</label>
                        <input type="text" class="form-control" id="filtro-placa" placeholder="Ej: ABCD60">
                    </div>
                    <div class="col-md-3">
                        <label for="filtro-numero-ot" class="form-label">Buscar por Número OT</label>
                        <input type="text" class="form-control" id="filtro-numero-ot" placeholder="Ej: OT-202401-0001">
                    </div>
                    <div class="col-md-3">
                        <label for="filtro-estado" class="form-label">Estado</label>
                        <select class="form-select" id="filtro-estado">
                            <option value="">Todos</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Completada">Completada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button class="btn btn-secondary" id="btn-aplicar-filtros">
                                <i class="fas fa-search me-2"></i>Buscar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de OTs -->
                <div class="table-responsive">
                    <table id="tabla-ots" class="table table-striped table-hover" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Número OT</th>
                                <th>Placa</th>
                                <th>Vehículo</th>
                                <th>Fecha Creación</th>
                                <th>Estado</th>
                                <th>Tipo Trabajo</th>
                                <th>Documentación</th>
                                <th>Fotos</th>
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

<!-- Modal para Nueva OT -->
<div class="modal fade" id="modalNuevaOT" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Orden de Trabajo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-nueva-ot">
                    <!-- Buscador de Vehículo -->
                    <div class="mb-3">
                        <label class="form-label">Buscar Vehículo por Placa <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control text-uppercase" id="buscador-placa-ot" 
                                   placeholder="Ingrese la placa" maxlength="10">
                            <button type="button" class="btn btn-primary" id="btn-buscar-placa-ot">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                        <small class="text-muted">Busque el vehículo que ingresó a taller</small>
                        <input type="hidden" id="vehiculo-id-ot" name="vehiculo_id">
                        <div id="info-vehiculo-ot" class="alert alert-info mt-2" style="display: none;"></div>
                    </div>

                    <!-- Información del Trabajo -->
                    <div class="mb-3">
                        <label for="tipo-trabajo" class="form-label">Tipo de Trabajo <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipo-trabajo" name="tipo_trabajo" required>
                            <option value="">Seleccione un tipo</option>
                            <option value="Mantenimiento Preventivo">Mantenimiento Preventivo</option>
                            <option value="Mantenimiento Correctivo">Mantenimiento Correctivo</option>
                            <option value="Reparación">Reparación</option>
                            <option value="Revisión Técnica">Revisión Técnica</option>
                            <option value="Diagnóstico">Diagnóstico</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion-trabajo" class="form-label">Descripción del Trabajo <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="descripcion-trabajo" name="descripcion_trabajo" rows="3" 
                                  placeholder="Describa el trabajo a realizar..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="observaciones-ot" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones-ot" name="observaciones" rows="2" 
                                  placeholder="Observaciones adicionales..."></textarea>
                    </div>

                    <!-- Toma de Imágenes -->
                    <div class="mb-3">
                        <label class="form-label">Fotos del Vehículo</label>
                        <div class="border rounded p-3">
                            <input type="file" class="form-control" id="fotos-vehiculo" name="fotos[]" 
                                   accept="image/*" multiple>
                            <small class="text-muted">Puede seleccionar múltiples imágenes</small>
                            <div id="preview-fotos" class="mt-3 row"></div>
                        </div>
                    </div>

                    <!-- Documentos -->
                    <div class="mb-3">
                        <label class="form-label">Documentación Técnica</label>
                        <div class="border rounded p-3">
                            <input type="file" class="form-control" id="documentos-ot" name="documentos[]" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple>
                            <small class="text-muted">Formatos permitidos: PDF, DOC, DOCX, JPG, PNG</small>
                            <div id="preview-documentos" class="mt-3"></div>
                        </div>
                    </div>

                    <!-- Validación de Documentación -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="documentos-validados" name="documentos_validados" value="1">
                            <label class="form-check-label" for="documentos-validados">
                                Documentación técnica validada
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Crear OT
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver/Editar OT -->
<div class="modal fade" id="modalVerOT" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de Orden de Trabajo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenido-ot">
                <!-- Se cargará dinámicamente -->
            </div>
        </div>
    </div>
</div>

