<?php
require_once __DIR__ . '/../../../config/conexion.php';

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
        // Obtener el registro más reciente por FechaRegistro
        $query = "SELECT Placa, TipoVehiculo, Marca, Modelo, Anio, ConductorNombre
                  FROM ingreso_vehiculos
                  WHERE Placa = '$patente'
                  ORDER BY FechaRegistro DESC
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

        // Verificar que no exista una solicitud aprobada activa para la misma placa
        // PERMITIR nueva solicitud si:
        // 1. Es el mismo día
        // 2. El vehículo ya salió del taller (tiene FechaSalida o estado Finalizado/Retirado)
        $checkQuery = "SELECT sa.ID, sa.Estado, a.Fecha as FechaAgenda, sa.FechaCreacion
                       FROM solicitudes_agendamiento sa
                       LEFT JOIN agenda_taller a ON sa.AgendaID = a.ID
                       WHERE sa.Placa = '$placa' 
                       AND sa.Estado = 'Aprobada'
                       ORDER BY sa.FechaCreacion DESC
                       LIMIT 1";
        $checkResult = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($checkResult) > 0) {
            $solicitudExistente = mysqli_fetch_assoc($checkResult);
            
            // Verificar si es el mismo día
            $fechaSolicitudExistente = !empty($solicitudExistente['FechaCreacion']) 
                ? date('Y-m-d', strtotime($solicitudExistente['FechaCreacion'])) 
                : null;
            $fechaHoy = date('Y-m-d');
            $esMismoDia = ($fechaSolicitudExistente === $fechaHoy);
            
            // Verificar si el vehículo ya salió del taller (obtener el registro más reciente)
            $checkVehiculo = "SELECT ID, Estado, 
                              COALESCE(fechasalida, FechaSalida) as FechaSalidaReal
                              FROM ingreso_vehiculos
                              WHERE Placa = '$placa'
                              ORDER BY FechaRegistro DESC, ID DESC
                              LIMIT 1";
            $resultVehiculo = mysqli_query($conn, $checkVehiculo);
            
            $estaRetirado = false;
            if ($resultVehiculo && mysqli_num_rows($resultVehiculo) > 0) {
                $vehiculo = mysqli_fetch_assoc($resultVehiculo);
                $fechaSalida = $vehiculo['FechaSalidaReal'] ?? null;
                $estadoVehiculo = $vehiculo['Estado'] ?? '';
                
                // Verificar si tiene FechaSalida o estado que indica que salió
                $estaRetirado = ($fechaSalida !== null && $fechaSalida !== '') ||
                               ($estadoVehiculo === 'Finalizado') ||
                               ($estadoVehiculo === 'Retirado') ||
                               ($estadoVehiculo === 'Disponible');
                
                // También verificar si no tiene asignaciones activas
                if (!$estaRetirado && !empty($vehiculo['ID'])) {
                    $vehiculoId = intval($vehiculo['ID']);
                    $checkAsignaciones = "SELECT COUNT(*) as total
                                          FROM asignaciones_mecanico
                                          WHERE VehiculoID = $vehiculoId
                                          AND Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'En Pausa')";
                    $resultAsignaciones = mysqli_query($conn, $checkAsignaciones);
                    if ($resultAsignaciones) {
                        $asignaciones = mysqli_fetch_assoc($resultAsignaciones);
                        // Si no tiene asignaciones activas, considerar que salió
                        if ($asignaciones['total'] == 0) {
                            $estaRetirado = true;
                        }
                    }
                }
            }
            
            // Si NO es el mismo día O el vehículo NO ha salido, bloquear
            if (!$esMismoDia || !$estaRetirado) {
            throw new Exception("Ya existe una solicitud aprobada para esta placa. No se puede crear una nueva solicitud mientras la solicitud aprobada esté activa.");
            }
            // Si es el mismo día Y el vehículo ya salió, permitir crear nueva solicitud
        }
        
        // Verificar que no exista una solicitud pendiente para la misma placa
        $checkPendiente = "SELECT ID, Estado
                           FROM solicitudes_agendamiento
                           WHERE Placa = '$placa' 
                           AND Estado = 'Pendiente'
                           ORDER BY FechaCreacion DESC
                           LIMIT 1";
        $resultPendiente = mysqli_query($conn, $checkPendiente);
        
        if (mysqli_num_rows($resultPendiente) > 0) {
            throw new Exception("Ya existe una solicitud pendiente para esta placa. Espere a que sea procesada antes de crear una nueva.");
        }

        // Verificar si el vehículo ya está en taller (estados que indican que está en proceso)
        $checkVehiculoEnTaller = "SELECT iv.ID, iv.Estado, iv.Placa
                                  FROM ingreso_vehiculos iv
                                  WHERE iv.Placa = '$placa'
                                  AND iv.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'Completado')
                                  ORDER BY iv.FechaRegistro DESC
                                  LIMIT 1";
        $resultVehiculoEnTaller = mysqli_query($conn, $checkVehiculoEnTaller);
        
        if (mysqli_num_rows($resultVehiculoEnTaller) > 0) {
            $vehiculoEnTaller = mysqli_fetch_assoc($resultVehiculoEnTaller);
            // Verificar si tiene asignaciones activas
            $checkAsignaciones = "SELECT COUNT(*) as total
                                  FROM asignaciones_mecanico
                                  WHERE VehiculoID = {$vehiculoEnTaller['ID']}
                                  AND Estado IN ('Asignado', 'En Proceso', 'En Revisión')";
            $resultAsignaciones = mysqli_query($conn, $checkAsignaciones);
            $asignaciones = mysqli_fetch_assoc($resultAsignaciones);
            
            if ($asignaciones['total'] > 0 || $vehiculoEnTaller['Estado'] === 'Completado') {
                throw new Exception("El vehículo ya está en taller. Debe esperar la notificación de retiro antes de crear una nueva solicitud.");
            }
        }
        // Los datos del vehículo se obtendrán automáticamente desde ingreso_vehiculos
        // mediante la función obtenerVehiculoPorPatente().

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
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    try {
        // Verificar si la tabla existe
        $checkTable = "SHOW TABLES LIKE 'solicitudes_agendamiento'";
        $resultCheck = $conn->query($checkTable);
        if (!$resultCheck || $resultCheck->num_rows == 0) {
            error_log("Error: La tabla solicitudes_agendamiento no existe.");
            $conn->close();
            return [];
        }

        $where = [];
        
        if (!empty($filtros['estado'])) {
            $estado = $conn->real_escape_string($filtros['estado']);
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
            $fecha_desde = $conn->real_escape_string($filtros['fecha_desde']);
            $where[] = "COALESCE(a.Fecha, s.FechaCreacion) >= '$fecha_desde'";
        }

        if (!empty($filtros['fecha_hasta'])) {
            $fecha_hasta = $conn->real_escape_string($filtros['fecha_hasta']);
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
                    COALESCE(a.Fecha, 
                        (SELECT a2.Fecha 
                         FROM asignaciones_mecanico asig2
                         INNER JOIN ingreso_vehiculos v2 ON asig2.VehiculoID = v2.ID
                         INNER JOIN solicitudes_agendamiento s2 ON v2.Placa COLLATE utf8mb4_unicode_ci = s2.Placa COLLATE utf8mb4_unicode_ci
                         INNER JOIN agenda_taller a2 ON s2.AgendaID = a2.ID
                         WHERE v2.Placa COLLATE utf8mb4_unicode_ci = s.Placa COLLATE utf8mb4_unicode_ci
                           AND asig2.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'Completado')
                           AND s2.Estado = 'Aprobada'
                           AND s2.AgendaID IS NOT NULL
                           AND (s2.ID = s.ID OR s2.FechaCreacion >= s.FechaCreacion)
                         ORDER BY s2.FechaCreacion DESC
                         LIMIT 1)
                    ) as FechaAgenda,
                    COALESCE(a.HoraInicio,
                        (SELECT a2.HoraInicio 
                         FROM asignaciones_mecanico asig2
                         INNER JOIN ingreso_vehiculos v2 ON asig2.VehiculoID = v2.ID
                         INNER JOIN solicitudes_agendamiento s2 ON v2.Placa COLLATE utf8mb4_unicode_ci = s2.Placa COLLATE utf8mb4_unicode_ci
                         INNER JOIN agenda_taller a2 ON s2.AgendaID = a2.ID
                         WHERE v2.Placa COLLATE utf8mb4_unicode_ci = s.Placa COLLATE utf8mb4_unicode_ci
                           AND asig2.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'Completado')
                           AND s2.Estado = 'Aprobada'
                           AND s2.AgendaID IS NOT NULL
                           AND (s2.ID = s.ID OR s2.FechaCreacion >= s.FechaCreacion)
                         ORDER BY s2.FechaCreacion DESC
                         LIMIT 1)
                    ) as HoraInicioAgenda,
                    COALESCE(a.HoraFin,
                        (SELECT a2.HoraFin 
                         FROM asignaciones_mecanico asig2
                         INNER JOIN ingreso_vehiculos v2 ON asig2.VehiculoID = v2.ID
                         INNER JOIN solicitudes_agendamiento s2 ON v2.Placa COLLATE utf8mb4_unicode_ci = s2.Placa COLLATE utf8mb4_unicode_ci
                         INNER JOIN agenda_taller a2 ON s2.AgendaID = a2.ID
                         WHERE v2.Placa COLLATE utf8mb4_unicode_ci = s.Placa COLLATE utf8mb4_unicode_ci
                           AND asig2.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'Completado')
                           AND s2.Estado = 'Aprobada'
                           AND s2.AgendaID IS NOT NULL
                           AND (s2.ID = s.ID OR s2.FechaCreacion >= s.FechaCreacion)
                         ORDER BY s2.FechaCreacion DESC
                         LIMIT 1)
                    ) as HoraFinAgenda,
                    v.ID as VehiculoID,
                    v.Estado as EstadoVehiculo,
                    COALESCE(v.fechasalida, v.FechaSalida) as FechaSalida,
                    (SELECT v2.FechaSalida 
                     FROM ingreso_vehiculos v2
                     LEFT JOIN asignaciones_mecanico asig2 ON v2.ID = asig2.VehiculoID
                     INNER JOIN solicitudes_agendamiento s2 ON v2.Placa COLLATE utf8mb4_unicode_ci = s2.Placa COLLATE utf8mb4_unicode_ci
                     WHERE s2.ID = s.ID
                       AND v2.Placa COLLATE utf8mb4_unicode_ci = s.Placa COLLATE utf8mb4_unicode_ci
                       AND (
                           (asig2.ID IS NOT NULL AND asig2.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'Completado'))
                           OR
                           (asig2.ID IS NULL AND v2.FechaRegistro >= s.FechaCreacion
                            AND NOT EXISTS (
                                SELECT 1 FROM solicitudes_agendamiento s3 
                                WHERE s3.Placa COLLATE utf8mb4_unicode_ci = s.Placa COLLATE utf8mb4_unicode_ci
                                  AND s3.ID != s.ID
                                  AND s3.FechaCreacion > s.FechaCreacion
                                  AND s3.FechaCreacion <= v2.FechaRegistro
                            ))
                       )
                     ORDER BY 
                       CASE WHEN asig2.ID IS NOT NULL THEN 1 ELSE 2 END,
                       v2.FechaRegistro DESC
                     LIMIT 1) as FechaSalidaRelacionada,
                    (SELECT asig2.ID
                     FROM asignaciones_mecanico asig2
                     INNER JOIN ingreso_vehiculos v2 ON asig2.VehiculoID = v2.ID
                     WHERE v2.Placa COLLATE utf8mb4_unicode_ci = s.Placa COLLATE utf8mb4_unicode_ci
                       AND asig2.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'Completado')
                       AND asig2.FechaAsignacion >= s.FechaCreacion
                       AND (
                           -- Si hay agenda, la asignación debe estar relacionada con esa fecha
                           (s.AgendaID IS NOT NULL AND EXISTS (
                               SELECT 1 FROM agenda_taller a2 
                               WHERE a2.ID = s.AgendaID 
                                 AND asig2.FechaAsignacion <= DATE_ADD(a2.Fecha, INTERVAL 1 DAY)
                           ))
                           OR
                           -- Si no hay agenda, la asignación debe estar dentro de un rango razonable
                           (s.AgendaID IS NULL AND asig2.FechaAsignacion <= DATE_ADD(s.FechaCreacion, INTERVAL 7 DAY))
                       )
                       -- Priorizar asignaciones que no tengan otra solicitud más reciente entre esta y la asignación
                       AND NOT EXISTS (
                           SELECT 1 FROM solicitudes_agendamiento s3
                           INNER JOIN ingreso_vehiculos v3 ON s3.Placa COLLATE utf8mb4_unicode_ci = v3.Placa COLLATE utf8mb4_unicode_ci
                           WHERE s3.Placa COLLATE utf8mb4_unicode_ci = s.Placa COLLATE utf8mb4_unicode_ci
                             AND s3.ID != s.ID
                             AND s3.FechaCreacion > s.FechaCreacion
                             AND s3.FechaCreacion < asig2.FechaAsignacion
                             AND v3.ID = v2.ID
                       )
                     ORDER BY 
                       -- Priorizar asignaciones más cercanas a la fecha de creación de esta solicitud
                       ABS(TIMESTAMPDIFF(HOUR, asig2.FechaAsignacion, s.FechaCreacion)) ASC,
                       asig2.FechaAsignacion ASC
                     LIMIT 1
                    ) as AsignacionID,
                    (SELECT asig3.Estado
                     FROM asignaciones_mecanico asig3
                     INNER JOIN ingreso_vehiculos v3 ON asig3.VehiculoID = v3.ID
                     WHERE v3.Placa COLLATE utf8mb4_unicode_ci = s.Placa COLLATE utf8mb4_unicode_ci
                       AND asig3.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'Completado')
                       AND asig3.FechaAsignacion >= s.FechaCreacion
                       AND (
                           (s.AgendaID IS NOT NULL AND EXISTS (
                               SELECT 1 FROM agenda_taller a3 
                               WHERE a3.ID = s.AgendaID 
                                 AND asig3.FechaAsignacion <= DATE_ADD(a3.Fecha, INTERVAL 1 DAY)
                           ))
                           OR
                           (s.AgendaID IS NULL AND asig3.FechaAsignacion <= DATE_ADD(s.FechaCreacion, INTERVAL 7 DAY))
                       )
                       AND NOT EXISTS (
                           SELECT 1 FROM solicitudes_agendamiento s4
                           INNER JOIN ingreso_vehiculos v4 ON s4.Placa COLLATE utf8mb4_unicode_ci = v4.Placa COLLATE utf8mb4_unicode_ci
                           WHERE s4.Placa COLLATE utf8mb4_unicode_ci = s.Placa COLLATE utf8mb4_unicode_ci
                             AND s4.ID != s.ID
                             AND s4.FechaCreacion > s.FechaCreacion
                             AND s4.FechaCreacion < asig3.FechaAsignacion
                             AND v4.ID = v3.ID
                       )
                     ORDER BY 
                       ABS(TIMESTAMPDIFF(HOUR, asig3.FechaAsignacion, s.FechaCreacion)) ASC,
                       asig3.FechaAsignacion ASC
                     LIMIT 1
                    ) as EstadoAsignacion,
                    (SELECT mech2.NombreUsuario
                     FROM asignaciones_mecanico asig4
                     INNER JOIN ingreso_vehiculos v5 ON asig4.VehiculoID = v5.ID
                     INNER JOIN usuarios mech2 ON asig4.MecanicoID = mech2.UsuarioID
                     WHERE v5.Placa COLLATE utf8mb4_unicode_ci = s.Placa COLLATE utf8mb4_unicode_ci
                       AND asig4.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'Completado')
                       AND asig4.FechaAsignacion >= s.FechaCreacion
                       AND (
                           (s.AgendaID IS NOT NULL AND EXISTS (
                               SELECT 1 FROM agenda_taller a4 
                               WHERE a4.ID = s.AgendaID 
                                 AND asig4.FechaAsignacion <= DATE_ADD(a4.Fecha, INTERVAL 1 DAY)
                           ))
                           OR
                           (s.AgendaID IS NULL AND asig4.FechaAsignacion <= DATE_ADD(s.FechaCreacion, INTERVAL 7 DAY))
                       )
                       AND NOT EXISTS (
                           SELECT 1 FROM solicitudes_agendamiento s5
                           INNER JOIN ingreso_vehiculos v6 ON s5.Placa COLLATE utf8mb4_unicode_ci = v6.Placa COLLATE utf8mb4_unicode_ci
                           WHERE s5.Placa COLLATE utf8mb4_unicode_ci = s.Placa COLLATE utf8mb4_unicode_ci
                             AND s5.ID != s.ID
                             AND s5.FechaCreacion > s.FechaCreacion
                             AND s5.FechaCreacion < asig4.FechaAsignacion
                             AND v6.ID = v5.ID
                       )
                     ORDER BY 
                       ABS(TIMESTAMPDIFF(HOUR, asig4.FechaAsignacion, s.FechaCreacion)) ASC,
                       asig4.FechaAsignacion ASC
                     LIMIT 1
                    ) as MecanicoNombre
                FROM solicitudes_agendamiento s
                LEFT JOIN usuarios u ON s.ChoferID = u.UsuarioID
                LEFT JOIN usuarios sup ON s.SupervisorID = sup.UsuarioID
                LEFT JOIN agenda_taller a ON s.AgendaID = a.ID
                LEFT JOIN ingreso_vehiculos v ON s.Placa COLLATE utf8mb4_unicode_ci = v.Placa COLLATE utf8mb4_unicode_ci
                $whereClause
                ORDER BY s.FechaCreacion DESC";

        $result = $conn->query($query);
        $solicitudes = [];

        if (!$result) {
            $error = $conn->error;
            error_log("Error en obtenerSolicitudesAgendamiento: " . $error);
            error_log("Query ejecutada: " . $query);
            $conn->close();
            throw new Exception("Error en la consulta SQL: " . $error);
        }

        // Array para rastrear solicitudes únicas por placa + fecha/hora
        $solicitudesUnicas = [];
        // Array para rastrear IDs de solicitudes ya procesadas (evitar duplicados por ID)
        $idsProcesados = [];
        
        while ($row = $result->fetch_assoc()) {
            // Verificar si este ID ya fue procesado (evitar duplicados)
            $solicitudId = intval($row['ID'] ?? 0);
            if (isset($idsProcesados[$solicitudId])) {
                continue; // Ya procesamos esta solicitud, saltar
            }
            // Si no hay FechaAgenda, intentar obtenerla desde la asignación
            $fechaAgenda = $row['FechaAgenda'] ?? null;
            $horaInicioAgenda = $row['HoraInicioAgenda'] ?? null;
            $horaFinAgenda = $row['HoraFinAgenda'] ?? null;
            
            // Si no hay agenda directa, buscar desde asignación
            if (!$fechaAgenda && !empty($row['Placa'])) {
                $placa = mysqli_real_escape_string($conn, $row['Placa']);
                $queryAgendaAsignacion = "SELECT 
                    a.Fecha as FechaAgenda,
                    a.HoraInicio as HoraInicioAgenda,
                    a.HoraFin as HoraFinAgenda
                FROM asignaciones_mecanico asig
                INNER JOIN ingreso_vehiculos v ON asig.VehiculoID = v.ID
                INNER JOIN solicitudes_agendamiento s2 ON v.Placa COLLATE utf8mb4_unicode_ci = s2.Placa COLLATE utf8mb4_unicode_ci
                INNER JOIN agenda_taller a ON s2.AgendaID = a.ID
                WHERE v.Placa COLLATE utf8mb4_unicode_ci = '$placa'
                  AND s2.Estado = 'Aprobada'
                  AND s2.AgendaID IS NOT NULL
                  AND asig.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'Completado')
                ORDER BY s2.FechaCreacion DESC
                LIMIT 1";
                
                $resultAgendaAsignacion = $conn->query($queryAgendaAsignacion);
                if ($resultAgendaAsignacion && $resultAgendaAsignacion->num_rows > 0) {
                    $agendaRow = $resultAgendaAsignacion->fetch_assoc();
                    $fechaAgenda = $agendaRow['FechaAgenda'] ?? null;
                    $horaInicioAgenda = $agendaRow['HoraInicioAgenda'] ?? null;
                    $horaFinAgenda = $agendaRow['HoraFinAgenda'] ?? null;
                }
                if ($resultAgendaAsignacion) {
                    mysqli_free_result($resultAgendaAsignacion);
                }
            }
            
            // Crear clave única: Placa + FechaAgenda + HoraInicioAgenda
            // Si no hay fecha/hora, usar solo el ID de la solicitud
            $claveUnica = '';
            if ($fechaAgenda && $horaInicioAgenda) {
                $claveUnica = $row['Placa'] . '|' . $fechaAgenda . '|' . $horaInicioAgenda;
            } else {
                // Si no tiene fecha/hora asignada, usar ID de solicitud para que cada una sea única
                $claveUnica = 'ID_' . ($row['ID'] ?? 0);
            }
            
            // Si ya existe una solicitud con la misma placa + fecha/hora, verificar si se debe mantener ambas
            if (!empty($claveUnica) && isset($solicitudesUnicas[$claveUnica])) {
                $solicitudExistente = $solicitudesUnicas[$claveUnica];
                
                // Verificar si es el mismo día
                $fechaCreacionActual = $row['FechaCreacion'] ?? '';
                $fechaCreacionExistente = $solicitudExistente['FechaCreacion'] ?? '';
                
                $fechaActualObj = !empty($fechaCreacionActual) ? date('Y-m-d', strtotime($fechaCreacionActual)) : null;
                $fechaExistenteObj = !empty($fechaCreacionExistente) ? date('Y-m-d', strtotime($fechaCreacionExistente)) : null;
                $fechaHoy = date('Y-m-d');
                
                $esMismoDia = ($fechaActualObj === $fechaHoy) && ($fechaExistenteObj === $fechaHoy);
                
                // Verificar si el vehículo ya salió del taller (consultar directamente la BD)
                // IMPORTANTE: Solo considerar retirado si la FechaSalida es posterior a la solicitud existente
                // para evitar marcar solicitudes nuevas como retiradas
                $placaEscapada = mysqli_real_escape_string($conn, $row['Placa'] ?? '');
                $fechaCreacionExistenteParaVerificar = !empty($solicitudExistente['FechaCreacion']) 
                    ? date('Y-m-d H:i:s', strtotime($solicitudExistente['FechaCreacion'])) 
                    : null;
                
                $checkVehiculo = "SELECT iv.ID, iv.Estado, 
                                  COALESCE(iv.fechasalida, iv.FechaSalida) as FechaSalidaReal,
                                  iv.FechaRegistro
                                  FROM ingreso_vehiculos iv
                                  WHERE iv.Placa = '$placaEscapada'
                                  ORDER BY iv.FechaRegistro DESC, iv.ID DESC
                                  LIMIT 1";
                $resultVehiculo = mysqli_query($conn, $checkVehiculo);
                
                $estaRetirado = false;
                if ($resultVehiculo && mysqli_num_rows($resultVehiculo) > 0) {
                    $vehiculo = mysqli_fetch_assoc($resultVehiculo);
                    $fechaSalida = $vehiculo['FechaSalidaReal'] ?? null;
                    $estadoVehiculo = $vehiculo['Estado'] ?? '';
                    $fechaRegistroVehiculo = $vehiculo['FechaRegistro'] ?? null;
                    
                    // Verificar si tiene FechaSalida Y que sea posterior a la solicitud existente
                    if ($fechaSalida !== null && $fechaSalida !== '') {
                        // Si hay fecha de creación de solicitud existente, verificar que la salida sea posterior
                        if ($fechaCreacionExistenteParaVerificar) {
                            $fechaSalidaObj = new DateTime($fechaSalida);
                            $fechaCreacionObj = new DateTime($fechaCreacionExistenteParaVerificar);
                            // Solo considerar retirado si la salida es posterior a la creación de la solicitud existente
                            if ($fechaSalidaObj >= $fechaCreacionObj) {
                                $estaRetirado = true;
                            }
                        } else {
                            // Si no hay fecha de solicitud existente, considerar retirado si tiene FechaSalida
                            $estaRetirado = true;
                        }
                    }
                    
                    // También verificar estados que indican que salió (solo si no es una solicitud nueva)
                    if (!$estaRetirado && $fechaCreacionExistenteParaVerificar) {
                        if ($estadoVehiculo === 'Finalizado' || 
                            $estadoVehiculo === 'Retirado' || 
                            $estadoVehiculo === 'Disponible') {
                            // Verificar que el registro del vehículo sea posterior a la solicitud existente
                            if ($fechaRegistroVehiculo) {
                                $fechaRegistroObj = new DateTime($fechaRegistroVehiculo);
                                $fechaCreacionObj = new DateTime($fechaCreacionExistenteParaVerificar);
                                if ($fechaRegistroObj >= $fechaCreacionObj) {
                                    $estaRetirado = true;
                                }
                            }
                        }
                    }
                    
                    // También verificar si no tiene asignaciones activas relacionadas con la solicitud existente
                    if (!$estaRetirado && !empty($vehiculo['ID'])) {
                        $vehiculoId = intval($vehiculo['ID']);
                        $solicitudExistenteId = intval($solicitudExistente['ID'] ?? 0);
                        
                        // Verificar si hay asignaciones activas relacionadas con la solicitud existente
                        $checkAsignaciones = "SELECT COUNT(*) as total
                                              FROM asignaciones_mecanico am
                                              INNER JOIN ingreso_vehiculos iv2 ON am.VehiculoID = iv2.ID
                                              INNER JOIN solicitudes_agendamiento sa2 ON iv2.Placa COLLATE utf8mb4_unicode_ci = sa2.Placa COLLATE utf8mb4_unicode_ci
                                              WHERE am.VehiculoID = $vehiculoId
                                              AND sa2.ID = $solicitudExistenteId
                                              AND am.Estado IN ('Asignado', 'En Proceso', 'En Revisión', 'En Pausa')";
                        $resultAsignaciones = mysqli_query($conn, $checkAsignaciones);
                        if ($resultAsignaciones) {
                            $asignaciones = mysqli_fetch_assoc($resultAsignaciones);
                            // Si no tiene asignaciones activas relacionadas con la solicitud existente, considerar que salió
                            if ($asignaciones['total'] == 0) {
                                // Pero solo si la solicitud existente fue aprobada (tiene agenda)
                                if (!empty($solicitudExistente['AgendaID']) || 
                                    ($solicitudExistente['Estado'] ?? '') === 'Aprobada') {
                                    $estaRetirado = true;
                                }
                            }
                        }
                    }
                }
                
                // Si es el mismo día Y el vehículo ya salió del taller, 
                // permitir ambas solicitudes usando una clave única diferente
                if ($esMismoDia && $estaRetirado) {
                    // Usar ID de solicitud como parte de la clave para permitir ambas
                    $claveUnica = $row['Placa'] . '|' . ($fechaAgenda ?? 'sin_fecha') . '|' . ($horaInicioAgenda ?? 'sin_hora') . '|ID_' . ($row['ID'] ?? 0);
                } else {
                    // Comparar fechas de creación, mantener solo la más reciente
                if ($fechaCreacionActual > $fechaCreacionExistente) {
                    // La actual es más reciente, reemplazar
                } else {
                    // La existente es más reciente o igual, saltar esta
                    continue;
                    }
                }
            }
            
            // Asegurar que todos los campos estén presentes
            $solicitud = [
                'ID' => $row['ID'] ?? 0,
                'ChoferID' => $row['ChoferID'] ?? 0,
                'Placa' => $row['Placa'] ?? '',
                'TipoVehiculo' => $row['TipoVehiculo'] ?? '',
                'Marca' => $row['Marca'] ?? '',
                'Modelo' => $row['Modelo'] ?? '',
                'ConductorNombre' => $row['ConductorNombre'] ?? ($row['ChoferNombre'] ?? ''),
                'Proposito' => $row['Proposito'] ?? '',
                'Estado' => $row['Estado'] ?? 'Pendiente',
                'AgendaID' => $row['AgendaID'] ?? null,
                'SupervisorID' => $row['SupervisorID'] ?? null,
                'Observaciones' => $row['Observaciones'] ?? '',
                'FechaCreacion' => $row['FechaCreacion'] ?? '',
                'FechaActualizacion' => $row['FechaActualizacion'] ?? '',
                'FechaAgenda' => $fechaAgenda,
                'HoraInicioAgenda' => $horaInicioAgenda,
                'HoraFinAgenda' => $horaFinAgenda,
                'ChoferNombre' => $row['ChoferNombre'] ?? '',
                'SupervisorNombre' => $row['SupervisorNombre'] ?? '',
                'MecanicoNombre' => $row['MecanicoNombre'] ?? '',
                'VehiculoID' => $row['VehiculoID'] ?? null,
                'AsignacionID' => $row['AsignacionID'] ?? null,
                'EstadoAsignacion' => $row['EstadoAsignacion'] ?? null,
                'MotivoRechazo' => $row['MotivoRechazo'] ?? null,
                'EstadoVehiculo' => $row['EstadoVehiculo'] ?? null,
                'FechaSalida' => $row['FechaSalida'] ?? ($row['FechaSalidaRelacionada'] ?? null)
            ];
            
            // Guardar el estado original antes de cualquier modificación
            $estadoOriginal = $solicitud['Estado'] ?? 'Pendiente';
            $solicitud['EstadoOriginal'] = $estadoOriginal;
            
            // NUEVO SISTEMA: Determinar estado basado en el estado real del vehículo y múltiples solicitudes del mismo día
            $placaEscapada = mysqli_real_escape_string($conn, $solicitud['Placa'] ?? '');
            $solicitudId = intval($solicitud['ID'] ?? 0);
            $fechaCreacionSolicitud = $solicitud['FechaCreacion'] ?? '';
            $fechaActualizacionSolicitud = $solicitud['FechaActualizacion'] ?? $fechaCreacionSolicitud;
            
            // 1. Verificar si hay múltiples solicitudes del mismo día para esta placa
            $fechaHoy = date('Y-m-d');
            $fechaCreacionObj = !empty($fechaCreacionSolicitud) ? date('Y-m-d', strtotime($fechaCreacionSolicitud)) : null;
            $esMismoDia = ($fechaCreacionObj === $fechaHoy);
            
            $esSolicitudMasTemprana = false;
            $hayOtrasSolicitudesHoy = false;
            
            if ($esMismoDia && !empty($placaEscapada)) {
                // Obtener todas las solicitudes del mismo día para esta placa
                $queryOtrasSolicitudes = "SELECT ID, FechaCreacion, Estado, AgendaID
                                         FROM solicitudes_agendamiento
                                         WHERE Placa = '$placaEscapada'
                                         AND DATE(FechaCreacion) = '$fechaHoy'
                                         ORDER BY ID ASC, FechaCreacion ASC";
                $resultOtras = mysqli_query($conn, $queryOtrasSolicitudes);
                
                if ($resultOtras && mysqli_num_rows($resultOtras) > 1) {
                    $hayOtrasSolicitudesHoy = true;
                    $solicitudesDelDia = [];
                    while ($otra = mysqli_fetch_assoc($resultOtras)) {
                        $solicitudesDelDia[] = $otra;
                    }
                    
                    // Encontrar la solicitud más temprana (menor ID o fecha más temprana)
                    $solicitudMasTemprana = $solicitudesDelDia[0];
                    $esSolicitudMasTemprana = ($solicitudMasTemprana['ID'] == $solicitudId);
                }
            }
            
            // 2. Obtener el estado real del vehículo (usar el que ya viene en la consulta principal)
            $estadoRealVehiculo = $solicitud['EstadoVehiculo'] ?? null;
            $fechaSalidaReal = $solicitud['FechaSalida'] ?? ($row['FechaSalidaRelacionada'] ?? null);
            $asignacionEstadoReal = $solicitud['EstadoAsignacion'] ?? null;
            
            // Si no viene el estado del vehículo en la consulta principal, obtenerlo directamente
            if (empty($estadoRealVehiculo) && !empty($placaEscapada)) {
                $queryEstadoVehiculo = "SELECT iv.ID, iv.Estado 
                                       FROM ingreso_vehiculos iv
                                       WHERE iv.Placa = '$placaEscapada'
                                       ORDER BY iv.FechaRegistro DESC, iv.ID DESC
                                       LIMIT 1";
                $resultEstadoVehiculo = mysqli_query($conn, $queryEstadoVehiculo);
                
                if ($resultEstadoVehiculo && mysqli_num_rows($resultEstadoVehiculo) > 0) {
                    $vehiculoInfo = mysqli_fetch_assoc($resultEstadoVehiculo);
                    $estadoRealVehiculo = $vehiculoInfo['Estado'] ?? '';
                }
            }
            
            // 3. Determinar el estado a mostrar según las reglas
            $tieneAgenda = !empty($solicitud['AgendaID']) || ($estadoOriginal === 'Aprobada');
            $estaRetirado = false;
            $estadoAMostrar = $estadoOriginal; // Por defecto, mantener el estado original
            
            if ($tieneAgenda) {
                // Verificar si el vehículo salió del taller (solo si tiene FechaSalida posterior a la aprobación)
                if ($fechaSalidaReal !== null && $fechaSalidaReal !== '' && !empty($fechaActualizacionSolicitud)) {
                    try {
                        $fechaSalidaObj = new DateTime($fechaSalidaReal);
                        $fechaAprobacionObj = new DateTime($fechaActualizacionSolicitud);
                        // Solo considerar retirado si la salida es POSTERIOR a cuando se aprobó la solicitud
                        if ($fechaSalidaObj > $fechaAprobacionObj) {
                            $estaRetirado = true;
                        }
                    } catch (Exception $e) {
                        error_log("Error al parsear fechas: " . $e->getMessage());
                    }
                }
                
                // Si está retirado, mostrar "Completado"
                if ($estaRetirado) {
                    $estadoAMostrar = 'Completado';
                } 
                // Si NO está retirado, mostrar el estado real del vehículo (exactamente como está en EstadoVehiculo)
                else {
                    // Usar el estado real del vehículo directamente, tal como está en la base de datos
                    // Este es el mismo estado que se muestra en "Estado vehículo" en los detalles
                    if (!empty($estadoRealVehiculo)) {
                        // Usar el estado real del vehículo tal como está en ingreso_vehiculos
                        // Este debe coincidir exactamente con el que se muestra en los detalles
                        $estadoAMostrar = $estadoRealVehiculo;
                    } elseif (!empty($asignacionEstadoReal)) {
                        // Si no hay estado del vehículo pero hay asignación, usar el estado de la asignación
                        // Pero solo si no hay estado del vehículo (caso raro)
                        $estadoAMostrar = $asignacionEstadoReal;
                    } else {
                        // Si no tiene estado pero tiene agenda, está en espera de asignación
                        // Solo mostrar "Completado" si realmente salió (tiene FechaSalida)
                        if ($estaRetirado) {
                            $estadoAMostrar = 'Completado';
                        } else {
                            // Si no tiene estado y no salió, está en espera
                            $estadoAMostrar = 'En Espera';
                        }
                    }
                }
                
                // 4. LÓGICA ESPECIAL: Si hay múltiples solicitudes del mismo día
                if ($hayOtrasSolicitudesHoy) {
                    if ($esSolicitudMasTemprana) {
                        // Si es la solicitud más temprana, verificar si ya se completó y salió
                        if ($estaRetirado) {
                            // La más temprana ya se completó, mostrar "Completado"
                            $estadoAMostrar = 'Completado';
                        }
                        // Si no está retirado, mantener el estado real que ya se determinó arriba
                    } else {
                        // Si NO es la más temprana, esta es una solicitud nueva del mismo día
                        // Verificar si la más temprana ya se completó
                        // Si la más temprana ya salió, esta nueva debe mostrar su estado real
                        // (que puede ser "Ingresado", "En Espera", "Asignado", "En Progreso", "Completo" si está en taller)
                        // Solo mostrar "Completado" si esta solicitud específica también salió
                        if (!$estaRetirado) {
                            // No está retirado, mantener el estado real que ya se determinó arriba
                            // (Ingresado, En Espera, Asignado, En Progreso, Completo)
                        }
                        // Si está retirado, ya se estableció como "Completado" arriba
                    }
                }
            }
            
            // Aplicar el estado determinado
            $solicitud['Estado'] = $estadoAMostrar;
            
            // LÓGICA 2: Si la fecha de agenda ya pasó y el estado ORIGINAL es "Pendiente", 
            // mostrar como "Cancelada" (solo si no está retirado)
            if (!$estaRetirado) {
                $fechaAgenda = $solicitud['FechaAgenda'] ?? null;
                
                if ($fechaAgenda && $estadoOriginal === 'Pendiente') {
                    // Comparar fecha de agenda con fecha actual
                    try {
                        $fechaAgendaObj = new DateTime($fechaAgenda);
                        $fechaActual = new DateTime();
                        $fechaActual->setTime(0, 0, 0); // Solo comparar fechas, no horas
                        $fechaAgendaObj->setTime(0, 0, 0);
                        
                        // Si la fecha de agenda es anterior a hoy, está en el pasado
                        if ($fechaAgendaObj < $fechaActual) {
                            $solicitud['Estado'] = 'Cancelada';
                        }
                    } catch (Exception $e) {
                        // Si hay error al parsear la fecha, mantener el estado original
                        error_log("Error al parsear fecha de agenda en obtenerSolicitudesAgendamiento: " . $e->getMessage());
                    }
                }
            }
            
            // Marcar este ID como procesado
            $idsProcesados[$solicitudId] = true;
            
            // Guardar en el array de únicas usando la clave
            if (!empty($claveUnica)) {
                $solicitudesUnicas[$claveUnica] = $solicitud;
            } else {
                // Si no hay clave única, usar el ID de la solicitud como clave
                $clavePorID = 'ID_' . ($row['ID'] ?? 0);
                $solicitudesUnicas[$clavePorID] = $solicitud;
            }
        }
        
        // Convertir el array asociativo a array indexado y ordenar por fecha de creación descendente
        $solicitudes = array_values($solicitudesUnicas);
        
        // Ordenar por fecha de creación descendente (más recientes primero)
        // Si hay empate en fecha, usar el ID más alto (más reciente)
        usort($solicitudes, function($a, $b) {
            $fechaA = $a['FechaCreacion'] ?? '';
            $fechaB = $b['FechaCreacion'] ?? '';
            $comparacionFecha = strcmp($fechaB, $fechaA);
            
            // Si las fechas son iguales, comparar por ID (más alto = más reciente)
            if ($comparacionFecha === 0) {
                $idA = intval($a['ID'] ?? 0);
                $idB = intval($b['ID'] ?? 0);
                return $idB - $idA; // Orden descendente por ID
            }
            
            return $comparacionFecha; // Orden descendente por fecha
        });

        $conn->close();
        return $solicitudes;
    } catch (Exception $e) {
        error_log("Excepción en obtenerSolicitudesAgendamiento: " . $e->getMessage());
        if ($conn) {
            $conn->close();
        }
        throw $e;
    } catch (Error $e) {
        error_log("Error fatal en obtenerSolicitudesAgendamiento: " . $e->getMessage());
        if ($conn) {
            $conn->close();
        }
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

    // Obtener todas las horas disponibles para la fecha en el rango de 9:00 AM a 11:00 PM
    // Mostrar horas que empiezan entre 9:00 AM y 11:00 PM (23:00:00)
    $query = "SELECT 
                ID, HoraInicio, HoraFin, Disponible, Observaciones, Fecha
            FROM agenda_taller
            WHERE Fecha = '$fecha' 
            AND Disponible = 1
            AND HoraInicio >= '09:00:00'
            AND HoraInicio <= '23:00:00'
            ORDER BY HoraInicio ASC";

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
            
            // Asegurar que la fecha esté incluida en el resultado
            $row['Fecha'] = $fecha;
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
                // Obtener el nombre del supervisor
                $nombreSupervisor = 'Supervisor';
                $querySupervisor = "SELECT NombreUsuario FROM usuarios WHERE UsuarioID = $supervisor_id";
                $resultSupervisor = mysqli_query($conn, $querySupervisor);
                if ($resultSupervisor && mysqli_num_rows($resultSupervisor) > 0) {
                    $supervisor = mysqli_fetch_assoc($resultSupervisor);
                    $nombreSupervisor = $supervisor['NombreUsuario'];
                }
                
                $observaciones = "Asignada por el supervisor: $nombreSupervisor";
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

    $conn->begin_transaction();

    try {
        $fecha = $conn->real_escape_string($datos['fecha']);
        $hora_inicio = $conn->real_escape_string($datos['hora_inicio']);
        $hora_fin = $conn->real_escape_string($datos['hora_fin']);
        $disponible = isset($datos['disponible']) ? intval($datos['disponible']) : 1;
        $observaciones = !empty($datos['observaciones']) ? $conn->real_escape_string($datos['observaciones']) : NULL;

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
            $resultCheckDuplicado = $conn->query($queryCheckDuplicado);
            $duplicado = $resultCheckDuplicado->fetch_assoc();
            
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
            $resultCheckSolapamiento = $conn->query($queryCheckSolapamiento);
            $solapamiento = $resultCheckSolapamiento->fetch_assoc();
            
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
            $resultCheckDuplicado = $conn->query($queryCheckDuplicado);
            $duplicado = $resultCheckDuplicado->fetch_assoc();
            
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
            $resultCheckSolapamiento = $conn->query($queryCheckSolapamiento);
            $solapamiento = $resultCheckSolapamiento->fetch_assoc();
            
            if ($solapamiento['total'] > 0) {
                throw new Exception("La hora se solapa con otra hora existente en la misma fecha. Verifique que no haya conflictos de horario.");
            }
            
            // Verificar si la hora está asignada a alguna solicitud aprobada
            $queryCheckAsignada = "SELECT COUNT(*) as total 
                FROM solicitudes_agendamiento 
                WHERE AgendaID = $id 
                AND Estado = 'Aprobada'";
            $resultCheckAsignada = $conn->query($queryCheckAsignada);
            $asignada = $resultCheckAsignada->fetch_assoc();
            
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

        if (!$conn->query($query)) {
            throw new Exception("Error al gestionar agenda: " . $conn->error);
        }

        $agenda_id = empty($datos['id']) ? $conn->insert_id : $datos['id'];
        $conn->commit();
        
        // Obtener todos los datos de la agenda guardada para devolverlos al frontend
        $queryAgenda = "SELECT ID, Fecha, HoraInicio, HoraFin, Disponible, Observaciones, FechaCreacion, FechaActualizacion
                       FROM agenda_taller
                       WHERE ID = " . intval($agenda_id);
        $resultAgenda = $conn->query($queryAgenda);
        
        $agendaData = null;
        if ($resultAgenda) {
            if ($resultAgenda->num_rows > 0) {
                $row = $resultAgenda->fetch_assoc();
                $agendaData = [
                    'ID' => (int)$row['ID'],
                    'Fecha' => $row['Fecha'],
                    'HoraInicio' => $row['HoraInicio'],
                    'HoraFin' => $row['HoraFin'],
                    'Disponible' => (int)$row['Disponible'],
                    'Observaciones' => $row['Observaciones'] ? $row['Observaciones'] : '',
                    'FechaCreacion' => $row['FechaCreacion'],
                    'FechaActualizacion' => $row['FechaActualizacion']
                ];
            } else {
                error_log("Error: No se encontró la agenda con ID $agenda_id después de guardarla");
            }
        } else {
            error_log("Error al consultar agenda guardada: " . $conn->error);
        }
        
        $conn->close();

        return [
            'status' => 'success',
            'message' => empty($datos['id']) ? 'Agenda creada correctamente' : 'Agenda actualizada correctamente',
            'agenda_id' => $agenda_id,
            'data' => $agendaData
        ];
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
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

    try {
    $where = [];

    if ($filtroFecha) {
            $filtroFecha = $conn->real_escape_string($filtroFecha);
            $where[] = "Fecha = '$filtroFecha'";
    }

    if ($filtroDisponible !== null) {
        $filtroDisponible = intval($filtroDisponible);
            $where[] = "Disponible = $filtroDisponible";
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Consulta para obtener TODOS los datos de agenda_taller
    $query = "SELECT 
                    ID,
                    Fecha,
                    HoraInicio,
                    HoraFin,
                    Disponible,
                    Observaciones,
                    FechaCreacion,
                    FechaActualizacion
                  FROM agenda_taller
              $whereClause
                  ORDER BY ID DESC";

        $result = $conn->query($query);
    if (!$result) {
            $error = $conn->error;
            $conn->close();
            return ['status' => 'error', 'message' => 'Error en consulta: ' . $error];
    }

    $agendas = [];

        while ($row = $result->fetch_assoc()) {
        $agendas[] = [
                'ID' => (int)$row['ID'],
            'Fecha' => $row['Fecha'],
            'HoraInicio' => $row['HoraInicio'],
            'HoraFin' => $row['HoraFin'],
                'Disponible' => (int)$row['Disponible'],
                'Observaciones' => $row['Observaciones'] ? $row['Observaciones'] : '',
            'FechaCreacion' => $row['FechaCreacion'],
                'FechaActualizacion' => $row['FechaActualizacion']
        ];
    }

        $conn->close();

    return [
        'status' => 'success',
        'data' => $agendas
    ];
    } catch (Exception $e) {
        if ($conn) {
            $conn->close();
        }
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
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

    $conn->begin_transaction();

    try {
        $agenda_id = intval($agenda_id);

        // Verificar si está asignada a alguna solicitud aprobada
        $queryCheck = "SELECT COUNT(*) as total 
                      FROM solicitudes_agendamiento 
                      WHERE AgendaID = $agenda_id 
                      AND Estado = 'Aprobada'";
        $resultCheck = $conn->query($queryCheck);
        $check = $resultCheck->fetch_assoc();

        if ($check['total'] > 0) {
            throw new Exception("No se puede eliminar una agenda que está asignada a una solicitud aprobada");
        }

        // Si hay solicitudes pendientes, actualizar su AgendaID a NULL
        $queryUpdateSolicitudes = "UPDATE solicitudes_agendamiento 
                                  SET AgendaID = NULL 
                                  WHERE AgendaID = $agenda_id 
                                  AND Estado = 'Pendiente'";

        if (!$conn->query($queryUpdateSolicitudes)) {
            throw new Exception("Error al actualizar solicitudes: " . $conn->error);
        }

        // Eliminar la agenda
        $query = "DELETE FROM agenda_taller WHERE ID = $agenda_id";

        if (!$conn->query($query)) {
            throw new Exception("Error al eliminar agenda: " . $conn->error);
        }

        $conn->commit();
        $conn->close();

        return [
            'status' => 'success',
            'message' => 'Agenda eliminada correctamente'
        ];
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
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

/**
 * Marca todos los vehículos como salidos del taller, excepto el especificado
 * @param string $placaExcluir - Placa del vehículo que NO se marcará como salido
 * @return array - Resultado de la operación
 */
function marcarVehiculosComoSalidos($placaExcluir = 'WLVY22') {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    try {
        $placaExcluir = mysqli_real_escape_string($conn, $placaExcluir);
        
        // Marcar todos los vehículos completados como salidos, excepto la placa especificada
        $query = "UPDATE ingreso_vehiculos 
                  SET Estado = 'Finalizado', 
                      FechaSalida = NOW() 
                  WHERE Estado = 'Completado' 
                  AND Placa != '$placaExcluir'";
        
        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al marcar vehículos: " . mysqli_error($conn));
        }
        
        $afectados = mysqli_affected_rows($conn);
            mysqli_close($conn);
        
        return [
            'status' => 'success',
            'message' => "Se marcaron $afectados vehículos como salidos",
            'vehiculos_afectados' => $afectados
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
 * Obtiene o guarda la configuración de horarios del taller
 */
function gestionarConfiguracionHorarios($datos = null) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    try {
        // Verificar si existe la tabla de configuración
        $checkTable = "SHOW TABLES LIKE 'configuracion_taller'";
        $resultCheck = mysqli_query($conn, $checkTable);
        
        if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
            // Crear tabla si no existe
            $createTable = "CREATE TABLE IF NOT EXISTS `configuracion_taller` (
                `ID` INT(11) NOT NULL AUTO_INCREMENT,
                `HoraApertura` TIME NOT NULL DEFAULT '08:00:00',
                `HoraCierre` TIME NOT NULL DEFAULT '20:00:00',
                `DuracionCitas` INT(11) NOT NULL DEFAULT 60,
                `IntervaloCitas` INT(11) NOT NULL DEFAULT 0,
                `DiasOperacion` VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6,0',
                `Operacion247` TINYINT(1) NOT NULL DEFAULT 1,
                `FechaActualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`ID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if (!mysqli_query($conn, $createTable)) {
                throw new Exception("Error al crear tabla de configuración: " . mysqli_error($conn));
            }
            
            // Insertar configuración por defecto
            $insertDefault = "INSERT INTO configuracion_taller (HoraApertura, HoraCierre, DuracionCitas, IntervaloCitas, DiasOperacion, Operacion247) 
                             VALUES ('08:00:00', '20:00:00', 60, 0, '1,2,3,4,5,6,0', 1)";
            mysqli_query($conn, $insertDefault);
        }

        if ($datos === null) {
            // Obtener configuración
            $query = "SELECT * FROM configuracion_taller ORDER BY ID DESC LIMIT 1";
            $result = mysqli_query($conn, $query);
            
            if (!$result || mysqli_num_rows($result) == 0) {
                // Retornar configuración por defecto
            return [
                    'status' => 'success',
                    'data' => [
                        'HoraApertura' => '08:00:00',
                        'HoraCierre' => '20:00:00',
                        'DuracionCitas' => 60,
                        'IntervaloCitas' => 0,
                        'DiasOperacion' => '1,2,3,4,5,6,0',
                        'Operacion247' => 1
                    ]
                ];
            }
            
            $config = mysqli_fetch_assoc($result);
            mysqli_close($conn);
            
            return [
                'status' => 'success',
                'data' => $config
            ];
        } else {
            // Guardar configuración
            $horaApertura = mysqli_real_escape_string($conn, $datos['hora_apertura'] ?? '08:00:00');
            $horaCierre = mysqli_real_escape_string($conn, $datos['hora_cierre'] ?? '20:00:00');
            $duracionCitas = intval($datos['duracion_citas'] ?? 60);
            $intervaloCitas = intval($datos['intervalo_citas'] ?? 0);
            $diasOperacion = mysqli_real_escape_string($conn, $datos['dias_operacion'] ?? '1,2,3,4,5,6,0');
            $operacion247 = isset($datos['operacion_247']) ? intval($datos['operacion_247']) : 1;
            
            // Verificar si ya existe configuración
            $checkConfig = "SELECT ID FROM configuracion_taller ORDER BY ID DESC LIMIT 1";
            $resultCheck = mysqli_query($conn, $checkConfig);
            
            if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
                // Actualizar
                $query = "UPDATE configuracion_taller 
                         SET HoraApertura = '$horaApertura',
                             HoraCierre = '$horaCierre',
                             DuracionCitas = $duracionCitas,
                             IntervaloCitas = $intervaloCitas,
                             DiasOperacion = '$diasOperacion',
                             Operacion247 = $operacion247
                         ORDER BY ID DESC LIMIT 1";
            } else {
                // Insertar
                $query = "INSERT INTO configuracion_taller (HoraApertura, HoraCierre, DuracionCitas, IntervaloCitas, DiasOperacion, Operacion247) 
                         VALUES ('$horaApertura', '$horaCierre', $duracionCitas, $intervaloCitas, '$diasOperacion', $operacion247)";
            }
        
        if (!mysqli_query($conn, $query)) {
                throw new Exception("Error al guardar configuración: " . mysqli_error($conn));
            }
            
            mysqli_close($conn);
            
            return [
                'status' => 'success',
                'message' => 'Configuración guardada correctamente'
            ];
        }
    } catch (Exception $e) {
        mysqli_close($conn);
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Genera horarios automáticamente según la configuración del taller
 */
function generarHorariosAutomaticos($fechaDesde, $fechaHasta) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    try {
        // Obtener configuración
        $configResult = gestionarConfiguracionHorarios();
        if ($configResult['status'] !== 'success') {
            throw new Exception("Error al obtener configuración");
        }
        
        $config = $configResult['data'];
        $horaApertura = $config['HoraApertura'];
        $horaCierre = $config['HoraCierre'];
        $duracionCitas = intval($config['DuracionCitas']);
        $intervaloCitas = intval($config['IntervaloCitas']);
        $diasOperacion = explode(',', $config['DiasOperacion']);
        $operacion247 = intval($config['Operacion247']);
        
        // Si es 24/7, usar horario completo
        if ($operacion247) {
            $horaApertura = '00:00:00';
            $horaCierre = '23:59:59';
        }
        
        mysqli_begin_transaction($conn);
        
        $fechaDesdeObj = new DateTime($fechaDesde);
        $fechaHastaObj = new DateTime($fechaHasta);
        $fechaHastaObj->modify('+1 day'); // Incluir el día final
        
        $horariosCreados = 0;
        $horariosExistentes = 0;
        
        // Iterar por cada día en el rango
        $fechaActual = clone $fechaDesdeObj;
        while ($fechaActual < $fechaHastaObj) {
            $fecha = $fechaActual->format('Y-m-d');
            $diaSemana = intval($fechaActual->format('w')); // 0 = domingo, 1 = lunes, etc.
            
            // Verificar si el día está en los días de operación
            if (in_array((string)$diaSemana, $diasOperacion) || $operacion247) {
                // Generar horarios para este día
                $horaInicioObj = new DateTime($fecha . ' ' . $horaApertura);
                $horaCierreObj = new DateTime($fecha . ' ' . $horaCierre);
                
                $horaActual = clone $horaInicioObj;
                
                while ($horaActual < $horaCierreObj) {
                    $horaInicio = $horaActual->format('H:i:s');
                    $horaActual->modify("+$duracionCitas minutes");
                    $horaFin = $horaActual->format('H:i:s');
                    
                    // Verificar si ya existe este horario
                    $checkQuery = "SELECT ID FROM agenda_taller 
                                  WHERE Fecha = '$fecha' 
                                  AND HoraInicio = '$horaInicio' 
                                  AND HoraFin = '$horaFin'";
                    $checkResult = mysqli_query($conn, $checkQuery);
                    
                    if (!$checkResult || mysqli_num_rows($checkResult) == 0) {
                        // Crear nuevo horario
                        $insertQuery = "INSERT INTO agenda_taller (Fecha, HoraInicio, HoraFin, Disponible) 
                                      VALUES ('$fecha', '$horaInicio', '$horaFin', 1)";
                        
                        if (mysqli_query($conn, $insertQuery)) {
                            $horariosCreados++;
                        }
                    } else {
                        $horariosExistentes++;
                    }
                    
                    // Agregar intervalo entre citas
                    if ($intervaloCitas > 0) {
                        $horaActual->modify("+$intervaloCitas minutes");
                    }
                }
            }
            
            $fechaActual->modify('+1 day');
        }
        
        mysqli_commit($conn);
        mysqli_close($conn);
        
        return [
            'status' => 'success',
            'message' => "Se generaron $horariosCreados nuevos horarios. $horariosExistentes horarios ya existían.",
            'horarios_creados' => $horariosCreados,
            'horarios_existentes' => $horariosExistentes
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
 * Obtiene estadísticas de la agenda
 */
function obtenerEstadisticasAgenda() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    try {
        // Horas disponibles
        $queryDisponibles = "SELECT COUNT(*) as total FROM agenda_taller WHERE Disponible = 1";
        $resultDisponibles = mysqli_query($conn, $queryDisponibles);
        $disponibles = mysqli_fetch_assoc($resultDisponibles)['total'];
        
        // Horas ocupadas (asignadas a solicitudes aprobadas)
        $queryOcupadas = "SELECT COUNT(DISTINCT a.ID) as total 
                         FROM agenda_taller a
                         INNER JOIN solicitudes_agendamiento s ON a.ID = s.AgendaID
                         WHERE s.Estado = 'Aprobada'";
        $resultOcupadas = mysqli_query($conn, $queryOcupadas);
        $ocupadas = mysqli_fetch_assoc($resultOcupadas)['total'];
        
        // Solicitudes pendientes
        $queryPendientes = "SELECT COUNT(*) as total FROM solicitudes_agendamiento WHERE Estado = 'Pendiente'";
        $resultPendientes = mysqli_query($conn, $queryPendientes);
        $pendientes = mysqli_fetch_assoc($resultPendientes)['total'];
        
        // Próximos 7 días
        $fechaHoy = date('Y-m-d');
        $fecha7Dias = date('Y-m-d', strtotime('+7 days'));
        $queryProximos = "SELECT COUNT(*) as total 
                         FROM agenda_taller 
                         WHERE Fecha >= '$fechaHoy' 
                         AND Fecha <= '$fecha7Dias' 
                         AND Disponible = 1";
        $resultProximos = mysqli_query($conn, $queryProximos);
        $proximos = mysqli_fetch_assoc($resultProximos)['total'];
        
        mysqli_close($conn);
        
        return [
            'status' => 'success',
            'data' => [
                'disponibles' => intval($disponibles),
                'ocupadas' => intval($ocupadas),
                'pendientes' => intval($pendientes),
                'proximos_7_dias' => intval($proximos)
            ]
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
 * Obtiene horarios por rango de fechas para el calendario
 */
function obtenerHorariosPorRango($fechaDesde, $fechaHasta) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    try {
        $fechaDesde = mysqli_real_escape_string($conn, $fechaDesde);
        $fechaHasta = mysqli_real_escape_string($conn, $fechaHasta);
        
        $query = "SELECT 
                    a.ID,
                    a.Fecha,
                    a.HoraInicio,
                    a.HoraFin,
                    a.Disponible,
                    a.Observaciones,
                    COUNT(DISTINCT CASE WHEN s.Estado = 'Aprobada' THEN s.ID END) as SolicitudesAprobadas,
                    COUNT(DISTINCT CASE WHEN s.Estado = 'Pendiente' THEN s.ID END) as SolicitudesPendientes
                  FROM agenda_taller a
                  LEFT JOIN solicitudes_agendamiento s ON a.ID = s.AgendaID
                  WHERE a.Fecha >= '$fechaDesde' AND a.Fecha <= '$fechaHasta'
                  GROUP BY a.ID
                  ORDER BY a.Fecha ASC, a.HoraInicio ASC";
        
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception("Error en consulta: " . mysqli_error($conn));
        }
        
        $horarios = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $horarios[] = [
                'ID' => $row['ID'],
                'Fecha' => $row['Fecha'],
                'HoraInicio' => $row['HoraInicio'],
                'HoraFin' => $row['HoraFin'],
                'Disponible' => (bool)$row['Disponible'],
                'Observaciones' => $row['Observaciones'],
                'SolicitudesAprobadas' => intval($row['SolicitudesAprobadas']),
                'SolicitudesPendientes' => intval($row['SolicitudesPendientes'])
            ];
        }
        
        mysqli_close($conn);
        
        return [
            'status' => 'success',
            'data' => $horarios
        ];
    } catch (Exception $e) {
        mysqli_close($conn);
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

?>

