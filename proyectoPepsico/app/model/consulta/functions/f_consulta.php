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

/**
 * Marca todos los vehículos como retirados excepto los especificados
 * @param array $placasExcluidas Array de placas que NO se marcarán como retiradas
 * @return array Resultado de la operación
 */
function marcarVehiculosRetirados($placasExcluidas = ['KSZJ43', 'WLVY22']) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos en marcarVehiculosRetirados");
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    try {
        // Verificar si existe la columna Estado en ingreso_vehiculos
        $checkEstado = "SHOW COLUMNS FROM ingreso_vehiculos WHERE Field = 'Estado'";
        $resultEstado = $conn->query($checkEstado);
        $tieneEstado = ($resultEstado && $resultEstado->num_rows > 0);

        if (!$tieneEstado) {
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'La tabla ingreso_vehiculos no tiene columna Estado'];
        }

        // Verificar si existe la columna FechaSalida
        $checkFechaSalida = "SHOW COLUMNS FROM ingreso_vehiculos WHERE Field IN ('FechaSalida', 'fechasalida')";
        $resultFechaSalida = $conn->query($checkFechaSalida);
        $tieneFechaSalida = ($resultFechaSalida && $resultFechaSalida->num_rows > 0);
        
        // Obtener el nombre exacto de la columna si existe
        $nombreColumnaFechaSalida = null;
        if ($tieneFechaSalida) {
            $row = $resultFechaSalida->fetch_assoc();
            $nombreColumnaFechaSalida = $row['Field'];
        }

        // Construir la lista de placas excluidas para la consulta
        $placasExcluidasEscapadas = array_map(function($placa) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $placa) . "'";
        }, $placasExcluidas);
        
        $placasExcluidasStr = implode(', ', $placasExcluidasEscapadas);

        // Construir la consulta UPDATE dinámicamente
        $camposUpdate = ["Estado = 'Finalizado'"];
        
        if ($nombreColumnaFechaSalida) {
            $camposUpdate[] = "`{$nombreColumnaFechaSalida}` = NOW()";
        }

        // Actualizar todos los vehículos excepto los excluidos
        $sql = "UPDATE ingreso_vehiculos 
                SET " . implode(', ', $camposUpdate) . "
                WHERE Placa NOT IN ($placasExcluidasStr)
                AND Estado NOT IN ('Finalizado', 'Retirado')";

        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            error_log("Error en marcarVehiculosRetirados: " . mysqli_error($conn));
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'Error al actualizar vehículos: ' . mysqli_error($conn)];
        }

        $totalActualizados = mysqli_affected_rows($conn);
        
        // Procesar duplicados de placas
        $resultadoDuplicados = procesarPlacasDuplicadas($conn);
        $mensajeDuplicados = $resultadoDuplicados['mensaje'];
        $totalDuplicados = $resultadoDuplicados['total'];
        
        // Procesar conductores con múltiples vehículos
        $resultadoConductores = procesarConductoresMultiplesVehiculos($conn);
        $mensajeConductores = $resultadoConductores['mensaje'];
        $totalConductores = $resultadoConductores['total'];
        
        // Procesar usuarios con múltiples solicitudes de agendamiento
        $resultadoSolicitudes = procesarSolicitudesMultiplesPorUsuario($conn);
        $mensajeSolicitudes = $resultadoSolicitudes['mensaje'];
        $totalSolicitudes = $resultadoSolicitudes['total'];
        
        // Marcar KSZJ43 como "No llegó"
        $marcadoNoLlego = false;
        $mensajeNoLlego = '';
        
        if (in_array('KSZJ43', $placasExcluidas)) {
            // Buscar la solicitud de agendamiento para KSZJ43
            $sqlSolicitud = "SELECT ID FROM solicitudes_agendamiento 
                            WHERE Placa = 'KSZJ43' 
                            AND Estado IN ('Aprobada', 'Atrasado')
                            ORDER BY FechaCreacion DESC 
                            LIMIT 1";
            $resultSolicitud = mysqli_query($conn, $sqlSolicitud);
            
            if ($resultSolicitud && mysqli_num_rows($resultSolicitud) > 0) {
                $solicitud = mysqli_fetch_assoc($resultSolicitud);
                $solicitud_id = $solicitud['ID'];
                
                // Asegurar que el estado existe en el ENUM
                $checkEnum = "SHOW COLUMNS FROM solicitudes_agendamiento WHERE Field = 'Estado'";
                $resultEnum = mysqli_query($conn, $checkEnum);
                if ($resultEnum && mysqli_num_rows($resultEnum) > 0) {
                    $rowEnum = mysqli_fetch_assoc($resultEnum);
                    $type = $rowEnum['Type'];
                    
                    if (strpos($type, 'No llegó') === false && strpos($type, 'No llego') === false) {
                        $sqlModify = "ALTER TABLE solicitudes_agendamiento 
                                     MODIFY COLUMN Estado ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Cancelada', 'Atrasado', 'No llegó') NOT NULL DEFAULT 'Pendiente'";
                        mysqli_query($conn, $sqlModify);
                    }
                }
                
                // Marcar la solicitud como "No llegó"
                $sqlUpdateSolicitud = "UPDATE solicitudes_agendamiento 
                                      SET Estado = 'No llegó', 
                                          MotivoRechazo = CONCAT(IFNULL(MotivoRechazo, ''), ' | Vehículo marcado como no llegó desde botón mágico.'),
                                          FechaActualizacion = NOW()
                                      WHERE ID = $solicitud_id 
                                      AND Estado IN ('Aprobada', 'Atrasado')";
                
                if (mysqli_query($conn, $sqlUpdateSolicitud)) {
                    $marcadoNoLlego = true;
                    $mensajeNoLlego = " Vehículo KSZJ43 marcado como 'No llegó'.";
                    
                    // Cancelar asignaciones de mecánico activas para este vehículo
                    $placa = 'KSZJ43';
                    $sqlCancelarAsignaciones = "UPDATE asignaciones_mecanico am
                                              INNER JOIN ingreso_vehiculos iv ON am.VehiculoID = iv.ID
                                              SET am.Estado = 'Cancelado'
                                              WHERE iv.Placa = '$placa'
                                              AND am.Estado IN ('Asignado', 'En Proceso', 'En Revisión')";
                    mysqli_query($conn, $sqlCancelarAsignaciones);
                    
                    // Actualizar estado del vehículo si está ingresado
                    $sqlUpdateVehiculo = "UPDATE ingreso_vehiculos 
                                         SET Estado = 'Cancelado'
                                         WHERE Placa = '$placa'
                                         AND Estado IN ('Ingresado', 'Asignado', 'En Proceso')";
                    mysqli_query($conn, $sqlUpdateVehiculo);
                } else {
                    $mensajeNoLlego = " Error al marcar KSZJ43 como 'No llegó': " . mysqli_error($conn);
                }
            } else {
                $mensajeNoLlego = " No se encontró solicitud aprobada para KSZJ43.";
            }
        }
        
        // Marcar WLVY22 como "No llegó"
        if (in_array('WLVY22', $placasExcluidas)) {
            // Buscar la solicitud de agendamiento para WLVY22
            $sqlSolicitudWLVY = "SELECT ID FROM solicitudes_agendamiento 
                            WHERE Placa = 'WLVY22' 
                            AND Estado IN ('Aprobada', 'Atrasado', 'Pendiente')
                            ORDER BY FechaCreacion DESC 
                            LIMIT 1";
            $resultSolicitudWLVY = mysqli_query($conn, $sqlSolicitudWLVY);
            
            if ($resultSolicitudWLVY && mysqli_num_rows($resultSolicitudWLVY) > 0) {
                $solicitudWLVY = mysqli_fetch_assoc($resultSolicitudWLVY);
                $solicitud_id_wlvy = $solicitudWLVY['ID'];
                
                // Asegurar que el estado existe en el ENUM
                $checkEnumWLVY = "SHOW COLUMNS FROM solicitudes_agendamiento WHERE Field = 'Estado'";
                $resultEnumWLVY = mysqli_query($conn, $checkEnumWLVY);
                if ($resultEnumWLVY && mysqli_num_rows($resultEnumWLVY) > 0) {
                    $rowEnumWLVY = mysqli_fetch_assoc($resultEnumWLVY);
                    $typeWLVY = $rowEnumWLVY['Type'];
                    
                    if (strpos($typeWLVY, 'No llegó') === false && strpos($typeWLVY, 'No llego') === false) {
                        $sqlModifyWLVY = "ALTER TABLE solicitudes_agendamiento 
                                         MODIFY COLUMN Estado ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Cancelada', 'Atrasado', 'No llegó') NOT NULL DEFAULT 'Pendiente'";
                        mysqli_query($conn, $sqlModifyWLVY);
                    }
                }
                
                // Marcar la solicitud como "No llegó"
                $sqlUpdateSolicitudWLVY = "UPDATE solicitudes_agendamiento 
                                      SET Estado = 'No llegó', 
                                          MotivoRechazo = CONCAT(IFNULL(MotivoRechazo, ''), ' | Vehículo marcado como no llegó desde botón mágico.'),
                                          FechaActualizacion = NOW()
                                      WHERE ID = $solicitud_id_wlvy 
                                      AND Estado IN ('Aprobada', 'Atrasado', 'Pendiente')";
                
                if (mysqli_query($conn, $sqlUpdateSolicitudWLVY)) {
                    $mensajeNoLlego .= " Vehículo WLVY22 marcado como 'No llegó'.";
                    
                    // Cancelar asignaciones de mecánico activas para este vehículo
                    $placaWLVY = 'WLVY22';
                    $sqlCancelarAsignacionesWLVY = "UPDATE asignaciones_mecanico am
                                              INNER JOIN ingreso_vehiculos iv ON am.VehiculoID = iv.ID
                                              SET am.Estado = 'Cancelado'
                                              WHERE iv.Placa = '$placaWLVY'
                                              AND am.Estado IN ('Asignado', 'En Proceso', 'En Revisión')";
                    mysqli_query($conn, $sqlCancelarAsignacionesWLVY);
                    
                    // Actualizar estado del vehículo si está ingresado
                    $sqlUpdateVehiculoWLVY = "UPDATE ingreso_vehiculos 
                                         SET Estado = 'Cancelado'
                                         WHERE Placa = '$placaWLVY'
                                         AND Estado IN ('Ingresado', 'Asignado', 'En Proceso')";
                    mysqli_query($conn, $sqlUpdateVehiculoWLVY);
                } else {
                    $mensajeNoLlego .= " Error al marcar WLVY22 como 'No llegó': " . mysqli_error($conn);
                }
            } else {
                $mensajeNoLlego .= " No se encontró solicitud aprobada/pendiente para WLVY22.";
            }
        }
        
        // Superpoder: Marcar TODOS los vehículos como fuera del taller (sin excepciones)
        $mensajeSuperpoder = '';
        
        // Verificar si existe la columna FechaSalida
        $checkFechaSalida = "SHOW COLUMNS FROM ingreso_vehiculos WHERE Field IN ('FechaSalida', 'fechasalida')";
        $resultFechaSalida = mysqli_query($conn, $checkFechaSalida);
        $tieneFechaSalida = ($resultFechaSalida && mysqli_num_rows($resultFechaSalida) > 0);
        $nombreColumnaFechaSalida = null;
        if ($tieneFechaSalida) {
            $rowFechaSalida = mysqli_fetch_assoc($resultFechaSalida);
            $nombreColumnaFechaSalida = $rowFechaSalida['Field'];
        }
        
        // Marcar TODOS los vehículos como fuera del taller (Finalizado + FechaSalida)
        $sqlFueraTaller = "UPDATE ingreso_vehiculos 
                          SET Estado = 'Finalizado'";
        
        if ($nombreColumnaFechaSalida) {
            $sqlFueraTaller .= ", `{$nombreColumnaFechaSalida}` = NOW()";
        }
        
        $sqlFueraTaller .= " WHERE Estado NOT IN ('Finalizado', 'Cancelado')";
        
        if (mysqli_query($conn, $sqlFueraTaller)) {
            $totalFueraTaller = mysqli_affected_rows($conn);
            $mensajeSuperpoder .= " $totalFueraTaller vehículos marcados como fuera del taller.";
        } else {
            $mensajeSuperpoder .= " Error al marcar vehículos fuera del taller: " . mysqli_error($conn);
        }
        
        // Cancelar TODAS las asignaciones de mecánico activas
        $sqlCancelarAsignaciones = "UPDATE asignaciones_mecanico am
                                   INNER JOIN ingreso_vehiculos iv ON am.VehiculoID = iv.ID
                                   SET am.Estado = 'Cancelado'
                                   WHERE am.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'En Pausa')";
        
        if (mysqli_query($conn, $sqlCancelarAsignaciones)) {
            $totalAsignacionesCanceladas = mysqli_affected_rows($conn);
            if ($totalAsignacionesCanceladas > 0) {
                $mensajeSuperpoder .= " $totalAsignacionesCanceladas asignaciones de mecánico canceladas.";
            }
        }
        
        // Superpoder: Eliminar completamente WLVY22 y asignar otro camión a Luis López
        $mensajeWLVY22 = '';
        $placaWLVY22 = 'WLVY22';
        $conductorLuisLopez = 'Luis López';
        
        // 1. Primero, obtener el ID del vehículo WLVY22 y los IDs de asignaciones relacionadas
        $sqlGetVehiculoID = "SELECT ID FROM ingreso_vehiculos WHERE Placa = ? LIMIT 1";
        $stmtGetID = $conn->prepare($sqlGetVehiculoID);
        $vehiculoID = null;
        $asignacionesIDs = [];
        
        if ($stmtGetID) {
            $stmtGetID->bind_param("s", $placaWLVY22);
            $stmtGetID->execute();
            $resultGetID = $stmtGetID->get_result();
            if ($resultGetID && $resultGetID->num_rows > 0) {
                $row = $resultGetID->fetch_assoc();
                $vehiculoID = $row['ID'];
                
                // Obtener todos los IDs de asignaciones_mecanico para este vehículo
                $sqlGetAsignaciones = "SELECT ID FROM asignaciones_mecanico WHERE VehiculoID = ?";
                $stmtGetAsignaciones = $conn->prepare($sqlGetAsignaciones);
                if ($stmtGetAsignaciones) {
                    $stmtGetAsignaciones->bind_param("i", $vehiculoID);
                    $stmtGetAsignaciones->execute();
                    $resultAsignaciones = $stmtGetAsignaciones->get_result();
                    while ($rowAsig = $resultAsignaciones->fetch_assoc()) {
                        $asignacionesIDs[] = $rowAsig['ID'];
                    }
                    $stmtGetAsignaciones->close();
                }
            }
            $stmtGetID->close();
        }
        
        // 2. Eliminar primero las tablas hijas de asignaciones_mecanico (en orden correcto)
        if (!empty($asignacionesIDs)) {
            $idsStr = implode(',', array_map('intval', $asignacionesIDs));
            
            // 2.1. Eliminar avances_mecanico (tiene FK a asignaciones_mecanico)
            $sqlEliminarAvances = "DELETE FROM avances_mecanico WHERE AsignacionID IN ($idsStr)";
            if (mysqli_query($conn, $sqlEliminarAvances)) {
                $eliminadosAvances = mysqli_affected_rows($conn);
                if ($eliminadosAvances > 0) {
                    $mensajeWLVY22 .= " $eliminadosAvances avances de mecánico eliminados.";
                }
            } else {
                error_log("Error eliminando avances_mecanico: " . mysqli_error($conn));
            }
            
            // 2.2. Eliminar repuestos_asignacion (tiene FK a asignaciones_mecanico)
            $sqlEliminarRepuestosAsignacion = "DELETE FROM repuestos_asignacion WHERE AsignacionID IN ($idsStr)";
            if (mysqli_query($conn, $sqlEliminarRepuestosAsignacion)) {
                $eliminadosRepAsig = mysqli_affected_rows($conn);
                if ($eliminadosRepAsig > 0) {
                    $mensajeWLVY22 .= " $eliminadosRepAsig repuestos de asignación eliminados.";
                }
            } else {
                error_log("Error eliminando repuestos_asignacion: " . mysqli_error($conn));
            }
            
            // 2.3. Eliminar diagnostico_calidad (tiene FK a asignaciones_mecanico)
            $sqlEliminarDiagnostico = "DELETE FROM diagnostico_calidad WHERE AsignacionID IN ($idsStr)";
            if (mysqli_query($conn, $sqlEliminarDiagnostico)) {
                $eliminadosDiag = mysqli_affected_rows($conn);
                if ($eliminadosDiag > 0) {
                    $mensajeWLVY22 .= " $eliminadosDiag diagnósticos de calidad eliminados.";
                }
            } else {
                error_log("Error eliminando diagnostico_calidad: " . mysqli_error($conn));
            }
            
            // 2.4. Actualizar solicitudes_repuestos (puede tener FK a asignaciones_mecanico, pero puede ser NULL)
            // En lugar de eliminar, ponemos AsignacionID a NULL para mantener las solicitudes
            $sqlActualizarSolicitudesRep = "UPDATE solicitudes_repuestos SET AsignacionID = NULL WHERE AsignacionID IN ($idsStr)";
            if (mysqli_query($conn, $sqlActualizarSolicitudesRep)) {
                $actualizadosSolicitudesRep = mysqli_affected_rows($conn);
                if ($actualizadosSolicitudesRep > 0) {
                    $mensajeWLVY22 .= " $actualizadosSolicitudesRep solicitudes de repuestos actualizadas (AsignacionID = NULL).";
                }
            } else {
                error_log("Error actualizando solicitudes_repuestos: " . mysqli_error($conn));
            }
        }
        
        // 3. Ahora eliminar asignaciones_mecanico (después de eliminar sus dependencias)
        if ($vehiculoID) {
            $sqlEliminarAsignaciones = "DELETE FROM asignaciones_mecanico WHERE VehiculoID = ?";
            $stmtAsignaciones = $conn->prepare($sqlEliminarAsignaciones);
            if ($stmtAsignaciones) {
                $stmtAsignaciones->bind_param("i", $vehiculoID);
                if ($stmtAsignaciones->execute()) {
                    $eliminadosAsignaciones = mysqli_affected_rows($conn);
                    if ($eliminadosAsignaciones > 0) {
                        $mensajeWLVY22 .= " $eliminadosAsignaciones asignaciones de mecánico eliminadas para WLVY22.";
                    }
                } else {
                    error_log("Error eliminando asignaciones_mecanico: " . mysqli_error($conn));
                }
                $stmtAsignaciones->close();
            }
        }
        
        // 4. Eliminar otras tablas que tienen foreign keys a ingreso_vehiculos por VehiculoID
        if ($vehiculoID) {
            // 4.1. Eliminar asignaciones_vehiculos (tiene FK a ingreso_vehiculos)
            $sqlEliminarAsignacionesVehiculos = "DELETE FROM asignaciones_vehiculos WHERE VehiculoID = ?";
            $stmtAsigVehiculos = $conn->prepare($sqlEliminarAsignacionesVehiculos);
            if ($stmtAsigVehiculos) {
                $stmtAsigVehiculos->bind_param("i", $vehiculoID);
                if ($stmtAsigVehiculos->execute()) {
                    $eliminadosAsigVehiculos = mysqli_affected_rows($conn);
                    if ($eliminadosAsigVehiculos > 0) {
                        $mensajeWLVY22 .= " $eliminadosAsigVehiculos asignaciones de vehículos eliminadas.";
                    }
                } else {
                    error_log("Error eliminando asignaciones_vehiculos: " . mysqli_error($conn));
                }
                $stmtAsigVehiculos->close();
            }
            
            // 4.2. Eliminar reportes_fallas_vehiculos (tiene FK a ingreso_vehiculos)
            $sqlEliminarReportesFallas = "DELETE FROM reportes_fallas_vehiculos WHERE VehiculoID = ?";
            $stmtReportesFallas = $conn->prepare($sqlEliminarReportesFallas);
            if ($stmtReportesFallas) {
                $stmtReportesFallas->bind_param("i", $vehiculoID);
                if ($stmtReportesFallas->execute()) {
                    $eliminadosReportesFallas = mysqli_affected_rows($conn);
                    if ($eliminadosReportesFallas > 0) {
                        $mensajeWLVY22 .= " $eliminadosReportesFallas reportes de fallas eliminados.";
                    }
                } else {
                    error_log("Error eliminando reportes_fallas_vehiculos: " . mysqli_error($conn));
                }
                $stmtReportesFallas->close();
            }
        }
        
        // 5. Eliminar solicitudes de agendamiento (puede tener foreign keys también)
        $sqlEliminarSolicitudes = "DELETE FROM solicitudes_agendamiento WHERE Placa = ?";
        $stmtSolicitudes = $conn->prepare($sqlEliminarSolicitudes);
        if ($stmtSolicitudes) {
            $stmtSolicitudes->bind_param("s", $placaWLVY22);
            if ($stmtSolicitudes->execute()) {
                $eliminadosSolicitudes = mysqli_affected_rows($conn);
                if ($eliminadosSolicitudes > 0) {
                    $mensajeWLVY22 .= " $eliminadosSolicitudes solicitudes de agendamiento eliminadas para WLVY22.";
                }
            } else {
                error_log("Error eliminando solicitudes_agendamiento: " . mysqli_error($conn));
            }
            $stmtSolicitudes->close();
        }
        
        // 6. Obtener todas las tablas que tienen columna Placa
        $tablasConPlaca = obtenerTablasConPlaca($conn);
        
        if (!empty($tablasConPlaca)) {
            $totalEliminados = 0;
            $tablasProcesadas = [];
            
            // Ordenar tablas: primero las que no tienen foreign keys, luego ingreso_vehiculos al final
            $tablasOrdenadas = [];
            $tablaIngresoVehiculos = null;
            
            foreach ($tablasConPlaca as $tabla) {
                if (strtolower($tabla) === 'ingreso_vehiculos') {
                    $tablaIngresoVehiculos = $tabla;
                } else {
                    $tablasOrdenadas[] = $tabla;
                }
            }
            
            // Agregar ingreso_vehiculos al final
            if ($tablaIngresoVehiculos) {
                $tablasOrdenadas[] = $tablaIngresoVehiculos;
            }
            
            foreach ($tablasOrdenadas as $tabla) {
                // Verificar el nombre exacto de la columna Placa en esta tabla
                $sqlColumnas = "SHOW COLUMNS FROM `$tabla` WHERE Field IN ('Placa', 'placa')";
                $resultColumnas = mysqli_query($conn, $sqlColumnas);
                
                if ($resultColumnas && mysqli_num_rows($resultColumnas) > 0) {
                    $columna = mysqli_fetch_assoc($resultColumnas);
                    $nombreColumnaPlaca = $columna['Field'];
                    
                    // Eliminar todos los registros con WLVY22
                    $sqlEliminar = "DELETE FROM `$tabla` WHERE `$nombreColumnaPlaca` = ?";
                    $stmtEliminar = $conn->prepare($sqlEliminar);
                    
                    if ($stmtEliminar) {
                        $stmtEliminar->bind_param("s", $placaWLVY22);
                        if ($stmtEliminar->execute()) {
                            $eliminados = mysqli_affected_rows($conn);
                            if ($eliminados > 0) {
                                $totalEliminados += $eliminados;
                                $tablasProcesadas[] = "$tabla ($eliminados registros)";
                            }
                        } else {
                            error_log("Error eliminando de $tabla: " . mysqli_error($conn));
                        }
                        $stmtEliminar->close();
                    }
                }
            }
            
            if ($totalEliminados > 0) {
                $mensajeWLVY22 .= " WLVY22 eliminado completamente: $totalEliminados registros de " . count($tablasProcesadas) . " tablas (" . implode(', ', $tablasProcesadas) . ").";
            } else {
                $mensajeWLVY22 .= " WLVY22 no tenía más registros para eliminar.";
            }
        }
        
        // 2. Buscar un camión disponible para asignar a Luis López
        // Buscar un camión que no tenga conductor asignado o que esté disponible
        $sqlBuscarCamion = "SELECT Placa, TipoVehiculo, Marca, Modelo 
                           FROM ingreso_vehiculos 
                           WHERE Placa != ? 
                           AND Placa != 'KSZJ43'
                           AND (ConductorNombre IS NULL OR ConductorNombre = '' OR ConductorNombre = 'N/A')
                           AND TipoVehiculo LIKE '%Camión%'
                           ORDER BY FechaRegistro DESC 
                           LIMIT 1";
        
        $stmtBuscar = $conn->prepare($sqlBuscarCamion);
        $nuevoCamion = null;
        
        if ($stmtBuscar) {
            $stmtBuscar->bind_param("s", $placaWLVY22);
            $stmtBuscar->execute();
            $resultBuscar = $stmtBuscar->get_result();
            
            if ($resultBuscar && $resultBuscar->num_rows > 0) {
                $nuevoCamion = $resultBuscar->fetch_assoc();
            }
            $stmtBuscar->close();
        }
        
        // Si no hay camión sin conductor, buscar cualquier camión disponible (excepto WLVY22 y KSZJ43)
        if (!$nuevoCamion) {
            $sqlBuscarCamion2 = "SELECT Placa, TipoVehiculo, Marca, Modelo 
                                FROM ingreso_vehiculos 
                                WHERE Placa != ? 
                                AND Placa != 'KSZJ43'
                                AND TipoVehiculo LIKE '%Camión%'
                                ORDER BY FechaRegistro DESC 
                                LIMIT 1";
            
            $stmtBuscar2 = $conn->prepare($sqlBuscarCamion2);
            if ($stmtBuscar2) {
                $stmtBuscar2->bind_param("s", $placaWLVY22);
                $stmtBuscar2->execute();
                $resultBuscar2 = $stmtBuscar2->get_result();
                
                if ($resultBuscar2 && $resultBuscar2->num_rows > 0) {
                    $nuevoCamion = $resultBuscar2->fetch_assoc();
                }
                $stmtBuscar2->close();
            }
        }
        
        // 3. Asignar el nuevo camión a Luis López
        if ($nuevoCamion) {
            $nuevaPlaca = $nuevoCamion['Placa'];
            
            // Actualizar ingreso_vehiculos
            $sqlAsignar = "UPDATE ingreso_vehiculos 
                          SET ConductorNombre = ? 
                          WHERE Placa = ?";
            
            $stmtAsignar = $conn->prepare($sqlAsignar);
            if ($stmtAsignar) {
                $stmtAsignar->bind_param("ss", $conductorLuisLopez, $nuevaPlaca);
                if ($stmtAsignar->execute()) {
                    $mensajeWLVY22 .= " Luis López asignado al camión $nuevaPlaca ({$nuevoCamion['Marca']} {$nuevoCamion['Modelo']}).";
                } else {
                    $mensajeWLVY22 .= " Error al asignar camión a Luis López: " . mysqli_error($conn);
                }
                $stmtAsignar->close();
            }
            
            // 3.1. Eliminar TODAS las solicitudes de agendamiento de Luis López
            // Eliminar por ConductorNombre y también por la placa del nuevo vehículo asignado
            $sqlEliminarSolicitudesLuis = "DELETE FROM solicitudes_agendamiento 
                                          WHERE ConductorNombre = ? 
                                          OR Placa = ?";
            
            $stmtEliminarSolicitudesLuis = $conn->prepare($sqlEliminarSolicitudesLuis);
            if ($stmtEliminarSolicitudesLuis) {
                $stmtEliminarSolicitudesLuis->bind_param("ss", $conductorLuisLopez, $nuevaPlaca);
                if ($stmtEliminarSolicitudesLuis->execute()) {
                    $eliminadosSolicitudesLuis = mysqli_affected_rows($conn);
                    if ($eliminadosSolicitudesLuis > 0) {
                        $mensajeWLVY22 .= " $eliminadosSolicitudesLuis solicitudes de agendamiento de Luis López eliminadas (sin horas solicitadas).";
                    }
                } else {
                    error_log("Error eliminando solicitudes de Luis López: " . mysqli_error($conn));
                }
                $stmtEliminarSolicitudesLuis->close();
            }
            
            // 3.2. También eliminar por ChoferID si existe un usuario con ese nombre
            // Buscar el ChoferID de Luis López en la tabla usuarios
            $sqlBuscarChoferID = "SELECT UsuarioID FROM usuarios WHERE NombreUsuario = ? LIMIT 1";
            $stmtBuscarChoferID = $conn->prepare($sqlBuscarChoferID);
            $choferIDLuis = null;
            
            if ($stmtBuscarChoferID) {
                $stmtBuscarChoferID->bind_param("s", $conductorLuisLopez);
                $stmtBuscarChoferID->execute();
                $resultChoferID = $stmtBuscarChoferID->get_result();
                if ($resultChoferID && $resultChoferID->num_rows > 0) {
                    $rowChofer = $resultChoferID->fetch_assoc();
                    $choferIDLuis = $rowChofer['UsuarioID'];
                }
                $stmtBuscarChoferID->close();
            }
            
            // Si encontramos el ChoferID, eliminar también por ese campo
            if ($choferIDLuis) {
                $sqlEliminarPorChoferID = "DELETE FROM solicitudes_agendamiento WHERE ChoferID = ?";
                $stmtEliminarPorChoferID = $conn->prepare($sqlEliminarPorChoferID);
                if ($stmtEliminarPorChoferID) {
                    $stmtEliminarPorChoferID->bind_param("i", $choferIDLuis);
                    if ($stmtEliminarPorChoferID->execute()) {
                        $eliminadosPorChoferID = mysqli_affected_rows($conn);
                        if ($eliminadosPorChoferID > 0) {
                            $mensajeWLVY22 .= " $eliminadosPorChoferID solicitudes adicionales eliminadas por ChoferID.";
                        }
                    }
                    $stmtEliminarPorChoferID->close();
                }
            }
        } else {
            $mensajeWLVY22 .= " No se encontró un camión disponible para asignar a Luis López.";
            
            // Aún así, eliminar las solicitudes de Luis López si no se encontró camión
            $sqlEliminarSolicitudesLuis = "DELETE FROM solicitudes_agendamiento WHERE ConductorNombre = ?";
            $stmtEliminarSolicitudesLuis = $conn->prepare($sqlEliminarSolicitudesLuis);
            if ($stmtEliminarSolicitudesLuis) {
                $stmtEliminarSolicitudesLuis->bind_param("s", $conductorLuisLopez);
                if ($stmtEliminarSolicitudesLuis->execute()) {
                    $eliminadosSolicitudesLuis = mysqli_affected_rows($conn);
                    if ($eliminadosSolicitudesLuis > 0) {
                        $mensajeWLVY22 .= " $eliminadosSolicitudesLuis solicitudes de agendamiento de Luis López eliminadas.";
                    }
                }
                $stmtEliminarSolicitudesLuis->close();
            }
        }
        
        mysqli_close($conn);

        return [
            'status' => 'success',
            'message' => "Se marcaron $totalActualizados vehículos como retirados correctamente.$mensajeDuplicados$mensajeConductores$mensajeSolicitudes$mensajeNoLlego$mensajeSuperpoder$mensajeWLVY22",
            'total' => $totalActualizados,
            'total_duplicados' => $resultadoDuplicados['total'],
            'total_conductores' => $totalConductores,
            'total_solicitudes' => $totalSolicitudes,
            'marcado_no_llego' => $marcadoNoLlego
        ];

    } catch (Exception $e) {
        error_log("Excepción en marcarVehiculosRetirados: " . $e->getMessage());
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Obtiene todas las tablas que tienen una columna "Placa"
 * @param mysqli $conn Conexión a la base de datos
 * @return array Lista de nombres de tablas
 */
function obtenerTablasConPlaca($conn) {
    $tablas = [];
    
    // Obtener el nombre de la base de datos actual
    $dbName = mysqli_fetch_assoc(mysqli_query($conn, "SELECT DATABASE() as db"))['db'];
    
    // Buscar todas las tablas que tienen una columna "Placa" (case-insensitive)
    $sql = "SELECT DISTINCT TABLE_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? 
            AND (COLUMN_NAME = 'Placa' OR COLUMN_NAME = 'placa' OR COLUMN_NAME = 'PLACA')
            ORDER BY TABLE_NAME";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $dbName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $tablas[] = $row['TABLE_NAME'];
        }
        
        $stmt->close();
    }
    
    return $tablas;
}

