<?php
require_once __DIR__ . '../../../../config/conexion.php';

/**
 * Sube un archivo (foto o documento) al servidor
 */
function subirArchivo($archivo, $tipo = 'foto') {
    // Directorios de uploads
    $directorio_base = __DIR__ . '/../../../../uploads/';
    $directorio_tipo = $tipo === 'foto' ? 'fotos/' : 'documentos/';
    $directorio_completo = $directorio_base . $directorio_tipo;
    
    // Crear directorios si no existen
    if (!file_exists($directorio_base)) {
        mkdir($directorio_base, 0755, true);
    }
    if (!file_exists($directorio_completo)) {
        mkdir($directorio_completo, 0755, true);
    }
    
    // Validar tipo de archivo
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    if ($tipo === 'foto') {
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    } else {
        $extensiones_permitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    }
    
    if (!in_array($extension, $extensiones_permitidas)) {
        return [
            'success' => false,
            'message' => 'Tipo de archivo no permitido. Extensiones permitidas: ' . implode(', ', $extensiones_permitidas)
        ];
    }
    
    // Validar tamaño (en bytes)
    $tamanio_maximo = $tipo === 'foto' ? 5 * 1024 * 1024 : 10 * 1024 * 1024; // 5MB o 10MB
    if ($archivo['size'] > $tamanio_maximo) {
        return [
            'success' => false,
            'message' => 'El archivo es demasiado grande. Máximo: ' . ($tipo === 'foto' ? '5MB' : '10MB')
        ];
    }
    
    // Generar nombre único
    $nombre_guardado = uniqid() . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo['name']);
    $ruta_completa = $directorio_completo . $nombre_guardado;
    
    if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        // Retornar ruta relativa desde la raíz del proyecto
        $ruta_relativa = 'uploads/' . $directorio_tipo . $nombre_guardado;
        return [
            'success' => true,
            'ruta' => $ruta_relativa,
            'nombre_guardado' => $nombre_guardado,
            'nombre_original' => $archivo['name'],
            'tipo' => $tipo,
            'extension' => $extension
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Error al mover el archivo subido'
    ];
}

/**
 * Obtiene los datos de un vehículo registrado por su patente
 * Verifica que el ConductorNombre del vehículo coincida con el conductor logueado
 */
