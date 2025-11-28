<div class="container-fluid py-4">
    <!-- Header con acciones principales -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        Administrar Agendas del Taller
                    </h2>
                    <p class="text-muted mb-0">Gestión de horarios disponibles del taller</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" id="btn-nueva-agenda">
                        <i class="fas fa-plus me-2"></i>Nueva Agenda
                    </button>
                    <button class="btn btn-info" id="btn-refrescar">
                        <i class="fas fa-sync-alt me-2"></i>Refrescar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Vista de Calendario -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-calendar me-2"></i>Vista de Calendario
                </h5>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary active" id="btn-vista-mes">
                        <i class="fas fa-calendar-alt me-1"></i>Mes
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-vista-semana">
                        <i class="fas fa-calendar-week me-1"></i>Semana
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-vista-dia">
                        <i class="fas fa-calendar-day me-1"></i>Día
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="calendario-agendas" style="min-height: 600px;"></div>
        </div>
    </div>

    <!-- Tabla de agendas -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Lista de Agendas
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabla-agendas" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
                            <th>Disponible</th>
                            <th>Observaciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Se llenará dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Agenda -->
<div class="modal fade" id="modalAgenda" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgendaTitulo">
                    <i class="fas fa-calendar-plus me-2"></i>Nueva Agenda
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-agenda">
                    <input type="hidden" id="agenda-id">
                    <div class="mb-3">
                        <label for="agenda-fecha" class="form-label">Fecha *</label>
                        <input type="date" class="form-control" id="agenda-fecha" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="agenda-hora-inicio" class="form-label">Hora Inicio *</label>
                                <input type="time" class="form-control" id="agenda-hora-inicio" required>
                            </div>
                        </div>
                        <div class="col-md-6">
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