/**
 * Actualiza una placa en todas las tablas que la contengan
 * @param mysqli $conn Conexión a la base de datos
 * @param string $placaAntigua Placa a reemplazar
 * @param string $placaNueva Nueva placa
 * @param array $tablas Lista de tablas a actualizar
 * @param int|null $idVehiculo ID específico del vehículo en ingreso_vehiculos (opcional)
 * @return array Resultado con total de actualizaciones
 */
function actualizarPlacaEnTodasLasTablas($conn, $placaAntigua, $placaNueva, $tablas, $idVehiculo = null) {
    $totalActualizaciones = 0;
    $errores = [];
    
    foreach ($tablas as $tabla) {
        try {
            // Verificar si la tabla tiene una columna ID que podamos usar para identificar registros específicos
            $sqlCheckId = "SHOW COLUMNS FROM `$tabla` LIKE 'ID'";
            $resultCheckId = mysqli_query($conn, $sqlCheckId);
            $tieneId = ($resultCheckId && mysqli_num_rows($resultCheckId) > 0);
            
            // Si es ingreso_vehiculos y tenemos un ID específico, actualizar solo ese registro
            if ($tabla === 'ingreso_vehiculos' && $idVehiculo !== null) {
                $sqlUpdate = "UPDATE `$tabla` SET Placa = ? WHERE ID = ? AND Placa = ?";
                $stmt = $conn->prepare($sqlUpdate);
                if ($stmt) {
                    $stmt->bind_param("sis", $placaNueva, $idVehiculo, $placaAntigua);
                    if ($stmt->execute()) {
                        $totalActualizaciones += mysqli_affected_rows($conn);
                    } else {
                        $errores[] = "Error en $tabla: " . $stmt->error;
                    }
                    $stmt->close();
                }
                } else {
                    // Para otras tablas, actualizar todos los registros con la placa antigua
                    // pero solo si no es el primer registro (más antiguo) de solicitudes_agendamiento
                    if ($tabla === 'solicitudes_agendamiento') {
                        // Mantener la primera solicitud con la placa original
                        // Usar una subconsulta más segura
                        $sqlUpdate = "UPDATE `$tabla` t1
                                    INNER JOIN (
                                        SELECT ID FROM `$tabla` 
                                        WHERE Placa = ? 
                                        ORDER BY FechaCreacion ASC 
                                        LIMIT 1
                                    ) t2 ON t1.ID = t2.ID
                                    SET t1.Placa = ?
                                    WHERE t1.Placa = ? AND t1.ID NOT IN (
                                        SELECT temp.ID FROM (
                                            SELECT ID FROM `$tabla` 
                                            WHERE Placa = ? 
                                            ORDER BY FechaCreacion ASC 
                                            LIMIT 1
                                        ) as temp
                                    )";
                        
                        // Alternativa más simple si la anterior falla
                        $sqlUpdateSimple = "UPDATE `$tabla` 
                                          SET Placa = ? 
                                          WHERE Placa = ? 
                                          AND ID != (
                                              SELECT min_id FROM (
                                                  SELECT MIN(ID) as min_id FROM `$tabla` 
                                                  WHERE Placa = ?
                                              ) as temp2
                                          )";
                        
                        $stmt = $conn->prepare($sqlUpdateSimple);
                        if ($stmt) {
                            $stmt->bind_param("sss", $placaNueva, $placaAntigua, $placaAntigua);
                            if ($stmt->execute()) {
                                $totalActualizaciones += mysqli_affected_rows($conn);
                            } else {
                                $errores[] = "Error en $tabla: " . $stmt->error;
                            }
                            $stmt->close();
                        }
                    } else {
                        // Para otras tablas, actualizar todos los registros
                        $sqlUpdate = "UPDATE `$tabla` SET Placa = ? WHERE Placa = ?";
                        $stmt = $conn->prepare($sqlUpdate);
                        if ($stmt) {
                            $stmt->bind_param("ss", $placaNueva, $placaAntigua);
                            if ($stmt->execute()) {
                                $totalActualizaciones += mysqli_affected_rows($conn);
                            } else {
                                $errores[] = "Error en $tabla: " . $stmt->error;
                            }
                            $stmt->close();
                        }
                    }
                }
        } catch (Exception $e) {
            $errores[] = "Excepción en $tabla: " . $e->getMessage();
        }
    }
    
    return [
        'total' => $totalActualizaciones,
        'errores' => $errores
    ];
}

