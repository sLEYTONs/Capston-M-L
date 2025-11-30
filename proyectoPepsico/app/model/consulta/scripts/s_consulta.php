<?php
require '../functions/f_consulta.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si es una acción especial
    $action = $_POST['action'] ?? '';
    
    if ($action === 'marcarVehiculosRetirados') {
        // Marcar vehículos como retirados
        try {
            $resultado = marcarVehiculosRetirados();
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error del servidor: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    // Búsqueda normal de vehículos
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