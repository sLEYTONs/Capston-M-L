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
            
        case 'obtenerMovimientosDelDia':
            try {
                $movimientos = obtenerMovimientosDelDia();
                echo json_encode(['success' => true, 'data' => $movimientos]);
            } catch (Exception $e) {
                error_log("Error en obtenerMovimientosDelDia: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error al obtener movimientos', 'data' => []]);
            }
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
            $motivo_retraso = $_POST['motivo_retraso'] ?? null;
            
            if (empty($placa)) {
                echo json_encode(['success' => false, 'message' => 'Placa requerida']);
                exit();
            }
            
            try {
                $resultado = registrarIngresoBasico($placa, $usuario_id, $motivo_retraso);
                echo json_encode($resultado);
            } catch (Exception $e) {
                error_log("Error en registrarIngresoBasico: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                echo json_encode(['success' => false, 'message' => 'Error al registrar ingreso: ' . $e->getMessage()]);
            }
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
            $tipoOperacion = $_POST['tipo_operacion'] ?? 'ingreso';
            
            if (empty($placa)) {
                echo json_encode(['success' => false, 'message' => 'Placa requerida']);
                exit();
            }
            
            $resultado = verificarEstadoVehiculo($placa, $fecha, $tipoOperacion);
            if ($resultado) {
                echo json_encode(['success' => true, 'data' => $resultado]);
            } else {
                if ($tipoOperacion === 'ingreso') {
                    echo json_encode(['success' => false, 'message' => 'Este vehículo no tiene una hora asignada aprobada para hoy. Solo se pueden ingresar vehículos con agenda aprobada para el día correspondiente.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se encontró un vehículo completado con esta placa. El vehículo debe estar terminado por el mecánico para poder salir.']);
                }
            }
            break;
            
        case 'obtenerVehiculosAgendados':
            $fecha = $_POST['fecha'] ?? date('Y-m-d');
            $rol = $_SESSION['usuario']['rol'] ?? null;
            $soloPendientes = isset($_POST['soloPendientes']) && $_POST['soloPendientes'] === 'true';
            $vehiculos = obtenerVehiculosAgendados($fecha, $rol, $soloPendientes);
            echo json_encode(['success' => true, 'data' => $vehiculos]);
            break;
            
        case 'obtenerHistorialVehiculosAgendados':
            $fecha = $_POST['fecha'] ?? date('Y-m-d');
            $rol = $_SESSION['usuario']['rol'] ?? null;
            $vehiculos = obtenerHistorialVehiculosAgendados($fecha, $rol);
            echo json_encode(['success' => true, 'data' => $vehiculos]);
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