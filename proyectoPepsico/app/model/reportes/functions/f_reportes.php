<?php
require_once __DIR__ . '../../../../config/conexion.php';

/**
 * Obtiene reportes de mantenimientos con información completa
 * @param array $filtros - Array con filtros de búsqueda (fecha_inicio, fecha_fin, mecanico_id, vehiculo_id)
 * @return array - Array con los reportes de mantenimientos
 */
function obtenerReportesMantenimientos($filtros = []) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    // Verificar si la tabla repuestos_asignacion existe
    $checkTable = "SHOW TABLES LIKE 'repuestos_asignacion'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaRepuestosExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    // Construir la consulta base
    $query = "SELECT 
                a.ID AS AsignacionID,
                a.VehiculoID,
                a.MecanicoID,
                DATE_FORMAT(a.FechaAsignacion, '%d/%m/%Y %H:%i') AS FechaAsignacion,
                DATE_FORMAT(a.FechaAsignacion, '%Y-%m-%d %H:%i:%s') AS FechaAsignacionRaw,
                a.Estado AS EstadoAsignacion,
                a.Observaciones,
                
                -- Datos del vehículo
                v.Placa,
                v.TipoVehiculo,
                v.Marca,
                v.Modelo,
                v.Anio,
                v.ConductorNombre,
                
                -- Datos del mecánico
                u.NombreUsuario AS MecanicoNombre,
                u.Correo AS MecanicoCorreo,
                
                -- Tiempo de mantenimiento
                (SELECT DATE_FORMAT(MAX(am.FechaAvance), '%Y-%m-%d %H:%i:%s')
                 FROM avances_mecanico am 
                 WHERE am.AsignacionID = a.ID 
                 AND am.Estado = 'Completado') AS FechaCompletado,
                 
                " . ($tablaRepuestosExiste ? "
                -- Costo de repuestos (si existe tabla repuestos_asignacion)
                IFNULL((
                    SELECT SUM(r.Cantidad * r.PrecioUnitario)
                    FROM repuestos_asignacion r
                    WHERE r.AsignacionID = a.ID
                ), 0) AS CostoRepuestos,
                
                -- Cantidad de repuestos
                IFNULL((
                    SELECT COUNT(*)
                    FROM repuestos_asignacion r
                    WHERE r.AsignacionID = a.ID
                ), 0) AS CantidadRepuestos,
                " : "
                0 AS CostoRepuestos,
                0 AS CantidadRepuestos,
                ") . "
                
                -- Descripción del último avance
                (SELECT am.Descripcion
                 FROM avances_mecanico am 
                 WHERE am.AsignacionID = a.ID 
                 ORDER BY am.FechaAvance DESC LIMIT 1) AS UltimoAvance
                
            FROM asignaciones_mecanico a
            INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
            INNER JOIN usuarios u ON a.MecanicoID = u.UsuarioID
            WHERE 1=1";

    $params = [];
    $types = '';

    // Aplicar filtros
    if (!empty($filtros['fecha_inicio'])) {
        $query .= " AND DATE(a.FechaAsignacion) >= ?";
        $params[] = $filtros['fecha_inicio'];
        $types .= 's';
    }

    if (!empty($filtros['fecha_fin'])) {
        $query .= " AND DATE(a.FechaAsignacion) <= ?";
        $params[] = $filtros['fecha_fin'];
        $types .= 's';
    }

    if (!empty($filtros['mecanico_id'])) {
        $query .= " AND a.MecanicoID = ?";
        $params[] = intval($filtros['mecanico_id']);
        $types .= 'i';
    }

    if (!empty($filtros['vehiculo_id'])) {
        $query .= " AND a.VehiculoID = ?";
        $params[] = intval($filtros['vehiculo_id']);
        $types .= 'i';
    }

    if (!empty($filtros['estado'])) {
        $query .= " AND a.Estado = ?";
        $params[] = $filtros['estado'];
        $types .= 's';
    }

    $query .= " ORDER BY a.FechaAsignacion DESC";

    $reportes = [];

    if (empty($params)) {
        $result = mysqli_query($conn, $query);
    } else {
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'Error preparando consulta: ' . mysqli_error($conn)];
        }
    }

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Calcular tiempo de mantenimiento en horas
            $tiempoMantenimiento = calcularTiempoMantenimiento(
                $row['FechaAsignacionRaw'], 
                $row['FechaCompletado']
            );
            
            $row['TiempoMantenimiento'] = $tiempoMantenimiento['horas'];
            $row['TiempoMantenimientoFormateado'] = $tiempoMantenimiento['formateado'];
            $row['CostoRepuestos'] = floatval($row['CostoRepuestos']);
            $row['CantidadRepuestos'] = intval($row['CantidadRepuestos']);
            
            $reportes[] = $row;
        }
        
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
        mysqli_free_result($result);
    } else {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error en consulta: ' . mysqli_error($conn)];
    }

    mysqli_close($conn);
    
    return [
        'status' => 'success',
        'data' => $reportes,
        'total' => count($reportes)
    ];
}

