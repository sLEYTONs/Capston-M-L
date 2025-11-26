<?php
/**
 * Genera un archivo Excel con formato visual mejorado usando HTML
 */
function exportarReporteSemanalExcel($datos, $semana) {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers para Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_semanal_' . $semana . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
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
    echo '.numero { text-align: right; }';
    echo '.moneda { text-align: right; color: #006600; font-weight: bold; }';
    echo '.centro { text-align: center; }';
    echo '.badge-completado { background-color: #28a745; color: white; padding: 3px 8px; }';
    echo '.badge-proceso { background-color: #17a2b8; color: white; padding: 3px 8px; }';
    echo '.badge-asignado { background-color: #ffc107; color: #000; padding: 3px 8px; }';
    echo '.separador { height: 20px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Título principal
    echo '<div class="titulo">REPORTE SEMANAL - ' . htmlspecialchars($semana, ENT_QUOTES, 'UTF-8') . '</div>';
    echo '<div class="separador"></div>';
    
    // Resumen
    echo '<div class="subtitulo">RESUMEN EJECUTIVO</div>';
    echo '<div class="resumen">';
    echo '<div class="resumen-item"><span class="resumen-label">Vehículos Atendidos:</span> <span class="resumen-valor">' . ($datos['resumen']['vehiculos_atendidos'] ?? 0) . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Total Gastos:</span> <span class="resumen-valor">$' . number_format($datos['resumen']['total_gastos'] ?? 0, 2, '.', ',') . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Repuestos Utilizados:</span> <span class="resumen-valor">' . ($datos['resumen']['repuestos_utilizados'] ?? 0) . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Tiempo Promedio en Taller:</span> <span class="resumen-valor">' . ($datos['resumen']['tiempo_promedio'] ?? 0) . ' días</span></div>';
    echo '</div>';
    echo '<div class="separador"></div>';
    
    // Vehículos
    if (!empty($datos['vehiculos'])) {
        echo '<div class="subtitulo">VEHÍCULOS ATENDIDOS</div>';
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>Placa</th>';
        echo '<th>Vehículo</th>';
        echo '<th>Fecha Ingreso</th>';
        echo '<th>Fecha Salida</th>';
        echo '<th class="centro">Días en Taller</th>';
        echo '<th class="centro">Estado</th>';
        echo '<th>Servicio</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($datos['vehiculos'] as $vehiculo) {
            $vehiculoNombre = trim(($vehiculo['Marca'] ?? '') . ' ' . ($vehiculo['Modelo'] ?? ''));
            $fechaIngreso = !empty($vehiculo['FechaIngreso']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaIngreso'])) : '-';
            $fechaSalida = !empty($vehiculo['FechaSalida']) ? date('d/m/Y H:i', strtotime($vehiculo['FechaSalida'])) : '-';
            $estado = $vehiculo['Estado'] ?? '-';
            $badgeClass = '';
            if ($estado === 'Completado') $badgeClass = 'badge-completado';
            elseif ($estado === 'En Proceso') $badgeClass = 'badge-proceso';
            elseif ($estado === 'Asignado') $badgeClass = 'badge-asignado';
            
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($vehiculo['Placa'] ?? '-', ENT_QUOTES, 'UTF-8') . '</strong></td>';
            echo '<td>' . htmlspecialchars($vehiculoNombre ?: '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($fechaIngreso, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($fechaSalida, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td class="centro numero">' . ($vehiculo['DiasEnTaller'] ?? 0) . '</td>';
            echo '<td class="centro"><span class="' . $badgeClass . '">' . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . '</span></td>';
            echo '<td>' . htmlspecialchars($vehiculo['Servicio'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '<div class="separador"></div>';
    }
    
    // Gastos
    if (!empty($datos['gastos'])) {
        echo '<div class="subtitulo">GASTOS</div>';
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>Fecha</th>';
        echo '<th>Vehículo</th>';
        echo '<th class="centro">Tipo</th>';
        echo '<th>Concepto</th>';
        echo '<th class="moneda">Costo</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($datos['gastos'] as $gasto) {
            $fecha = !empty($gasto['Fecha']) ? date('d/m/Y', strtotime($gasto['Fecha'])) : '-';
            $tipo = $gasto['Tipo'] ?? '-';
            $tipoClass = $tipo === 'Interno' ? 'badge-proceso' : 'badge-asignado';
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($gasto['Vehiculo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td class="centro"><span class="' . $tipoClass . '">' . htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') . '</span></td>';
            echo '<td>' . htmlspecialchars($gasto['Concepto'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td class="moneda">$' . number_format($gasto['Costo'] ?? 0, 2, '.', ',') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '<div class="separador"></div>';
    }
    
    // Repuestos
    if (!empty($datos['repuestos'])) {
        echo '<div class="subtitulo">REPUESTOS UTILIZADOS</div>';
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>Código</th>';
        echo '<th>Nombre</th>';
        echo '<th class="centro numero">Cantidad</th>';
        echo '<th class="moneda">Precio Unitario</th>';
        echo '<th class="moneda">Total</th>';
        echo '<th>Vehículo</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($datos['repuestos'] as $repuesto) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($repuesto['Codigo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</strong></td>';
            echo '<td>' . htmlspecialchars($repuesto['Nombre'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td class="centro numero">' . ($repuesto['Cantidad'] ?? 0) . '</td>';
            echo '<td class="moneda">$' . number_format($repuesto['PrecioUnitario'] ?? 0, 2, '.', ',') . '</td>';
            echo '<td class="moneda"><strong>$' . number_format($repuesto['Total'] ?? 0, 2, '.', ',') . '</strong></td>';
            echo '<td>' . htmlspecialchars($repuesto['Vehiculo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
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
