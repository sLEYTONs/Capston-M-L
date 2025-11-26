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
    require_once '../functions/f_coordinacion_jefe_taller.php';
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
            case 'crear_comunicacion':
                $datos = [
                    'tipo_solicitud' => $_POST['tipo_solicitud'] ?? '',
                    'prioridad' => $_POST['prioridad'] ?? '',
                    'asunto' => $_POST['asunto'] ?? '',
                    'descripcion' => $_POST['descripcion'] ?? ''
                ];
                $resultado = crearComunicacionJefeTaller($datos);
                echo json_encode($resultado);
                break;
                
            case 'obtener_comunicaciones':
                $resultado = obtenerComunicacionesJefeTaller();
                echo json_encode($resultado);
                break;
                
            case 'obtener_detalles':
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
                    break;
                }
                $resultado = obtenerDetallesComunicacion($id);
                echo json_encode($resultado);
                break;
                
            case 'obtener_estadisticas':
                $resultado = obtenerEstadisticasComunicaciones();
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

