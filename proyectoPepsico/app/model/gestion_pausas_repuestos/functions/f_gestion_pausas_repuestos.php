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

        $asignacion_id = !empty($datos['asignacion_id']) ? intval($datos['asignacion_id']) : null;
        $mecanico_id = intval($datos['mecanico_id']);
        $repuesto_id = intval($datos['repuesto_id']);
        $cantidad = intval($datos['cantidad']);
        $urgencia = mysqli_real_escape_string($conn, $datos['urgencia']);
        $motivo = mysqli_real_escape_string($conn, $datos['motivo'] ?? '');

        // Validar que la asignación pertenece al mecánico solo si se proporciona
        if ($asignacion_id !== null) {
            $queryAsignacion = "SELECT ID FROM asignaciones_mecanico WHERE ID = $asignacion_id AND MecanicoID = $mecanico_id";
            $resultAsignacion = mysqli_query($conn, $queryAsignacion);
            if (!$resultAsignacion || mysqli_num_rows($resultAsignacion) == 0) {
                return [
                    'status' => 'error',
                    'message' => 'La asignación no existe o no pertenece al mecánico'
                ];
            }
        }

        // Insertar solicitud (AsignacionID puede ser NULL)
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
                DATE_FORMAT(sr.FechaEntrega, '%d/%m/%Y %H:%i') as FechaEntrega,
                r.Codigo as RepuestoCodigo,
                r.Nombre as RepuestoNombre,
                r.Categoria as RepuestoCategoria,
                v.Placa
            FROM solicitudes_repuestos sr
            INNER JOIN repuestos r ON sr.RepuestoID = r.ID
            INNER JOIN asignaciones_mecanico a ON sr.AsignacionID = a.ID
            INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
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
            INNER JOIN asignaciones_mecanico a ON sr.AsignacionID = a.ID
            INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
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
function obtenerRepuestosDisponibles($soloSinStock = false) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    $where = "Estado = 'Activo'";
    if ($soloSinStock) {
        $where .= " AND Stock = 0";
    }

    $query = "SELECT 
                ID,
                Codigo,
                Nombre,
                Categoria,
                Stock,
                StockMinimo,
                Descripcion
            FROM repuestos
            WHERE $where
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
                     INNER JOIN asignaciones_mecanico a ON sr.AsignacionID = a.ID
                     INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
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
                     INNER JOIN asignaciones_mecanico a ON sr.AsignacionID = a.ID
                     INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
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
