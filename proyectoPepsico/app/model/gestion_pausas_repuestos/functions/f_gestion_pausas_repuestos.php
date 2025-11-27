<?php
// Prevenir múltiples inclusiones
if (!defined('F_GESTION_PAUSAS_REPUESTOS_INCLUIDO')) {
    define('F_GESTION_PAUSAS_REPUESTOS_INCLUIDO', true);
    
    // Rutas relativas desde este archivo
    // Desde: app/model/gestion_pausas_repuestos/functions/
    // Hacia: app/config/conexion.php (subir 3 niveles)
    // Hacia: pages/general/funciones_notificaciones.php (subir 4 niveles hasta la raíz)
    $conexion_file = __DIR__ . '/../../../config/conexion.php';
    $notificaciones_file = __DIR__ . '/../../../../pages/general/funciones_notificaciones.php';
    
    if (!file_exists($conexion_file)) {
        error_log("Error: No se encontró el archivo de conexión: $conexion_file");
        throw new Exception("Archivo de conexión no encontrado");
    }
    
    if (!file_exists($notificaciones_file)) {
        error_log("Error: No se encontró el archivo de notificaciones: $notificaciones_file");
        throw new Exception("Archivo de notificaciones no encontrado");
    }
    
    require_once $conexion_file;
    require_once $notificaciones_file;
}

/**
 * Verifica y modifica la tabla solicitudes_repuestos para permitir NULL en AsignacionID
 */
function verificarYModificarTablaSolicitudesRepuestos($conn) {
    // Verificar si la columna AsignacionID permite NULL
    $checkColumn = "SELECT IS_NULLABLE 
                   FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'solicitudes_repuestos' 
                   AND COLUMN_NAME = 'AsignacionID'";
    
    $result = mysqli_query($conn, $checkColumn);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if ($row['IS_NULLABLE'] === 'NO') {
            // Modificar la columna para permitir NULL
            // Primero eliminar la foreign key si existe
            $dropFK = "ALTER TABLE solicitudes_repuestos DROP FOREIGN KEY fk_solicitudes_repuestos_asignacion";
            @mysqli_query($conn, $dropFK);
            
            // Modificar la columna para permitir NULL
            $alterColumn = "ALTER TABLE solicitudes_repuestos MODIFY COLUMN AsignacionID INT(11) NULL COMMENT 'ID de la asignación de mecánico que requiere el repuesto (opcional)'";
            mysqli_query($conn, $alterColumn);
            
            // Recrear la foreign key con ON DELETE SET NULL
            $addFK = "ALTER TABLE solicitudes_repuestos 
                     ADD CONSTRAINT fk_solicitudes_repuestos_asignacion 
                     FOREIGN KEY (AsignacionID) 
                     REFERENCES asignaciones_mecanico(ID) 
                     ON DELETE SET NULL 
                     ON UPDATE CASCADE";
            @mysqli_query($conn, $addFK);
        }
        mysqli_free_result($result);
    }
}

/**
 * Obtiene las tareas en pausa de un mecánico
 */
