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
            $queryRepuesto = "SELECT nombre as Nombre, codigo as Codigo FROM repuestos WHERE id = $repuesto_id";
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
        $queryRepuesto = "SELECT nombre as Nombre FROM repuestos WHERE id = $repuesto_id";
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

        // Obtener usuarios con rol "Asistente de Repuestos"
        $queryUsuarios = "SELECT UsuarioID FROM usuarios WHERE Rol = 'Asistente de Repuestos'";
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
                COALESCE(v.Placa, '') as Placa
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

    $where = "r.estado = 'Activo'";
    if ($soloSinStock) {
        $where .= " AND r.stock = 0";
    }

    // Si se proporciona el mecánico, excluir repuestos que ya tienen solicitudes pendientes o aprobadas
    // Mejorado: Excluir TODAS las solicitudes del mismo mecánico y repuesto, sin importar la asignación
    $exclusionSolicitudes = "";
    if ($mecanico_id !== null && $mecanico_id > 0) {
        $mecanico_id = intval($mecanico_id);
        
        // Excluir cualquier repuesto que ya tenga una solicitud pendiente o aprobada del mismo mecánico
        // Sin importar si tiene asignación o no, para evitar duplicados
        $exclusionSolicitudes = " AND r.id NOT IN (
            SELECT DISTINCT sr.RepuestoID 
            FROM solicitudes_repuestos sr 
            WHERE sr.MecanicoID = $mecanico_id 
            AND sr.Estado IN ('Pendiente', 'Aprobada')
        )";
    }

    $query = "SELECT 
                r.id as ID,
                r.codigo as Codigo,
                r.nombre as Nombre,
                r.categoria as Categoria,
                r.stock as Stock,
                r.stockminimo as StockMinimo,
                r.descripcion as Descripcion
            FROM repuestos r
            WHERE $where
            $exclusionSolicitudes
            ORDER BY r.nombre ASC";

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

        // Verificar stock disponible antes de aprobar
        $repuesto_id = intval($info['RepuestoID']);
        $cantidad = intval($info['Cantidad']);
        
        // Obtener stock actual y considerar solicitudes ya aprobadas pendientes de entrega
        $queryStock = "SELECT r.Stock, r.StockMinimo,
                      COALESCE(SUM(CASE WHEN sr.Estado = 'Aprobada' THEN sr.Cantidad ELSE 0 END), 0) as CantidadAprobadaPendiente
                      FROM repuestos r
                      LEFT JOIN solicitudes_repuestos sr ON sr.RepuestoID = r.ID AND sr.Estado = 'Aprobada'
                      WHERE r.ID = $repuesto_id
                      GROUP BY r.ID, r.Stock, r.StockMinimo";
        $resultStock = mysqli_query($conn, $queryStock);
        
        if (!$resultStock || mysqli_num_rows($resultStock) == 0) {
            throw new Exception('Repuesto no encontrado');
        }
        
        $rowStock = mysqli_fetch_assoc($resultStock);
        $stockDisponible = intval($rowStock['Stock']);
        $cantidadAprobadaPendiente = intval($rowStock['CantidadAprobadaPendiente']);
        
        // Stock realmente disponible = Stock actual - Cantidad ya aprobada pendiente de entrega
        $stockRealDisponible = $stockDisponible - $cantidadAprobadaPendiente;
        
        // Aprobar solo si hay stock suficiente (considerando solicitudes ya aprobadas)
        if ($stockRealDisponible < $cantidad) {
            // Verificar si ya se notificó al jefe de taller para esta solicitud
            // Primero verificar si el campo existe en la tabla
            $campoExiste = false;
            $queryCheckCampo = "SHOW COLUMNS FROM solicitudes_repuestos LIKE 'FechaNotificacionJefe'";
            $resultCheckCampo = mysqli_query($conn, $queryCheckCampo);
            if ($resultCheckCampo && mysqli_num_rows($resultCheckCampo) > 0) {
                $campoExiste = true;
            }
            
            $yaNotificado = false;
            $fechaNotificacion = null;
            
            if ($campoExiste) {
                $queryNotificacion = "SELECT FechaNotificacionJefe FROM solicitudes_repuestos WHERE ID = $solicitud_id";
                $resultNotificacion = mysqli_query($conn, $queryNotificacion);
                
                if ($resultNotificacion && mysqli_num_rows($resultNotificacion) > 0) {
                    $rowNotificacion = mysqli_fetch_assoc($resultNotificacion);
                    $fechaNotificacion = $rowNotificacion['FechaNotificacionJefe'] ?? null;
                    $yaNotificado = !empty($fechaNotificacion);
                }
            }
            
            // Obtener información del repuesto para la notificación
            $repuestoNombre = $info['RepuestoNombre'];
            $repuestoCodigo = '';
            $queryRepuesto = "SELECT Codigo FROM repuestos WHERE ID = $repuesto_id";
            $resultRepuesto = mysqli_query($conn, $queryRepuesto);
            if ($resultRepuesto && mysqli_num_rows($resultRepuesto) > 0) {
                $rowRepuesto = mysqli_fetch_assoc($resultRepuesto);
                $repuestoCodigo = $rowRepuesto['Codigo'] ?? '';
            }
            
            // Obtener usuarios con rol "Jefe de Taller"
            $jefesTaller = obtenerUsuariosPorRoles(['Jefe de Taller']);
            
            if (!empty($jefesTaller) && !$yaNotificado) {
                // Crear mensaje de notificación
                $codigoTexto = !empty($repuestoCodigo) ? " ({$repuestoCodigo})" : "";
                $placaTexto = !empty($info['Placa']) ? " para el vehículo con placa {$info['Placa']}" : "";
                $mensaje = "Se requiere solicitar más repuestos al proveedor.\n\n";
                $mensaje .= "Repuesto: {$repuestoNombre}{$codigoTexto}\n";
                $mensaje .= "Cantidad solicitada: {$cantidad} unidad(es)\n";
                $mensaje .= "Stock disponible: {$stockDisponible} unidad(es)\n";
                $mensaje .= "Stock insuficiente para aprobar la solicitud{$placaTexto}.\n\n";
                $mensaje .= "Por favor, contacte al proveedor para solicitar más unidades de este repuesto.";
                
                $titulo = "Solicitud de Repuestos - Stock Insuficiente";
                $modulo = "gestion_solicitudes_repuestos";
                $enlace = "gestion_solicitudes_repuestos.php";
                
                // Enviar notificación a todos los jefes de taller
                crearNotificacion($jefesTaller, $titulo, $mensaje, $modulo, $enlace);
                
                // Marcar que se notificó al jefe de taller (solo si el campo existe)
                if ($campoExiste) {
                    $queryUpdateNotificacion = "UPDATE solicitudes_repuestos SET FechaNotificacionJefe = NOW() WHERE ID = $solicitud_id";
                    mysqli_query($conn, $queryUpdateNotificacion);
                }
                
                $mensajeError = "Stock insuficiente para aprobar. Stock disponible: $stockDisponible unidad(es), ya aprobado pendiente de entrega: $cantidadAprobadaPendiente unidad(es), disponible real: $stockRealDisponible unidad(es), solicitado: $cantidad unidad(es). Se ha notificado al Jefe de Taller para solicitar más repuestos al proveedor.";
            } else if ($yaNotificado) {
                // Formatear fecha de notificación para el mensaje
                $fechaFormateada = '';
                if ($fechaNotificacion) {
                    $fechaObj = new DateTime($fechaNotificacion);
                    $fechaFormateada = $fechaObj->format('d/m/Y H:i');
                }
                $mensajeError = "Stock insuficiente para aprobar. Stock disponible: $stockDisponible unidad(es), ya aprobado pendiente de entrega: $cantidadAprobadaPendiente unidad(es), disponible real: $stockRealDisponible unidad(es), solicitado: $cantidad unidad(es). La notificación al Jefe de Taller ya fue enviada" . ($fechaFormateada ? " el $fechaFormateada" : "") . ".";
            } else {
                $mensajeError = "Stock insuficiente para aprobar. Stock disponible: $stockDisponible unidad(es), ya aprobado pendiente de entrega: $cantidadAprobadaPendiente unidad(es), disponible real: $stockRealDisponible unidad(es), solicitado: $cantidad unidad(es).";
            }
            
            throw new Exception($mensajeError);
        }

        // Descontar stock del repuesto INMEDIATAMENTE al aprobar
        // Usar UPDATE con condición WHERE para prevenir stock negativo
        $queryUpdateStock = "UPDATE repuestos 
                            SET Stock = Stock - $cantidad 
                            WHERE ID = $repuesto_id 
                            AND Stock >= $cantidad";
        
        if (!mysqli_query($conn, $queryUpdateStock)) {
            throw new Exception("Error al actualizar stock: " . mysqli_error($conn));
        }
        
        // Verificar si se actualizó alguna fila (si no se actualizó, significa que el stock no era suficiente)
        if (mysqli_affected_rows($conn) == 0) {
            // Re-verificar stock actual para dar mensaje más preciso
            $queryRecheckStock = "SELECT Stock FROM repuestos WHERE ID = $repuesto_id";
            $resultRecheckStock = mysqli_query($conn, $queryRecheckStock);
            $stockActual = 0;
            if ($resultRecheckStock && mysqli_num_rows($resultRecheckStock) > 0) {
                $rowRecheckStock = mysqli_fetch_assoc($resultRecheckStock);
                $stockActual = intval($rowRecheckStock['Stock']);
            }
            throw new Exception("No se puede aprobar: el stock disponible ($stockActual unidad(es)) es insuficiente para la cantidad solicitada ($cantidad unidad(es)). Es posible que otra solicitud haya consumido el stock disponible.");
        }

        // Actualizar solicitud
        $query = "UPDATE solicitudes_repuestos 
                  SET Estado = 'Aprobada', 
                      AprobadoPor = $aprobador_id,
                      FechaAprobacion = NOW()
                  WHERE ID = $solicitud_id";

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al aprobar solicitud: " . mysqli_error($conn));
        }

        // Notificar al mecánico con el estado actual
        $placaTexto = !empty($info['Placa']) ? " para el vehículo con placa {$info['Placa']}" : "";
        $mensaje = "Su solicitud de {$info['Cantidad']} unidad(es) de {$info['RepuestoNombre']}$placaTexto ha sido <strong>APROBADA</strong>.\n\n";
        $mensaje .= "Estado actual: <strong>Aprobada</strong>\n";
        $mensaje .= "Los repuestos han sido reservados y están disponibles para su uso.\n\n";
        $mensaje .= "Recuerde registrar el uso o devolver los repuestos no utilizados desde la vista de tareas.";
        $titulo = "Solicitud de Repuestos Aprobada - Estado: Aprobada";
        $modulo = "tareas";
        $enlace = "tareas.php";
        
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

        // Actualizar solicitud a "Entregada"
        // NOTA: El stock ya fue descontado al aprobar la solicitud, así que solo marcamos como entregada
        $query = "UPDATE solicitudes_repuestos 
                  SET Estado = 'Entregada', 
                      FechaEntrega = NOW()
                  WHERE ID = $solicitud_id";

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error al marcar como entregada: " . mysqli_error($conn));
        }

        // Notificar al mecánico con el estado actual
        $placaTexto = !empty($info['Placa']) ? " para el vehículo con placa {$info['Placa']}" : "";
        $mensaje = "Los repuestos solicitados ({$cantidad} unidad(es) de {$info['RepuestoNombre']})$placaTexto han sido entregados.\n\n";
        $mensaje .= "Estado actual: <strong>Entregada</strong>\n";
        $mensaje .= "Puede continuar con la tarea.";
        $titulo = "Repuestos Entregados - Estado: Entregada";
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
 * Notifica al Jefe de Taller sobre stock insuficiente (sin intentar aprobar)
 */
