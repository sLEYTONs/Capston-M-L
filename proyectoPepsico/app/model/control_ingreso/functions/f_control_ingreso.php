<?php
require_once '../../../config/conexion.php';

/**
 * Busca un vehículo por placa
 */
function buscarVehiculo($placa) {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT * FROM ingreso_vehiculos WHERE Placa = ? AND Estado = 'Ingresado'";
    
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
 * Obtiene estadísticas del patio
 */
function obtenerEstadisticasPatio() {
    $conn = conectar_Pepsico();
    
    // Vehículos activos
    $sql1 = "SELECT COUNT(*) as total FROM ingreso_vehiculos WHERE Estado = 'Ingresado'";
    $result1 = $conn->query($sql1);
    $vehiculosActivos = $result1->fetch_assoc()['total'];
    
    // Ingresos de hoy
    $sql2 = "SELECT COUNT(*) as total FROM ingreso_vehiculos 
             WHERE DATE(FechaIngreso) = CURDATE()";
    $result2 = $conn->query($sql2);
    $ingresosHoy = $result2->fetch_assoc()['total'];
    
    // Novedades pendientes
    $sql3 = "SELECT COUNT(*) as total FROM novedades_guardia WHERE Estado = 'Pendiente'";
    $result3 = $conn->query($sql3);
    $novedadesPendientes = $result3->fetch_assoc()['total'];
    
    $conn->close();
    
    return [
        'vehiculosActivos' => $vehiculosActivos,
        'ingresosHoy' => $ingresosHoy,
        'novedadesPendientes' => $novedadesPendientes
    ];
}

/**
 * Reporta una novedad/incidente
 */
function reportarNovedad($placa, $tipo, $descripcion, $gravedad, $usuario_id) {
    $conn = conectar_Pepsico();
    
    $sql = "INSERT INTO novedades_guardia (Placa, Tipo, Descripcion, Gravedad, UsuarioReporta) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $placa, $tipo, $descripcion, $gravedad, $usuario_id);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Obtiene novedades recientes
 */
function obtenerNovedadesRecientes() {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT ng.*, u.NombreUsuario as Reportador, iv.ConductorNombre
            FROM novedades_guardia ng 
            LEFT JOIN USUARIOS u ON ng.UsuarioReporta = u.UsuarioID 
            LEFT JOIN ingreso_vehiculos iv ON ng.Placa = iv.Placa
            WHERE ng.FechaReporte >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY ng.FechaReporte DESC 
            LIMIT 10";
    
    $result = $conn->query($sql);
    $novedades = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $novedades[] = [
                'placa' => $row['Placa'],
                'tipo' => $row['Tipo'],
                'descripcion' => $row['Descripcion'],
                'gravedad' => $row['Gravedad'],
                'fecha' => $row['FechaReporte'],
                'reportador' => $row['Reportador'],
                'conductor' => $row['ConductorNombre'],
                'estado' => $row['Estado']
            ];
        }
    }
    
    $conn->close();
    
    return $novedades;
}

/**
 * Registra ingreso de vehículo con hora asignada (solo guardia)
 * Solo permite ingresar vehículos que tengan una solicitud de agendamiento aprobada
 */
