<?php
require_once __DIR__ . '../../../../config/conexion.php';
require_once __DIR__ . '/../../../../pages/general/funciones_notificaciones.php';

function obtenerTareasMecanico($mecanico_id) {
    error_log("=== obtenerTareasMecanico ===");
    error_log("Mecánico ID recibido: " . $mecanico_id);
    
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos");
        return [];
    }

    $mecanico_id = mysqli_real_escape_string($conn, $mecanico_id);
    error_log("Mecánico ID escapado: " . $mecanico_id);

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

    // Si tiene MotivoPausa pero no tiene estado, considerar como "En Pausa"
    $estadoField = $columnExists 
        ? "COALESCE(NULLIF(a.Estado, ''), CASE WHEN a.MotivoPausa IS NOT NULL AND a.MotivoPausa != '' THEN 'En Pausa' ELSE 'Asignado' END) AS Estado"
        : "COALESCE(NULLIF(a.Estado, ''), 'Asignado') AS Estado";

    $query = "SELECT 
                a.ID AS AsignacionID,
                v.ID AS VehiculoID,
                COALESCE(sa.Placa, v.Placa) AS Placa,
                COALESCE(sa.TipoVehiculo, v.TipoVehiculo) AS TipoVehiculo,
                COALESCE(sa.Marca, v.Marca) AS Marca,
                COALESCE(sa.Modelo, v.Modelo) AS Modelo,
                COALESCE(sa.ConductorNombre, v.ConductorNombre) AS ConductorNombre,
                sa.Proposito,
                sa.Observaciones AS ObservacionesSolicitud,
                DATE_FORMAT(a.FechaAsignacion, '%d/%m/%Y %H:%i') as FechaAsignacion,
                $estadoField,
                a.Observaciones,
                " . ($columnExists ? "a.MotivoPausa," : "") . "
                (SELECT DATE_FORMAT(am.FechaAvance, '%d/%m/%Y %H:%i') 
                 FROM avances_mecanico am 
                 WHERE am.AsignacionID = a.ID 
                 ORDER BY am.FechaAvance DESC LIMIT 1) as UltimaFechaAvance,
                (SELECT am.Descripcion 
                 FROM avances_mecanico am 
                 WHERE am.AsignacionID = a.ID 
                 ORDER BY am.FechaAvance DESC LIMIT 1) as UltimaDescripcion
            FROM asignaciones_mecanico a
            INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
            LEFT JOIN solicitudes_agendamiento sa ON v.Placa COLLATE utf8mb4_unicode_ci = sa.Placa COLLATE utf8mb4_unicode_ci
                AND sa.Estado IN ('Aprobada', 'Ingresado')
            WHERE a.MecanicoID = '$mecanico_id'
            ORDER BY a.FechaAsignacion DESC";

    error_log("Query ejecutada: " . $query);

    $result = mysqli_query($conn, $query);

    if (!$result) {
        error_log("Error en consulta obtenerTareasMecanico: " . mysqli_error($conn));
        mysqli_close($conn);
        return [];
    }

    $tareas = [];
    $tareasPorVehiculo = []; // Para eliminar duplicados: agrupar por placa/vehiculo
    $count = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $count++;
        // Formatear último avance
        $row['UltimoAvance'] = null;
        if ($row['UltimaFechaAvance'] && $row['UltimaDescripcion']) {
            $row['UltimoAvance'] = [
                'Fecha' => $row['UltimaFechaAvance'],
                'Descripcion' => $row['UltimaDescripcion']
            ];
        }
        
        // Usar Placa como clave para identificar duplicados
        $placa = $row['Placa'] ?? '';
        $asignacionId = intval($row['AsignacionID'] ?? 0);
        
        // Si ya existe una asignación para esta placa, mantener solo la más reciente (ID más alto)
        if (isset($tareasPorVehiculo[$placa])) {
            $asignacionExistente = intval($tareasPorVehiculo[$placa]['AsignacionID'] ?? 0);
            if ($asignacionId > $asignacionExistente) {
                // Esta asignación es más reciente, reemplazar la anterior
                $tareasPorVehiculo[$placa] = $row;
            }
            // Si es más antigua, ignorarla
        } else {
            // Primera vez que vemos esta placa, agregarla
            $tareasPorVehiculo[$placa] = $row;
        }
    }
    
    // Convertir el array asociativo de vuelta a un array indexado
    $tareas = array_values($tareasPorVehiculo);
    
    // Log de la primera tarea para debug
    if (count($tareas) > 0) {
        error_log("Primera tarea encontrada (después de filtrar duplicados): " . json_encode($tareas[0]));
    }
    
    error_log("Total de tareas encontradas (antes de filtrar): " . $count);
    error_log("Total de tareas únicas (después de filtrar duplicados): " . count($tareas));
    mysqli_free_result($result);
    mysqli_close($conn);
    return $tareas;
}
function obtenerDetallesAsignacion($asignacion_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return null;
    }

    $asignacion_id = mysqli_real_escape_string($conn, $asignacion_id);

    $query = "SELECT 
                a.ID,
                v.ID AS VehiculoID,
                COALESCE(sa.Placa, v.Placa) AS Placa,
                COALESCE(sa.TipoVehiculo, v.TipoVehiculo) AS TipoVehiculo,
                COALESCE(sa.Marca, v.Marca) AS Marca,
                COALESCE(sa.Modelo, v.Modelo) AS Modelo,
                COALESCE(sa.ConductorNombre, v.ConductorNombre) AS ConductorNombre,
                sa.Proposito,
                sa.Observaciones AS ObservacionesSolicitud,
                a.Estado,
                a.Observaciones
            FROM asignaciones_mecanico a
            INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
            LEFT JOIN solicitudes_agendamiento sa ON v.Placa COLLATE utf8mb4_unicode_ci = sa.Placa COLLATE utf8mb4_unicode_ci
                AND sa.Estado IN ('Aprobada', 'Ingresado')
            WHERE a.ID = '$asignacion_id'
            ORDER BY sa.FechaCreacion DESC
            LIMIT 1";

    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("Error en consulta obtenerDetallesAsignacion: " . mysqli_error($conn));
        mysqli_close($conn);
        return null;
    }

    $asignacion = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_close($conn);
    
    return $asignacion;
}