/**
 * Calcula el tiempo de mantenimiento entre dos fechas
 * @param string $fechaInicio - Fecha de inicio (YYYY-MM-DD HH:MM:SS)
 * @param string $fechaFin - Fecha de fin (YYYY-MM-DD HH:MM:SS) o null si aún no está completado
 * @return array - Array con horas y formato legible
 */
function calcularTiempoMantenimiento($fechaInicio, $fechaFin = null) {
    if (empty($fechaInicio)) {
        return ['horas' => 0, 'formateado' => 'N/A'];
    }

    $inicio = new DateTime($fechaInicio);
    $fin = $fechaFin ? new DateTime($fechaFin) : new DateTime();
    
    $diferencia = $inicio->diff($fin);
    
    $horas = ($diferencia->days * 24) + $diferencia->h + ($diferencia->i / 60);
    
    $formateado = '';
    if ($diferencia->days > 0) {
        $formateado .= $diferencia->days . ' día(s) ';
    }
    if ($diferencia->h > 0) {
        $formateado .= $diferencia->h . ' hora(s) ';
    }
    if ($diferencia->i > 0 && $diferencia->days == 0) {
        $formateado .= $diferencia->i . ' minuto(s)';
    }
    
    if (empty($formateado)) {
        $formateado = 'Menos de 1 minuto';
    }
    
    return [
        'horas' => round($horas, 2),
        'formateado' => trim($formateado)
    ];
}

/**
 * Obtiene estadísticas de mantenimientos
 * @param array $filtros - Array con filtros de búsqueda
 * @return array - Array con estadísticas
 */
function obtenerEstadisticasMantenimientos($filtros = []) {
    $reportes = obtenerReportesMantenimientos($filtros);
    
    if ($reportes['status'] !== 'success') {
        return $reportes;
    }
    
    $datos = $reportes['data'];
    $total = count($datos);
    
    if ($total === 0) {
        return [
            'status' => 'success',
            'total_mantenimientos' => 0,
            'costo_total' => 0,
            'tiempo_promedio' => 0,
            'costo_promedio' => 0
        ];
    }
    
    $costoTotal = 0;
    $tiempoTotal = 0;
    $completados = 0;
    
    foreach ($datos as $reporte) {
        $costoTotal += $reporte['CostoRepuestos'];
        if ($reporte['EstadoAsignacion'] === 'Completado') {
            $tiempoTotal += $reporte['TiempoMantenimiento'];
            $completados++;
        }
    }
    
    $tiempoPromedio = $completados > 0 ? $tiempoTotal / $completados : 0;
    $costoPromedio = $total > 0 ? $costoTotal / $total : 0;
    
    return [
        'status' => 'success',
        'total_mantenimientos' => $total,
        'completados' => $completados,
        'costo_total' => round($costoTotal, 2),
        'tiempo_promedio' => round($tiempoPromedio, 2),
        'costo_promedio' => round($costoPromedio, 2)
    ];
}

/**
 * Obtiene los repuestos utilizados en una asignación
 * @param int $asignacion_id - ID de la asignación
 * @return array - Array con los repuestos utilizados
 */
function obtenerRepuestosAsignacion($asignacion_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    // Verificar si la tabla repuestos_asignacion existe
    $checkTable = "SHOW TABLES LIKE 'repuestos_asignacion'";
    $resultCheck = mysqli_query($conn, $checkTable);
    
    if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
        // Si la tabla no existe, retornar array vacío
        mysqli_close($conn);
        return [];
    }
    
    // Intentar obtener de tabla repuestos_asignacion
    $query = "SELECT 
                r.ID,
                r.RepuestoID,
                r.Cantidad,
                r.PrecioUnitario,
                r.Total,
                rep.Nombre AS RepuestoNombre,
                rep.Codigo AS RepuestoCodigo,
                rep.Categoria
            FROM repuestos_asignacion r
            INNER JOIN repuestos rep ON r.RepuestoID = rep.ID
            WHERE r.AsignacionID = ?";

    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        // Si hay error al preparar, retornar array vacío
        mysqli_close($conn);
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $asignacion_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $repuestos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $repuestos[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $repuestos;
}

