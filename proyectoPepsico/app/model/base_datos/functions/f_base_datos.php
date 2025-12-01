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
    
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos en obtenerVehiculosFiltrados");
        return [];
    }
    
    // Consulta simplificada - primero obtener datos básicos
    $sql = "SELECT 
                iv.ID,
                iv.Placa,
                iv.TipoVehiculo,
                iv.Marca,
                iv.Modelo,
                iv.Anio,
                CONCAT(COALESCE(iv.Marca, ''), ' ', COALESCE(iv.Modelo, '')) as MarcaModelo,
                iv.ConductorNombre,
                COALESCE(iv.Estado, '') as EstadoRaw,
                iv.FechaIngreso,
                iv.FechaRegistro,
                iv.Kilometraje
            FROM ingreso_vehiculos iv
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($filtros['busqueda'])) {
        $sql .= " AND (iv.Placa LIKE ? OR iv.ConductorNombre LIKE ? OR iv.Marca LIKE ?)";
        $searchTerm = "%{$filtros['busqueda']}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        $types .= 'sss';
    }
    
    // Los filtros de estado se aplicarán después de calcular el estado en PHP
    // Por ahora, no filtrar por estado en la consulta SQL para evitar subconsultas complejas
    
    if (!empty($filtros['marca'])) {
        $sql .= " AND iv.Marca = ?";
        $params[] = $filtros['marca'];
        $types .= 's';
    }
    
    $sql .= " ORDER BY iv.FechaIngreso DESC";
    
    try {
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Error preparando consulta obtenerVehiculosFiltrados: " . $conn->error);
            $conn->close();
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log("Error ejecutando consulta obtenerVehiculosFiltrados: " . $stmt->error);
            $stmt->close();
            $conn->close();
            return [];
        }
        
        $result = $stmt->get_result();
        
        // Obtener IDs de vehículos para consultar asignaciones
        $vehiculosIds = [];
        $vehiculosData = [];
        
        while ($row = $result->fetch_assoc()) {
            $vehiculoId = intval($row['ID'] ?? 0);
            $vehiculosIds[] = $vehiculoId;
            $vehiculosData[$vehiculoId] = $row;
        }
        
        $stmt->close();
        
        // Obtener estados de asignaciones activas en una sola consulta
        $estadosAsignaciones = [];
        if (!empty($vehiculosIds)) {
            // Sanitizar IDs y construir consulta segura
            $vehiculosIdsSanitizados = array_filter(array_map('intval', $vehiculosIds), function($id) {
                return $id > 0;
            });
            
            if (!empty($vehiculosIdsSanitizados)) {
                $placeholders = str_repeat('?,', count($vehiculosIdsSanitizados) - 1) . '?';
                $sqlAsignaciones = "SELECT VehiculoID, Estado 
                                   FROM asignaciones_mecanico 
                                   WHERE VehiculoID IN ($placeholders) 
                                   AND Estado IN ('Asignado', 'En Proceso', 'En Pausa', 'En Revisión', 'Completado')
                                   ORDER BY VehiculoID, FechaAsignacion DESC";
                
                $stmtAsig = $conn->prepare($sqlAsignaciones);
                if ($stmtAsig) {
                    $typesAsig = str_repeat('i', count($vehiculosIdsSanitizados));
                    $stmtAsig->bind_param($typesAsig, ...$vehiculosIdsSanitizados);
                    
                    if ($stmtAsig->execute()) {
                        $resultAsignaciones = $stmtAsig->get_result();
                        while ($rowAsig = $resultAsignaciones->fetch_assoc()) {
                            $vehId = intval($rowAsig['VehiculoID']);
                            // Solo guardar el estado más reciente por vehículo (ordenado por fecha desc)
                            if (!isset($estadosAsignaciones[$vehId])) {
                                $estadosAsignaciones[$vehId] = $rowAsig['Estado'];
                            }
                        }
                    }
                    $stmtAsig->close();
                }
            }
        }
        
        // Construir array final con estados calculados
        $vehiculos = [];
        foreach ($vehiculosData as $vehiculoId => $row) {
            $estadoRaw = trim($row['EstadoRaw'] ?? '');
            
            // Calcular estado: prioridad 1 = asignación activa, 2 = estado del vehículo, 3 = En Circulación
            $estadoFinal = 'En Circulación'; // Por defecto
            
            if (isset($estadosAsignaciones[$vehiculoId])) {
                // Si tiene asignación activa, usar ese estado
                $estadoFinal = $estadosAsignaciones[$vehiculoId];
            } elseif (in_array($estadoRaw, ['Ingresado', 'Asignado', 'Completado', 'En Proceso', 'En Pausa'])) {
                // Si tiene estado válido de taller, usarlo
                $estadoFinal = $estadoRaw;
            }
            
            $vehiculo = [
                'ID' => $vehiculoId,
                'Placa' => $row['Placa'] ?? '',
                'TipoVehiculo' => $row['TipoVehiculo'] ?? '',
                'Marca' => $row['Marca'] ?? '',
                'Modelo' => $row['Modelo'] ?? '',
                'Anio' => $row['Anio'] ?? '',
                'MarcaModelo' => !empty($row['MarcaModelo']) ? trim($row['MarcaModelo']) : trim(($row['Marca'] ?? '') . ' ' . ($row['Modelo'] ?? '')),
                'ConductorNombre' => $row['ConductorNombre'] ?? '',
                'Estado' => $estadoFinal,
                'FechaIngreso' => $row['FechaIngreso'] ?? null,
                'FechaRegistro' => $row['FechaRegistro'] ?? null,
                'Kilometraje' => $row['Kilometraje'] ?? null
            ];
            
            $vehiculos[] = $vehiculo;
        }
        
        // Aplicar filtro de estado después de calcular los estados
        if (!empty($filtros['estado'])) {
            $estadoFiltro = $filtros['estado'];
            $vehiculos = array_filter($vehiculos, function($v) use ($estadoFiltro) {
                return $v['Estado'] === $estadoFiltro;
            });
            $vehiculos = array_values($vehiculos); // Re-indexar el array
        }
        
        $conn->close();
        
        return $vehiculos;
        
    } catch (Exception $e) {
        error_log("Excepción en obtenerVehiculosFiltrados: " . $e->getMessage());
        if (isset($stmt)) {
            $stmt->close();
        }
        if ($conn) {
            $conn->close();
        }
        return [];
    }
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
 * Exporta CSV completo de la base de datos (todas las pestañas)
 */