function obtenerHistorialAvances($asignacion_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['vehiculo' => null, 'avances' => []];
    }

    $asignacion_id = mysqli_real_escape_string($conn, $asignacion_id);

    // Obtener información del vehículo desde solicitudes_agendamiento
    $queryVehiculo = "SELECT 
                        COALESCE(sa.Placa, v.Placa) AS Placa,
                        COALESCE(sa.Marca, v.Marca) AS Marca,
                        COALESCE(sa.Modelo, v.Modelo) AS Modelo,
                        sa.Proposito
                    FROM asignaciones_mecanico a
                    INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                    LEFT JOIN solicitudes_agendamiento sa ON v.Placa COLLATE utf8mb4_unicode_ci = sa.Placa COLLATE utf8mb4_unicode_ci
                        AND sa.Estado IN ('Aprobada', 'Ingresado')
                    WHERE a.ID = '$asignacion_id'
                    ORDER BY sa.FechaCreacion DESC
                    LIMIT 1";
    
    $resultVehiculo = mysqli_query($conn, $queryVehiculo);
    
    if (!$resultVehiculo) {
        error_log("Error en consulta obtenerHistorialAvances (vehiculo): " . mysqli_error($conn));
        mysqli_close($conn);
        return ['vehiculo' => null, 'avances' => []];
    }
    
    $vehiculo = mysqli_fetch_assoc($resultVehiculo);
    mysqli_free_result($resultVehiculo);

    // Verificar si existe la columna Fotos
    $checkColumnaFotos = "SHOW COLUMNS FROM avances_mecanico LIKE 'Fotos'";
    $resultCheckFotos = mysqli_query($conn, $checkColumnaFotos);
    $columnaFotosExiste = ($resultCheckFotos && mysqli_num_rows($resultCheckFotos) > 0);
    
    // Obtener avances (incluyendo Fotos si la columna existe)
    $queryAvances = "SELECT 
                        ID,
                        Descripcion,
                        Estado,
                        " . ($columnaFotosExiste ? "Fotos," : "NULL AS Fotos,") . "
                        DATE_FORMAT(FechaAvance, '%d/%m/%Y %H:%i') as FechaAvance,
                        DATE_FORMAT(FechaAvance, '%Y-%m-%d %H:%i:%s') as FechaAvanceRaw
                    FROM avances_mecanico 
                    WHERE AsignacionID = '$asignacion_id' 
                    ORDER BY FechaAvance ASC";
    
    $resultAvances = mysqli_query($conn, $queryAvances);
    
    $avances = [];
    if ($resultAvances) {
        while ($row = mysqli_fetch_assoc($resultAvances)) {
            // Decodificar JSON de fotos si existe
            if (!empty($row['Fotos']) && $row['Fotos'] !== 'NULL' && $row['Fotos'] !== null) {
                $fotosDecoded = json_decode($row['Fotos'], true);
                $row['Fotos'] = is_array($fotosDecoded) ? $fotosDecoded : [];
            } else {
                $row['Fotos'] = [];
            }
            $avances[] = $row;
        }
        mysqli_free_result($resultAvances);
    } else {
        error_log("Error en consulta obtenerHistorialAvances (avances): " . mysqli_error($conn));
    }

    mysqli_close($conn);
    return [
        'vehiculo' => $vehiculo,
        'avances' => $avances
    ];
}

