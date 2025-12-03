<!-- Sección de Repuestos -->
<section id="repuestos-section" class="section">
    <div class="repuestos-container">
        <div class="repuestos-layout">
            <!-- Contenido principal con tabla -->
            <div class="repuestos-main-content">
                <div class="card h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-boxes me-2"></i>
                            Inventario de Repuestos
                        </h4>
                        <?php if ($usuario_rol === 'Asistente de Repuestos' || $usuario_rol === 'Administrador' || $usuario_rol === 'Jefe de Taller'): ?>
                        <div class="card-actions">
                            <button class="btn btn-outline-light btn-sm" id="btn-refresh" title="Actualizar">
                                <i class="fas fa-sync-alt me-1"></i> Actualizar
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <!-- Contenedor de tabla con padding -->
                        <div id="tabla-container" class="tabla-container">
                            <table id="repuestos-table" class="table table-striped table-hover table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-left">Nombre</th>
                                        <th class="text-left">Categoría</th>
                                        <th class="text-right">Stock</th>
                                        <th class="text-right">Stock Mín.</th>
                                        <th class="text-right">Precio Unit.</th>
                                        <th class="text-right">Valor Total</th>
                                        <th class="text-left">Estado</th>
                                        <th class="text-center">Acciones</th>
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
            
            <!-- Aside con resumen de inventario -->
            <aside class="repuestos-aside">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Resumen de Inventario
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="resumen-inventario">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<!-- Modal para Gestión de Repuestos -->
<div class="modal fade" id="repuestoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="repuestoModalTitle">
                    <i class="fas fa-cog me-2"></i>
                    Nuevo Repuesto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="repuesto-form">
                    <input type="hidden" id="repuesto-id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="repuesto-codigo" class="form-label">Código *</label>
                                <input type="text" class="form-control" id="repuesto-codigo" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="repuesto-nombre" class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="repuesto-nombre" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="repuesto-categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="repuesto-categoria">
                                    <option value="Motor">Motor</option>
                                    <option value="Transmisión">Transmisión</option>
                                    <option value="Frenos">Frenos</option>
                                    <option value="Suspensión">Suspensión</option>
                                    <option value="Eléctrico">Eléctrico</option>
                                    <option value="Carrocería">Carrocería</option>
                                    <option value="Otros">Otros</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="repuesto-stock" class="form-label">Stock *</label>
                                <input type="number" class="form-control" id="repuesto-stock" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="repuesto-precio" class="form-label">Precio Unitario *</label>
                                <input type="number" class="form-control" id="repuesto-precio" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="repuesto-minimo" class="form-label">Stock Mínimo</label>
                                <input type="number" class="form-control" id="repuesto-minimo" min="0" value="5">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="repuesto-descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="repuesto-descripcion" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardar-repuesto">
                    <i class="fas fa-save me-2"></i>Guardar Repuesto
                </button>
            </div>
        </div>
    </div>
</div>