function obtenerTareasEnPausa($mecanico_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    // Verificar si existe la columna MotivoPausa
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

    $mecanico_id = mysqli_real_escape_string($conn, $mecanico_id);
    
    $motivoPausaField = $columnExists ? "a.MotivoPausa," : "NULL as MotivoPausa,";
    
    // Construir la condición WHERE para tareas en pausa
    if ($columnExists) {
        $wherePausa = "(a.Estado = 'En Pausa' OR (a.Estado IS NULL OR a.Estado = '') AND a.MotivoPausa IS NOT NULL AND a.MotivoPausa != '')";
    } else {
        $wherePausa = "a.Estado = 'En Pausa'";
    }

    $query = "SELECT 
                a.ID as AsignacionID,
                a.VehiculoID,
                a.MecanicoID,
                a.Observaciones,
                COALESCE(NULLIF(a.Estado, ''), CASE WHEN a.MotivoPausa IS NOT NULL AND a.MotivoPausa != '' THEN 'En Pausa' ELSE 'Asignado' END) AS Estado,
                $motivoPausaField
                v.Placa,
                v.Marca,
                v.Modelo,
                v.TipoVehiculo,
                DATE_FORMAT(a.FechaAsignacion, '%d/%m/%Y %H:%i') as FechaAsignacion
            FROM asignaciones_mecanico a
            INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
            WHERE a.MecanicoID = '$mecanico_id' 
            AND $wherePausa
            ORDER BY a.FechaAsignacion DESC";

    $result = mysqli_query($conn, $query);
    $tareas = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tareas[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return $tareas;
}

/**
 * Verifica si ya existe una solicitud pendiente o aprobada para el mismo repuesto
 * Mejorado: Verifica todas las solicitudes del mismo mecánico y repuesto, sin importar la asignación
 */
function verificarSolicitudDuplicada($mecanico_id, $repuesto_id, $asignacion_id = null) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return null; // En caso de error, permitir continuar
    }

    try {
        $mecanico_id = intval($mecanico_id);
        $repuesto_id = intval($repuesto_id);
        
        // Verificar si existe CUALQUIER solicitud pendiente o aprobada para el mismo repuesto del mismo mecánico
        // Sin importar si tiene asignación o no, para evitar duplicados
        // IMPORTANTE: No permitir duplicados del mismo repuesto, independientemente de la asignación
        $query = "SELECT ID, Estado, FechaSolicitud, Cantidad, Urgencia, AsignacionID, Motivo
                  FROM solicitudes_repuestos 
                  WHERE MecanicoID = $mecanico_id 
                  AND RepuestoID = $repuesto_id 
                  AND Estado IN ('Pendiente', 'Aprobada')
                  ORDER BY FechaSolicitud DESC
                  LIMIT 1";
        
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $solicitud = mysqli_fetch_assoc($result);
            mysqli_close($conn);
            return $solicitud;
        }
        
        mysqli_close($conn);
        return null;
    } catch (Exception $e) {
        error_log("Error al verificar solicitud duplicada: " . $e->getMessage());
        if ($conn) {
            mysqli_close($conn);
        }
        return null; // En caso de error, permitir continuar
    }
}

/**
 * Crea una solicitud de repuestos
 */
