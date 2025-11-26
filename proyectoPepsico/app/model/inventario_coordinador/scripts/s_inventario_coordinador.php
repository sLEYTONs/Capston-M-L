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
    require_once '../functions/f_inventario_coordinador.php';
    require_once '../functions/f_exportar_excel.php';
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
            case 'obtener_inventario':
                $filtros = [
                    'categoria' => $_POST['categoria'] ?? '',
                    'estado' => $_POST['estado'] ?? '',
                    'busqueda' => $_POST['busqueda'] ?? ''
                ];
                $resultado = obtenerInventarioCoordinador($filtros);
                echo json_encode($resultado);
                break;
                
            case 'obtener_estadisticas':
                $resultado = obtenerEstadisticasInventario();
                echo json_encode($resultado);
                break;
                
            case 'obtener_categorias':
                $resultado = obtenerCategoriasRepuestos();
                echo json_encode($resultado);
                break;
                
            case 'obtener_movimientos':
                $repuesto_id = intval($_POST['repuesto_id'] ?? 0);
                if ($repuesto_id <= 0) {
                    echo json_encode(['status' => 'error', 'message' => 'ID de repuesto inválido']);
                    break;
                }
                $resultado = obtenerMovimientosStock($repuesto_id);
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

// Para exportación (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'exportar_inventario') {
    try {
        $filtros = [
            'categoria' => $_GET['categoria'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'busqueda' => $_GET['busqueda'] ?? ''
        ];
        
        $resultado = obtenerInventarioCoordinador($filtros);
        
        if ($resultado['status'] === 'success') {
            exportarInventarioExcel($resultado['data'], $filtros);
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
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);

