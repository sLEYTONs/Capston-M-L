<?php
require_once __DIR__ . '/../functions/f_recepcion_tecnica.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'crear_ot':
                $datos = [
                    'vehiculo_id' => $_POST['vehiculo_id'] ?? 0,
                    'recepcionista_id' => $_SESSION['usuario']['id'] ?? 0,
                    'usuario_creacion_id' => $_SESSION['usuario']['id'] ?? 0,
                    'tipo_trabajo' => $_POST['tipo_trabajo'] ?? '',
                    'descripcion_trabajo' => $_POST['descripcion_trabajo'] ?? '',
                    'observaciones' => $_POST['observaciones'] ?? '',
                    'documentos_validados' => isset($_POST['documentos_validados']) ? (int)$_POST['documentos_validados'] : 0,
                    'fotos' => isset($_POST['fotos']) ? json_decode($_POST['fotos'], true) : [],
                    'documentos' => isset($_POST['documentos']) ? json_decode($_POST['documentos'], true) : []
                ];
                
                $resultado = crearOrdenTrabajo($datos);
                echo json_encode($resultado);
                break;
                
            case 'obtener_ots':
                $filtros = [
                    'estado' => $_POST['estado'] ?? '',
                    'recepcionista_id' => $_POST['recepcionista_id'] ?? ($_SESSION['usuario']['id'] ?? 0),
                    'placa' => $_POST['placa'] ?? '',
                    'numero_ot' => $_POST['numero_ot'] ?? '',
                    'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
                    'fecha_fin' => $_POST['fecha_fin'] ?? ''
                ];
                
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
                
            case 'validar_documentacion':
                $ot_id = intval($_POST['ot_id'] ?? 0);
                $documentos_validados = isset($_POST['documentos_validados']) ? (int)$_POST['documentos_validados'] : 0;
                $observaciones = $_POST['observaciones'] ?? '';
                $usuario_id = $_SESSION['usuario']['id'] ?? 0;
                
                if ($ot_id == 0 || $usuario_id == 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Datos incompletos'
                    ]);
                    break;
                }
                
                $resultado = validarDocumentacion($ot_id, $documentos_validados, $observaciones, $usuario_id);
                echo json_encode($resultado);
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
                
            case 'actualizar_documentos':
                $ot_id = intval($_POST['ot_id'] ?? 0);
                $documentos = isset($_POST['documentos']) ? json_decode($_POST['documentos'], true) : [];
                
                if ($ot_id == 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'ID de OT inválido'
                    ]);
                    break;
                }
                
                $resultado = actualizarDocumentosOT($ot_id, $documentos);
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

