<?php
require '../functions/f_consulta.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que el ID esté presente
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID del vehículo no especificado']);
        exit;
    }

    // Recoger y sanitizar los datos (usando los nombres correctos de la base de datos)
    $datos = [
        'id' => intval($_POST['id']),
        'Placa' => trim($_POST['Placa'] ?? ''),
        'TipoVehiculo' => trim($_POST['TipoVehiculo'] ?? ''),
        'Marca' => trim($_POST['Marca'] ?? ''),
        'Modelo' => trim($_POST['Modelo'] ?? ''),
        'Color' => trim($_POST['Color'] ?? ''),
        'Anio' => !empty($_POST['Anio']) ? intval($_POST['Anio']) : null,
        'ConductorNombre' => trim($_POST['ConductorNombre'] ?? ''),
        'ConductorCedula' => trim($_POST['ConductorCedula'] ?? ''),
        'ConductorTelefono' => trim($_POST['ConductorTelefono'] ?? ''),
        'Licencia' => trim($_POST['Licencia'] ?? ''),
        'EmpresaCodigo' => trim($_POST['EmpresaCodigo'] ?? ''),
        'EmpresaNombre' => trim($_POST['EmpresaNombre'] ?? ''),
        'Proposito' => trim($_POST['Proposito'] ?? ''),
        'Area' => trim($_POST['Area'] ?? ''),
        'PersonaContacto' => trim($_POST['PersonaContacto'] ?? ''),
        'Observaciones' => trim($_POST['Observaciones'] ?? ''),
        'Estado' => trim($_POST['Estado'] ?? 'active')
    ];

    try {
        // Validar campos requeridos
        $camposRequeridos = [
            'Placa', 'TipoVehiculo', 'Marca', 'Modelo', 
            'ConductorNombre', 'ConductorCedula', 
            'EmpresaCodigo', 'EmpresaNombre', 'Proposito'
        ];

        $camposFaltantes = [];
        foreach ($camposRequeridos as $campo) {
            if (empty($datos[$campo])) {
                $camposFaltantes[] = $campo;
            }
        }

        if (!empty($camposFaltantes)) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Campos requeridos faltantes: ' . implode(', ', $camposFaltantes)
            ]);
            exit;
        }

        // Validar año si se proporciona
        if ($datos['Anio'] !== null && ($datos['Anio'] < 1980 || $datos['Anio'] > 2025)) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'El año debe estar entre 1980 y 2025'
            ]);
            exit;
        }

        // Actualizar el vehículo
        $resultado = actualizarVehiculo($datos);
        
        echo json_encode($resultado);

    } catch (Exception $e) {
        error_log("Error en s_editar.php: " . $e->getMessage());
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