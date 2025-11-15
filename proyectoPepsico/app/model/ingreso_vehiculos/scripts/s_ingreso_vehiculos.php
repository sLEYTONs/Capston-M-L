<?php
require_once '../functions/f_ingreso_vehiculos.php';

header('Content-Type: application/json');

// Configuración para CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'registrar_ingreso':
                $response = ['success' => false, 'message' => ''];
                
                // Validaciones básicas
                $campos_obligatorios = [
                    'placa', 'tipo_vehiculo', 'marca', 'modelo', 
                    'conductor_nombre', 'conductor_cedula', 
                    'empresa_codigo', 'empresa_nombre', 
                    'proposito', 'estado_ingreso', 'combustible'
                ];
                
                $campos_faltantes = [];
                foreach ($campos_obligatorios as $campo) {
                    if (empty($_POST[$campo])) {
                        $campos_faltantes[] = $campo;
                    }
                }
                
                if (!empty($campos_faltantes)) {
                    $response['message'] = 'Campos obligatorios faltantes: ' . implode(', ', $campos_faltantes);
                    echo json_encode($response);
                    exit;
                }
                
                // Validar longitud de campos
                if (strlen($_POST['placa']) > 10) {
                    $response['message'] = 'La placa no puede tener más de 10 caracteres';
                    echo json_encode($response);
                    exit;
                }
                
                if (strlen($_POST['conductor_cedula']) > 15) {
                    $response['message'] = 'La cédula no puede tener más de 15 caracteres';
                    echo json_encode($response);
                    exit;
                }
                
                // Verificar si la placa ya existe
                if (placaExiste($_POST['placa'])) {
                    $response['message'] = 'La placa ' . $_POST['placa'] . ' ya está registrada en el sistema';
                    echo json_encode($response);
                    exit;
                }
                
                // NUEVA VALIDACIÓN: Verificar si el chasis ya existe (solo si no está vacío)
                $chasis = trim($_POST['chasis'] ?? '');
                if (!empty($chasis)) {
                    if (chasisExiste($chasis)) {
                        $response['message'] = 'El chasis ' . $chasis . ' ya está registrado en el sistema';
                        echo json_encode($response);
                        exit;
                    }
                }
                
                // Preparar datos
                $datos = [
                    'placa' => trim($_POST['placa']),
                    'tipo_vehiculo' => trim($_POST['tipo_vehiculo']),
                    'marca' => trim($_POST['marca']),
                    'modelo' => trim($_POST['modelo']),
                    'chasis' => $chasis,
                    'color' => trim($_POST['color'] ?? ''),
                    'anio' => $_POST['anio'] ?? '',
                    'conductor_nombre' => trim($_POST['conductor_nombre']),
                    'conductor_cedula' => trim($_POST['conductor_cedula']),
                    'conductor_telefono' => $_POST['conductor_telefono'] ?? '',
                    'licencia' => $_POST['licencia'] ?? '',
                    'empresa_codigo' => trim($_POST['empresa_codigo']),
                    'empresa_nombre' => trim($_POST['empresa_nombre']),
                    'proposito' => trim($_POST['proposito']),
                    'area' => $_POST['area'] ?? '',
                    'persona_contacto' => $_POST['persona_contacto'] ?? '',
                    'observaciones' => $_POST['observaciones'] ?? '',
                    'estado_ingreso' => trim($_POST['estado_ingreso']),
                    'kilometraje' => $_POST['kilometraje'] ?? '',
                    'combustible' => trim($_POST['combustible']),
                    'usuario_id' => intval($_POST['usuario_id'] ?? 1),
                    'documentos' => $_FILES['documentos'] ?? [],
                    'fotos' => $_FILES['fotos'] ?? []
                ];
                
                // Registrar ingreso
                $ingreso_id = registrarIngresoVehiculo($datos);
                
                if ($ingreso_id) {
                    // Obtener roles para notificación
                    $roles_notificar = obtenerRolesParaNotificacion($datos['proposito']);
                    $mensaje_notificacion = "Nuevo ingreso de vehículo: {$datos['placa']} - {$datos['marca']} {$datos['modelo']} - Conductor: {$datos['conductor_nombre']}";
                    guardarNotificacion($ingreso_id, $roles_notificar, $mensaje_notificacion);
                    
                    $response['success'] = true;
                    $response['message'] = "Vehículo registrado exitosamente. ID: {$ingreso_id}";
                    $response['ingreso_id'] = $ingreso_id;
                } else {
                    $response['message'] = 'Error al registrar el vehículo en la base de datos';
                }
                
                echo json_encode($response);
                break;
                
            default:
                echo json_encode([
                    'success' => false, 
                    'message' => 'Acción no válida'
                ]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Método no permitido'
    ]);
}
?>