/**
 * Obtiene el ID del chofer asociado a un vehículo
 */
function obtenerChoferIDDelVehiculo($vehiculo_id, $conn) {
    $vehiculo_id = intval($vehiculo_id);
    
    // Primero intentar obtener el ChoferID desde solicitudes_agendamiento
    $queryChofer = "SELECT sa.ChoferID, iv.Placa, iv.ConductorNombre
                    FROM ingreso_vehiculos iv
                    LEFT JOIN solicitudes_agendamiento sa ON iv.Placa COLLATE utf8mb4_unicode_ci = sa.Placa COLLATE utf8mb4_unicode_ci
                        AND sa.Estado IN ('Aprobada', 'Ingresado')
                    WHERE iv.ID = $vehiculo_id
                    ORDER BY sa.FechaCreacion DESC
                    LIMIT 1";
    
    $resultChofer = mysqli_query($conn, $queryChofer);
    if ($resultChofer && $row = mysqli_fetch_assoc($resultChofer)) {
        // Si hay ChoferID en la solicitud, usarlo
        if (!empty($row['ChoferID'])) {
            return intval($row['ChoferID']);
        }
        
        // Si no hay ChoferID pero hay ConductorNombre, buscar por nombre en usuarios
        if (!empty($row['ConductorNombre'])) {
            $conductorNombre = mysqli_real_escape_string($conn, $row['ConductorNombre']);
            $queryUsuario = "SELECT UsuarioID FROM usuarios WHERE NombreUsuario = '$conductorNombre' AND Rol = 'Chofer' LIMIT 1";
            $resultUsuario = mysqli_query($conn, $queryUsuario);
            if ($resultUsuario && $usuarioRow = mysqli_fetch_assoc($resultUsuario)) {
                return intval($usuarioRow['UsuarioID']);
            }
        }
    }
    
    return null;
}

