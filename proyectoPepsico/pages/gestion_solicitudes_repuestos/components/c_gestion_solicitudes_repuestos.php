<section id="gestion-solicitudes-section" class="section">
    <div class="repuestos-container">
        <div class="repuestos-layout">
            <!-- Contenido principal -->
            <div class="repuestos-main-content">
                <div class="card h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>
                            Gestión de Solicitudes de Repuestos
                        </h4>
                        <div class="card-actions">
                            <button class="btn btn-outline-light btn-sm" id="btn-refresh" title="Actualizar">
                                <i class="fas fa-sync-alt me-1"></i> Actualizar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="filtro-estado" class="form-label">Filtrar por Estado</label>
                                <select id="filtro-estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="Pendiente" selected>Pendientes</option>
                                    <option value="Aprobada">Aprobadas</option>
                                    <option value="Entregada">Entregadas</option>
                                    <option value="Rechazada">Rechazadas</option>
                                </select>
                            </div>
                        </div>
                        <div class="tabla-container">
                            <table id="tabla-solicitudes" class="table table-striped table-hover table-bordered" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Mecánico</th>
                                        <th>Repuesto</th>
                                        <th>Cantidad</th>
                                        <th>Stock Disponible</th>
                                        <th>Vehículo</th>
                                        <th>Urgencia</th>
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

