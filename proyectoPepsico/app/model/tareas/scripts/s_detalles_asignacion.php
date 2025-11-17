<?php
require '../functions/f_tareas.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignacion_id'])) {
    $asignacion_id = intval($_POST['asignacion_id']);

    try {
        $asignacion = obtenerDetallesAsignacion($asignacion_id);
        if ($asignacion) {
            echo json_encode(['status' => 'success', 'data' => $asignacion]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Asignación no encontrada']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al cargar detalles: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
?>