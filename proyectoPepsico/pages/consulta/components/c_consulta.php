<!-- Sección de Consulta -->
<section id="search-section" class="section">
    <div class="search-container">
        <h2>Consulta de Vehículos</h2>
        <div class="search-form">
            <div class="search-fields">
                <div class="input-group">
                    <input type="text" id="search-plate" placeholder="Buscar por placa..." class="search-input">
                    <button class="btn-clear" data-target="search-plate" title="Limpiar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="input-group">
                    <input type="text" id="search-driver" placeholder="Buscar por conductor..." class="search-input">
                    <button class="btn-clear" data-target="search-driver" title="Limpiar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="input-group">
                    <input type="date" id="search-date" placeholder="Buscar por fecha..." class="search-input">
                    <button class="btn-clear" data-target="search-date" title="Limpiar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <button class="btn btn-primary" id="search-btn">
                    <i class="fas fa-search me-2"></i>Buscar
                </button>
            </div>
        </div>
        <div class="results-container">
            <table id="results-table" class="results-table display" style="width:100%">
                <thead>
                    <tr>
                        <th>Placa</th>
                        <th>Vehículo</th>
                        <th>Conductor</th>
                        <th>Fecha Ingreso</th>
                        <th>Mecánico</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Los datos se cargarán via Ajax -->
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Modal para Asignar Mecánico -->
<div class="modal fade" id="asignarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tools me-2"></i>
                    Asignar Mecánico - <span id="modal-placa-asignar"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="asignar-form">
                    <input type="hidden" id="asignar-vehiculo-id">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="asignar-mecanico" class="form-label">Seleccionar Mecánico *</label>
                                <select class="form-select" id="asignar-mecanico" required>
                                    <option value="">Seleccionar mecánico...</option>
                                    <!-- Los mecánicos se cargarán dinámicamente -->
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="asignar-observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="asignar-observaciones" rows="3" 
                                  placeholder="Observaciones para el mecánico..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmar-asignacion">
                    <i class="fas fa-user-cog me-2"></i>Asignar Mecánico
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Seguimiento -->
<div class="modal fade" id="seguimientoModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Seguimiento de Reparación - <span id="modal-placa-seguimiento"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <h6 class="section-title mb-3">
                            <i class="fas fa-info-circle me-2"></i>Información de la Asignación
                        </h6>
                        <div id="info-asignacion">
                            <!-- Se cargará dinámicamente -->
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h6 class="section-title mb-3">
                            <i class="fas fa-tasks me-2"></i>Avances del Mecánico
                        </h6>
                        <div id="avances-mecanico" class="avances-container">
                            <!-- Se cargarán los avances -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