function crearSolicitudRepuestos($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [
            'status' => 'error',
            'message' => 'Error de conexión'
        ];
    }

    try {
        mysqli_autocommit($conn, false);

        // Verificar y modificar la tabla si es necesario para permitir NULL en AsignacionID
        verificarYModificarTablaSolicitudesRepuestos($conn);

        $asignacion_id = !empty($datos['asignacion_id']) ? intval($datos['asignacion_id']) : null;
        $mecanico_id = intval($datos['mecanico_id']);
        $repuesto_id = intval($datos['repuesto_id']);
        $cantidad = intval($datos['cantidad']);
        $urgencia = mysqli_real_escape_string($conn, $datos['urgencia']);
        $motivo = mysqli_real_escape_string($conn, $datos['motivo'] ?? '');

        // Validar asignación solo si se proporciona (opcional)
        if ($asignacion_id !== null && $asignacion_id !== 0 && $asignacion_id !== '') {
            $asignacion_id = intval($asignacion_id);
            $queryAsignacion = "SELECT ID FROM asignaciones_mecanico WHERE ID = $asignacion_id AND MecanicoID = $mecanico_id";
            $resultAsignacion = mysqli_query($conn, $queryAsignacion);
            if (!$resultAsignacion || mysqli_num_rows($resultAsignacion) == 0) {
                mysqli_rollback($conn);
                mysqli_close($conn);
                return [
                    'status' => 'error',
                    'message' => 'La asignación no existe o no pertenece al mecánico'
                ];
            }
        } else {
            // Si no hay asignación, establecer como NULL
            $asignacion_id = null;
        }

        // Verificar si ya existe una solicitud pendiente o aprobada para el mismo repuesto
        // (sin importar si tiene asignación o no, para evitar duplicados)
        // IMPORTANTE: Verificar DENTRO de la transacción con FOR UPDATE para evitar condiciones de carrera
        $queryVerificar = "SELECT ID, Estado, FechaSolicitud, Cantidad, Urgencia, AsignacionID, Motivo
                          FROM solicitudes_repuestos 
                          WHERE MecanicoID = $mecanico_id 
                          AND RepuestoID = $repuesto_id 
                          AND Estado IN ('Pendiente', 'Aprobada')
                          ORDER BY FechaSolicitud DESC
                          LIMIT 1
                          FOR UPDATE"; // Bloqueo de fila para evitar condiciones de carrera
        
        $resultVerificar = mysqli_query($conn, $queryVerificar);
        $solicitudExistente = null;
        if ($resultVerificar && mysqli_num_rows($resultVerificar) > 0) {
            $solicitudExistente = mysqli_fetch_assoc($resultVerificar);
        }
        
        if ($solicitudExistente) {
            // Obtener información del repuesto para el mensaje
            $queryRepuesto = "SELECT Nombre, Codigo FROM repuestos WHERE ID = $repuesto_id";
            $resultRepuesto = mysqli_query($conn, $queryRepuesto);
            $repuesto = mysqli_fetch_assoc($resultRepuesto);
            $repuestoNombre = $repuesto['Nombre'] ?? 'Repuesto';
            $repuestoCodigo = $repuesto['Codigo'] ?? '';
            
            // Obtener información de la asignación si existe
            $placa = null;
            $tieneAsignacion = !empty($solicitudExistente['AsignacionID']);
            if ($tieneAsignacion) {
                $asignacionExistenteId = intval($solicitudExistente['AsignacionID']);
                $queryPlaca = "SELECT v.Placa 
                             FROM asignaciones_mecanico a
                             INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                             WHERE a.ID = $asignacionExistenteId";
                $resultPlaca = mysqli_query($conn, $queryPlaca);
                if ($resultPlaca && mysqli_num_rows($resultPlaca) > 0) {
                    $placaData = mysqli_fetch_assoc($resultPlaca);
                    $placa = $placaData['Placa'];
                }
            }
            
            $fechaSolicitud = date('d/m/Y H:i', strtotime($solicitudExistente['FechaSolicitud']));
            $estadoTexto = $solicitudExistente['Estado'] === 'Pendiente' ? 'pendiente de aprobación' : 'aprobada y esperando entrega';
            $mensajeAsignacion = $tieneAsignacion && $placa 
                ? " para el vehículo $placa" 
                : " (sin asignación de vehículo)";
            
            // Hacer rollback antes de retornar
            mysqli_rollback($conn);
            mysqli_close($conn);
            
            return [
                'status' => 'duplicado',
                'message' => "Ya existe una solicitud $estadoTexto para este repuesto$mensajeAsignacion. Por favor, espere la respuesta de esa solicitud antes de crear una nueva.",
                'solicitud_existente' => [
                    'id' => $solicitudExistente['ID'],
                    'estado' => $solicitudExistente['Estado'],
                    'fecha' => $fechaSolicitud,
                    'cantidad' => $solicitudExistente['Cantidad'],
                    'urgencia' => $solicitudExistente['Urgencia'],
                    'repuesto_nombre' => $repuestoNombre,
                    'repuesto_codigo' => $repuestoCodigo,
                    'tiene_asignacion' => $tieneAsignacion,
                    'placa' => $placa
                ]
            ];
        }

        // Insertar solicitud (AsignacionID es opcional)
        $asignacionValue = $asignacion_id !== null ? $asignacion_id : 'NULL';
        $query = "INSERT INTO solicitudes_repuestos 
                  (AsignacionID, MecanicoID, RepuestoID, Cantidad, Urgencia, Motivo, Estado) 
                  VALUES ($asignacionValue, $mecanico_id, $repuesto_id, $cantidad, '$urgencia', '$motivo', 'Pendiente')";

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al crear solicitud: " . mysqli_error($conn));
        }

        $solicitud_id = mysqli_insert_id($conn);

        // Obtener información del repuesto para la notificación
        $queryRepuesto = "SELECT Nombre FROM repuestos WHERE ID = $repuesto_id";
        $resultRepuesto = mysqli_query($conn, $queryRepuesto);
        $repuesto = mysqli_fetch_assoc($resultRepuesto);
        $repuestoNombre = $repuesto['Nombre'] ?? 'Repuesto';

        // Obtener información del vehículo solo si hay asignación
        $placa = null;
        if ($asignacion_id !== null) {
            $queryInfo = "SELECT v.Placa 
                         FROM asignaciones_mecanico a
                         INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                         WHERE a.ID = $asignacion_id";
            $resultInfo = mysqli_query($conn, $queryInfo);
            if ($resultInfo && mysqli_num_rows($resultInfo) > 0) {
                $info = mysqli_fetch_assoc($resultInfo);
                $placa = $info['Placa'];
            }
        }

        // Obtener usuarios con rol "Jefe de Taller" o "Administrador"
        $queryUsuarios = "SELECT UsuarioID FROM usuarios WHERE Rol IN ('Jefe de Taller', 'Administrador')";
        $resultUsuarios = mysqli_query($conn, $queryUsuarios);
        $usuarios = [];
        while ($row = mysqli_fetch_assoc($resultUsuarios)) {
            $usuarios[] = $row['UsuarioID'];
        }

        if (!empty($usuarios)) {
            $cantidad = $datos['cantidad'];
            $urgencia = $datos['urgencia'];
            
            // Construir mensaje según si hay asignación o no
            if ($placa) {
                $mensaje = "El mecánico ha solicitado $cantidad unidad(es) de $repuestoNombre para el vehículo con placa $placa. Urgencia: $urgencia";
            } else {
                $mensaje = "El mecánico ha solicitado $cantidad unidad(es) de $repuestoNombre. Urgencia: $urgencia";
            }
            
            if (!empty($motivo)) {
                $mensaje .= ". Motivo: $motivo";
            }
            
            $titulo = "Nueva Solicitud de Repuestos";
            $modulo = "gestion_solicitudes_repuestos";
            $enlace = "gestion_solicitudes_repuestos.php";
            
            crearNotificacion($usuarios, $titulo, $mensaje, $modulo, $enlace);
        }

        mysqli_commit($conn);

        return [
            'status' => 'success',
            'message' => 'Solicitud creada correctamente',
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
 * Obtiene las solicitudes de repuestos de un mecánico
 */
function obtenerSolicitudesRepuestos($mecanico_id, $asignacion_id = null) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    $mecanico_id = mysqli_real_escape_string($conn, $mecanico_id);
    $where = "sr.MecanicoID = '$mecanico_id'";
    
    if ($asignacion_id) {
        $asignacion_id = intval($asignacion_id);
        $where .= " AND sr.AsignacionID = $asignacion_id";
    }

    $query = "SELECT 
                sr.ID,
                sr.AsignacionID,
                sr.RepuestoID,
                sr.Cantidad,
                sr.Urgencia,
                sr.Motivo,
                sr.Estado,
                DATE_FORMAT(sr.FechaSolicitud, '%d/%m/%Y %H:%i') as FechaSolicitud,
                DATE_FORMAT(sr.FechaAprobacion, '%d/%m/%Y %H:%i') as FechaAprobacion,
                DATE_FORMAT(sr.FechaEnProceso, '%d/%m/%Y %H:%i') as FechaEnProceso,
                DATE_FORMAT(sr.FechaEnTransito, '%d/%m/%Y %H:%i') as FechaEnTransito,
                DATE_FORMAT(sr.FechaRecibido, '%d/%m/%Y %H:%i') as FechaRecibido,
                DATE_FORMAT(sr.FechaEntrega, '%d/%m/%Y %H:%i') as FechaEntrega,
                r.Codigo as RepuestoCodigo,
                r.Nombre as RepuestoNombre,
                r.Categoria as RepuestoCategoria,
                v.Placa
            FROM solicitudes_repuestos sr
            INNER JOIN repuestos r ON sr.RepuestoID = r.ID
            LEFT JOIN asignaciones_mecanico a ON sr.AsignacionID = a.ID
            LEFT JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
            WHERE $where
            ORDER BY sr.FechaSolicitud DESC";

    $result = mysqli_query($conn, $query);
    $solicitudes = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $solicitudes[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return $solicitudes;
}

/**
 * Pausa una tarea asignada
 */
function pausarTarea($asignacion_id, $mecanico_id, $motivo_pausa) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [
            'status' => 'error',
            'message' => 'Error de conexión'
        ];
    }

    try {
        mysqli_autocommit($conn, false);

        // Verificar que la asignación pertenece al mecánico
        $asignacion_id = intval($asignacion_id);
        $mecanico_id = intval($mecanico_id);
        $motivo_pausa = mysqli_real_escape_string($conn, trim($motivo_pausa));
        
        if (empty($motivo_pausa)) {
            return [
                'status' => 'error',
                'message' => 'El motivo de pausa es requerido'
            ];
        }
        
        $queryVerificar = "SELECT ID, Estado FROM asignaciones_mecanico WHERE ID = $asignacion_id AND MecanicoID = $mecanico_id";
        $resultVerificar = mysqli_query($conn, $queryVerificar);
        
        if (!$resultVerificar || mysqli_num_rows($resultVerificar) == 0) {
            return [
                'status' => 'error',
                'message' => 'La asignación no existe o no pertenece al mecánico'
            ];
        }

        $asignacion = mysqli_fetch_assoc($resultVerificar);
        
        // Verificar si existe la columna MotivoPausa
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
        
        // Si la columna no existe, crearla
        if (!$columnExists) {
            $alterTable = "ALTER TABLE asignaciones_mecanico ADD COLUMN MotivoPausa TEXT NULL COMMENT 'Motivo por el cual la tarea fue pausada'";
            if (!mysqli_query($conn, $alterTable)) {
                throw new Exception("Error al crear columna MotivoPausa: " . mysqli_error($conn));
            }
        }
        
        // Actualizar estado y motivo de pausa
        $query = "UPDATE asignaciones_mecanico 
                  SET Estado = 'En Pausa', MotivoPausa = '$motivo_pausa' 
                  WHERE ID = $asignacion_id";

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al pausar tarea: " . mysqli_error($conn));
        }

        mysqli_commit($conn);

        return [
            'status' => 'success',
            'message' => 'Tarea pausada correctamente'
        ];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error en pausarTarea: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    } finally {
        mysqli_close($conn);
    }
}

