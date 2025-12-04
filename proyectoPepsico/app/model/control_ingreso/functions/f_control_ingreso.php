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
 * Obtiene los movimientos del día (ingresos y salidas)
 */
function obtenerMovimientosDelDia() {
    try {
        $conn = conectar_Pepsico();
        
        if (!$conn) {
            error_log("Error: No se pudo conectar a la base de datos");
            return [];
        }
        
        // Verificar nombres de columnas reales
        $columnas = [];
        $resultColumnas = $conn->query("SHOW COLUMNS FROM ingreso_vehiculos");
        if ($resultColumnas) {
            while ($col = $resultColumnas->fetch_assoc()) {
                $columnas[strtolower($col['Field'])] = $col['Field'];
            }
        }
        
        // Determinar nombres de columnas a usar
        $placaCol = $columnas['placa'] ?? 'Placa';
        $fechaIngresoCol = $columnas['fechaingreso'] ?? 'FechaIngreso';
        $fechaSalidaCol = $columnas['fechasalida'] ?? 'FechaSalida';
        $estadoCol = $columnas['estado'] ?? 'Estado';
        $marcaCol = $columnas['marca'] ?? 'Marca';
        $modeloCol = $columnas['modelo'] ?? 'Modelo';
        $tipoVehiculoCol = $columnas['tipovehiculo'] ?? 'TipoVehiculo';
        $conductorCol = $columnas['conductornombre'] ?? ($columnas['conductor_nombre'] ?? 'ConductorNombre');
    
        // Obtener ingresos del día
        $sqlIngresos = "SELECT 
                        iv.{$placaCol} as Placa,
                        iv.{$tipoVehiculoCol} as TipoVehiculo,
                        iv.{$marcaCol} as Marca,
                        iv.{$modeloCol} as Modelo,
                        iv.{$conductorCol} as ConductorNombre,
                        iv.{$fechaIngresoCol} as FechaIngreso,
                        iv.{$estadoCol} as Estado
                    FROM ingreso_vehiculos iv
                    WHERE DATE(iv.{$fechaIngresoCol}) = CURDATE()
                    ORDER BY iv.{$fechaIngresoCol} DESC";
    
        $resultIngresos = $conn->query($sqlIngresos);
        $ingresos = [];
        
        if ($resultIngresos) {
            while ($row = $resultIngresos->fetch_assoc()) {
                $ingresos[] = [
                    'placa' => $row['Placa'] ?? '',
                    'tipo_vehiculo' => $row['TipoVehiculo'] ?? '',
                    'marca' => $row['Marca'] ?? '',
                    'modelo' => $row['Modelo'] ?? '',
                    'conductor' => $row['ConductorNombre'] ?? 'N/A',
                    'fecha' => $row['FechaIngreso'] ?? '',
                    'estado' => $row['Estado'] ?? '',
                    'tipo_movimiento' => 'Ingreso'
                ];
            }
        } else {
            error_log("Error en consulta de ingresos: " . $conn->error);
        }
    
        // Obtener salidas del día (solo si existe la columna FechaSalida)
        $salidas = [];
        if ($fechaSalidaCol) {
            $sqlSalidas = "SELECT 
                            iv.{$placaCol} as Placa,
                            iv.{$tipoVehiculoCol} as TipoVehiculo,
                            iv.{$marcaCol} as Marca,
                            iv.{$modeloCol} as Modelo,
                            iv.{$conductorCol} as ConductorNombre,
                            iv.{$fechaSalidaCol} as FechaSalida,
                            iv.{$estadoCol} as Estado
                        FROM ingreso_vehiculos iv
                        WHERE iv.{$fechaSalidaCol} IS NOT NULL
                            AND DATE(iv.{$fechaSalidaCol}) = CURDATE()
                        ORDER BY iv.{$fechaSalidaCol} DESC";
            
            $resultSalidas = $conn->query($sqlSalidas);
            
            if ($resultSalidas) {
                while ($row = $resultSalidas->fetch_assoc()) {
                    $salidas[] = [
                        'placa' => $row['Placa'] ?? '',
                        'tipo_vehiculo' => $row['TipoVehiculo'] ?? '',
                        'marca' => $row['Marca'] ?? '',
                        'modelo' => $row['Modelo'] ?? '',
                        'conductor' => $row['ConductorNombre'] ?? 'N/A',
                        'fecha' => $row['FechaSalida'] ?? '',
                        'estado' => $row['Estado'] ?? '',
                        'tipo_movimiento' => 'Salida'
                    ];
                }
            } else {
                error_log("Error en consulta de salidas: " . $conn->error);
            }
        }
    
        // Combinar y ordenar por fecha (más reciente primero)
        $movimientos = array_merge($ingresos, $salidas);
        usort($movimientos, function($a, $b) {
            $timeA = strtotime($a['fecha'] ?? '1970-01-01');
            $timeB = strtotime($b['fecha'] ?? '1970-01-01');
            return $timeB - $timeA;
        });
    
        $conn->close();
        
        return array_slice($movimientos, 0, 20); // Limitar a los últimos 20 movimientos
    } catch (Exception $e) {
        error_log("Error en obtenerMovimientosDelDia: " . $e->getMessage());
        if (isset($conn)) {
            $conn->close();
        }
        return [];
    }
}

