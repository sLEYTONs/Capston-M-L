<?php
// Evitar mostrar errores en la salida pero capturarlos
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Capturar cualquier output antes del JSON
ob_start();

// Función para manejar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error fatal: ' . $error['message'] . ' en línea ' . $error['line'] . ' de ' . basename($error['file'])
        ]);
        exit;
    }
});

try {
    $functionsPath = __DIR__ . '/../functions/f_recepcion_entrega.php';
    if (!file_exists($functionsPath)) {
        throw new Exception('Archivo de funciones no encontrado: ' . $functionsPath);
    }
    require_once $functionsPath;
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error al cargar funciones: ' . $e->getMessage()]);
    exit;
} catch (Error $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error fatal al cargar funciones: ' . $e->getMessage()]);
    exit;
}

// Limpiar cualquier output no deseado
ob_end_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Iniciar sesión solo si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
        case 'obtener_proveedores':
            $resultado = obtenerProveedoresActivos();
            echo json_encode($resultado);
            break;
            
        case 'obtener_vehiculos':
            $resultado = obtenerVehiculosActivos();
            echo json_encode($resultado);
            break;
            
        case 'obtener_mecanicos':
            $resultado = obtenerMecanicosActivos();
            echo json_encode($resultado);
            break;
            
        case 'obtener_repuestos':
            $resultado = obtenerRepuestosDisponibles();
            echo json_encode($resultado);
            break;
            
        case 'registrar_recepcion':
            $datos = [
                'proveedor_id' => $_POST['proveedor_id'] ?? 0,
                'numero_factura' => $_POST['numero_factura'] ?? '',
                'fecha_recepcion' => $_POST['fecha_recepcion'] ?? '',
                'observaciones' => $_POST['observaciones'] ?? '',
                'repuestos' => isset($_POST['repuestos']) ? json_decode($_POST['repuestos'], true) : [],
                'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0)
            ];
            $resultado = registrarRecepcion($datos);
            echo json_encode($resultado);
            break;
            
        case 'registrar_entrega':
            $datos = [
                'vehiculo_id' => $_POST['vehiculo_id'] ?? 0,
                'mecanico_id' => $_POST['mecanico_id'] ?? 0,
                'fecha_entrega' => $_POST['fecha_entrega'] ?? '',
                'observaciones' => $_POST['observaciones'] ?? '',
                'repuestos' => isset($_POST['repuestos']) ? json_decode($_POST['repuestos'], true) : [],
                'usuario_id' => isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0)
            ];
            $resultado = registrarEntrega($datos);
            echo json_encode($resultado);
            break;
            
        case 'obtener_historial':
            $resultado = obtenerHistorial();
            echo json_encode($resultado);
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    } catch (Error $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error fatal: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);

