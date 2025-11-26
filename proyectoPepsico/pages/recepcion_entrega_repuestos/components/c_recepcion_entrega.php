<!-- Sección de Recepción y Entrega de Repuestos -->
<section id="recepcion-entrega-section" class="section">
    <div class="container-fluid">
        <div class="row">
            <!-- Recepción de Repuestos -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-box-open me-2"></i>
                            Recepción de Repuestos
                        </h4>
                    </div>
                    <div class="card-body">
                        <form id="form-recepcion">
                            <div class="mb-3">
                                <label for="proveedor" class="form-label">Proveedor *</label>
                                <select class="form-select" id="proveedor" required>
                                    <option value="">Seleccionar proveedor...</option>
                                    <!-- Se cargarán dinámicamente -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="factura" class="form-label">Número de Factura *</label>
                                <input type="text" class="form-control" id="factura" required>
                            </div>
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
                            <div class="mb-3">
                                <label for="repuestos-recepcion" class="form-label">Repuestos Recibidos *</label>
                                <div id="lista-repuestos-recepcion">
                                    <p class="text-muted">Cargando repuestos...</p>
                                </div>
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

            <!-- Entrega de Repuestos -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-hand-holding me-2"></i>
                            Entrega de Repuestos
                        </h4>
                    </div>
                    <div class="card-body">
                        <form id="form-entrega">
                            <div class="mb-3">
                                <label for="vehiculo" class="form-label">Vehículo *</label>
                                <select class="form-select" id="vehiculo" required>
                                    <option value="">Seleccionar vehículo...</option>
                                    <!-- Se cargarán dinámicamente -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="mecanico" class="form-label">Mecánico *</label>
                                <select class="form-select" id="mecanico" required>
                                    <option value="">Seleccionar mecánico...</option>
                                    <!-- Se cargarán dinámicamente -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="repuestos-entrega" class="form-label">Repuestos a Entregar *</label>
                                <div id="lista-repuestos-entrega">
                                    <!-- Se cargarán dinámicamente -->
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="observaciones-entrega" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones-entrega" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Registrar Entrega
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historial de Recepciones y Entregas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-history me-2"></i>
                            Historial de Recepciones y Entregas
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabla-historial" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Proveedor/Vehículo</th>
                                        <th>Repuestos</th>
                                        <th>Cantidad</th>
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
</section>

