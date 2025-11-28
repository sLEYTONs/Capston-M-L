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
    $sqlCheck = "SELECT ID, Estado FROM ingreso_vehiculos WHERE Placa = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $placa);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $vehiculoExistente = $resultCheck->fetch_assoc();
    $stmtCheck->close();
    
    // Si existe un vehículo con estado 'Ingresado', no permitir duplicado
    if ($vehiculoExistente && $vehiculoExistente['Estado'] === 'Ingresado') {
        $conn->close();
        return ['success' => false, 'message' => 'Ya existe un vehículo activo con esta placa'];
    }
    
    // Si existe un vehículo con otro estado, lo actualizaremos en lugar de insertar
    $vehiculo_id_existente = $vehiculoExistente ? $vehiculoExistente['ID'] : null;
    
    // Verificar si la columna Fotos existe en solicitudes_agendamiento
    $columna_fotos_existe = false;
    $checkColumna = "SHOW COLUMNS FROM solicitudes_agendamiento LIKE 'Fotos'";
    $resultColumna = $conn->query($checkColumna);
    if ($resultColumna && $resultColumna->num_rows > 0) {
        $columna_fotos_existe = true;
    }
    
    // Verificar que tenga una solicitud de agendamiento aprobada o atrasada para hoy
    // Incluimos 'Atrasado' porque un vehículo puede llegar entre 10-30 minutos después y aún puede ingresar
    // 
    // IMPORTANTE: Esta consulta busca en la tabla solicitudes_agendamiento (s)
    // La columna Proposito existe en solicitudes_agendamiento (s.Proposito) pero NO en ingreso_vehiculos
    // Por eso NO se inserta Proposito en ingreso_vehiculos, solo se usa para verificar la agenda
    $fecha = date('Y-m-d');
    
    // Log para debugging
    error_log("registrarIngresoBasico - Buscando placa: $placa, fecha: $fecha");
    
    $sqlAgenda = "SELECT 
                    s.ID as SolicitudID,
                    s.Placa,
                    s.TipoVehiculo,
                    s.Marca,
                    s.Modelo,
                    s.Anio,
                    s.ConductorNombre,
                    s.Proposito,  -- Esta columna existe en solicitudes_agendamiento, se usa solo para referencia
                    s.Estado as EstadoSolicitud,
                    a.ID as AgendaID,
                    a.Fecha as FechaAgenda,
                    a.HoraInicio,
                    a.HoraFin
                  FROM solicitudes_agendamiento s
                  INNER JOIN agenda_taller a ON s.AgendaID = a.ID
                  WHERE s.Placa = ? 
                    AND s.Estado IN ('Aprobada', 'Atrasado')
                    AND s.AgendaID IS NOT NULL
                    AND a.Fecha = ?
                  ORDER BY s.FechaCreacion DESC
                  LIMIT 1";
    
    $stmtAgenda = $conn->prepare($sqlAgenda);
    if (!$stmtAgenda) {
        error_log("Error preparando consulta de agenda en registrarIngresoBasico: " . mysqli_error($conn));
        $conn->close();
        return ['success' => false, 'message' => 'Error al verificar la agenda del vehículo'];
    }
    
    $stmtAgenda->bind_param("ss", $placa, $fecha);
    
    if (!$stmtAgenda->execute()) {
        error_log("Error ejecutando consulta de agenda en registrarIngresoBasico: " . mysqli_stmt_error($stmtAgenda));
        $stmtAgenda->close();
        $conn->close();
        return ['success' => false, 'message' => 'Error al verificar la agenda del vehículo'];
    }
    
    $resultAgenda = $stmtAgenda->get_result();
    $agenda = $resultAgenda->fetch_assoc();
    $stmtAgenda->close();
    
    if (!$agenda) {
        // Log para debugging - verificar qué solicitudes existen para esta placa
        $sqlDebug = "SELECT s.ID, s.Placa, s.Estado, a.Fecha as FechaAgenda, DATE(a.Fecha) as FechaAgendaDate
                    FROM solicitudes_agendamiento s
                    LEFT JOIN agenda_taller a ON s.AgendaID = a.ID
                    WHERE s.Placa = ? AND s.AgendaID IS NOT NULL
                    ORDER BY s.FechaCreacion DESC
                    LIMIT 5";
        $stmtDebug = $conn->prepare($sqlDebug);
        if ($stmtDebug) {
            $stmtDebug->bind_param("s", $placa);
            $stmtDebug->execute();
            $resultDebug = $stmtDebug->get_result();
            $debugInfo = [];
            while ($row = $resultDebug->fetch_assoc()) {
                $debugInfo[] = $row;
            }
            error_log("Debug - Solicitudes encontradas para placa $placa: " . json_encode($debugInfo));
            error_log("Debug - Fecha buscada: $fecha");
            $stmtDebug->close();
        }
        
        $conn->close();
        return ['success' => false, 'message' => 'Este vehículo no tiene una hora asignada aprobada o atrasada para hoy. Solo se pueden ingresar vehículos con agenda aprobada o que hayan llegado dentro del margen permitido.'];
    }
    
    error_log("registrarIngresoBasico - Agenda encontrada: ID=" . $agenda['AgendaID'] . ", Estado=" . ($agenda['EstadoSolicitud'] ?? 'N/A') . ", Fecha=" . $agenda['FechaAgenda']);
    
    // Verificar qué columnas existen en la tabla ingreso_vehiculos
    $columnas_existentes = [];
    $checkColumnas = "SHOW COLUMNS FROM ingreso_vehiculos";
    $resultColumnas = $conn->query($checkColumnas);
    if ($resultColumnas) {
        while ($row = $resultColumnas->fetch_assoc()) {
            $columnas_existentes[] = $row['Field'];
        }
    }
    
    // Construir INSERT solo con las columnas que existen en la tabla
    // Campos básicos requeridos
    $campos = ["Placa", "TipoVehiculo", "Marca", "Modelo", "ConductorNombre", "Estado", "FechaIngreso", "UsuarioRegistro"];
    $valores = ["?", "?", "?", "?", "?", "'Ingresado'", "NOW()", "?"];
    $tipos = "sssssi";
    $parametros = [
        $agenda['Placa'],
        $agenda['TipoVehiculo'],
        $agenda['Marca'],
        $agenda['Modelo'],
        $agenda['ConductorNombre'],
        $usuario_id
    ];
    
    // Agregar Anio si existe la columna y tiene valor
    if (in_array('Anio', $columnas_existentes)) {
        $campos[] = "Anio";
        $anio = !empty($agenda['Anio']) ? intval($agenda['Anio']) : null;
        if ($anio !== null) {
            $valores[] = "?";
            $tipos .= "i";
            $parametros[] = $anio;
        } else {
            $valores[] = "NULL";
        }
    }
    
    // Agregar FechaRegistro si existe la columna
    if (in_array('FechaRegistro', $columnas_existentes)) {
        $campos[] = "FechaRegistro";
        $valores[] = "NOW()";
    }
    
    // Agregar EstadoIngreso si existe la columna
    if (in_array('EstadoIngreso', $columnas_existentes)) {
        $campos[] = "EstadoIngreso";
        $valores[] = "'Bueno'";
    }
    
    // NOTA: Proposito y Observaciones NO se insertan porque no existen en ingreso_vehiculos
    // Esos datos están en la tabla solicitudes_agendamiento (s.Proposito, s.Observaciones)
    
    // Si ya existe un registro con esa placa, actualizarlo en lugar de insertar
    if ($vehiculo_id_existente) {
        // Construir UPDATE - mapear campos con sus valores correctamente
        $updateCampos = [];
        $updateTipos = "";
        $updateParametros = [];
        $paramIndex = 0; // Índice para rastrear la posición en $parametros
        
        // Mapear campos con valores, excluyendo Placa
        foreach ($campos as $index => $campo) {
            if ($campo !== 'Placa') {
                if ($valores[$index] === '?') {
                    // Es un parámetro - obtener el tipo y valor del array de parámetros
                    $updateCampos[] = "$campo = ?";
                    $updateTipos .= substr($tipos, $paramIndex, 1);
                    $updateParametros[] = $parametros[$paramIndex];
                    $paramIndex++;
                } else {
                    // Es un valor literal (NOW(), 'Ingresado', etc.)
                    $updateCampos[] = "$campo = " . $valores[$index];
                }
            } else {
                // Si es Placa y es un parámetro, saltarlo pero incrementar el índice
                if ($valores[$index] === '?') {
                    $paramIndex++;
                }
            }
        }
        
        // Agregar ID al final de los parámetros
        $updateTipos .= "i";
        $updateParametros[] = $vehiculo_id_existente;
        
        $sql = "UPDATE ingreso_vehiculos SET " . implode(", ", $updateCampos) . " WHERE ID = ?";
        
        error_log("SQL UPDATE en registrarIngresoBasico: " . $sql);
        error_log("Tipos UPDATE: " . $updateTipos);
        error_log("Parámetros UPDATE: " . print_r($updateParametros, true));
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparando UPDATE en registrarIngresoBasico: " . mysqli_error($conn));
            $conn->close();
            return ['success' => false, 'message' => 'Error al preparar la consulta de actualización: ' . mysqli_error($conn)];
        }
        
        // Usar call_user_func_array para bind_param dinámico
        $bindParams = array_merge([$updateTipos], $updateParametros);
        $refs = [];
        foreach ($bindParams as $key => $value) {
            $refs[$key] = &$bindParams[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Error ejecutando UPDATE en registrarIngresoBasico: " . mysqli_stmt_error($stmt));
            $errorMsg = mysqli_stmt_error($stmt);
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Error al actualizar ingreso: ' . $errorMsg];
        }
        
        $nuevo_id = $vehiculo_id_existente;
        $stmt->close();
    } else {
        // No existe, hacer INSERT
        $sql = "INSERT INTO ingreso_vehiculos (" . implode(", ", $campos) . ") VALUES (" . implode(", ", $valores) . ")";
        
        error_log("SQL INSERT en registrarIngresoBasico: " . $sql);
        error_log("Parámetros: " . print_r($parametros, true));
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparando INSERT en registrarIngresoBasico: " . mysqli_error($conn));
            $conn->close();
            return ['success' => false, 'message' => 'Error al preparar la consulta de ingreso: ' . mysqli_error($conn)];
        }
        
        // Usar call_user_func_array para bind_param dinámico
        $bindParams = array_merge([$tipos], $parametros);
        $refs = [];
        foreach ($bindParams as $key => $value) {
            $refs[$key] = &$bindParams[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Error ejecutando INSERT en registrarIngresoBasico: " . mysqli_stmt_error($stmt));
            $errorMsg = mysqli_stmt_error($stmt);
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Error al registrar ingreso: ' . $errorMsg];
        }
        
        $nuevo_id = $conn->insert_id;
        $stmt->close();
    }
    
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
    
    // Verificar si existe la columna Estado en la tabla
    $checkEstado = "SHOW COLUMNS FROM ingreso_vehiculos LIKE 'Estado'";
    $resultEstado = $conn->query($checkEstado);
    $tieneEstado = ($resultEstado && $resultEstado->num_rows > 0);
    
    // Verificar si existe la columna FechaSalida
    $checkFechaSalida = "SHOW COLUMNS FROM ingreso_vehiculos LIKE 'FechaSalida'";
    $resultFechaSalida = $conn->query($checkFechaSalida);
    $tieneFechaSalida = ($resultFechaSalida && $resultFechaSalida->num_rows > 0);
    
    // Construir la consulta UPDATE dinámicamente
    $camposUpdate = [];
    
    if ($tieneFechaSalida) {
        $camposUpdate[] = "FechaSalida = NOW()";
    }
    
    if ($tieneEstado) {
        // Cambiar estado a "Finalizado" si existe, de lo contrario mantener "Completado"
        $camposUpdate[] = "Estado = 'Finalizado'";
    }
    
    if (empty($camposUpdate)) {
        $conn->close();
        return [
            'success' => false,
            'message' => 'No se encontraron columnas para actualizar la salida'
        ];
    }
    
    $sql = "UPDATE ingreso_vehiculos SET " . implode(", ", $camposUpdate) . " WHERE ID = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error preparando UPDATE en registrarSalidaVehiculo: " . mysqli_error($conn));
        $conn->close();
        return [
            'success' => false,
            'message' => 'Error al preparar la consulta de salida: ' . mysqli_error($conn)
        ];
    }
    
    $stmt->bind_param("i", $vehiculo['ID']);
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Error ejecutando UPDATE en registrarSalidaVehiculo: " . mysqli_stmt_error($stmt));
        $errorMsg = mysqli_stmt_error($stmt);
        $stmt->close();
        $conn->close();
        return [
            'success' => false,
            'message' => 'Error al registrar salida: ' . $errorMsg
        ];
    }
    
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
        // Asegurar que la fecha esté en formato YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            error_log("Formato de fecha inválido en obtenerVehiculosAgendados: " . $fecha);
            $fecha = date('Y-m-d');
        }
        
        // Consulta para obtener vehículos agendados para la fecha especificada
        // Incluye estados: Aprobada, Atrasado (puede tener agenda válida), y No llegó (para mostrar histórico)
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
                WHERE s.AgendaID IS NOT NULL
                    AND DATE(a.Fecha) = ?
                    AND s.Estado IN ('Aprobada', 'Atrasado', 'No llegó')
                ORDER BY a.HoraInicio ASC";
        
        error_log("obtenerVehiculosAgendados - Fecha buscada: " . $fecha);
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparando consulta en obtenerVehiculosAgendados: " . mysqli_error($conn));
            $conn->close();
            return [];
        }
        
        $stmt->bind_param("s", $fecha);
        
        if (!$stmt->execute()) {
            error_log("Error ejecutando consulta en obtenerVehiculosAgendados: " . mysqli_stmt_error($stmt));
            $stmt->close();
            $conn->close();
            return [];
        }
        
        $result = $stmt->get_result();
        $count = 0;
        
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
            $count++;
            error_log("Vehículo encontrado - Placa: " . $row['Placa'] . ", Estado: " . $row['EstadoSolicitud'] . ", Fecha: " . $row['FechaAgenda']);
        }
        
        error_log("obtenerVehiculosAgendados - Total vehículos encontrados: " . $count);
        
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
    
    // Buscar el vehículo por placa, incluyendo estados 'Ingresado', 'Completado' y 'Finalizado'
    // Esto permite guardar fotos tanto al ingresar como al salir
    $sqlSelect = "SELECT ID, Fotos, Estado FROM ingreso_vehiculos 
                  WHERE Placa = ? AND Estado IN ('Ingresado', 'Completado', 'Finalizado')
                  ORDER BY FechaIngreso DESC
                  LIMIT 1";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->bind_param("s", $placa);
    $stmtSelect->execute();
    $result = $stmtSelect->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        $stmtSelect->close();
        $conn->close();
        error_log("No se encontró vehículo con placa $placa para guardar fotos");
        return false;
    }
    
    $vehiculo_id = $row['ID'];
    $fotosActuales = [];
    if ($row['Fotos']) {
        $fotosActuales = json_decode($row['Fotos'], true) ?? [];
    }
    
    // Agregar nuevas fotos
    foreach ($fotosData as $foto) {
        $nuevaFoto = [
            'foto' => $foto['data'],
            'fecha' => date('Y-m-d H:i:s'),
            'usuario' => $usuario_id,
            'tipo' => $foto['tipo'] ?? 'foto_vehiculo',
            'angulo' => $foto['angulo'] ?? 'general'
        ];
        $fotosActuales[] = $nuevaFoto;
    }
    
    // Actualizar en base de datos usando el ID del vehículo
    $fotosJson = json_encode($fotosActuales);
    
    $sqlUpdate = "UPDATE ingreso_vehiculos SET Fotos = ? WHERE ID = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("si", $fotosJson, $vehiculo_id);
    $result = $stmtUpdate->execute();
    
    if (!$result) {
        error_log("Error al guardar fotos: " . mysqli_stmt_error($stmtUpdate));
    }
    
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
    
    // Si no está ingresado, verificar si tiene una solicitud de agendamiento aprobada o atrasada
    // Incluimos 'Atrasado' porque un vehículo puede llegar entre 10-30 minutos después y aún puede ingresar
    // Buscar solicitud aprobada o atrasada para esta placa y fecha actual
    $fechaActual = date('Y-m-d');
    
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
                    s.Estado as EstadoSolicitud,
                    a.ID as AgendaID,
                    a.Fecha as FechaAgenda,
                    a.HoraInicio,
                    a.HoraFin,
                    u.NombreUsuario as ChoferNombre
                  FROM solicitudes_agendamiento s
                  INNER JOIN agenda_taller a ON s.AgendaID = a.ID
                  LEFT JOIN usuarios u ON s.ChoferID = u.UsuarioID
                  WHERE s.Placa = ? 
                    AND s.Estado IN ('Aprobada', 'Atrasado')
                    AND s.AgendaID IS NOT NULL
                    AND a.Fecha = ?
                  ORDER BY a.Fecha DESC, a.HoraInicio DESC
                  LIMIT 1";
    
    $stmtAgenda = $conn->prepare($sqlAgenda);
    if (!$stmtAgenda) {
        error_log("Error preparando consulta de agenda en verificarEstadoVehiculo: " . mysqli_error($conn));
        $conn->close();
        return null;
    }
    
    $stmtAgenda->bind_param("ss", $placa, $fechaActual);
    
    if (!$stmtAgenda->execute()) {
        error_log("Error ejecutando consulta de agenda en verificarEstadoVehiculo: " . mysqli_stmt_error($stmtAgenda));
        $stmtAgenda->close();
        $conn->close();
        return null;
    }
    
    $resultAgenda = $stmtAgenda->get_result();
    $solicitud = $resultAgenda->fetch_assoc();
    $stmtAgenda->close();
    
    // Si no tiene ninguna solicitud aprobada o atrasada para hoy, no puede ingresar
    if (!$solicitud) {
        $conn->close();
        return null;
    }
    
    // Verificar que la fecha de la agenda sea del día actual (doble verificación)
    $fechaAgenda = $solicitud['FechaAgenda'];
    
    // Normalizar fechas para comparación (asegurar formato YYYY-MM-DD)
    if (is_string($fechaAgenda)) {
        $fechaAgenda = date('Y-m-d', strtotime($fechaAgenda));
    }
    
    // Si la fecha de la agenda es diferente al día actual (esto no debería pasar si la consulta está bien)
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
    
    // Si la fecha es del día actual, verificar el horario con márgenes de tiempo
    $horaActual = date('H:i:s');
    $horaInicio = $solicitud['HoraInicio'];
    $horaFin = $solicitud['HoraFin'];
    
    // Calcular timestamps y márgenes
    $horaInicioTimestamp = strtotime($horaInicio);
    $horaActualTimestamp = strtotime($horaActual);
    
    // Márgenes: 10 minutos antes, 10 minutos después (margen normal), 30 minutos después (límite atrasado)
    $horaLimiteAntesTimestamp = $horaInicioTimestamp - (10 * 60); // 10 minutos antes
    $horaLimiteNormalTimestamp = $horaInicioTimestamp + (10 * 60); // 10 minutos después
    $horaLimiteAtrasadoTimestamp = $horaInicioTimestamp + (30 * 60); // 30 minutos después
    
    // Calcular diferencia en minutos
    $diferenciaSegundos = $horaActualTimestamp - $horaInicioTimestamp;
    $diferenciaMinutos = round($diferenciaSegundos / 60);
    
    // Caso 1: Llegó más de 10 minutos antes - No puede ingresar (debe esperar)
    if ($horaActualTimestamp < $horaLimiteAntesTimestamp) {
        $minutosAntes = abs($diferenciaMinutos);
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
            'mensaje' => "El vehículo llegó {$minutosAntes} minutos antes de la hora asignada (" . date('H:i', strtotime($horaInicio)) . "). Debe esperar hasta 10 minutos antes de su hora de cita."
        ];
    }
    
    // Caso 2: Llegó más de 30 minutos después - No puede ingresar, marcar como "No llegó"
    if ($horaActualTimestamp > $horaLimiteAtrasadoTimestamp) {
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
            'mensaje' => 'El vehículo no llegó a tiempo. Pasó más de 30 minutos de la hora asignada (' . date('H:i', strtotime($horaInicio)) . '). La solicitud ha sido marcada como "No llegó" y el proceso ha sido cerrado. Debe crear una nueva solicitud de agendamiento.',
            'hora_actual' => $horaActual
        ];
    }
    
    // Caso 3: Llegó entre 10 y 30 minutos después - Puede ingresar pero se marca como "Atrasado"
    // O si el estado ya es 'Atrasado' y la fecha coincide con hoy, permitir ingreso directamente
    $estadoSolicitud = $solicitud['EstadoSolicitud'] ?? 'Aprobada';
    
    if ($estadoSolicitud === 'Atrasado') {
        // Si ya está marcado como atrasado y la fecha es de hoy, permitir ingreso directamente
        // (ya fue marcado como atrasado anteriormente, no necesitamos volver a marcarlo)
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
            'puede_ingresar' => true, // Puede ingresar aunque esté marcado como atrasado
            'puede_salir' => false,
            'es_atrasado' => true,
            'tipo_atraso' => 'hora',
            'mensaje' => "El vehículo tiene una solicitud marcada como 'Atrasado' para hoy. Puede proceder con el ingreso.",
            'hora_actual' => $horaActual
        ];
    }
    
    if ($horaActualTimestamp > $horaLimiteNormalTimestamp && $horaActualTimestamp <= $horaLimiteAtrasadoTimestamp) {
        // Marcar como atrasado pero permitir ingreso
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
            'puede_ingresar' => true, // Puede ingresar aunque llegó atrasado
            'puede_salir' => false,
            'es_atrasado' => true,
            'tipo_atraso' => 'hora',
            'mensaje' => "El vehículo llegó con {$diferenciaMinutos} minutos de atraso. La solicitud ha sido marcada como 'Atrasado', pero puede proceder con el ingreso.",
            'hora_actual' => $horaActual
        ];
    }
    
    // Caso 4: Llegó dentro del margen normal (10 minutos antes hasta 10 minutos después) - Puede ingresar normalmente
    // Este caso cubre: desde 10 minutos antes hasta 10 minutos después de la hora asignada
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