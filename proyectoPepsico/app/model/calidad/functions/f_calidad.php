<?php
require_once __DIR__ . '../../../../config/conexion.php';

/**
 * Obtiene todas las asignaciones para revisión de calidad
 * @param array $filtros - Array con filtros de búsqueda
 * @return array - Array con las asignaciones para revisión
 */
function obtenerAsignacionesRevision($filtros = []) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    // Verificar si la tabla diagnostico_calidad existe
    $checkTable = "SHOW TABLES LIKE 'diagnostico_calidad'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaCalidadExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    $query = "SELECT 
                a.ID AS AsignacionID,
                a.VehiculoID,
                a.MecanicoID,
                DATE_FORMAT(a.FechaAsignacion, '%d/%m/%Y %H:%i') AS FechaAsignacion,
                a.Estado AS EstadoAsignacion,
                a.Observaciones AS ObservacionesAsignacion,
                
                -- Datos del vehículo
                v.Placa,
                v.TipoVehiculo,
                v.Marca,
                v.Modelo,
                v.Color,
                v.Anio,
                v.ConductorNombre,
                v.EmpresaNombre,
                
                -- Datos del mecánico
                u.NombreUsuario AS MecanicoNombre,
                u.Correo AS MecanicoCorreo,
                
                -- Diagnóstico inicial (primer avance)
                (SELECT am.Descripcion
                 FROM avances_mecanico am 
                 WHERE am.AsignacionID = a.ID 
                 ORDER BY am.FechaAvance ASC LIMIT 1) AS DiagnosticoInicial,
                 
                (SELECT DATE_FORMAT(am.FechaAvance, '%d/%m/%Y %H:%i')
                 FROM avances_mecanico am 
                 WHERE am.AsignacionID = a.ID 
                 ORDER BY am.FechaAvance ASC LIMIT 1) AS FechaDiagnostico,
                 
                -- Último avance
                (SELECT am.Descripcion
                 FROM avances_mecanico am 
                 WHERE am.AsignacionID = a.ID 
                 ORDER BY am.FechaAvance DESC LIMIT 1) AS UltimoAvance,
                 
                (SELECT DATE_FORMAT(am.FechaAvance, '%d/%m/%Y %H:%i')
                 FROM avances_mecanico am 
                 WHERE am.AsignacionID = a.ID 
                 ORDER BY am.FechaAvance DESC LIMIT 1) AS FechaUltimoAvance,
                 
                -- Total de avances
                (SELECT COUNT(*) FROM avances_mecanico WHERE AsignacionID = a.ID) AS TotalAvances,
                
                " . ($tablaCalidadExiste ? "
                -- Estado de calidad (si existe tabla diagnostico_calidad)
                COALESCE((
                    SELECT dc.EstadoCalidad
                    FROM diagnostico_calidad dc
                    WHERE dc.AsignacionID = a.ID
                    ORDER BY dc.FechaRevision DESC LIMIT 1
                ), 'Pendiente') AS EstadoCalidad,
                
                -- Observaciones de calidad
                (SELECT dc.Observaciones
                 FROM diagnostico_calidad dc
                 WHERE dc.AsignacionID = a.ID
                 ORDER BY dc.FechaRevision DESC LIMIT 1) AS ObservacionesCalidad,
                 
                -- Fecha de última revisión
                (SELECT DATE_FORMAT(dc.FechaRevision, '%d/%m/%Y %H:%i')
                 FROM diagnostico_calidad dc
                 WHERE dc.AsignacionID = a.ID
                 ORDER BY dc.FechaRevision DESC LIMIT 1) AS FechaRevisionCalidad
                " : "
                -- Si no existe la tabla, usar valores por defecto
                'Pendiente' AS EstadoCalidad,
                NULL AS ObservacionesCalidad,
                NULL AS FechaRevisionCalidad
                ") . "
                
            FROM asignaciones_mecanico a
            INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
            INNER JOIN usuarios u ON a.MecanicoID = u.UsuarioID
            WHERE 1=1";

    $params = [];
    $types = '';

    // Aplicar filtros
    if (!empty($filtros['estado'])) {
        $query .= " AND a.Estado = ?";
        $params[] = $filtros['estado'];
        $types .= 's';
    }

    if (!empty($filtros['estado_calidad'])) {
        if ($tablaCalidadExiste) {
            $query .= " AND EXISTS (
                SELECT 1 FROM diagnostico_calidad dc 
                WHERE dc.AsignacionID = a.ID 
                AND dc.EstadoCalidad = ?
                ORDER BY dc.FechaRevision DESC LIMIT 1
            )";
            $params[] = $filtros['estado_calidad'];
            $types .= 's';
        } else {
            // Si no existe la tabla y el filtro es "Pendiente", mostrar todas las asignaciones
            // Si es otro estado, no mostrar nada
            if ($filtros['estado_calidad'] !== 'Pendiente') {
                $query .= " AND 1=0"; // No mostrar resultados
            }
        }
    }

    if (!empty($filtros['mecanico_id'])) {
        $query .= " AND a.MecanicoID = ?";
        $params[] = intval($filtros['mecanico_id']);
        $types .= 'i';
    }

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

    $query .= " ORDER BY a.FechaAsignacion DESC";

    $asignaciones = [];

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
            $asignaciones[] = $row;
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
        'data' => $asignaciones,
        'total' => count($asignaciones)
    ];
}