/**
 * Procesa y reemplaza placas duplicadas en toda la base de datos
 * @param mysqli $conn Conexión a la base de datos
 * @return array Resultado del procesamiento
 */
function procesarPlacasDuplicadas($conn) {
    $totalReemplazos = 0;
    $mensaje = '';
    $reemplazos = [];
    $totalRegistrosActualizados = 0;
    
    try {
        // 1. Obtener todas las tablas que tienen columna Placa
        $tablasConPlaca = obtenerTablasConPlaca($conn);
        
        if (empty($tablasConPlaca)) {
            return [
                'total' => 0,
                'mensaje' => ' No se encontraron tablas con columna Placa.',
                'reemplazos' => []
            ];
        }
        
        error_log("Tablas con columna Placa encontradas: " . implode(', ', $tablasConPlaca));
        
        // 2. Encontrar todas las placas duplicadas en ingreso_vehiculos
        $sqlDuplicados = "SELECT Placa, COUNT(*) as cantidad, GROUP_CONCAT(ID ORDER BY ID) as ids
                         FROM ingreso_vehiculos
                         GROUP BY Placa
                         HAVING cantidad > 1";
        
        $resultDuplicados = mysqli_query($conn, $sqlDuplicados);
        
        if (!$resultDuplicados) {
            return [
                'total' => 0,
                'mensaje' => ' Error al buscar duplicados: ' . mysqli_error($conn)
            ];
        }
        
        $placasProcesadas = [];
        
        while ($row = mysqli_fetch_assoc($resultDuplicados)) {
            $placa = $row['Placa'];
            $ids = explode(',', $row['ids']);
            $cantidad = count($ids);
            
            // Evitar procesar la misma placa dos veces
            if (in_array($placa, $placasProcesadas)) {
                continue;
            }
            $placasProcesadas[] = $placa;
            
            // Mantener el primer ID, reemplazar los demás
            $idPrincipal = intval($ids[0]);
            $idsDuplicados = array_slice($ids, 1);
            
            foreach ($idsDuplicados as $idDuplicado) {
                $idDuplicado = intval($idDuplicado);
                
                // Generar nueva placa única
                $nuevaPlaca = generarPlacaUnica($conn, $placa);
                
                if (!$nuevaPlaca) {
                    $mensaje .= " Error al generar placa única para duplicado de $placa (ID: $idDuplicado).";
                    continue;
                }
                
                // Actualizar en TODAS las tablas que tengan la columna Placa
                $resultadoActualizacion = actualizarPlacaEnTodasLasTablas(
                    $conn, 
                    $placa, 
                    $nuevaPlaca, 
                    $tablasConPlaca, 
                    $idDuplicado
                );
                
                $totalRegistrosActualizados += $resultadoActualizacion['total'];
                
                if (!empty($resultadoActualizacion['errores'])) {
                    $mensaje .= " Errores al actualizar: " . implode('; ', $resultadoActualizacion['errores']);
                }
                
                $reemplazos[] = [
                    'placa_original' => $placa,
                    'nueva_placa' => $nuevaPlaca,
                    'id' => $idDuplicado,
                    'registros_actualizados' => $resultadoActualizacion['total']
                ];
                $totalReemplazos++;
            }
        }
        
        // 3. Verificar también duplicados en otras tablas (por si acaso)
        foreach ($tablasConPlaca as $tabla) {
            if ($tabla === 'ingreso_vehiculos') {
                continue; // Ya lo procesamos arriba
            }
            
            $sqlDuplicadosTabla = "SELECT Placa, COUNT(*) as cantidad, GROUP_CONCAT(ID ORDER BY ID) as ids
                                  FROM `$tabla`
                                  GROUP BY Placa
                                  HAVING cantidad > 1
                                  LIMIT 50"; // Limitar para no sobrecargar
            
            $resultDuplicadosTabla = mysqli_query($conn, $sqlDuplicadosTabla);
            
            if ($resultDuplicadosTabla) {
                while ($rowTabla = mysqli_fetch_assoc($resultDuplicadosTabla)) {
                    $placaTabla = $rowTabla['Placa'];
                    
                    // Solo procesar si no está en ingreso_vehiculos o si es diferente
                    $sqlCheckIngreso = "SELECT COUNT(*) as total FROM ingreso_vehiculos WHERE Placa = ?";
                    $stmtCheck = $conn->prepare($sqlCheckIngreso);
                    if ($stmtCheck) {
                        $stmtCheck->bind_param("s", $placaTabla);
                        $stmtCheck->execute();
                        $resultCheck = $stmtCheck->get_result();
                        $rowCheck = $resultCheck->fetch_assoc();
                        $stmtCheck->close();
                        
                        // Si hay más de un registro en ingreso_vehiculos, ya se procesó arriba
                        if ($rowCheck['total'] > 1) {
                            continue;
                        }
                        
                        // Si hay duplicados solo en esta tabla, generar nueva placa
                        $idsTabla = explode(',', $rowTabla['ids']);
                        if (count($idsTabla) > 1) {
                            $idPrincipalTabla = intval($idsTabla[0]);
                            $idsDuplicadosTabla = array_slice($idsTabla, 1);
                            
                            foreach ($idsDuplicadosTabla as $idDuplicadoTabla) {
                                $nuevaPlacaTabla = generarPlacaUnica($conn, $placaTabla);
                                
                                if ($nuevaPlacaTabla) {
                                    $resultadoActualizacionTabla = actualizarPlacaEnTodasLasTablas(
                                        $conn, 
                                        $placaTabla, 
                                        $nuevaPlacaTabla, 
                                        [$tabla]
                                    );
                                    
                                    $totalRegistrosActualizados += $resultadoActualizacionTabla['total'];
                                    $totalReemplazos++;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if ($totalReemplazos > 0) {
            $mensaje = " Se reemplazaron $totalReemplazos placas duplicadas en $totalRegistrosActualizados registros de " . count($tablasConPlaca) . " tablas.";
        } else {
            $mensaje = " No se encontraron placas duplicadas.";
        }
        
        return [
            'total' => $totalReemplazos,
            'mensaje' => $mensaje,
            'reemplazos' => $reemplazos,
            'tablas_procesadas' => $tablasConPlaca,
            'total_registros' => $totalRegistrosActualizados
        ];
        
    } catch (Exception $e) {
        error_log("Error en procesarPlacasDuplicadas: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return [
            'total' => 0,
            'mensaje' => ' Error al procesar duplicados: ' . $e->getMessage()
        ];
    }
}

/**
 * Verifica si una placa existe en todas las tablas que tienen columna Placa
 * @param mysqli $conn Conexión a la base de datos
 * @param string $placa Placa a verificar
 * @param array $tablas Lista de tablas a verificar
 * @return bool true si la placa existe en alguna tabla, false si no existe en ninguna
 */
function placaExisteEnTodasLasTablas($conn, $placa, $tablas) {
    foreach ($tablas as $tabla) {
        $sqlCheck = "SELECT COUNT(*) as total FROM `$tabla` WHERE Placa = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        if ($stmtCheck) {
            $stmtCheck->bind_param("s", $placa);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            $rowCheck = $resultCheck->fetch_assoc();
            $stmtCheck->close();
            
            if ($rowCheck['total'] > 0) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Genera una placa única basada en una placa existente
 * @param mysqli $conn Conexión a la base de datos
 * @param string $placaOriginal Placa original
 * @return string Nueva placa única o false si falla
 */
function generarPlacaUnica($conn, $placaOriginal) {
    // Obtener todas las tablas con columna Placa
    $tablasConPlaca = obtenerTablasConPlaca($conn);
    
    if (empty($tablasConPlaca)) {
        // Fallback: usar solo las tablas principales
        $tablasConPlaca = ['ingreso_vehiculos', 'solicitudes_agendamiento'];
    }
    
    // Intentar generar variaciones de la placa
    $intentos = 0;
    $maxIntentos = 200;
    
    while ($intentos < $maxIntentos) {
        // Generar variación: agregar sufijo numérico o letra
        if (preg_match('/^([A-Z]{2,4})(\d{2,4})$/', strtoupper($placaOriginal), $matches)) {
            // Formato: LETRAS + NÚMEROS (ej: ABC123)
            $letras = $matches[1];
            $numeros = intval($matches[2]);
            $nuevaPlaca = $letras . str_pad($numeros + $intentos + 1, strlen($matches[2]), '0', STR_PAD_LEFT);
        } elseif (preg_match('/^([A-Z]+)(\d+)$/', strtoupper($placaOriginal), $matches)) {
            // Formato más flexible: cualquier cantidad de letras + números
            $letras = $matches[1];
            $numeros = intval($matches[2]);
            $nuevaPlaca = $letras . ($numeros + $intentos + 1);
        } else {
            // Formato desconocido, agregar sufijo
            $sufijo = str_pad($intentos + 1, 3, '0', STR_PAD_LEFT);
            $placaLimpia = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placaOriginal));
            if (strlen($placaLimpia) > 3) {
                $nuevaPlaca = substr($placaLimpia, 0, -3) . $sufijo;
            } else {
                $nuevaPlaca = $placaLimpia . $sufijo;
            }
        }
        
        // Verificar que no exista en NINGUNA tabla
        if (!placaExisteEnTodasLasTablas($conn, $nuevaPlaca, $tablasConPlaca)) {
            return $nuevaPlaca;
        }
        
        $intentos++;
    }
    
    // Si no se pudo generar una placa única con variaciones, usar timestamp + random
    $timestamp = time();
    $random = rand(100, 999);
    $nuevaPlaca = 'DUP' . substr($timestamp, -6) . $random;
    
    // Verificar una vez más
    if (!placaExisteEnTodasLasTablas($conn, $nuevaPlaca, $tablasConPlaca)) {
        return $nuevaPlaca;
    }
    
    // Último intento: usar microtime + random
    $microtime = substr(str_replace('.', '', microtime(true)), -8);
    $random = rand(1000, 9999);
    $nuevaPlaca = 'DUP' . $microtime . $random;
    
    if (!placaExisteEnTodasLasTablas($conn, $nuevaPlaca, $tablasConPlaca)) {
        return $nuevaPlaca;
    }
    
    return false;
}

/**
 * Obtiene todas las tablas que tienen una columna "ConductorNombre"
 * @param mysqli $conn Conexión a la base de datos
 * @return array Lista de nombres de tablas
 */
function obtenerTablasConConductorNombre($conn) {
    $tablas = [];
    
    // Obtener el nombre de la base de datos actual
    $dbName = mysqli_fetch_assoc(mysqli_query($conn, "SELECT DATABASE() as db"))['db'];
    
    // Buscar todas las tablas que tienen una columna "ConductorNombre" (case-insensitive)
    $sql = "SELECT DISTINCT TABLE_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? 
            AND (COLUMN_NAME = 'ConductorNombre' OR COLUMN_NAME = 'conductorNombre' OR COLUMN_NAME = 'CONDUCTORNOMBRE')
            ORDER BY TABLE_NAME";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $dbName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $tablas[] = $row['TABLE_NAME'];
        }
        
        $stmt->close();
    }
    
    return $tablas;
}

/**
 * Procesa conductores que tienen múltiples vehículos asignados
 * Asigna un solo vehículo a cada conductor (el más reciente o activo)
 * @param mysqli $conn Conexión a la base de datos
 * @return array Resultado del procesamiento
 */
function procesarConductoresMultiplesVehiculos($conn) {
    $totalProcesados = 0;
    $mensaje = '';
    $asignaciones = [];
    
    try {
        // 1. Encontrar conductores con múltiples vehículos en ingreso_vehiculos
        $sqlConductoresMultiples = "SELECT 
                                    ConductorNombre, 
                                    COUNT(DISTINCT Placa) as cantidad_vehiculos,
                                    GROUP_CONCAT(DISTINCT Placa ORDER BY FechaRegistro DESC SEPARATOR ',') as placas,
                                    GROUP_CONCAT(DISTINCT ID ORDER BY FechaRegistro DESC SEPARATOR ',') as ids
                                   FROM ingreso_vehiculos
                                   WHERE ConductorNombre IS NOT NULL 
                                   AND ConductorNombre != ''
                                   GROUP BY ConductorNombre
                                   HAVING cantidad_vehiculos > 1";
        
        $resultConductores = mysqli_query($conn, $sqlConductoresMultiples);
        
        if (!$resultConductores) {
            return [
                'total' => 0,
                'mensaje' => ' Error al buscar conductores con múltiples vehículos: ' . mysqli_error($conn)
            ];
        }
        
        $tablasConConductor = obtenerTablasConConductorNombre($conn);
        $tablasConPlaca = obtenerTablasConPlaca($conn);
        
        if (empty($tablasConConductor)) {
            return [
                'total' => 0,
                'mensaje' => ' No se encontraron tablas con columna ConductorNombre.'
            ];
        }
        
        while ($row = mysqli_fetch_assoc($resultConductores)) {
            $conductorNombre = $row['ConductorNombre'];
            $placas = explode(',', $row['placas']);
            $ids = explode(',', $row['ids']);
            $cantidad = count($placas);
            
            // Mantener el primer vehículo (más reciente) y procesar los demás
            $placaPrincipal = $placas[0];
            $idPrincipal = intval($ids[0]);
            $placasSecundarias = array_slice($placas, 1);
            $idsSecundarios = array_slice($ids, 1);
            
            foreach ($placasSecundarias as $index => $placaSecundaria) {
                $idSecundario = intval($idsSecundarios[$index]);
                
                // Opción 1: Asignar una nueva placa única al vehículo secundario
                $nuevaPlaca = generarPlacaUnica($conn, $placaSecundaria);
                
                if ($nuevaPlaca) {
                    // Actualizar la placa en todas las tablas
                    $resultadoActualizacion = actualizarPlacaEnTodasLasTablas(
                        $conn,
                        $placaSecundaria,
                        $nuevaPlaca,
                        $tablasConPlaca,
                        $idSecundario
                    );
                    
                    // Limpiar el ConductorNombre del vehículo secundario para que quede sin conductor
                    // O asignarle un conductor genérico
                    foreach ($tablasConConductor as $tabla) {
                        try {
                            // Verificar si la tabla tiene columna ID
                            $sqlCheckId = "SHOW COLUMNS FROM `$tabla` LIKE 'ID'";
                            $resultCheckId = mysqli_query($conn, $sqlCheckId);
                            $tieneId = ($resultCheckId && mysqli_num_rows($resultCheckId) > 0);
                            
                            if ($tabla === 'ingreso_vehiculos' && $tieneId) {
                                // Limpiar conductor del vehículo secundario
                                $sqlLimpiarConductor = "UPDATE `$tabla` 
                                                        SET ConductorNombre = NULL 
                                                        WHERE ID = ? AND Placa = ?";
                                $stmtLimpiar = $conn->prepare($sqlLimpiarConductor);
                                if ($stmtLimpiar) {
                                    $stmtLimpiar->bind_param("is", $idSecundario, $nuevaPlaca);
                                    $stmtLimpiar->execute();
                                    $stmtLimpiar->close();
                                }
                            } else {
                                // Para otras tablas, actualizar donde coincida la placa
                                $sqlLimpiarConductor = "UPDATE `$tabla` 
                                                        SET ConductorNombre = NULL 
                                                        WHERE Placa = ? 
                                                        AND ConductorNombre = ?";
                                $stmtLimpiar = $conn->prepare($sqlLimpiarConductor);
                                if ($stmtLimpiar) {
                                    $stmtLimpiar->bind_param("ss", $nuevaPlaca, $conductorNombre);
                                    $stmtLimpiar->execute();
                                    $stmtLimpiar->close();
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Error al limpiar conductor en $tabla: " . $e->getMessage());
                        }
                    }
                    
                    $asignaciones[] = [
                        'conductor' => $conductorNombre,
                        'placa_principal' => $placaPrincipal,
                        'placa_secundaria_original' => $placaSecundaria,
                        'placa_secundaria_nueva' => $nuevaPlaca,
                        'id_secundario' => $idSecundario
                    ];
                    $totalProcesados++;
                }
            }
        }
        
        // 2. También verificar en solicitudes_agendamiento
        $sqlConductoresSolicitudes = "SELECT 
                                      ConductorNombre, 
                                      COUNT(DISTINCT Placa) as cantidad_vehiculos,
                                      GROUP_CONCAT(DISTINCT Placa ORDER BY FechaCreacion DESC SEPARATOR ',') as placas,
                                      GROUP_CONCAT(DISTINCT ID ORDER BY FechaCreacion DESC SEPARATOR ',') as ids
                                     FROM solicitudes_agendamiento
                                     WHERE ConductorNombre IS NOT NULL 
                                     AND ConductorNombre != ''
                                     GROUP BY ConductorNombre
                                     HAVING cantidad_vehiculos > 1";
        
        $resultSolicitudes = mysqli_query($conn, $sqlConductoresSolicitudes);
        
        if ($resultSolicitudes) {
            while ($row = mysqli_fetch_assoc($resultSolicitudes)) {
                $conductorNombre = $row['ConductorNombre'];
                $placas = explode(',', $row['placas']);
                $ids = explode(',', $row['ids']);
                
                // Verificar si este conductor ya fue procesado en ingreso_vehiculos
                $yaProcesado = false;
                foreach ($asignaciones as $asignacion) {
                    if ($asignacion['conductor'] === $conductorNombre) {
                        $yaProcesado = true;
                        break;
                    }
                }
                
                if (!$yaProcesado && count($placas) > 1) {
                    // Mantener la primera solicitud, limpiar conductor de las demás
                    $idPrincipal = intval($ids[0]);
                    $idsSecundarios = array_slice($ids, 1);
                    
                    foreach ($idsSecundarios as $idSecundario) {
                        $idSecundario = intval($idSecundario);
                        
                        // Limpiar conductor de la solicitud secundaria
                        $sqlLimpiar = "UPDATE solicitudes_agendamiento 
                                      SET ConductorNombre = NULL 
                                      WHERE ID = ?";
                        $stmtLimpiar = $conn->prepare($sqlLimpiar);
                        if ($stmtLimpiar) {
                            $stmtLimpiar->bind_param("i", $idSecundario);
                            $stmtLimpiar->execute();
                            $stmtLimpiar->close();
                            $totalProcesados++;
                        }
                    }
                }
            }
        }
        
        if ($totalProcesados > 0) {
            $mensaje = " Se procesaron $totalProcesados vehículos para asegurar que cada conductor tenga solo un camión.";
        } else {
            $mensaje = " Todos los conductores ya tienen un solo vehículo asignado.";
        }
        
        return [
            'total' => $totalProcesados,
            'mensaje' => $mensaje,
            'asignaciones' => $asignaciones
        ];
        
    } catch (Exception $e) {
        error_log("Error en procesarConductoresMultiplesVehiculos: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return [
            'total' => 0,
            'mensaje' => ' Error al procesar conductores: ' . $e->getMessage()
        ];
    }
}

/**
 * Procesa usuarios que tienen múltiples solicitudes de agendamiento
 * Mantiene solo la solicitud más reciente y cancela las demás
 * @param mysqli $conn Conexión a la base de datos
 * @return array Resultado del procesamiento
 */
function procesarSolicitudesMultiplesPorUsuario($conn) {
    $totalProcesados = 0;
    $mensaje = '';
    $procesados = [];
    
    try {
        // 1. Buscar específicamente al usuario "juan pap"
        $sqlJuanPap = "SELECT UsuarioID, NombreUsuario FROM usuarios 
                       WHERE NombreUsuario LIKE '%juan%pap%' 
                       OR NombreUsuario LIKE '%juanpap%'
                       OR NombreUsuario = 'juan pap'
                       LIMIT 1";
        $resultJuanPap = mysqli_query($conn, $sqlJuanPap);
        $juanPapId = null;
        $juanPapNombre = null;
        
        if ($resultJuanPap && mysqli_num_rows($resultJuanPap) > 0) {
            $juanPap = mysqli_fetch_assoc($resultJuanPap);
            $juanPapId = $juanPap['UsuarioID'];
            $juanPapNombre = $juanPap['NombreUsuario'];
        }
        
        // 2. Encontrar usuarios con múltiples solicitudes de agendamiento
        $sqlUsuariosMultiples = "SELECT 
                                ChoferID,
                                COUNT(*) as cantidad_solicitudes,
                                GROUP_CONCAT(ID ORDER BY FechaCreacion DESC SEPARATOR ',') as ids,
                                GROUP_CONCAT(Placa ORDER BY FechaCreacion DESC SEPARATOR ',') as placas,
                                GROUP_CONCAT(Estado ORDER BY FechaCreacion DESC SEPARATOR ',') as estados
                               FROM solicitudes_agendamiento
                               WHERE ChoferID IS NOT NULL
                               GROUP BY ChoferID
                               HAVING cantidad_solicitudes > 1";
        
        $resultUsuarios = mysqli_query($conn, $sqlUsuariosMultiples);
        
        if (!$resultUsuarios) {
            return [
                'total' => 0,
                'mensaje' => ' Error al buscar usuarios con múltiples solicitudes: ' . mysqli_error($conn)
            ];
        }
        
        // Verificar si el estado 'Cancelada' existe en el ENUM
        $checkEnum = "SHOW COLUMNS FROM solicitudes_agendamiento WHERE Field = 'Estado'";
        $resultEnum = mysqli_query($conn, $checkEnum);
        $tieneCancelada = false;
        if ($resultEnum && $rowEnum = $resultEnum->fetch_assoc()) {
            $type = $rowEnum['Type'];
            $tieneCancelada = (strpos($type, "'Cancelada'") !== false);
        }
        
        // Si no tiene 'Cancelada', agregarla al ENUM
        if (!$tieneCancelada) {
            $sqlModifyEnum = "ALTER TABLE solicitudes_agendamiento 
                            MODIFY COLUMN Estado ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Cancelada', 'Atrasado', 'No llegó') 
                            NOT NULL DEFAULT 'Pendiente'";
            mysqli_query($conn, $sqlModifyEnum);
        }
        
        while ($row = mysqli_fetch_assoc($resultUsuarios)) {
            $choferId = intval($row['ChoferID']);
            $ids = explode(',', $row['ids']);
            $placas = explode(',', $row['placas']);
            $estados = explode(',', $row['estados']);
            $cantidad = count($ids);
            
            // Mantener la primera solicitud (más reciente) y cancelar las demás
            $idPrincipal = intval($ids[0]);
            $placaPrincipal = $placas[0];
            $estadoPrincipal = $estados[0];
            $idsSecundarios = array_slice($ids, 1);
            $placasSecundarias = array_slice($placas, 1);
            $estadosSecundarios = array_slice($estados, 1);
            
            // Obtener nombre del usuario para el log
            $sqlUsuario = "SELECT NombreUsuario FROM usuarios WHERE UsuarioID = ?";
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $nombreUsuario = 'Usuario ID ' . $choferId;
            if ($stmtUsuario) {
                $stmtUsuario->bind_param("i", $choferId);
                $stmtUsuario->execute();
                $resultUsuario = $stmtUsuario->get_result();
                if ($resultUsuario && $rowUsuario = $resultUsuario->fetch_assoc()) {
                    $nombreUsuario = $rowUsuario['NombreUsuario'];
                }
                $stmtUsuario->close();
            }
            
            foreach ($idsSecundarios as $index => $idSecundario) {
                $idSecundario = intval($idSecundario);
                $placaSecundaria = $placasSecundarias[$index] ?? '';
                $estadoSecundario = $estadosSecundarios[$index] ?? '';
                
                // Cancelar la solicitud secundaria
                $sqlCancelar = "UPDATE solicitudes_agendamiento 
                              SET Estado = 'Cancelada',
                                  MotivoRechazo = CONCAT(IFNULL(MotivoRechazo, ''), ' | Cancelada automáticamente: Usuario tenía múltiples solicitudes. Se mantiene la más reciente.'),
                                  FechaActualizacion = NOW()
                              WHERE ID = ?";
                $stmtCancelar = $conn->prepare($sqlCancelar);
                
                if ($stmtCancelar) {
                    $stmtCancelar->bind_param("i", $idSecundario);
                    if ($stmtCancelar->execute()) {
                        // Si la solicitud tenía una agenda asignada, liberarla
                        $sqlLiberarAgenda = "UPDATE solicitudes_agendamiento 
                                            SET AgendaID = NULL 
                                            WHERE ID = ?";
                        $stmtLiberar = $conn->prepare($sqlLiberarAgenda);
                        if ($stmtLiberar) {
                            $stmtLiberar->bind_param("i", $idSecundario);
                            $stmtLiberar->execute();
                            $stmtLiberar->close();
                        }
                        
                        $procesados[] = [
                            'usuario_id' => $choferId,
                            'usuario_nombre' => $nombreUsuario,
                            'solicitud_principal_id' => $idPrincipal,
                            'solicitud_principal_placa' => $placaPrincipal,
                            'solicitud_cancelada_id' => $idSecundario,
                            'solicitud_cancelada_placa' => $placaSecundaria
                        ];
                        $totalProcesados++;
                    }
                    $stmtCancelar->close();
                }
            }
        }
        
        // 3. Procesar específicamente a "juan pap" si se encontró y tiene múltiples solicitudes
        if ($juanPapId !== null) {
            $sqlSolicitudesJuanPap = "SELECT ID, Placa, Estado, FechaCreacion 
                                     FROM solicitudes_agendamiento 
                                     WHERE ChoferID = ? 
                                     ORDER BY FechaCreacion DESC";
            $stmtJuanPap = $conn->prepare($sqlSolicitudesJuanPap);
            
            if ($stmtJuanPap) {
                $stmtJuanPap->bind_param("i", $juanPapId);
                $stmtJuanPap->execute();
                $resultSolicitudes = $stmtJuanPap->get_result();
                $solicitudesJuanPap = [];
                
                while ($row = $resultSolicitudes->fetch_assoc()) {
                    $solicitudesJuanPap[] = $row;
                }
                $stmtJuanPap->close();
                
                // Para juan pap, mantener solo KSZJ43 y eliminar todas las demás
                if (count($solicitudesJuanPap) > 0) {
                    foreach ($solicitudesJuanPap as $solicitud) {
                        $idSolicitud = intval($solicitud['ID']);
                        $placaSolicitud = $solicitud['Placa'];
                        
                        // Si NO es KSZJ43, eliminar completamente la solicitud
                        if (strtoupper($placaSolicitud) !== 'KSZJ43') {
                            // Primero liberar la agenda si tenía una asignada
                            $sqlLiberarAgenda = "UPDATE solicitudes_agendamiento 
                                                SET AgendaID = NULL 
                                                WHERE ID = ?";
                            $stmtLiberar = $conn->prepare($sqlLiberarAgenda);
                            if ($stmtLiberar) {
                                $stmtLiberar->bind_param("i", $idSolicitud);
                                $stmtLiberar->execute();
                                $stmtLiberar->close();
                            }
                            
                            // Eliminar completamente la solicitud
                            $sqlEliminar = "DELETE FROM solicitudes_agendamiento WHERE ID = ?";
                            $stmtEliminar = $conn->prepare($sqlEliminar);
                            
                            if ($stmtEliminar) {
                                $stmtEliminar->bind_param("i", $idSolicitud);
                                if ($stmtEliminar->execute()) {
                                    $totalProcesados++;
                                    error_log("Solicitud ID $idSolicitud (Placa: $placaSolicitud) eliminada para juan pap. Solo se mantiene KSZJ43.");
                                }
                                $stmtEliminar->close();
                            }
                        }
                    }
                }
            }
        }
        
        if ($totalProcesados > 0) {
            $mensaje = " Se procesaron $totalProcesados solicitudes duplicadas para asegurar que cada usuario tenga solo una solicitud activa.";
            if ($juanPapId !== null) {
                $mensaje .= " Usuario 'juan pap' procesado específicamente: Solo se mantiene KSZJ43, las demás solicitudes fueron eliminadas.";
            }
        } else {
            $mensaje = " Todos los usuarios ya tienen una sola solicitud activa.";
        }
        
        return [
            'total' => $totalProcesados,
            'mensaje' => $mensaje,
            'procesados' => $procesados,
            'juan_pap_procesado' => $juanPapId !== null
        ];
        
    } catch (Exception $e) {
        error_log("Error en procesarSolicitudesMultiplesPorUsuario: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return [
            'total' => 0,
            'mensaje' => ' Error al procesar solicitudes múltiples: ' . $e->getMessage()
        ];
    }
}
?>