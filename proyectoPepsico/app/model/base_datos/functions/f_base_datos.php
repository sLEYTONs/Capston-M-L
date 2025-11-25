<?php
require_once '../../../config/conexion.php';

/**
 * Obtiene estadísticas generales del sistema
 */
function obtenerEstadisticasGenerales() {
    $conn = conectar_Pepsico();
    
    // Total de registros
    $sql1 = "SELECT COUNT(*) as total FROM ingreso_vehiculos";
    $result1 = $conn->query($sql1);
    $totalRegistros = $result1->fetch_assoc()['total'];
    
    // Vehículos activos (Ingresado, Asignado, En Proceso - excluye Completado)
    $sql2 = "SELECT COUNT(*) as total FROM ingreso_vehiculos WHERE Estado IN ('Ingresado', 'Asignado', 'En Proceso')";
    $result2 = $conn->query($sql2);
    $vehiculosActivos = $result2->fetch_assoc()['total'];
    
    // Repuestos con stock bajo (Stock <= StockMinimo)
    $repuestosStockBajo = 0;
    $checkTable = "SHOW TABLES LIKE 'repuestos'";
    $resultCheck = $conn->query($checkTable);
    
    if ($resultCheck && $resultCheck->num_rows > 0) {
        $sql3 = "SELECT COUNT(*) as total 
                 FROM repuestos 
                 WHERE Estado = 'Activo' AND Stock <= StockMinimo";
        $result3 = $conn->query($sql3);
        if ($result3) {
            $repuestosStockBajo = $result3->fetch_assoc()['total'];
        }
    }
    
    $conn->close();
    
    return [
        'totalRegistros' => $totalRegistros,
        'vehiculosActivos' => $vehiculosActivos,
        'repuestosStockBajo' => $repuestosStockBajo
    ];
}

/**
 * Obtiene vehículos con filtros (ahora trae todos los vehículos ingresados)
 */
function obtenerVehiculosFiltrados($filtros) {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT 
                ID,
                Placa,
                CONCAT(Marca, ' ', Modelo) as MarcaModelo,
                ConductorNombre,
                Estado,
                FechaIngreso
            FROM ingreso_vehiculos 
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($filtros['busqueda'])) {
        $sql .= " AND (Placa LIKE ? OR ConductorNombre LIKE ? OR Marca LIKE ?)";
        $searchTerm = "%{$filtros['busqueda']}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        $types .= 'sss';
    }
    
    if (!empty($filtros['estado'])) {
        $sql .= " AND Estado = ?";
        $params[] = $filtros['estado'];
        $types .= 's';
    }
    
    if (!empty($filtros['marca'])) {
        $sql .= " AND Marca = ?";
        $params[] = $filtros['marca'];
        $types .= 's';
    }
    
    $sql .= " ORDER BY FechaIngreso DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vehiculos = [];
    while ($row = $result->fetch_assoc()) {
        $vehiculos[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $vehiculos;
}

/**
 * Obtiene lista de empresas para filtros
 */
function obtenerListaEmpresas() {
    $conn = conectar_Pepsico();
    
    // Función deshabilitada - columna EmpresaNombre eliminada
    $empresas = [];
    
    $conn->close();
    
    return $empresas;
}

/**
 * Obtiene lista de marcas para filtros
 */
function obtenerListaMarcas() {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT DISTINCT Marca FROM ingreso_vehiculos WHERE Marca IS NOT NULL AND Marca != '' ORDER BY Marca";
    $result = $conn->query($sql);
    
    $marcas = [];
    while ($row = $result->fetch_assoc()) {
        $marcas[] = $row['Marca'];
    }
    
    $conn->close();
    
    return $marcas;
}

/**
 * Obtiene análisis por marcas
 */
function obtenerAnalisisMarcas() {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT 
                Marca,
                COUNT(*) as Total,
                SUM(CASE WHEN Estado IN ('Ingresado', 'Asignado', 'En Proceso') THEN 1 ELSE 0 END) as Activos,
                0 as Empresas,
                MAX(FechaIngreso) as UltimoIngreso
            FROM ingreso_vehiculos 
            WHERE Marca IS NOT NULL AND Marca != ''
            GROUP BY Marca 
            ORDER BY Total DESC";
    
    $result = $conn->query($sql);
    
    $analisis = [];
    while ($row = $result->fetch_assoc()) {
        $row['Porcentaje'] = round(($row['Activos'] / $row['Total']) * 100, 2);
        $analisis[] = $row;
    }
    
    $conn->close();
    
    return $analisis;
}

/**
 * Obtiene análisis por empresas
 */
function obtenerAnalisisEmpresas() {
    // Función deshabilitada - columnas EmpresaNombre y ConductorCedula eliminadas
    return [];
    
    return $analisis;
}

/**
 * Obtiene análisis de conductores (usuarios con rol "Chofer")
 */
function obtenerAnalisisConductores() {
    $conn = conectar_Pepsico();
    
    // Verificar si la tabla existe
    $checkTable = "SHOW TABLES LIKE 'USUARIOS'";
    $resultCheck = $conn->query($checkTable);
    
    if (!$resultCheck || $resultCheck->num_rows == 0) {
        $conn->close();
        return [];
    }
    
    $sql = "SELECT 
                UsuarioID as ID,
                NombreUsuario as Nombre,
                Correo,
                Estado,
                FechaCreacion,
                UltimoAcceso as UltimaVisita
            FROM USUARIOS 
            WHERE Rol = 'Chofer'
            ORDER BY NombreUsuario ASC";
    
    $result = $conn->query($sql);
    
    $conductores = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Contar vehículos asociados a este conductor
            $sqlVehiculos = "SELECT COUNT(*) as total, COUNT(DISTINCT Placa) as unicos, 
                            MIN(FechaIngreso) as primera, MAX(FechaIngreso) as ultima
                            FROM ingreso_vehiculos WHERE ConductorNombre = ?";
            $stmt = $conn->prepare($sqlVehiculos);
            $stmt->bind_param("s", $row['Nombre']);
            $stmt->execute();
            $resultVehiculos = $stmt->get_result();
            $vehiculosData = $resultVehiculos->fetch_assoc();
            $row['Vehiculos'] = $vehiculosData['total'] ?? 0;
            $row['VehiculosUnicos'] = $vehiculosData['unicos'] ?? 0;
            $row['PrimeraVisita'] = $vehiculosData['primera'] ?? '';
            $row['UltimaVisita'] = $vehiculosData['ultima'] ?? $row['UltimaVisita'] ?? '';
            $stmt->close();
            
            $conductores[] = $row;
        }
    }
    
    $conn->close();
    return $conductores;
}

