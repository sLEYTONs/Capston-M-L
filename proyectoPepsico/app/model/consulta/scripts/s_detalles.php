<?php
require '../functions/f_consulta.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    $vehiculo = obtenerVehiculoPorID($id);

    if ($vehiculo) {
        echo json_encode(['status' => 'success', 'data' => $vehiculo]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Vehículo no encontrado']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
?>