function obtenerVehiculoPorPatente($patente, $conductor_nombre = null) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    try {
        $patente = mysqli_real_escape_string($conn, $patente);
        
        // Buscar en la tabla ingreso_vehiculos (vehículos registrados)
        $query = "SELECT Placa, TipoVehiculo, Marca, Modelo, Anio, ConductorNombre
                  FROM ingreso_vehiculos
                  WHERE Placa = '$patente'
                  ORDER BY FechaIngreso DESC
                  LIMIT 1";
        
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception("Error en la consulta: " . mysqli_error($conn));
        }
        
        if (mysqli_num_rows($result) == 0) {
            mysqli_close($conn);
            return [
                'status' => 'error',
                'message' => 'No se encontró un vehículo registrado con esa patente'
            ];
        }
        
        $vehiculo = mysqli_fetch_assoc($result);
        
        // Si se proporciona el nombre del conductor, verificar que coincida
        if ($conductor_nombre !== null) {
            $conductor_nombre_vehiculo = trim($vehiculo['ConductorNombre']);
            $conductor_nombre_provisto = trim($conductor_nombre);
            
            // Comparar nombres (case-insensitive y sin espacios extra)
            if (strcasecmp($conductor_nombre_vehiculo, $conductor_nombre_provisto) !== 0) {
                mysqli_close($conn);
                return [
                    'status' => 'error',
                    'message' => 'Esta placa no corresponde a su vehículo'
                ];
            }
        }
        
        mysqli_close($conn);
        
        // No incluir ConductorNombre en la respuesta (ya está validado)
        unset($vehiculo['ConductorNombre']);
        
        return [
            'status' => 'success',
            'data' => $vehiculo
        ];
        
    } catch (Exception $e) {
        mysqli_close($conn);
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Crea una nueva solicitud de agendamiento por parte del chofer
 */
function crearSolicitudAgendamiento($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        // Validar campos requeridos (fecha y hora ya no son requeridos, se asignan al aprobar)
        $camposRequeridos = [
            'chofer_id', 'placa', 'tipo_vehiculo', 'marca', 'modelo',
            'conductor_nombre', 'proposito'
        ];

        foreach ($camposRequeridos as $campo) {
            if (empty($datos[$campo])) {
                throw new Exception("Campo requerido faltante: $campo");
            }
        }

        // Escapar datos
        $chofer_id = intval($datos['chofer_id']);
        $placa = mysqli_real_escape_string($conn, $datos['placa']);
        $tipo_vehiculo = mysqli_real_escape_string($conn, $datos['tipo_vehiculo']);
        $marca = mysqli_real_escape_string($conn, $datos['marca']);
        $modelo = mysqli_real_escape_string($conn, $datos['modelo']);
        $anio = !empty($datos['anio']) ? intval($datos['anio']) : NULL;
        $conductor_nombre = mysqli_real_escape_string($conn, $datos['conductor_nombre']);
        $conductor_telefono = !empty($datos['conductor_telefono']) ? mysqli_real_escape_string($conn, $datos['conductor_telefono']) : NULL;
        $proposito = mysqli_real_escape_string($conn, $datos['proposito']);
        $observaciones = !empty($datos['observaciones']) ? mysqli_real_escape_string($conn, $datos['observaciones']) : NULL;
        // Fecha y hora se asignarán cuando el supervisor apruebe

        // Verificar que no exista una solicitud pendiente o aprobada activa para la misma placa
        // Permitir crear nueva solicitud si el estado es "Atrasado" o "No llegó" (proceso cancelado)
        $fechaActual = date('Y-m-d');
        $horaActual = date('H:i:s');
        
        $checkQuery = "SELECT sa.ID, sa.Estado, a.Fecha as FechaAgenda, a.HoraInicio, a.HoraFin
                       FROM solicitudes_agendamiento sa
                       LEFT JOIN agenda_taller a ON sa.AgendaID = a.ID
                       WHERE sa.Placa = '$placa' 
                       AND sa.Estado IN ('Pendiente', 'Aprobada', 'Atrasado', 'No llegó')
                       ORDER BY sa.FechaCreacion DESC
                       LIMIT 1";
        $checkResult = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($checkResult) > 0) {
            $solicitudExistente = mysqli_fetch_assoc($checkResult);
            
            // Si es "Atrasado" o "No llegó", permitir crear nueva solicitud
            if ($solicitudExistente['Estado'] === 'Atrasado' || $solicitudExistente['Estado'] === 'No llegó') {
                // Permitir crear nueva solicitud - el proceso anterior está cancelado
            } 
            // Si es "Pendiente", no permitir
            else if ($solicitudExistente['Estado'] === 'Pendiente') {
                throw new Exception("Ya existe una solicitud pendiente para esta placa");
            }
            // Si es "Aprobada", verificar si la fecha/hora ya pasó
            else if ($solicitudExistente['Estado'] === 'Aprobada') {
                if ($solicitudExistente['FechaAgenda']) {
                    $fechaAgenda = $solicitudExistente['FechaAgenda'];
                    $horaInicio = $solicitudExistente['HoraInicio'];
                    
                    // Si la fecha es futura, no permitir
                    if ($fechaAgenda > $fechaActual) {
                        throw new Exception("Ya existe una solicitud aprobada para esta placa con fecha futura. No se puede crear una nueva solicitud.");
                    }
                    // Si la fecha es hoy, verificar la hora (con margen de 30 minutos)
                    else if ($fechaAgenda == $fechaActual && $horaInicio) {
                        $horaInicioTimestamp = strtotime($horaInicio);
                        $horaActualTimestamp = strtotime($horaActual);
                        $horaLimiteTimestamp = $horaInicioTimestamp + (30 * 60); // 30 minutos
                        
                        // Si aún no ha pasado el margen de 30 minutos, no permitir
                        if ($horaActualTimestamp <= $horaLimiteTimestamp) {
                            throw new Exception("Ya existe una solicitud aprobada para esta placa que aún está vigente. No se puede crear una nueva solicitud.");
                        }
                        // Si ya pasó el margen, permitir (se marcará como atrasado automáticamente)
                    }
                    // Si la fecha es pasada, permitir crear nueva
                } else {
                    // Si no tiene fecha asignada, no permitir
                    throw new Exception("Ya existe una solicitud aprobada para esta placa. No se puede crear una nueva solicitud.");
                }
            }
        }

        // Verificar si el vehículo ya está en mantenimiento
        // Un vehículo está en mantenimiento si tiene una asignación activa con mecánico
        // PERO solo si la solicitud asociada NO está en estado "Atrasado" o "No llegó"
        $checkMantenimiento = "SELECT iv.ID, iv.Placa, iv.Estado as EstadoVehiculo, 
                              a.ID as AsignacionID, a.Estado as EstadoAsignacion,
                              u.NombreUsuario as MecanicoNombre,
                              sa.Estado as EstadoSolicitud
                              FROM ingreso_vehiculos iv
                              LEFT JOIN asignaciones_mecanico a ON iv.ID = a.VehiculoID 
                                  AND a.Estado IN ('Asignado', 'En Proceso', 'En Revisión')
                              LEFT JOIN usuarios u ON a.MecanicoID = u.UsuarioID
                              LEFT JOIN solicitudes_agendamiento sa ON sa.Placa COLLATE utf8mb4_unicode_ci = iv.Placa COLLATE utf8mb4_unicode_ci
                                  AND sa.Estado IN ('Aprobada', 'Atrasado', 'No llegó')
                              WHERE iv.Placa = '$placa'
                              AND a.ID IS NOT NULL
                              AND (sa.Estado IS NULL OR sa.Estado NOT IN ('Atrasado', 'No llegó'))
                              ORDER BY sa.FechaCreacion DESC
                              LIMIT 1";
        $resultMantenimiento = mysqli_query($conn, $checkMantenimiento);
        if ($resultMantenimiento && mysqli_num_rows($resultMantenimiento) > 0) {
            $vehiculoMantenimiento = mysqli_fetch_assoc($resultMantenimiento);
            $mecanico = !empty($vehiculoMantenimiento['MecanicoNombre']) 
                       ? $vehiculoMantenimiento['MecanicoNombre'] 
                       : 'un mecánico';
            throw new Exception("El vehículo con placa $placa ya está en mantenimiento asignado a $mecanico. No se puede crear una nueva solicitud mientras el vehículo esté en mantenimiento.");
        }

        // Procesar fotos si existen
        $fotos_json = NULL;
        if (!empty($datos['fotos']) && is_array($datos['fotos'])) {
            $fotos_array = [];
            foreach ($datos['fotos'] as $foto) {
                if (is_array($foto) && isset($foto['ruta'])) {
                    $fotos_array[] = $foto;
                }
            }
            if (!empty($fotos_array)) {
                $fotos_json = json_encode($fotos_array);
            }
        }

        // Verificar si la columna Fotos existe en la tabla
        $columna_fotos_existe = false;
        $checkColumna = "SHOW COLUMNS FROM solicitudes_agendamiento LIKE 'Fotos'";
        $resultColumna = mysqli_query($conn, $checkColumna);
        if ($resultColumna && mysqli_num_rows($resultColumna) > 0) {
            $columna_fotos_existe = true;
        }
        
        // Insertar solicitud (sin PersonaContacto, Area y ConductorTelefono ya que se eliminaron)
        $campos = "ChoferID, Placa, TipoVehiculo, Marca, Modelo, Anio,
            ConductorNombre, Proposito,
            Observaciones, Estado";
        $valores = "$chofer_id, '$placa', '$tipo_vehiculo', '$marca', '$modelo',
            " . ($anio ? $anio : "NULL") . ",
            '$conductor_nombre',
            '$proposito',
            " . ($observaciones ? "'" . mysqli_real_escape_string($conn, $observaciones) . "'" : "NULL") . ",
            'Pendiente'";
        
        // Agregar Fotos si existen y la columna existe
        if ($fotos_json && $columna_fotos_existe) {
            $campos .= ", Fotos";
            $valores .= ", '" . mysqli_real_escape_string($conn, $fotos_json) . "'";
        }
        
        $query = "INSERT INTO solicitudes_agendamiento ($campos) VALUES ($valores)";

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al crear solicitud: " . mysqli_error($conn));
        }

        $solicitud_id = mysqli_insert_id($conn);
        mysqli_commit($conn);

        // Notificar al supervisor
        notificarNuevaSolicitud($solicitud_id);

        return [
            'status' => 'success',
            'message' => 'Solicitud de agendamiento creada correctamente',
            'solicitud_id' => $solicitud_id
        ];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    } finally {
        mysqli_close($conn);
    }
}