/**
 * Exporta reportes de mantenimientos a formato Excel (CSV)
 * @param array $datos - Array con los datos a exportar
 */
function exportarReportesExcel($datos) {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers para Excel
    $filename = 'reportes_mantenimientos_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Calcular resumen
    $totalRegistros = count($datos);
    $totalCostoRepuestos = 0;
    $totalRepuestos = 0;
    $estados = ['Asignado' => 0, 'En Proceso' => 0, 'Completado' => 0];
    
    foreach ($datos as $row) {
        $totalCostoRepuestos += floatval($row['CostoRepuestos'] ?? 0);
        $totalRepuestos += intval($row['CantidadRepuestos'] ?? 0);
        $estado = $row['EstadoAsignacion'] ?? '';
        if (isset($estados[$estado])) {
            $estados[$estado]++;
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
    echo '<div class="titulo">REPORTE DE MANTENIMIENTOS</div>';
    echo '<div class="separador"></div>';
    
    // Resumen
    echo '<div class="subtitulo">RESUMEN EJECUTIVO</div>';
    echo '<div class="resumen">';
    echo '<div class="resumen-item"><span class="resumen-label">Total de Registros:</span> <span class="resumen-valor">' . $totalRegistros . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Total Costo Repuestos:</span> <span class="resumen-valor">$' . number_format($totalCostoRepuestos, 2, '.', ',') . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Total Repuestos Utilizados:</span> <span class="resumen-valor">' . $totalRepuestos . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Asignados:</span> <span class="resumen-valor">' . $estados['Asignado'] . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">En Proceso:</span> <span class="resumen-valor">' . $estados['En Proceso'] . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Completados:</span> <span class="resumen-valor">' . $estados['Completado'] . '</span></div>';
    echo '</div>';
    echo '<div class="separador"></div>';
    
    // Tabla de datos
    if (!empty($datos)) {
        echo '<div class="subtitulo">DETALLE DE MANTENIMIENTOS</div>';
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>ID</th>';
        echo '<th>Placa</th>';
        echo '<th>Vehículo</th>';
        echo '<th>Tipo</th>';
        echo '<th>Conductor</th>';
        echo '<th>Mecánico</th>';
        echo '<th>Fecha Asignación</th>';
        echo '<th class="centro">Estado</th>';
        echo '<th class="centro">Tiempo (horas)</th>';
        echo '<th class="centro">Tiempo (formato)</th>';
        echo '<th class="moneda">Costo Repuestos</th>';
        echo '<th class="centro">Cant. Repuestos</th>';
        echo '<th>Observaciones</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($datos as $row) {
            $vehiculoNombre = trim(($row['Marca'] ?? '') . ' ' . ($row['Modelo'] ?? ''));
            $estado = $row['EstadoAsignacion'] ?? '-';
            $badgeClass = '';
            if ($estado === 'Completado') $badgeClass = 'badge-completado';
            elseif ($estado === 'En Proceso') $badgeClass = 'badge-proceso';
            elseif ($estado === 'Asignado') $badgeClass = 'badge-asignado';
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['AsignacionID'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td><strong>' . htmlspecialchars($row['Placa'] ?? '-', ENT_QUOTES, 'UTF-8') . '</strong></td>';
            echo '<td>' . htmlspecialchars($vehiculoNombre ?: '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['TipoVehiculo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['ConductorNombre'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['MecanicoNombre'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($row['FechaAsignacion'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td class="centro"><span class="' . $badgeClass . '">' . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . '</span></td>';
            echo '<td class="centro numero">' . ($row['TiempoMantenimiento'] ?? 0) . '</td>';
            echo '<td class="centro">' . htmlspecialchars($row['TiempoMantenimientoFormateado'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td class="moneda">$' . number_format($row['CostoRepuestos'] ?? 0, 2, '.', ',') . '</td>';
            echo '<td class="centro numero">' . ($row['CantidadRepuestos'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars(substr($row['Observaciones'] ?? '', 0, 100), ENT_QUOTES, 'UTF-8') . '</td>';
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

