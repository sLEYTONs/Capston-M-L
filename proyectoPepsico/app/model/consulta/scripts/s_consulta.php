<?php
require '../functions/f_consulta.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filtros = [
        'placa' => $_POST['placa'] ?? '',
        'conductor' => $_POST['conductor'] ?? '',
        'fecha' => $_POST['fecha'] ?? ''
    ];

    try {
        $resultados = buscarVehiculos($filtros);
        
        if (is_array($resultados)) {
            echo json_encode([
                'status' => 'success',
                'data' => $resultados,
                'total' => count($resultados)
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error en la búsqueda'
            ]);
        }
    } catch (Exception $e) {
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