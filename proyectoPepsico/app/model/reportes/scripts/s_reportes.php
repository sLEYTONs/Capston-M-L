<?php
require '../functions/f_reportes.php';

// Verificar si es una petición de exportación (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Exportar a Excel
    $filtros = [
        'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
        'fecha_fin' => $_GET['fecha_fin'] ?? '',
        'mecanico_id' => $_GET['mecanico_id'] ?? '',
        'vehiculo_id' => $_GET['vehiculo_id'] ?? '',
        'estado' => $_GET['estado'] ?? ''
    ];
    
    $resultado = obtenerReportesMantenimientos($filtros);
    
    if ($resultado['status'] === 'success') {
        exportarReportesExcel($resultado['data']);
    } else {
        header('Content-Type: application/json');
        echo json_encode($resultado);
    }
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'obtener_reportes':
                $filtros = [
                    'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
                    'fecha_fin' => $_POST['fecha_fin'] ?? '',
                    'mecanico_id' => $_POST['mecanico_id'] ?? '',
                    'vehiculo_id' => $_POST['vehiculo_id'] ?? '',
                    'estado' => $_POST['estado'] ?? ''
                ];
                
                $resultado = obtenerReportesMantenimientos($filtros);
                echo json_encode($resultado);
                break;
                
            case 'obtener_estadisticas':
                $filtros = [
                    'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
                    'fecha_fin' => $_POST['fecha_fin'] ?? '',
                    'mecanico_id' => $_POST['mecanico_id'] ?? '',
                    'vehiculo_id' => $_POST['vehiculo_id'] ?? '',
                    'estado' => $_POST['estado'] ?? ''
                ];
                
                $resultado = obtenerEstadisticasMantenimientos($filtros);
                echo json_encode($resultado);
                break;
                
            case 'obtener_repuestos':
                $asignacion_id = intval($_POST['asignacion_id'] ?? 0);
                if ($asignacion_id > 0) {
                    $repuestos = obtenerRepuestosAsignacion($asignacion_id);
                    echo json_encode([
                        'status' => 'success',
                        'data' => $repuestos
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'ID de asignación inválido'
                    ]);
                }
                break;
                
            default:
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Acción no válida'
                ]);
                break;
        }
    } catch (Exception $e) {
        error_log("Error en s_reportes.php: " . $e->getMessage());
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