function registrarAvance($asignacion_id, $descripcion, $estado) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    // Escapar datos
    $asignacion_id = mysqli_real_escape_string($conn, $asignacion_id);
    $descripcion = mysqli_real_escape_string($conn, $descripcion);
    $estado = mysqli_real_escape_string($conn, $estado);

    mysqli_begin_transaction($conn);

    try {
        // 1. Insertar el avance
        $queryAvance = "INSERT INTO avances_mecanico (AsignacionID, Descripcion, Estado) VALUES ('$asignacion_id', '$descripcion', '$estado')";
        $resultAvance = mysqli_query($conn, $queryAvance);

        if (!$resultAvance) {
            throw new Exception('Error al insertar avance: ' . mysqli_error($conn));
        }

        // 2. Actualizar el estado de la asignación
        $queryAsignacion = "UPDATE asignaciones_mecanico SET Estado = '$estado' WHERE ID = '$asignacion_id'";
        $resultAsignacion = mysqli_query($conn, $queryAsignacion);

        if (!$resultAsignacion) {
            throw new Exception('Error al actualizar asignación: ' . mysqli_error($conn));
        }

        // 3. Si el estado es "Completado", actualizar el estado del vehículo y notificar al chofer
        if ($estado === 'Completado') {
            // Primero obtener el VehiculoID
            $queryGetVehiculo = "SELECT VehiculoID FROM asignaciones_mecanico WHERE ID = '$asignacion_id'";
            $resultVehiculo = mysqli_query($conn, $queryGetVehiculo);
            
            if ($resultVehiculo && $row = mysqli_fetch_assoc($resultVehiculo)) {
                $vehiculo_id = $row['VehiculoID'];
                $queryUpdateVehiculo = "UPDATE ingreso_vehiculos SET Estado = 'Completado' WHERE ID = '$vehiculo_id'";
                $resultUpdateVehiculo = mysqli_query($conn, $queryUpdateVehiculo);
                
                if (!$resultUpdateVehiculo) {
                    throw new Exception('Error al actualizar estado del vehículo: ' . mysqli_error($conn));
                }
                
                // Obtener información del vehículo para la notificación
                $queryInfoVehiculo = "SELECT Placa, ConductorNombre FROM ingreso_vehiculos WHERE ID = '$vehiculo_id'";
                $resultInfoVehiculo = mysqli_query($conn, $queryInfoVehiculo);
                $infoVehiculo = null;
                if ($resultInfoVehiculo && $infoRow = mysqli_fetch_assoc($resultInfoVehiculo)) {
                    $infoVehiculo = $infoRow;
                }
                
                // Obtener el ID del chofer y notificar
                $chofer_id = obtenerChoferIDDelVehiculo($vehiculo_id, $conn);
                if ($chofer_id && $infoVehiculo) {
                    $placa = $infoVehiculo['Placa'];
                    $conductorNombre = $infoVehiculo['ConductorNombre'] ?? 'el chofer';
                    $mensaje = "El vehículo con placa <strong>$placa</strong> ha sido completado por el mecánico.\n\n";
                    $mensaje .= "Por favor, acuda al taller para retirar el vehículo.\n\n";
                    $mensaje .= "El guardia procesará la salida cuando llegue.";
                    $titulo = "Vehículo Listo para Retiro - Placa: $placa";
                    $modulo = "control_ingreso";
                    $enlace = "control_ingreso.php";
                    
                    crearNotificacion([$chofer_id], $titulo, $mensaje, $modulo, $enlace);
                }
            }
            mysqli_free_result($resultVehiculo);
        }

        mysqli_commit($conn);
        return ['status' => 'success', 'message' => 'Avance registrado correctamente'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
        mysqli_close($conn);
    }
}

