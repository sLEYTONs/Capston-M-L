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

try {
    require_once __DIR__ . '/../functions/f_consulta.php';
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al cargar funciones: ' . $e->getMessage()
    ]);
    exit;
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fatal al cargar funciones: ' . $e->getMessage()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $id = intval($_POST['id']);
        
        if ($id <= 0) {
            throw new Exception('ID de vehículo inválido');
        }
        
        $vehiculo = obtenerVehiculoPorID($id);

        if ($vehiculo) {
            ob_clean();
            echo json_encode(['status' => 'success', 'data' => $vehiculo]);
        } else {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Vehículo no encontrado']);
        }
    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al obtener vehículo: ' . $e->getMessage()
        ]);
    } catch (Error $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error fatal al obtener vehículo: ' . $e->getMessage()
        ]);
    }
    exit;
}

ob_clean();
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
?>