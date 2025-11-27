<main class="main">
    <div class="container">
        <div class="search-container">
            <div class="d-flex align-items-center mb-3">
                <h2 class="mb-0">
                    <i class="fas fa-calendar-check me-2"></i>
                    Gestión de Solicitudes de Agendamiento
                </h2>
                <div class="ms-auto">
                    <button class="btn btn-success" id="btn-gestionar-agenda" data-bs-toggle="modal" data-bs-target="#agendaModal">
                        <i class="fas fa-calendar-plus me-2"></i>Gestionar Agenda
                    </button>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="search-form">
                <div class="search-fields">
                    <div class="input-group">
                        <select id="filtro-estado-solicitud" class="search-input">
                            <option value="">Todos los estados</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Aprobada">Aprobada</option>
                            <option value="Rechazada">Rechazada</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <input type="date" id="filtro-fecha-desde" class="search-input" placeholder="Fecha desde...">
                    </div>
                    <div class="input-group">
                        <input type="date" id="filtro-fecha-hasta" class="search-input" placeholder="Fecha hasta...">
                    </div>
                    <button class="btn btn-primary" id="btn-buscar-solicitudes">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
                </div>
            </div>

            <!-- Tabla de Solicitudes -->
            <div class="results-container">
                <table id="solicitudes-table" class="results-table display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Placa</th>
                            <th>Vehículo</th>
                            <th>Propósito</th>
                            <th>Estado</th>
                            <th>Chofer</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los datos se cargarán via Ajax -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal para Aprobar/Rechazar Solicitud -->
<div class="modal fade" id="gestionarSolicitudModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Gestionar Solicitud de Agendamiento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="info-solicitud-detalle">
                    <!-- Se cargará dinámicamente -->
                </div>
                <hr>
                <div id="acciones-solicitud">
                    <h6 class="mb-3">
                        <i class="fas fa-calendar-alt me-2"></i>Seleccionar Fecha y Hora Disponible (Horario: 9:00 AM - 11:00 PM):
                    </h6>
                    
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instrucciones:</strong> Haz clic en cualquier bloque de hora disponible (verde) en el calendario para seleccionarlo.
                    </div>
                    
                    <!-- Calendario con horas disponibles -->
                    <div class="mb-3">
                        <div id="calendario-horas-disponibles" style="min-height: 400px;"></div>
                    </div>
                    
                    <!-- Información de selección -->
                    <div id="info-seleccion-hora" class="alert alert-success" style="display: none;">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Hora seleccionada:</strong> <span id="hora-seleccionada-texto"></span>
                    </div>
                    
                    <!-- Input oculto para almacenar el ID de la agenda seleccionada -->
                    <input type="hidden" id="agenda-id-seleccionada" name="hora-disponible" required>
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3">
                        <i class="fas fa-wrench me-2"></i>Mecánicos Disponibles:
                    </h6>
                    <div id="mecanicos-disponibles-container" class="mb-3">
                        <!-- Se cargarán los mecánicos disponibles -->
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="mb-3">
                        <label for="motivo-rechazo" class="form-label">Motivo de Rechazo (si aplica):</label>
                        <textarea class="form-control" id="motivo-rechazo" rows="3" 
                                  placeholder="Ingrese el motivo del rechazo..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-rechazar-solicitud">
                    <i class="fas fa-times me-2"></i>Rechazar
                </button>
                <button type="button" class="btn btn-success" id="btn-aprobar-solicitud">
                    <i class="fas fa-check me-2"></i>Aprobar y Asignar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Gestionar Agenda -->
<div class="modal fade" id="agendaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Gestionar Agenda del Taller
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="agenda-form">
                    <input type="hidden" id="agenda-id">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="agenda-fecha" class="form-label">Fecha *</label>
                                <input type="date" class="form-control" id="agenda-fecha" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="agenda-hora-inicio" class="form-label">Hora Inicio *</label>
                                <input type="time" class="form-control" id="agenda-hora-inicio" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="agenda-hora-fin" class="form-label">Hora Fin *</label>
                                <input type="time" class="form-control" id="agenda-hora-fin" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agenda-disponible" checked>
                            <label class="form-check-label" for="agenda-disponible">
                                Disponible
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="agenda-observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="agenda-observaciones" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-agenda">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

