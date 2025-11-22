<?php
session_start();
require '../functions/f_consulta.php';

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No autorizado'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehiculo_id'])) {
    $vehiculo_id = intval($_POST['vehiculo_id']);

    try {
        $vehiculo = obtenerVehiculoPorID($vehiculo_id);
        $asignacion = obtenerAsignacionActiva($vehiculo_id);
        $avances = obtenerAvancesMecanico($asignacion ? $asignacion['ID'] : null);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'vehiculo' => $vehiculo,
                'asignacion' => $asignacion,
                'avances' => $avances
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al cargar seguimiento: ' . $e->getMessage()
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