/**
 * Obtiene detalle completo de un vehículo
 */
function obtenerDetalleVehiculo($placa) {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT * FROM ingreso_vehiculos WHERE Placa = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $placa);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vehiculo = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $vehiculo;
}

/**
 * Obtiene datos para gráficos
 */
function obtenerDatosParaGraficos() {
    $conn = conectar_Pepsico();
    
    // Datos para gráfico de marcas (Top 10)
    $sqlMarcas = "SELECT Marca, COUNT(*) as Total FROM ingreso_vehiculos WHERE Marca IS NOT NULL GROUP BY Marca ORDER BY Total DESC LIMIT 10";
    $resultMarcas = $conn->query($sqlMarcas);
    $marcasData = [
        'labels' => [],
        'datasets' => [[
            'label' => 'Vehículos por Marca',
            'data' => [],
            'backgroundColor' => [
                '#004B93', '#0066CC', '#0080FF', '#3399FF', '#66B2FF',
                '#99CCFF', '#CCE5FF', '#E6F2FF', '#FF6B6B', '#4ECDC4'
            ],
            'borderWidth' => 1
        ]]
    ];
    
    while ($row = $resultMarcas->fetch_assoc()) {
        $marcasData['labels'][] = $row['Marca'];
        $marcasData['datasets'][0]['data'][] = $row['Total'];
    }
    
    // Datos para gráfico de estados
    $sqlEstados = "SELECT Estado, COUNT(*) as Total FROM ingreso_vehiculos GROUP BY Estado";
    $resultEstados = $conn->query($sqlEstados);
    $estadosData = [
        'labels' => [],
        'datasets' => [[
            'label' => 'Vehículos por Estado',
            'data' => [],
            'backgroundColor' => ['#28a745', '#dc3545', '#ffc107'],
            'borderWidth' => 1
        ]]
    ];
    
    while ($row = $resultEstados->fetch_assoc()) {
        // Mapear estados nuevos a etiquetas legibles
        $estadoMap = [
            'Ingresado' => 'Ingresado',
            'Asignado' => 'Asignado',
            'En Proceso' => 'En Proceso',
            'Completado' => 'Completado',
            'active' => 'Activo',  // Mantener compatibilidad con registros antiguos
            'inactive' => 'Inactivo'
        ];
        
        $label = $estadoMap[$row['Estado']] ?? $row['Estado'];
        $estadosData['labels'][] = $label;
        $estadosData['datasets'][0]['data'][] = $row['Total'];
    }
    
    // Datos para gráfico de empresas - deshabilitado (columna EmpresaNombre eliminada)
    $empresasData = [
        'labels' => [],
        'datasets' => [[
            'label' => 'Vehículos por Empresa',
            'data' => [],
            'backgroundColor' => [],
            'borderWidth' => 1
        ]]
    ];
    
    // Datos para gráfico mensual (últimos 6 meses)
    $sqlMensual = "SELECT 
                    DATE_FORMAT(FechaIngreso, '%Y-%m') as Mes,
                    COUNT(*) as Total
                  FROM ingreso_vehiculos 
                  WHERE FechaIngreso >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                  GROUP BY DATE_FORMAT(FechaIngreso, '%Y-%m')
                  ORDER BY Mes DESC
                  LIMIT 6";
    $resultMensual = $conn->query($sqlMensual);
    $mensualData = [
        'labels' => [],
        'datasets' => [[
            'label' => 'Ingresos Mensuales',
            'data' => [],
            'borderColor' => '#004B93',
            'backgroundColor' => 'rgba(0, 75, 147, 0.1)',
            'borderWidth' => 2,
            'fill' => true,
            'tension' => 0.4
        ]]
    ];
    
    $mesesData = [];
    while ($row = $resultMensual->fetch_assoc()) {
        $mesesData[] = $row;
    }
    $mesesData = array_reverse($mesesData); // Ordenar cronológicamente
    
    foreach ($mesesData as $row) {
        $fecha = DateTime::createFromFormat('Y-m', $row['Mes']);
        $mensualData['labels'][] = $fecha->format('M Y');
        $mensualData['datasets'][0]['data'][] = $row['Total'];
    }
    
    $conn->close();
    
    return [
        'marcas' => $marcasData,
        'estados' => $estadosData,
        'empresas' => $empresasData,
        'mensual' => $mensualData
    ];
}