function registrarIngresoBasico($placa, $usuario_id) {
    $conn = conectar_Pepsico();
    
    // Verificar si ya existe un registro activo con esa placa
    $sqlCheck = "SELECT COUNT(*) as total FROM ingreso_vehiculos WHERE Placa = ? AND Estado = 'Ingresado'";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $placa);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $row = $resultCheck->fetch_assoc();
    
    if ($row['total'] > 0) {
        $stmtCheck->close();
        $conn->close();
        return ['success' => false, 'message' => 'Ya existe un vehículo activo con esta placa'];
    }
    $stmtCheck->close();
    
    // Verificar si la columna Fotos existe en solicitudes_agendamiento
    $columna_fotos_existe = false;
    $checkColumna = "SHOW COLUMNS FROM solicitudes_agendamiento LIKE 'Fotos'";
    $resultColumna = $conn->query($checkColumna);
    if ($resultColumna && $resultColumna->num_rows > 0) {
        $columna_fotos_existe = true;
    }
    
    // Verificar que tenga una solicitud de agendamiento aprobada para hoy
    $fecha = date('Y-m-d');
    $sqlAgenda = "SELECT 
                    s.ID as SolicitudID,
                    s.Placa,
                    s.TipoVehiculo,
                    s.Marca,
                    s.Modelo,
                    s.Anio,
                    s.ConductorNombre,
                    s.Proposito,
                    s.Observaciones" . 
                    ($columna_fotos_existe ? ", s.Fotos" : "") . ",
                    a.ID as AgendaID,
                    a.Fecha as FechaAgenda,
                    a.HoraInicio,
                    a.HoraFin
                  FROM solicitudes_agendamiento s
                  INNER JOIN agenda_taller a ON s.AgendaID = a.ID
                  WHERE s.Placa = ? 
                    AND s.Estado = 'Aprobada'
                    AND a.Fecha = ?
                  ORDER BY s.FechaCreacion DESC
                  LIMIT 1";
    
    $stmtAgenda = $conn->prepare($sqlAgenda);
    $stmtAgenda->bind_param("ss", $placa, $fecha);
    $stmtAgenda->execute();
    $resultAgenda = $stmtAgenda->get_result();
    $agenda = $resultAgenda->fetch_assoc();
    $stmtAgenda->close();
    
    if (!$agenda) {
        $conn->close();
        return ['success' => false, 'message' => 'Este vehículo no tiene una hora asignada aprobada para hoy. Solo se pueden ingresar vehículos con agenda aprobada.'];
    }
    
    // Obtener las fotos de la solicitud (si existen y la columna existe)
    $fotos = NULL;
    if ($columna_fotos_existe && !empty($agenda['Fotos'])) {
        $fotos = $agenda['Fotos'];
    }
    
    // Insertar registro usando los datos de la solicitud aprobada
    $sql = "INSERT INTO ingreso_vehiculos (
        Placa, 
        TipoVehiculo, 
        Marca, 
        Modelo,
        Anio,
        ConductorNombre, 
        Proposito, 
        Observaciones" . 
        ($columna_fotos_existe ? ", Fotos" : "") . ",
        Estado, 
        EstadoIngreso, 
        FechaIngreso,
        UsuarioRegistro
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?" . 
        ($columna_fotos_existe ? ", ?" : "") . ", 'Ingresado', 'Bueno', NOW(), ?)";
    
    $stmt = $conn->prepare($sql);
    $anio = !empty($agenda['Anio']) ? intval($agenda['Anio']) : null;
    
    if ($columna_fotos_existe) {
        $stmt->bind_param("ssssissisi", 
            $agenda['Placa'],
            $agenda['TipoVehiculo'],
            $agenda['Marca'],
            $agenda['Modelo'],
            $anio,
            $agenda['ConductorNombre'],
            $agenda['Proposito'],
            $agenda['Observaciones'],
            $fotos,
            $usuario_id
        );
    } else {
        $stmt->bind_param("ssssissi", 
            $agenda['Placa'],
            $agenda['TipoVehiculo'],
            $agenda['Marca'],
            $agenda['Modelo'],
            $anio,
            $agenda['ConductorNombre'],
            $agenda['Proposito'],
            $agenda['Observaciones'],
            $usuario_id
        );
    }
    $result = $stmt->execute();
    
    $nuevo_id = $conn->insert_id;
    
    $stmt->close();
    $conn->close();
    
    return [
        'success' => $result,
        'message' => $result ? 'Ingreso registrado correctamente' : 'Error al registrar ingreso',
        'id' => $nuevo_id
    ];
}

/**
 * Registra salida de vehículo (solo guardia)
 * Solo permite salir vehículos que estén completados (terminados por el mecánico)
 */
function registrarSalidaVehiculo($placa, $usuario_id) {
    $conn = conectar_Pepsico();
    
    // Verificar si existe un vehículo completado con esa placa
    $sqlCheck = "SELECT ID, Estado FROM ingreso_vehiculos WHERE Placa = ? AND Estado = 'Completado'";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $placa);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows === 0) {
        // Verificar si está ingresado pero no completado
        $sqlIngresado = "SELECT ID, Estado FROM ingreso_vehiculos WHERE Placa = ? AND Estado = 'Ingresado'";
        $stmtIngresado = $conn->prepare($sqlIngresado);
        $stmtIngresado->bind_param("s", $placa);
        $stmtIngresado->execute();
        $resultIngresado = $stmtIngresado->get_result();
        
        if ($resultIngresado->num_rows > 0) {
            $stmtIngresado->close();
            $stmtCheck->close();
            $conn->close();
            return ['success' => false, 'message' => 'El vehículo aún está en proceso. Solo puede salir cuando el mecánico haya completado el trabajo.'];
        }
        
        $stmtIngresado->close();
        $stmtCheck->close();
        $conn->close();
        return ['success' => false, 'message' => 'No se encontró vehículo completado con esta placa. El vehículo debe estar terminado por el mecánico para poder salir.'];
    }
    
    $vehiculo = $resultCheck->fetch_assoc();
    $stmtCheck->close();
    
    // Actualizar estado a "Finalizado" o mantener "Completado" y agregar fecha de salida
    // Usamos un estado diferente para indicar que ya salió, o podemos usar un campo FechaSalida
    $sql = "UPDATE ingreso_vehiculos SET FechaSalida = NOW() WHERE ID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vehiculo['ID']);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return [
        'success' => $result,
        'message' => $result ? 'Salida registrada correctamente' : 'Error al registrar salida'
    ];
}

