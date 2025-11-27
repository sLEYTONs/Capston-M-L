<?php
// INICIAR SESIÓN AL PRINCIPIO
session_start();

require '../functions/f_tareas.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar sesión usando la estructura correcta
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id'])) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'No autenticado - Sesión no válida'
        ]);
        exit;
    }
    
    $mecanico_id = $_SESSION['usuario']['id'];

    $asignacion_id = intval($_POST['asignacion_id'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado = trim($_POST['estado'] ?? 'En progreso');

    if (!$asignacion_id || !$descripcion) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        exit;
    }

    try {
        // Procesar fotos si se enviaron
        $fotos = [];
        if (!empty($_FILES['avance_fotos']) && is_array($_FILES['avance_fotos']['tmp_name'])) {
            // Reorganizar el array de archivos múltiples
            $fileCount = count($_FILES['avance_fotos']['tmp_name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['avance_fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    $fotos[] = [
                        'name' => $_FILES['avance_fotos']['name'][$i],
                        'type' => $_FILES['avance_fotos']['type'][$i],
                        'tmp_name' => $_FILES['avance_fotos']['tmp_name'][$i],
                        'error' => $_FILES['avance_fotos']['error'][$i],
                        'size' => $_FILES['avance_fotos']['size'][$i]
                    ];
                }
            }
        }

        $resultado = registrarAvanceConFotos($asignacion_id, $descripcion, $estado, $fotos);
        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error al registrar avance: ' . $e->getMessage()
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Método no permitido'
]);
?>