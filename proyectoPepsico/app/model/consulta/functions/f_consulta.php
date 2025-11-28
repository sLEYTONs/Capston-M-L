<?php
require_once __DIR__ . '../../../../config/conexion.php';
require_once '../../../../pages/general/funciones_notificaciones.php';

function buscarVehiculos($filtros) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos en buscarVehiculos");
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Construir la consulta para mostrar TODOS los vehículos con su estado real
    // Determina si está en circulación, en mecánico, con solicitud, etc.
            $query = "SELECT 
                iv.ID,
                iv.Placa,
                iv.TipoVehiculo,
                iv.Marca,
                iv.Modelo,
                iv.Anio,
                iv.ConductorNombre,
                DATE_FORMAT(iv.FechaIngreso, '%d/%m/%Y %H:%i') as FechaIngresoFormateada,
                DATE_FORMAT(COALESCE(sa.FechaCreacion, sa.FechaActualizacion, iv.FechaRegistro), '%d/%m/%Y %H:%i') as FechaSolicitudFormateada,
                iv.Estado as EstadoVehiculo,
                iv.Kilometraje,
                DATE_FORMAT(iv.FechaRegistro, '%d/%m/%Y %H:%i') as FechaRegistroFormateada,
                COALESCE(u.NombreUsuario, '') as MecanicoNombre,
                ultima_asignacion.EstadoAsignacion,
                sa.ID as SolicitudID,
                sa.Proposito,
                sa.Observaciones as ObservacionesSolicitud,
                sa.Estado as EstadoSolicitud,
                DATE_FORMAT(a.Fecha, '%d/%m/%Y') as FechaAgendaFormateada,
                a.Fecha as FechaAgenda,
                TIME_FORMAT(a.HoraInicio, '%H:%i') as HoraInicioFormateada,
                a.HoraInicio as HoraInicioAgenda,
                TIME_FORMAT(a.HoraFin, '%H:%i') as HoraFinFormateada,
                a.HoraFin as HoraFinAgenda,
                sup.NombreUsuario as SupervisorNombre,
                chofer.NombreUsuario as ChoferNombre,
                DATE_FORMAT(sa.FechaActualizacion, '%d/%m/%Y %H:%i') as FechaAprobacionFormateada,
                CASE 
                    -- Si tiene asignación activa con mecánico, está en mecánico
                    WHEN ultima_asignacion.VehiculoID IS NOT NULL AND ultima_asignacion.EstadoAsignacion IN ('Asignado', 'En Proceso', 'En Revisión', 'En Pausa') THEN 
                        CASE ultima_asignacion.EstadoAsignacion
                            WHEN 'Asignado' THEN 'Asignado a Mecánico'
                            WHEN 'En Proceso' THEN 'En Reparación'
                            WHEN 'En Revisión' THEN 'En Revisión'
                            WHEN 'En Pausa' THEN 'En Pausa'
                            ELSE 'En Taller'
                        END
                    -- Si está completado, está listo para salir
                    WHEN iv.Estado = 'Completado' OR ultima_asignacion.EstadoAsignacion = 'Completado' THEN 'Listo para Salir'
                    -- Si está ingresado pero sin asignación, está en espera
                    WHEN iv.Estado = 'Ingresado' THEN 'En Taller - Espera'
                    WHEN iv.Estado = 'Asignado' THEN 'En Taller - Asignado'
                    -- Si tiene solicitud pendiente
                    WHEN sa.Estado = 'Pendiente' THEN 'Solicitud Pendiente'
                    WHEN sa.Estado = 'Aprobada' THEN 'Solicitud Aprobada'
                    WHEN sa.Estado = 'Atrasado' THEN 'Solicitud Atrasada'
                    WHEN sa.Estado = 'No llegó' THEN 'No Llegó'
                    -- Si está disponible, está en circulación
                    WHEN iv.Estado = 'Disponible' THEN 'En Circulación'
                    -- Si no tiene estado de taller ni asignación, probablemente está en circulación
                    WHEN iv.Estado NOT IN ('Ingresado', 'Asignado', 'Completado') AND ultima_asignacion.VehiculoID IS NULL THEN 'En Circulación'
                    -- Estado por defecto
                    ELSE COALESCE(iv.Estado, 'Sin Estado')
                END as Estado,
                CASE 
                    WHEN iv.Estado = 'Disponible' OR (iv.Estado NOT IN ('Ingresado', 'Asignado', 'Completado') AND ultima_asignacion.VehiculoID IS NULL) THEN 'circulacion'
                    WHEN iv.Estado = 'Completado' OR ultima_asignacion.EstadoAsignacion = 'Completado' THEN 'listo'
                    WHEN ultima_asignacion.VehiculoID IS NOT NULL THEN 'mecanico'
                    WHEN iv.Estado IN ('Ingresado', 'Asignado') THEN 'taller'
                    WHEN sa.ID IS NOT NULL THEN 'solicitud'
                    ELSE 'disponible'
                END as TipoEstado
            FROM ingreso_vehiculos iv
            LEFT JOIN (
                SELECT sa1.*
                FROM solicitudes_agendamiento sa1
                INNER JOIN (
                    SELECT Placa, MAX(FechaCreacion) as MaxFecha
                    FROM solicitudes_agendamiento
                    WHERE Estado IN ('Aprobada', 'Atrasado', 'No llegó', 'Pendiente')
                    GROUP BY Placa
                ) sa2 ON sa1.Placa = sa2.Placa AND sa1.FechaCreacion = sa2.MaxFecha
                WHERE sa1.Estado IN ('Aprobada', 'Atrasado', 'No llegó', 'Pendiente')
            ) sa ON sa.Placa COLLATE utf8mb4_unicode_ci = iv.Placa COLLATE utf8mb4_unicode_ci
            LEFT JOIN agenda_taller a ON sa.AgendaID = a.ID
            LEFT JOIN usuarios chofer ON sa.ChoferID = chofer.UsuarioID
            LEFT JOIN usuarios sup ON sa.SupervisorID = sup.UsuarioID
            LEFT JOIN (
                SELECT a1.VehiculoID, a1.MecanicoID, a1.FechaAsignacion, a1.Estado as EstadoAsignacion, iv2.Placa as VehiculoPlaca
                FROM asignaciones_mecanico a1
                INNER JOIN ingreso_vehiculos iv2 ON a1.VehiculoID = iv2.ID
                INNER JOIN (
                    SELECT a3.VehiculoID, MAX(a3.FechaAsignacion) as MaxFecha
                    FROM asignaciones_mecanico a3
                    WHERE a3.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'En Pausa', 'Completado')
                    GROUP BY a3.VehiculoID
                ) a2 ON a1.VehiculoID = a2.VehiculoID AND a1.FechaAsignacion = a2.MaxFecha
                WHERE a1.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'En Pausa', 'Completado')
            ) ultima_asignacion ON iv.ID = ultima_asignacion.VehiculoID
            LEFT JOIN usuarios u ON ultima_asignacion.MecanicoID = u.UsuarioID
            WHERE 1=1";

    $params = [];

    // Aplicar filtros
    if (!empty($filtros['placa'])) {
        $placa = mysqli_real_escape_string($conn, $filtros['placa']);
        $query .= " AND iv.Placa LIKE ?";
        $params[] = "%$placa%";
    }

    if (!empty($filtros['conductor'])) {
        $conductor = mysqli_real_escape_string($conn, $filtros['conductor']);
        $query .= " AND iv.ConductorNombre LIKE ?";
        $params[] = "%$conductor%";
    }

    if (!empty($filtros['fecha'])) {
        $fecha = mysqli_real_escape_string($conn, $filtros['fecha']);
        $query .= " AND (DATE(iv.FechaIngreso) = ? OR DATE(iv.FechaRegistro) = ? OR DATE(a.Fecha) = ?)";
        $params[] = $fecha;
        $params[] = $fecha;
        $params[] = $fecha;
    }

    // Ordenar por fecha de registro o fecha de ingreso descendente
    $query .= " ORDER BY COALESCE(iv.FechaIngreso, iv.FechaRegistro) DESC";

    // Preparar y ejecutar la consulta
    $result = [];
    
    if (!empty($params)) {
        // Si hay parámetros, usar consulta preparada
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            $types = str_repeat('s', count($params));
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            
            if (mysqli_stmt_execute($stmt)) {
                $resultado = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($resultado)) {
                    $result[] = $row;
                }
            } else {
                error_log("Error ejecutando consulta buscarVehiculos: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Error preparando consulta buscarVehiculos: " . mysqli_error($conn));
        }
    } else {
        // Si no hay parámetros, ejecutar consulta directa
        $resultado = mysqli_query($conn, $query);
        if ($resultado) {
            while ($row = mysqli_fetch_assoc($resultado)) {
                $result[] = $row;
            }
        } else {
            error_log("Error en consulta buscarVehiculos: " . mysqli_error($conn));
        }
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

    // Primero intentar obtener desde solicitudes aprobadas (como en buscarVehiculos)
    // Si no hay solicitud aprobada, obtener solo del vehículo
    $query = "SELECT 
                COALESCE(iv.ID, sa.ID) as ID,
                COALESCE(iv.Placa, sa.Placa) as Placa,
                COALESCE(iv.TipoVehiculo, sa.TipoVehiculo) as TipoVehiculo,
                COALESCE(iv.Marca, sa.Marca) as Marca,
                COALESCE(iv.Modelo, sa.Modelo) as Modelo,
                COALESCE(iv.Anio, sa.Anio) as Anio,
                COALESCE(iv.ConductorNombre, sa.ConductorNombre) as ConductorNombre,
                DATE_FORMAT(iv.FechaIngreso, '%d/%m/%Y %H:%i') as FechaIngresoFormateada,
                DATE_FORMAT(COALESCE(sa.FechaCreacion, sa.FechaActualizacion), '%d/%m/%Y %H:%i') as FechaSolicitudFormateada,
                CASE 
                    WHEN sa.Estado = 'No llegó' THEN 'No llegó'
                    WHEN sa.Estado = 'Atrasado' THEN 'Atrasado'
                    ELSE COALESCE(iv.Estado, 'Pendiente Ingreso')
                END as Estado,
                iv.Kilometraje,
                DATE_FORMAT(iv.FechaRegistro, '%d/%m/%Y %H:%i') as FechaRegistroFormateada,
                COALESCE(u.NombreUsuario, '') as MecanicoNombre,
                sa.ID as SolicitudID,
                sa.Proposito,
                sa.Observaciones as ObservacionesSolicitud,
                sa.Estado as EstadoSolicitud,
                DATE_FORMAT(a.Fecha, '%d/%m/%Y') as FechaAgendaFormateada,
                a.Fecha as FechaAgenda,
                TIME_FORMAT(a.HoraInicio, '%H:%i') as HoraInicioFormateada,
                a.HoraInicio as HoraInicioAgenda,
                TIME_FORMAT(a.HoraFin, '%H:%i') as HoraFinFormateada,
                a.HoraFin as HoraFinAgenda,
                sup.NombreUsuario as SupervisorNombre,
                chofer.NombreUsuario as ChoferNombre,
                DATE_FORMAT(sa.FechaActualizacion, '%d/%m/%Y %H:%i') as FechaAprobacionFormateada,
                CASE 
                    WHEN iv.ID IS NOT NULL THEN 1 
                    ELSE 0 
                END as VehiculoIngresado
            FROM ingreso_vehiculos iv
            LEFT JOIN solicitudes_agendamiento sa ON sa.Placa COLLATE utf8mb4_unicode_ci = iv.Placa COLLATE utf8mb4_unicode_ci 
                AND sa.Estado IN ('Aprobada', 'Atrasado', 'No llegó')
            LEFT JOIN agenda_taller a ON sa.AgendaID = a.ID
            LEFT JOIN usuarios chofer ON sa.ChoferID = chofer.UsuarioID
            LEFT JOIN usuarios sup ON sa.SupervisorID = sup.UsuarioID
            LEFT JOIN (
                SELECT a1.VehiculoID, a1.MecanicoID, a1.FechaAsignacion, a1.Estado as EstadoAsignacion, iv2.Placa as VehiculoPlaca
                FROM asignaciones_mecanico a1
                INNER JOIN ingreso_vehiculos iv2 ON a1.VehiculoID = iv2.ID
                INNER JOIN (
                    SELECT a3.VehiculoID, MAX(a3.FechaAsignacion) as MaxFecha
                    FROM asignaciones_mecanico a3
                    GROUP BY a3.VehiculoID
                ) a2 ON a1.VehiculoID = a2.VehiculoID AND a1.FechaAsignacion = a2.MaxFecha
            ) ultima_asignacion ON iv.ID = ultima_asignacion.VehiculoID
            LEFT JOIN usuarios u ON ultima_asignacion.MecanicoID = u.UsuarioID
            WHERE iv.ID = ?
            ORDER BY sa.FechaCreacion DESC
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Error preparando consulta en obtenerVehiculoPorID: " . mysqli_error($conn));
        mysqli_close($conn);
        return null;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Error ejecutando consulta en obtenerVehiculoPorID: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return null;
    }
    
    $result = mysqli_stmt_get_result($stmt);

    $vehiculo = null;
    if ($row = mysqli_fetch_assoc($result)) {
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
        // 0. Verificar el estado de la solicitud asociada
        $checkSolicitud = "SELECT sa.Estado 
                          FROM ingreso_vehiculos iv
                          LEFT JOIN solicitudes_agendamiento sa ON sa.Placa COLLATE utf8mb4_unicode_ci = iv.Placa COLLATE utf8mb4_unicode_ci
                          WHERE iv.ID = ? 
                          AND sa.Estado IN ('Aprobada', 'Atrasado', 'No llegó')
                          ORDER BY sa.FechaCreacion DESC
                          LIMIT 1";
        $stmt_check = mysqli_prepare($conn, $checkSolicitud);
        mysqli_stmt_bind_param($stmt_check, 'i', $vehiculo_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $solicitud = mysqli_fetch_assoc($result_check);
        mysqli_stmt_close($stmt_check);
        
        // Si la solicitud está marcada como "No llegó" o "Atrasado", no se puede asignar mecánico (proceso cancelado)
        if ($solicitud && ($solicitud['Estado'] === 'No llegó' || $solicitud['Estado'] === 'Atrasado')) {
            mysqli_rollback($conn);
            mysqli_close($conn);
            $mensaje = $solicitud['Estado'] === 'No llegó' 
                ? 'No se puede asignar mecánico. El vehículo no llegó a tiempo y la solicitud ha sido cerrada.'
                : 'No se puede asignar mecánico. El vehículo llegó atrasado y el proceso ha sido cancelado.';
            return ['status' => 'error', 'message' => $mensaje];
        }
        
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