function notificarJefeTallerStock($solicitud_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [
            'status' => 'error',
            'message' => 'Error de conexión'
        ];
    }

    try {
        $solicitud_id = intval($solicitud_id);

        // Obtener información de la solicitud
        $queryInfo = "SELECT sr.MecanicoID, sr.AsignacionID, sr.RepuestoID, sr.Cantidad, 
                     r.Nombre as RepuestoNombre, r.Stock as StockDisponible, v.Placa
                     FROM solicitudes_repuestos sr
                     INNER JOIN repuestos r ON sr.RepuestoID = r.ID
                     LEFT JOIN asignaciones_mecanico a ON sr.AsignacionID = a.ID
                     LEFT JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                     WHERE sr.ID = $solicitud_id";
        
        $resultInfo = mysqli_query($conn, $queryInfo);
        if (!$resultInfo || mysqli_num_rows($resultInfo) == 0) {
            return [
                'status' => 'error',
                'message' => 'Solicitud no encontrada'
            ];
        }

        $info = mysqli_fetch_assoc($resultInfo);
        $repuesto_id = intval($info['RepuestoID']);
        $cantidad = intval($info['Cantidad']);
        $stockDisponible = intval($info['StockDisponible']);

        // Verificar si el campo FechaNotificacionJefe existe
        $campoExiste = false;
        $queryCheckCampo = "SHOW COLUMNS FROM solicitudes_repuestos LIKE 'FechaNotificacionJefe'";
        $resultCheckCampo = mysqli_query($conn, $queryCheckCampo);
        if ($resultCheckCampo && mysqli_num_rows($resultCheckCampo) > 0) {
            $campoExiste = true;
        }
        
        $yaNotificado = false;
        $fechaNotificacion = null;
        
        if ($campoExiste) {
            $queryNotificacion = "SELECT FechaNotificacionJefe FROM solicitudes_repuestos WHERE ID = $solicitud_id";
            $resultNotificacion = mysqli_query($conn, $queryNotificacion);
            
            if ($resultNotificacion && mysqli_num_rows($resultNotificacion) > 0) {
                $rowNotificacion = mysqli_fetch_assoc($resultNotificacion);
                $fechaNotificacion = $rowNotificacion['FechaNotificacionJefe'] ?? null;
                $yaNotificado = !empty($fechaNotificacion);
            }
        }

        if ($yaNotificado) {
            // Formatear fecha de notificación para el mensaje
            $fechaFormateada = '';
            if ($fechaNotificacion) {
                $fechaObj = new DateTime($fechaNotificacion);
                $fechaFormateada = $fechaObj->format('d/m/Y H:i');
            }
            return [
                'status' => 'error',
                'message' => "La notificación al Jefe de Taller ya fue enviada" . ($fechaFormateada ? " el $fechaFormateada" : "") . "."
            ];
        }

        // Obtener información del repuesto
        $repuestoNombre = $info['RepuestoNombre'];
        $repuestoCodigo = '';
        $queryRepuesto = "SELECT Codigo FROM repuestos WHERE ID = $repuesto_id";
        $resultRepuesto = mysqli_query($conn, $queryRepuesto);
        if ($resultRepuesto && mysqli_num_rows($resultRepuesto) > 0) {
            $rowRepuesto = mysqli_fetch_assoc($resultRepuesto);
            $repuestoCodigo = $rowRepuesto['Codigo'] ?? '';
        }
        
        // Obtener usuarios con rol "Jefe de Taller"
        $jefesTaller = obtenerUsuariosPorRoles(['Jefe de Taller']);
        
        if (empty($jefesTaller)) {
            return [
                'status' => 'error',
                'message' => 'No se encontraron usuarios con rol Jefe de Taller'
            ];
        }

        // Crear mensaje de notificación
        $codigoTexto = !empty($repuestoCodigo) ? " ({$repuestoCodigo})" : "";
        $placaTexto = !empty($info['Placa']) ? " para el vehículo con placa {$info['Placa']}" : "";
        $mensaje = "Se requiere solicitar más repuestos al proveedor.\n\n";
        $mensaje .= "Repuesto: {$repuestoNombre}{$codigoTexto}\n";
        $mensaje .= "Cantidad solicitada: {$cantidad} unidad(es)\n";
        $mensaje .= "Stock disponible: {$stockDisponible} unidad(es)\n";
        $mensaje .= "Stock insuficiente para aprobar la solicitud{$placaTexto}.\n\n";
        $mensaje .= "Por favor, contacte al proveedor para solicitar más unidades de este repuesto.";
        
        $titulo = "Solicitud de Repuestos - Stock Insuficiente";
        $modulo = "gestion_solicitudes_repuestos";
        $enlace = "gestion_solicitudes_repuestos.php";
        
        // Enviar notificación a todos los jefes de taller
        crearNotificacion($jefesTaller, $titulo, $mensaje, $modulo, $enlace);
        
        // Marcar que se notificó al jefe de taller (solo si el campo existe)
        if ($campoExiste) {
            $queryUpdateNotificacion = "UPDATE solicitudes_repuestos SET FechaNotificacionJefe = NOW() WHERE ID = $solicitud_id";
            mysqli_query($conn, $queryUpdateNotificacion);
        }

        mysqli_close($conn);

        return [
            'status' => 'success',
            'message' => 'La notificación al Jefe de Taller ha sido enviada correctamente. Se le ha informado sobre la necesidad de solicitar más repuestos al proveedor.'
        ];

    } catch (Exception $e) {
        mysqli_close($conn);
        return [
            'status' => 'error',
            'message' => 'Error al enviar notificación: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtiene todas las solicitudes de repuestos (para asistente de repuestos)
 */
function obtenerTodasSolicitudesRepuestos($estado = null) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    $where = "1=1";
    if ($estado) {
        $estado = mysqli_real_escape_string($conn, $estado);
        $where .= " AND sr.Estado = '$estado'";
    }

    // Verificar si el campo FechaNotificacionJefe existe
    $campoExiste = false;
    $queryCheckCampo = "SHOW COLUMNS FROM solicitudes_repuestos LIKE 'FechaNotificacionJefe'";
    $resultCheckCampo = mysqli_query($conn, $queryCheckCampo);
    if ($resultCheckCampo && mysqli_num_rows($resultCheckCampo) > 0) {
        $campoExiste = true;
    }
    
    $campoNotificacion = $campoExiste ? 
        "DATE_FORMAT(sr.FechaNotificacionJefe, '%d/%m/%Y %H:%i') as FechaNotificacionJefe, sr.FechaNotificacionJefe as FechaNotificacionJefeRaw," : 
        "NULL as FechaNotificacionJefe, NULL as FechaNotificacionJefeRaw,";
    
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
                DATE_FORMAT(sr.FechaEntrega, '%d/%m/%Y %H:%i') as FechaEntrega,
                $campoNotificacion
                r.Codigo as RepuestoCodigo,
                r.Nombre as RepuestoNombre,
                r.Categoria as RepuestoCategoria,
                r.Stock as StockDisponible,
                r.StockMinimo as StockMinimo,
                COALESCE((
                    SELECT SUM(sr2.Cantidad)
                    FROM solicitudes_repuestos sr2
                    WHERE sr2.RepuestoID = r.ID 
                    AND sr2.Estado = 'Aprobada'
                    AND sr2.ID != sr.ID
                ), 0) as CantidadAprobadaPendiente,
                GREATEST(0, r.Stock - COALESCE((
                    SELECT SUM(sr2.Cantidad)
                    FROM solicitudes_repuestos sr2
                    WHERE sr2.RepuestoID = r.ID 
                    AND sr2.Estado = 'Aprobada'
                    AND sr2.ID != sr.ID
                ), 0)) as StockRealDisponible,
                v.Placa,
                u.NombreUsuario as MecanicoNombre
            FROM solicitudes_repuestos sr
            INNER JOIN repuestos r ON sr.RepuestoID = r.ID
            LEFT JOIN asignaciones_mecanico a ON sr.AsignacionID = a.ID
            LEFT JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
            LEFT JOIN usuarios u ON sr.MecanicoID = u.UsuarioID
            WHERE $where
            ORDER BY 
                CASE sr.Urgencia
                    WHEN 'Alta' THEN 1
                    WHEN 'Media' THEN 2
                    WHEN 'Baja' THEN 3
                    ELSE 4
                END,
                sr.FechaSolicitud DESC";

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
 * Obtiene todos los repuestos para el inventario
 */
function obtenerTodosRepuestos() {
    try {
        // Usar conexión segura sin die() para evitar romper la respuesta JSON
        $conn = @new mysqli("localhost", "root", "", "Pepsico");
        
        if ($conn->connect_errno) {
            return ['status' => 'error', 'message' => 'Error de conexión a la base de datos: ' . $conn->connect_error];
        }
        
        if (!$conn->set_charset("utf8mb4")) {
            $conn->close();
            return ['status' => 'error', 'message' => 'Error cargando charset: ' . $conn->error];
        }

        // Verificar si existe la tabla repuestos
        $checkTable = "SHOW TABLES LIKE 'repuestos'";
        $resultCheck = mysqli_query($conn, $checkTable);
        $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
        
        if ($resultCheck) {
            mysqli_free_result($resultCheck);
        }
        
        if (!$tablaExiste) {
            mysqli_close($conn);
            return ['status' => 'success', 'data' => []];
        }

        // Usar nombres de columnas con backticks para evitar problemas de case-sensitivity
        // Intentar primero con mayúsculas (como está en el CREATE TABLE)
        $query = "SELECT 
                    `ID` as ID,
                    `Codigo` as Codigo,
                    `Nombre` as Nombre,
                    `Categoria` as Categoria,
                    `Stock` as Stock,
                    `StockMinimo` as StockMinimo,
                    `Precio` as Precio,
                    `Estado` as Estado,
                    `Descripcion` as Descripcion,
                    `FechaCreacion` as FechaCreacion,
                    `FechaActualizacion` as FechaActualizacion
                FROM repuestos
                ORDER BY Nombre ASC";

        $result = mysqli_query($conn, $query);
        $repuestos = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $repuestos[] = $row;
            }
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'Error en consulta: ' . $error];
        }

        mysqli_close($conn);
        return ['status' => 'success', 'data' => $repuestos];
        
    } catch (Exception $e) {
        if (isset($conn)) {
            mysqli_close($conn);
        }
        return ['status' => 'error', 'message' => 'Error al obtener repuestos: ' . $e->getMessage()];
    }
}

