<!-- Sección de Reportes Semanales -->
<section id="reportes-semanales-section" class="section">
    <div class="container-fluid">
        <!-- Filtros y Generación -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-week me-2"></i>
                            Generar Reporte Semanal
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="semana-reporte" class="form-label">Seleccionar Semana *</label>
                                <input type="week" class="form-control" id="semana-reporte" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button class="btn btn-primary w-100" id="btn-generar-reporte">
                                    <i class="fas fa-file-pdf me-2"></i>Generar Reporte
                                </button>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button class="btn btn-success w-100" id="btn-exportar-excel">
                                    <i class="fas fa-file-excel me-2"></i>Exportar Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen del Reporte -->
        <div class="row mb-4" id="resumen-reporte" style="display: none;">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Vehículos Atendidos</h6>
                        <h3 class="mb-0" id="vehiculos-atendidos">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Total Gastos</h6>
                        <h3 class="mb-0" id="total-gastos">$0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Repuestos Utilizados</h6>
                        <h3 class="mb-0" id="repuestos-utilizados">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Tiempo Promedio</h6>
                        <h3 class="mb-0" id="tiempo-promedio">0 días</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalles del Reporte -->
        <div class="row" id="detalles-reporte" style="display: none;">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Detalles del Reporte Semanal
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Tabs para diferentes secciones -->
                        <ul class="nav nav-tabs" id="tabs-reporte" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-vehiculos" data-bs-toggle="tab" 
                                        data-bs-target="#panel-vehiculos" type="button" role="tab">
                                    <i class="fas fa-car me-2"></i>Vehículos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-gastos" data-bs-toggle="tab" 
                                        data-bs-target="#panel-gastos" type="button" role="tab">
                                    <i class="fas fa-dollar-sign me-2"></i>Gastos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-repuestos" data-bs-toggle="tab" 
                                        data-bs-target="#panel-repuestos" type="button" role="tab">
                                    <i class="fas fa-tools me-2"></i>Repuestos
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content mt-3" id="tabContentReporte">
                            <!-- Panel Vehículos -->
                            <div class="tab-pane fade show active" id="panel-vehiculos" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="tabla-vehiculos-reporte" class="table table-striped table-hover" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Placa</th>
                                                <th>Vehículo</th>
                                                <th>Fecha Ingreso</th>
                                                <th>Fecha Salida</th>
                                                <th>Días en Taller</th>
                                                <th>Estado</th>
                                                <th>Servicio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Se cargarán dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Panel Gastos -->
                            <div class="tab-pane fade" id="panel-gastos" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="tabla-gastos-reporte" class="table table-striped table-hover" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Vehículo</th>
                                                <th>Tipo</th>
                                                <th>Concepto</th>
                                                <th>Costo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Se cargarán dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Panel Repuestos -->
                            <div class="tab-pane fade" id="panel-repuestos" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="tabla-repuestos-reporte" class="table table-striped table-hover" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Nombre</th>
                                                <th>Cantidad</th>
                                                <th>Precio Unitario</th>
                                                <th>Total</th>
                                                <th>Vehículo</th>
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
