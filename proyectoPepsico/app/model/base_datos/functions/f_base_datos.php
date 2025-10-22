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
    
    // Vehículos activos
    $sql2 = "SELECT COUNT(*) as total FROM ingreso_vehiculos WHERE Estado = 'active'";
    $result2 = $conn->query($sql2);
    $vehiculosActivos = $result2->fetch_assoc()['total'];
    
    // Marcas únicas
    $sql3 = "SELECT COUNT(DISTINCT Marca) as total FROM ingreso_vehiculos";
    $result3 = $conn->query($sql3);
    $marcasUnicas = $result3->fetch_assoc()['total'];
    
    // Empresas registradas
    $sql4 = "SELECT COUNT(DISTINCT EmpresaNombre) as total FROM ingreso_vehiculos";
    $result4 = $conn->query($sql4);
    $empresasRegistradas = $result4->fetch_assoc()['total'];
    
    // Conductores únicos
    $sql5 = "SELECT COUNT(DISTINCT ConductorCedula) as total FROM ingreso_vehiculos";
    $result5 = $conn->query($sql5);
    $conductoresUnicos = $result5->fetch_assoc()['total'];
    
    // Ingresos hoy
    $sql6 = "SELECT COUNT(*) as total FROM ingreso_vehiculos WHERE DATE(FechaIngreso) = CURDATE()";
    $result6 = $conn->query($sql6);
    $ingresosHoy = $result6->fetch_assoc()['total'];
    
    $conn->close();
    
    return [
        'totalRegistros' => $totalRegistros,
        'vehiculosActivos' => $vehiculosActivos,
        'marcasUnicas' => $marcasUnicas,
        'empresasRegistradas' => $empresasRegistradas,
        'conductoresUnicos' => $conductoresUnicos,
        'ingresosHoy' => $ingresosHoy
    ];
}

/**
 * Obtiene vehículos con filtros
 */
function obtenerVehiculosFiltrados($filtros) {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT 
                ID,
                Placa,
                CONCAT(Marca, ' ', Modelo) as MarcaModelo,
                ConductorNombre,
                EmpresaNombre,
                Estado,
                FechaIngreso,
                Proposito,
                Area,
                EstadoIngreso
            FROM ingreso_vehiculos 
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($filtros['busqueda'])) {
        $sql .= " AND (Placa LIKE ? OR ConductorNombre LIKE ? OR EmpresaNombre LIKE ? OR Marca LIKE ? OR ConductorCedula LIKE ?)";
        $searchTerm = "%{$filtros['busqueda']}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $types .= 'sssss';
    }
    
    if (!empty($filtros['estado'])) {
        $sql .= " AND Estado = ?";
        $params[] = $filtros['estado'];
        $types .= 's';
    }
    
    if (!empty($filtros['empresa'])) {
        $sql .= " AND EmpresaNombre = ?";
        $params[] = $filtros['empresa'];
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
    
    $sql = "SELECT DISTINCT EmpresaNombre FROM ingreso_vehiculos WHERE EmpresaNombre IS NOT NULL AND EmpresaNombre != '' ORDER BY EmpresaNombre";
    $result = $conn->query($sql);
    
    $empresas = [];
    while ($row = $result->fetch_assoc()) {
        $empresas[] = $row['EmpresaNombre'];
    }
    
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
                SUM(CASE WHEN Estado = 'active' THEN 1 ELSE 0 END) as Activos,
                COUNT(DISTINCT EmpresaNombre) as Empresas,
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
    $conn = conectar_Pepsico();
    
    $sql = "SELECT 
                EmpresaNombre,
                COUNT(*) as Total,
                SUM(CASE WHEN Estado = 'active' THEN 1 ELSE 0 END) as Activos,
                COUNT(DISTINCT ConductorCedula) as Conductores,
                COUNT(DISTINCT Marca) as Marcas,
                MAX(FechaIngreso) as UltimoIngreso
            FROM ingreso_vehiculos 
            WHERE EmpresaNombre IS NOT NULL AND EmpresaNombre != ''
            GROUP BY EmpresaNombre 
            ORDER BY Total DESC";
    
    $result = $conn->query($sql);
    
    $analisis = [];
    while ($row = $result->fetch_assoc()) {
        $row['Frecuencia'] = $row['Total'] > 10 ? 'Alta' : ($row['Total'] > 5 ? 'Media' : 'Baja');
        $analisis[] = $row;
    }
    
    $conn->close();
    
    return $analisis;
}

/**
 * Obtiene análisis de conductores
 */
