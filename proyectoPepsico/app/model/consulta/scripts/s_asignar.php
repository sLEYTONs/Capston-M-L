<?php
require '../functions/f_consulta.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['vehiculo_id']) || !isset($_POST['mecanico_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        exit;
    }

    $vehiculo_id = intval($_POST['vehiculo_id']);
    $mecanico_id = intval($_POST['mecanico_id']);
    $prioridad = $_POST['prioridad'] ?? 'Media';
    $descripcion = trim($_POST['descripcion'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');

    try {
        $resultado = asignarMecanico($vehiculo_id, $mecanico_id, $prioridad, $descripcion, $observaciones);
        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error al asignar: ' . $e->getMessage()
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