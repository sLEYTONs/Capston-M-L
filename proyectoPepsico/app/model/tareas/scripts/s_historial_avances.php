<?php
require '../functions/f_tareas.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignacion_id'])) {
    $asignacion_id = intval($_POST['asignacion_id']);

    try {
        $historial = obtenerHistorialAvances($asignacion_id);
        echo json_encode([
            'status' => 'success',
            'data' => $historial
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al cargar historial: ' . $e->getMessage()
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