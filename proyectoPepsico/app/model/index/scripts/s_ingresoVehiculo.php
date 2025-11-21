<?php
require '../functions/f_index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Procesar y validar datos
    $vehiculo = [
        'Placa'             => trim($_POST['plate'] ?? ''),
        'TipoVehiculo'      => trim($_POST['vehicleType'] ?? ''),
        'Marca'             => trim($_POST['brand'] ?? ''),
        'Modelo'            => trim($_POST['model'] ?? ''),
        'Color'             => trim($_POST['color'] ?? ''),
        'Anio'              => trim($_POST['year'] ?? ''),
        'ConductorNombre'   => trim($_POST['driverName'] ?? ''),
        'ConductorTelefono' => trim($_POST['driverPhone'] ?? ''),
        'Proposito'         => trim($_POST['purpose'] ?? ''),
        'Area'              => trim($_POST['area'] ?? ''),
        'PersonaContacto'   => trim($_POST['contactPerson'] ?? ''),
        'Observaciones'     => trim($_POST['observations'] ?? ''),
        'FechaIngreso'      => $_POST['entryTime'] ?? ''
    ];

    // Validar campos requeridos
    $camposRequeridos = [
        'Placa', 'TipoVehiculo', 'Marca', 'Modelo', 
        'ConductorNombre', 'Proposito'
    ];
    
    $camposFaltantes = [];
    foreach ($camposRequeridos as $campo) {
        if (empty($vehiculo[$campo])) {
            $camposFaltantes[] = $campo;
        }
    }
    
    if (!empty($camposFaltantes)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error', 
            'message' => 'Campos requeridos faltantes: ' . implode(', ', $camposFaltantes)
        ]);
        exit;
    }

    // Validar año si se proporciona
    if (!empty($vehiculo['Anio']) && ($vehiculo['Anio'] < 1980 || $vehiculo['Anio'] > 2025)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error', 
            'message' => 'El año debe estar entre 1980 y 2025'
        ]);
        exit;
    }

    // Procesar FechaIngreso
    if (empty($vehiculo['FechaIngreso'])) {
        $vehiculo['FechaIngreso'] = date('Y-m-d H:i:s');
    } else {
        // Convertir formato datetime-local a MySQL
        $vehiculo['FechaIngreso'] = date('Y-m-d H:i:s', strtotime($vehiculo['FechaIngreso']));
    }

    $resultado = ingresoVehiculo($vehiculo);

    header('Content-Type: application/json');
    echo json_encode($resultado);
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);