/**
 * Obtiene datos para análisis temporal
 */
function obtenerDatosTemporales() {
    $conn = conectar_Pepsico();
    
    // Ingresos por hora del día
    $sqlHoras = "SELECT 
                    HOUR(FechaIngreso) as Hora,
                    COUNT(*) as Total
                 FROM ingreso_vehiculos 
                 GROUP BY HOUR(FechaIngreso)
                 ORDER BY Hora";
    
    $resultHoras = $conn->query($sqlHoras);
    $datosHoras = [];
    while ($row = $resultHoras->fetch_assoc()) {
        $datosHoras[] = $row;
    }
    
    // Ingresos por día de la semana
    $sqlDias = "SELECT 
                    DAYNAME(FechaIngreso) as Dia,
                    COUNT(*) as Total
                 FROM ingreso_vehiculos 
                 GROUP BY DAYNAME(FechaIngreso), DAYOFWEEK(FechaIngreso)
                 ORDER BY DAYOFWEEK(FechaIngreso)";
    
    $resultDias = $conn->query($sqlDias);
    $datosDias = [];
    while ($row = $resultDias->fetch_assoc()) {
        $datosDias[] = $row;
    }
    
    $conn->close();
    
    return [
        'porHora' => $datosHoras,
        'porDia' => $datosDias
    ];
}

// =============================================================================
// FUNCIONES DE EXPORTACIÓN COMPLETAS
// =============================================================================

/**
 * Exporta CSV completo de la base de datos
 */
function exportarCSVCompleto() {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=base_datos_completa_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados
    fputcsv($output, [
        'ID', 'Placa', 'TipoVehiculo', 'Marca', 'Modelo', 'Anio',
        'ConductorNombre', 'FechaIngreso', 'Estado', 'FechaRegistro',
        'Kilometraje', 'UsuarioRegistro', 'Notificado'
    ]);
    
    $conn = conectar_Pepsico();
    $sql = "SELECT * FROM ingreso_vehiculos ORDER BY FechaIngreso DESC";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['ID'],
            $row['Placa'],
            $row['TipoVehiculo'],
            $row['Marca'],
            $row['Modelo'],
            $row['Anio'],
            $row['ConductorNombre'],
            $row['FechaIngreso'],
            $row['Estado'],
            $row['FechaRegistro'],
            $row['Kilometraje'],
            $row['UsuarioRegistro'],
            $row['Notificado']
        ]);
    }
    
    $conn->close();
    fclose($output);
    exit;
}

