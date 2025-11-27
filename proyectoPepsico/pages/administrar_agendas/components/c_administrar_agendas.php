<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        Administrar Agendas del Taller
                    </h2>
                    <p class="text-muted mb-0">Gestione las horas disponibles en la agenda del taller</p>
                </div>
                <button class="btn btn-primary" id="btn-nueva-agenda">
                    <i class="fas fa-plus me-2"></i>Nueva Agenda
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="filtro-fecha" class="form-label">Filtrar por Fecha</label>
                    <input type="date" class="form-control" id="filtro-fecha">
                </div>
                <div class="col-md-4">
                    <label for="filtro-disponible" class="form-label">Filtrar por Disponibilidad</label>
                    <select class="form-select" id="filtro-disponible">
                        <option value="">Todas</option>
                        <option value="1">Disponibles</option>
                        <option value="0">No Disponibles</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-secondary me-2" id="btn-aplicar-filtros">
                        <i class="fas fa-filter me-2"></i>Aplicar Filtros
                    </button>
                    <button class="btn btn-outline-secondary" id="btn-limpiar-filtros">
                        <i class="fas fa-times me-2"></i>Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Agendas -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabla-agendas" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
                            <th>Disponible</th>
                            <th>Solicitudes Aprobadas</th>
                            <th>Solicitudes Pendientes</th>
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

<!-- Modal de Confirmación para Eliminar -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar esta agenda?</p>
                <div class="alert alert-warning mb-0">
                    <strong>Nota:</strong> Solo se pueden eliminar agendas que no estén asignadas a solicitudes aprobadas.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-eliminar">
                    <i class="fas fa-trash me-2"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

