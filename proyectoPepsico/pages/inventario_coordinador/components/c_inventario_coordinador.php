<!-- Sección de Inventario para Coordinador de Zona -->
<section id="inventario-coordinador-section" class="section">
    <div class="container-fluid">
        <!-- Estadísticas Rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Repuestos</h6>
                                <h3 class="mb-0" id="total-repuestos">0</h3>
                            </div>
                            <i class="fas fa-boxes fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Stock Bajo</h6>
                                <h3 class="mb-0" id="stock-bajo">0</h3>
                            </div>
                            <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Sin Stock</h6>
                                <h3 class="mb-0" id="sin-stock">0</h3>
                            </div>
                            <i class="fas fa-times-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Valor Total</h6>
                                <h3 class="mb-0" id="valor-total">$0</h3>
                            </div>
                            <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                        </div>
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
                                <label for="filtro-categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="filtro-categoria">
                                    <option value="">Todas las categorías</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filtro-estado" class="form-label">Estado Stock</label>
                                <select class="form-select" id="filtro-estado">
                                    <option value="">Todos</option>
                                    <option value="normal">Stock Normal</option>
                                    <option value="bajo">Stock Bajo</option>
                                    <option value="sin">Sin Stock</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="filtro-busqueda" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="filtro-busqueda" placeholder="Código, nombre...">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-primary w-100" id="btn-exportar">
                                    <i class="fas fa-file-excel me-2"></i>Exportar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Inventario -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Inventario de Repuestos
                        </h4>
                        <button class="btn btn-sm btn-primary" id="btn-actualizar-inventario">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabla-inventario" class="table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Stock</th>
                                        <th>Stock Mínimo</th>
                                        <th>Precio Unitario</th>
                                        <th>Valor Total</th>
                                        <th>Estado</th>
                                        <th>Última Actualización</th>
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

<!-- Modal para Ver Movimientos -->
<div class="modal fade" id="modal-movimientos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Movimientos de Stock - <span id="modal-repuesto-nombre"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tabla-movimientos" class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Stock Anterior</th>
                                <th>Stock Nuevo</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