function obtenerAnalisisConductores() {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT 
                ConductorCedula,
                ConductorNombre,
                COUNT(*) as TotalVehiculos,
                COUNT(DISTINCT Placa) as VehiculosUnicos,
                GROUP_CONCAT(DISTINCT EmpresaNombre) as Empresas,
                MAX(FechaIngreso) as UltimaVisita,
                MIN(FechaIngreso) as PrimeraVisita
            FROM ingreso_vehiculos 
            WHERE ConductorCedula IS NOT NULL AND ConductorCedula != ''
            GROUP BY ConductorCedula, ConductorNombre
            ORDER BY TotalVehiculos DESC";
    
    $result = $conn->query($sql);
    
    $analisis = [];
    while ($row = $result->fetch_assoc()) {
        $analisis[] = $row;
    }
    
    $conn->close();
    
    return $analisis;
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
        $label = $row['Estado'] === 'active' ? 'Activo' : ($row['Estado'] === 'inactive' ? 'Inactivo' : 'Retirado');
        $estadosData['labels'][] = $label;
        $estadosData['datasets'][0]['data'][] = $row['Total'];
    }
    
    // Datos para gráfico de empresas (Top 8)
    $sqlEmpresas = "SELECT EmpresaNombre, COUNT(*) as Total FROM ingreso_vehiculos WHERE EmpresaNombre IS NOT NULL GROUP BY EmpresaNombre ORDER BY Total DESC LIMIT 8";
    $resultEmpresas = $conn->query($sqlEmpresas);
    $empresasData = [
        'labels' => [],
        'datasets' => [[
            'label' => 'Vehículos por Empresa',
            'data' => [],
            'backgroundColor' => [
                '#004B93', '#28a745', '#dc3545', '#ffc107', '#17a2b8',
                '#6f42c1', '#e83e8c', '#fd7e14'
            ],
            'borderWidth' => 1
        ]]
    ];
    
    while ($row = $resultEmpresas->fetch_assoc()) {
        $empresasData['labels'][] = $row['EmpresaNombre'];
        $empresasData['datasets'][0]['data'][] = $row['Total'];
    }
    
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
        'ID', 'Placa', 'TipoVehiculo', 'Marca', 'Modelo', 'Color', 'Anio',
        'ConductorNombre', 'ConductorCedula', 'ConductorTelefono', 'Licencia',
        'EmpresaCodigo', 'EmpresaNombre', 'FechaIngreso', 'Proposito', 'Area',
        'PersonaContacto', 'Observaciones', 'Estado', 'FechaRegistro',
        'EstadoIngreso', 'Kilometraje', 'Combustible', 'UsuarioRegistro', 'Notificado'
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
            $row['Color'],
            $row['Anio'],
            $row['ConductorNombre'],
            $row['ConductorCedula'],
            $row['ConductorTelefono'],
            $row['Licencia'],
            $row['EmpresaCodigo'],
            $row['EmpresaNombre'],
            $row['FechaIngreso'],
            $row['Proposito'],
            $row['Area'],
            $row['PersonaContacto'],
            $row['Observaciones'],
            $row['Estado'],
            $row['FechaRegistro'],
            $row['EstadoIngreso'],
            $row['Kilometraje'],
            $row['Combustible'],
            $row['UsuarioRegistro'],
            $row['Notificado']
        ]);
    }
    
    $conn->close();
    fclose($output);
    exit;
}

/**
 * Exporta datos en formato Excel (CSV con nombre diferente)
 */
function exportarExcelCompleto() {
    exportarCSVCompleto(); // Por simplicidad, usamos CSV con nombre diferente
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
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=vehiculos_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Placa', 'TipoVehiculo', 'Marca', 'Modelo', 'Color', 'Anio',
        'EstadoIngreso', 'Kilometraje', 'Combustible', 'Estado', 'FechaIngreso'
    ]);
    
    $conn = conectar_Pepsico();
    $sql = "SELECT 
                Placa, TipoVehiculo, Marca, Modelo, Color, Anio,
                EstadoIngreso, Kilometraje, Combustible, Estado, FechaIngreso
            FROM ingreso_vehiculos 
            ORDER BY Marca, Modelo";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
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
            $empresa['EmpresaNombre'],
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
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=conductores_analisis_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Cédula', 'Nombre', 'Total Vehículos', 'Vehículos Únicos',
        'Empresas', 'Primera Visita', 'Última Visita'
    ]);
    
    $datos = obtenerAnalisisConductores();
    
    foreach ($datos as $conductor) {
        fputcsv($output, [
            $conductor['ConductorCedula'],
            $conductor['ConductorNombre'],
            $conductor['TotalVehiculos'],
            $conductor['VehículosUnicos'],
            $conductor['Empresas'],
            $conductor['PrimeraVisita'],
            $conductor['UltimaVisita']
        ]);
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
?>