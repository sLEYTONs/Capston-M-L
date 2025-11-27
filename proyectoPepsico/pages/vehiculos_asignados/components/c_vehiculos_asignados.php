<!-- Sección de Vehículos Asignados -->
<section id="vehiculos-asignados-section" class="section">
    <div class="container-fluid">
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title mb-0">Vehículos Asignados</h6>
                        <h3 class="mb-0" id="total-vehiculos">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Vehículos Asignados -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-car me-2"></i>
                            Vehículos Asignados
                        </h4>
                        <button class="btn btn-sm btn-primary" id="btn-actualizar-vehiculos">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabla-vehiculos-asignados" class="table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Placa</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Tipo</th>
                                        <th>Kilometraje</th>
                                        <th>Fecha Asignación</th>
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

<!-- Modal para Ver Detalles -->
<div class="modal fade" id="modal-detalles-vehiculo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    Detalles del Vehículo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalles-vehiculo">
                    <!-- Se cargarán dinámicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
