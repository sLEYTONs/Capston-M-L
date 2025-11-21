<?php
// Iniciar buffer de salida para capturar cualquier output inesperado
ob_start();

// Desactivar errores de visualización para evitar que se muestren en la respuesta JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Habilitar errores en el log para depuración
$error_log_path = __DIR__ . '/../../../../error_log.txt';
if (is_writable(dirname($error_log_path))) {
    ini_set('error_log', $error_log_path);
}

// Iniciar sesión antes de cualquier output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar cualquier output capturado
ob_clean();

// Establecer header JSON inmediatamente
header('Content-Type: application/json; charset=utf-8');

// Verificar que el require_once funcione
$functions_file = __DIR__ . '/../functions/f_agendamiento.php';
if (!file_exists($functions_file)) {
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: Archivo de funciones no encontrado: ' . $functions_file
    ]);
    exit;
}

try {
    require_once $functions_file;
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al cargar funciones: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fatal al cargar funciones: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        switch ($accion) {
            case 'crear_solicitud':
                // Verificar que el usuario sea chofer
                if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Chofer') {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $datos = [
                    'chofer_id' => $_SESSION['usuario']['id'],
                    'placa' => trim($_POST['placa'] ?? ''),
                    'tipo_vehiculo' => trim($_POST['tipo_vehiculo'] ?? ''),
                    'marca' => trim($_POST['marca'] ?? ''),
                    'modelo' => trim($_POST['modelo'] ?? ''),
                    'color' => trim($_POST['color'] ?? ''),
                    'anio' => !empty($_POST['anio']) ? intval($_POST['anio']) : null,
                    'conductor_nombre' => trim($_POST['conductor_nombre'] ?? ''),
                    'conductor_telefono' => trim($_POST['conductor_telefono'] ?? ''),
                    'proposito' => trim($_POST['proposito'] ?? ''),
                    'area' => trim($_POST['area'] ?? ''),
                    'persona_contacto' => trim($_POST['persona_contacto'] ?? ''),
                    'observaciones' => trim($_POST['observaciones'] ?? ''),
                    'fecha_solicitada' => trim($_POST['fecha_solicitada'] ?? ''),
                    'hora_solicitada' => trim($_POST['hora_solicitada'] ?? '')
                ];

                $resultado = crearSolicitudAgendamiento($datos);
                echo json_encode($resultado);
                break;

            case 'obtener_solicitudes':
                // Verificar que el usuario esté autenticado
                if (!isset($_SESSION['usuario'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $filtros = [
                    'estado' => $_POST['estado'] ?? '',
                    'chofer_id' => $_POST['chofer_id'] ?? '',
                    'supervisor_id' => $_POST['supervisor_id'] ?? '',
                    'fecha_desde' => $_POST['fecha_desde'] ?? '',
                    'fecha_hasta' => $_POST['fecha_hasta'] ?? '',
                    'solicitud_id' => $_POST['solicitud_id'] ?? ''
                ];

                // Si es chofer, solo puede ver sus propias solicitudes
                if ($_SESSION['usuario']['rol'] === 'Chofer') {
                    $filtros['chofer_id'] = $_SESSION['usuario']['id'];
                }

                try {
                    // Limpiar buffer antes de ejecutar
                    ob_clean();
                    
                    $solicitudes = obtenerSolicitudesAgendamiento($filtros);
                    
                    echo json_encode([
                        'status' => 'success',
                        'data' => $solicitudes
                    ]);
                } catch (Exception $e) {
                    ob_clean();
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error al obtener solicitudes: ' . $e->getMessage(),
                        'debug' => [
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]
                    ]);
                } catch (Error $e) {
                    ob_clean();
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error fatal al obtener solicitudes: ' . $e->getMessage(),
                        'debug' => [
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]
                    ]);
                }
                break;

            case 'obtener_horas_disponibles':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $fecha = trim($_POST['fecha'] ?? '');
                if (empty($fecha)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Fecha requerida'
                    ]);
                    exit;
                }

                $horas = obtenerHorasDisponibles($fecha);
                echo json_encode([
                    'status' => 'success',
                    'data' => $horas
                ]);
                break;

            case 'aprobar_solicitud':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $solicitud_id = intval($_POST['solicitud_id'] ?? 0);
                $supervisor_id = $_SESSION['usuario']['id'];
                $agenda_id = !empty($_POST['agenda_id']) ? intval($_POST['agenda_id']) : null;
                $mecanico_id = !empty($_POST['mecanico_id']) ? intval($_POST['mecanico_id']) : null;

                if ($solicitud_id <= 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'ID de solicitud inválido'
                    ]);
                    exit;
                }

                $resultado = aprobarSolicitudAgendamiento($solicitud_id, $supervisor_id, $agenda_id, $mecanico_id);
                echo json_encode($resultado);
                break;

            case 'rechazar_solicitud':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $solicitud_id = intval($_POST['solicitud_id'] ?? 0);
                $supervisor_id = $_SESSION['usuario']['id'];
                $motivo_rechazo = trim($_POST['motivo_rechazo'] ?? '');

                if ($solicitud_id <= 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'ID de solicitud inválido'
                    ]);
                    exit;
                }

                if (empty($motivo_rechazo)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Motivo de rechazo requerido'
                    ]);
                    exit;
                }

                $resultado = rechazarSolicitudAgendamiento($solicitud_id, $supervisor_id, $motivo_rechazo);
                echo json_encode($resultado);
                break;

            case 'gestionar_agenda':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $datos = [
                    'id' => !empty($_POST['id']) ? intval($_POST['id']) : null,
                    'fecha' => trim($_POST['fecha'] ?? ''),
                    'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
                    'hora_fin' => trim($_POST['hora_fin'] ?? ''),
                    'disponible' => isset($_POST['disponible']) ? intval($_POST['disponible']) : 1,
                    'observaciones' => trim($_POST['observaciones'] ?? '')
                ];

                $resultado = gestionarAgendaTaller($datos);
                echo json_encode($resultado);
                break;

            case 'obtener_mecanicos_disponibles':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                require_once __DIR__ . '/../consulta/functions/f_consulta.php';
                $mecanicos = obtenerMecanicosDisponibles();
                echo json_encode([
                    'status' => 'success',
                    'data' => $mecanicos
                ]);
                break;

            default:
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Acción no válida'
                ]);
                break;
        }
    } catch (Exception $e) {
        // Asegurar que siempre devolvamos JSON válido
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        exit;
    } catch (Error $e) {
        // Capturar errores fatales también
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error fatal: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        exit;
    }
    exit;
}

http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Método no permitido'
]);

?>