/**
 * Exporta datos en formato Excel (CSV con headers para Excel)
 */
function exportarExcelCompleto() {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=base_datos_completa_' . date('Y-m-d') . '.xls');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (para que Excel reconozca correctamente los caracteres)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados
    fputcsv($output, [
        'ID', 'Placa', 'TipoVehiculo', 'Marca', 'Modelo', 'Anio',
        'ConductorNombre', 'FechaIngreso', 'Estado', 'FechaRegistro',
        'Kilometraje', 'UsuarioRegistro', 'Notificado'
    ], "\t");
    
    $conn = conectar_Pepsico();
    $sql = "SELECT * FROM ingreso_vehiculos ORDER BY FechaIngreso DESC";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['ID'],
            $row['Placa'],
            $row['TipoVehiculo'],
            $row['Marca'],
            $row['Modelo'],
            $row['Anio'],
            $row['ConductorNombre'],
            $row['FechaIngreso'],
            $row['Estado'],
            $row['FechaRegistro'],
            $row['Kilometraje'],
            $row['UsuarioRegistro'],
            $row['Notificado']
        ], "\t");
    }
    
    $conn->close();
    fclose($output);
    exit;
}

/**
 * Exporta datos en formato JSON
 */
function exportarJSONCompleto() {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=base_datos_completa_' . date('Y-m-d') . '.json');
    
    $conn = conectar_Pepsico();
    $sql = "SELECT * FROM ingreso_vehiculos ORDER BY FechaIngreso DESC";
    $result = $conn->query($sql);
    
    $datos = [];
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
    }
    
    $conn->close();
    
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Exporta solo datos de vehículos
 */
function exportarVehiculosCSV() {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=vehiculos_' . date('Y-m-d') . '.xls');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (para que Excel reconozca correctamente los caracteres)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados con columnas que existen en la tabla
    fputcsv($output, [
        'ID', 'Placa', 'TipoVehiculo', 'Marca', 'Modelo', 'Anio',
        'ConductorNombre', 'FechaIngreso', 'Estado', 'FechaRegistro',
        'Kilometraje', 'UsuarioRegistro', 'Notificado'
    ], "\t");
    
    $conn = conectar_Pepsico();
    $sql = "SELECT 
                ID, Placa, TipoVehiculo, Marca, Modelo, Anio,
                ConductorNombre, FechaIngreso, Estado, FechaRegistro,
                Kilometraje, UsuarioRegistro, Notificado
            FROM ingreso_vehiculos 
            ORDER BY Marca, Modelo";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['ID'],
            $row['Placa'],
            $row['TipoVehiculo'],
            $row['Marca'],
            $row['Modelo'],
            $row['Anio'],
            $row['ConductorNombre'],
            $row['FechaIngreso'],
            $row['Estado'],
            $row['FechaRegistro'],
            $row['Kilometraje'],
            $row['UsuarioRegistro'],
            $row['Notificado']
        ], "\t");
    }
    
    $conn->close();
    fclose($output);
    exit;
}

/**
 * Exporta análisis de marcas
 */
