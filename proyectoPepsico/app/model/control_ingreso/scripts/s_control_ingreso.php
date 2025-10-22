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
            $tipo = $_POST['tipo'] ?? '';
            $valor = $_POST['valor'] ?? '';
            
            if (empty($tipo) || empty($valor)) {
                echo json_encode(['success' => false, 'message' => 'Datos de búsqueda incompletos']);
                exit();
            }
            
            $vehiculo = buscarVehiculo($tipo, $valor);
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
            
        case 'guardarFoto':
            $placa = $_POST['placa'] ?? '';
            $foto = $_POST['foto'] ?? '';
            $usuario_id = $_SESSION['usuario']['id'];
            
            if (empty($placa) || empty($foto)) {
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit();
            }
            
            $resultado = guardarFotoVehiculo($placa, $foto, $usuario_id);
            echo json_encode(['success' => $resultado, 'message' => $resultado ? 'Foto guardada' : 'Error al guardar foto']);
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