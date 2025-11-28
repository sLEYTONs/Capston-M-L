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
            case 'obtener_vehiculo_por_patente':
                // Verificar que el usuario esté autenticado
                if (!isset($_SESSION['usuario'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $patente = trim($_POST['patente'] ?? '');
                if (empty($patente)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Patente requerida'
                    ]);
                    exit;
                }

                // Obtener el nombre del conductor desde la sesión para validar
                $conductor_nombre = $_SESSION['usuario']['nombre'] ?? null;
                
                $resultado = obtenerVehiculoPorPatente($patente, $conductor_nombre);
                echo json_encode($resultado);
                break;

            case 'crear_solicitud':
                // Verificar que el usuario sea chofer
                if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Chofer') {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                // Obtener el nombre del conductor desde la sesión (NombreUsuario)
                $conductor_nombre = $_SESSION['usuario']['nombre'] ?? '';

                // Procesar imágenes si existen
                $fotos_procesadas = [];
                if (!empty($_FILES['fotos']) && is_array($_FILES['fotos']['tmp_name'])) {
                    foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
                            $archivo = [
                                'name' => $_FILES['fotos']['name'][$key],
                                'type' => $_FILES['fotos']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['fotos']['error'][$key],
                                'size' => $_FILES['fotos']['size'][$key]
                            ];
                            $resultado = subirArchivo($archivo, 'foto');
                            if ($resultado['success']) {
                                $fotos_procesadas[] = $resultado;
                            }
                        }
                    }
                }

                $datos = [
                    'chofer_id' => $_SESSION['usuario']['id'],
                    'placa' => trim($_POST['placa'] ?? ''),
                    'tipo_vehiculo' => trim($_POST['tipo_vehiculo'] ?? ''),
                    'marca' => trim($_POST['marca'] ?? ''),
                    'modelo' => trim($_POST['modelo'] ?? ''),
                    'anio' => !empty($_POST['anio']) ? intval($_POST['anio']) : null,
                    'conductor_nombre' => $conductor_nombre, // Obtener automáticamente de la sesión
                    'conductor_telefono' => trim($_POST['conductor_telefono'] ?? ''),
                    'proposito' => trim($_POST['proposito'] ?? ''),
                    'observaciones' => trim($_POST['observaciones'] ?? ''),
                    'fotos' => !empty($fotos_procesadas) ? $fotos_procesadas : null
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
                    
                    // Asegurar que siempre devolvamos un array
                    if (!is_array($solicitudes)) {
                        $solicitudes = [];
                    }
                    
                    echo json_encode([
                        'status' => 'success',
                        'data' => $solicitudes
                    ], JSON_UNESCAPED_UNICODE);
                } catch (Exception $e) {
                    ob_clean();
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error al obtener solicitudes: ' . $e->getMessage()
                    ], JSON_UNESCAPED_UNICODE);
                } catch (Error $e) {
                    ob_clean();
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error fatal al obtener solicitudes: ' . $e->getMessage()
                    ], JSON_UNESCAPED_UNICODE);
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

                // Obtener mecanico_id opcional para filtrar horas ya asignadas a ese mecánico
                $mecanico_id = !empty($_POST['mecanico_id']) ? intval($_POST['mecanico_id']) : null;

                $horas = obtenerHorasDisponibles($fecha, $mecanico_id);
                error_log("Horas disponibles para fecha $fecha" . ($mecanico_id ? " (mecánico ID: $mecanico_id)" : "") . ": " . count($horas));
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
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
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

                try {
                    // Intentar con la ruta relativa desde scripts
                    $consulta_functions1 = __DIR__ . '/../../consulta/functions/f_consulta.php';
                    // Intentar con la ruta relativa desde functions (como en f_agendamiento.php)
                    $consulta_functions2 = __DIR__ . '/../consulta/functions/f_consulta.php';
                    
                    $consulta_functions = null;
                    if (file_exists($consulta_functions1)) {
                        $consulta_functions = $consulta_functions1;
                    } elseif (file_exists($consulta_functions2)) {
                        $consulta_functions = $consulta_functions2;
                    } else {
                        throw new Exception("Archivo de funciones no encontrado. Intentó: $consulta_functions1 y $consulta_functions2");
                    }
                    
                    require_once $consulta_functions;
                    
                    if (!function_exists('obtenerMecanicosDisponibles')) {
                        throw new Exception("La función obtenerMecanicosDisponibles no está definida");
                    }
                    
                    $mecanicos = obtenerMecanicosDisponibles();
                    echo json_encode([
                        'status' => 'success',
                        'data' => $mecanicos
                    ]);
                } catch (Exception $e) {
                    error_log("Error en obtener_mecanicos_disponibles: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error al cargar mecánicos: ' . $e->getMessage()
                    ]);
                } catch (Error $e) {
                    error_log("Error fatal en obtener_mecanicos_disponibles: " . $e->getMessage());
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error fatal al cargar mecánicos: ' . $e->getMessage()
                    ]);
                }
                break;

            case 'obtener_todas_agendas':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $filtroFecha = !empty($_POST['filtro_fecha']) ? trim($_POST['filtro_fecha']) : null;
                $filtroDisponible = isset($_POST['filtro_disponible']) && $_POST['filtro_disponible'] !== '' ? intval($_POST['filtro_disponible']) : null;

                $resultado = obtenerTodasLasAgendas($filtroFecha, $filtroDisponible);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                break;

            case 'obtener_agenda':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
                if ($id <= 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'ID de agenda inválido'
                    ]);
                    exit;
                }

                $conn = conectar_Pepsico();
                if (!$conn) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error de conexión'
                    ]);
                    exit;
                }

                $id = $conn->real_escape_string($id);
                $query = "SELECT ID, Fecha, HoraInicio, HoraFin, Disponible, Observaciones, FechaCreacion, FechaActualizacion 
                          FROM agenda_taller WHERE ID = $id LIMIT 1";
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0) {
                    $agenda = $result->fetch_assoc();
                    echo json_encode([
                        'status' => 'success',
                        'data' => $agenda
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Agenda no encontrada'
                    ], JSON_UNESCAPED_UNICODE);
                }
                $conn->close();
                break;

            case 'eliminar_agenda':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $id = !empty($_POST['id']) ? intval($_POST['id']) : (!empty($_POST['agenda_id']) ? intval($_POST['agenda_id']) : 0);
                if ($id <= 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'ID de agenda no proporcionado'
                    ]);
                    exit;
                }

                $resultado = eliminarAgenda($id);
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
                break;

            case 'marcar_vehiculos_salidos':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                // Obtener placa a excluir (por defecto WLVY22)
                $placaExcluir = !empty($_POST['placa_excluir']) ? trim($_POST['placa_excluir']) : 'WLVY22';
                
                $resultado = marcarVehiculosComoSalidos($placaExcluir);
                echo json_encode($resultado);
                break;

            case 'obtener_configuracion_horarios':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $resultado = gestionarConfiguracionHorarios();
                echo json_encode($resultado);
                break;

            case 'guardar_configuracion_horarios':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $datos = [
                    'hora_apertura' => $_POST['hora_apertura'] ?? '08:00:00',
                    'hora_cierre' => $_POST['hora_cierre'] ?? '20:00:00',
                    'duracion_citas' => $_POST['duracion_citas'] ?? 60,
                    'intervalo_citas' => $_POST['intervalo_citas'] ?? 0,
                    'dias_operacion' => $_POST['dias_operacion'] ?? '1,2,3,4,5,6,0',
                    'operacion_247' => isset($_POST['operacion_247']) ? intval($_POST['operacion_247']) : 1
                ];

                $resultado = gestionarConfiguracionHorarios($datos);
                echo json_encode($resultado);
                break;

            case 'generar_horarios_automaticos':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $fechaDesde = $_POST['fecha_desde'] ?? '';
                $fechaHasta = $_POST['fecha_hasta'] ?? '';

                if (empty($fechaDesde) || empty($fechaHasta)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Fechas requeridas'
                    ]);
                    exit;
                }

                $resultado = generarHorariosAutomaticos($fechaDesde, $fechaHasta);
                echo json_encode($resultado);
                break;

            case 'obtener_estadisticas_agenda':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $resultado = obtenerEstadisticasAgenda();
                echo json_encode($resultado);
                break;

            case 'obtener_horarios_por_rango':
                // Verificar que el usuario sea supervisor o administrador
                if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Supervisor', 'Administrador'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                $fechaDesde = $_POST['fecha_desde'] ?? date('Y-m-d');
                $fechaHasta = $_POST['fecha_hasta'] ?? date('Y-m-d', strtotime('+1 month'));

                $resultado = obtenerHorariosPorRango($fechaDesde, $fechaHasta);
                echo json_encode($resultado);
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