/**
 * Obtiene las solicitudes de agendamiento según filtros
 */
function obtenerSolicitudesAgendamiento($filtros = []) {
    $conn = null;
    try {
        // Intentar conectar sin usar die() - conexión directa
        $mysqli = @new mysqli("localhost", "root", "", "Pepsico");
        
        if ($mysqli->connect_errno) {
            error_log("Error de conexión a la base de datos en obtenerSolicitudesAgendamiento: " . $mysqli->connect_error);
            return [];
        }
        
        // Forzar charset a UTF-8
        if (!$mysqli->set_charset("utf8mb4")) {
            error_log("Error cargando el conjunto de caracteres utf8mb4: " . $mysqli->error);
            $mysqli->close();
            return [];
        }
        
        $conn = $mysqli;

        // Verificar si la tabla existe
        $checkTable = "SHOW TABLES LIKE 'solicitudes_agendamiento'";
        $resultCheck = @mysqli_query($conn, $checkTable);
        if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
            error_log("Error: La tabla solicitudes_agendamiento no existe. Ejecute el script create_solicitudes_agendamiento.sql");
            if ($conn) {
                mysqli_close($conn);
            }
            return [];
        }
    } catch (Exception $e) {
        error_log("Excepción en obtenerSolicitudesAgendamiento: " . $e->getMessage());
        if ($conn) {
            mysqli_close($conn);
        }
        return [];
    } catch (Error $e) {
        error_log("Error fatal en obtenerSolicitudesAgendamiento: " . $e->getMessage());
        if ($conn) {
            mysqli_close($conn);
        }
        return [];
    }

    if (!$conn) {
        return [];
    }

    $where = [];
    
    if (!empty($filtros['estado'])) {
        $estado = mysqli_real_escape_string($conn, $filtros['estado']);
        $where[] = "s.Estado = '$estado'";
    }

    if (!empty($filtros['chofer_id'])) {
        $chofer_id = intval($filtros['chofer_id']);
        $where[] = "s.ChoferID = $chofer_id";
    }

    if (!empty($filtros['supervisor_id'])) {
        $supervisor_id = intval($filtros['supervisor_id']);
        $where[] = "s.SupervisorID = $supervisor_id";
    }

    if (!empty($filtros['fecha_desde'])) {
        $fecha_desde = mysqli_real_escape_string($conn, $filtros['fecha_desde']);
        $where[] = "COALESCE(a.Fecha, s.FechaCreacion) >= '$fecha_desde'";
    }

    if (!empty($filtros['fecha_hasta'])) {
        $fecha_hasta = mysqli_real_escape_string($conn, $filtros['fecha_hasta']);
        $where[] = "COALESCE(a.Fecha, s.FechaCreacion) <= '$fecha_hasta'";
    }

    if (!empty($filtros['solicitud_id'])) {
        $solicitud_id = intval($filtros['solicitud_id']);
        $where[] = "s.ID = $solicitud_id";
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $query = "SELECT 
                s.*,
                u.NombreUsuario as ChoferNombre,
                u.Correo as ChoferCorreo,
                sup.NombreUsuario as SupervisorNombre,
                a.Fecha as FechaAgenda,
                a.HoraInicio as HoraInicioAgenda,
                a.HoraFin as HoraFinAgenda,
                v.ID as VehiculoID,
                asig.ID as AsignacionID,
                asig.Estado as EstadoAsignacion,
                mech.NombreUsuario as MecanicoNombre
            FROM solicitudes_agendamiento s
            LEFT JOIN usuarios u ON s.ChoferID = u.UsuarioID
            LEFT JOIN usuarios sup ON s.SupervisorID = sup.UsuarioID
            LEFT JOIN agenda_taller a ON s.AgendaID = a.ID
            LEFT JOIN ingreso_vehiculos v ON s.Placa COLLATE utf8mb4_unicode_ci = v.Placa COLLATE utf8mb4_unicode_ci AND v.Estado IN ('Ingresado', 'Asignado')
            LEFT JOIN asignaciones_mecanico asig ON v.ID = asig.VehiculoID AND asig.Estado IN ('Asignado', 'En Proceso', 'En Revisión')
            LEFT JOIN usuarios mech ON asig.MecanicoID = mech.UsuarioID
            $whereClause
            ORDER BY s.FechaCreacion DESC";

    try {
        // Ejecutar la consulta sin suprimir errores para poder capturarlos
        $result = mysqli_query($conn, $query);
        $solicitudes = [];

        if (!$result) {
            $error = mysqli_error($conn);
            error_log("Error en obtenerSolicitudesAgendamiento: " . $error);
            error_log("Query ejecutada: " . $query);
            error_log("Código de error MySQL: " . mysqli_errno($conn));
            if ($conn) {
                mysqli_close($conn);
            }
            // Lanzar excepción para que el script principal la capture
            throw new Exception("Error en la consulta SQL: " . $error);
        }

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $solicitudes[] = $row;
            }
        }

        if ($conn) {
            mysqli_close($conn);
        }
        return $solicitudes;
    } catch (Exception $e) {
        error_log("Excepción en obtenerSolicitudesAgendamiento: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        if ($conn) {
            mysqli_close($conn);
        }
        // Re-lanzar la excepción para que el script principal la capture
        throw $e;
    } catch (Error $e) {
        error_log("Error fatal en obtenerSolicitudesAgendamiento: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        if ($conn) {
            mysqli_close($conn);
        }
        // Re-lanzar el error para que el script principal lo capture
        throw $e;
    }
}

