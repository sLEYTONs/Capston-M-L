<?php
/**
 * Genera un archivo Excel con formato visual mejorado para la base de datos
 */
function exportarBaseDatosExcel($datos) {
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
    
    // Calcular resumen
    $totalRegistros = count($datos);
    $vehiculosActivos = 0;
    $vehiculosCompletados = 0;
    $vehiculosEnProceso = 0;
    
    foreach ($datos as $vehiculo) {
        $estado = $vehiculo['Estado'] ?? '';
        if ($estado === 'Ingresado' || $estado === 'Asignado') {
            $vehiculosActivos++;
        } elseif ($estado === 'Completado') {
            $vehiculosCompletados++;
        } elseif ($estado === 'En Proceso') {
            $vehiculosEnProceso++;
        }
    }
    
    // Generar HTML simple que Excel puede interpretar
    echo '<html>';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo '.titulo { font-size: 18px; font-weight: bold; color: #1f4788; background-color: #d9e1f2; padding: 15px; text-align: center; }';
    echo '.subtitulo { font-size: 14px; font-weight: bold; color: #2e75b6; background-color: #e7f3ff; padding: 10px; margin-top: 20px; }';
    echo '.resumen { background-color: #f2f2f2; padding: 10px; margin: 10px 0; }';
    echo '.resumen-item { padding: 5px; }';
    echo '.resumen-label { font-weight: bold; color: #333; }';
    echo '.resumen-valor { color: #0066cc; font-size: 14px; }';
    echo 'table { border-collapse: collapse; width: 100%; margin: 10px 0; }';
    echo 'th { background-color: #4472C4; color: white; font-weight: bold; padding: 10px; text-align: left; border: 1px solid #2e5a8a; }';
    echo 'td { padding: 8px; border: 1px solid #d0d0d0; }';
    echo 'tr:nth-child(even) { background-color: #f9f9f9; }';
    echo '.centro { text-align: center; }';
    echo '.badge-completado { background-color: #28a745; color: white; padding: 3px 8px; }';
    echo '.badge-proceso { background-color: #17a2b8; color: white; padding: 3px 8px; }';
    echo '.badge-asignado { background-color: #ffc107; color: #000; padding: 3px 8px; }';
    echo '.badge-ingresado { background-color: #6c757d; color: white; padding: 3px 8px; }';
    echo '.separador { height: 20px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Título principal
    echo '<div class="titulo">BASE DE DATOS COMPLETA - VEHÍCULOS</div>';
    echo '<div class="separador"></div>';
    
    // Resumen
    echo '<div class="subtitulo">RESUMEN EJECUTIVO</div>';
    echo '<div class="resumen">';
    echo '<div class="resumen-item"><span class="resumen-label">Total Registros:</span> <span class="resumen-valor">' . $totalRegistros . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Vehículos Activos:</span> <span class="resumen-valor">' . $vehiculosActivos . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">En Proceso:</span> <span class="resumen-valor">' . $vehiculosEnProceso . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Completados:</span> <span class="resumen-valor">' . $vehiculosCompletados . '</span></div>';
    echo '</div>';
    echo '<div class="separador"></div>';
    
    // Tabla de datos
    if (!empty($datos)) {
        echo '<div class="subtitulo">DETALLE DE VEHÍCULOS</div>';
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>Placa</th>';
        echo '<th>Tipo Vehículo</th>';
        echo '<th>Marca</th>';
        echo '<th>Modelo</th>';
        echo '<th>Año</th>';
        echo '<th>Conductor</th>';
        echo '<th>Fecha Ingreso</th>';
        echo '<th class="centro">Estado</th>';
        echo '<th>Kilometraje</th>';
        echo '<th>Fecha Registro</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($datos as $vehiculo) {
            $estado = $vehiculo['Estado'] ?? '-';
            $badgeClass = '';
            if ($estado === 'Completado') $badgeClass = 'badge-completado';
            elseif ($estado === 'En Proceso') $badgeClass = 'badge-proceso';
            elseif ($estado === 'Asignado') $badgeClass = 'badge-asignado';
            elseif ($estado === 'Ingresado') $badgeClass = 'badge-ingresado';
            
            $fechaIngreso = !empty($vehiculo['FechaIngreso']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaIngreso'])) : '-';
            $fechaRegistro = !empty($vehiculo['FechaRegistro']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaRegistro'])) : '-';
            
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($vehiculo['Placa'] ?? '-', ENT_QUOTES, 'UTF-8') . '</strong></td>';
            echo '<td>' . htmlspecialchars($vehiculo['TipoVehiculo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($vehiculo['Marca'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($vehiculo['Modelo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($vehiculo['Anio'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($vehiculo['ConductorNombre'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($fechaIngreso, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td class="centro"><span class="' . $badgeClass . '">' . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . '</span></td>';
            echo '<td>' . htmlspecialchars($vehiculo['Kilometraje'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($fechaRegistro, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    // Pie de página
    echo '<div class="separador"></div>';
    echo '<div style="text-align: center; color: #666; font-size: 11px; margin-top: 30px;">';
    echo 'Generado el ' . date('d/m/Y H:i:s') . ' - Sistema PepsiCo';
    echo '</div>';
    
    echo '</body></html>';
    exit;
}

/**
 * Genera un archivo CSV con formato mejorado para la base de datos
 */
function exportarBaseDatosCSV($datos) {
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
    
    // Título del reporte
    fputcsv($output, ['BASE DE DATOS COMPLETA - VEHÍCULOS']);
    fputcsv($output, ['Generado el ' . date('d/m/Y H:i:s') . ' - Sistema PepsiCo']);
    fputcsv($output, []); // Línea en blanco
    
    // Resumen
    $totalRegistros = count($datos);
    $vehiculosActivos = 0;
    $vehiculosCompletados = 0;
    $vehiculosEnProceso = 0;
    
    foreach ($datos as $vehiculo) {
        $estado = $vehiculo['Estado'] ?? '';
        if ($estado === 'Ingresado' || $estado === 'Asignado') {
            $vehiculosActivos++;
        } elseif ($estado === 'Completado') {
            $vehiculosCompletados++;
        } elseif ($estado === 'En Proceso') {
            $vehiculosEnProceso++;
        }
    }
    
    fputcsv($output, ['RESUMEN EJECUTIVO']);
    fputcsv($output, ['Total Registros', $totalRegistros]);
    fputcsv($output, ['Vehículos Activos', $vehiculosActivos]);
    fputcsv($output, ['En Proceso', $vehiculosEnProceso]);
    fputcsv($output, ['Completados', $vehiculosCompletados]);
    fputcsv($output, []); // Línea en blanco
    
    // Encabezados de la tabla
    fputcsv($output, ['DETALLE DE VEHÍCULOS']);
    fputcsv($output, [
        'Placa', 
        'Tipo Vehículo', 
        'Marca', 
        'Modelo', 
        'Año',
        'Conductor', 
        'Fecha Ingreso', 
        'Estado', 
        'Kilometraje', 
        'Fecha Registro'
    ]);
    
    // Datos
    foreach ($datos as $vehiculo) {
        $fechaIngreso = !empty($vehiculo['FechaIngreso']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaIngreso'])) : '-';
        $fechaRegistro = !empty($vehiculo['FechaRegistro']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaRegistro'])) : '-';
        $kilometraje = !empty($vehiculo['Kilometraje']) ? number_format($vehiculo['Kilometraje'], 0, '.', ',') . ' km' : '-';
        
        fputcsv($output, [
            $vehiculo['Placa'] ?? '-',
            $vehiculo['TipoVehiculo'] ?? '-',
            $vehiculo['Marca'] ?? '-',
            $vehiculo['Modelo'] ?? '-',
            $vehiculo['Anio'] ?? '-',
            $vehiculo['ConductorNombre'] ?? '-',
            $fechaIngreso,
            $vehiculo['Estado'] ?? '-',
            $kilometraje,
            $fechaRegistro
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Genera un archivo Excel con formato visual mejorado para vehículos
 */
function exportarVehiculosExcel($datos) {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers para Excel
    $filename = 'vehiculos_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Calcular resumen
    $totalRegistros = count($datos);
    $vehiculosActivos = 0;
    $vehiculosCompletados = 0;
    $vehiculosEnProceso = 0;
    $marcasUnicas = [];
    
    foreach ($datos as $vehiculo) {
        $estado = $vehiculo['Estado'] ?? '';
        if ($estado === 'Ingresado' || $estado === 'Asignado') {
            $vehiculosActivos++;
        } elseif ($estado === 'Completado') {
            $vehiculosCompletados++;
        } elseif ($estado === 'En Proceso') {
            $vehiculosEnProceso++;
        }
        
        $marca = $vehiculo['Marca'] ?? '';
        if ($marca && !in_array($marca, $marcasUnicas)) {
            $marcasUnicas[] = $marca;
        }
    }
    
    // Generar HTML simple que Excel puede interpretar
    echo '<html>';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo '.titulo { font-size: 18px; font-weight: bold; color: #1f4788; background-color: #d9e1f2; padding: 15px; text-align: center; }';
    echo '.subtitulo { font-size: 14px; font-weight: bold; color: #2e75b6; background-color: #e7f3ff; padding: 10px; margin-top: 20px; }';
    echo '.resumen { background-color: #f2f2f2; padding: 10px; margin: 10px 0; }';
    echo '.resumen-item { padding: 5px; }';
    echo '.resumen-label { font-weight: bold; color: #333; }';
    echo '.resumen-valor { color: #0066cc; font-size: 14px; }';
    echo 'table { border-collapse: collapse; width: 100%; margin: 10px 0; }';
    echo 'th { background-color: #4472C4; color: white; font-weight: bold; padding: 10px; text-align: left; border: 1px solid #2e5a8a; }';
    echo 'td { padding: 8px; border: 1px solid #d0d0d0; }';
    echo 'tr:nth-child(even) { background-color: #f9f9f9; }';
    echo '.centro { text-align: center; }';
    echo '.badge-completado { background-color: #28a745; color: white; padding: 3px 8px; }';
    echo '.badge-proceso { background-color: #17a2b8; color: white; padding: 3px 8px; }';
    echo '.badge-asignado { background-color: #ffc107; color: #000; padding: 3px 8px; }';
    echo '.badge-ingresado { background-color: #6c757d; color: white; padding: 3px 8px; }';
    echo '.separador { height: 20px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Título principal
    echo '<div class="titulo">REPORTE DE VEHÍCULOS</div>';
    echo '<div class="separador"></div>';
    
    // Resumen
    echo '<div class="subtitulo">RESUMEN EJECUTIVO</div>';
    echo '<div class="resumen">';
    echo '<div class="resumen-item"><span class="resumen-label">Total Vehículos:</span> <span class="resumen-valor">' . $totalRegistros . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Vehículos Activos:</span> <span class="resumen-valor">' . $vehiculosActivos . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">En Proceso:</span> <span class="resumen-valor">' . $vehiculosEnProceso . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Completados:</span> <span class="resumen-valor">' . $vehiculosCompletados . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Marcas Diferentes:</span> <span class="resumen-valor">' . count($marcasUnicas) . '</span></div>';
    echo '</div>';
    echo '<div class="separador"></div>';
    
    // Tabla de datos
    if (!empty($datos)) {
        echo '<div class="subtitulo">DETALLE DE VEHÍCULOS</div>';
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>Placa</th>';
        echo '<th>Tipo Vehículo</th>';
        echo '<th>Marca</th>';
        echo '<th>Modelo</th>';
        echo '<th>Año</th>';
        echo '<th>Conductor</th>';
        echo '<th>Fecha Ingreso</th>';
        echo '<th class="centro">Estado</th>';
        echo '<th>Kilometraje</th>';
        echo '<th>Fecha Registro</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($datos as $vehiculo) {
            $estado = $vehiculo['Estado'] ?? '-';
            $badgeClass = '';
            if ($estado === 'Completado') $badgeClass = 'badge-completado';
            elseif ($estado === 'En Proceso') $badgeClass = 'badge-proceso';
            elseif ($estado === 'Asignado') $badgeClass = 'badge-asignado';
            elseif ($estado === 'Ingresado') $badgeClass = 'badge-ingresado';
            
            $fechaIngreso = !empty($vehiculo['FechaIngreso']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaIngreso'])) : '-';
            $fechaRegistro = !empty($vehiculo['FechaRegistro']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaRegistro'])) : '-';
            $kilometraje = !empty($vehiculo['Kilometraje']) ? number_format($vehiculo['Kilometraje'], 0, '.', ',') . ' km' : '-';
            
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($vehiculo['Placa'] ?? '-', ENT_QUOTES, 'UTF-8') . '</strong></td>';
            echo '<td>' . htmlspecialchars($vehiculo['TipoVehiculo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($vehiculo['Marca'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($vehiculo['Modelo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($vehiculo['Anio'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($vehiculo['ConductorNombre'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($fechaIngreso, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td class="centro"><span class="' . $badgeClass . '">' . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . '</span></td>';
            echo '<td>' . htmlspecialchars($kilometraje, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($fechaRegistro, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    // Pie de página
    echo '<div class="separador"></div>';
    echo '<div style="text-align: center; color: #666; font-size: 11px; margin-top: 30px;">';
    echo 'Generado el ' . date('d/m/Y H:i:s') . ' - Sistema PepsiCo';
    echo '</div>';
    
    echo '</body></html>';
    exit;
}

/**
 * Genera un archivo Excel con formato visual mejorado para conductores
 */
function exportarConductoresExcel($datos) {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers para Excel
    $filename = 'conductores_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Calcular resumen
    $totalConductores = count($datos);
    $totalVehiculos = 0;
    $totalVehiculosUnicos = 0;
    
    foreach ($datos as $conductor) {
        $totalVehiculos += intval($conductor['Vehiculos'] ?? 0);
        $totalVehiculosUnicos += intval($conductor['VehiculosUnicos'] ?? 0);
    }
    
    // Generar HTML simple que Excel puede interpretar
    echo '<html>';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo '.titulo { font-size: 18px; font-weight: bold; color: #1f4788; background-color: #d9e1f2; padding: 15px; text-align: center; }';
    echo '.subtitulo { font-size: 14px; font-weight: bold; color: #2e75b6; background-color: #e7f3ff; padding: 10px; margin-top: 20px; }';
    echo '.resumen { background-color: #f2f2f2; padding: 10px; margin: 10px 0; }';
    echo '.resumen-item { padding: 5px; }';
    echo '.resumen-label { font-weight: bold; color: #333; }';
    echo '.resumen-valor { color: #0066cc; font-size: 14px; }';
    echo 'table { border-collapse: collapse; width: 100%; margin: 10px 0; }';
    echo 'th { background-color: #4472C4; color: white; font-weight: bold; padding: 10px; text-align: left; border: 1px solid #2e5a8a; }';
    echo 'td { padding: 8px; border: 1px solid #d0d0d0; }';
    echo 'tr:nth-child(even) { background-color: #f9f9f9; }';
    echo '.centro { text-align: center; }';
    echo '.numero { text-align: right; }';
    echo '.badge-completado { background-color: #28a745; color: white; padding: 3px 8px; }';
    echo '.badge-ingresado { background-color: #6c757d; color: white; padding: 3px 8px; }';
    echo '.separador { height: 20px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Título principal
    echo '<div class="titulo">REPORTE DE CONDUCTORES</div>';
    echo '<div class="separador"></div>';
    
    // Resumen
    echo '<div class="subtitulo">RESUMEN EJECUTIVO</div>';
    echo '<div class="resumen">';
    echo '<div class="resumen-item"><span class="resumen-label">Total Conductores:</span> <span class="resumen-valor">' . $totalConductores . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Total Vehículos Asociados:</span> <span class="resumen-valor">' . $totalVehiculos . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Vehículos Únicos:</span> <span class="resumen-valor">' . $totalVehiculosUnicos . '</span></div>';
    echo '</div>';
    echo '<div class="separador"></div>';
    
    // Tabla de datos
    if (!empty($datos)) {
        echo '<div class="subtitulo">DETALLE DE CONDUCTORES</div>';
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>Nombre</th>';
        echo '<th>Correo</th>';
        echo '<th class="centro numero">Total Vehículos</th>';
        echo '<th class="centro numero">Vehículos Únicos</th>';
        echo '<th>Primera Visita</th>';
        echo '<th>Última Visita</th>';
        echo '<th class="centro">Estado</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($datos as $conductor) {
            $estado = $conductor['Estado'] ?? 'Activo';
            $badgeClass = $estado === 'Activo' ? 'badge-completado' : 'badge-ingresado';
            
            $primeraVisita = !empty($conductor['PrimeraVisita']) ? date('d/m/Y', strtotime($conductor['PrimeraVisita'])) : '-';
            $ultimaVisita = !empty($conductor['UltimaVisita']) ? date('d/m/Y H:i', strtotime($conductor['UltimaVisita'])) : '-';
            
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($conductor['Nombre'] ?? '-', ENT_QUOTES, 'UTF-8') . '</strong></td>';
            echo '<td>' . htmlspecialchars($conductor['Correo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td class="centro numero">' . ($conductor['Vehiculos'] ?? 0) . '</td>';
            echo '<td class="centro numero">' . ($conductor['VehiculosUnicos'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars($primeraVisita, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($ultimaVisita, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td class="centro"><span class="' . $badgeClass . '">' . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . '</span></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    // Pie de página
    echo '<div class="separador"></div>';
    echo '<div style="text-align: center; color: #666; font-size: 11px; margin-top: 30px;">';
    echo 'Generado el ' . date('d/m/Y H:i:s') . ' - Sistema PepsiCo';
    echo '</div>';
    
    echo '</body></html>';
    exit;
}

