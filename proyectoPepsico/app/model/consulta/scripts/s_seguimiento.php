<?php
session_start();
require '../functions/f_consulta.php';
// Incluir funciones de agendamiento si no están disponibles
if (!function_exists('obtenerSolicitudAgendamientoPorID')) {
    require '../../agendamiento/functions/f_agendamiento.php';
}

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No autorizado'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehiculo_id'])) {
    $vehiculo_id = intval($_POST['vehiculo_id']);
    $asignacion_id = isset($_POST['asignacion_id']) ? intval($_POST['asignacion_id']) : null;
    $fecha_agenda = isset($_POST['fecha_agenda']) ? trim($_POST['fecha_agenda']) : null;
    $solicitud_id = isset($_POST['solicitud_id']) ? intval($_POST['solicitud_id']) : null;

    try {
        $vehiculo = obtenerVehiculoPorID($vehiculo_id);
        
        // Obtener información de la solicitud si se proporciona el ID
        $solicitud = null;
        if ($solicitud_id) {
            $solicitud = obtenerSolicitudAgendamientoPorID($solicitud_id);
        }
        
        // Prioridad: 1) asignacion_id específico, 2) solicitud_id (con fecha), 3) fecha_agenda, 4) asignación activa más reciente
        $asignacion_id_para_avances = null;
        
        if ($asignacion_id) {
            // Si se proporciona asignacion_id directamente, usarlo para obtener la asignación y los avances
            $asignacion = obtenerAsignacionPorID($asignacion_id);
            $asignacion_id_para_avances = $asignacion_id; // Usar el ID proporcionado directamente
        } elseif ($solicitud_id && $solicitud) {
            // Buscar asignación por solicitud (usa la fecha de la solicitud y el ID de la solicitud)
            $fecha_solicitud = null;
            if (isset($solicitud['FechaAgendaRaw']) && $solicitud['FechaAgendaRaw']) {
                $fecha_solicitud = $solicitud['FechaAgendaRaw'];
            } elseif (isset($solicitud['FechaAgenda']) && $solicitud['FechaAgenda']) {
                $fecha_solicitud = $solicitud['FechaAgenda'];
            }
            
            if ($fecha_solicitud) {
                // Pasar también el solicitud_id para buscar la asignación específica de esta solicitud
                $asignacion = obtenerAsignacionPorFechaAgenda($vehiculo_id, $fecha_solicitud, $solicitud_id);
            }
            // Si no se encuentra por fecha de solicitud, intentar con la activa más reciente
            if (!$asignacion) {
                $asignacion = obtenerAsignacionActiva($vehiculo_id);
            }
            // Usar el ID de la asignación encontrada para obtener los avances
            $asignacion_id_para_avances = $asignacion ? $asignacion['ID'] : null;
        } elseif ($fecha_agenda) {
            // Buscar asignación por fecha de agenda (sin solicitud_id específico)
            $asignacion = obtenerAsignacionPorFechaAgenda($vehiculo_id, $fecha_agenda);
            // Si no se encuentra por fecha, intentar con la activa más reciente
            if (!$asignacion) {
                $asignacion = obtenerAsignacionActiva($vehiculo_id);
            }
            // Usar el ID de la asignación encontrada para obtener los avances
            $asignacion_id_para_avances = $asignacion ? $asignacion['ID'] : null;
        } else {
            $asignacion = obtenerAsignacionActiva($vehiculo_id);
            // Usar el ID de la asignación encontrada para obtener los avances
            $asignacion_id_para_avances = $asignacion ? $asignacion['ID'] : null;
        }
        
        // Obtener avances de la tabla avances_mecanico usando el AsignacionID
        // La función obtenerAvancesMecanico filtra por AsignacionID en la tabla avances_mecanico
        // WHERE AsignacionID = ? y obtiene FechaAvance de cada avance
        $avances = obtenerAvancesMecanico($asignacion_id_para_avances);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'vehiculo' => $vehiculo,
                'asignacion' => $asignacion,
                'avances' => $avances,
                'solicitud' => $solicitud
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al cargar seguimiento: ' . $e->getMessage()
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Método no permitido'
]);
?>