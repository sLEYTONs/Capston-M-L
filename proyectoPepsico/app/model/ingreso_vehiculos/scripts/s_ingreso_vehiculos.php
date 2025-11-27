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
            case 'buscar_ingreso_pendiente':
            case 'buscar_por_placa':
                $placa = $_POST['placa'] ?? '';
                
                if (empty($placa)) {
                    echo json_encode(['success' => false, 'message' => 'Placa requerida']);
                    exit;
                }
                
                try {
                    $vehiculo = buscarIngresoPendiente($placa);
                    
                    if ($vehiculo) {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Vehículo encontrado',
                            'data' => $vehiculo
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'No se encontró un vehículo ingresado con esta placa. Verifique que la placa esté correcta y que el vehículo tenga estado "Ingresado".'
                        ]);
                    }
                } catch (Exception $e) {
                    error_log("Error en buscar_por_placa: " . $e->getMessage());
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Error al buscar vehículo: ' . $e->getMessage()
                    ]);
                }
                break;
                
            case 'actualizar_ingreso':
                $response = ['success' => false, 'message' => ''];
                
                // Validaciones básicas
                $campos_obligatorios = [
                    'ingreso_id', 'placa', 'tipo_vehiculo', 'marca', 'modelo', 
                    'conductor_nombre', 
                    'proposito', 'estado_ingreso'
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
                
                // Validación de cédula eliminada - columna no existe
                
                // Validación de formato de nombre
                $nombreConductor = trim($_POST['conductor_nombre']);
                if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombreConductor)) {
                    $response['message'] = 'El nombre del conductor solo puede contener letras y espacios';
                    echo json_encode($response);
                    exit;
                }
                
                // Validación de teléfono (si se proporciona)
                $telefono = trim($_POST['conductor_telefono'] ?? '');
                if (!empty($telefono) && !preg_match('/^\d{8,15}$/', $telefono)) {
                    $response['message'] = 'El teléfono debe contener solo números y tener entre 8 y 15 dígitos';
                    echo json_encode($response);
                    exit;
                }
                
                // NORMALIZAR DATOS
                $placa_normalizada = strtoupper(trim($_POST['placa']));
                
                // Verificar duplicados (excluyendo el registro actual)
                $campos_duplicados = [];
                $datos_duplicados = [];
                
                // Verificar si la placa ya existe en otro registro
                if (placaExisteEnOtroRegistro($placa_normalizada, $_POST['ingreso_id'])) {
                    $campos_duplicados[] = 'placa';
                    $datos_duplicados['placa'] = obtenerInfoPlacaDuplicada($placa_normalizada);
                }
                
                // Verificar si la cédula ya existe en otro registro
                if (cedulaExisteEnOtroRegistro($cedula_normalizada, $_POST['ingreso_id'])) {
                    $campos_duplicados[] = 'cedula';
                    $datos_duplicados['cedula'] = obtenerInfoCedulaDuplicada($cedula_normalizada);
                }
                
                // Si hay campos duplicados, retornar error específico
                if (!empty($campos_duplicados)) {
                    $response['duplicated_fields'] = $campos_duplicados;
                    $response['duplicated_data'] = $datos_duplicados;
                    
                    $mensajes_campos = [
                        'placa' => 'La placa ' . $placa_normalizada . ' ya está registrada en otro vehículo',
                        'cedula' => 'La cédula ' . $cedula_normalizada . ' ya está registrada en otro conductor'
                    ];
                    
                    $mensajes = [];
                    foreach ($campos_duplicados as $campo) {
                        $mensajes[] = $mensajes_campos[$campo];
                    }
                    
                    $response['message'] = implode('. ', $mensajes);
                    echo json_encode($response);
                    exit;
                }
                
                // Preparar datos
                $datos = [
                    'ingreso_id' => intval($_POST['ingreso_id']),
                    'placa' => $placa_normalizada,
                    'tipo_vehiculo' => trim($_POST['tipo_vehiculo']),
                    'marca' => trim($_POST['marca']),
                    'modelo' => trim($_POST['modelo']),
                    'anio' => $_POST['anio'] ?? '',
                    'conductor_nombre' => trim($_POST['conductor_nombre']),
                    'conductor_telefono' => $_POST['conductor_telefono'] ?? '',
                    'proposito' => trim($_POST['proposito']),
                    'area' => $_POST['area'] ?? '',
                    'persona_contacto' => $_POST['persona_contacto'] ?? '',
                    'observaciones' => $_POST['observaciones'] ?? '',
                    'estado_ingreso' => trim($_POST['estado_ingreso']),
                    'kilometraje' => $_POST['kilometraje'] ?? '',
                    'usuario_id' => intval($_POST['usuario_id'] ?? 1),
                    'documentos' => []
                ];
                
                // Procesar archivos subidos
                if (!empty($_FILES['documentos'])) {
                    foreach ($_FILES['documentos']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['documentos']['error'][$key] === UPLOAD_ERR_OK) {
                            $archivo = [
                                'name' => $_FILES['documentos']['name'][$key],
                                'type' => $_FILES['documentos']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['documentos']['error'][$key],
                                'size' => $_FILES['documentos']['size'][$key]
                            ];
                            $resultado = subirArchivo($archivo, 'documento');
                            if ($resultado['success']) {
                                $datos['documentos'][] = $resultado;
                            }
                        }
                    }
                }
                
                // Actualizar registro
                $ingreso_id = actualizarIngresoVehiculo($datos);
                
                if ($ingreso_id) {
                    $response['success'] = true;
                    $response['message'] = "Registro actualizado exitosamente. ID: {$ingreso_id}";
                    $response['ingreso_id'] = $ingreso_id;
                } else {
                    $response['message'] = 'Error al actualizar el registro';
                }
                
                echo json_encode($response);
                break;

            case 'registrar_ingreso':
                $response = ['success' => false, 'message' => '', 'duplicated_fields' => [], 'duplicated_data' => []];
                
                // Validaciones básicas
                $campos_obligatorios = [
                    'placa', 'tipo_vehiculo', 'marca', 'modelo', 
                    'conductor_nombre', 
                    'proposito', 'estado_ingreso'
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
                
                // Validación de cédula eliminada - columna no existe
                
                // Validación de formato de nombre
                $nombreConductor = trim($_POST['conductor_nombre']);
                if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombreConductor)) {
                    $response['message'] = 'El nombre del conductor solo puede contener letras y espacios';
                    echo json_encode($response);
                    exit;
                }
                
                // Validación de teléfono (si se proporciona)
                $telefono = trim($_POST['conductor_telefono'] ?? '');
                if (!empty($telefono) && !preg_match('/^\d{8,15}$/', $telefono)) {
                    $response['message'] = 'El teléfono debe contener solo números y tener entre 8 y 15 dígitos';
                    echo json_encode($response);
                    exit;
                }
                
                // NORMALIZAR DATOS PARA EVITAR DUPLICADOS POR FORMATO
                $placa_normalizada = strtoupper(trim($_POST['placa']));
                
                // DEBUG: Mostrar valores normalizados
                error_log("DEBUG - Placa normalizada: " . $placa_normalizada);
                
                // Verificar duplicados y recolectar campos duplicados
                $campos_duplicados = [];
                $datos_duplicados = [];
                
                // Verificar si la placa ya existe
                if (placaExiste($placa_normalizada)) {
                    error_log("DEBUG - Placa duplicada encontrada: " . $placa_normalizada);
                    $campos_duplicados[] = 'placa';
                    $datos_duplicados['placa'] = obtenerInfoPlacaDuplicada($placa_normalizada);
                }
                
                // DEBUG: Mostrar resultados de validación
                error_log("DEBUG - Campos duplicados encontrados: " . implode(', ', $campos_duplicados));
                
                // Si hay campos duplicados, retornar error específico
                if (!empty($campos_duplicados)) {
                    $response['duplicated_fields'] = $campos_duplicados;
                    $response['duplicated_data'] = $datos_duplicados;
                    
                    $mensajes_campos = [
                        'placa' => 'La placa ' . $placa_normalizada . ' ya está registrada en el sistema'
                    ];
                    
                    $mensajes = [];
                    foreach ($campos_duplicados as $campo) {
                        $mensajes[] = $mensajes_campos[$campo];
                    }
                    
                    $response['message'] = implode('. ', $mensajes);
                    echo json_encode($response);
                    exit;
                }
                
                // Preparar datos
                $datos = [
                    'placa' => $placa_normalizada,
                    'tipo_vehiculo' => trim($_POST['tipo_vehiculo']),
                    'marca' => trim($_POST['marca']),
                    'modelo' => trim($_POST['modelo']),
                    'anio' => $_POST['anio'] ?? '',
                    'conductor_nombre' => trim($_POST['conductor_nombre']),
                    'conductor_telefono' => $_POST['conductor_telefono'] ?? '',
                    'proposito' => trim($_POST['proposito']),
                    'area' => $_POST['area'] ?? '',
                    'persona_contacto' => $_POST['persona_contacto'] ?? '',
                    'observaciones' => $_POST['observaciones'] ?? '',
                    'estado_ingreso' => trim($_POST['estado_ingreso']),
                    'kilometraje' => $_POST['kilometraje'] ?? '',
                    'usuario_id' => intval($_POST['usuario_id'] ?? 1),
                    'documentos' => [],
                    'fotos' => []
                ];
                
                // Procesar archivos subidos
                if (!empty($_FILES['documentos'])) {
                    foreach ($_FILES['documentos']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['documentos']['error'][$key] === UPLOAD_ERR_OK) {
                            $archivo = [
                                'name' => $_FILES['documentos']['name'][$key],
                                'type' => $_FILES['documentos']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['documentos']['error'][$key],
                                'size' => $_FILES['documentos']['size'][$key]
                            ];
                            $resultado = subirArchivo($archivo, 'documento');
                            if ($resultado['success']) {
                                $datos['documentos'][] = $resultado;
                            }
                        }
                    }
                }
                
                if (!empty($_FILES['fotos'])) {
                    foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
                            $archivo = [
                                'name' => $_FILES['fotos']['name'][$key],
                                'type' => $_FILES['fotos']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['fotos']['error'][$key],
                                'size' => $_FILES['fotos']['size'][$key]
                            ];
                            $resultado = subirArchivo($archivo, 'foto');
                            if ($resultado['success']) {
                                $datos['fotos'][] = $resultado;
                            }
                        }
                    }
                }
                
                // Registrar ingreso
                $ingreso_id = registrarIngresoVehiculo($datos);
                
                if ($ingreso_id) {
                    // ✅ LAS NOTIFICACIONES SE ENVÍAN AUTOMÁTICAMENTE DENTRO DE registrarIngresoVehiculo()
                    
                    $response['success'] = true;
                    $response['message'] = "Vehículo registrado exitosamente. ID: {$ingreso_id}";
                    $response['ingreso_id'] = $ingreso_id;
                    
                    // Log para debugging
                    error_log("✅ Ingreso registrado exitosamente. ID: {$ingreso_id}. Notificaciones enviadas automáticamente.");
                } else {
                    $response['message'] = 'Error al registrar el vehículo en la base de datos';
                }
                
                echo json_encode($response);
                break;
                
            case 'registrar_vehiculo':
                // Solo administradores pueden registrar vehículos nuevos
                session_start();
                if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Administrador') {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No tiene permisos para realizar esta acción'
                    ]);
                    exit;
                }

                // Validar campos requeridos
                $campos_requeridos = ['placa', 'tipo_vehiculo', 'marca', 'modelo', 'conductor_nombre'];
                $campos_faltantes = [];
                foreach ($campos_requeridos as $campo) {
                    if (empty($_POST[$campo])) {
                        $campos_faltantes[] = $campo;
                    }
                }
                
                if (!empty($campos_faltantes)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Campos requeridos faltantes: ' . implode(', ', $campos_faltantes)
                    ]);
                    exit;
                }

                // Normalizar datos
                $datos = [
                    'placa' => strtoupper(trim($_POST['placa'])),
                    'tipo_vehiculo' => trim($_POST['tipo_vehiculo']),
                    'marca' => trim($_POST['marca']),
                    'modelo' => trim($_POST['modelo']),
                    'anio' => !empty($_POST['anio']) ? intval($_POST['anio']) : null,
                    'conductor_nombre' => trim($_POST['conductor_nombre']),
                    'kilometraje' => !empty($_POST['kilometraje']) ? intval($_POST['kilometraje']) : null,
                    'usuario_id' => $_SESSION['usuario']['id']
                ];

                try {
                    $id_vehiculo = registrarVehiculoPepsico($datos);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Vehículo registrado correctamente',
                        'vehiculo_id' => $id_vehiculo
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                break;

            default:
                echo json_encode([
                    'success' => false, 
                    'message' => 'Acción no válida'
                ]);
                break;
        }
    } catch (Exception $e) {
        error_log("ERROR en s_ingreso_vehiculos: " . $e->getMessage());
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