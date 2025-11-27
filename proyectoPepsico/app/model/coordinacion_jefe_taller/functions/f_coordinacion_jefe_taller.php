<?php
require_once __DIR__ . '/../../../config/conexion.php';

/**
 * Conexión segura sin die() - retorna recurso mysqli
 */
function conectarPepsicoSeguro() {
    try {
        $mysqli = @mysqli_connect("localhost", "root", "", "Pepsico");
        
        if (!$mysqli || mysqli_connect_errno()) {
            error_log("Error de conexión: " . (mysqli_connect_error() ?: "Error desconocido"));
            return null;
        }
        
        if (!mysqli_set_charset($mysqli, "utf8mb4")) {
            error_log("Error cargando charset: " . mysqli_error($mysqli));
            mysqli_close($mysqli);
            return null;
        }
        
        return $mysqli;
    } catch (Exception $e) {
        error_log("Excepción en conexión: " . $e->getMessage());
        return null;
    }
}

/**
 * Crea una comunicación/solicitud al jefe de taller desde coordinador de zona
 */
function crearComunicacionJefeTaller($datos) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Verificar y crear tabla si no existe
    verificarTablaComunicacionesJefeTaller($conn);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $usuarioId = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0;

    $tipoSolicitud = mysqli_real_escape_string($conn, $datos['tipo_solicitud']);
    $prioridad = mysqli_real_escape_string($conn, $datos['prioridad']);
    $asunto = mysqli_real_escape_string($conn, $datos['asunto']);
    $descripcion = mysqli_real_escape_string($conn, $datos['descripcion']);

    $query = "INSERT INTO comunicaciones_jefe_taller 
              (UsuarioID, TipoSolicitud, Prioridad, Asunto, Descripcion, Estado, FechaCreacion) 
              VALUES ($usuarioId, '$tipoSolicitud', '$prioridad', '$asunto', '$descripcion', 'Pendiente', NOW())";

    if (mysqli_query($conn, $query)) {
        $comunicacionId = mysqli_insert_id($conn);
        mysqli_close($conn);
        return ['status' => 'success', 'message' => 'Comunicación enviada correctamente', 'id' => $comunicacionId];
    } else {
        $error = mysqli_error($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al crear comunicación: ' . $error];
    }
}

/**
 * Obtiene las comunicaciones del coordinador de zona o todas si es Jefe de Taller
 */
function obtenerComunicacionesJefeTaller() {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Verificar si existe la tabla
    $checkTable = "SHOW TABLES LIKE 'comunicaciones_jefe_taller'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    if (!$tablaExiste) {
        mysqli_close($conn);
        return ['status' => 'success', 'data' => []];
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $usuarioId = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0;
    $usuarioRol = isset($_SESSION['usuario']['rol']) ? $_SESSION['usuario']['rol'] : '';

    // Si es Jefe de Taller, mostrar todas las comunicaciones
    // Si es Coordinador de Zona, mostrar solo las suyas
    $whereClause = ($usuarioRol === 'Jefe de Taller') ? "1=1" : "c.UsuarioID = $usuarioId";

    $query = "SELECT c.ID, c.TipoSolicitud, c.Prioridad, c.Asunto, c.Descripcion, 
                     c.Estado, c.FechaCreacion, c.FechaRespuesta, c.Respuesta,
                     u.NombreUsuario as UsuarioNombre,
                     c.UsuarioID
              FROM comunicaciones_jefe_taller c
              LEFT JOIN usuarios u ON c.UsuarioID = u.UsuarioID
              WHERE $whereClause
              ORDER BY c.FechaCreacion DESC
              LIMIT 100";

    $result = mysqli_query($conn, $query);
    $comunicaciones = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $comunicaciones[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $comunicaciones];
}

/**
 * Obtiene los detalles de una comunicación
 */
function obtenerDetallesComunicacion($id) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    $id = intval($id);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $usuarioId = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0;
    $usuarioRol = isset($_SESSION['usuario']['rol']) ? $_SESSION['usuario']['rol'] : '';

    // Si es Jefe de Taller, puede ver cualquier comunicación
    // Si es Coordinador de Zona, solo puede ver las suyas
    $whereClause = ($usuarioRol === 'Jefe de Taller') 
        ? "c.ID = $id" 
        : "c.ID = $id AND c.UsuarioID = $usuarioId";

    $query = "SELECT c.*, u.NombreUsuario as UsuarioNombre
              FROM comunicaciones_jefe_taller c
              LEFT JOIN usuarios u ON c.UsuarioID = u.UsuarioID
              WHERE $whereClause
              LIMIT 1";

    $result = mysqli_query($conn, $query);
    $comunicacion = null;

    if ($result && mysqli_num_rows($result) > 0) {
        $comunicacion = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    
    if ($comunicacion) {
        return ['status' => 'success', 'data' => $comunicacion];
    } else {
        return ['status' => 'error', 'message' => 'Comunicación no encontrada'];
    }
}

