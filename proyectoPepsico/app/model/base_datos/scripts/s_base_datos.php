<?php
// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_end_clean();
}

// Solo desactivar la visualización de errores en la salida (pero seguir registrándolos)
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../functions/f_base_datos.php';

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Las exportaciones manejan sus propios headers, no establecer JSON por defecto
$es_exportacion = in_array($action, ['exportarCSV', 'exportarExcel', 'exportarJSON', 'exportarVehiculos', 'exportarConductores', 'exportarMarcas']);

if (!$es_exportacion) {
    header('Content-Type: application/json; charset=utf-8');
}

try {
    switch ($action) {
        case 'obtenerEstadisticas':
            $estadisticas = obtenerEstadisticasGenerales();
            echo json_encode(['success' => true, 'data' => $estadisticas]);
            break;
            
        case 'obtenerVehiculos':
            try {
                $filtros = [
                    'busqueda' => $_POST['busqueda'] ?? $_GET['busqueda'] ?? '',
                    'estado' => $_POST['estado'] ?? $_GET['estado'] ?? '',
                    'empresa' => $_POST['empresa'] ?? $_GET['empresa'] ?? '',
                    'marca' => $_POST['marca'] ?? $_GET['marca'] ?? ''
                ];
                
                $vehiculos = obtenerVehiculosFiltrados($filtros);
                
                // Asegurar que siempre sea un array
                if (!is_array($vehiculos)) {
                    error_log("Warning: obtenerVehiculosFiltrados no devolvió un array");
                    $vehiculos = [];
                }
                
                $response = [
                    'success' => true, 
                    'data' => $vehiculos
                ];
                
                echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
                
            } catch (Exception $e) {
                error_log("Error en obtenerVehiculos: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                $response = [
                    'success' => false, 
                    'message' => 'Error al obtener vehículos: ' . $e->getMessage(), 
                    'data' => []
                ];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } catch (Error $e) {
                error_log("Error fatal en obtenerVehiculos: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                $response = [
                    'success' => false, 
                    'message' => 'Error fatal al obtener vehículos', 
                    'data' => []
                ];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'obtenerEmpresas':
            $empresas = obtenerListaEmpresas();
            echo json_encode(['success' => true, 'data' => $empresas]);
            break;
            
        case 'obtenerMarcas':
            $marcas = obtenerListaMarcas();
            echo json_encode(['success' => true, 'data' => $marcas]);
            break;
            
        case 'obtenerAnalisisMarcas':
            $analisis = obtenerAnalisisMarcas();
            echo json_encode(['success' => true, 'data' => $analisis]);
            break;
            
        case 'obtenerAnalisisEmpresas':
            $analisis = obtenerAnalisisEmpresas();
            echo json_encode(['success' => true, 'data' => $analisis]);
            break;
            
        case 'obtenerAnalisisConductores':
            $analisis = obtenerAnalisisConductores();
            echo json_encode(['success' => true, 'data' => $analisis]);
            break;
            
        case 'obtenerDetalleVehiculo':
            $placa = $_POST['placa'] ?? '';
            $detalle = obtenerDetalleVehiculo($placa);
            if ($detalle) {
                echo json_encode(['success' => true, 'data' => $detalle]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Vehículo no encontrado']);
            }
            break;
            
        case 'obtenerDatosGraficos':
            $datos = obtenerDatosParaGraficos();
            echo json_encode(['success' => true, 'data' => $datos]);
            break;
            
        case 'obtenerDatosTemporales':
            $datos = obtenerDatosTemporales();
            echo json_encode(['success' => true, 'data' => $datos]);
            break;
            
        // Nuevas acciones para Jefe de Taller
        case 'obtenerAgendas':
            $agendas = obtenerAgendasTaller();
            echo json_encode(['success' => true, 'data' => $agendas]);
            break;
            
        case 'obtenerVehiculosIngresados':
            $vehiculos = obtenerVehiculosIngresados();
            echo json_encode(['success' => true, 'data' => $vehiculos]);
            break;
            
        case 'obtenerRepuestos':
            $repuestos = obtenerRepuestos();
            echo json_encode(['success' => true, 'data' => $repuestos]);
            break;
            
        case 'obtenerUsuarios':
            $usuarios = obtenerUsuarios();
            echo json_encode(['success' => true, 'data' => $usuarios]);
            break;
            
        case 'marcarTodosVehiculosCompletado':
            try {
                $resultado = marcarTodosVehiculosCompletado();
                if (!isset($resultado['success'])) {
                    $resultado = ['success' => false, 'message' => 'Respuesta inválida del servidor'];
                }
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error excepcional en marcarTodosVehiculosCompletado: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'asignarVehiculoPedro':
            try {
                $resultado = asignarVehiculoPedro();
                if (!isset($resultado['success'])) {
                    $resultado = ['success' => false, 'message' => 'Respuesta inválida del servidor'];
                }
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error excepcional en asignarVehiculoPedro: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'eliminarVehiculosDuplicados':
            try {
                $resultado = eliminarVehiculosDuplicados();
                if (!isset($resultado['success'])) {
                    $resultado = ['success' => false, 'message' => 'Respuesta inválida del servidor'];
                }
                echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                error_log("Error excepcional en eliminarVehiculosDuplicados: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        // Exportaciones
        case 'exportarCSV':
            exportarCSVCompleto();
            break;
            
        case 'exportarExcel':
            exportarExcelCompleto();
            break;
            
        case 'exportarJSON':
            exportarJSONCompleto();
            break;
            
        case 'exportarVehiculos':
            exportarVehiculosCSV();
            break;
            
        case 'exportarMarcas':
            exportarMarcasCSV();
            break;
            
        case 'exportarEmpresas':
            exportarEmpresasCSV();
            break;
            
        case 'exportarConductores':
            exportarConductoresCSV();
            break;
            
        case 'exportarEstadisticas':
            exportarEstadisticasCSV();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en s_base_datos: " . $e->getMessage());
    if (!$es_exportacion) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    } else {
        // Para exportaciones, mostrar error simple
        die('Error al exportar: ' . $e->getMessage());
    }
}
?>