/**
 * Obtiene vehículos con horas agendadas aprobadas (para vista del guardia)
 */
function obtenerVehiculosAgendados($fecha = null) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        return [];
    }
    
    // Si no se proporciona fecha, usar la fecha actual
    if ($fecha === null) {
        $fecha = date('Y-m-d');
    }
    
    $vehiculos = [];
    
    try {
        $sql = "SELECT 
                    s.ID as SolicitudID,
                    s.Placa,
                    s.TipoVehiculo,
                    s.Marca,
                    s.Modelo,
                    s.Anio,
                    s.ConductorNombre,
                    s.Proposito,
                    s.Observaciones,
                    s.Estado as EstadoSolicitud,
                    s.FechaCreacion as FechaSolicitud,
                    a.ID as AgendaID,
                    a.Fecha as FechaAgenda,
                    a.HoraInicio,
                    a.HoraFin,
                    u.NombreUsuario as ChoferNombre,
                    u.Correo as ChoferCorreo,
                    sup.NombreUsuario as SupervisorNombre,
                    CASE 
                        WHEN iv.ID IS NOT NULL THEN 'Ingresado'
                        ELSE 'Pendiente Ingreso'
                    END as EstadoIngreso,
                    iv.FechaIngreso
                FROM solicitudes_agendamiento s
                INNER JOIN agenda_taller a ON s.AgendaID = a.ID
                LEFT JOIN usuarios u ON s.ChoferID = u.UsuarioID
                LEFT JOIN usuarios sup ON s.SupervisorID = sup.UsuarioID
                LEFT JOIN ingreso_vehiculos iv ON s.Placa COLLATE utf8mb4_unicode_ci = iv.Placa COLLATE utf8mb4_unicode_ci 
                    AND iv.Estado = 'Ingresado'
                WHERE s.Estado = 'Aprobada'
                    AND a.Fecha >= ?
                ORDER BY a.Fecha ASC, a.HoraInicio ASC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            return [];
        }
        
        $stmt->bind_param("s", $fecha);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Error obteniendo vehículos agendados: " . $e->getMessage());
        if ($conn) {
            $conn->close();
        }
        return [];
    }
    
    return $vehiculos;
}

/**
 * Guarda fotos del vehículo
 */
function guardarFotosVehiculo($placa, $fotosData, $usuario_id) {
    $conn = conectar_Pepsico();
    
    // Primero obtenemos las fotos actuales
    $sqlSelect = "SELECT Fotos FROM ingreso_vehiculos WHERE Placa = ? AND Estado = 'Ingresado'";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->bind_param("s", $placa);
    $stmtSelect->execute();
    $result = $stmtSelect->get_result();
    $row = $result->fetch_assoc();
    
    $fotosActuales = [];
    if ($row && $row['Fotos']) {
        $fotosActuales = json_decode($row['Fotos'], true) ?? [];
    }
    
    // Agregar nuevas fotos
    foreach ($fotosData as $foto) {
        $nuevaFoto = [
            'foto' => $foto['data'],
            'fecha' => date('Y-m-d H:i:s'),
            'usuario' => $usuario_id,
            'tipo' => $foto['tipo'],
            'angulo' => $foto['angulo']
        ];
        $fotosActuales[] = $nuevaFoto;
    }
    
    // Actualizar en base de datos
    $fotosJson = json_encode($fotosActuales);
    
    $sqlUpdate = "UPDATE ingreso_vehiculos SET Fotos = ? WHERE Placa = ? AND Estado = 'Ingresado'";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ss", $fotosJson, $placa);
    $result = $stmtUpdate->execute();
    
    $stmtSelect->close();
    $stmtUpdate->close();
    $conn->close();
    
    return $result;
}

/**
 * Asegura que los estados "Atrasado" y "No llegó" existan en el ENUM
 */
