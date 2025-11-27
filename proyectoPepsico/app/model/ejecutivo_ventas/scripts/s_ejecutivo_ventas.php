<?php
// Prevenir output antes de JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once '../functions/f_ejecutivo_ventas.php';
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error al cargar funciones: ' . $e->getMessage()]);
    exit;
}

// Limpiar cualquier output no deseado
ob_end_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $accion = $_POST['accion'] ?? '';
        $usuario_id = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0;
        
        if ($usuario_id == 0) {
            echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
            exit;
        }
        
        switch ($accion) {
            case 'obtener_vehiculos_asignados':
                $resultado = obtenerVehiculosAsignados($usuario_id);
                echo json_encode($resultado);
                break;
                
            case 'obtener_vehiculos_disponibles':
                $resultado = obtenerVehiculosDisponibles();
                echo json_encode($resultado);
                break;
                
            case 'obtener_historial_recepcion_devolucion':
                $resultado = obtenerHistorialRecepcionDevolucion($usuario_id);
                echo json_encode($resultado);
                break;
                
            case 'registrar_recepcion':
                $datos = [
                    'vehiculo_id' => $_POST['vehiculo_id'] ?? 0,
                    'kilometraje_inicial' => $_POST['kilometraje_inicial'] ?? 0,
                    'observaciones' => $_POST['observaciones'] ?? '',
                    'estado_vehiculo' => $_POST['estado_vehiculo'] ?? 'Bueno',
                    'fotos_recepcion' => isset($_POST['fotos_recepcion']) ? json_decode($_POST['fotos_recepcion'], true) : []
                ];
                $resultado = registrarRecepcionVehiculo($datos);
                echo json_encode($resultado);
                break;
                
            case 'registrar_devolucion':
                $datos = [
                    'asignacion_id' => $_POST['asignacion_id'] ?? 0,
                    'kilometraje_final' => $_POST['kilometraje_final'] ?? 0,
                    'observaciones' => $_POST['observaciones'] ?? '',
                    'estado_vehiculo' => $_POST['estado_vehiculo'] ?? 'Bueno',
                    'fotos_devolucion' => isset($_POST['fotos_devolucion']) ? json_decode($_POST['fotos_devolucion'], true) : []
                ];
                $resultado = registrarDevolucionVehiculo($datos);
                echo json_encode($resultado);
                break;
                
            case 'reportar_falla':
                $datos = [
                    'vehiculo_id' => $_POST['vehiculo_id'] ?? 0,
                    'tipo_falla' => $_POST['tipo_falla'] ?? '',
                    'descripcion' => $_POST['descripcion'] ?? '',
                    'prioridad' => $_POST['prioridad'] ?? 'Media',
                    'kilometraje' => $_POST['kilometraje'] ?? 0,
                    'fotos' => isset($_POST['fotos']) ? json_decode($_POST['fotos'], true) : []
                ];
                $resultado = reportarFallaVehiculo($datos);
                echo json_encode($resultado);
                break;
                
            case 'obtener_reportes_fallas':
                $resultado = obtenerReportesFallas($usuario_id);
                echo json_encode($resultado);
                break;
                
            case 'obtener_detalles_reporte_falla':
                $reporte_id = intval($_POST['reporte_id'] ?? 0);
                if ($reporte_id <= 0) {
                    echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
                    break;
                }
                $resultado = obtenerDetallesReporteFalla($reporte_id, $usuario_id);
                echo json_encode($resultado);
                break;
                
            default:
                echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);

