<div class="row">
    <div class="col-12">
        <!-- Tarjeta de Estadísticas Generales -->
        <div class="card base-datos-card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Estadísticas del Sistema</h5>
            </div>
            <div class="card-body">
                <div class="row" id="stats-container">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="total-registros">0</h3>
                                <p>Total Registros</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-car"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="vehiculos-activos">0</h3>
                                <p>Vehículos Activos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="marcas-unicas">0</h3>
                                <p>Marcas Únicas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="empresas-registradas">0</h3>
                                <p>Empresas Registradas</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Búsqueda y Filtros -->
        <div class="card base-datos-card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Búsqueda y Filtros Avanzados</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Búsqueda Global</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="global-search" placeholder="Buscar en todos los campos...">
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" id="filter-status">
                            <option value="">Todos</option>
                            <option value="Ingresado">Ingresado</option>
                            <option value="Asignado">Asignado</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Completado">Completado</option>
                            <option value="active">Activo (antiguo)</option>
                            <option value="inactive">Inactivo (antiguo)</option>
                        </select>
                    </div>
                    <!-- Filtro por empresa eliminado - columna no existe -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Marca</label>
                        <select class="form-select" id="filter-brand">
                            <option value="">Todas las marcas</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button class="btn btn-primary me-2" id="apply-filters">
                            <i class="fas fa-filter me-2"></i>Aplicar Filtros
                        </button>
                        <button class="btn btn-outline-secondary" id="reset-filters">
                            <i class="fas fa-redo me-2"></i>Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación por Pestañas -->
        <div class="card base-datos-card mt-4">
            <div class="card-body p-0">
                <ul class="nav nav-tabs base-datos-tabs" id="databaseTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="vehiculos-tab" data-bs-toggle="tab" data-bs-target="#vehiculos" type="button" role="tab">
                            <i class="fas fa-truck me-2"></i>Vehículos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="marcas-tab" data-bs-toggle="tab" data-bs-target="#marcas" type="button" role="tab">
                            <i class="fas fa-tags me-2"></i>Marcas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="empresas-tab" data-bs-toggle="tab" data-bs-target="#empresas" type="button" role="tab">
                            <i class="fas fa-building me-2"></i>Empresas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="conductores-tab" data-bs-toggle="tab" data-bs-target="#conductores" type="button" role="tab">
                            <i class="fas fa-users me-2"></i>Conductores
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="graficos-tab" data-bs-toggle="tab" data-bs-target="#graficos" type="button" role="tab">
                            <i class="fas fa-chart-pie me-2"></i>Gráficos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="exportar-tab" data-bs-toggle="tab" data-bs-target="#exportar" type="button" role="tab">
                            <i class="fas fa-download me-2"></i>Exportar
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-3" id="databaseTabContent">
                    <!-- Pestaña Vehículos -->
                    <div class="tab-pane fade show active" id="vehiculos" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Registros de Vehículos</h5>
                            <button class="btn btn-success btn-sm" id="refresh-vehicles">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table id="vehiculos-table" class="table table-striped table-hover" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Placa</th>
                                        <th>Marca/Modelo</th>
                                        <th>Conductor</th>
                                        <th>Estado</th>
                                        <th>FechaIngreso</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pestaña Marcas -->
                    <div class="tab-pane fade" id="marcas" role="tabpanel">
                        <h5 class="mb-3"><i class="fas fa-tags me-2"></i>Análisis por Marcas</h5>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="table-responsive">
                                    <table id="marcas-table" class="table table-striped table-hover" style="width:100%">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Marca</th>
                                                <th>Total Vehículos</th>
                                                <th>Activos</th>
                                                <th>Porcentaje</th>
                                                <th>Último Ingreso</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="chart-container">
                                    <canvas id="marcas-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña Empresas - Deshabilitada (columna eliminada) -->
                    <div class="tab-pane fade" id="empresas" role="tabpanel">
                        <h5 class="mb-3"><i class="fas fa-building me-2"></i>Análisis por Empresas (No disponible)</h5>
                        <p class="text-muted">Esta funcionalidad ha sido deshabilitada debido a que la columna EmpresaNombre fue eliminada de la base de datos.</p>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="table-responsive">
                                    <table id="empresas-table" class="table table-striped table-hover" style="width:100%">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Total Vehículos</th>
                                                <th>Conductores</th>
                                                <th>Activos</th>
                                                <th>Frecuencia</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="chart-container">
                                    <canvas id="empresas-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña Conductores -->
                    <div class="tab-pane fade" id="conductores" role="tabpanel">
                        <h5 class="mb-3"><i class="fas fa-users me-2"></i>Registros de Conductores</h5>
                        <div class="table-responsive">
                            <table id="conductores-table" class="table table-striped table-hover" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Teléfono</th>
                                        <th>Vehículos</th>
                                        <th>Última Visita</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pestaña Gráficos -->
                    <div class="tab-pane fade" id="graficos" role="tabpanel">
                        <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Gráficos y Analíticas</h5>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Distribución por Marcas</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="chart-marcas" height="250"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Distribución por Empresas</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="chart-empresas" height="250"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Estados de Vehículos</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="chart-estados" height="250"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Ingresos por Mes</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="chart-mensual" height="250"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña Exportar -->
                    <div class="tab-pane fade" id="exportar" role="tabpanel">
                        <h5 class="mb-3"><i class="fas fa-download me-2"></i>Exportar Datos</h5>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-file-export me-2"></i>Exportación Completa</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <p class="text-muted">Exporta todos los datos del sistema</p>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-primary" id="export-csv-completo">
                                                <i class="fas fa-file-csv me-2"></i>CSV Completo
                                            </button>
                                            <button class="btn btn-warning" id="export-excel-completo">
                                                <i class="fas fa-file-excel me-2"></i>Excel Completo
                                            </button>
                                            <button class="btn btn-info" id="export-json-completo">
                                                <i class="fas fa-file-code me-2"></i>JSON Completo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Exportaciones Específicas</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <p class="text-muted">Exporta datos por categoría</p>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-primary" id="export-vehiculos">
                                                <i class="fas fa-truck me-2"></i>Solo Vehículos
                                            </button>
                                            <button class="btn btn-outline-primary" id="export-marcas">
                                                <i class="fas fa-tags me-2"></i>Solo Marcas
                                            </button>
                                            <button class="btn btn-outline-primary" id="export-empresas">
                                                <i class="fas fa-building me-2"></i>Solo Empresas
                                            </button>
                                            <button class="btn btn-outline-primary" id="export-conductores">
                                                <i class="fas fa-users me-2"></i>Solo Conductores
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalles de Vehículo -->
<div class="modal fade" id="modalDetalleVehiculo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Vehículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalle-vehiculo-content">
                <!-- Contenido cargado dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Notificación -->
<div id="notification" class="notification-alert"></div>