function exportarCSVCompleto() {
    require_once __DIR__ . '/f_exportar_excel.php';
    
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers para CSV
    $filename = 'base_datos_completa_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Título principal
    fputcsv($output, ['BASE DE DATOS COMPLETA - TODAS LAS PESTAÑAS']);
    fputcsv($output, ['Generado el ' . date('d/m/Y H:i:s') . ' - Sistema PepsiCo']);
    fputcsv($output, []); // Línea en blanco
    
    // Obtener datos de todas las pestañas
    $vehiculos = obtenerVehiculosFiltrados([]);
    $agendas = obtenerAgendasTaller();
    $repuestos = obtenerRepuestos();
    $usuarios = obtenerUsuarios();
    $conductores = obtenerAnalisisConductores();
    
    // Exportar Vehículos
    fputcsv($output, ['=== VEHÍCULOS ===']);
    fputcsv($output, ['Placa', 'Tipo Vehículo', 'Marca', 'Modelo', 'Año', 'Conductor', 'Fecha Ingreso', 'Estado', 'Kilometraje', 'Fecha Registro']);
    foreach ($vehiculos as $vehiculo) {
        $fechaIngreso = !empty($vehiculo['FechaIngreso']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaIngreso'])) : '-';
        $fechaRegistro = !empty($vehiculo['FechaRegistro']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaRegistro'])) : '-';
        fputcsv($output, [
            $vehiculo['Placa'] ?? '-',
            $vehiculo['TipoVehiculo'] ?? '-',
            $vehiculo['Marca'] ?? '-',
            $vehiculo['Modelo'] ?? '-',
            $vehiculo['Anio'] ?? '-',
            $vehiculo['ConductorNombre'] ?? '-',
            $fechaIngreso,
            $vehiculo['Estado'] ?? '-',
            $vehiculo['Kilometraje'] ?? '-',
            $fechaRegistro
        ]);
    }
    fputcsv($output, []); // Línea en blanco
    
    // Exportar Agendas
    fputcsv($output, ['=== AGENDAS ===']);
    fputcsv($output, ['ID', 'Fecha', 'Hora Inicio', 'Hora Fin', 'Disponible', 'Observaciones', 'Fecha Creación']);
    foreach ($agendas as $agenda) {
        $fecha = !empty($agenda['Fecha']) ? date('d/m/Y', strtotime($agenda['Fecha'])) : '-';
        $disponible = $agenda['Disponible'] == 1 ? 'Sí' : 'No';
        $fechaCreacion = !empty($agenda['FechaCreacion']) ? date('d/m/Y H:i', strtotime($agenda['FechaCreacion'])) : '-';
        fputcsv($output, [
            $agenda['ID'] ?? '-',
            $fecha,
            $agenda['HoraInicio'] ?? '-',
            $agenda['HoraFin'] ?? '-',
            $disponible,
            $agenda['Observaciones'] ?? '-',
            $fechaCreacion
        ]);
    }
    fputcsv($output, []); // Línea en blanco
    
    // Exportar Repuestos
    fputcsv($output, ['=== REPUESTOS ===']);
    fputcsv($output, ['ID', 'Nombre', 'Descripción', 'Stock', 'Stock Mínimo', 'Precio', 'Estado', 'Fecha Creación']);
    foreach ($repuestos as $repuesto) {
        $fechaCreacion = !empty($repuesto['FechaCreacion']) ? date('d/m/Y H:i', strtotime($repuesto['FechaCreacion'])) : '-';
        fputcsv($output, [
            $repuesto['ID'] ?? '-',
            $repuesto['Nombre'] ?? '-',
            $repuesto['Descripcion'] ?? '-',
            $repuesto['Stock'] ?? 0,
            $repuesto['StockMinimo'] ?? 0,
            $repuesto['Precio'] ?? 0,
            $repuesto['Estado'] ?? '-',
            $fechaCreacion
        ]);
    }
    fputcsv($output, []); // Línea en blanco
    
    // Exportar Usuarios
    fputcsv($output, ['=== USUARIOS ===']);
    fputcsv($output, ['ID', 'Nombre', 'Correo', 'Rol', 'Estado', 'Fecha Creación', 'Último Acceso']);
    foreach ($usuarios as $usuario) {
        $fechaCreacion = !empty($usuario['FechaCreacion']) ? date('d/m/Y H:i', strtotime($usuario['FechaCreacion'])) : '-';
        $ultimoAcceso = !empty($usuario['UltimoAcceso']) ? date('d/m/Y H:i', strtotime($usuario['UltimoAcceso'])) : '-';
        fputcsv($output, [
            $usuario['ID'] ?? '-',
            $usuario['NombreUsuario'] ?? '-',
            $usuario['Correo'] ?? '-',
            $usuario['Rol'] ?? '-',
            $usuario['Estado'] ?? '-',
            $fechaCreacion,
            $ultimoAcceso
        ]);
    }
    fputcsv($output, []); // Línea en blanco
    
    // Exportar Conductores
    fputcsv($output, ['=== CONDUCTORES ===']);
    fputcsv($output, ['ID', 'Nombre', 'Correo', 'Total Vehículos', 'Vehículos Únicos', 'Primera Visita', 'Última Visita', 'Estado']);
    foreach ($conductores as $conductor) {
        $primeraVisita = !empty($conductor['PrimeraVisita']) ? date('d/m/Y', strtotime($conductor['PrimeraVisita'])) : '-';
        $ultimaVisita = !empty($conductor['UltimaVisita']) ? date('d/m/Y H:i', strtotime($conductor['UltimaVisita'])) : '-';
        fputcsv($output, [
            $conductor['ID'] ?? '-',
            $conductor['Nombre'] ?? '-',
            $conductor['Correo'] ?? '-',
            $conductor['Vehiculos'] ?? 0,
            $conductor['VehiculosUnicos'] ?? 0,
            $primeraVisita,
            $ultimaVisita,
            $conductor['Estado'] ?? '-'
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Exporta datos en formato Excel con formato visual mejorado (todas las pestañas)
 */
function exportarExcelCompleto() {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers para Excel
    $filename = 'base_datos_completa_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Obtener datos de todas las pestañas
    $vehiculos = obtenerVehiculosFiltrados([]);
    $agendas = obtenerAgendasTaller();
    $repuestos = obtenerRepuestos();
    $usuarios = obtenerUsuarios();
    $conductores = obtenerAnalisisConductores();
    
    // Generar HTML simple que Excel puede interpretar
    echo '<html>';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo '.titulo { font-size: 18px; font-weight: bold; color: #1f4788; background-color: #d9e1f2; padding: 15px; text-align: center; }';
    echo '.subtitulo { font-size: 14px; font-weight: bold; color: #2e75b6; background-color: #e7f3ff; padding: 10px; margin-top: 20px; }';
    echo 'table { border-collapse: collapse; width: 100%; margin: 10px 0; }';
    echo 'th { background-color: #4472C4; color: white; font-weight: bold; padding: 10px; text-align: left; border: 1px solid #2e5a8a; }';
    echo 'td { padding: 8px; border: 1px solid #d0d0d0; }';
    echo 'tr:nth-child(even) { background-color: #f9f9f9; }';
    echo '.separador { height: 20px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Título principal
    echo '<div class="titulo">BASE DE DATOS COMPLETA - TODAS LAS PESTAÑAS</div>';
    echo '<div class="separador"></div>';
    
    // VEHÍCULOS
    echo '<div class="subtitulo">VEHÍCULOS</div>';
    echo '<table>';
    echo '<thead><tr>';
    echo '<th>Placa</th><th>Tipo Vehículo</th><th>Marca</th><th>Modelo</th><th>Año</th>';
    echo '<th>Conductor</th><th>Fecha Ingreso</th><th>Estado</th><th>Kilometraje</th><th>Fecha Registro</th>';
    echo '</tr></thead><tbody>';
    foreach ($vehiculos as $vehiculo) {
        $fechaIngreso = !empty($vehiculo['FechaIngreso']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaIngreso'])) : '-';
        $fechaRegistro = !empty($vehiculo['FechaRegistro']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaRegistro'])) : '-';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($vehiculo['Placa'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($vehiculo['TipoVehiculo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($vehiculo['Marca'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($vehiculo['Modelo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($vehiculo['Anio'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($vehiculo['ConductorNombre'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($fechaIngreso, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($vehiculo['Estado'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($vehiculo['Kilometraje'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($fechaRegistro, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<div class="separador"></div>';
    
    // AGENDAS
    echo '<div class="subtitulo">AGENDAS</div>';
    echo '<table>';
    echo '<thead><tr>';
    echo '<th>ID</th><th>Fecha</th><th>Hora Inicio</th><th>Hora Fin</th><th>Disponible</th><th>Observaciones</th><th>Fecha Creación</th>';
    echo '</tr></thead><tbody>';
    foreach ($agendas as $agenda) {
        $fecha = !empty($agenda['Fecha']) ? date('d/m/Y', strtotime($agenda['Fecha'])) : '-';
        $disponible = $agenda['Disponible'] == 1 ? 'Sí' : 'No';
        $fechaCreacion = !empty($agenda['FechaCreacion']) ? date('d/m/Y H:i', strtotime($agenda['FechaCreacion'])) : '-';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($agenda['ID'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($agenda['HoraInicio'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($agenda['HoraFin'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($disponible, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($agenda['Observaciones'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($fechaCreacion, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<div class="separador"></div>';
    
    // REPUESTOS
    echo '<div class="subtitulo">REPUESTOS</div>';
    echo '<table>';
    echo '<thead><tr>';
    echo '<th>ID</th><th>Nombre</th><th>Descripción</th><th>Stock</th><th>Stock Mínimo</th><th>Precio</th><th>Estado</th><th>Fecha Creación</th>';
    echo '</tr></thead><tbody>';
    foreach ($repuestos as $repuesto) {
        $fechaCreacion = !empty($repuesto['FechaCreacion']) ? date('d/m/Y H:i', strtotime($repuesto['FechaCreacion'])) : '-';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($repuesto['ID'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($repuesto['Nombre'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($repuesto['Descripcion'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($repuesto['Stock'] ?? 0, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($repuesto['StockMinimo'] ?? 0, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($repuesto['Precio'] ?? 0, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($repuesto['Estado'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($fechaCreacion, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<div class="separador"></div>';
    
    // USUARIOS
    echo '<div class="subtitulo">USUARIOS</div>';
    echo '<table>';
    echo '<thead><tr>';
    echo '<th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Fecha Creación</th><th>Último Acceso</th>';
    echo '</tr></thead><tbody>';
    foreach ($usuarios as $usuario) {
        $fechaCreacion = !empty($usuario['FechaCreacion']) ? date('d/m/Y H:i', strtotime($usuario['FechaCreacion'])) : '-';
        $ultimoAcceso = !empty($usuario['UltimoAcceso']) ? date('d/m/Y H:i', strtotime($usuario['UltimoAcceso'])) : '-';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($usuario['ID'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($usuario['NombreUsuario'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($usuario['Correo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($usuario['Rol'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($usuario['Estado'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($fechaCreacion, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($ultimoAcceso, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<div class="separador"></div>';
    
    // CONDUCTORES
    echo '<div class="subtitulo">CONDUCTORES</div>';
    echo '<table>';
    echo '<thead><tr>';
    echo '<th>ID</th><th>Nombre</th><th>Correo</th><th>Total Vehículos</th><th>Vehículos Únicos</th><th>Primera Visita</th><th>Última Visita</th><th>Estado</th>';
    echo '</tr></thead><tbody>';
    foreach ($conductores as $conductor) {
        $primeraVisita = !empty($conductor['PrimeraVisita']) ? date('d/m/Y', strtotime($conductor['PrimeraVisita'])) : '-';
        $ultimaVisita = !empty($conductor['UltimaVisita']) ? date('d/m/Y H:i', strtotime($conductor['UltimaVisita'])) : '-';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($conductor['ID'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($conductor['Nombre'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($conductor['Correo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($conductor['Vehiculos'] ?? 0, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($conductor['VehiculosUnicos'] ?? 0, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($primeraVisita, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($ultimaVisita, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($conductor['Estado'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    
    // Pie de página
    echo '<div class="separador"></div>';
    echo '<div style="text-align: center; color: #666; font-size: 11px; margin-top: 30px;">';
    echo 'Generado el ' . date('d/m/Y H:i:s') . ' - Sistema PepsiCo';
    echo '</div>';
    
    echo '</body></html>';
    exit;
}

/**
 * Exporta datos en formato JSON (todas las pestañas)
 */
function exportarJSONCompleto() {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=base_datos_completa_' . date('Y-m-d') . '.json');
    
    // Obtener datos de todas las pestañas
    $vehiculos = obtenerVehiculosFiltrados([]);
    $agendas = obtenerAgendasTaller();
    $repuestos = obtenerRepuestos();
    $usuarios = obtenerUsuarios();
    $conductores = obtenerAnalisisConductores();
    
    // Estructurar datos
    $datos = [
        'fecha_exportacion' => date('Y-m-d H:i:s'),
        'vehiculos' => $vehiculos,
        'agendas' => $agendas,
        'repuestos' => $repuestos,
        'usuarios' => $usuarios,
        'conductores' => $conductores,
        'resumen' => [
            'total_vehiculos' => count($vehiculos),
            'total_agendas' => count($agendas),
            'total_repuestos' => count($repuestos),
            'total_usuarios' => count($usuarios),
            'total_conductores' => count($conductores)
        ]
    ];
    
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Exporta solo datos de vehículos con formato visual mejorado
 */
function exportarVehiculosCSV() {
    require_once __DIR__ . '/f_exportar_excel.php';
    
    $conn = conectar_Pepsico();
    $sql = "SELECT 
                ID, Placa, TipoVehiculo, Marca, Modelo, Anio,
                ConductorNombre, FechaIngreso, Estado, FechaRegistro,
                Kilometraje, UsuarioRegistro, Notificado
            FROM ingreso_vehiculos 
            ORDER BY Marca, Modelo";
    $result = $conn->query($sql);
    
    $datos = [];
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
    }
    
    $conn->close();
    
    exportarVehiculosExcel($datos);
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
 * Exporta datos de conductores con formato visual mejorado
 */
function exportarConductoresCSV() {
    require_once __DIR__ . '/f_exportar_excel.php';
    
    $datos = obtenerAnalisisConductores();
    
    exportarConductoresExcel($datos);
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

/**
 * Marca todos los vehículos como "Completado" (fuera del taller)
 * @return array - Array con status y mensaje
 */
function marcarTodosVehiculosCompletado() {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Error de conexión a la base de datos'];
    }
    
    try {
        // Contar cuántos vehículos se van a actualizar (para el mensaje)
        $sqlCount = "SELECT COUNT(*) as total 
                     FROM ingreso_vehiculos 
                     WHERE Estado IS NULL 
                        OR Estado = '' 
                        OR Estado != 'Completado'";
        
        $resultCount = $conn->query($sqlCount);
        $row = $resultCount->fetch_assoc();
        $totalAVehiculos = intval($row['total']);
        
        // Actualizar todos los vehículos a "Completado"
        $sql = "UPDATE ingreso_vehiculos 
                SET Estado = 'Completado' 
                WHERE Estado IS NULL 
                   OR Estado = '' 
                   OR Estado != 'Completado'";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            $error = $conn->error;
            error_log("Error SQL en marcarTodosVehiculosCompletado (vehículos): " . $error);
            throw new Exception('Error al actualizar vehículos: ' . $error);
        }
        
        $vehiculosActualizados = $conn->affected_rows > 0 ? $conn->affected_rows : $totalAVehiculos;
        
        // También actualizar asignaciones activas a "Completado"
        $sqlAsignaciones = "UPDATE asignaciones_mecanico 
                           SET Estado = 'Completado' 
                           WHERE Estado IN ('Asignado', 'En Proceso', 'En Pausa', 'En Revisión')";
        
        $resultAsignaciones = $conn->query($sqlAsignaciones);
        
        if (!$resultAsignaciones) {
            // Log del error pero no fallar la operación principal
            $errorAsignaciones = $conn->error;
            error_log("Error al actualizar asignaciones: " . $errorAsignaciones);
        }
        
        $conn->close();
        
        return [
            'success' => true, 
            'message' => "Se marcaron {$vehiculosActualizados} vehículo(s) como completados (fuera del taller)",
            'vehiculos_actualizados' => $vehiculosActualizados
        ];
        
    } catch (Exception $e) {
        if (isset($conn) && $conn) {
            $conn->close();
        }
        error_log("Error en marcarTodosVehiculosCompletado: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()];
    }
}

/**
 * Asigna un vehículo de Luis López a Pedro Sánchez
 * Quita el vehículo más antiguo de Luis López y se lo asigna a Pedro
 * @return array - Array con status y mensaje
 */
function asignarVehiculoPedro() {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Error de conexión a la base de datos'];
    }
    
    try {
        $nombreLuis = 'Luis López';
        $nombrePedro = 'Pedro Sanchez';
        
        // 1. Buscar el vehículo más antiguo de Luis López
        $sqlBuscar = "SELECT ID, Placa, FechaIngreso 
                     FROM ingreso_vehiculos 
                     WHERE ConductorNombre = ? 
                     ORDER BY FechaIngreso ASC, ID ASC 
                     LIMIT 1";
        
        $stmt = $conn->prepare($sqlBuscar);
        $stmt->bind_param("s", $nombreLuis);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result || $result->num_rows == 0) {
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Luis López no tiene vehículos para reasignar'];
        }
        
        $vehiculo = $result->fetch_assoc();
        $vehiculoId = intval($vehiculo['ID']);
        $placa = $vehiculo['Placa'];
        $stmt->close();
        
        // 2. Verificar que Pedro existe como chofer
        $sqlVerificarPedro = "SELECT UsuarioID, NombreUsuario 
                             FROM USUARIOS 
                             WHERE Rol = 'Chofer' AND NombreUsuario = ? 
                             LIMIT 1";
        
        $stmtPedro = $conn->prepare($sqlVerificarPedro);
        $stmtPedro->bind_param("s", $nombrePedro);
        $stmtPedro->execute();
        $resultPedro = $stmtPedro->get_result();
        
        if (!$resultPedro || $resultPedro->num_rows == 0) {
            $stmtPedro->close();
            $conn->close();
            return ['success' => false, 'message' => 'Pedro Sánchez no existe como chofer en el sistema'];
        }
        
        $stmtPedro->close();
        
        // 3. Asignar el vehículo a Pedro
        $sqlAsignar = "UPDATE ingreso_vehiculos 
                      SET ConductorNombre = ? 
                      WHERE ID = ?";
        
        $stmtAsignar = $conn->prepare($sqlAsignar);
        $stmtAsignar->bind_param("si", $nombrePedro, $vehiculoId);
        
        if (!$stmtAsignar->execute()) {
            $error = $stmtAsignar->error;
            $stmtAsignar->close();
            $conn->close();
            error_log("Error al asignar vehículo: " . $error);
            return ['success' => false, 'message' => 'Error al asignar vehículo: ' . $error];
        }
        
        $stmtAsignar->close();
        $conn->close();
        
        return [
            'success' => true, 
            'message' => "Vehículo con placa {$placa} asignado correctamente de Luis López a Pedro Sánchez.",
            'vehiculo_id' => $vehiculoId,
            'placa' => $placa
        ];
        
    } catch (Exception $e) {
        if (isset($conn) && $conn) {
            $conn->close();
        }
        error_log("Error en asignarVehiculoPedro: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al asignar vehículo: ' . $e->getMessage()];
    }
}

/**
 * Elimina vehículos duplicados (misma placa), manteniendo solo el más reciente
 * @return array - Array con status y mensaje
 */
function eliminarVehiculosDuplicados() {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Error de conexión a la base de datos'];
    }
    
    try {
        // Identificar vehículos duplicados por placa
        // Mantener el que tenga el ID más alto (más reciente) o FechaRegistro más reciente
        $sql = "DELETE iv1 FROM ingreso_vehiculos iv1
                INNER JOIN ingreso_vehiculos iv2 
                WHERE iv1.Placa = iv2.Placa 
                AND iv1.ID < iv2.ID";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            $error = $conn->error;
            error_log("Error SQL en eliminarVehiculosDuplicados: " . $error);
            throw new Exception('Error al eliminar duplicados: ' . $error);
        }
        
        $vehiculosEliminados = $conn->affected_rows;
        
        $conn->close();
        
        return [
            'success' => true, 
            'message' => "Se eliminaron {$vehiculosEliminados} vehículo(s) duplicado(s). Se mantuvieron los registros más recientes.",
            'vehiculos_eliminados' => $vehiculosEliminados
        ];
        
    } catch (Exception $e) {
        if (isset($conn) && $conn) {
            $conn->close();
        }
        error_log("Error en eliminarVehiculosDuplicados: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al eliminar duplicados: ' . $e->getMessage()];
    }
}
?>