/**
 * Reanuda una tarea en pausa
 */
function reanudarTarea($asignacion_id, $mecanico_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [
            'status' => 'error',
            'message' => 'Error de conexión'
        ];
    }

    try {
        mysqli_autocommit($conn, false);

        // Verificar que la asignación pertenece al mecánico
        $asignacion_id = intval($asignacion_id);
        $mecanico_id = intval($mecanico_id);
        
        $queryVerificar = "SELECT ID, Estado, MotivoPausa FROM asignaciones_mecanico WHERE ID = $asignacion_id AND MecanicoID = $mecanico_id";
        $resultVerificar = mysqli_query($conn, $queryVerificar);
        
        if (!$resultVerificar || mysqli_num_rows($resultVerificar) == 0) {
            return [
                'status' => 'error',
                'message' => 'La asignación no existe o no pertenece al mecánico'
            ];
        }

        $asignacion = mysqli_fetch_assoc($resultVerificar);
        
        // Actualizar estado y limpiar motivo de pausa
        $query = "UPDATE asignaciones_mecanico 
                  SET Estado = 'En proceso', MotivoPausa = NULL 
                  WHERE ID = $asignacion_id";

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al reanudar tarea: " . mysqli_error($conn));
        }

        mysqli_commit($conn);

        return [
            'status' => 'success',
            'message' => 'Tarea reanudada correctamente'
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
 * Obtiene los detalles de una solicitud de repuestos
 */
function obtenerDetallesSolicitud($solicitud_id, $mecanico_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return null;
    }

    $solicitud_id = intval($solicitud_id);
    $mecanico_id = intval($mecanico_id);

    $query = "SELECT 
                sr.ID,
                sr.AsignacionID,
                sr.RepuestoID,
                sr.Cantidad,
                sr.Urgencia,
                sr.Motivo,
                sr.Estado,
                sr.Observaciones,
                DATE_FORMAT(sr.FechaSolicitud, '%d/%m/%Y %H:%i') as FechaSolicitud,
                DATE_FORMAT(sr.FechaAprobacion, '%d/%m/%Y %H:%i') as FechaAprobacion,
                DATE_FORMAT(sr.FechaEntrega, '%d/%m/%Y %H:%i') as FechaEntrega,
                r.Codigo as RepuestoCodigo,
                r.Nombre as RepuestoNombre,
                r.Categoria as RepuestoCategoria,
                v.Placa,
                v.Marca,
                v.Modelo
            FROM solicitudes_repuestos sr
            INNER JOIN repuestos r ON sr.RepuestoID = r.ID
            LEFT JOIN asignaciones_mecanico a ON sr.AsignacionID = a.ID
            LEFT JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
            WHERE sr.ID = $solicitud_id AND sr.MecanicoID = $mecanico_id";

    $result = mysqli_query($conn, $query);
    $solicitud = null;

    if ($result && mysqli_num_rows($result) > 0) {
        $solicitud = mysqli_fetch_assoc($result);
    }

    mysqli_close($conn);
    return $solicitud;
}

