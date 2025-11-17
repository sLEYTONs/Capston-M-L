<?php
// INICIAR SESIÓN AL PRINCIPIO
session_start();

// Depuración extensiva
error_log("=== s_tareas.php - INICIO ===");
error_log("Session ID: " . session_id());
error_log("SESSION array: " . print_r($_SESSION, true));

require '../functions/f_tareas.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Verificar sesión usando la estructura correcta
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id'])) {
        error_log("ERROR: No hay usuario en sesión o estructura incorrecta");
        echo json_encode([
            'status' => 'error', 
            'message' => 'No autenticado - Sesión no válida',
            'session_data' => $_SESSION
        ]);
        exit;
    }

    $mecanico_id = $_SESSION['usuario']['id'];
    $mecanico_rol = $_SESSION['usuario']['rol'];
    
    error_log("Mecánico ID: " . $mecanico_id);
    error_log("Mecánico Rol: " . $mecanico_rol);
    error_log("Buscando tareas para mecánico ID: " . $mecanico_id);

    try {
        $tareas = obtenerTareasMecanico($mecanico_id);
        error_log("Tareas encontradas: " . count($tareas));
        
        // Depurar primeras tareas
        if (count($tareas) > 0) {
            error_log("Primera tarea: " . json_encode($tareas[0]));
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $tareas,
            'debug' => [
                'mecanico_id' => $mecanico_id,
                'mecanico_rol' => $mecanico_rol,
                'total_tareas' => count($tareas)
            ]
        ]);
    } catch (Exception $e) {
        error_log("EXCEPCIÓN en s_tareas.php: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al cargar tareas: ' . $e->getMessage()
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