function registrarAvanceConFotos($asignacion_id, $descripcion, $estado, $fotos = []) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    // Escapar datos
    $asignacion_id = mysqli_real_escape_string($conn, $asignacion_id);
    $descripcion = mysqli_real_escape_string($conn, $descripcion);
    $estado = mysqli_real_escape_string($conn, $estado);

    mysqli_begin_transaction($conn);

    try {
        // Procesar fotos si existen
        $fotos_procesadas = [];
        if (!empty($fotos)) {
            foreach ($fotos as $foto) {
                $resultado_foto = subirFotoAvance($foto);
                if ($resultado_foto['success']) {
                    $fotos_procesadas[] = $resultado_foto;
                }
            }
        }

        // Convertir array de fotos a JSON
        $fotos_json = !empty($fotos_procesadas) ? json_encode($fotos_procesadas) : null;
        
        // Log para debugging
        if (!empty($fotos)) {
            error_log("Fotos recibidas: " . count($fotos));
            error_log("Fotos procesadas: " . count($fotos_procesadas));
            if ($fotos_json) {
                error_log("JSON de fotos: " . substr($fotos_json, 0, 200));
            }
        }

        // Verificar si la columna Fotos existe en la tabla, si no existe, crearla
        $columna_fotos_existe = false;
        $checkColumna = "SHOW COLUMNS FROM avances_mecanico LIKE 'Fotos'";
        $resultColumna = mysqli_query($conn, $checkColumna);
        if ($resultColumna && mysqli_num_rows($resultColumna) > 0) {
            $columna_fotos_existe = true;
        } else {
            // Crear la columna Fotos si no existe
            $crearColumna = "ALTER TABLE avances_mecanico ADD COLUMN Fotos TEXT NULL COMMENT 'JSON con las rutas de las fotos del avance'";
            if (mysqli_query($conn, $crearColumna)) {
                $columna_fotos_existe = true;
            } else {
                error_log("Error al crear columna Fotos: " . mysqli_error($conn));
            }
        }

        // 1. Insertar el avance (con o sin fotos según si la columna existe)
        if ($columna_fotos_existe) {
            if ($fotos_json) {
                $fotos_json_escaped = mysqli_real_escape_string($conn, $fotos_json);
                $queryAvance = "INSERT INTO avances_mecanico (AsignacionID, Descripcion, Estado, Fotos) 
                               VALUES ('$asignacion_id', '$descripcion', '$estado', '$fotos_json_escaped')";
            } else {
                $queryAvance = "INSERT INTO avances_mecanico (AsignacionID, Descripcion, Estado, Fotos) 
                               VALUES ('$asignacion_id', '$descripcion', '$estado', NULL)";
            }
        } else {
            $queryAvance = "INSERT INTO avances_mecanico (AsignacionID, Descripcion, Estado) 
                           VALUES ('$asignacion_id', '$descripcion', '$estado')";
        }
        $resultAvance = mysqli_query($conn, $queryAvance);

        if (!$resultAvance) {
            throw new Exception('Error al insertar avance: ' . mysqli_error($conn));
        }

        // 2. Actualizar el estado de la asignación
        $queryAsignacion = "UPDATE asignaciones_mecanico SET Estado = '$estado' WHERE ID = '$asignacion_id'";
        $resultAsignacion = mysqli_query($conn, $queryAsignacion);

        if (!$resultAsignacion) {
            throw new Exception('Error al actualizar asignación: ' . mysqli_error($conn));
        }

        // 3. Si el estado es "Completado", actualizar el estado del vehículo y notificar al chofer
        if ($estado === 'Completado') {
            $queryGetVehiculo = "SELECT VehiculoID FROM asignaciones_mecanico WHERE ID = '$asignacion_id'";
            $resultVehiculo = mysqli_query($conn, $queryGetVehiculo);
            
            if ($resultVehiculo && $row = mysqli_fetch_assoc($resultVehiculo)) {
                $vehiculo_id = $row['VehiculoID'];
                $queryUpdateVehiculo = "UPDATE ingreso_vehiculos SET Estado = 'Completado' WHERE ID = '$vehiculo_id'";
                $resultUpdateVehiculo = mysqli_query($conn, $queryUpdateVehiculo);
                
                if (!$resultUpdateVehiculo) {
                    throw new Exception('Error al actualizar estado del vehículo: ' . mysqli_error($conn));
                }
                
                // Obtener información del vehículo para la notificación
                $queryInfoVehiculo = "SELECT Placa, ConductorNombre FROM ingreso_vehiculos WHERE ID = '$vehiculo_id'";
                $resultInfoVehiculo = mysqli_query($conn, $queryInfoVehiculo);
                $infoVehiculo = null;
                if ($resultInfoVehiculo && $infoRow = mysqli_fetch_assoc($resultInfoVehiculo)) {
                    $infoVehiculo = $infoRow;
                }
                
                // Obtener el ID del chofer y notificar
                $chofer_id = obtenerChoferIDDelVehiculo($vehiculo_id, $conn);
                if ($chofer_id && $infoVehiculo) {
                    $placa = $infoVehiculo['Placa'];
                    $conductorNombre = $infoVehiculo['ConductorNombre'] ?? 'el chofer';
                    $mensaje = "El vehículo con placa <strong>$placa</strong> ha sido completado por el mecánico.\n\n";
                    $mensaje .= "Por favor, acuda al taller para retirar el vehículo.\n\n";
                    $mensaje .= "El guardia procesará la salida cuando llegue.";
                    $titulo = "Vehículo Listo para Retiro - Placa: $placa";
                    $modulo = "control_ingreso";
                    $enlace = "control_ingreso.php";
                    
                    crearNotificacion([$chofer_id], $titulo, $mensaje, $modulo, $enlace);
                }
            }
            mysqli_free_result($resultVehiculo);
        }

        mysqli_commit($conn);
        return ['status' => 'success', 'message' => 'Avance registrado correctamente'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
        mysqli_close($conn);
    }
}

