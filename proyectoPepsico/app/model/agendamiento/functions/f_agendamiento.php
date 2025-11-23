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
        // Fecha y hora se asignarán cuando el supervisor apruebe, usar valores por defecto
        $fecha_solicitada = !empty($datos['fecha_solicitada']) ? mysqli_real_escape_string($conn, $datos['fecha_solicitada']) : date('Y-m-d');
        $hora_solicitada = !empty($datos['hora_solicitada']) ? mysqli_real_escape_string($conn, $datos['hora_solicitada']) : '08:00';

        // Verificar que no exista una solicitud pendiente para la misma placa
        $checkQuery = "SELECT ID FROM solicitudes_agendamiento 
                       WHERE Placa = '$placa' 
                       AND Estado = 'Pendiente'";
        $checkResult = mysqli_query($conn, $checkQuery);
        if (mysqli_num_rows($checkResult) > 0) {
            throw new Exception("Ya existe una solicitud pendiente para esta placa");
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
            Observaciones, FechaSolicitada, HoraSolicitada, Estado";
        $valores = "$chofer_id, '$placa', '$tipo_vehiculo', '$marca', '$modelo',
            " . ($anio ? $anio : "NULL") . ",
            '$conductor_nombre',
            '$proposito',
            " . ($observaciones ? "'" . mysqli_real_escape_string($conn, $observaciones) . "'" : "NULL") . ",
            '$fecha_solicitada', '$hora_solicitada', 'Pendiente'";
        
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
        $where[] = "s.FechaSolicitada >= '$fecha_desde'";
    }

    if (!empty($filtros['fecha_hasta'])) {
        $fecha_hasta = mysqli_real_escape_string($conn, $filtros['fecha_hasta']);
        $where[] = "s.FechaSolicitada <= '$fecha_hasta'";
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
 * Solo retorna horas en el rango laboral de 9:00 a 18:00 (6pm)
 */
function obtenerHorasDisponibles($fecha) {
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

    // Obtener todas las horas disponibles para la fecha en el rango laboral (9:00 a 18:00)
    $query = "SELECT 
                ID, HoraInicio, HoraFin, Disponible, Observaciones
            FROM agenda_taller
            WHERE Fecha = '$fecha' 
            AND Disponible = 1
            AND HoraInicio >= '09:00:00'
            AND HoraFin <= '18:00:00'
            ORDER BY HoraInicio";

    $result = mysqli_query($conn, $query);
    $horas = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Verificar que no esté asignada a otra solicitud aprobada
            $agenda_id = $row['ID'];
            $checkQuery = "SELECT COUNT(*) as total 
                          FROM solicitudes_agendamiento 
                          WHERE AgendaID = $agenda_id 
                          AND Estado = 'Aprobada'";
            $checkResult = mysqli_query($conn, $checkQuery);
            $checkRow = mysqli_fetch_assoc($checkResult);
            
            if ($checkRow['total'] == 0) {
                $horas[] = $row;
            }
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

        // Si no se proporciona agenda_id, buscar una hora disponible
        if (empty($agenda_id)) {
            $fecha_solicitada = $solicitud['FechaSolicitada'];
            $hora_solicitada = $solicitud['HoraSolicitada'];
            
            // Buscar una hora disponible que coincida o esté cerca
            $queryAgenda = "SELECT ID FROM agenda_taller 
                           WHERE Fecha = '$fecha_solicitada' 
                           AND Disponible = 1
                           AND HoraInicio <= '$hora_solicitada'
                           AND HoraFin >= '$hora_solicitada'
                           AND ID NOT IN (
                               SELECT AgendaID FROM solicitudes_agendamiento 
                               WHERE AgendaID IS NOT NULL AND Estado = 'Aprobada'
                           )
                           LIMIT 1";
            
            $resultAgenda = mysqli_query($conn, $queryAgenda);
            
            if (mysqli_num_rows($resultAgenda) == 0) {
                throw new Exception("No hay horas disponibles para la fecha y hora solicitada");
            }
            
            $agenda = mysqli_fetch_assoc($resultAgenda);
            $agenda_id = $agenda['ID'];
        } else {
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

        // Si se proporciona un mecánico, crear una asignación automática
        if ($mecanico_id) {
            $mecanico_id = intval($mecanico_id);
            
            // Obtener información del vehículo de la solicitud
            $queryVehiculo = "SELECT Placa, Marca, Modelo, TipoVehiculo FROM solicitudes_agendamiento WHERE ID = $solicitud_id";
            $resultVehiculo = mysqli_query($conn, $queryVehiculo);
            $solicitud = mysqli_fetch_assoc($resultVehiculo);
            
            // Verificar si el vehículo ya existe en ingreso_vehiculos
            $placa = mysqli_real_escape_string($conn, $solicitud['Placa']);
            $queryCheckVehiculo = "SELECT ID FROM ingreso_vehiculos WHERE Placa = '$placa' AND Estado = 'Ingresado' LIMIT 1";
            $resultCheckVehiculo = mysqli_query($conn, $queryCheckVehiculo);
            
            if (mysqli_num_rows($resultCheckVehiculo) > 0) {
                $vehiculo = mysqli_fetch_assoc($resultCheckVehiculo);
                $vehiculo_id = $vehiculo['ID'];
                
                // Crear asignación al mecánico
                // Intentar diferentes rutas para encontrar f_consulta.php
                $rutas_posibles = [
                    __DIR__ . '/../../consulta/functions/f_consulta.php',
                    __DIR__ . '/../consulta/functions/f_consulta.php'
                ];
                
                $f_consulta_cargado = false;
                foreach ($rutas_posibles as $ruta) {
                    if (file_exists($ruta)) {
                        require_once $ruta;
                        $f_consulta_cargado = true;
                        break;
                    }
                }
                
                if ($f_consulta_cargado && function_exists('asignarMecanico')) {
                    $observaciones = "Asignación automática desde solicitud de agendamiento #$solicitud_id";
                    $resultado_asignacion = asignarMecanico($vehiculo_id, $mecanico_id, $observaciones);
                    
                    if (isset($resultado_asignacion['status']) && $resultado_asignacion['status'] === 'error') {
                        error_log("Error al asignar mecánico: " . $resultado_asignacion['message']);
                        // No lanzar excepción, solo registrar el error
                    }
                } else {
                    error_log("No se pudo cargar f_consulta.php o la función asignarMecanico no existe");
                    // No lanzar excepción, solo registrar el error
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

        if (empty($datos['id'])) {
            // Crear nueva hora
            $query = "INSERT INTO agenda_taller (Fecha, HoraInicio, HoraFin, Disponible, Observaciones)
                     VALUES ('$fecha', '$hora_inicio', '$hora_fin', $disponible, " . ($observaciones ? "'$observaciones'" : "NULL") . ")";
        } else {
            // Actualizar hora existente
            $id = intval($datos['id']);
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
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    } finally {
        mysqli_close($conn);
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
        $fecha_agenda = $solicitud['FechaSolicitada'];
    }
    
    $hora_agenda = !empty($solicitud['HoraInicio']) ? $solicitud['HoraInicio'] : $solicitud['HoraSolicitada'];

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