/**
 * Asigna un vehículo a una solicitud de repuestos aprobada
 */
function asignarVehiculoASolicitud($solicitud_id, $mecanico_id, $asignacion_id) {
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
        $mecanico_id = intval($mecanico_id);
        $asignacion_id = intval($asignacion_id);

        // Verificar que la solicitud existe, pertenece al mecánico y está aprobada
        $queryVerificar = "SELECT sr.ID, sr.Estado, sr.AsignacionID, r.Nombre as RepuestoNombre
                          FROM solicitudes_repuestos sr
                          INNER JOIN repuestos r ON sr.RepuestoID = r.ID
                          WHERE sr.ID = $solicitud_id 
                            AND sr.MecanicoID = $mecanico_id 
                            AND sr.Estado = 'Aprobada'";

        $resultVerificar = mysqli_query($conn, $queryVerificar);
        if (!$resultVerificar || mysqli_num_rows($resultVerificar) == 0) {
            throw new Exception('Solicitud no encontrada, no autorizada o no está aprobada');
        }

        $info = mysqli_fetch_assoc($resultVerificar);

        // Verificar que la asignación pertenece al mecánico
        $queryAsignacion = "SELECT a.ID, v.Placa 
                           FROM asignaciones_mecanico a
                           INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                           WHERE a.ID = $asignacion_id 
                             AND a.MecanicoID = $mecanico_id";

        $resultAsignacion = mysqli_query($conn, $queryAsignacion);
        if (!$resultAsignacion || mysqli_num_rows($resultAsignacion) == 0) {
            throw new Exception('La asignación no existe o no pertenece al mecánico');
        }

        $asignacionInfo = mysqli_fetch_assoc($resultAsignacion);

        // Actualizar la solicitud con la asignación
        $queryUpdate = "UPDATE solicitudes_repuestos 
                       SET AsignacionID = $asignacion_id
                       WHERE ID = $solicitud_id";

        if (!mysqli_query($conn, $queryUpdate)) {
            throw new Exception("Error al asignar vehículo: " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        mysqli_close($conn);

        return [
            'status' => 'success',
            'message' => "Vehículo {$asignacionInfo['Placa']} asignado correctamente a la solicitud de {$info['RepuestoNombre']}"
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
 * Obtiene los vehículos asignados a un mecánico (para selección en solicitud de repuestos)
 */
function obtenerVehiculosAsignadosMecanico($mecanico_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [
            'status' => 'error',
            'message' => 'Error de conexión'
        ];
    }

    try {
        $mecanico_id = intval($mecanico_id);
        
        $query = "SELECT DISTINCT
                    a.ID as AsignacionID,
                    v.ID as VehiculoID,
                    v.Placa,
                    v.Marca,
                    v.Modelo,
                    v.TipoVehiculo,
                    COALESCE(NULLIF(a.Estado, ''), 'Asignado') as EstadoAsignacion
                  FROM asignaciones_mecanico a
                  INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                  WHERE a.MecanicoID = $mecanico_id
                    AND (a.Estado IS NULL OR a.Estado = '' OR a.Estado != 'Completado')
                  ORDER BY a.FechaAsignacion DESC";

        $result = mysqli_query($conn, $query);
        $vehiculos = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $vehiculos[] = $row;
            }
            mysqli_free_result($result);
        }

        mysqli_close($conn);
        return [
            'status' => 'success',
            'data' => $vehiculos
        ];

    } catch (Exception $e) {
        if (isset($conn)) {
            mysqli_close($conn);
        }
        return [
            'status' => 'error',
            'message' => 'Error al obtener vehículos: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtiene los repuestos aprobados de un mecánico (pendientes de uso/devolución)
 */
function obtenerRepuestosAprobadosMecanico($mecanico_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [
            'status' => 'error',
            'message' => 'Error de conexión'
        ];
    }

    try {
        $mecanico_id = intval($mecanico_id);
        
        $query = "SELECT 
                    sr.ID as SolicitudID,
                    sr.RepuestoID,
                    sr.Cantidad,
                    sr.AsignacionID,
                    r.Codigo as RepuestoCodigo,
                    r.Nombre as RepuestoNombre,
                    COALESCE(v.Placa, '') as Placa,
                    DATE_FORMAT(sr.FechaAprobacion, '%d/%m/%Y %H:%i') as FechaAprobacion,
                    COALESCE(SUM(CASE WHEN ru.ID IS NOT NULL THEN ru.CantidadUsada ELSE 0 END), 0) as CantidadUsada,
                    COALESCE(SUM(CASE WHEN rd.ID IS NOT NULL THEN rd.CantidadDevuelta ELSE 0 END), 0) as CantidadDevuelta,
                    (sr.Cantidad - COALESCE(SUM(CASE WHEN ru.ID IS NOT NULL THEN ru.CantidadUsada ELSE 0 END), 0) 
                     - COALESCE(SUM(CASE WHEN rd.ID IS NOT NULL THEN rd.CantidadDevuelta ELSE 0 END), 0)) as CantidadPendiente
                  FROM solicitudes_repuestos sr
                  INNER JOIN repuestos r ON sr.RepuestoID = r.ID
                  LEFT JOIN asignaciones_mecanico a ON sr.AsignacionID = a.ID
                  LEFT JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                  LEFT JOIN registro_uso_repuestos ru ON sr.ID = ru.SolicitudID
                  LEFT JOIN registro_devolucion_repuestos rd ON sr.ID = rd.SolicitudID
                  WHERE sr.MecanicoID = $mecanico_id 
                    AND sr.Estado = 'Aprobada'
                  GROUP BY sr.ID, sr.RepuestoID, sr.Cantidad, sr.AsignacionID, r.Codigo, r.Nombre, v.Placa, sr.FechaAprobacion
                  HAVING CantidadPendiente > 0
                  ORDER BY sr.FechaAprobacion DESC";

        $result = mysqli_query($conn, $query);
        $repuestos = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $repuestos[] = $row;
            }
            mysqli_free_result($result);
        }

        mysqli_close($conn);
        return [
            'status' => 'success',
            'data' => $repuestos
        ];

    } catch (Exception $e) {
        if (isset($conn)) {
            mysqli_close($conn);
        }
        return [
            'status' => 'error',
            'message' => 'Error al obtener repuestos aprobados: ' . $e->getMessage()
        ];
    }
}

/**
 * Registra el uso de repuestos aprobados
 */
function registrarUsoRepuestos($solicitud_id, $mecanico_id, $cantidad_usada, $observaciones = '') {
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
        $mecanico_id = intval($mecanico_id);
        $cantidad_usada = intval($cantidad_usada);
        $observaciones = mysqli_real_escape_string($conn, $observaciones);

        // Verificar que la solicitud existe y pertenece al mecánico
        // Permitir tanto 'Aprobada' como 'Entregada' para poder gestionar repuestos incluso si ya se marcó como entregada parcialmente
        $queryVerificar = "SELECT sr.ID, sr.Cantidad, sr.RepuestoID, sr.AsignacionID, r.Nombre as RepuestoNombre,
                           COALESCE(SUM(CASE WHEN ru.ID IS NOT NULL THEN ru.CantidadUsada ELSE 0 END), 0) as CantidadUsadaTotal,
                           COALESCE(SUM(CASE WHEN rd.ID IS NOT NULL THEN rd.CantidadDevuelta ELSE 0 END), 0) as CantidadDevueltaTotal
                           FROM solicitudes_repuestos sr
                           INNER JOIN repuestos r ON sr.RepuestoID = r.ID
                           LEFT JOIN registro_uso_repuestos ru ON sr.ID = ru.SolicitudID
                           LEFT JOIN registro_devolucion_repuestos rd ON sr.ID = rd.SolicitudID
                           WHERE sr.ID = $solicitud_id 
                             AND sr.MecanicoID = $mecanico_id 
                             AND sr.Estado IN ('Aprobada', 'Entregada')
                           GROUP BY sr.ID, sr.Cantidad, sr.RepuestoID, sr.AsignacionID, r.Nombre";

        $resultVerificar = mysqli_query($conn, $queryVerificar);
        if (!$resultVerificar || mysqli_num_rows($resultVerificar) == 0) {
            throw new Exception('Solicitud no encontrada o no autorizada');
        }

        $info = mysqli_fetch_assoc($resultVerificar);
        $cantidadTotal = intval($info['Cantidad']);
        $cantidadUsadaTotal = intval($info['CantidadUsadaTotal']);
        $cantidadDevueltaTotal = intval($info['CantidadDevueltaTotal']);
        $cantidadDisponible = $cantidadTotal - $cantidadUsadaTotal - $cantidadDevueltaTotal;

        if ($cantidad_usada > $cantidadDisponible) {
            throw new Exception("La cantidad a usar ($cantidad_usada) excede la cantidad disponible ($cantidadDisponible)");
        }

        if ($cantidad_usada <= 0) {
            throw new Exception("La cantidad a usar debe ser mayor a 0");
        }

        // Verificar si existe la tabla registro_uso_repuestos, si no, crearla
        $checkTable = "SHOW TABLES LIKE 'registro_uso_repuestos'";
        $resultCheck = mysqli_query($conn, $checkTable);
        if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
            // Crear tabla si no existe
            $createTable = "CREATE TABLE IF NOT EXISTS registro_uso_repuestos (
                ID INT(11) NOT NULL AUTO_INCREMENT,
                SolicitudID INT(11) NOT NULL,
                MecanicoID INT(11) NOT NULL,
                CantidadUsada INT(11) NOT NULL,
                Observaciones TEXT DEFAULT NULL,
                FechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (ID),
                KEY SolicitudID (SolicitudID),
                KEY MecanicoID (MecanicoID),
                CONSTRAINT fk_registro_uso_solicitud FOREIGN KEY (SolicitudID) 
                    REFERENCES solicitudes_repuestos(ID) ON DELETE CASCADE,
                CONSTRAINT fk_registro_uso_mecanico FOREIGN KEY (MecanicoID) 
                    REFERENCES usuarios(UsuarioID) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            mysqli_query($conn, $createTable);
        }

        // Insertar registro de uso
        $queryInsert = "INSERT INTO registro_uso_repuestos 
                       (SolicitudID, MecanicoID, CantidadUsada, Observaciones) 
                       VALUES ($solicitud_id, $mecanico_id, $cantidad_usada, '$observaciones')";

        if (!mysqli_query($conn, $queryInsert)) {
            throw new Exception("Error al registrar uso: " . mysqli_error($conn));
        }

        // Verificar si se debe marcar la solicitud como "Entregada" (si se usó todo)
        $nuevaCantidadUsadaTotal = $cantidadUsadaTotal + $cantidad_usada;
        $marcarComoEntregada = false;
        if ($nuevaCantidadUsadaTotal + $cantidadDevueltaTotal >= $cantidadTotal) {
            $queryUpdateEstado = "UPDATE solicitudes_repuestos 
                                 SET Estado = 'Entregada', 
                                     FechaEntrega = NOW()
                                 WHERE ID = $solicitud_id";
            mysqli_query($conn, $queryUpdateEstado);
            $marcarComoEntregada = true;
        }

        // Notificar al mecánico si se marcó como entregada
        if ($marcarComoEntregada) {
            // Obtener información de la asignación para la placa
            $placa = null;
            if ($info['AsignacionID']) {
                $queryPlaca = "SELECT v.Placa 
                              FROM asignaciones_mecanico a
                              INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                              WHERE a.ID = " . intval($info['AsignacionID']);
                $resultPlaca = mysqli_query($conn, $queryPlaca);
                if ($resultPlaca && mysqli_num_rows($resultPlaca) > 0) {
                    $rowPlaca = mysqli_fetch_assoc($resultPlaca);
                    $placa = $rowPlaca['Placa'];
                }
            }
            $placaTexto = !empty($placa) ? " para el vehículo con placa $placa" : "";
            $mensaje = "Los repuestos solicitados ({$info['Cantidad']} unidad(es) de {$info['RepuestoNombre']})$placaTexto han sido completamente utilizados.\n\n";
            $mensaje .= "Estado actual: <strong>Entregada</strong>\n";
            $mensaje .= "Cantidad usada: $nuevaCantidadUsadaTotal unidad(es)";
            $titulo = "Repuestos Completamente Utilizados - Estado: Entregada";
            $modulo = "estado_solicitudes_repuestos";
            $enlace = "estado_solicitudes_repuestos.php";
            crearNotificacion([$mecanico_id], $titulo, $mensaje, $modulo, $enlace);
        }

        mysqli_commit($conn);
        mysqli_close($conn);

        $mensajeRespuesta = "Uso de $cantidad_usada unidad(es) de {$info['RepuestoNombre']} registrado correctamente";
        if ($marcarComoEntregada) {
            $mensajeRespuesta .= ". La solicitud ha sido marcada como Entregada.";
        }

        return [
            'status' => 'success',
            'message' => $mensajeRespuesta
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
 * Registra la devolución de repuestos no utilizados
 */
function registrarDevolucionRepuestos($solicitud_id, $mecanico_id, $cantidad_devuelta, $observaciones = '') {
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
        $mecanico_id = intval($mecanico_id);
        $cantidad_devuelta = intval($cantidad_devuelta);
        $observaciones = mysqli_real_escape_string($conn, $observaciones);

        // Verificar que la solicitud existe y pertenece al mecánico
        // Permitir tanto 'Aprobada' como 'Entregada' para poder gestionar repuestos incluso si ya se marcó como entregada parcialmente
        $queryVerificar = "SELECT sr.ID, sr.Cantidad, sr.RepuestoID, sr.AsignacionID, r.Nombre as RepuestoNombre,
                           COALESCE(SUM(CASE WHEN ru.ID IS NOT NULL THEN ru.CantidadUsada ELSE 0 END), 0) as CantidadUsadaTotal,
                           COALESCE(SUM(CASE WHEN rd.ID IS NOT NULL THEN rd.CantidadDevuelta ELSE 0 END), 0) as CantidadDevueltaTotal
                           FROM solicitudes_repuestos sr
                           INNER JOIN repuestos r ON sr.RepuestoID = r.ID
                           LEFT JOIN registro_uso_repuestos ru ON sr.ID = ru.SolicitudID
                           LEFT JOIN registro_devolucion_repuestos rd ON sr.ID = rd.SolicitudID
                           WHERE sr.ID = $solicitud_id 
                             AND sr.MecanicoID = $mecanico_id 
                             AND sr.Estado IN ('Aprobada', 'Entregada')
                           GROUP BY sr.ID, sr.Cantidad, sr.RepuestoID, sr.AsignacionID, r.Nombre";

        $resultVerificar = mysqli_query($conn, $queryVerificar);
        if (!$resultVerificar || mysqli_num_rows($resultVerificar) == 0) {
            throw new Exception('Solicitud no encontrada o no autorizada');
        }

        $info = mysqli_fetch_assoc($resultVerificar);
        $cantidadTotal = intval($info['Cantidad']);
        $cantidadUsadaTotal = intval($info['CantidadUsadaTotal']);
        $cantidadDevueltaTotal = intval($info['CantidadDevueltaTotal']);
        $cantidadDisponible = $cantidadTotal - $cantidadUsadaTotal - $cantidadDevueltaTotal;

        if ($cantidad_devuelta > $cantidadDisponible) {
            throw new Exception("La cantidad a devolver ($cantidad_devuelta) excede la cantidad disponible ($cantidadDisponible)");
        }

        if ($cantidad_devuelta <= 0) {
            throw new Exception("La cantidad a devolver debe ser mayor a 0");
        }

        // Verificar si existe la tabla registro_devolucion_repuestos, si no, crearla
        $checkTable = "SHOW TABLES LIKE 'registro_devolucion_repuestos'";
        $resultCheck = mysqli_query($conn, $checkTable);
        if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
            // Crear tabla si no existe
            $createTable = "CREATE TABLE IF NOT EXISTS registro_devolucion_repuestos (
                ID INT(11) NOT NULL AUTO_INCREMENT,
                SolicitudID INT(11) NOT NULL,
                MecanicoID INT(11) NOT NULL,
                CantidadDevuelta INT(11) NOT NULL,
                Observaciones TEXT DEFAULT NULL,
                FechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (ID),
                KEY SolicitudID (SolicitudID),
                KEY MecanicoID (MecanicoID),
                CONSTRAINT fk_registro_devolucion_solicitud FOREIGN KEY (SolicitudID) 
                    REFERENCES solicitudes_repuestos(ID) ON DELETE CASCADE,
                CONSTRAINT fk_registro_devolucion_mecanico FOREIGN KEY (MecanicoID) 
                    REFERENCES usuarios(UsuarioID) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            mysqli_query($conn, $createTable);
        }

        // Insertar registro de devolución
        $queryInsert = "INSERT INTO registro_devolucion_repuestos 
                       (SolicitudID, MecanicoID, CantidadDevuelta, Observaciones) 
                       VALUES ($solicitud_id, $mecanico_id, $cantidad_devuelta, '$observaciones')";

        if (!mysqli_query($conn, $queryInsert)) {
            throw new Exception("Error al registrar devolución: " . mysqli_error($conn));
        }

        // Devolver el stock al inventario
        $repuesto_id = intval($info['RepuestoID']);
        $queryUpdateStock = "UPDATE repuestos 
                            SET Stock = Stock + $cantidad_devuelta 
                            WHERE ID = $repuesto_id";

        if (!mysqli_query($conn, $queryUpdateStock)) {
            throw new Exception("Error al actualizar stock: " . mysqli_error($conn));
        }

        // Verificar si se debe marcar la solicitud como "Entregada" (si se devolvió/usó todo)
        $nuevaCantidadDevueltaTotal = $cantidadDevueltaTotal + $cantidad_devuelta;
        $marcarComoEntregada = false;
        if ($nuevaCantidadDevueltaTotal + $cantidadUsadaTotal >= $cantidadTotal) {
            $queryUpdateEstado = "UPDATE solicitudes_repuestos 
                                 SET Estado = 'Entregada', 
                                     FechaEntrega = NOW()
                                 WHERE ID = $solicitud_id";
            mysqli_query($conn, $queryUpdateEstado);
            $marcarComoEntregada = true;
        }

        // Notificar al mecánico si se marcó como entregada
        if ($marcarComoEntregada) {
            // Obtener información de la asignación para la placa
            $placa = null;
            $queryInfoSolicitud = "SELECT AsignacionID FROM solicitudes_repuestos WHERE ID = $solicitud_id";
            $resultInfoSolicitud = mysqli_query($conn, $queryInfoSolicitud);
            if ($resultInfoSolicitud && mysqli_num_rows($resultInfoSolicitud) > 0) {
                $rowInfoSolicitud = mysqli_fetch_assoc($resultInfoSolicitud);
                $asignacionId = intval($rowInfoSolicitud['AsignacionID'] ?? 0);
                if ($asignacionId > 0) {
                    $queryPlaca = "SELECT v.Placa 
                                  FROM asignaciones_mecanico a
                                  INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                                  WHERE a.ID = $asignacionId";
                    $resultPlaca = mysqli_query($conn, $queryPlaca);
                    if ($resultPlaca && mysqli_num_rows($resultPlaca) > 0) {
                        $rowPlaca = mysqli_fetch_assoc($resultPlaca);
                        $placa = $rowPlaca['Placa'];
                    }
                }
            }
            $placaTexto = !empty($placa) ? " para el vehículo con placa $placa" : "";
            $mensaje = "Los repuestos solicitados ({$info['Cantidad']} unidad(es) de {$info['RepuestoNombre']})$placaTexto han sido completamente gestionados (usados y/o devueltos).\n\n";
            $mensaje .= "Estado actual: <strong>Entregada</strong>\n";
            $mensaje .= "Cantidad devuelta: $nuevaCantidadDevueltaTotal unidad(es)\n";
            $mensaje .= "El stock ha sido actualizado.";
            $titulo = "Repuestos Completamente Gestionados - Estado: Entregada";
            $modulo = "estado_solicitudes_repuestos";
            $enlace = "estado_solicitudes_repuestos.php";
            crearNotificacion([$mecanico_id], $titulo, $mensaje, $modulo, $enlace);
        }

        mysqli_commit($conn);
        mysqli_close($conn);

        $mensajeRespuesta = "Devolución de $cantidad_devuelta unidad(es) de {$info['RepuestoNombre']} registrada correctamente. El stock ha sido actualizado.";
        if ($marcarComoEntregada) {
            $mensajeRespuesta .= " La solicitud ha sido marcada como Entregada.";
        }

        return [
            'status' => 'success',
            'message' => $mensajeRespuesta
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
 * Obtiene repuestos con stock bajo (Stock <= StockMinimo)
 */
function obtenerRepuestosStockBajo() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    $query = "SELECT 
                id as ID,
                codigo as Codigo,
                nombre as Nombre,
                categoria as Categoria,
                stock as Stock,
                stockminimo as StockMinimo,
                precio as Precio,
                estado as Estado,
                descripcion as Descripcion
            FROM repuestos
            WHERE estado = 'Activo' AND stock <= stockminimo
            ORDER BY stock ASC, nombre ASC";

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
    $queryStock = "SELECT stock as Stock FROM repuestos WHERE id = $repuesto_id";
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
