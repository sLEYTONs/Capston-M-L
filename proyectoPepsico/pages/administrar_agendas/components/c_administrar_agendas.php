<?php
// Cargar funciones de agendamiento
require_once __DIR__ . '/../../../app/model/agendamiento/functions/f_agendamiento.php';
require_once __DIR__ . '/../../../app/config/conexion.php';

// Función helper para verificar si una agenda ya pasó
function agendaVencida($fecha, $horaFin) {
    if (empty($fecha) || empty($horaFin)) {
        return false;
    }
    
    // Crear DateTime para la fecha/hora de fin de la agenda
    $horaFinFormat = $horaFin;
    if (substr_count($horaFinFormat, ':') === 1) {
        $horaFinFormat .= ':00';
    }
    
    try {
        $fechaHoraFin = new DateTime($fecha . ' ' . $horaFinFormat);
        $ahora = new DateTime();
        
        // Si la fecha/hora de fin ya pasó, está vencida
        return $fechaHoraFin < $ahora;
    } catch (Exception $e) {
        return false;
    }
}

// Función helper para obtener el estado visual de una agenda
function obtenerEstadoAgenda($agenda) {
    $disponible = ($agenda['Disponible'] == 1 || $agenda['Disponible'] === true || $agenda['Disponible'] === '1');
    
    // Verificar si está vencida
    if (agendaVencida($agenda['Fecha'], $agenda['HoraFin'])) {
        return [
            'clase' => 'bg-secondary',
            'texto' => 'Vencida',
            'icono' => 'fa-clock'
        ];
    }
    
    // Si no está vencida, verificar disponibilidad
    if ($disponible) {
        return [
            'clase' => 'bg-success',
            'texto' => 'Disponible',
            'icono' => 'fa-check-circle'
        ];
    } else {
        return [
            'clase' => 'bg-danger',
            'texto' => 'Ocupado',
            'icono' => 'fa-times-circle'
        ];
    }
}

// Obtener todas las agendas para renderizar en la tabla
$agendas_data = obtenerTodasLasAgendas();
$agendas = ($agendas_data['status'] === 'success') ? $agendas_data['data'] : [];

// Ordenar agendas: primero las disponibles, luego ocupadas, luego vencidas
if (!empty($agendas)) {
    usort($agendas, function($a, $b) {
        // Obtener valor de ordenamiento del estado para agenda A
        // 3 = Disponible (no vencida y Disponible = 1)
        // 2 = Ocupado (no vencida y Disponible = 0)
        // 1 = Vencida
        $vencidaA = agendaVencida($a['Fecha'] ?? '', $a['HoraFin'] ?? '');
        $estadoA = 1; // Por defecto vencida
        if (!$vencidaA) {
            $disponibleA = isset($a['Disponible']) && ($a['Disponible'] == 1 || $a['Disponible'] === true || $a['Disponible'] === '1');
            $estadoA = $disponibleA ? 3 : 2; // 3 = Disponible, 2 = Ocupado
        }
        
        // Obtener valor de ordenamiento del estado para agenda B
        $vencidaB = agendaVencida($b['Fecha'] ?? '', $b['HoraFin'] ?? '');
        $estadoB = 1; // Por defecto vencida
        if (!$vencidaB) {
            $disponibleB = isset($b['Disponible']) && ($b['Disponible'] == 1 || $b['Disponible'] === true || $b['Disponible'] === '1');
            $estadoB = $disponibleB ? 3 : 2; // 3 = Disponible, 2 = Ocupado
        }
        
        // Primero ordenar por estado (Disponible > Ocupado > Vencida)
        if ($estadoA != $estadoB) {
            return $estadoB - $estadoA; // Mayor valor primero (3 > 2 > 1)
        }
        
        // Si tienen el mismo estado, ordenar por fecha
        if (!empty($a['Fecha']) && !empty($b['Fecha'])) {
            $fechaA = strtotime($a['Fecha']);
            $fechaB = strtotime($b['Fecha']);
            if ($fechaA != $fechaB) {
                return $fechaA - $fechaB; // Fecha más antigua primero
            }
        }
        
        // Si tienen la misma fecha, ordenar por hora de inicio
        if (!empty($a['HoraInicio']) && !empty($b['HoraInicio'])) {
            return strcmp($a['HoraInicio'], $b['HoraInicio']); // Hora más temprana primero
        }
        
        // Si todo es igual, ordenar por ID descendente
        return (isset($b['ID']) ? (int)$b['ID'] : 0) - (isset($a['ID']) ? (int)$a['ID'] : 0);
    });
}

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
        
        // Verificar si la agenda está vencida
        $vencida = agendaVencida($agenda['Fecha'], $agenda['HoraFin']);
        
        $eventos_calendario[] = [
            'id' => $agenda['ID'],
            'title' => substr($agenda['HoraInicio'], 0, 5) . ' - ' . substr($agenda['HoraFin'], 0, 5),
            'start' => $start,
            'end' => $end,
            'disponible' => (int)$agenda['Disponible'],
            'vencida' => $vencida ? 1 : 0,
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
    <div class="card mb-4 calendar-card">
        <div class="card-header calendar-header">
            <h5 class="mb-0">
                <i class="fas fa-calendar me-2"></i>Vista de Calendario
            </h5>
        </div>
        <div class="card-body calendar-body">
            <div id="calendario-agendas"></div>
            <div id="calendario-loading" class="calendar-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando calendario...</span>
                </div>
                <p class="mt-3 text-muted">Cargando calendario...</p>
            </div>
            <div id="calendario-error" class="calendar-error">
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
                <table id="tabla-agendas" class="table-agendas-moderna" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
                            <th>Estado</th>
                            <th>Observaciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($agendas)): ?>
                            <?php foreach ($agendas as $agenda): ?>
                                <?php 
                                $estado = obtenerEstadoAgenda($agenda);
                                $vencida = agendaVencida($agenda['Fecha'], $agenda['HoraFin']);
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo htmlspecialchars($agenda['ID']); ?></td>
                                    <td class="text-center">
                                        <?php 
                                        if (!empty($agenda['Fecha'])) {
                                            $fecha = new DateTime($agenda['Fecha']);
                                            echo htmlspecialchars($fecha->format('d/m/Y'));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center"><?php echo !empty($agenda['HoraInicio']) ? htmlspecialchars(substr($agenda['HoraInicio'], 0, 5)) : '-'; ?></td>
                                    <td class="text-center"><?php echo !empty($agenda['HoraFin']) ? htmlspecialchars(substr($agenda['HoraFin'], 0, 5)) : '-'; ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-estado <?php echo $estado['clase']; ?>">
                                            <i class="fas <?php echo $estado['icono']; ?> me-1"></i>
                                            <?php echo htmlspecialchars($estado['texto']); ?>
                                        </span>
                                    </td>
                                    <td class="text-left"><?php echo !empty($agenda['Observaciones']) ? htmlspecialchars($agenda['Observaciones']) : '-'; ?></td>
                                    <td class="text-center">
                                        <?php if (!$vencida): ?>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-warning btn-editar" data-id="<?php echo htmlspecialchars($agenda['ID']); ?>" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?php echo htmlspecialchars($agenda['ID']); ?>" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted" title="Agenda vencida - No se pueden realizar acciones">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        <?php endif; ?>
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
