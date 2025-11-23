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
    // Configurar headers para descarga de archivo
    $filename = 'reportes_mantenimientos_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Abrir output stream
    $output = fopen('php://output', 'w');
    
    // Agregar BOM para UTF-8 (Excel reconoce mejor los caracteres especiales)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados de las columnas
    $headers = [
        'ID Asignación',
        'Vehículo',
        'Placa',
        'Marca',
        'Modelo',
        'Tipo Vehículo',
        'Conductor',
        'Empresa',
        'Mecánico',
        'Fecha Asignación',
        'Estado',
        'Tiempo Mantenimiento (horas)',
        'Tiempo Mantenimiento (formateado)',
        'Costo Repuestos',
        'Cantidad Repuestos',
        'Observaciones'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Escribir datos
    foreach ($datos as $row) {
        $fila = [
            $row['AsignacionID'],
            $row['Marca'] . ' ' . $row['Modelo'],
            $row['Placa'],
            $row['Marca'],
            $row['Modelo'],
            $row['TipoVehiculo'],
            $row['ConductorNombre'] ?? '',
            '', // EmpresaNombre eliminado
            $row['MecanicoNombre'],
            $row['FechaAsignacion'],
            $row['EstadoAsignacion'],
            $row['TiempoMantenimiento'] ?? 0,
            $row['TiempoMantenimientoFormateado'] ?? '-',
            number_format($row['CostoRepuestos'] ?? 0, 2, '.', ''),
            $row['CantidadRepuestos'] ?? 0,
            $row['Observaciones'] ?? ''
        ];
        
        fputcsv($output, $fila, ';');
    }
    
    fclose($output);
    exit;
}