function asegurarEstadosEnum($conn) {
    try {
        $checkEnum = "SHOW COLUMNS FROM solicitudes_agendamiento WHERE Field = 'Estado'";
        $resultEnum = $conn->query($checkEnum);
        
        if ($resultEnum && $resultEnum->num_rows > 0) {
            $row = $resultEnum->fetch_assoc();
            $type = $row['Type'];
            
            // Verificar si faltan estados y agregarlos
            $necesitaActualizacion = false;
            $nuevosEstados = [];
            
            if (strpos($type, 'Atrasado') === false) {
                $necesitaActualizacion = true;
                $nuevosEstados[] = 'Atrasado';
            }
            
            if (strpos($type, 'No llegó') === false && strpos($type, 'No llego') === false) {
                $necesitaActualizacion = true;
                $nuevosEstados[] = 'No llegó';
            }
            
            if ($necesitaActualizacion) {
                // Modificar el ENUM para incluir los nuevos estados
                $sqlModify = "ALTER TABLE solicitudes_agendamiento 
                             MODIFY COLUMN Estado ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Cancelada', 'Atrasado', 'No llegó') NOT NULL DEFAULT 'Pendiente'";
                if ($conn->query($sqlModify)) {
                    error_log("ENUM actualizado para incluir nuevos estados: " . implode(', ', $nuevosEstados));
                } else {
                    error_log("Error al modificar ENUM: " . $conn->error);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error al verificar/modificar ENUM: " . $e->getMessage());
    }
}

/**
 * Marca una solicitud como atrasada (llegó dentro de los 30 minutos)
 * El proceso se cancela, permitiendo crear una nueva solicitud
 * También cancela las asignaciones de mecánico activas
 */
function marcarSolicitudAtrasada($solicitud_id) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        error_log("Error de conexión en marcarSolicitudAtrasada");
        return false;
    }
    
    // Asegurar que el estado existe en el ENUM
    asegurarEstadosEnum($conn);
    
    $solicitud_id = intval($solicitud_id);
    
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Obtener la placa de la solicitud
        $sqlPlaca = "SELECT Placa FROM solicitudes_agendamiento WHERE ID = ?";
        $stmtPlaca = $conn->prepare($sqlPlaca);
        $stmtPlaca->bind_param("i", $solicitud_id);
        $stmtPlaca->execute();
        $resultPlaca = $stmtPlaca->get_result();
        $solicitud = $resultPlaca->fetch_assoc();
        $stmtPlaca->close();
        
        if (!$solicitud) {
            mysqli_rollback($conn);
            $conn->close();
            return false;
        }
        
        $placa = $solicitud['Placa'];
        
        // 2. Actualizar el estado de la solicitud
        $sql = "UPDATE solicitudes_agendamiento 
                SET Estado = 'Atrasado', 
                    MotivoRechazo = CONCAT(IFNULL(MotivoRechazo, ''), ' | Vehículo llegó dentro del margen de atraso (0-30 minutos). Proceso cancelado.'), 
                    FechaActualizacion = NOW()
                WHERE ID = ? AND Estado = 'Aprobada'";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            mysqli_rollback($conn);
            $conn->close();
            return false;
        }
        
        $stmt->bind_param("i", $solicitud_id);
        $result = $stmt->execute();
        $stmt->close();
        
        if (!$result) {
            mysqli_rollback($conn);
            $conn->close();
            return false;
        }
        
        // 3. Cancelar asignaciones de mecánico activas para este vehículo
        $sqlCancelarAsignaciones = "UPDATE asignaciones_mecanico am
                                    INNER JOIN ingreso_vehiculos iv ON am.VehiculoID = iv.ID
                                    SET am.Estado = 'Cancelado'
                                    WHERE iv.Placa = ?
                                    AND am.Estado IN ('Asignado', 'En Proceso', 'En Revisión')";
        
        $stmtCancelar = $conn->prepare($sqlCancelarAsignaciones);
        if ($stmtCancelar) {
            $stmtCancelar->bind_param("s", $placa);
            $stmtCancelar->execute();
            $stmtCancelar->close();
        }
        
        // 4. Actualizar estado del vehículo si está ingresado
        $sqlUpdateVehiculo = "UPDATE ingreso_vehiculos 
                             SET Estado = 'Cancelado'
                             WHERE Placa = ?
                             AND Estado IN ('Ingresado', 'Asignado', 'En Proceso')";
        
        $stmtVehiculo = $conn->prepare($sqlUpdateVehiculo);
        if ($stmtVehiculo) {
            $stmtVehiculo->bind_param("s", $placa);
            $stmtVehiculo->execute();
            $stmtVehiculo->close();
        }
        
        mysqli_commit($conn);
        $conn->close();
        return true;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error al marcar solicitud como atrasada: " . $e->getMessage());
        $conn->close();
        return false;
    }
}

