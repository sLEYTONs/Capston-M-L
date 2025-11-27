<?php
require_once __DIR__ . '/../functions/f_comunicacion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'crear_comunicacion_flota':
                $datos = [
                    'placa' => $_POST['placa'] ?? '',
                    'tipo' => $_POST['tipo'] ?? '',
                    'asunto' => $_POST['asunto'] ?? '',
                    'mensaje' => $_POST['mensaje'] ?? '',
                    'usuario_id' => $_SESSION['usuario']['id'] ?? 0,
                    'comunicacion_padre_id' => $_POST['comunicacion_padre_id'] ?? null
                ];
                
                $resultado = crearComunicacionFlota($datos);
                echo json_encode($resultado);
                break;
                
            case 'crear_comunicacion_proveedor':
                $datos = [
                    'proveedor' => $_POST['proveedor'] ?? '',
                    'contacto' => $_POST['contacto'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'telefono' => $_POST['telefono'] ?? '',
                    'tipo' => $_POST['tipo'] ?? '',
                    'asunto' => $_POST['asunto'] ?? '',
                    'mensaje' => $_POST['mensaje'] ?? '',
                    'usuario_id' => $_SESSION['usuario']['id'] ?? 0
                ];
                
                $resultado = crearComunicacionProveedor($datos);
                echo json_encode($resultado);
                break;
                
            case 'obtener_comunicaciones_flota':
                $filtros = [
                    'placa' => $_POST['filtro_placa'] ?? $_POST['placa'] ?? '',
                    'conductor' => $_POST['filtro_conductor'] ?? $_POST['conductor'] ?? '',
                    'tipo' => $_POST['filtro_tipo'] ?? $_POST['tipo'] ?? '',
                    'estado' => $_POST['filtro_estado'] ?? $_POST['estado'] ?? '',
                    'usuario_id' => $_POST['usuario_id'] ?? ''
                ];
                
                // Si el usuario es Chofer, filtrar automáticamente por su nombre
                if (isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'Chofer') {
                    $filtros['conductor'] = $_SESSION['usuario']['nombre'] ?? '';
                }
                
                // Si se envía usuario_id, filtrar por ese usuario (para Ejecutivo de Ventas)
                if (empty($filtros['usuario_id']) && isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'Ejecutivo/a de Ventas') {
                    $filtros['usuario_id'] = $_SESSION['usuario']['id'] ?? 0;
                }
                
                $resultado = obtenerComunicacionesFlota($filtros);
                echo json_encode($resultado);
                break;
                
            case 'obtener_comunicaciones_proveedor':
                $filtros = [
                    'proveedor' => $_POST['proveedor'] ?? '',
                    'tipo' => $_POST['tipo'] ?? '',
                    'estado' => $_POST['estado'] ?? ''
                ];
                
                $resultado = obtenerComunicacionesProveedor($filtros);
                echo json_encode($resultado);
                break;
                
            case 'obtener_comunicacion':
                $id = intval($_POST['id'] ?? 0);
                $tipo = $_POST['tipo'] ?? '';
                
                if ($id > 0 && !empty($tipo)) {
                    $resultado = obtenerComunicacionPorID($id, $tipo);
                    echo json_encode($resultado);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'ID o tipo inválido'
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
        error_log("Error en s_comunicacion: " . $e->getMessage());
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

