<?php
session_start();
require_once '../functions/f_control_ingreso.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'buscarVehiculo':
            $placa = $_POST['placa'] ?? '';
            
            if (empty($placa)) {
                echo json_encode(['success' => false, 'message' => 'Placa requerida']);
                exit();
            }
            
            $vehiculo = buscarVehiculo($placa);
            if ($vehiculo) {
                echo json_encode(['success' => true, 'data' => $vehiculo]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Vehículo no encontrado']);
            }
            break;
            
        case 'obtenerEstadisticas':
            $estadisticas = obtenerEstadisticasPatio();
            echo json_encode(['success' => true, 'data' => $estadisticas]);
            break;
            
        case 'obtenerNovedades':
            $novedades = obtenerNovedadesRecientes();
            echo json_encode(['success' => true, 'data' => $novedades]);
            break;
            
        case 'reportarNovedad':
            $placa = $_POST['placa'] ?? '';
            $tipo = $_POST['tipo'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $gravedad = $_POST['gravedad'] ?? '';
            $usuario_id = $_SESSION['usuario']['id'];
            
            if (empty($placa) || empty($descripcion)) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit();
            }
            
            $resultado = reportarNovedad($placa, $tipo, $descripcion, $gravedad, $usuario_id);
            echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Novedad reportada' : 'Error al reportar']);
            break;
            
        case 'registrarIngresoBasico':
            $placa = $_POST['placa'] ?? '';
            $usuario_id = $_SESSION['usuario']['id'];
            
            if (empty($placa)) {
                echo json_encode(['success' => false, 'message' => 'Placa requerida']);
                exit();
            }
            
            $resultado = registrarIngresoBasico($placa, $usuario_id);
            echo json_encode($resultado);
            break;
            
        case 'registrarSalida':
            $placa = $_POST['placa'] ?? '';
            $usuario_id = $_SESSION['usuario']['id'];
            
            if (empty($placa)) {
                echo json_encode(['success' => false, 'message' => 'Placa requerida']);
                exit();
            }
            
            $resultado = registrarSalidaVehiculo($placa, $usuario_id);
            echo json_encode($resultado);
            break;
            
        case 'guardarFotos':
            $placa = $_POST['placa'] ?? '';
            $fotos = json_decode($_POST['fotos'], true) ?? [];
            $usuario_id = $_SESSION['usuario']['id'];
            
            if (empty($placa) || empty($fotos)) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit();
            }
            
            $resultado = guardarFotosVehiculo($placa, $fotos, $usuario_id);
            echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Fotos guardadas' : 'Error al guardar fotos']);
            break;
            
        case 'verificarEstado':
            $placa = $_POST['placa'] ?? '';
            $fecha = $_POST['fecha'] ?? date('Y-m-d');
            
            if (empty($placa)) {
                echo json_encode(['success' => false, 'message' => 'Placa requerida']);
                exit();
            }
            
            $resultado = verificarEstadoVehiculo($placa, $fecha);
            if ($resultado) {
                echo json_encode(['success' => true, 'data' => $resultado]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Vehículo no encontrado o ya salió. No tiene agenda aprobada para hoy.']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en s_control_ingreso: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>