<?php
require_once __DIR__ . '../../../../config/conexion.php';
require_once '../../../../pages/general/funciones_notificaciones.php';

function buscarVehiculos($filtros) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos en buscarVehiculos");
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Construir la consulta base con LEFT JOIN para obtener el mecánico asignado
    $query = "SELECT 
                iv.ID,
                iv.Placa,
                iv.TipoVehiculo,
                iv.Marca,
                iv.Modelo,
                iv.Anio,
                iv.ConductorNombre,
                DATE_FORMAT(iv.FechaIngreso, '%d/%m/%Y %H:%i') as FechaIngresoFormateada,
                iv.Estado,
                iv.Kilometraje,
                DATE_FORMAT(iv.FechaRegistro, '%d/%m/%Y %H:%i') as FechaRegistroFormateada,
                COALESCE(u.NombreUsuario, '') as MecanicoNombre
            FROM ingreso_vehiculos iv
            LEFT JOIN (
                SELECT a1.VehiculoID, a1.MecanicoID
                FROM asignaciones_mecanico a1
                INNER JOIN (
                    SELECT VehiculoID, MAX(FechaAsignacion) as MaxFecha
                    FROM asignaciones_mecanico
                    GROUP BY VehiculoID
                ) a2 ON a1.VehiculoID = a2.VehiculoID AND a1.FechaAsignacion = a2.MaxFecha
            ) ultima_asignacion ON iv.ID = ultima_asignacion.VehiculoID
            LEFT JOIN usuarios u ON ultima_asignacion.MecanicoID = u.UsuarioID
            WHERE 1=1";

    $params = [];

    // Aplicar filtros
    if (!empty($filtros['placa'])) {
        $placa = mysqli_real_escape_string($conn, $filtros['placa']);
        $query .= " AND Placa LIKE ?";
        $params[] = "%$placa%";
    }

    if (!empty($filtros['conductor'])) {
        $conductor = mysqli_real_escape_string($conn, $filtros['conductor']);
        $query .= " AND ConductorNombre LIKE ?";
        $params[] = "%$conductor%";
    }

    if (!empty($filtros['fecha'])) {
        $fecha = mysqli_real_escape_string($conn, $filtros['fecha']);
        $query .= " AND DATE(FechaIngreso) = ?";
        $params[] = $fecha;
    }

    // Ordenar por fecha de ingreso descendente
    $query .= " ORDER BY FechaIngreso DESC";

    // Preparar y ejecutar la consulta
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt && !empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    $result = [];
    if ($stmt && mysqli_stmt_execute($stmt)) {
        $resultado = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($resultado)) {
            $result[] = $row;
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error en consulta buscarVehiculos: " . mysqli_error($conn));
    }

    mysqli_close($conn);
    return $result;
}

function obtenerVehiculoPorID($id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos en obtenerVehiculoPorID");
        return null;
    }

    $query = "SELECT 
                ID,
                Placa,
                TipoVehiculo,
                Marca,
                Modelo,
                Anio,
                ConductorNombre,
                DATE_FORMAT(FechaIngreso, '%d/%m/%Y %H:%i') as FechaIngresoFormateada,
                Estado,
                Kilometraje,
                DATE_FORMAT(FechaRegistro, '%d/%m/%Y %H:%i') as FechaRegistroFormateada
            FROM ingreso_vehiculos 
            WHERE ID = ?";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $vehiculo = null;
    if ($row = mysqli_fetch_assoc($result)) {
        // Las fotos ya no están en ingreso_vehiculos, se obtienen de solicitudes_agendamiento
        $vehiculo = $row;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $vehiculo;
}

function obtenerTodosLosVehiculos() {
    return buscarVehiculos([]);
}

function actualizarVehiculo($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos en actualizarVehiculo");
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Verificar si la placa ya existe en otro vehículo
    if (!empty($datos['Placa'])) {
        $checkQuery = "SELECT ID FROM ingreso_vehiculos WHERE Placa = ? AND ID != ?";
        $stmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($stmt, 'si', $datos['Placa'], $datos['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'La placa ya existe en otro vehículo'];
        }
        mysqli_stmt_close($stmt);
    }

    // Construir la consulta de actualización
    $query = "UPDATE ingreso_vehiculos SET 
                Placa = ?,
                TipoVehiculo = ?,
                Marca = ?,
                Modelo = ?,
                Anio = ?,
                ConductorNombre = ?,
                Estado = ?
            WHERE ID = ?";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error en prepare: ' . mysqli_error($conn)];
    }

    // Bind parameters - USANDO LOS NOMBRES CORRECTOS
    // Parámetros: Placa(s), TipoVehiculo(s), Marca(s), Modelo(s), Anio(s), ConductorNombre(s), Estado(s), id(i)
    mysqli_stmt_bind_param(
        $stmt,
        'sssssssi',
        $datos['Placa'],
        $datos['TipoVehiculo'],
        $datos['Marca'],
        $datos['Modelo'],
        $datos['Anio'],
        $datos['ConductorNombre'],
        $datos['Estado'],
        $datos['id']
    );

    if (mysqli_stmt_execute($stmt)) {
        $response = ['status' => 'success', 'message' => 'Vehículo actualizado correctamente'];
    } else {
        $response = ['status' => 'error', 'message' => 'Error al actualizar: ' . mysqli_stmt_error($stmt)];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $response;
}

// Nuevas funciones para mecánicos
function obtenerMecanicosDisponibles() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    // Buscar mecánicos con ambos formatos posibles (con y sin tilde)
    $query = "SELECT UsuarioID, NombreUsuario, Correo 
              FROM usuarios 
              WHERE (Rol = 'Mecánico' OR Rol = 'Mecanico') AND Estado = 1 
              ORDER BY NombreUsuario";
    $result = mysqli_query($conn, $query);
    $mecanicos = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $mecanicos[] = $row;
        }
    }

    mysqli_close($conn);
    return $mecanicos;
}