/**
 * Obtiene las horas disponibles en la agenda para una fecha específica
 * Retorna horas en el rango de 9:00 a 23:00 (11pm) para pruebas
 * Opcionalmente puede filtrar por mecánico para excluir horas ya asignadas a ese mecánico
 */
function obtenerHorasDisponibles($fecha, $mecanico_id = null) {
    // Crear conexión directamente sin usar die()
    $mysqli = @new mysqli("localhost", "root", "", "Pepsico");
    
    if ($mysqli->connect_errno) {
        error_log("Error de conexión en obtenerHorasDisponibles: " . $mysqli->connect_error);
        return [];
    }
    
    if (!$mysqli->set_charset("utf8mb4")) {
        error_log("Error cargando charset en obtenerHorasDisponibles: " . $mysqli->error);
        $mysqli->close();
        return [];
    }
    
    $conn = $mysqli;

    $fecha = mysqli_real_escape_string($conn, $fecha);
    $mecanico_id = $mecanico_id ? intval($mecanico_id) : null;

    // Obtener todas las horas disponibles para la fecha en el rango de 9:00 a 23:00 (para pruebas)
    $query = "SELECT 
                ID, HoraInicio, HoraFin, Disponible, Observaciones
            FROM agenda_taller
            WHERE Fecha = '$fecha' 
            AND Disponible = 1
            AND HoraInicio >= '09:00:00'
            AND HoraFin <= '23:00:00'
            ORDER BY HoraInicio";

    $result = mysqli_query($conn, $query);
    $horas = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $agenda_id = $row['ID'];
            $hora_inicio = $row['HoraInicio'];
            $hora_fin = $row['HoraFin'];
            
            // Verificar que no esté asignada a otra solicitud aprobada
            $checkQuery = "SELECT COUNT(*) as total 
                          FROM solicitudes_agendamiento 
                          WHERE AgendaID = $agenda_id 
                          AND Estado = 'Aprobada'";
            $checkResult = mysqli_query($conn, $checkQuery);
            $checkRow = mysqli_fetch_assoc($checkResult);
            
            if ($checkRow['total'] > 0) {
                continue; // Ya está asignada, saltar
            }
            
            // Si se especifica un mecánico, verificar que no tenga conflicto de horario
            if ($mecanico_id) {
                $queryConflictoMecanico = "SELECT COUNT(*) as total
                    FROM solicitudes_agendamiento sa
                    INNER JOIN agenda_taller a ON sa.AgendaID = a.ID
                    INNER JOIN ingreso_vehiculos iv ON sa.Placa COLLATE utf8mb4_unicode_ci = iv.Placa COLLATE utf8mb4_unicode_ci
                    INNER JOIN asignaciones_mecanico am ON iv.ID = am.VehiculoID
                    WHERE sa.Estado = 'Aprobada'
                    AND am.MecanicoID = $mecanico_id
                    AND am.Estado IN ('Asignado', 'En Proceso', 'En Revisión')
                    AND a.Fecha = '$fecha'
                    AND (
                        (a.HoraInicio < '$hora_fin' AND a.HoraFin > '$hora_inicio')
                    )";
                
                $resultConflictoMecanico = mysqli_query($conn, $queryConflictoMecanico);
                $conflictoMecanico = mysqli_fetch_assoc($resultConflictoMecanico);
                
                if ($conflictoMecanico['total'] > 0) {
                    continue; // El mecánico ya tiene una asignación en este horario, saltar
                }
            }
            
            $horas[] = $row;
        }
    }

    mysqli_close($conn);
    return $horas;
}

/**
 * Aprueba una solicitud de agendamiento y asigna una hora de la agenda
 * Opcionalmente puede asignar un mecánico
 */
