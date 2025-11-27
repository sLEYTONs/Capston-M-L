<?php
require_once __DIR__ . '/../functions/f_recepcion_tecnica.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'crear_ot':
                // Procesar fotos - asegurar que sea un array válido
                $fotos_array = [];
                if (isset($_POST['fotos']) && !empty($_POST['fotos'])) {
                    $fotos_decoded = json_decode($_POST['fotos'], true);
                    if (is_array($fotos_decoded) && count($fotos_decoded) > 0) {
                        $fotos_array = $fotos_decoded;
                    }
                }
                
                // Procesar documentos técnicos
                $documentos_array = [];
                if (isset($_POST['documentos']) && !empty($_POST['documentos'])) {
                    $documentos_decoded = json_decode($_POST['documentos'], true);
                    if (is_array($documentos_decoded) && count($documentos_decoded) > 0) {
                        $documentos_array = $documentos_decoded;
                    }
                }
                
                $datos = [
                    'vehiculo_id' => $_POST['vehiculo_id'] ?? 0,
                    'recepcionista_id' => $_SESSION['usuario']['id'] ?? 0,
                    'usuario_creacion_id' => $_SESSION['usuario']['id'] ?? 0,
                    'tipo_trabajo' => isset($_POST['tipo_trabajo']) ? trim((string)$_POST['tipo_trabajo']) : '',
                    'descripcion_trabajo' => isset($_POST['descripcion_trabajo']) ? trim((string)$_POST['descripcion_trabajo']) : '',
                    'fotos' => $fotos_array,
                    'documentos' => $documentos_array,
                    'documentos_validados' => isset($_POST['documentos_validados']) ? intval($_POST['documentos_validados']) : 0
                ];
                
                $resultado = crearOrdenTrabajo($datos);
                echo json_encode($resultado);
                break;
                
            case 'obtener_ots':
                $filtros = [
                    'estado' => $_POST['estado'] ?? '',
                    'recepcionista_id' => $_POST['recepcionista_id'] ?? '', // No filtrar por recepcionista por defecto
                    'placa' => $_POST['placa'] ?? '',
                    'numero_ot' => $_POST['numero_ot'] ?? '',
                    'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
                    'fecha_fin' => $_POST['fecha_fin'] ?? ''
                ];
                
                // Log para depuración
                error_log("obtener_ots - Filtros recibidos: " . print_r($filtros, true));
                
                $resultado = obtenerOrdenesTrabajo($filtros);
                echo json_encode($resultado);
                break;
                
            case 'obtener_ot':
                $ot_id = intval($_POST['ot_id'] ?? 0);
                if ($ot_id > 0) {
                    $resultado = obtenerOTPorID($ot_id);
                    echo json_encode($resultado);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'ID de OT inválido'
                    ]);
                }
                break;
                
            case 'actualizar_fotos':
                $ot_id = intval($_POST['ot_id'] ?? 0);
                $fotos = isset($_POST['fotos']) ? json_decode($_POST['fotos'], true) : [];
                
                if ($ot_id == 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'ID de OT inválido'
                    ]);
                    break;
                }
                
                $resultado = actualizarFotosOT($ot_id, $fotos);
                echo json_encode($resultado);
                break;
                
            default:
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Acción no válida'
                ]);
                break;
        }
    } catch (Exception $e) {
        error_log("Error en s_recepcion_tecnica: " . $e->getMessage());
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