/**
 * Obtiene repuestos disponibles (para solicitar)
 */
function obtenerRepuestosDisponibles($soloSinStock = false, $mecanico_id = null, $asignacion_id = null) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    $where = "r.Estado = 'Activo'";
    if ($soloSinStock) {
        $where .= " AND r.Stock = 0";
    }

    // Si se proporciona el mecánico, excluir repuestos que ya tienen solicitudes pendientes o aprobadas
    // Mejorado: Excluir TODAS las solicitudes del mismo mecánico y repuesto, sin importar la asignación
    $exclusionSolicitudes = "";
    if ($mecanico_id !== null && $mecanico_id > 0) {
        $mecanico_id = intval($mecanico_id);
        
        // Excluir cualquier repuesto que ya tenga una solicitud pendiente o aprobada del mismo mecánico
        // Sin importar si tiene asignación o no, para evitar duplicados
        $exclusionSolicitudes = " AND r.ID NOT IN (
            SELECT DISTINCT sr.RepuestoID 
            FROM solicitudes_repuestos sr 
            WHERE sr.MecanicoID = $mecanico_id 
            AND sr.Estado IN ('Pendiente', 'Aprobada')
        )";
    }

    $query = "SELECT 
                r.ID,
                r.Codigo,
                r.Nombre,
                r.Categoria,
                r.Stock,
                r.StockMinimo,
                r.Descripcion
            FROM repuestos r
            WHERE $where
            $exclusionSolicitudes
            ORDER BY r.Nombre ASC";

    $result = mysqli_query($conn, $query);
    $repuestos = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $repuestos[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return $repuestos;
}