function aprobarSolicitudAgendamiento($solicitud_id, $supervisor_id, $agenda_id = null, $mecanico_id = null) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        $solicitud_id = intval($solicitud_id);
        $supervisor_id = intval($supervisor_id);

        // Obtener información de la solicitud
        $querySolicitud = "SELECT * FROM solicitudes_agendamiento WHERE ID = $solicitud_id";
        $resultSolicitud = mysqli_query($conn, $querySolicitud);
        
        if (mysqli_num_rows($resultSolicitud) == 0) {
            throw new Exception("Solicitud no encontrada");
        }

        $solicitud = mysqli_fetch_assoc($resultSolicitud);

        if ($solicitud['Estado'] != 'Pendiente') {
            throw new Exception("La solicitud ya fue procesada");
        }

        // El agenda_id debe ser proporcionado por el supervisor al seleccionar del calendario
        if (empty($agenda_id)) {
            throw new Exception("Debe seleccionar una hora disponible del calendario");
        }
        
        {
            // Verificar que la agenda esté disponible
            $agenda_id = intval($agenda_id);
            $queryCheck = "SELECT * FROM agenda_taller WHERE ID = $agenda_id AND Disponible = 1";
            $resultCheck = mysqli_query($conn, $queryCheck);
            
            if (mysqli_num_rows($resultCheck) == 0) {
                throw new Exception("La hora seleccionada no está disponible");
            }

            // Verificar que no esté asignada a otra solicitud aprobada
            $queryOcupada = "SELECT COUNT(*) as total 
                            FROM solicitudes_agendamiento 
                            WHERE AgendaID = $agenda_id AND Estado = 'Aprobada'";
            $resultOcupada = mysqli_query($conn, $queryOcupada);
            $ocupada = mysqli_fetch_assoc($resultOcupada);
            
            if ($ocupada['total'] > 0) {
                throw new Exception("La hora seleccionada ya está asignada a otra solicitud");
            }
        }

        // Actualizar solicitud
        $queryUpdate = "UPDATE solicitudes_agendamiento 
                       SET Estado = 'Aprobada',
                           AgendaID = $agenda_id,
                           SupervisorID = $supervisor_id,
                           FechaActualizacion = NOW()
                       WHERE ID = $solicitud_id";

        if (!mysqli_query($conn, $queryUpdate)) {
            throw new Exception("Error al aprobar solicitud: " . mysqli_error($conn));
        }

        // Si se proporciona un mecánico, verificar que no tenga conflictos de horario
        if ($mecanico_id) {
            $mecanico_id = intval($mecanico_id);
            
            // Obtener información de la agenda asignada
            $queryAgenda = "SELECT Fecha, HoraInicio, HoraFin FROM agenda_taller WHERE ID = $agenda_id";
            $resultAgenda = mysqli_query($conn, $queryAgenda);
            if (mysqli_num_rows($resultAgenda) > 0) {
                $agenda = mysqli_fetch_assoc($resultAgenda);
                $fecha_agenda = $agenda['Fecha'];
                $hora_inicio = $agenda['HoraInicio'];
                $hora_fin = $agenda['HoraFin'];
                
                // Verificar si el mecánico ya tiene otra asignación en el mismo rango de tiempo
                $queryConflicto = "SELECT COUNT(*) as total
                    FROM solicitudes_agendamiento sa
                    INNER JOIN agenda_taller a ON sa.AgendaID = a.ID
                    INNER JOIN ingreso_vehiculos iv ON sa.Placa COLLATE utf8mb4_unicode_ci = iv.Placa COLLATE utf8mb4_unicode_ci
                    INNER JOIN asignaciones_mecanico am ON iv.ID = am.VehiculoID
                    WHERE sa.Estado = 'Aprobada'
                    AND sa.ID != $solicitud_id
                    AND am.MecanicoID = $mecanico_id
                    AND am.Estado IN ('Asignado', 'En Proceso', 'En Revisión')
                    AND a.Fecha = '$fecha_agenda'
                    AND (
                        (a.HoraInicio < '$hora_fin' AND a.HoraFin > '$hora_inicio')
                    )";
                
                $resultConflicto = mysqli_query($conn, $queryConflicto);
                $conflicto = mysqli_fetch_assoc($resultConflicto);
                
                if ($conflicto['total'] > 0) {
                    throw new Exception("El mecánico ya tiene una asignación en el rango de tiempo seleccionado ($fecha_agenda de $hora_inicio a $hora_fin). Por favor, seleccione otra hora.");
                }
            }
            
            // Obtener información completa de la solicitud
            $querySolicitudCompleta = "SELECT * FROM solicitudes_agendamiento WHERE ID = $solicitud_id";
            $resultSolicitudCompleta = mysqli_query($conn, $querySolicitudCompleta);
            $solicitudCompleta = mysqli_fetch_assoc($resultSolicitudCompleta);
            
            $placa = mysqli_real_escape_string($conn, $solicitudCompleta['Placa']);
            $marca = mysqli_real_escape_string($conn, $solicitudCompleta['Marca']);
            $modelo = mysqli_real_escape_string($conn, $solicitudCompleta['Modelo']);
            $tipoVehiculo = mysqli_real_escape_string($conn, $solicitudCompleta['TipoVehiculo']);
            $anio = !empty($solicitudCompleta['Anio']) ? intval($solicitudCompleta['Anio']) : 'NULL';
            $conductorNombre = mysqli_real_escape_string($conn, $solicitudCompleta['ConductorNombre']);
            $proposito = mysqli_real_escape_string($conn, $solicitudCompleta['Proposito']);
            $observacionesSolicitud = !empty($solicitudCompleta['Observaciones']) ? 
                "'" . mysqli_real_escape_string($conn, $solicitudCompleta['Observaciones']) . "'" : 'NULL';
            
            // Verificar si el vehículo ya existe en ingreso_vehiculos (cualquier estado)
            $queryCheckVehiculo = "SELECT ID, Estado FROM ingreso_vehiculos WHERE Placa = '$placa' ORDER BY FechaRegistro DESC LIMIT 1";
            $resultCheckVehiculo = mysqli_query($conn, $queryCheckVehiculo);
            
            $vehiculo_id = null;
            
            if (mysqli_num_rows($resultCheckVehiculo) > 0) {
                // El vehículo ya existe, usar ese ID
                $vehiculo = mysqli_fetch_assoc($resultCheckVehiculo);
                $vehiculo_id = $vehiculo['ID'];
                
                // Si el estado no es 'Ingresado' o 'Asignado', actualizarlo a 'Asignado'
                if (!in_array($vehiculo['Estado'], ['Ingresado', 'Asignado'])) {
                    $queryUpdateEstado = "UPDATE ingreso_vehiculos SET Estado = 'Asignado' WHERE ID = $vehiculo_id";
                    if (!mysqli_query($conn, $queryUpdateEstado)) {
                        error_log("Error al actualizar estado del vehículo: " . mysqli_error($conn));
                    }
                }
            } else {
                // El vehículo no existe, crearlo en ingreso_vehiculos
                // Estado inicial: 'Asignado' (porque ya tiene mecánico asignado)
                $queryInsertVehiculo = "INSERT INTO ingreso_vehiculos (
                    Placa, TipoVehiculo, Marca, Modelo, Anio, 
                    ConductorNombre, Proposito, Observaciones, 
                    Estado, FechaRegistro
                ) VALUES (
                    '$placa', '$tipoVehiculo', '$marca', '$modelo', $anio,
                    '$conductorNombre', '$proposito', $observacionesSolicitud,
                    'Asignado', NOW()
                )";
                
                if (mysqli_query($conn, $queryInsertVehiculo)) {
                    $vehiculo_id = mysqli_insert_id($conn);
                } else {
                    throw new Exception("Error al crear registro de vehículo: " . mysqli_error($conn));
                }
            }
            
            // Crear asignación al mecánico
            // NOTA: No usar asignarMecanico() aquí porque cierra su propia conexión
            // y estamos dentro de una transacción. Hacer la asignación directamente.
            if ($vehiculo_id) {
                $observaciones = "Asignación automática desde solicitud de agendamiento #$solicitud_id";
                $observacionesEscapadas = mysqli_real_escape_string($conn, $observaciones);
                
                // Crear la asignación directamente dentro de la transacción
                $queryInsertAsignacion = "INSERT INTO asignaciones_mecanico (
                    VehiculoID, MecanicoID, Estado, Observaciones, FechaAsignacion
                ) VALUES (
                    $vehiculo_id, $mecanico_id, 'Asignado', '$observacionesEscapadas', NOW()
                )";
                
                if (!mysqli_query($conn, $queryInsertAsignacion)) {
                    throw new Exception("Error al crear asignación de mecánico: " . mysqli_error($conn));
                }
                
                // El estado del vehículo ya debería estar en 'Asignado' desde antes
                // pero verificamos y actualizamos por si acaso
                $queryUpdateVehiculo = "UPDATE ingreso_vehiculos SET Estado = 'Asignado' WHERE ID = $vehiculo_id";
                if (!mysqli_query($conn, $queryUpdateVehiculo)) {
                    error_log("Error al actualizar estado del vehículo: " . mysqli_error($conn));
                }
            }
        }

        mysqli_commit($conn);

        // Notificar al chofer
        notificarAprobacionSolicitud($solicitud_id);

        return [
            'status' => 'success',
            'message' => 'Solicitud aprobada correctamente',
            'agenda_id' => $agenda_id
        ];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    } finally {
        mysqli_close($conn);
    }
}