/**
 * Registra ingreso de vehículo con hora asignada (solo guardia)
 * Solo permite ingresar vehículos que tengan una solicitud de agendamiento aprobada
 */
function registrarIngresoBasico($placa, $usuario_id, $motivo_retraso = null) {
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
    
    // Agregar MotivoRetraso si existe la columna y se proporcionó un motivo
    if (in_array('MotivoRetraso', $columnas_existentes) && !empty($motivo_retraso)) {
        $campos[] = "MotivoRetraso";
        $valores[] = "?";
        $tipos .= "s";
        $parametros[] = $motivo_retraso;
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
    
    // Verificar si existe la columna FechaSalida (puede estar en mayúsculas o minúsculas)
    $checkFechaSalida = "SHOW COLUMNS FROM ingreso_vehiculos WHERE Field IN ('FechaSalida', 'fechasalida')";
    $resultFechaSalida = $conn->query($checkFechaSalida);
    $tieneFechaSalida = ($resultFechaSalida && $resultFechaSalida->num_rows > 0);
    
    // Obtener el nombre exacto de la columna si existe
    $nombreColumnaFechaSalida = null;
    if ($tieneFechaSalida) {
        $row = $resultFechaSalida->fetch_assoc();
        $nombreColumnaFechaSalida = $row['Field']; // Usar el nombre exacto de la columna
    }
    
    // Construir la consulta UPDATE dinámicamente
    $camposUpdate = [];
    
    if ($nombreColumnaFechaSalida) {
        $camposUpdate[] = "`{$nombreColumnaFechaSalida}` = NOW()";
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
function obtenerVehiculosAgendados($fecha = null, $rol = null, $soloPendientes = false) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        return [];
    }
    
    // Si el rol es Guardia, mostrar todo el historial sin filtrar por fecha
    $esGuardia = ($rol === 'Guardia');
    
    // Si no se proporciona fecha y no es Guardia, usar la fecha actual
    if ($fecha === null && !$esGuardia) {
        $fecha = date('Y-m-d');
    }
    
    $vehiculos = [];
    
    try {
        // Consulta para obtener vehículos agendados
        // Si es Guardia, muestra todo el historial; si no, filtra por fecha
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
                        WHEN iv.ID IS NOT NULL AND iv.Estado = 'Completado' THEN 'Completado'
                        WHEN iv.ID IS NOT NULL AND iv.Estado = 'Finalizado' THEN 'Completado'
                        WHEN iv.ID IS NOT NULL AND iv.FechaSalida IS NOT NULL THEN 'Completado'
                        WHEN iv.ID IS NOT NULL AND iv.Estado = 'Ingresado' THEN 'Ingresado'
                        WHEN iv.ID IS NOT NULL AND iv.Estado = 'Asignado' THEN 'Ingresado'
                        WHEN iv.ID IS NOT NULL THEN COALESCE(iv.Estado, 'Ingresado')
                        WHEN iv.ID IS NULL AND s.Estado = 'Completada' THEN 'Completado'
                        WHEN iv.ID IS NULL THEN NULL
                        ELSE 'Pendiente Ingreso'
                    END as EstadoIngreso,
                    iv.FechaIngreso,
                    iv.Estado as EstadoVehiculo
                FROM solicitudes_agendamiento s
                INNER JOIN agenda_taller a ON s.AgendaID = a.ID
                LEFT JOIN usuarios u ON s.ChoferID = u.UsuarioID
                LEFT JOIN usuarios sup ON s.SupervisorID = sup.UsuarioID
                LEFT JOIN ingreso_vehiculos iv ON s.Placa COLLATE utf8mb4_unicode_ci = iv.Placa COLLATE utf8mb4_unicode_ci
                WHERE s.AgendaID IS NOT NULL
                    AND s.Estado IN ('Aprobada', 'Atrasado', 'No llegó')";
        
        // Si solo se quieren pendientes, filtrar por EstadoIngreso
        // Excluir los que están "Ingresado" o "Asignado", pero incluir "Completado" para que el guardia pueda verlos
        if ($soloPendientes) {
            $sql .= " AND (iv.ID IS NULL OR iv.Estado = 'Completado')";
        } else {
            // Si no es solo pendientes, mostrar todos: pendientes, ingresados y completados
            // No agregar filtro adicional
        }
        
        // Si no es Guardia, agregar filtro de fecha
        if (!$esGuardia) {
            // Asegurar que la fecha esté en formato YYYY-MM-DD
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                error_log("Formato de fecha inválido en obtenerVehiculosAgendados: " . $fecha);
                $fecha = date('Y-m-d');
            }
            $sql .= " AND DATE(a.Fecha) = ?";
        }
        
        $sql .= " ORDER BY a.Fecha DESC, a.HoraInicio ASC";
        
        error_log("obtenerVehiculosAgendados - Rol: " . $rol . ", Es Guardia: " . ($esGuardia ? 'Sí' : 'No') . ", Solo Pendientes: " . ($soloPendientes ? 'Sí' : 'No') . ", Fecha buscada: " . ($fecha ?? 'Todas'));
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparando consulta en obtenerVehiculosAgendados: " . mysqli_error($conn));
            $conn->close();
            return [];
        }
        
        // Solo bindear parámetro si no es Guardia
        if (!$esGuardia) {
            $stmt->bind_param("s", $fecha);
        }
        
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
 * Obtiene el historial completo de vehículos agendados desde ingreso_vehiculos
 */
function obtenerHistorialVehiculosAgendados($fecha = null, $rol = null) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        return [];
    }
    
    // Si el rol es Guardia, mostrar todo el historial sin filtrar por fecha
    $esGuardia = ($rol === 'Guardia');
    
    // Si no se proporciona fecha y no es Guardia, usar la fecha actual
    if ($fecha === null && !$esGuardia) {
        $fecha = date('Y-m-d');
    }
    
    $vehiculos = [];
    
    try {
        // Consulta para obtener historial desde ingreso_vehiculos como tabla principal
        // Se relaciona con solicitudes_agendamiento y agenda_taller para obtener información de agenda
        // Usando los nombres exactos de columnas de ingreso_vehiculos
        // Agrupando por vehículo para evitar duplicados cuando hay múltiples solicitudes
        $sql = "SELECT 
                    iv.Id as VehiculoID,
                    COALESCE(iv.placa, iv.Placa) as Placa,
                    COALESCE(sa.TipoVehiculo, iv.tipovehiculo, iv.TipoVehiculo) as TipoVehiculo,
                    COALESCE(sa.Marca, iv.marca, iv.Marca) as Marca,
                    COALESCE(sa.Modelo, iv.modelo, iv.Modelo) as Modelo,
                    COALESCE(sa.Anio, iv.anio, iv.Anio) as Anio,
                    COALESCE(sa.ConductorNombre, iv.conductorNombre, iv.ConductorNombre) as ConductorNombre,
                    COALESCE(sa.Proposito, 'N/A') as Proposito,
                    COALESCE(sa.Observaciones, '') as Observaciones,
                    sa.ID as SolicitudID,
                    sa.Estado as EstadoSolicitud,
                    sa.FechaCreacion as FechaSolicitud,
                    a.ID as AgendaID,
                    a.Fecha as FechaAgenda,
                    a.HoraInicio,
                    a.HoraFin,
                    u.NombreUsuario as ChoferNombre,
                    u.Correo as ChoferCorreo,
                    sup.NombreUsuario as SupervisorNombre,
                    CASE 
                        WHEN COALESCE(iv.fechasalida, iv.FechaSalida) IS NOT NULL THEN 'En Circulación'
                        WHEN COALESCE(iv.estado, iv.Estado) = 'Finalizado' THEN 'En Circulación'
                        WHEN COALESCE(iv.estado, iv.Estado) = 'Retirado' THEN 'En Circulación'
                        WHEN COALESCE(iv.estado, iv.Estado) = 'Ingresado' THEN 'Ingresado'
                        WHEN COALESCE(iv.estado, iv.Estado) = 'Completado' THEN 'Completado'
                        WHEN COALESCE(iv.estado, iv.Estado) = 'Asignado' THEN 'Solicitó Hora'
                        WHEN COALESCE(sa.Estado, '') = 'Aprobada' THEN 'Solicitó Hora'
                        WHEN COALESCE(sa.Estado, '') = 'Atrasado' THEN 'Solicitó Hora'
                        WHEN COALESCE(sa.Estado, '') = 'No llegó' THEN 'No Llegó'
                        WHEN COALESCE(sa.Estado, '') = 'Cancelada' THEN 'Cancelada'
                        ELSE 'Pendiente Ingreso'
                    END as EstadoIngreso,
                    COALESCE(iv.fechaingreso, iv.FechaIngreso) as FechaIngreso,
                    COALESCE(iv.fechasalida, iv.FechaSalida) as FechaSalida,
                    COALESCE(iv.estado, iv.Estado) as EstadoVehiculo,
                    iv.FechaRegistro
                FROM ingreso_vehiculos iv
                LEFT JOIN (
                    SELECT sa1.*
                    FROM solicitudes_agendamiento sa1
                    INNER JOIN (
                        SELECT Placa, MAX(FechaCreacion) as MaxFecha
                        FROM solicitudes_agendamiento
                        WHERE Estado IN ('Aprobada', 'Atrasado', 'No llegó', 'Completada', 'Cancelada')
                        GROUP BY Placa
                    ) sa2 ON sa1.Placa = sa2.Placa AND sa1.FechaCreacion = sa2.MaxFecha
                    WHERE sa1.Estado IN ('Aprobada', 'Atrasado', 'No llegó', 'Completada', 'Cancelada')
                ) sa ON sa.Placa COLLATE utf8mb4_unicode_ci = COALESCE(iv.placa, iv.Placa) COLLATE utf8mb4_unicode_ci
                LEFT JOIN agenda_taller a ON sa.AgendaID = a.ID
                LEFT JOIN usuarios u ON sa.ChoferID = u.UsuarioID
                LEFT JOIN usuarios sup ON sa.SupervisorID = sup.UsuarioID
                WHERE 1=1";
        
        // Si no es Guardia, agregar filtro de fecha por fechaingreso o FechaAgenda
        if (!$esGuardia) {
            // Asegurar que la fecha esté en formato YYYY-MM-DD
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                error_log("Formato de fecha inválido en obtenerHistorialVehiculosAgendados: " . $fecha);
                $fecha = date('Y-m-d');
            }
            $sql .= " AND (DATE(COALESCE(iv.fechaingreso, iv.FechaIngreso)) = ? OR (a.Fecha IS NOT NULL AND DATE(a.Fecha) = ?))";
        }
        
        // Ordenar por FechaAgenda primero (si existe), luego por FechaIngreso, de más reciente a más antigua
        // Usar STR_TO_DATE para asegurar ordenamiento correcto de fechas
        $sql .= " ORDER BY 
                    CASE 
                        WHEN a.Fecha IS NOT NULL THEN STR_TO_DATE(a.Fecha, '%Y-%m-%d')
                        ELSE DATE(COALESCE(iv.fechaingreso, iv.FechaIngreso))
                    END DESC,
                    CASE 
                        WHEN a.HoraInicio IS NOT NULL THEN TIME(a.HoraInicio)
                        ELSE TIME(COALESCE(iv.fechaingreso, iv.FechaIngreso))
                    END DESC,
                    COALESCE(iv.fechaingreso, iv.FechaIngreso) DESC";
        
        error_log("obtenerHistorialVehiculosAgendados - Rol: " . $rol . ", Es Guardia: " . ($esGuardia ? 'Sí' : 'No') . ", Fecha buscada: " . ($fecha ?? 'Todas'));
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparando consulta en obtenerHistorialVehiculosAgendados: " . mysqli_error($conn));
            $conn->close();
            return [];
        }
        
        // Solo bindear parámetro si no es Guardia (dos veces porque hay dos condiciones OR)
        if (!$esGuardia) {
            $stmt->bind_param("ss", $fecha, $fecha);
        }
        
        if (!$stmt->execute()) {
            error_log("Error ejecutando consulta en obtenerHistorialVehiculosAgendados: " . mysqli_stmt_error($stmt));
            $stmt->close();
            $conn->close();
            return [];
        }
        
        $result = $stmt->get_result();
        $count = 0;
        $vehiculosRaw = [];
        
        while ($row = $result->fetch_assoc()) {
            $vehiculosRaw[] = $row;
            $count++;
            error_log("Vehículo historial encontrado - Placa: " . ($row['Placa'] ?? 'N/A') . ", Estado: " . ($row['EstadoVehiculo'] ?? 'N/A') . ", Fecha: " . ($row['FechaIngreso'] ?? 'N/A'));
        }
        
        // Verificar para cada placa si el vehículo más reciente está en el taller
        // Agrupar por placa y encontrar el más reciente
        $vehiculosPorPlaca = [];
        foreach ($vehiculosRaw as $vehiculo) {
            $placa = $vehiculo['Placa'] ?? '';
            if (empty($placa)) continue;
            
            if (!isset($vehiculosPorPlaca[$placa])) {
                $vehiculosPorPlaca[$placa] = [];
            }
            $vehiculosPorPlaca[$placa][] = $vehiculo;
        }
        
        // Para cada placa, verificar si el vehículo más reciente está en el taller
        $placasEnTaller = [];
        foreach ($vehiculosPorPlaca as $placa => $vehiculosPlaca) {
            // Ordenar por fecha de ingreso descendente (más reciente primero)
            usort($vehiculosPlaca, function($a, $b) {
                $fechaA = $a['FechaIngreso'] ?? $a['FechaAgenda'] ?? '';
                $fechaB = $b['FechaIngreso'] ?? $b['FechaAgenda'] ?? '';
                if ($fechaA === $fechaB) {
                    // Si las fechas son iguales, comparar por ID
                    $idA = intval($a['VehiculoID'] ?? 0);
                    $idB = intval($b['VehiculoID'] ?? 0);
                    return $idB - $idA;
                }
                return strcmp($fechaB, $fechaA);
            });
            
            $vehiculoMasReciente = $vehiculosPlaca[0];
            $estadoVehiculo = $vehiculoMasReciente['EstadoVehiculo'] ?? '';
            $estadoIngreso = $vehiculoMasReciente['EstadoIngreso'] ?? '';
            
            // Verificar si está en el taller
            $estaEnTaller = false;
            
            // Estados que indican que está en el taller
            $estadosEnTaller = ['Ingresado', 'Asignado', 'Completado', 'En Proceso', 'En Revisión', 'En Pausa'];
            
            if (in_array($estadoVehiculo, $estadosEnTaller) || 
                in_array($estadoIngreso, $estadosEnTaller) ||
                $estadoIngreso === 'En Taller' ||
                $estadoIngreso === 'Solicitó Hora' ||
                $estadoIngreso === 'Pendiente Ingreso') {
                $estaEnTaller = true;
            }
            
            // También verificar si tiene asignación activa (antes de cerrar la conexión)
            if (!$estaEnTaller && !empty($vehiculoMasReciente['VehiculoID'])) {
                $vehiculoId = intval($vehiculoMasReciente['VehiculoID']);
                $queryAsignacion = "SELECT Estado FROM asignaciones_mecanico 
                                   WHERE VehiculoID = $vehiculoId 
                                   AND Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'Completado', 'En Pausa')
                                   ORDER BY FechaAsignacion DESC LIMIT 1";
                $resultAsignacion = $conn->query($queryAsignacion);
                if ($resultAsignacion && $resultAsignacion->num_rows > 0) {
                    $estaEnTaller = true;
                }
                if ($resultAsignacion) {
                    mysqli_free_result($resultAsignacion);
                }
            }
            
            if ($estaEnTaller) {
                $placasEnTaller[$placa] = true;
            }
        }
        
        // Procesar vehículos y ajustar estado si están en el taller
        $vehiculos = [];
        foreach ($vehiculosRaw as $vehiculo) {
            $placa = $vehiculo['Placa'] ?? '';
            $estadoIngreso = $vehiculo['EstadoIngreso'] ?? '';
            $fechaSalida = $vehiculo['FechaSalida'] ?? null;
            $estadoVehiculo = $vehiculo['EstadoVehiculo'] ?? '';
            
            // IMPORTANTE: Si el vehículo tiene FechaSalida o estado Finalizado/Retirado, 
            // significa que ya fue retirado y debe estar "En Circulación"
            // NO cambiar este estado aunque esté marcado como en el taller
            $estaRetirado = ($fechaSalida !== null && $fechaSalida !== '') || 
                           ($estadoVehiculo === 'Finalizado') ||
                           ($estadoVehiculo === 'Retirado');
            
            // Si la placa está en el taller y el estado es "En Circulación", 
            // pero el vehículo NO está retirado (no tiene FechaSalida ni estado Finalizado), cambiarlo
            if (isset($placasEnTaller[$placa]) && $estadoIngreso === 'En Circulación' && !$estaRetirado) {
                // Verificar el estado real del vehículo
                
                // Si tiene estado de taller, usar ese
                if (in_array($estadoVehiculo, ['Ingresado', 'Asignado', 'Completado', 'En Proceso', 'En Revisión', 'En Pausa'])) {
                    $vehiculo['EstadoIngreso'] = $estadoVehiculo === 'Completado' ? 'Completado' : 
                                                  ($estadoVehiculo === 'Ingresado' ? 'Ingresado' : 
                                                  ($estadoVehiculo === 'Asignado' ? 'Solicitó Hora' : 'En Taller'));
                } else {
                    // Si no tiene estado específico pero está en el taller, mostrar "En Taller"
                    $vehiculo['EstadoIngreso'] = 'En Taller';
                }
            }
            
            // Asegurar que si tiene FechaSalida o estado Finalizado, siempre muestre "En Circulación"
            if ($estaRetirado) {
                $vehiculo['EstadoIngreso'] = 'En Circulación';
            }
            
            $vehiculos[] = $vehiculo;
        }
        
        error_log("obtenerHistorialVehiculosAgendados - Total vehículos encontrados: " . $count);
        error_log("obtenerHistorialVehiculosAgendados - Placas en taller: " . implode(', ', array_keys($placasEnTaller)));
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Error obteniendo historial de vehículos agendados: " . $e->getMessage());
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
    // El guardia puede sacar vehículos completados sin importar nada
    if ($tipoOperacion === 'salida') {
        // Buscar vehículo completado (prioridad 1)
        $sql = "SELECT ID, Placa, Estado, FechaIngreso, ConductorNombre, TipoVehiculo, Marca, Modelo
                FROM ingreso_vehiculos 
                WHERE Placa = ? AND Estado = 'Completado'
                ORDER BY FechaIngreso DESC
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $placa);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $vehiculo = $result->fetch_assoc();
        $stmt->close();
        
        if ($vehiculo) {
            // Vehículo completado: puede salir sin restricciones
            $conn->close();
            return [
                'vehiculo' => $vehiculo,
                'tiene_agenda' => false,
                'puede_ingresar' => false,
                'puede_salir' => true,
                'mensaje' => 'Vehículo completado. Puede salir del taller.'
            ];
        }
        
        // Si no está completado, no puede salir
        $conn->close();
        return null;
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
    
    // Caso 1: Llegó más de 10 minutos antes - Puede ingresar pero requiere motivo
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
            'puede_ingresar' => true, // Cambiado a true para permitir ingreso con motivo
            'puede_salir' => false,
            'es_temprano' => true,
            'requiere_motivo_retraso' => true, // Requiere motivo para ingreso temprano
            'tipo_atraso' => 'temprano',
            'mensaje' => "El vehículo llegó {$minutosAntes} minutos antes de la hora asignada (" . date('H:i', strtotime($horaInicio)) . "). Se requiere un motivo para permitir el ingreso anticipado.",
            'hora_actual' => $horaActual,
            'diferencia_minutos' => -$minutosAntes
        ];
    }
    
    // Caso 2: Llegó más de 30 minutos después - Puede ingresar pero requiere motivo de retraso
    if ($horaActualTimestamp > $horaLimiteAtrasadoTimestamp) {
        // NO marcar automáticamente como "No llegó", permitir ingreso con motivo
        $diferenciaMinutos = round($diferenciaSegundos / 60);
        
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
            'puede_ingresar' => true, // Cambiado a true para permitir ingreso con motivo
            'puede_salir' => false,
            'es_atrasado' => true,
            'no_llego' => true,
            'requiere_motivo_retraso' => true, // Nuevo flag para indicar que requiere motivo
            'tipo_atraso' => 'no_llego',
            'mensaje' => 'El vehículo llegó con más de 30 minutos de retraso (' . date('H:i', strtotime($horaInicio)) . '). Se requiere ingresar un motivo del retraso para permitir el ingreso.',
            'hora_actual' => $horaActual,
            'diferencia_minutos' => $diferenciaMinutos
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

/**
 * Marca una solicitud de agendamiento como completada
 * Esto oculta el vehículo de la lista principal de vehículos agendados
 */
function marcarSolicitudCompletada($solicitud_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['success' => false, 'message' => 'Error de conexión'];
    }

    $solicitud_id = intval($solicitud_id);
    
    // Verificar que la solicitud existe y está aprobada
    $queryCheck = "SELECT ID, Estado, Placa FROM solicitudes_agendamiento WHERE ID = $solicitud_id";
    $resultCheck = mysqli_query($conn, $queryCheck);
    
    if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
        mysqli_close($conn);
        return ['success' => false, 'message' => 'Solicitud no encontrada'];
    }
    
    $solicitud = mysqli_fetch_assoc($resultCheck);
    
    // Actualizar el estado a 'Completada' si no lo está ya
    if ($solicitud['Estado'] !== 'Completada') {
        $queryUpdate = "UPDATE solicitudes_agendamiento SET Estado = 'Completada', FechaActualizacion = NOW() WHERE ID = $solicitud_id";
        $resultUpdate = mysqli_query($conn, $queryUpdate);
        
        if (!$resultUpdate) {
            mysqli_close($conn);
            return ['success' => false, 'message' => 'Error al actualizar solicitud: ' . mysqli_error($conn)];
        }
        
        error_log("Solicitud $solicitud_id (Placa: {$solicitud['Placa']}) marcada como Completada");
    }
    
    mysqli_close($conn);
    return ['success' => true, 'message' => 'Solicitud marcada como completada correctamente'];
}

?>