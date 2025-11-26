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
    require_once '../functions/f_control_gastos.php';
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
        
        switch ($accion) {
            case 'obtener_gastos_internos':
                $filtros = [
                    'fecha_desde' => $_POST['fecha_desde'] ?? '',
                    'fecha_hasta' => $_POST['fecha_hasta'] ?? ''
                ];
                $resultado = obtenerGastosInternos($filtros);
                echo json_encode($resultado);
                break;
                
            case 'obtener_gastos_externos':
                $filtros = [
                    'fecha_desde' => $_POST['fecha_desde'] ?? '',
                    'fecha_hasta' => $_POST['fecha_hasta'] ?? ''
                ];
                $resultado = obtenerGastosExternos($filtros);
                echo json_encode($resultado);
                break;
                
            case 'registrar_gasto_externo':
                $datos = [
                    'vehiculo_id' => $_POST['vehiculo_id'] ?? '',
                    'taller_nombre' => $_POST['taller_nombre'] ?? '',
                    'servicio' => $_POST['servicio'] ?? '',
                    'costo_total' => $_POST['costo_total'] ?? '',
                    'fecha' => $_POST['fecha'] ?? '',
                    'observaciones' => $_POST['observaciones'] ?? ''
                ];
                $resultado = registrarGastoExterno($datos);
                echo json_encode($resultado);
                break;
                
            case 'obtener_vehiculos':
                $resultado = obtenerVehiculosActivos();
                echo json_encode($resultado);
                break;
                
            case 'obtener_estadisticas':
                $filtros = [
                    'fecha_desde' => $_POST['fecha_desde'] ?? '',
                    'fecha_hasta' => $_POST['fecha_hasta'] ?? ''
                ];
                $resultado = obtenerEstadisticasGastos($filtros);
                echo json_encode($resultado);
                break;
                
            case 'obtener_detalles_gasto':
                $gastoId = $_POST['gasto_id'] ?? 0;
                $resultado = obtenerDetallesGastoExterno($gastoId);
                echo json_encode($resultado);
                break;
                
            case 'actualizar_gasto_externo':
                $datos = [
                    'gasto_id' => $_POST['gasto_id'] ?? '',
                    'vehiculo_id' => $_POST['vehiculo_id'] ?? '',
                    'taller_nombre' => $_POST['taller_nombre'] ?? '',
                    'servicio' => $_POST['servicio'] ?? '',
                    'costo_total' => $_POST['costo_total'] ?? '',
                    'fecha' => $_POST['fecha'] ?? '',
                    'observaciones' => $_POST['observaciones'] ?? ''
                ];
                $resultado = actualizarGastoExterno($datos);
                echo json_encode($resultado);
                break;
                
            case 'eliminar_gasto_externo':
                $gastoId = $_POST['gasto_id'] ?? 0;
                $resultado = eliminarGastoExterno($gastoId);
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