/**
 * Aprobar una solicitud de repuestos (solo para Jefe de Taller/Administrador)
 */
function aprobarSolicitudRepuestos($solicitud_id, $aprobador_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [
            'status' => 'error',
            'message' => 'Error de conexión'
        ];
    }

    try {
        mysqli_autocommit($conn, false);

        $solicitud_id = intval($solicitud_id);
        $aprobador_id = intval($aprobador_id);

        // Obtener información de la solicitud
        $queryInfo = "SELECT sr.MecanicoID, sr.AsignacionID, sr.RepuestoID, sr.Cantidad, 
                     r.Nombre as RepuestoNombre, v.Placa
                     FROM solicitudes_repuestos sr
                     INNER JOIN repuestos r ON sr.RepuestoID = r.ID
                     LEFT JOIN asignaciones_mecanico a ON sr.AsignacionID = a.ID
                     LEFT JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                     WHERE sr.ID = $solicitud_id AND sr.Estado = 'Pendiente'";
        
        $resultInfo = mysqli_query($conn, $queryInfo);
        if (!$resultInfo || mysqli_num_rows($resultInfo) == 0) {
            return [
                'status' => 'error',
                'message' => 'Solicitud no encontrada o ya procesada'
            ];
        }

        $info = mysqli_fetch_assoc($resultInfo);

        // Actualizar solicitud
        $query = "UPDATE solicitudes_repuestos 
                  SET Estado = 'Aprobada', 
                      AprobadoPor = $aprobador_id,
                      FechaAprobacion = NOW()
                  WHERE ID = $solicitud_id";

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al aprobar solicitud: " . mysqli_error($conn));
        }

        // Notificar al mecánico
        $mensaje = "Su solicitud de {$info['Cantidad']} unidad(es) de {$info['RepuestoNombre']} para el vehículo con placa {$info['Placa']} ha sido aprobada. Los repuestos están disponibles.";
        $titulo = "Solicitud de Repuestos Aprobada";
        $modulo = "estado_solicitudes_repuestos";
        $enlace = "estado_solicitudes_repuestos.php";
        
        crearNotificacion([$info['MecanicoID']], $titulo, $mensaje, $modulo, $enlace);

        mysqli_commit($conn);

        return [
            'status' => 'success',
            'message' => 'Solicitud aprobada correctamente'
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
 * Marcar solicitud como entregada (solo para Jefe de Taller/Administrador)
 */
function entregarSolicitudRepuestos($solicitud_id, $entregador_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [
            'status' => 'error',
            'message' => 'Error de conexión'
        ];
    }

    try {
        mysqli_autocommit($conn, false);

        $solicitud_id = intval($solicitud_id);
        $entregador_id = intval($entregador_id);

        // Obtener información de la solicitud
        $queryInfo = "SELECT sr.MecanicoID, sr.AsignacionID, sr.RepuestoID, sr.Cantidad, 
                     r.Nombre as RepuestoNombre, v.Placa, a.Estado as EstadoTarea
                     FROM solicitudes_repuestos sr
                     INNER JOIN repuestos r ON sr.RepuestoID = r.ID
                     LEFT JOIN asignaciones_mecanico a ON sr.AsignacionID = a.ID
                     LEFT JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                     WHERE sr.ID = $solicitud_id AND sr.Estado = 'Aprobada'";
        
        $resultInfo = mysqli_query($conn, $queryInfo);
        if (!$resultInfo || mysqli_num_rows($resultInfo) == 0) {
            return [
                'status' => 'error',
                'message' => 'Solicitud no encontrada o no está aprobada'
            ];
        }

        $info = mysqli_fetch_assoc($resultInfo);

        // Actualizar solicitud
        $query = "UPDATE solicitudes_repuestos 
                  SET Estado = 'Entregada', 
                      FechaEntrega = NOW()
                  WHERE ID = $solicitud_id";

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al marcar como entregada: " . mysqli_error($conn));
        }

        // Notificar al mecánico
        $mensaje = "Los repuestos solicitados ({$info['Cantidad']} unidad(es) de {$info['RepuestoNombre']}) para el vehículo con placa {$info['Placa']} han sido entregados. Puede continuar con la tarea.";
        $titulo = "Repuestos Entregados";
        $modulo = "estado_solicitudes_repuestos";
        $enlace = "estado_solicitudes_repuestos.php";
        
        crearNotificacion([$info['MecanicoID']], $titulo, $mensaje, $modulo, $enlace);

        mysqli_commit($conn);

        return [
            'status' => 'success',
            'message' => 'Solicitud marcada como entregada. El mecánico ha sido notificado.'
        ];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
        mysqli_close($conn);
    }
}