/**
 * Obtiene todos los avances de una asignación (historial completo)
 * @param int $asignacion_id - ID de la asignación
 * @return array - Array con todos los avances
 */
function obtenerHistorialCompletoAvances($asignacion_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    $asignacion_id = mysqli_real_escape_string($conn, intval($asignacion_id));

    $query = "SELECT 
                am.ID,
                am.Descripcion,
                am.Estado,
                DATE_FORMAT(am.FechaAvance, '%d/%m/%Y %H:%i:%s') AS FechaAvance
            FROM avances_mecanico am 
            WHERE am.AsignacionID = '$asignacion_id' 
            ORDER BY am.FechaAvance ASC";

    $result = mysqli_query($conn, $query);
    $avances = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $avances[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    
    return [
        'status' => 'success',
        'data' => $avances
    ];
}

/**
 * Registra un diagnóstico de falla y revisión de calidad
 * @param int $asignacion_id - ID de la asignación
 * @param string $diagnostico_falla - Descripción del diagnóstico de falla
 * @param string $estado_calidad - Estado de calidad (Aprobado, Rechazado, En Revisión)
 * @param string $observaciones - Observaciones de calidad
 * @param int $usuario_revisor_id - ID del usuario que revisa (Jefe de Taller)
 * @return array - Resultado de la operación
 */
function registrarRevisionCalidad($asignacion_id, $diagnostico_falla, $estado_calidad, $observaciones, $usuario_revisor_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        // Verificar si existe la tabla diagnostico_calidad, si no, usar otra estrategia
        $checkTable = "SHOW TABLES LIKE 'diagnostico_calidad'";
        $resultCheck = mysqli_query($conn, $checkTable);
        $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

        if ($tablaExiste) {
            // Insertar en tabla diagnostico_calidad
            $query = "INSERT INTO diagnostico_calidad 
                     (AsignacionID, DiagnosticoFalla, EstadoCalidad, Observaciones, UsuarioRevisorID, FechaRevision)
                     VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                throw new Exception('Error preparando consulta: ' . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, 'isssi', $asignacion_id, $diagnostico_falla, $estado_calidad, $observaciones, $usuario_revisor_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error ejecutando consulta: ' . mysqli_stmt_error($stmt));
            }
            
            mysqli_stmt_close($stmt);
        } else {
            // Si la tabla no existe, intentar crearla
            $createTableQuery = "CREATE TABLE IF NOT EXISTS `diagnostico_calidad` (
                `ID` INT(11) NOT NULL AUTO_INCREMENT,
                `AsignacionID` INT(11) NOT NULL,
                `DiagnosticoFalla` TEXT NOT NULL,
                `EstadoCalidad` ENUM('Aprobado', 'Rechazado', 'En Revisión') NOT NULL DEFAULT 'En Revisión',
                `Observaciones` TEXT DEFAULT NULL,
                `UsuarioRevisorID` INT(11) NOT NULL,
                `FechaRevision` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `FechaActualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`ID`),
                KEY `AsignacionID` (`AsignacionID`),
                KEY `UsuarioRevisorID` (`UsuarioRevisorID`),
                KEY `EstadoCalidad` (`EstadoCalidad`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if (!mysqli_query($conn, $createTableQuery)) {
                error_log("Error al crear tabla diagnostico_calidad: " . mysqli_error($conn));
                // Continuar de todas formas
            } else {
                // Si se creó la tabla, intentar agregar el registro nuevamente
                $query = "INSERT INTO diagnostico_calidad 
                         (AsignacionID, DiagnosticoFalla, EstadoCalidad, Observaciones, UsuarioRevisorID, FechaRevision)
                         VALUES (?, ?, ?, ?, ?, NOW())";
                
                $stmt = mysqli_prepare($conn, $query);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'isssi', $asignacion_id, $diagnostico_falla, $estado_calidad, $observaciones, $usuario_revisor_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }

        mysqli_commit($conn);
        return ['status' => 'success', 'message' => 'Revisión de calidad registrada correctamente'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => 'error', 'message' => 'Error al registrar revisión: ' . $e->getMessage()];
    } finally {
        mysqli_close($conn);
    }
}

/**
 * Obtiene estadísticas de calidad
 * @param array $filtros - Filtros opcionales
 * @return array - Estadísticas de calidad
 */
function obtenerEstadisticasCalidad($filtros = []) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    // Verificar si existe la tabla
    $checkTable = "SHOW TABLES LIKE 'diagnostico_calidad'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    if (!$tablaExiste) {
        // Retornar estadísticas básicas sin tabla de calidad
        $query = "SELECT 
                    COUNT(*) as TotalAsignaciones,
                    SUM(CASE WHEN Estado = 'Completado' THEN 1 ELSE 0 END) as Completadas,
                    SUM(CASE WHEN Estado IN ('Ingresado', 'Asignado', 'En Proceso') THEN 1 ELSE 0 END) as EnProceso
                  FROM asignaciones_mecanico";
        
        $result = mysqli_query($conn, $query);
        $stats = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        mysqli_close($conn);
        
        return [
            'status' => 'success',
            'total_asignaciones' => $stats['TotalAsignaciones'] ?? 0,
            'completadas' => $stats['Completadas'] ?? 0,
            'en_proceso' => $stats['EnProceso'] ?? 0,
            'aprobadas' => 0,
            'rechazadas' => 0,
            'pendientes_revision' => $stats['Completadas'] ?? 0
        ];
    }

    $query = "SELECT 
                COUNT(DISTINCT dc.AsignacionID) as TotalRevisiones,
                SUM(CASE WHEN dc.EstadoCalidad = 'Aprobado' THEN 1 ELSE 0 END) as Aprobadas,
                SUM(CASE WHEN dc.EstadoCalidad = 'Rechazado' THEN 1 ELSE 0 END) as Rechazadas,
                SUM(CASE WHEN dc.EstadoCalidad = 'En Revisión' THEN 1 ELSE 0 END) as EnRevision
              FROM diagnostico_calidad dc
              INNER JOIN (
                  SELECT AsignacionID, MAX(FechaRevision) as UltimaRevision
                  FROM diagnostico_calidad
                  GROUP BY AsignacionID
              ) ultima ON dc.AsignacionID = ultima.AsignacionID 
                      AND dc.FechaRevision = ultima.UltimaRevision";

    $result = mysqli_query($conn, $query);
    $stats = mysqli_fetch_assoc($result);
    
    // Obtener asignaciones completadas sin revisión
    $queryPendientes = "SELECT COUNT(*) as total
                        FROM asignaciones_mecanico a
                        WHERE a.Estado = 'Completado'
                        AND NOT EXISTS (
                            SELECT 1 FROM diagnostico_calidad dc 
                            WHERE dc.AsignacionID = a.ID
                        )";
    
    $resultPendientes = mysqli_query($conn, $queryPendientes);
    $pendientes = mysqli_fetch_assoc($resultPendientes);
    
    mysqli_free_result($result);
    mysqli_free_result($resultPendientes);
    mysqli_close($conn);
    
    return [
        'status' => 'success',
        'total_revisiones' => $stats['TotalRevisiones'] ?? 0,
        'aprobadas' => $stats['Aprobadas'] ?? 0,
        'rechazadas' => $stats['Rechazadas'] ?? 0,
        'en_revision' => $stats['EnRevision'] ?? 0,
        'pendientes_revision' => $pendientes['total'] ?? 0
    ];
}

