<?php
require '../functions/f_tareas.php';

header('Content-Type: application/json');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['UsuarioID'])) {
        echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
        exit;
    }

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
        if (!empty($_FILES['avance_fotos'])) {
            $fotos = $_FILES['avance_fotos'];
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