/**
 * Rechaza una solicitud de agendamiento
 */
function rechazarSolicitudAgendamiento($solicitud_id, $supervisor_id, $motivo_rechazo) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        $solicitud_id = intval($solicitud_id);
        $supervisor_id = intval($supervisor_id);
        $motivo_rechazo = mysqli_real_escape_string($conn, $motivo_rechazo);

        // Verificar que la solicitud exista y esté pendiente
        $queryCheck = "SELECT * FROM solicitudes_agendamiento WHERE ID = $solicitud_id";
        $resultCheck = mysqli_query($conn, $queryCheck);
        
        if (mysqli_num_rows($resultCheck) == 0) {
            throw new Exception("Solicitud no encontrada");
        }

        $solicitud = mysqli_fetch_assoc($resultCheck);

        if ($solicitud['Estado'] != 'Pendiente') {
            throw new Exception("La solicitud ya fue procesada");
        }

        // Actualizar solicitud
        $queryUpdate = "UPDATE solicitudes_agendamiento 
                       SET Estado = 'Rechazada',
                           SupervisorID = $supervisor_id,
                           MotivoRechazo = '$motivo_rechazo',
                           FechaActualizacion = NOW()
                       WHERE ID = $solicitud_id";

        if (!mysqli_query($conn, $queryUpdate)) {
            throw new Exception("Error al rechazar solicitud: " . mysqli_error($conn));
        }

        mysqli_commit($conn);

        // Notificar al chofer
        notificarRechazoSolicitud($solicitud_id);

        return [
            'status' => 'success',
            'message' => 'Solicitud rechazada correctamente'
        ];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    } finally {
        mysqli_close($conn);
    }
}

/**
 * Crea o actualiza una hora en la agenda del taller
 */
function gestionarAgendaTaller($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        $fecha = mysqli_real_escape_string($conn, $datos['fecha']);
        $hora_inicio = mysqli_real_escape_string($conn, $datos['hora_inicio']);
        $hora_fin = mysqli_real_escape_string($conn, $datos['hora_fin']);
        $disponible = isset($datos['disponible']) ? intval($datos['disponible']) : 1;
        $observaciones = !empty($datos['observaciones']) ? mysqli_real_escape_string($conn, $datos['observaciones']) : NULL;

        // Validar que hora_inicio sea menor que hora_fin
        if (strtotime($hora_inicio) >= strtotime($hora_fin)) {
            throw new Exception("La hora de inicio debe ser menor que la hora de fin");
        }

        if (empty($datos['id'])) {
            // Verificar que no exista una hora duplicada (misma fecha, misma hora inicio, misma hora fin)
            $queryCheckDuplicado = "SELECT COUNT(*) as total 
                FROM agenda_taller 
                WHERE Fecha = '$fecha' 
                AND HoraInicio = '$hora_inicio' 
                AND HoraFin = '$hora_fin'";
            $resultCheckDuplicado = mysqli_query($conn, $queryCheckDuplicado);
            $duplicado = mysqli_fetch_assoc($resultCheckDuplicado);
            
            if ($duplicado['total'] > 0) {
                throw new Exception("Ya existe una hora con la misma fecha y rango horario ($fecha de $hora_inicio a $hora_fin)");
            }
            
            // Verificar solapamiento de horarios (que no se solape con otra hora existente)
            $queryCheckSolapamiento = "SELECT COUNT(*) as total 
                FROM agenda_taller 
                WHERE Fecha = '$fecha' 
                AND Disponible = 1
                AND (
                    (HoraInicio < '$hora_fin' AND HoraFin > '$hora_inicio')
                )";
            $resultCheckSolapamiento = mysqli_query($conn, $queryCheckSolapamiento);
            $solapamiento = mysqli_fetch_assoc($resultCheckSolapamiento);
            
            if ($solapamiento['total'] > 0) {
                throw new Exception("La hora se solapa con otra hora existente en la misma fecha. Verifique que no haya conflictos de horario.");
            }
            
            // Crear nueva hora
            $query = "INSERT INTO agenda_taller (Fecha, HoraInicio, HoraFin, Disponible, Observaciones)
                     VALUES ('$fecha', '$hora_inicio', '$hora_fin', $disponible, " . ($observaciones ? "'$observaciones'" : "NULL") . ")";
        } else {
            // Actualizar hora existente
            $id = intval($datos['id']);
            
            // Verificar que no exista otra hora duplicada (excluyendo la actual)
            $queryCheckDuplicado = "SELECT COUNT(*) as total 
                FROM agenda_taller 
                WHERE Fecha = '$fecha' 
                AND HoraInicio = '$hora_inicio' 
                AND HoraFin = '$hora_fin'
                AND ID != $id";
            $resultCheckDuplicado = mysqli_query($conn, $queryCheckDuplicado);
            $duplicado = mysqli_fetch_assoc($resultCheckDuplicado);
            
            if ($duplicado['total'] > 0) {
                throw new Exception("Ya existe otra hora con la misma fecha y rango horario ($fecha de $hora_inicio a $hora_fin)");
            }
            
            // Verificar solapamiento con otras horas (excluyendo la actual)
            $queryCheckSolapamiento = "SELECT COUNT(*) as total 
                FROM agenda_taller 
                WHERE Fecha = '$fecha' 
                AND Disponible = 1
                AND ID != $id
                AND (
                    (HoraInicio < '$hora_fin' AND HoraFin > '$hora_inicio')
                )";
            $resultCheckSolapamiento = mysqli_query($conn, $queryCheckSolapamiento);
            $solapamiento = mysqli_fetch_assoc($resultCheckSolapamiento);
            
            if ($solapamiento['total'] > 0) {
                throw new Exception("La hora se solapa con otra hora existente en la misma fecha. Verifique que no haya conflictos de horario.");
            }
            
            // Verificar si la hora está asignada a alguna solicitud aprobada
            $queryCheckAsignada = "SELECT COUNT(*) as total 
                FROM solicitudes_agendamiento 
                WHERE AgendaID = $id 
                AND Estado = 'Aprobada'";
            $resultCheckAsignada = mysqli_query($conn, $queryCheckAsignada);
            $asignada = mysqli_fetch_assoc($resultCheckAsignada);
            
            if ($asignada['total'] > 0 && $disponible == 0) {
                throw new Exception("No se puede marcar como no disponible una hora que está asignada a una solicitud aprobada");
            }
            
            // Actualizar hora existente
            $query = "UPDATE agenda_taller 
                     SET Fecha = '$fecha',
                         HoraInicio = '$hora_inicio',
                         HoraFin = '$hora_fin',
                         Disponible = $disponible,
                         Observaciones = " . ($observaciones ? "'$observaciones'" : "NULL") . "
                     WHERE ID = $id";
        }

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al gestionar agenda: " . mysqli_error($conn));
        }

        $agenda_id = empty($datos['id']) ? mysqli_insert_id($conn) : $datos['id'];
        mysqli_commit($conn);

        return [
            'status' => 'success',
            'message' => 'Agenda actualizada correctamente',
            'agenda_id' => $agenda_id
        ];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_close($conn);
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Obtiene todas las agendas del taller con información de solicitudes asignadas
 */
