<?php
require '../functions/f_consulta.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que el usuario esté autenticado
    session_start();
    if (!isset($_SESSION['usuario'])) {
        echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
        exit;
    }

    // Obtener los datos del formulario
    $datos = [
        'id' => intval($_POST['id'] ?? 0),
        'Placa' => trim($_POST['Placa'] ?? ''),
        'TipoVehiculo' => trim($_POST['TipoVehiculo'] ?? ''),
        'Marca' => trim($_POST['Marca'] ?? ''),
        'Modelo' => trim($_POST['Modelo'] ?? ''),
        'Color' => trim($_POST['Color'] ?? ''),
        'Anio' => !empty($_POST['Anio']) ? intval($_POST['Anio']) : null,
        'ConductorNombre' => trim($_POST['ConductorNombre'] ?? ''),
        'Proposito' => trim($_POST['Proposito'] ?? ''),
        'Observaciones' => trim($_POST['Observaciones'] ?? ''),
        'Estado' => trim($_POST['Estado'] ?? '')
    ];

    // Validación básica
    if (empty($datos['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'ID de vehículo requerido']);
        exit;
    }

    if (empty($datos['Placa']) || empty($datos['TipoVehiculo']) || empty($datos['Marca']) || 
        empty($datos['Modelo']) || empty($datos['ConductorNombre']) || empty($datos['Proposito'])) {
        echo json_encode(['status' => 'error', 'message' => 'Campos requeridos faltantes']);
        exit;
    }

    // Actualizar el vehículo
    $resultado = actualizarVehiculo($datos);

    echo json_encode($resultado);
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
?>