function subirFotoAvance($archivo) {
    // Validar que el archivo tenga la estructura correcta
    if (!isset($archivo['name']) || !isset($archivo['tmp_name']) || !isset($archivo['size'])) {
        return [
            'success' => false,
            'message' => 'Estructura de archivo inválida'
        ];
    }
    
    // __DIR__ apunta a: app/model/tareas/functions/
    // Necesitamos ir 4 niveles arriba para llegar a la raíz del proyecto:
    // app/model/tareas/functions/ -> ../ -> app/model/tareas/ -> ../ -> app/model/ -> ../ -> app/ -> ../ -> raíz
    $directorio_base = __DIR__ . '/../../../../uploads/avances/';
    
    // Normalizar la ruta (convertir / a \ en Windows si es necesario)
    $directorio_base = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $directorio_base);
    
    // Crear directorio si no existe
    if (!file_exists($directorio_base)) {
        if (!mkdir($directorio_base, 0755, true)) {
            error_log("Error: No se pudo crear el directorio: " . $directorio_base);
            return [
                'success' => false,
                'message' => 'Error al crear el directorio de avances'
            ];
        }
    }
    
    // Validar tipo de archivo
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extension, $extensiones_permitidas)) {
        return [
            'success' => false,
            'message' => 'Tipo de archivo no permitido'
        ];
    }
    
    // Validar tamaño (5MB máximo)
    $tamanio_maximo = 5 * 1024 * 1024;
    if ($archivo['size'] > $tamanio_maximo) {
        return [
            'success' => false,
            'message' => 'El archivo es demasiado grande. Máximo: 5MB'
        ];
    }
    
    // Generar nombre único
    $nombre_guardado = uniqid() . '_' . time() . '.' . $extension;
    $ruta_completa = $directorio_base . $nombre_guardado;
    
    // Log para debugging
    error_log("=== Subir Foto Avance ===");
    error_log("Directorio base: " . $directorio_base);
    error_log("Ruta completa: " . $ruta_completa);
    error_log("Archivo temporal: " . $archivo['tmp_name']);
    error_log("Directorio existe: " . (file_exists($directorio_base) ? 'Sí' : 'No'));
    error_log("Directorio escribible: " . (is_writable($directorio_base) ? 'Sí' : 'No'));
    error_log("Archivo temporal existe: " . (file_exists($archivo['tmp_name']) ? 'Sí' : 'No'));
    
    if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        // Verificar que el archivo se guardó correctamente
        if (!file_exists($ruta_completa)) {
            error_log("Error: El archivo no existe después de move_uploaded_file");
            return [
                'success' => false,
                'message' => 'El archivo no se guardó correctamente'
            ];
        }
        
        // Guardar la ruta relativa desde la raíz del proyecto para acceso web
        $ruta_web = 'uploads/avances/' . $nombre_guardado;
        
        error_log("Archivo guardado exitosamente: " . $ruta_web);
        error_log("Tamaño del archivo guardado: " . filesize($ruta_completa) . " bytes");
        
        return [
            'success' => true,
            'ruta' => $ruta_web,
            'nombre_guardado' => $nombre_guardado,
            'nombre_original' => $archivo['name'],
            'tipo' => 'foto_avance',
            'extension' => $extension
        ];
    }
    
    $error_msg = 'Error al mover el archivo subido';
    if (isset($archivo['error'])) {
        $error_msg .= ' (Código de error: ' . $archivo['error'] . ')';
    }
    
    error_log("Error al mover archivo: " . $error_msg);
    error_log("Último error PHP: " . error_get_last()['message'] ?? 'N/A');
    
    return [
        'success' => false,
        'message' => $error_msg
    ];
}

?>