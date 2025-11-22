<?php
session_start();
require_once '../functions/f_base_datos.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'obtenerEstadisticas':
            $estadisticas = obtenerEstadisticasGenerales();
            echo json_encode(['success' => true, 'data' => $estadisticas]);
            break;
            
        case 'obtenerVehiculos':
            $filtros = [
                'busqueda' => $_POST['busqueda'] ?? $_GET['busqueda'] ?? '',
                'estado' => $_POST['estado'] ?? $_GET['estado'] ?? '',
                'empresa' => $_POST['empresa'] ?? $_GET['empresa'] ?? '',
                'marca' => $_POST['marca'] ?? $_GET['marca'] ?? ''
            ];
            $vehiculos = obtenerVehiculosFiltrados($filtros);
            echo json_encode(['success' => true, 'data' => $vehiculos]);
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
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>