function obtenerTodasLasAgendas($filtroFecha = null, $filtroDisponible = null) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    $where = [];

    if ($filtroFecha) {
        $filtroFecha = mysqli_real_escape_string($conn, $filtroFecha);
        $where[] = "a.Fecha = '$filtroFecha'";
    }

    if ($filtroDisponible !== null) {
        $filtroDisponible = intval($filtroDisponible);
        $where[] = "a.Disponible = $filtroDisponible";
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $query = "SELECT 
                a.ID,
                a.Fecha,
                a.HoraInicio,
                a.HoraFin,
                a.Disponible,
                a.Observaciones,
                a.FechaCreacion,
                a.FechaActualizacion,
                COUNT(DISTINCT CASE WHEN s.Estado = 'Aprobada' THEN s.ID END) as SolicitudesAprobadas,
                COUNT(DISTINCT CASE WHEN s.Estado = 'Pendiente' THEN s.ID END) as SolicitudesPendientes
              FROM agenda_taller a
              LEFT JOIN solicitudes_agendamiento s ON a.ID = s.AgendaID
              $whereClause
              GROUP BY a.ID
              ORDER BY a.Fecha DESC, a.HoraInicio DESC";

    $result = mysqli_query($conn, $query);
    if (!$result) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error en consulta: ' . mysqli_error($conn)];
    }

    $agendas = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $agendas[] = [
            'ID' => $row['ID'],
            'Fecha' => $row['Fecha'],
            'HoraInicio' => $row['HoraInicio'],
            'HoraFin' => $row['HoraFin'],
            'Disponible' => (bool)$row['Disponible'],
            'Observaciones' => $row['Observaciones'],
            'FechaCreacion' => $row['FechaCreacion'],
            'FechaActualizacion' => $row['FechaActualizacion'],
            'SolicitudesAprobadas' => (int)$row['SolicitudesAprobadas'],
            'SolicitudesPendientes' => (int)$row['SolicitudesPendientes']
        ];
    }

    mysqli_close($conn);

    return [
        'status' => 'success',
        'data' => $agendas
    ];
}

/**
 * Elimina una agenda del taller
 * Solo permite eliminar si no está asignada a ninguna solicitud aprobada
 */
