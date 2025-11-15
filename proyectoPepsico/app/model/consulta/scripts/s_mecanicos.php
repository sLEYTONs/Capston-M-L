<?php
require '../functions/f_consulta.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mecanicos = obtenerMecanicosDisponibles();
        echo json_encode([
            'status' => 'success',
            'data' => $mecanicos
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al cargar mecánicos: ' . $e->getMessage()
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