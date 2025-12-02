<?php
// Iniciar buffer de salida para capturar cualquier output inesperado
ob_start();

// Desactivar errores de visualización para evitar que se muestren en la respuesta JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Iniciar sesión antes de cualquier output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar cualquier output capturado
ob_clean();

// Establecer header JSON inmediatamente
header('Content-Type: application/json; charset=utf-8');

// Verificar que el require_once funcione
$functions_file = __DIR__ . '/../functions/f_gestion_pausas_repuestos.php';
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

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'obtenerTareasEnPausa':
            $mecanico_id = $_SESSION['usuario']['id'];
            $tareas = obtenerTareasEnPausa($mecanico_id);
            echo json_encode(['status' => 'success', 'data' => $tareas]);
            break;

        case 'obtenerRepuestos':
            // Solo mostrar repuestos sin stock (stock = 0) para solicitar compra
            // Excluir repuestos que ya tienen solicitudes pendientes o aprobadas del mismo mecánico
            $mecanico_id = null;
            $asignacion_id = null;
            
            // Obtener ID del mecánico desde la sesión si está disponible
            if (isset($_SESSION['usuario']['id']) && $_SESSION['usuario']['rol'] === 'Mecánico') {
                $mecanico_id = intval($_SESSION['usuario']['id']);
                
                // Obtener asignacion_id de la URL si existe
                $asignacion_id = !empty($_GET['asignacion_id']) ? intval($_GET['asignacion_id']) : null;
                if (empty($asignacion_id) && !empty($_POST['asignacion_id'])) {
                    $asignacion_id = intval($_POST['asignacion_id']);
                }
            }
            
            $repuestos = obtenerRepuestosDisponibles(true, $mecanico_id, $asignacion_id);
            echo json_encode(['status' => 'success', 'data' => $repuestos]);
            break;

        case 'crearSolicitudRepuestos':
            // Solo los mecánicos pueden crear solicitudes de repuestos
            if ($_SESSION['usuario']['rol'] !== 'Mecánico') {
                echo json_encode(['status' => 'error', 'message' => 'Solo los mecánicos pueden crear solicitudes de repuestos']);
                exit();
            }

            $datos = [
                'asignacion_id' => intval($_POST['asignacion_id'] ?? 0),
                'mecanico_id' => $_SESSION['usuario']['id'],
                'repuesto_id' => intval($_POST['repuesto_id'] ?? 0),
                'cantidad' => intval($_POST['cantidad'] ?? 1),
                'urgencia' => trim($_POST['urgencia'] ?? 'Media'),
                'motivo' => trim($_POST['motivo'] ?? '')
            ];

            $resultado = crearSolicitudRepuestos($datos);
            echo json_encode($resultado);
            break;

        case 'obtenerSolicitudes':
            $mecanico_id = $_SESSION['usuario']['id'];
            $asignacion_id = !empty($_POST['asignacion_id']) ? intval($_POST['asignacion_id']) : null;
            $solicitudes = obtenerSolicitudesRepuestos($mecanico_id, $asignacion_id);
            echo json_encode(['status' => 'success', 'data' => $solicitudes]);
            break;

        case 'obtenerTodosRepuestos':
            try {
                $resultado = obtenerTodosRepuestos();
                // Si ya viene con formato de respuesta, devolverlo directamente
                if (isset($resultado['status'])) {
                    echo json_encode($resultado);
                } else {
                    // Compatibilidad con formato antiguo
                    echo json_encode(['status' => 'success', 'data' => $resultado]);
                }
            } catch (Exception $e) {
                ob_clean();
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al obtener repuestos: ' . $e->getMessage()
                ]);
            } catch (Error $e) {
                ob_clean();
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error fatal al obtener repuestos: ' . $e->getMessage()
                ]);
            }
            break;

        case 'obtenerRepuestosStockBajo':
            $repuestos = obtenerRepuestosStockBajo();
            echo json_encode(['status' => 'success', 'data' => $repuestos]);
            break;

        case 'enviarAlertaStockBajo':
            $usuario_id = $_SESSION['usuario']['id'];
            $resultado = enviarAlertaStockBajo($usuario_id);
            echo json_encode($resultado);
            break;

        case 'obtenerMovimientosStock':
            $repuesto_id = intval($_GET['repuesto_id'] ?? $_POST['repuesto_id'] ?? 0);
            if ($repuesto_id > 0) {
                $movimientos = obtenerMovimientosStock($repuesto_id);
                echo json_encode(['status' => 'success', 'data' => $movimientos]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de repuesto inválido']);
            }
            break;

        case 'pausarTarea':
            ob_clean();
            if ($_SESSION['usuario']['rol'] !== 'Mecánico') {
                echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
                exit();
            }

            $asignacion_id = intval($_POST['asignacion_id'] ?? 0);
            $mecanico_id = $_SESSION['usuario']['id'];
            $motivo_pausa = trim($_POST['motivo_pausa'] ?? '');
            
            if (empty($motivo_pausa)) {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => 'El motivo de pausa es requerido']);
                exit();
            }

            try {
                $resultado = pausarTarea($asignacion_id, $mecanico_id, $motivo_pausa);
                ob_clean();
                echo json_encode($resultado);
            } catch (Exception $e) {
                ob_clean();
                error_log("Error en pausarTarea (script): " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al pausar tarea: ' . $e->getMessage()
                ]);
            }
            break;

        case 'reanudarTarea':
            if ($_SESSION['usuario']['rol'] !== 'Mecánico') {
                echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
                exit();
            }

            $asignacion_id = intval($_POST['asignacion_id'] ?? 0);
            $mecanico_id = $_SESSION['usuario']['id'];
            $resultado = reanudarTarea($asignacion_id, $mecanico_id);
            echo json_encode($resultado);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    ob_clean();
    error_log("Error en s_gestion_pausas_repuestos: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error interno del servidor: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    ob_clean();
    error_log("Error fatal en s_gestion_pausas_repuestos: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error fatal del servidor: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