/**
 * Obtiene todos los repuestos para el inventario
 */
function obtenerTodosRepuestos() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    $query = "SELECT 
                ID,
                Codigo,
                Nombre,
                Categoria,
                Stock,
                StockMinimo,
                Precio,
                Estado,
                Descripcion
            FROM repuestos
            ORDER BY Nombre ASC";

    $result = mysqli_query($conn, $query);
    $repuestos = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $repuestos[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return $repuestos;
}

/**
 * Obtiene repuestos con stock bajo (Stock <= StockMinimo)
 */
function obtenerRepuestosStockBajo() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    $query = "SELECT 
                ID,
                Codigo,
                Nombre,
                Categoria,
                Stock,
                StockMinimo,
                Precio,
                Estado,
                Descripcion
            FROM repuestos
            WHERE Estado = 'Activo' AND Stock <= StockMinimo
            ORDER BY Stock ASC, Nombre ASC";

    $result = mysqli_query($conn, $query);
    $repuestos = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $repuestos[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return $repuestos;
}

/**
 * Obtiene los movimientos de stock de un repuesto
 */
function obtenerMovimientosStock($repuesto_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    $repuesto_id = intval($repuesto_id);
    $movimientos = [];

    // Verificar si existe la tabla repuestos_asignacion
    $checkTable = "SELECT COUNT(*) as existe 
                  FROM information_schema.TABLES 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'repuestos_asignacion'";
    $resultTable = mysqli_query($conn, $checkTable);
    $tablaAsignacionExiste = false;
    if ($resultTable) {
        $row = mysqli_fetch_assoc($resultTable);
        $tablaAsignacionExiste = ($row['existe'] > 0);
    }

    // Obtener stock actual del repuesto
    $queryStock = "SELECT Stock FROM repuestos WHERE ID = $repuesto_id";
    $resultStock = mysqli_query($conn, $queryStock);
    $stockActual = 0;
    if ($resultStock && mysqli_num_rows($resultStock) > 0) {
        $rowStock = mysqli_fetch_assoc($resultStock);
        $stockActual = intval($rowStock['Stock']);
    }

    // Obtener salidas desde repuestos_asignacion (si existe)
    if ($tablaAsignacionExiste) {
        $querySalidas = "SELECT 
                            ra.ID,
                            ra.Cantidad,
                            ra.FechaRegistro as Fecha,
                            ra.Observaciones,
                            a.ID as AsignacionID,
                            v.Placa,
                            u.NombreUsuario as Usuario
                        FROM repuestos_asignacion ra
                        INNER JOIN asignaciones_mecanico a ON ra.AsignacionID = a.ID
                        INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                        LEFT JOIN usuarios u ON a.MecanicoID = u.UsuarioID
                        WHERE ra.RepuestoID = $repuesto_id
                        ORDER BY ra.FechaRegistro ASC";
        
        $resultSalidas = mysqli_query($conn, $querySalidas);
        $salidas = [];
        if ($resultSalidas) {
            while ($row = mysqli_fetch_assoc($resultSalidas)) {
                $salidas[] = [
                    'ID' => $row['ID'],
                    'Fecha' => $row['Fecha'],
                    'Cantidad' => intval($row['Cantidad']),
                    'Observaciones' => $row['Observaciones'],
                    'Placa' => $row['Placa'],
                    'Usuario' => $row['Usuario'] ?? 'Sistema'
                ];
            }
            mysqli_free_result($resultSalidas);
        }
        
        // Calcular stock anterior y nuevo para cada salida (orden cronológico inverso)
        // Empezamos desde el stock actual y vamos sumando las salidas hacia atrás
        $stockAcumulado = $stockActual;
        $movimientosTemporales = [];
        
        foreach (array_reverse($salidas) as $salida) {
            $stockNuevo = $stockAcumulado;
            $stockAcumulado += $salida['Cantidad']; // Sumamos la salida para obtener el stock anterior
            $stockAnterior = $stockAcumulado;
            
            $movimientosTemporales[] = [
                'ID' => $salida['ID'],
                'Fecha' => date('d/m/Y H:i', strtotime($salida['Fecha'])),
                'Tipo' => 'Salida',
                'Cantidad' => $salida['Cantidad'],
                'StockAnterior' => $stockAnterior,
                'StockNuevo' => $stockNuevo,
                'Usuario' => $salida['Usuario'],
                'Observaciones' => $salida['Observaciones'] ?? ('Asignado a vehículo: ' . ($salida['Placa'] ?? 'N/A'))
            ];
        }
        
        // Invertir para mostrar los más recientes primero
        $movimientos = array_reverse($movimientosTemporales);
    }

    // Obtener entradas desde solicitudes_repuestos cuando se entregan (si existe)
    $checkTableSolicitudes = "SELECT COUNT(*) as existe 
                             FROM information_schema.TABLES 
                             WHERE TABLE_SCHEMA = DATABASE() 
                             AND TABLE_NAME = 'solicitudes_repuestos'";
    $resultTableSolicitudes = mysqli_query($conn, $checkTableSolicitudes);
    $tablaSolicitudesExiste = false;
    if ($resultTableSolicitudes) {
        $row = mysqli_fetch_assoc($resultTableSolicitudes);
        $tablaSolicitudesExiste = ($row['existe'] > 0);
    }

    // Nota: Las entradas reales de stock (compras, recepciones) deberían registrarse en una tabla específica
    // Por ahora, solo mostramos las salidas desde repuestos_asignacion
    // Los movimientos ya están ordenados por fecha descendente (más recientes primero)

    mysqli_close($conn);
    return $movimientos;
}