function eliminarAgenda($agenda_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        $agenda_id = intval($agenda_id);

        // Verificar si está asignada a alguna solicitud aprobada
        $queryCheck = "SELECT COUNT(*) as total 
                      FROM solicitudes_agendamiento 
                      WHERE AgendaID = $agenda_id 
                      AND Estado = 'Aprobada'";
        $resultCheck = mysqli_query($conn, $queryCheck);
        $check = mysqli_fetch_assoc($resultCheck);

        if ($check['total'] > 0) {
            throw new Exception("No se puede eliminar una agenda que está asignada a una solicitud aprobada");
        }

        // Si hay solicitudes pendientes, actualizar su AgendaID a NULL
        $queryUpdateSolicitudes = "UPDATE solicitudes_agendamiento 
                                  SET AgendaID = NULL 
                                  WHERE AgendaID = $agenda_id 
                                  AND Estado = 'Pendiente'";

        if (!mysqli_query($conn, $queryUpdateSolicitudes)) {
            throw new Exception("Error al actualizar solicitudes: " . mysqli_error($conn));
        }

        // Eliminar la agenda
        $query = "DELETE FROM agenda_taller WHERE ID = $agenda_id";

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al eliminar agenda: " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        mysqli_close($conn);

        return [
            'status' => 'success',
            'message' => 'Agenda eliminada correctamente'
        ];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_close($conn);
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Notifica a los supervisores sobre una nueva solicitud
 */
function notificarNuevaSolicitud($solicitud_id) {
    // Cargar funciones de notificaciones si no están cargadas
    $notificaciones_path = __DIR__ . '/../../../../pages/general/funciones_notificaciones.php';
    if (file_exists($notificaciones_path)) {
        require_once $notificaciones_path;
    } else {
        error_log("Archivo de notificaciones no encontrado: " . $notificaciones_path);
        return false;
    }
    
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error de conexión al notificar nueva solicitud");
        return false;
    }

    // Obtener información de la solicitud
    $query = "SELECT s.*, u.NombreUsuario as ChoferNombre 
              FROM solicitudes_agendamiento s
              LEFT JOIN usuarios u ON s.ChoferID = u.UsuarioID
              WHERE s.ID = $solicitud_id";
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        mysqli_close($conn);
        return false;
    }
    
    $solicitud = mysqli_fetch_assoc($result);
    mysqli_close($conn);

    // Obtener supervisores
    $roles_notificar = ['Supervisor', 'Administrador'];
    $usuarios_destino = obtenerUsuariosPorRoles($roles_notificar);
    
    if (empty($usuarios_destino)) {
        error_log("No se encontraron supervisores para notificar");
        return false;
    }

    // Crear mensaje de notificación
    $titulo = "Nueva Solicitud de Agendamiento";
    $mensaje = "Solicitud #{$solicitud_id}: Vehículo {$solicitud['Placa']} - {$solicitud['Marca']} {$solicitud['Modelo']} solicitado por {$solicitud['ChoferNombre']}. Propósito: {$solicitud['Proposito']}";
    $modulo = "agendamiento";
    $enlace = "gestion_solicitudes.php";

    // Crear notificación
    $resultado = crearNotificacion($usuarios_destino, $titulo, $mensaje, $modulo, $enlace);
    
    if ($resultado) {
        error_log("Notificaciones enviadas a " . count($usuarios_destino) . " supervisores");
    } else {
        error_log("Error al enviar notificaciones");
    }
    
    return $resultado;
}

/**
 * Notifica al chofer sobre la aprobación de su solicitud
 */
function notificarAprobacionSolicitud($solicitud_id) {
    // Cargar funciones de notificaciones si no están cargadas
    $notificaciones_path = __DIR__ . '/../../../../pages/general/funciones_notificaciones.php';
    if (file_exists($notificaciones_path)) {
        require_once $notificaciones_path;
    } else {
        error_log("Archivo de notificaciones no encontrado: " . $notificaciones_path);
        return false;
    }
    
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error de conexión al notificar aprobación");
        return false;
    }

    // Obtener información de la solicitud
    $query = "SELECT s.*, a.Fecha as FechaAgenda, a.HoraInicio, a.HoraFin 
              FROM solicitudes_agendamiento s
              LEFT JOIN agenda_taller a ON s.AgendaID = a.ID
              WHERE s.ID = $solicitud_id";
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        mysqli_close($conn);
        return false;
    }
    
    $solicitud = mysqli_fetch_assoc($result);
    mysqli_close($conn);

    // Notificar al chofer
    $chofer_id = $solicitud['ChoferID'];
    
    // Formatear fecha de agenda
    if (!empty($solicitud['FechaAgenda'])) {
        $fecha_obj = new DateTime($solicitud['FechaAgenda']);
        $fecha_agenda = $fecha_obj->format('d/m/Y');
    } else {
        $fecha_agenda = !empty($solicitud['FechaAgenda']) ? $solicitud['FechaAgenda'] : date('Y-m-d');
    }
    
    $hora_agenda = !empty($solicitud['HoraInicioAgenda']) ? $solicitud['HoraInicioAgenda'] : (!empty($solicitud['HoraInicio']) ? $solicitud['HoraInicio'] : 'N/A');

    $titulo = "Solicitud de Agendamiento Aprobada";
    $mensaje = "Su solicitud #{$solicitud_id} para el vehículo {$solicitud['Placa']} ha sido aprobada. Fecha asignada: {$fecha_agenda} a las {$hora_agenda}";
    $modulo = "agendamiento";
    $enlace = "solicitudes_agendamiento.php";

    $resultado = crearNotificacion([$chofer_id], $titulo, $mensaje, $modulo, $enlace);
    
    if ($resultado) {
        error_log("Notificación de aprobación enviada al chofer ID: $chofer_id");
    }
    
    return $resultado;
}

/**
 * Notifica al chofer sobre el rechazo de su solicitud
 */
function notificarRechazoSolicitud($solicitud_id) {
    // Cargar funciones de notificaciones si no están cargadas
    $notificaciones_path = __DIR__ . '/../../../../pages/general/funciones_notificaciones.php';
    if (file_exists($notificaciones_path)) {
        require_once $notificaciones_path;
    } else {
        error_log("Archivo de notificaciones no encontrado: " . $notificaciones_path);
        return false;
    }
    
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error de conexión al notificar rechazo");
        return false;
    }

    // Obtener información de la solicitud
    $query = "SELECT * FROM solicitudes_agendamiento WHERE ID = $solicitud_id";
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        mysqli_close($conn);
        return false;
    }
    
    $solicitud = mysqli_fetch_assoc($result);
    mysqli_close($conn);

    // Notificar al chofer
    $chofer_id = $solicitud['ChoferID'];
    $motivo = $solicitud['MotivoRechazo'] ? "\nMotivo: " . $solicitud['MotivoRechazo'] : '';

    $titulo = "Solicitud de Agendamiento Rechazada";
    $mensaje = "Su solicitud #{$solicitud_id} para el vehículo {$solicitud['Placa']} ha sido rechazada.{$motivo}";
    $modulo = "agendamiento";
    $enlace = "solicitudes_agendamiento.php";

    $resultado = crearNotificacion([$chofer_id], $titulo, $mensaje, $modulo, $enlace);
    
    if ($resultado) {
        error_log("Notificación de rechazo enviada al chofer ID: $chofer_id");
    }
    
    return $resultado;
}

?>