/**
 * Responde a una comunicación (solo Jefe de Taller)
 */
function responderComunicacion($id, $respuesta) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $usuarioRol = isset($_SESSION['usuario']['rol']) ? $_SESSION['usuario']['rol'] : '';

    // Solo el Jefe de Taller puede responder
    if ($usuarioRol !== 'Jefe de Taller') {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'No tiene permisos para responder'];
    }

    $id = intval($id);
    $respuesta = mysqli_real_escape_string($conn, trim($respuesta));

    if (empty($respuesta)) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'La respuesta no puede estar vacía'];
    }

    // Verificar que la comunicación existe
    $checkQuery = "SELECT ID FROM comunicaciones_jefe_taller WHERE ID = $id LIMIT 1";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (!$checkResult || mysqli_num_rows($checkResult) == 0) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Comunicación no encontrada'];
    }

    // Actualizar la comunicación con la respuesta
    $query = "UPDATE comunicaciones_jefe_taller 
              SET Respuesta = '$respuesta', 
                  FechaRespuesta = NOW(),
                  Estado = CASE 
                      WHEN Estado = 'Pendiente' THEN 'En Proceso'
                      ELSE Estado
                  END
              WHERE ID = $id";

    if (mysqli_query($conn, $query)) {
        mysqli_close($conn);
        return ['status' => 'success', 'message' => 'Respuesta enviada correctamente'];
    } else {
        $error = mysqli_error($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al guardar respuesta: ' . $error];
    }
}

/**
 * Obtiene estadísticas de comunicaciones
 */
function obtenerEstadisticasComunicaciones() {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $usuarioId = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0;
    $usuarioRol = isset($_SESSION['usuario']['rol']) ? $_SESSION['usuario']['rol'] : '';

    $estadisticas = [
        'pendientes' => 0,
        'en_proceso' => 0,
        'completadas' => 0,
        'urgentes' => 0
    ];

    $checkTable = "SHOW TABLES LIKE 'comunicaciones_jefe_taller'";
    $resultCheck = mysqli_query($conn, $checkTable);
    if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
        // Si es Jefe de Taller, mostrar todas las comunicaciones
        // Si es Coordinador de Zona, mostrar solo las suyas
        $whereClause = ($usuarioRol === 'Jefe de Taller') ? "1=1" : "UsuarioID = $usuarioId";

        // Pendientes
        $query = "SELECT COUNT(*) as total FROM comunicaciones_jefe_taller 
                  WHERE $whereClause AND Estado = 'Pendiente'";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['pendientes'] = $row['total'];
            mysqli_free_result($result);
        }

        // En proceso
        $query = "SELECT COUNT(*) as total FROM comunicaciones_jefe_taller 
                  WHERE $whereClause AND Estado = 'En Proceso'";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['en_proceso'] = $row['total'];
            mysqli_free_result($result);
        }

        // Completadas/Aprobadas
        $query = "SELECT COUNT(*) as total FROM comunicaciones_jefe_taller 
                  WHERE $whereClause AND Estado IN ('Aprobada', 'Completada')";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['completadas'] = $row['total'];
            mysqli_free_result($result);
        }

        // Urgentes
        $query = "SELECT COUNT(*) as total FROM comunicaciones_jefe_taller 
                  WHERE $whereClause AND Prioridad = 'urgente' AND Estado != 'Completada'";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['urgentes'] = $row['total'];
            mysqli_free_result($result);
        }
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $estadisticas];
}

/**
 * Verifica y crea la tabla de comunicaciones si no existe
 */
function verificarTablaComunicacionesJefeTaller($conn) {
    $checkTable = "SHOW TABLES LIKE 'comunicaciones_jefe_taller'";
    $result = mysqli_query($conn, $checkTable);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return true;
    }

    // Crear tabla
    $createTable = "CREATE TABLE IF NOT EXISTS comunicaciones_jefe_taller (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        UsuarioID INT NOT NULL,
        TipoSolicitud VARCHAR(50) NOT NULL,
        Prioridad VARCHAR(20) NOT NULL,
        Asunto VARCHAR(255) NOT NULL,
        Descripcion TEXT NOT NULL,
        Estado VARCHAR(20) DEFAULT 'Pendiente',
        Respuesta TEXT NULL,
        FechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        FechaRespuesta DATETIME NULL,
        INDEX idx_usuario (UsuarioID),
        INDEX idx_estado (Estado),
        INDEX idx_prioridad (Prioridad)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return mysqli_query($conn, $createTable);
}