/**
 * Marca una solicitud como "No llegó" (pasó más de 30 minutos)
 * También cancela las asignaciones de mecánico activas
 */
function marcarSolicitudNoLlego($solicitud_id) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        error_log("Error de conexión en marcarSolicitudNoLlego");
        return false;
    }
    
    // Asegurar que el estado existe en el ENUM
    asegurarEstadosEnum($conn);
    
    $solicitud_id = intval($solicitud_id);
    
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Obtener la placa de la solicitud
        $sqlPlaca = "SELECT Placa FROM solicitudes_agendamiento WHERE ID = ?";
        $stmtPlaca = $conn->prepare($sqlPlaca);
        $stmtPlaca->bind_param("i", $solicitud_id);
        $stmtPlaca->execute();
        $resultPlaca = $stmtPlaca->get_result();
        $solicitud = $resultPlaca->fetch_assoc();
        $stmtPlaca->close();
        
        if (!$solicitud) {
            mysqli_rollback($conn);
            $conn->close();
            return false;
        }
        
        $placa = $solicitud['Placa'];
        
        // 2. Actualizar el estado de la solicitud
        $sql = "UPDATE solicitudes_agendamiento 
                SET Estado = 'No llegó', 
                    MotivoRechazo = CONCAT(IFNULL(MotivoRechazo, ''), ' | Vehículo no llegó a tiempo (pasó más de 30 minutos de la hora asignada). Proceso cerrado.'),
                    FechaActualizacion = NOW()
                WHERE ID = ? AND Estado IN ('Aprobada', 'Atrasado')";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            mysqli_rollback($conn);
            $conn->close();
            return false;
        }
        
        $stmt->bind_param("i", $solicitud_id);
        $result = $stmt->execute();
        $stmt->close();
        
        if (!$result) {
            mysqli_rollback($conn);
            $conn->close();
            return false;
        }
        
        // 3. Cancelar asignaciones de mecánico activas para este vehículo
        $sqlCancelarAsignaciones = "UPDATE asignaciones_mecanico am
                                    INNER JOIN ingreso_vehiculos iv ON am.VehiculoID = iv.ID
                                    SET am.Estado = 'Cancelado'
                                    WHERE iv.Placa = ?
                                    AND am.Estado IN ('Asignado', 'En Proceso', 'En Revisión')";
        
        $stmtCancelar = $conn->prepare($sqlCancelarAsignaciones);
        if ($stmtCancelar) {
            $stmtCancelar->bind_param("s", $placa);
            $stmtCancelar->execute();
            $stmtCancelar->close();
        }
        
        // 4. Actualizar estado del vehículo si está ingresado
        $sqlUpdateVehiculo = "UPDATE ingreso_vehiculos 
                             SET Estado = 'Cancelado'
                             WHERE Placa = ?
                             AND Estado IN ('Ingresado', 'Asignado', 'En Proceso')";
        
        $stmtVehiculo = $conn->prepare($sqlUpdateVehiculo);
        if ($stmtVehiculo) {
            $stmtVehiculo->bind_param("s", $placa);
            $stmtVehiculo->execute();
            $stmtVehiculo->close();
        }
        
        mysqli_commit($conn);
        $conn->close();
        return true;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error al marcar solicitud como no llegó: " . $e->getMessage());
        $conn->close();
        return false;
    }
}

/**
 * Verifica estado del vehículo
 * Para INGRESO: Solo retorna vehículos con solicitud de agendamiento aprobada y hora asignada
 * Para SALIDA: Retorna vehículos que están ingresados y listos para retirar
 */
