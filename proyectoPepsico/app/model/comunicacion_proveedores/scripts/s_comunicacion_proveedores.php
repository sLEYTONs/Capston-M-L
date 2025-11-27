<?php
require_once '../functions/f_comunicacion_proveedores.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Intentar obtener la acción de diferentes formas posibles
    $accion = trim($_POST['accion'] ?? $_POST['action'] ?? '');
    
    // Log para depuración
    error_log("s_comunicacion_proveedores.php - Acción recibida: '" . $accion . "'");
    error_log("s_comunicacion_proveedores.php - POST completo: " . print_r($_POST, true));
    
    switch ($accion) {
        case 'crear_proveedor':
            $datos = [
                'nombre' => $_POST['nombre'] ?? '',
                'contacto' => $_POST['contacto'] ?? '',
                'email' => $_POST['email'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'rut' => $_POST['rut'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'estado' => $_POST['estado'] ?? 'Activo'
            ];
            $resultado = crearProveedor($datos);
            echo json_encode($resultado);
            break;
            
        case 'actualizar_proveedor':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de proveedor inválido']);
                break;
            }
            $datos = [
                'nombre' => $_POST['nombre'] ?? '',
                'contacto' => $_POST['contacto'] ?? '',
                'email' => $_POST['email'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'rut' => $_POST['rut'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'estado' => $_POST['estado'] ?? 'Activo'
            ];
            $resultado = actualizarProveedor($id, $datos);
            echo json_encode($resultado);
            break;
            
        case 'obtener_proveedores':
            $resultado = obtenerProveedores();
            echo json_encode($resultado);
            break;
            
        case 'obtener_proveedor':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de proveedor inválido']);
                break;
            }
            $resultado = obtenerProveedorPorId($id);
            echo json_encode($resultado);
            break;
            
        case 'eliminar_proveedor':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de proveedor inválido']);
                break;
            }
            $resultado = eliminarProveedor($id);
            echo json_encode($resultado);
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);

