<!-- Sección de Control de Gastos y Vehículos -->
<section id="control-gastos-section" class="section">
    <div class="container-fluid">
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Gastos del Mes</h6>
                        <h3 class="mb-0" id="gastos-mes">$0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Talleres Internos</h6>
                        <h3 class="mb-0" id="talleres-internos">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Talleres Externos</h6>
                        <h3 class="mb-0" id="talleres-externos">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Vehículos en Taller</h6>
                        <h3 class="mb-0" id="vehiculos-taller">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="filtro-taller" class="form-label">Tipo de Taller</label>
                                <select class="form-select" id="filtro-taller">
                                    <option value="">Todos</option>
                                    <option value="interno">Interno</option>
                                    <option value="externo">Externo</option>
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
                                <button class="btn btn-primary w-100" id="btn-filtrar">
                                    <i class="fas fa-filter me-2"></i>Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs para Talleres Internos y Externos -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="tabs-gastos" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-internos" data-bs-toggle="tab" 
                                        data-bs-target="#panel-internos" type="button" role="tab">
                                    <i class="fas fa-building me-2"></i>Talleres Internos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-externos" data-bs-toggle="tab" 
                                        data-bs-target="#panel-externos" type="button" role="tab">
                                    <i class="fas fa-store me-2"></i>Talleres Externos
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="tabContentGastos">
                            <!-- Panel Talleres Internos -->
                            <div class="tab-pane fade show active" id="panel-internos" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="tabla-gastos-internos" class="table table-striped table-hover" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Vehículo</th>
                                                <th>Placa</th>
                                                <th>Servicio</th>
                                                <th>Repuestos</th>
                                                <th>Costo Repuestos</th>
                                                <th>Costo Mano de Obra</th>
                                                <th>Total</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Se cargarán dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Panel Talleres Externos -->
                            <div class="tab-pane fade" id="panel-externos" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="tabla-gastos-externos" class="table table-striped table-hover" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Vehículo</th>
                                                <th>Placa</th>
                                                <th>Taller</th>
                                                <th>Servicio</th>
                                                <th>Costo Total</th>
                                                <th>Estado</th>
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
        </div>
    </div>
</section>

<!-- Modal para Registrar Gasto Externo -->
<div class="modal fade" id="modal-gasto-externo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Registrar Gasto en Taller Externo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-gasto-externo">
                    <div class="mb-3">
                        <label for="vehiculo-gasto" class="form-label">Vehículo *</label>
                        <select class="form-select" id="vehiculo-gasto" required>
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="taller-externo" class="form-label">Taller Externo *</label>
                        <input type="text" class="form-control" id="taller-externo" required>
                    </div>
                    <div class="mb-3">
                        <label for="servicio-gasto" class="form-label">Servicio Realizado *</label>
                        <textarea class="form-control" id="servicio-gasto" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="costo-gasto" class="form-label">Costo Total *</label>
                        <input type="number" class="form-control" id="costo-gasto" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="fecha-gasto" class="form-label">Fecha *</label>
                        <input type="date" class="form-control" id="fecha-gasto" required>
                    </div>
                    <div class="mb-3">
                        <label for="observaciones-gasto" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones-gasto" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-gasto">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>