function verificarEstadoVehiculo($placa, $fecha = null, $tipoOperacion = 'ingreso') {
    $conn = conectar_Pepsico();
    
    // Si no se proporciona fecha, usar la fecha actual
    if ($fecha === null) {
        $fecha = date('Y-m-d');
    }
    
    // Si es operación de SALIDA, buscar vehículos completados (terminados por mecánico)
    if ($tipoOperacion === 'salida') {
        $sql = "SELECT ID, Placa, Estado, FechaIngreso, ConductorNombre, TipoVehiculo, Marca, Modelo
                FROM ingreso_vehiculos 
                WHERE Placa = ? AND Estado = 'Completado'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $placa);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $vehiculo = $result->fetch_assoc();
        $stmt->close();
        
        if ($vehiculo) {
            $conn->close();
            return [
                'vehiculo' => $vehiculo,
                'tiene_agenda' => false,
                'puede_ingresar' => false,
                'puede_salir' => true
            ];
        } else {
            // Verificar si está ingresado pero no completado
            $sqlIngresado = "SELECT ID, Placa, Estado, FechaIngreso, ConductorNombre, TipoVehiculo, Marca, Modelo
                            FROM ingreso_vehiculos 
                            WHERE Placa = ? AND Estado = 'Ingresado'";
            
            $stmtIngresado = $conn->prepare($sqlIngresado);
            $stmtIngresado->bind_param("s", $placa);
            $stmtIngresado->execute();
            $resultIngresado = $stmtIngresado->get_result();
            $vehiculoIngresado = $resultIngresado->fetch_assoc();
            $stmtIngresado->close();
            $conn->close();
            
            if ($vehiculoIngresado) {
                return [
                    'vehiculo' => $vehiculoIngresado,
                    'tiene_agenda' => false,
                    'puede_ingresar' => false,
                    'puede_salir' => false,
                    'mensaje' => 'El vehículo aún está en proceso. Solo puede salir cuando el mecánico haya completado el trabajo.'
                ];
            }
            
            return null; // No hay vehículo ingresado para salida
        }
    }
    
    // Para INGRESO: Primero verificar si ya está ingresado
    $sql = "SELECT ID, Placa, Estado, FechaIngreso, ConductorNombre
            FROM ingreso_vehiculos 
            WHERE Placa = ? AND Estado = 'Ingresado'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $placa);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vehiculo = $result->fetch_assoc();
    $stmt->close();
    
    // Si el vehículo ya está ingresado, retornarlo (no puede ingresar de nuevo)
    if ($vehiculo) {
        $conn->close();
        return [
            'vehiculo' => $vehiculo,
            'tiene_agenda' => false,
            'puede_ingresar' => false,
            'puede_salir' => true,
            'mensaje' => 'Vehículo ya está ingresado en el patio'
        ];
    }
    
    // Si no está ingresado, verificar si tiene una solicitud de agendamiento aprobada
    // Primero buscar cualquier solicitud aprobada para esta placa
    $sqlAgenda = "SELECT 
                    s.ID as SolicitudID,
                    s.Placa,
                    s.TipoVehiculo,
                    s.Marca,
                    s.Modelo,
                    s.Anio,
                    s.ConductorNombre,
                    s.Proposito,
                    s.Observaciones,
                    a.ID as AgendaID,
                    a.Fecha as FechaAgenda,
                    a.HoraInicio,
                    a.HoraFin,
                    u.NombreUsuario as ChoferNombre
                  FROM solicitudes_agendamiento s
                  INNER JOIN agenda_taller a ON s.AgendaID = a.ID
                  LEFT JOIN usuarios u ON s.ChoferID = u.UsuarioID
                  WHERE s.Placa = ? 
                    AND s.Estado = 'Aprobada'
                  ORDER BY a.Fecha DESC, a.HoraInicio DESC
                  LIMIT 1";
    
    $stmtAgenda = $conn->prepare($sqlAgenda);
    $stmtAgenda->bind_param("s", $placa);
    $stmtAgenda->execute();
    $resultAgenda = $stmtAgenda->get_result();
    
    $solicitud = $resultAgenda->fetch_assoc();
    $stmtAgenda->close();
    
    // Si no tiene ninguna solicitud aprobada, no puede ingresar
    if (!$solicitud) {
        $conn->close();
        return null;
    }
    
    // Verificar que la fecha de la agenda sea del día actual
    $fechaActual = date('Y-m-d');
    $fechaAgenda = $solicitud['FechaAgenda'];
    
    // Si la fecha de la agenda es diferente al día actual
    if ($fechaAgenda != $fechaActual) {
        $conn->close();
        
        // Si la fecha es pasada, está atrasado
        if ($fechaAgenda < $fechaActual) {
            // Marcar la solicitud como atrasada
            marcarSolicitudAtrasada($solicitud['SolicitudID']);
            
            return [
                'vehiculo' => [
                    'ID' => null,
                    'Placa' => $solicitud['Placa'],
                    'TipoVehiculo' => $solicitud['TipoVehiculo'],
                    'Marca' => $solicitud['Marca'],
                    'Modelo' => $solicitud['Modelo'],
                    'Anio' => $solicitud['Anio'],
                    'ConductorNombre' => $solicitud['ConductorNombre'],
                    'Proposito' => $solicitud['Proposito'],
                    'Observaciones' => $solicitud['Observaciones']
                ],
                'agenda' => [
                    'SolicitudID' => $solicitud['SolicitudID'],
                    'AgendaID' => $solicitud['AgendaID'],
                    'FechaAgenda' => $solicitud['FechaAgenda'],
                    'HoraInicio' => $solicitud['HoraInicio'],
                    'HoraFin' => $solicitud['HoraFin'],
                    'ChoferNombre' => $solicitud['ChoferNombre']
                ],
                'tiene_agenda' => true,
                'puede_ingresar' => false,
                'puede_salir' => false,
                'es_atrasado' => true,
                'tipo_atraso' => 'fecha',
                'mensaje' => 'Este vehículo tiene una hora asignada para el día ' . date('d/m/Y', strtotime($fechaAgenda)) . ', pero está atrasado. No se puede permitir el ingreso.',
                'hora_actual' => date('H:i:s')
            ];
        } else {
            // Si la fecha es futura, no puede ingresar aún
            return [
                'vehiculo' => [
                    'ID' => null,
                    'Placa' => $solicitud['Placa'],
                    'TipoVehiculo' => $solicitud['TipoVehiculo'],
                    'Marca' => $solicitud['Marca'],
                    'Modelo' => $solicitud['Modelo'],
                    'Anio' => $solicitud['Anio'],
                    'ConductorNombre' => $solicitud['ConductorNombre'],
                    'Proposito' => $solicitud['Proposito'],
                    'Observaciones' => $solicitud['Observaciones']
                ],
                'agenda' => [
                    'SolicitudID' => $solicitud['SolicitudID'],
                    'AgendaID' => $solicitud['AgendaID'],
                    'FechaAgenda' => $solicitud['FechaAgenda'],
                    'HoraInicio' => $solicitud['HoraInicio'],
                    'HoraFin' => $solicitud['HoraFin'],
                    'ChoferNombre' => $solicitud['ChoferNombre']
                ],
                'tiene_agenda' => true,
                'puede_ingresar' => false,
                'puede_salir' => false,
                'mensaje' => 'Este vehículo tiene una hora asignada para el día ' . date('d/m/Y', strtotime($fechaAgenda)) . '. Solo puede ingresar en la fecha correspondiente.'
            ];
        }
    }
    
    // Si la fecha es del día actual, verificar el horario con margen de atraso
    $horaActual = date('H:i:s');
    $horaInicio = $solicitud['HoraInicio'];
    $horaFin = $solicitud['HoraFin'];
    
    // Margen de atraso: 30 minutos después de la hora de inicio
    $horaInicioTimestamp = strtotime($horaInicio);
    $horaActualTimestamp = strtotime($horaActual);
    $horaLimiteAtrasadoTimestamp = $horaInicioTimestamp + (30 * 60); // 30 minutos en segundos
    
    // Verificar si está dentro del rango permitido (desde la hora de inicio hasta 30 minutos después)
    if ($horaActualTimestamp < $horaInicioTimestamp) {
        // Llegó antes de la hora asignada
        $conn->close();
        return [
            'vehiculo' => [
                'ID' => null,
                'Placa' => $solicitud['Placa'],
                'TipoVehiculo' => $solicitud['TipoVehiculo'],
                'Marca' => $solicitud['Marca'],
                'Modelo' => $solicitud['Modelo'],
                'Anio' => $solicitud['Anio'],
                'ConductorNombre' => $solicitud['ConductorNombre'],
                'Proposito' => $solicitud['Proposito'],
                'Observaciones' => $solicitud['Observaciones']
            ],
            'agenda' => [
                'SolicitudID' => $solicitud['SolicitudID'],
                'AgendaID' => $solicitud['AgendaID'],
                'FechaAgenda' => $solicitud['FechaAgenda'],
                'HoraInicio' => $solicitud['HoraInicio'],
                'HoraFin' => $solicitud['HoraFin'],
                'ChoferNombre' => $solicitud['ChoferNombre']
            ],
            'tiene_agenda' => true,
            'puede_ingresar' => false,
            'puede_salir' => false,
            'mensaje' => 'El vehículo llegó antes de la hora asignada (' . date('H:i', strtotime($horaInicio)) . '). Debe esperar hasta su hora de cita.'
        ];
    } elseif ($horaActualTimestamp > $horaLimiteAtrasadoTimestamp) {
        // Llegó después de 30 minutos - Marcar como "No llegó" y cerrar proceso
        marcarSolicitudNoLlego($solicitud['SolicitudID']);
        
        $conn->close();
        return [
            'vehiculo' => [
                'ID' => null,
                'Placa' => $solicitud['Placa'],
                'TipoVehiculo' => $solicitud['TipoVehiculo'],
                'Marca' => $solicitud['Marca'],
                'Modelo' => $solicitud['Modelo'],
                'Anio' => $solicitud['Anio'],
                'ConductorNombre' => $solicitud['ConductorNombre'],
                'Proposito' => $solicitud['Proposito'],
                'Observaciones' => $solicitud['Observaciones']
            ],
            'agenda' => [
                'SolicitudID' => $solicitud['SolicitudID'],
                'AgendaID' => $solicitud['AgendaID'],
                'FechaAgenda' => $solicitud['FechaAgenda'],
                'HoraInicio' => $solicitud['HoraInicio'],
                'HoraFin' => $solicitud['HoraFin'],
                'ChoferNombre' => $solicitud['ChoferNombre']
            ],
            'tiene_agenda' => true,
            'puede_ingresar' => false,
            'puede_salir' => false,
            'es_atrasado' => true,
            'no_llego' => true,
            'tipo_atraso' => 'no_llego',
            'mensaje' => 'El vehículo no llegó a tiempo. Pasó más de 30 minutos de la hora asignada (' . date('H:i', strtotime($horaInicio)) . '). La solicitud ha sido marcada como "No llegó" y el proceso ha sido cerrado.',
            'hora_actual' => $horaActual
        ];
    } elseif ($horaActualTimestamp > $horaInicioTimestamp) {
        // Llegó dentro del margen de 30 minutos - Marcar como "Atrasado" y cancelar proceso
        marcarSolicitudAtrasada($solicitud['SolicitudID']);
        
        $conn->close();
        return [
            'vehiculo' => [
                'ID' => null,
                'Placa' => $solicitud['Placa'],
                'TipoVehiculo' => $solicitud['TipoVehiculo'],
                'Marca' => $solicitud['Marca'],
                'Modelo' => $solicitud['Modelo'],
                'Anio' => $solicitud['Anio'],
                'ConductorNombre' => $solicitud['ConductorNombre'],
                'Proposito' => $solicitud['Proposito'],
                'Observaciones' => $solicitud['Observaciones']
            ],
            'agenda' => [
                'SolicitudID' => $solicitud['SolicitudID'],
                'AgendaID' => $solicitud['AgendaID'],
                'FechaAgenda' => $solicitud['FechaAgenda'],
                'HoraInicio' => $solicitud['HoraInicio'],
                'HoraFin' => $solicitud['HoraFin'],
                'ChoferNombre' => $solicitud['ChoferNombre']
            ],
            'tiene_agenda' => true,
            'puede_ingresar' => false, // No puede ingresar - proceso cancelado
            'puede_salir' => false,
            'es_atrasado' => true,
            'tipo_atraso' => 'hora',
            'mensaje' => 'El vehículo llegó dentro del margen de atraso permitido (30 minutos), pero el proceso ha sido cancelado. La solicitud ha sido marcada como "Atrasado". Debe crear una nueva solicitud de agendamiento.',
            'hora_actual' => $horaActual
        ];
    }
    
    // Si está dentro del rango permitido, puede ingresar
    $conn->close();
    return [
        'vehiculo' => [
            'ID' => null,
            'Placa' => $solicitud['Placa'],
            'TipoVehiculo' => $solicitud['TipoVehiculo'],
            'Marca' => $solicitud['Marca'],
            'Modelo' => $solicitud['Modelo'],
            'Anio' => $solicitud['Anio'],
            'ConductorNombre' => $solicitud['ConductorNombre'],
            'Proposito' => $solicitud['Proposito'],
            'Observaciones' => $solicitud['Observaciones']
        ],
        'agenda' => [
            'SolicitudID' => $solicitud['SolicitudID'],
            'AgendaID' => $solicitud['AgendaID'],
            'FechaAgenda' => $solicitud['FechaAgenda'],
            'HoraInicio' => $solicitud['HoraInicio'],
            'HoraFin' => $solicitud['HoraFin'],
            'ChoferNombre' => $solicitud['ChoferNombre']
        ],
        'tiene_agenda' => true,
        'puede_ingresar' => true,
        'puede_salir' => false
    ];
}
?>