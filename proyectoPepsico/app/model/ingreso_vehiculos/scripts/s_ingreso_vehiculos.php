<?php
require_once '../functions/f_ingreso_vehiculos.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'registrar_ingreso':
            $response = ['success' => false, 'message' => ''];
            
            // Validaciones básicas
            if (empty($_POST['placa']) || empty($_POST['tipo_vehiculo']) || empty($_POST['marca']) || empty($_POST['modelo'])) {
                $response['message'] = 'Todos los campos obligatorios deben ser completados';
                echo json_encode($response);
                exit;
            }
            
            // Verificar si la placa ya existe
            if (placaExiste($_POST['placa'])) {
                $response['message'] = 'La placa ya está registrada en el sistema con un vehículo activo';
                echo json_encode($response);
                exit;
            }
            
            // Preparar datos
            $datos = [
                'placa' => trim($_POST['placa']),
                'tipo_vehiculo' => trim($_POST['tipo_vehiculo']),
                'marca' => trim($_POST['marca']),
                'modelo' => trim($_POST['modelo']),
                'color' => trim($_POST['color'] ?? 'Sin especificar'),
                'anio' => !empty($_POST['anio']) ? intval($_POST['anio']) : NULL,
                'conductor_nombre' => trim($_POST['conductor_nombre']),
                'conductor_cedula' => trim($_POST['conductor_cedula']),
                'conductor_telefono' => trim($_POST['conductor_telefono'] ?? 'No registrado'),
                'licencia' => trim($_POST['licencia'] ?? 'No registrada'),
                'empresa_codigo' => trim($_POST['empresa_codigo']),
                'empresa_nombre' => trim($_POST['empresa_nombre']),
                'proposito' => trim($_POST['proposito']),
                'area' => trim($_POST['area'] ?? 'General'),
                'persona_contacto' => trim($_POST['persona_contacto'] ?? 'No asignado'),
                'observaciones' => trim($_POST['observaciones'] ?? ''),
                'estado_ingreso' => trim($_POST['estado_ingreso']),
                'kilometraje' => !empty($_POST['kilometraje']) ? intval($_POST['kilometraje']) : NULL,
                'combustible' => trim($_POST['combustible']),
                'documentos' => !empty($_POST['documentos']) ? json_decode($_POST['documentos'], true) : [],
                'fotos' => !empty($_POST['fotos']) ? json_decode($_POST['fotos'], true) : [],
                'usuario_id' => intval($_POST['usuario_id'])
            ];
            
            // Registrar ingreso
            $ingreso_id = registrarIngresoVehiculo($datos);
            
            if ($ingreso_id) {
                // Obtener roles para notificación
                $roles_notificar = obtenerRolesParaNotificacion($datos['proposito']);
                
                // Guardar notificación
                $mensaje_notificacion = "Nuevo ingreso de vehículo: {$datos['placa']} - {$datos['marca']} {$datos['modelo']}";
                guardarNotificacion($ingreso_id, $roles_notificar, $mensaje_notificacion);
                
                $response['success'] = true;
                $response['message'] = "Vehículo registrado exitosamente. ID: {$ingreso_id}. Se han notificado a los responsables.";
                $response['ingreso_id'] = $ingreso_id;
            } else {
                $response['message'] = 'Error al registrar el vehículo en la base de datos';
            }
            
            echo json_encode($response);
            break;
            
        case 'subir_archivo':
            // Manejar subida de archivos para Dropzone
            if (isset($_FILES['documentos']) || isset($_FILES['fotos'])) {
                $tipo = isset($_FILES['documentos']) ? 'documento' : 'foto';
                $archivo = isset($_FILES['documentos']) ? $_FILES['documentos'] : $_FILES['fotos'];
                
                $resultado = subirArchivo($archivo, $tipo);
                echo json_encode($resultado);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
}
?>