/**
 * Envía alerta de stock bajo al jefe de taller
 */
function enviarAlertaStockBajo($usuario_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [
            'status' => 'error',
            'message' => 'Error de conexión'
        ];
    }

    try {
        $repuestosBajo = obtenerRepuestosStockBajo();
        
        if (empty($repuestosBajo)) {
            return [
                'status' => 'error',
                'message' => 'No hay repuestos con stock bajo'
            ];
        }

        // Obtener usuarios con rol "Jefe de Taller"
        $queryJefe = "SELECT UsuarioID FROM usuarios WHERE Rol = 'Jefe de Taller'";
        $resultJefe = mysqli_query($conn, $queryJefe);
        
        if (!$resultJefe || mysqli_num_rows($resultJefe) == 0) {
            return [
                'status' => 'error',
                'message' => 'No se encontró ningún jefe de taller'
            ];
        }

        $jefesTaller = [];
        while ($row = mysqli_fetch_assoc($resultJefe)) {
            $jefesTaller[] = $row['UsuarioID'];
        }

        // Crear mensaje con los repuestos con stock bajo
        $mensaje = "Alerta de Stock Bajo:\n\n";
        $mensaje .= "Los siguientes repuestos tienen stock bajo o sin stock:\n\n";
        
        foreach ($repuestosBajo as $repuesto) {
            $mensaje .= "• {$repuesto['Nombre']} ({$repuesto['Codigo']}): Stock actual: {$repuesto['Stock']}, Mínimo requerido: {$repuesto['StockMinimo']}\n";
        }
        
        $mensaje .= "\nPor favor, revise el inventario y considere realizar una compra.";

        $titulo = "Alerta de Stock Bajo";
        $modulo = "repuestos";
        $enlace = "repuestos.php";

        // Enviar notificación a todos los jefes de taller
        $resultado = crearNotificacion($jefesTaller, $titulo, $mensaje, $modulo, $enlace);

        if ($resultado) {
            return [
                'status' => 'success',
                'message' => 'Alerta enviada correctamente a ' . count($jefesTaller) . ' jefe(s) de taller',
                'repuestos_alertados' => count($repuestosBajo)
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Error al enviar la alerta'
            ];
        }

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    } finally {
        mysqli_close($conn);
    }
}
