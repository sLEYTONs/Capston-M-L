<?php
// Cargar funciones de agendamiento
require_once __DIR__ . '/../../../app/model/agendamiento/functions/f_agendamiento.php';
require_once __DIR__ . '/../../../app/config/conexion.php';

// Obtener todas las agendas para renderizar en la tabla
$agendas_data = obtenerTodasLasAgendas();
$agendas = ($agendas_data['status'] === 'success') ? $agendas_data['data'] : [];

// Preparar datos para el calendario (formato JSON)
$eventos_calendario = [];
foreach ($agendas as $agenda) {
    if (!empty($agenda['Fecha']) && !empty($agenda['HoraInicio']) && !empty($agenda['HoraFin'])) {
        // Asegurar formato completo de hora (HH:MM:SS)
        $horaInicio = $agenda['HoraInicio'];
        $horaFin = $agenda['HoraFin'];
        
        // Si la hora no tiene segundos, agregarlos
        if (substr_count($horaInicio, ':') === 1) {
            $horaInicio .= ':00';
        }
        if (substr_count($horaFin, ':') === 1) {
            $horaFin .= ':00';
        }
        
        // Concatenar fecha + hora para formato ISO (YYYY-MM-DDTHH:MM:SS)
        $start = $agenda['Fecha'] . 'T' . $horaInicio;
        $end = $agenda['Fecha'] . 'T' . $horaFin;
        
        $eventos_calendario[] = [
            'id' => $agenda['ID'],
            'title' => substr($agenda['HoraInicio'], 0, 5) . ' - ' . substr($agenda['HoraFin'], 0, 5),
            'start' => $start,
            'end' => $end,
            'disponible' => (int)$agenda['Disponible'],
            'observaciones' => $agenda['Observaciones'] ?? ''
        ];
    }
}
$eventos_json = json_encode($eventos_calendario, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
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
            <h5 class="mb-0">
                <i class="fas fa-calendar me-2"></i>Vista de Calendario
            </h5>
        </div>
        <div class="card-body" style="padding: 0;">
            <div id="calendario-agendas"></div>
            <div id="calendario-loading" style="display: none; text-align: center; padding: 40px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando calendario...</span>
                </div>
                <p class="mt-3 text-muted">Cargando calendario...</p>
            </div>
            <div id="calendario-error" style="display: none; text-align: center; padding: 40px; color: #dc3545;">
                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                <p>Error al cargar el calendario. Por favor, recarga la página.</p>
                <button class="btn btn-primary" onclick="location.reload()">Recargar Página</button>
            </div>
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
                        <?php if (!empty($agendas)): ?>
                            <?php foreach ($agendas as $agenda): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($agenda['ID']); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($agenda['Fecha'])) {
                                            $fecha = new DateTime($agenda['Fecha']);
                                            echo htmlspecialchars($fecha->format('d/m/Y'));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo !empty($agenda['HoraInicio']) ? htmlspecialchars(substr($agenda['HoraInicio'], 0, 5)) : '-'; ?></td>
                                    <td><?php echo !empty($agenda['HoraFin']) ? htmlspecialchars(substr($agenda['HoraFin'], 0, 5)) : '-'; ?></td>
                                    <td>
                                        <?php if ($agenda['Disponible'] == 1 || $agenda['Disponible'] === true || $agenda['Disponible'] === '1'): ?>
                                            <span class="badge bg-success">Disponible</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Ocupado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo !empty($agenda['Observaciones']) ? htmlspecialchars($agenda['Observaciones']) : '-'; ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-warning btn-editar" data-id="<?php echo htmlspecialchars($agenda['ID']); ?>" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?php echo htmlspecialchars($agenda['ID']); ?>" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // Pasar eventos del calendario al JavaScript
    window.eventosCalendarioIniciales = <?php echo $eventos_json; ?>;
    console.log('Eventos del calendario cargados:', window.eventosCalendarioIniciales);
</script>

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
