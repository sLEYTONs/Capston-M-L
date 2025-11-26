<?php
require_once __DIR__ . '/../../../config/conexion.php';

/**
 * Crea una nueva solicitud al jefe de taller
 * @param array $datos
 * @return array
 */
function crearSolicitudJefe($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Verificar y crear tabla si no existe
    verificarTablaSolicitudesJefe($conn);

    // Obtener usuario de la sesión
    session_start();
    $usuarioId = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0;

    // Escapar datos
    $tipoSolicitud = mysqli_real_escape_string($conn, $datos['tipo_solicitud']);
    $prioridad = mysqli_real_escape_string($conn, $datos['prioridad']);
    $asunto = mysqli_real_escape_string($conn, $datos['asunto']);
    $descripcion = mysqli_real_escape_string($conn, $datos['descripcion']);
    $archivos = isset($datos['archivos']) ? json_encode($datos['archivos']) : NULL;

    // Insertar solicitud
    $query = "INSERT INTO solicitudes_jefe_taller 
              (UsuarioID, TipoSolicitud, Prioridad, Asunto, Descripcion, Archivos, Estado, FechaCreacion) 
              VALUES ($usuarioId, '$tipoSolicitud', '$prioridad', '$asunto', '$descripcion', " . 
              ($archivos ? "'$archivos'" : "NULL") . ", 'Pendiente', NOW())";

    if (mysqli_query($conn, $query)) {
        $solicitudId = mysqli_insert_id($conn);
        mysqli_close($conn);
        return ['status' => 'success', 'message' => 'Solicitud creada correctamente', 'id' => $solicitudId];
    } else {
        $error = mysqli_error($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al crear solicitud: ' . $error];
    }
}

/**
 * Obtiene todas las solicitudes pendientes
 * @return array
 */
function obtenerSolicitudesPendientes() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Verificar si existe la tabla
    $checkTable = "SHOW TABLES LIKE 'solicitudes_jefe_taller'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    if (!$tablaExiste) {
        mysqli_close($conn);
        return ['status' => 'success', 'data' => []];
    }

    // Verificar nombre de tabla usuarios
    $checkUsuarios = "SHOW TABLES LIKE 'usuarios'";
    $resultUsuarios = mysqli_query($conn, $checkUsuarios);
    $tablaUsuarios = ($resultUsuarios && mysqli_num_rows($resultUsuarios) > 0) ? 'usuarios' : 'USUARIOS';
    if ($resultUsuarios) {
        mysqli_free_result($resultUsuarios);
    }

    $query = "SELECT s.ID, s.TipoSolicitud, s.Prioridad, s.Asunto, s.Descripcion, 
                     s.Estado, s.FechaCreacion, u.NombreUsuario as UsuarioNombre
              FROM solicitudes_jefe_taller s
              LEFT JOIN $tablaUsuarios u ON s.UsuarioID = u.UsuarioID
              WHERE s.Estado = 'Pendiente'
              ORDER BY 
                CASE s.Prioridad
                    WHEN 'urgente' THEN 1
                    WHEN 'alta' THEN 2
                    WHEN 'media' THEN 3
                    WHEN 'baja' THEN 4
                END,
                s.FechaCreacion DESC";

    $result = mysqli_query($conn, $query);
    $solicitudes = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $solicitudes[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $solicitudes];
}

/**
 * Obtiene el historial de comunicaciones
 * @return array
 */
function obtenerComunicacionesJefe() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Verificar si existe la tabla
    $checkTable = "SHOW TABLES LIKE 'solicitudes_jefe_taller'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    if (!$tablaExiste) {
        mysqli_close($conn);
        return ['status' => 'success', 'data' => []];
    }

    // Verificar nombre de tabla usuarios
    $checkUsuarios = "SHOW TABLES LIKE 'usuarios'";
    $resultUsuarios = mysqli_query($conn, $checkUsuarios);
    $tablaUsuarios = ($resultUsuarios && mysqli_num_rows($resultUsuarios) > 0) ? 'usuarios' : 'USUARIOS';
    if ($resultUsuarios) {
        mysqli_free_result($resultUsuarios);
    }

    $query = "SELECT s.ID, s.TipoSolicitud, s.Prioridad, s.Asunto, s.Descripcion, 
                     s.Estado, s.FechaCreacion, s.FechaRespuesta, s.Respuesta,
                     u.NombreUsuario as UsuarioNombre
              FROM solicitudes_jefe_taller s
              LEFT JOIN $tablaUsuarios u ON s.UsuarioID = u.UsuarioID
              ORDER BY s.FechaCreacion DESC
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
 * Verifica y crea la tabla de solicitudes al jefe si no existe
 * @param mysqli $conn
 * @return bool
 */
function verificarTablaSolicitudesJefe($conn) {
    $checkTable = "SHOW TABLES LIKE 'solicitudes_jefe_taller'";
    $result = mysqli_query($conn, $checkTable);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return true;
    }

    // Crear tabla
    $createTable = "CREATE TABLE IF NOT EXISTS solicitudes_jefe_taller (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        UsuarioID INT NOT NULL,
        TipoSolicitud VARCHAR(50) NOT NULL,
        Prioridad VARCHAR(20) NOT NULL,
        Asunto VARCHAR(255) NOT NULL,
        Descripcion TEXT NOT NULL,
        Archivos TEXT NULL,
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

/**
 * Obtiene estadísticas para el jefe de taller
 * @return array
 */
function obtenerEstadisticasJefe() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    $estadisticas = [
        'total_repuestos' => 0,
        'stock_bajo' => 0,
        'solicitudes_pendientes' => 0,
        'entregas_mes' => 0
    ];

    // Total de repuestos
    $checkRepuestos = "SHOW TABLES LIKE 'repuestos'";
    $resultCheck = mysqli_query($conn, $checkRepuestos);
    if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
        $query = "SELECT COUNT(*) as total FROM repuestos WHERE Estado = 'Activo'";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['total_repuestos'] = $row['total'];
            mysqli_free_result($result);
        }

        // Stock bajo
        $query = "SELECT COUNT(*) as total FROM repuestos WHERE Estado = 'Activo' AND Stock <= StockMinimo";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['stock_bajo'] = $row['total'];
            mysqli_free_result($result);
        }
    }

    // Solicitudes pendientes
    $checkSolicitudes = "SHOW TABLES LIKE 'solicitudes_jefe_taller'";
    $resultCheck = mysqli_query($conn, $checkSolicitudes);
    if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
        $query = "SELECT COUNT(*) as total FROM solicitudes_jefe_taller WHERE Estado = 'Pendiente'";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['solicitudes_pendientes'] = $row['total'];
            mysqli_free_result($result);
        }
    }

    // Entregas del mes
    $checkEntregas = "SHOW TABLES LIKE 'entregas_repuestos'";
    $resultCheck = mysqli_query($conn, $checkEntregas);
    if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
        $query = "SELECT COUNT(*) as total FROM entregas_repuestos 
                  WHERE MONTH(FechaEntrega) = MONTH(CURRENT_DATE()) 
                  AND YEAR(FechaEntrega) = YEAR(CURRENT_DATE())";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['entregas_mes'] = $row['total'];
            mysqli_free_result($result);
        }
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $estadisticas];
}

