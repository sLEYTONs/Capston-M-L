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
    require_once '../functions/f_reportes_semanales.php';
    require_once '../functions/f_exportar_excel.php';
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error al cargar funciones: ' . $e->getMessage()]);
    exit;
}

// Limpiar cualquier output no deseado
ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $accion = $_POST['accion'] ?? '';
        
        switch ($accion) {
            case 'generar_reporte_semanal':
                $semana = $_POST['semana'] ?? '';
                if (empty($semana)) {
                    echo json_encode(['status' => 'error', 'message' => 'Semana no especificada']);
                    break;
                }
                $resultado = generarReporteSemanal($semana);
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

// Exportación Excel (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'exportar_excel') {
    $semana = $_GET['semana'] ?? '';
    if (empty($semana)) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Semana no especificada']);
        exit;
    }

    try {
        $resultado = generarReporteSemanal($semana);
        
        if ($resultado['status'] === 'success') {
            exportarReporteSemanalExcel($resultado['data'], $semana);
        } else {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode($resultado);
            exit;
        }
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Error al generar reporte: ' . $e->getMessage()]);
        exit;
    }
}

http_response_code(405);
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);

