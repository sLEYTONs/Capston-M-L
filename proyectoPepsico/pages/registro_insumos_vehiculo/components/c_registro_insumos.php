<!-- Sección de Registro de Insumos por Vehículo -->
<section id="registro-insumos-section" class="section">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-clipboard-list me-2"></i>
                            Registro de Insumos por Vehículo
                        </h4>
                        <button class="btn btn-primary btn-sm" id="btn-nuevo-registro">
                            <i class="fas fa-plus me-1"></i> Nuevo Registro
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Filtros -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="filtro-vehiculo" class="form-label">Filtrar por Vehículo</label>
                                <select class="form-select" id="filtro-vehiculo">
                                    <option value="">Todos los vehículos</option>
                                    <!-- Se cargarán dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filtro-fecha-desde" class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" id="filtro-fecha-desde">
                            </div>
                            <div class="col-md-3">
                                <label for="filtro-fecha-hasta" class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" id="filtro-fecha-hasta">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-secondary w-100" id="btn-filtrar">
                                    <i class="fas fa-filter me-1"></i> Filtrar
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de Registros -->
                        <div class="table-responsive">
                            <table id="tabla-insumos" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Vehículo</th>
                                        <th>Placa</th>
                                        <th>Repuestos Utilizados</th>
                                        <th>Cantidad Total</th>
                                        <th>Mecánico</th>
                                        <th>Observaciones</th>
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

<!-- Modal para Nuevo Registro -->
<div class="modal fade" id="modal-registro" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-registro-title">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Nuevo Registro de Insumos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-registro-insumos">
                    <input type="hidden" id="registro-id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vehiculo-registro" class="form-label">Vehículo *</label>
                                <select class="form-select" id="vehiculo-registro" required>
                                    <option value="">Seleccionar vehículo...</option>
                                    <!-- Se cargarán dinámicamente -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mecanico-registro" class="form-label">Mecánico *</label>
                                <select class="form-select" id="mecanico-registro" required>
                                    <option value="">Seleccionar mecánico...</option>
                                    <!-- Se cargarán dinámicamente -->
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Repuestos Utilizados *</label>
                        <div id="lista-repuestos-registro">
                            <!-- Se cargarán dinámicamente -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn-agregar-repuesto">
                            <i class="fas fa-plus me-1"></i> Agregar Repuesto
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="observaciones-registro" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones-registro" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-registro">
                    <i class="fas fa-save me-2"></i>Guardar Registro
                </button>
            </div>
        </div>
    </div>
</div>