function exportarMarcasCSV() {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=marcas_analisis_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Marca', 'Total Vehículos', 'Vehículos Activos', 'Empresas Diferentes',
        'Porcentaje Activos', 'Último Ingreso'
    ]);
    
    $datos = obtenerAnalisisMarcas();
    
    foreach ($datos as $marca) {
        fputcsv($output, [
            $marca['Marca'],
            $marca['Total'],
            $marca['Activos'],
            $marca['Empresas'],
            $marca['Porcentaje'] . '%',
            $marca['UltimoIngreso']
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Exporta análisis de empresas
 */
function exportarEmpresasCSV() {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=empresas_analisis_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Empresa', 'Total Vehículos', 'Vehículos Activos', 'Conductores Únicos',
        'Marcas Diferentes', 'Frecuencia', 'Último Ingreso'
    ]);
    
    $datos = obtenerAnalisisEmpresas();
    
    foreach ($datos as $empresa) {
        fputcsv($output, [
            'N/A', // EmpresaNombre eliminado
            $empresa['Total'],
            $empresa['Activos'],
            $empresa['Conductores'],
            $empresa['Marcas'],
            $empresa['Frecuencia'],
            $empresa['UltimoIngreso']
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Exporta datos de conductores
 */
function exportarConductoresCSV() {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=conductores_analisis_' . date('Y-m-d') . '.xls');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (para que Excel reconozca correctamente los caracteres)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados con separador de tabulación para Excel
    fputcsv($output, [
        'Nombre', 'Total Vehículos', 'Vehículos Únicos',
        'Primera Visita', 'Última Visita'
    ], "\t");
    
    $datos = obtenerAnalisisConductores();
    
    foreach ($datos as $conductor) {
        fputcsv($output, [
            $conductor['Nombre'] ?? '',
            $conductor['Vehiculos'] ?? 0,
            $conductor['VehiculosUnicos'] ?? 0,
            $conductor['PrimeraVisita'] ?? '',
            $conductor['UltimaVisita'] ?? ''
        ], "\t");
    }
    
    fclose($output);
    exit;
}

/**
 * Exporta estadísticas generales
 */
function exportarEstadisticasCSV() {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=estadisticas_generales_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Métrica', 'Valor']);
    
    $estadisticas = obtenerEstadisticasGenerales();
    
    $metricas = [
        'Total de Registros' => $estadisticas['totalRegistros'],
        'Vehículos Activos' => $estadisticas['vehiculosActivos'],
        'Marcas Únicas' => $estadisticas['marcasUnicas'],
        'Empresas Registradas' => $estadisticas['empresasRegistradas'],
        'Conductores Únicos' => $estadisticas['conductoresUnicos'],
        'Ingresos Hoy' => $estadisticas['ingresosHoy']
    ];
    
    foreach ($metricas as $metrica => $valor) {
        fputcsv($output, [$metrica, $valor]);
    }
    
    fclose($output);
    exit;
}

/**
 * Obtiene todas las agendas del taller
 */
function obtenerAgendasTaller() {
    $conn = conectar_Pepsico();
    
    // Verificar si la tabla existe
    $checkTable = "SHOW TABLES LIKE 'agenda_taller'";
    $resultCheck = $conn->query($checkTable);
    
    if (!$resultCheck || $resultCheck->num_rows == 0) {
        $conn->close();
        return [];
    }
    
    $sql = "SELECT 
                ID,
                Fecha,
                HoraInicio,
                HoraFin,
                Disponible,
                Observaciones,
                FechaCreacion,
                FechaActualizacion
            FROM agenda_taller 
            ORDER BY Fecha DESC, HoraInicio DESC";
    
    $result = $conn->query($sql);
    
    $agendas = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $agendas[] = $row;
        }
    }
    
    $conn->close();
    return $agendas;
}

/**
 * Obtiene todos los vehículos ingresados
 */
function obtenerVehiculosIngresados() {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT 
                ID,
                Placa,
                CONCAT(Marca, ' ', Modelo) as MarcaModelo,
                ConductorNombre,
                Estado,
                FechaIngreso
            FROM ingreso_vehiculos 
            ORDER BY FechaIngreso DESC";
    
    $result = $conn->query($sql);
    
    $vehiculos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
    }
    
    $conn->close();
    return $vehiculos;
}

/**
 * Obtiene todos los repuestos
 */
function obtenerRepuestos() {
    $conn = conectar_Pepsico();
    
    // Verificar si la tabla existe
    $checkTable = "SHOW TABLES LIKE 'repuestos'";
    $resultCheck = $conn->query($checkTable);
    
    if (!$resultCheck || $resultCheck->num_rows == 0) {
        $conn->close();
        return [];
    }
    
    $sql = "SELECT 
                ID,
                Nombre,
                Descripcion,
                Stock,
                StockMinimo,
                Precio,
                Estado,
                FechaCreacion
            FROM repuestos 
            WHERE Estado = 'Activo'
            ORDER BY Nombre ASC";
    
    $result = $conn->query($sql);
    
    $repuestos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $repuestos[] = $row;
        }
    }
    
    $conn->close();
    return $repuestos;
}

/**
 * Obtiene todos los usuarios del sistema
 */
function obtenerUsuarios() {
    $conn = conectar_Pepsico();
    
    // Verificar si la tabla existe
    $checkTable = "SHOW TABLES LIKE 'USUARIOS'";
    $resultCheck = $conn->query($checkTable);
    
    if (!$resultCheck || $resultCheck->num_rows == 0) {
        $conn->close();
        return [];
    }
    
    $sql = "SELECT 
                UsuarioID as ID,
                NombreUsuario,
                Correo,
                Rol,
                Estado,
                FechaCreacion,
                UltimoAcceso
            FROM USUARIOS 
            ORDER BY FechaCreacion DESC";
    
    $result = $conn->query($sql);
    
    $usuarios = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
    
    $conn->close();
    return $usuarios;
}
?>