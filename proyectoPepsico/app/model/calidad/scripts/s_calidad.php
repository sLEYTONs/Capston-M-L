<?php
require_once __DIR__ . '/../functions/f_calidad.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'obtener_asignaciones':
                $filtros = [
                    'estado' => $_POST['estado'] ?? '',
                    'estado_calidad' => $_POST['estado_calidad'] ?? '',
                    'mecanico_id' => $_POST['mecanico_id'] ?? '',
                    'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
                    'fecha_fin' => $_POST['fecha_fin'] ?? ''
                ];
                
                $resultado = obtenerAsignacionesRevision($filtros);
                echo json_encode($resultado);
                break;
                
            case 'obtener_historial':
                $asignacion_id = intval($_POST['asignacion_id'] ?? 0);
                if ($asignacion_id > 0) {
                    $resultado = obtenerHistorialCompletoAvances($asignacion_id);
                    echo json_encode($resultado);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'ID de asignación inválido'
                    ]);
                }
                break;
                
            case 'registrar_revision':
                session_start();
                $usuario_revisor_id = $_SESSION['usuario']['id'] ?? 0;
                
                if ($usuario_revisor_id == 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Usuario no autenticado'
                    ]);
                    break;
                }
                
                $asignacion_id = intval($_POST['asignacion_id'] ?? 0);
                $diagnostico_falla = trim($_POST['diagnostico_falla'] ?? '');
                $estado_calidad = trim($_POST['estado_calidad'] ?? '');
                $observaciones = trim($_POST['observaciones'] ?? '');
                
                if ($asignacion_id == 0 || empty($estado_calidad)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Datos incompletos'
                    ]);
                    break;
                }
                
                $resultado = registrarRevisionCalidad(
                    $asignacion_id,
                    $diagnostico_falla,
                    $estado_calidad,
                    $observaciones,
                    $usuario_revisor_id
                );
                echo json_encode($resultado);
                break;
                
            case 'obtener_estadisticas':
                $resultado = obtenerEstadisticasCalidad();
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
        error_log("Error en s_calidad.php: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Error del servidor: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Si no es POST, devolver error
http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Método no permitido'
]);
?>

