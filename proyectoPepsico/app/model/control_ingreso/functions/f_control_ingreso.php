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
 */
function registrarSalidaVehiculo($placa, $usuario_id) {
    $conn = conectar_Pepsico();
    
    // Verificar si existe un vehículo activo con esa placa
    $sqlCheck = "SELECT ID FROM ingreso_vehiculos WHERE Placa = ? AND Estado = 'Ingresado'";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $placa);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    
    if ($resultCheck->num_rows === 0) {
        $stmtCheck->close();
        $conn->close();
        return ['success' => false, 'message' => 'No se encontró vehículo activo con esta placa'];
    }
    
    $vehiculo = $resultCheck->fetch_assoc();
    $stmtCheck->close();
    
    // Actualizar estado a "Completado" (que representa la salida)
    $sql = "UPDATE ingreso_vehiculos SET Estado = 'Completado', FechaSalida = NOW() WHERE ID = ?";
    
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
    
    // Si es operación de SALIDA, buscar vehículos ingresados
    if ($tipoOperacion === 'salida') {
        $sql = "SELECT ID, Placa, Estado, FechaIngreso, ConductorNombre, TipoVehiculo, Marca, Modelo
                FROM ingreso_vehiculos 
                WHERE Placa = ? AND Estado = 'Ingresado'";
        
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
            $conn->close();
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
    
    // Si no está ingresado, verificar si tiene una solicitud de agendamiento aprobada para hoy
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
                    AND a.Fecha = ?
                  ORDER BY s.FechaCreacion DESC
                  LIMIT 1";
    
    $stmtAgenda = $conn->prepare($sqlAgenda);
    $stmtAgenda->bind_param("ss", $placa, $fecha);
    $stmtAgenda->execute();
    $resultAgenda = $stmtAgenda->get_result();
    
    $solicitud = $resultAgenda->fetch_assoc();
    $stmtAgenda->close();
    $conn->close();
    
    // Si encontró una solicitud aprobada, retornar los datos del vehículo de la solicitud
    if ($solicitud) {
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
    
    // Si no tiene agenda aprobada, no puede ingresar
    return null;
}
?>