function asignarMecanico($vehiculo_id, $mecanico_id, $observaciones) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        // 1. Obtener información del vehículo antes de actualizar
        $selectVehiculo = "SELECT Placa, Marca, Modelo, TipoVehiculo FROM ingreso_vehiculos WHERE ID = ?";
        $stmt_select = mysqli_prepare($conn, $selectVehiculo);
        mysqli_stmt_bind_param($stmt_select, 'i', $vehiculo_id);
        mysqli_stmt_execute($stmt_select);
        $result_vehiculo = mysqli_stmt_get_result($stmt_select);
        $vehiculo = mysqli_fetch_assoc($result_vehiculo);
        mysqli_stmt_close($stmt_select);

        // 2. Actualizar estado del vehículo
        $updateVehiculo = "UPDATE ingreso_vehiculos SET Estado = 'Asignado' WHERE ID = ?";
        $stmt = mysqli_prepare($conn, $updateVehiculo);
        mysqli_stmt_bind_param($stmt, 'i', $vehiculo_id);
        mysqli_stmt_execute($stmt);

        // 3. Crear asignación
        $insertAsignacion = "INSERT INTO asignaciones_mecanico 
                            (VehiculoID, MecanicoID, Observaciones) 
                            VALUES (?, ?, ?)";
        $stmt2 = mysqli_prepare($conn, $insertAsignacion);
        mysqli_stmt_bind_param($stmt2, 'iis', $vehiculo_id, $mecanico_id, $observaciones);
        mysqli_stmt_execute($stmt2);
        $asignacion_id = mysqli_insert_id($conn);

        mysqli_commit($conn);
        
        // 4. Notificar al mecánico después de la asignación exitosa
        if ($vehiculo && $asignacion_id) {
            notificarAsignacionMecanico($mecanico_id, $vehiculo, $observaciones, $asignacion_id);
        }
        
        return ['status' => 'success', 'message' => 'Mecánico asignado correctamente'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => 'error', 'message' => 'Error al asignar: ' . $e->getMessage()];
    } finally {
        mysqli_close($conn);
    }
}

