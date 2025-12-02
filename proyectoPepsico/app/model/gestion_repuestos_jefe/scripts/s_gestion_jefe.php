<?php
session_start();
require_once '../functions/f_gestion_jefe.php';

header('Content-Type: application/json');

// Obtener acción desde POST o GET
$accion = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = $_GET['action'] ?? $_GET['accion'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($accion) {
        case 'crear_solicitud':
            $datos = [
                'tipo_solicitud' => $_POST['tipo_solicitud'] ?? '',
                'prioridad' => $_POST['prioridad'] ?? '',
                'asunto' => $_POST['asunto'] ?? '',
                'descripcion' => $_POST['descripcion'] ?? '',
                'archivos' => isset($_FILES['archivos']) ? $_FILES['archivos'] : null
            ];
            
            // Procesar archivos si existen
            if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
                $archivosInfo = [];
                $uploadDir = __DIR__ . '/../../../../uploads/documentos/';
                
                // Crear directorio si no existe
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                foreach ($_FILES['archivos']['name'] as $key => $name) {
                    if ($_FILES['archivos']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['archivos']['tmp_name'][$key];
                        $fileName = uniqid() . '_' . time() . '_' . basename($name);
                        $filePath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($tmpName, $filePath)) {
                            $archivosInfo[] = [
                                'nombre' => $name,
                                'archivo' => 'uploads/documentos/' . $fileName
                            ];
                        }
                    }
                }
                
                $datos['archivos'] = $archivosInfo;
            }
            
            $resultado = crearSolicitudJefe($datos);
            echo json_encode($resultado);
            break;
            
        case 'obtener_solicitudes_pendientes':
            $resultado = obtenerSolicitudesPendientes();
            echo json_encode($resultado);
            break;
            
        case 'obtener_comunicaciones':
            $resultado = obtenerComunicacionesJefe();
            echo json_encode($resultado);
            break;
            
        case 'obtener_estadisticas':
            $resultado = obtenerEstadisticasJefe();
            echo json_encode($resultado);
            break;
            
        case 'obtener_todos_repuestos':
            $resultado = obtenerTodosRepuestos();
            echo json_encode($resultado);
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($accion) {
        case 'obtener_todos_repuestos':
        case 'obtener_repuestos':
            $resultado = obtenerTodosRepuestos();
            echo json_encode($resultado);
            exit;
            
        case 'obtener_solicitudes_pendientes':
            $resultado = obtenerSolicitudesPendientes();
            echo json_encode($resultado);
            exit;
            
        case 'obtener_comunicaciones':
            $resultado = obtenerComunicacionesJefe();
            echo json_encode($resultado);
            exit;
            
        case 'obtener_estadisticas':
            $resultado = obtenerEstadisticasJefe();
            echo json_encode($resultado);
            exit;
    }
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);