function obtenerAsignacionActiva($vehiculo_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return null;
    }

    // Verificar si la columna MotivoPausa existe
    $checkColumn = "SELECT COUNT(*) as existe 
                   FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'asignaciones_mecanico' 
                   AND COLUMN_NAME = 'MotivoPausa'";
    $resultCheck = mysqli_query($conn, $checkColumn);
    $columnExists = false;
    if ($resultCheck) {
        $row = mysqli_fetch_assoc($resultCheck);
        $columnExists = ($row['existe'] > 0);
    }

    // Verificar si la tabla solicitudes_repuestos existe
    $checkTable = "SELECT COUNT(*) as existe 
                  FROM information_schema.TABLES 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'solicitudes_repuestos'";
    $resultTable = mysqli_query($conn, $checkTable);
    $tableExists = false;
    if ($resultTable) {
        $row = mysqli_fetch_assoc($resultTable);
        $tableExists = ($row['existe'] > 0);
    }

    $motivoPausaField = $columnExists ? "a.MotivoPausa," : "NULL as MotivoPausa,";
    $solicitudesSubquery = $tableExists 
        ? "(SELECT COUNT(*) 
            FROM solicitudes_repuestos sr 
            WHERE sr.AsignacionID = a.ID 
            AND sr.Estado IN ('Pendiente', 'Aprobada'))"
        : "0";

    // Construir la lógica del Estado según si existe la columna MotivoPausa
    $estadoLogic = $columnExists 
        ? "COALESCE(NULLIF(a.Estado, ''), CASE WHEN a.MotivoPausa IS NOT NULL AND a.MotivoPausa != '' THEN 'En Pausa' ELSE 'Asignado' END)"
        : "COALESCE(NULLIF(a.Estado, ''), 'Asignado')";

    $query = "SELECT 
                a.ID,
                a.VehiculoID,
                a.MecanicoID,
                u.NombreUsuario as MecanicoNombre,
                a.Observaciones,
                $estadoLogic AS Estado,
                $motivoPausaField
                $solicitudesSubquery as SolicitudesPendientes,
                DATE_FORMAT(a.FechaAsignacion, '%d/%m/%Y %H:%i') as FechaAsignacion
            FROM asignaciones_mecanico a
            INNER JOIN usuarios u ON a.MecanicoID = u.UsuarioID
            WHERE a.VehiculoID = ? 
            ORDER BY a.FechaAsignacion DESC LIMIT 1";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $vehiculo_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $asignacion = mysqli_fetch_assoc($result);

    // Si hay asignación y la tabla de solicitudes existe, obtener detalles de repuestos solicitados
    if ($asignacion && $tableExists && isset($asignacion['ID'])) {
        $asignacion_id = $asignacion['ID'];
        
        // Verificar si la tabla repuestos existe
        $checkRepuestos = "SELECT COUNT(*) as existe 
                          FROM information_schema.TABLES 
                          WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'repuestos'";
        $resultRepuestos = mysqli_query($conn, $checkRepuestos);
        $repuestosTableExists = false;
        if ($resultRepuestos) {
            $row = mysqli_fetch_assoc($resultRepuestos);
            $repuestosTableExists = ($row['existe'] > 0);
        }
        
        if ($repuestosTableExists) {
            $queryRepuestos = "SELECT 
                                sr.Cantidad,
                                r.Nombre as RepuestoNombre,
                                sr.Urgencia,
                                sr.Estado as EstadoSolicitud
                              FROM solicitudes_repuestos sr
                              INNER JOIN repuestos r ON sr.RepuestoID = r.ID
                              WHERE sr.AsignacionID = ? 
                              AND sr.Estado IN ('Pendiente', 'Aprobada')
                              ORDER BY sr.Urgencia DESC, sr.FechaSolicitud DESC";
            
            $stmtRepuestos = mysqli_prepare($conn, $queryRepuestos);
            if ($stmtRepuestos) {
                mysqli_stmt_bind_param($stmtRepuestos, 'i', $asignacion_id);
                mysqli_stmt_execute($stmtRepuestos);
                $resultRepuestos = mysqli_stmt_get_result($stmtRepuestos);
                
                $repuestosSolicitados = [];
                while ($row = mysqli_fetch_assoc($resultRepuestos)) {
                    $repuestosSolicitados[] = $row;
                }
                
                $asignacion['RepuestosSolicitados'] = $repuestosSolicitados;
                mysqli_stmt_close($stmtRepuestos);
            }
        } else {
            $asignacion['RepuestosSolicitados'] = [];
        }
    } else {
        if ($asignacion) {
            $asignacion['RepuestosSolicitados'] = [];
        }
    }

    mysqli_close($conn);
    return $asignacion;
}

/**
 * Notifica al mecánico sobre una nueva tarea asignada
 */
function notificarAsignacionMecanico($mecanico_id, $vehiculo, $observaciones, $asignacion_id) {
    if (empty($mecanico_id) || empty($vehiculo)) {
        error_log("Error: Datos incompletos para notificar asignación al mecánico");
        return false;
    }
    
    // Crear mensaje de notificación
    $titulo = "Nueva Tarea Asignada";
    $mensaje = "Se te ha asignado el vehículo {$vehiculo['Placa']} - {$vehiculo['Marca']} {$vehiculo['Modelo']}";
    if (!empty($observaciones)) {
        $mensaje .= ". Observaciones: " . substr($observaciones, 0, 100);
        if (strlen($observaciones) > 100) {
            $mensaje .= "...";
        }
    }
    $modulo = "tareas";
    $enlace = "tareas.php";
    
    // Crear notificación para el mecánico específico
    $resultado = crearNotificacion([$mecanico_id], $titulo, $mensaje, $modulo, $enlace);
    
    if ($resultado) {
        error_log("Notificación enviada al mecánico ID: {$mecanico_id} para vehículo {$vehiculo['Placa']}");
    } else {
        error_log("Error al enviar notificación al mecánico ID: {$mecanico_id}");
    }
    
    return $resultado;
}

function obtenerAvancesMecanico($asignacion_id) {
    if (!$asignacion_id) return [];

    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    // Verificar si existe la columna Fotos
    $checkColumn = "SHOW COLUMNS FROM avances_mecanico LIKE 'Fotos'";
    $resultCheck = mysqli_query($conn, $checkColumn);
    $columnaFotosExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    $query = "SELECT 
                ID,
                Descripcion,
                Estado,
                " . ($columnaFotosExiste ? "Fotos," : "NULL AS Fotos,") . "
                DATE_FORMAT(FechaAvance, '%d/%m/%Y %H:%i') as FechaAvance,
                DATE_FORMAT(FechaAvance, '%Y-%m-%d %H:%i:%s') as FechaAvanceRaw
            FROM avances_mecanico 
            WHERE AsignacionID = ? 
            ORDER BY FechaAvance DESC";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $asignacion_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $avances = [];

    while ($row = mysqli_fetch_assoc($result)) {
        // Decodificar JSON de fotos si existe
        if (!empty($row['Fotos']) && $row['Fotos'] !== 'NULL' && $row['Fotos'] !== null) {
            $fotosDecoded = json_decode($row['Fotos'], true);
            $row['Fotos'] = is_array($fotosDecoded) ? $fotosDecoded : [];
        } else {
            $row['Fotos'] = [];
        }
        $avances[] = $row;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $avances;
}
?>