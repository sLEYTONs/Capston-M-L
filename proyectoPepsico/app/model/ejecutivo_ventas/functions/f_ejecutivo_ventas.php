<?php
require_once __DIR__ . '/../../../config/conexion.php';

/**
 * Obtiene los vehículos asignados al ejecutivo de ventas actual
 */
function obtenerVehiculosAsignados($usuario_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Verificar y crear tabla de asignaciones de vehículos si no existe
    verificarTablaAsignacionesVehiculos($conn);

    $usuario_id = intval($usuario_id);
    
    // Obtener vehículos asignados directamente al usuario
    $query = "SELECT 
                v.ID,
                v.Placa,
                v.Marca,
                v.Modelo,
                v.TipoVehiculo,
                v.Anio,
                v.ConductorNombre,
                v.Estado,
                v.FechaIngreso,
                v.Kilometraje,
                av.FechaAsignacion,
                av.FechaDevolucion,
                av.ObservacionesAsignacion,
                av.KilometrajeInicial,
                av.KilometrajeFinal
              FROM ingreso_vehiculos v
              INNER JOIN asignaciones_vehiculos av ON v.ID = av.VehiculoID
              WHERE av.UsuarioID = $usuario_id 
                AND av.FechaDevolucion IS NULL
                AND v.Estado = 'Asignado'
              ORDER BY av.FechaAsignacion DESC";

    $result = mysqli_query($conn, $query);
    $vehiculos = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $vehiculos[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $vehiculos];
}

/**
 * Obtiene el historial de recepciones y devoluciones de vehículos
 */
function obtenerHistorialRecepcionDevolucion($usuario_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    verificarTablaAsignacionesVehiculos($conn);

    $usuario_id = intval($usuario_id);
    
    $query = "SELECT 
                av.ID,
                av.VehiculoID,
                v.Placa,
                v.Marca,
                v.Modelo,
                v.TipoVehiculo,
                av.TipoOperacion,
                av.FechaAsignacion,
                av.FechaDevolucion,
                av.KilometrajeInicial,
                av.KilometrajeFinal,
                av.ObservacionesAsignacion,
                av.EstadoVehiculo,
                av.FotosRecepcion,
                av.FotosDevolucion,
                u.NombreUsuario as UsuarioNombre
              FROM asignaciones_vehiculos av
              INNER JOIN ingreso_vehiculos v ON av.VehiculoID = v.ID
              LEFT JOIN usuarios u ON av.UsuarioID = u.UsuarioID
              WHERE av.UsuarioID = $usuario_id
              ORDER BY av.FechaAsignacion DESC
              LIMIT 100";

    $result = mysqli_query($conn, $query);
    $historial = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $historial[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $historial];
}

/**
 * Registra la recepción de un vehículo prestado
 */
function registrarRecepcionVehiculo($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $usuario_id = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0;

    verificarTablaAsignacionesVehiculos($conn);

    $vehiculo_id = intval($datos['vehiculo_id']);
    $kilometraje_inicial = floatval($datos['kilometraje_inicial'] ?? 0);
    $observaciones = mysqli_real_escape_string($conn, $datos['observaciones'] ?? '');
    $estado_vehiculo = mysqli_real_escape_string($conn, $datos['estado_vehiculo'] ?? 'Bueno');
    $fotos_recepcion = isset($datos['fotos_recepcion']) ? json_encode($datos['fotos_recepcion']) : null;
    $fotos_recepcion_escaped = $fotos_recepcion ? mysqli_real_escape_string($conn, $fotos_recepcion) : 'NULL';

    // Verificar que el vehículo existe y está disponible
    $queryVerificar = "SELECT ID, Estado, Placa FROM ingreso_vehiculos WHERE ID = $vehiculo_id";
    $resultVerificar = mysqli_query($conn, $queryVerificar);
    
    if (!$resultVerificar || mysqli_num_rows($resultVerificar) == 0) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Vehículo no encontrado'];
    }

    $vehiculo = mysqli_fetch_assoc($resultVerificar);

    // Verificar que no esté ya asignado a otro usuario
    $queryAsignacion = "SELECT ID FROM asignaciones_vehiculos 
                        WHERE VehiculoID = $vehiculo_id 
                        AND FechaDevolucion IS NULL";
    $resultAsignacion = mysqli_query($conn, $queryAsignacion);
    
    if ($resultAsignacion && mysqli_num_rows($resultAsignacion) > 0) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'El vehículo ya está asignado a otro usuario'];
    }

    mysqli_begin_transaction($conn);

    try {
        // Insertar asignación
        $queryInsert = "INSERT INTO asignaciones_vehiculos 
                        (VehiculoID, UsuarioID, TipoOperacion, FechaAsignacion, 
                         KilometrajeInicial, ObservacionesAsignacion, EstadoVehiculo, FotosRecepcion)
                        VALUES ($vehiculo_id, $usuario_id, 'Recepcion', NOW(), 
                                $kilometraje_inicial, '$observaciones', '$estado_vehiculo', 
                                " . ($fotos_recepcion ? "'$fotos_recepcion_escaped'" : "NULL") . ")";

        if (!mysqli_query($conn, $queryInsert)) {
            throw new Exception('Error al registrar recepción: ' . mysqli_error($conn));
        }

        // Actualizar estado del vehículo
        $queryUpdate = "UPDATE ingreso_vehiculos 
                        SET Estado = 'Asignado', 
                            Kilometraje = $kilometraje_inicial
                        WHERE ID = $vehiculo_id";

        if (!mysqli_query($conn, $queryUpdate)) {
            throw new Exception('Error al actualizar estado del vehículo: ' . mysqli_error($conn));
        }

        mysqli_commit($conn);
        mysqli_close($conn);
        
        return ['status' => 'success', 'message' => 'Recepción de vehículo registrada correctamente'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Registra la devolución de un vehículo prestado
 */
function registrarDevolucionVehiculo($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $usuario_id = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0;

    verificarTablaAsignacionesVehiculos($conn);

    $asignacion_id = intval($datos['asignacion_id']);
    $kilometraje_final = floatval($datos['kilometraje_final'] ?? 0);
    $observaciones = mysqli_real_escape_string($conn, $datos['observaciones'] ?? '');
    $estado_vehiculo = mysqli_real_escape_string($conn, $datos['estado_vehiculo'] ?? 'Bueno');
    $fotos_devolucion = isset($datos['fotos_devolucion']) ? json_encode($datos['fotos_devolucion']) : null;
    $fotos_devolucion_escaped = $fotos_devolucion ? mysqli_real_escape_string($conn, $fotos_devolucion) : 'NULL';

    // Verificar que la asignación existe y pertenece al usuario
    $queryVerificar = "SELECT av.*, v.Placa 
                       FROM asignaciones_vehiculos av
                       INNER JOIN ingreso_vehiculos v ON av.VehiculoID = v.ID
                       WHERE av.ID = $asignacion_id 
                         AND av.UsuarioID = $usuario_id
                         AND av.FechaDevolucion IS NULL";
    $resultVerificar = mysqli_query($conn, $queryVerificar);
    
    if (!$resultVerificar || mysqli_num_rows($resultVerificar) == 0) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Asignación no encontrada o ya devuelta'];
    }

    $asignacion = mysqli_fetch_assoc($resultVerificar);
    $vehiculo_id = $asignacion['VehiculoID'];

    mysqli_begin_transaction($conn);

    try {
        // Actualizar asignación con fecha de devolución
        $queryUpdate = "UPDATE asignaciones_vehiculos 
                        SET FechaDevolucion = NOW(),
                            KilometrajeFinal = $kilometraje_final,
                            EstadoVehiculo = '$estado_vehiculo',
                            FotosDevolucion = " . ($fotos_devolucion ? "'$fotos_devolucion_escaped'" : "NULL") . "
                        WHERE ID = $asignacion_id";

        if (!mysqli_query($conn, $queryUpdate)) {
            throw new Exception('Error al registrar devolución: ' . mysqli_error($conn));
        }

        // Actualizar estado del vehículo a 'Disponible' o 'Ingresado'
        $queryUpdateVehiculo = "UPDATE ingreso_vehiculos 
                                SET Estado = 'Disponible', 
                                    Kilometraje = $kilometraje_final
                                WHERE ID = $vehiculo_id";

        if (!mysqli_query($conn, $queryUpdateVehiculo)) {
            throw new Exception('Error al actualizar estado del vehículo: ' . mysqli_error($conn));
        }

        mysqli_commit($conn);
        mysqli_close($conn);
        
        return ['status' => 'success', 'message' => 'Devolución de vehículo registrada correctamente'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Obtiene vehículos disponibles para préstamo
 */
function obtenerVehiculosDisponibles() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    $query = "SELECT ID, Placa, Marca, Modelo, TipoVehiculo, Anio, Estado, Kilometraje
              FROM ingreso_vehiculos
              WHERE Estado IN ('Disponible', 'Ingresado')
              ORDER BY Placa ASC";

    $result = mysqli_query($conn, $query);
    $vehiculos = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $vehiculos[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $vehiculos];
}

/**
 * Verifica y crea la tabla de asignaciones de vehículos si no existe
 */
function verificarTablaAsignacionesVehiculos($conn) {
    $checkTable = "SHOW TABLES LIKE 'asignaciones_vehiculos'";
    $result = mysqli_query($conn, $checkTable);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return true;
    }

    // Crear tabla sin foreign keys inicialmente
    $createTable = "CREATE TABLE IF NOT EXISTS asignaciones_vehiculos (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        VehiculoID INT NOT NULL,
        UsuarioID INT NOT NULL,
        TipoOperacion ENUM('Recepcion', 'Devolucion') DEFAULT 'Recepcion',
        FechaAsignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        FechaDevolucion DATETIME NULL,
        KilometrajeInicial DECIMAL(10,2) DEFAULT 0,
        KilometrajeFinal DECIMAL(10,2) NULL,
        ObservacionesAsignacion TEXT NULL,
        EstadoVehiculo VARCHAR(50) DEFAULT 'Bueno',
        FotosRecepcion TEXT NULL,
        FotosDevolucion TEXT NULL,
        INDEX idx_vehiculo (VehiculoID),
        INDEX idx_usuario (UsuarioID),
        INDEX idx_fecha_asignacion (FechaAsignacion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!mysqli_query($conn, $createTable)) {
        error_log("Error al crear tabla asignaciones_vehiculos: " . mysqli_error($conn));
        return false;
    }

    // Verificar y agregar foreign keys después de crear la tabla
    // Verificar existencia de tabla ingreso_vehiculos
    $checkTablaVehiculos = "SHOW TABLES LIKE 'ingreso_vehiculos'";
    $resultVehiculos = mysqli_query($conn, $checkTablaVehiculos);
    $tablaVehiculosExiste = ($resultVehiculos && mysqli_num_rows($resultVehiculos) > 0);

    // Verificar existencia de tabla usuarios
    $checkTablaUsuarios = "SHOW TABLES LIKE 'usuarios'";
    $resultUsuarios = mysqli_query($conn, $checkTablaUsuarios);
    $tablaUsuariosExiste = ($resultUsuarios && mysqli_num_rows($resultUsuarios) > 0);

    // Verificar existencia de columna ID en ingreso_vehiculos
    $columnaVehiculoExiste = false;
    if ($tablaVehiculosExiste) {
        $checkColumnaVehiculo = "SELECT COUNT(*) as existe 
                                  FROM INFORMATION_SCHEMA.COLUMNS 
                                  WHERE TABLE_SCHEMA = DATABASE() 
                                    AND TABLE_NAME = 'ingreso_vehiculos' 
                                    AND COLUMN_NAME = 'ID'";
        $resultColumnaVehiculo = mysqli_query($conn, $checkColumnaVehiculo);
        if ($resultColumnaVehiculo) {
            $row = mysqli_fetch_assoc($resultColumnaVehiculo);
            $columnaVehiculoExiste = ($row['existe'] > 0);
        }
    }

    // Verificar existencia de columna UsuarioID en usuarios
    $columnaUsuarioExiste = false;
    if ($tablaUsuariosExiste) {
        $checkColumnaUsuario = "SELECT COUNT(*) as existe 
                                FROM INFORMATION_SCHEMA.COLUMNS 
                                WHERE TABLE_SCHEMA = DATABASE() 
                                  AND TABLE_NAME = 'usuarios' 
                                  AND COLUMN_NAME = 'UsuarioID'";
        $resultColumnaUsuario = mysqli_query($conn, $checkColumnaUsuario);
        if ($resultColumnaUsuario) {
            $row = mysqli_fetch_assoc($resultColumnaUsuario);
            $columnaUsuarioExiste = ($row['existe'] > 0);
        }
    }

    // Verificar si ya existe la foreign key fk_asignaciones_vehiculo
    $fkVehiculoExiste = false;
    $checkFKVehiculo = "SELECT COUNT(*) as existe 
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'asignaciones_vehiculos' 
                          AND CONSTRAINT_NAME = 'fk_asignaciones_vehiculo'";
    $resultFKVehiculo = mysqli_query($conn, $checkFKVehiculo);
    if ($resultFKVehiculo) {
        $row = mysqli_fetch_assoc($resultFKVehiculo);
        $fkVehiculoExiste = ($row['existe'] > 0);
    }

    // Verificar si ya existe la foreign key fk_asignaciones_usuario
    $fkUsuarioExiste = false;
    $checkFKUsuario = "SELECT COUNT(*) as existe 
                       FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                       WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = 'asignaciones_vehiculos' 
                         AND CONSTRAINT_NAME = 'fk_asignaciones_usuario'";
    $resultFKUsuario = mysqli_query($conn, $checkFKUsuario);
    if ($resultFKUsuario) {
        $row = mysqli_fetch_assoc($resultFKUsuario);
        $fkUsuarioExiste = ($row['existe'] > 0);
    }

    // Agregar foreign key para VehiculoID si es posible
    if (!$fkVehiculoExiste && $tablaVehiculosExiste && $columnaVehiculoExiste) {
        $addFKVehiculo = "ALTER TABLE asignaciones_vehiculos 
                         ADD CONSTRAINT fk_asignaciones_vehiculo 
                         FOREIGN KEY (VehiculoID) 
                         REFERENCES ingreso_vehiculos(ID) 
                         ON DELETE RESTRICT 
                         ON UPDATE CASCADE";
        if (!mysqli_query($conn, $addFKVehiculo)) {
            error_log("Advertencia: No se pudo agregar foreign key fk_asignaciones_vehiculo: " . mysqli_error($conn));
        }
    }

    // Agregar foreign key para UsuarioID si es posible
    if (!$fkUsuarioExiste && $tablaUsuariosExiste && $columnaUsuarioExiste) {
        $addFKUsuario = "ALTER TABLE asignaciones_vehiculos 
                        ADD CONSTRAINT fk_asignaciones_usuario 
                        FOREIGN KEY (UsuarioID) 
                        REFERENCES usuarios(UsuarioID) 
                        ON DELETE RESTRICT 
                        ON UPDATE CASCADE";
        if (!mysqli_query($conn, $addFKUsuario)) {
            error_log("Advertencia: No se pudo agregar foreign key fk_asignaciones_usuario: " . mysqli_error($conn));
        }
    }

    return true;
}

/**
 * Reporta una falla del vehículo al taller
 */
function reportarFallaVehiculo($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $usuario_id = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0;

    verificarTablaReportesFallas($conn);

    $vehiculo_id = intval($datos['vehiculo_id']);
    $tipo_falla = mysqli_real_escape_string($conn, $datos['tipo_falla'] ?? '');
    $descripcion = mysqli_real_escape_string($conn, $datos['descripcion'] ?? '');
    $prioridad = mysqli_real_escape_string($conn, $datos['prioridad'] ?? 'Media');
    $kilometraje = floatval($datos['kilometraje'] ?? 0);
    $fotos = isset($datos['fotos']) ? json_encode($datos['fotos']) : null;
    $fotos_escaped = $fotos ? mysqli_real_escape_string($conn, $fotos) : 'NULL';

    // Verificar que el vehículo existe
    $queryVerificar = "SELECT ID, Placa, Estado FROM ingreso_vehiculos WHERE ID = $vehiculo_id";
    $resultVerificar = mysqli_query($conn, $queryVerificar);
    
    if (!$resultVerificar || mysqli_num_rows($resultVerificar) == 0) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Vehículo no encontrado'];
    }

    $vehiculo = mysqli_fetch_assoc($resultVerificar);

    // Insertar reporte de falla
    $queryInsert = "INSERT INTO reportes_fallas_vehiculos 
                    (VehiculoID, UsuarioID, TipoFalla, Descripcion, Prioridad, 
                     Kilometraje, Fotos, Estado, FechaReporte)
                    VALUES ($vehiculo_id, $usuario_id, '$tipo_falla', '$descripcion', '$prioridad',
                            $kilometraje, " . ($fotos ? "'$fotos_escaped'" : "NULL") . ", 
                            'Pendiente', NOW())";

    if (mysqli_query($conn, $queryInsert)) {
        $reporte_id = mysqli_insert_id($conn);
        
        // Actualizar estado del vehículo si es necesario
        if ($prioridad === 'Urgente' || $prioridad === 'Alta') {
            mysqli_query($conn, "UPDATE ingreso_vehiculos SET Estado = 'En Reparación' WHERE ID = $vehiculo_id");
        }
        
        mysqli_close($conn);
        return ['status' => 'success', 'message' => 'Falla reportada correctamente', 'id' => $reporte_id];
    } else {
        $error = mysqli_error($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al reportar falla: ' . $error];
    }
}

/**
 * Obtiene los reportes de fallas del usuario
 */
function obtenerReportesFallas($usuario_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    verificarTablaReportesFallas($conn);

    $usuario_id = intval($usuario_id);
    
    $query = "SELECT 
                rf.ID,
                rf.VehiculoID,
                v.Placa,
                v.Marca,
                v.Modelo,
                rf.TipoFalla,
                rf.Descripcion,
                rf.Prioridad,
                rf.Kilometraje,
                rf.Estado,
                rf.FechaReporte,
                rf.FechaResolucion,
                rf.RespuestaTaller,
                rf.Fotos
              FROM reportes_fallas_vehiculos rf
              INNER JOIN ingreso_vehiculos v ON rf.VehiculoID = v.ID
              WHERE rf.UsuarioID = $usuario_id
              ORDER BY rf.FechaReporte DESC
              LIMIT 100";

    $result = mysqli_query($conn, $query);
    $reportes = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $reportes[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $reportes];
}

/**
 * Obtiene los detalles de un reporte de falla
 */
function obtenerDetallesReporteFalla($reporte_id, $usuario_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    $reporte_id = intval($reporte_id);
    $usuario_id = intval($usuario_id);
    
    $query = "SELECT 
                rf.*,
                v.Placa,
                v.Marca,
                v.Modelo,
                v.TipoVehiculo,
                u.NombreUsuario as UsuarioNombre
              FROM reportes_fallas_vehiculos rf
              INNER JOIN ingreso_vehiculos v ON rf.VehiculoID = v.ID
              LEFT JOIN usuarios u ON rf.UsuarioID = u.UsuarioID
              WHERE rf.ID = $reporte_id AND rf.UsuarioID = $usuario_id
              LIMIT 1";

    $result = mysqli_query($conn, $query);
    $reporte = null;

    if ($result && mysqli_num_rows($result) > 0) {
        $reporte = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    
    if ($reporte) {
        return ['status' => 'success', 'data' => $reporte];
    } else {
        return ['status' => 'error', 'message' => 'Reporte no encontrado'];
    }
}

/**
 * Verifica y crea la tabla de reportes de fallas si no existe
 */
function verificarTablaReportesFallas($conn) {
    $checkTable = "SHOW TABLES LIKE 'reportes_fallas_vehiculos'";
    $result = mysqli_query($conn, $checkTable);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return true;
    }

    // Crear tabla sin foreign keys inicialmente
    $createTable = "CREATE TABLE IF NOT EXISTS reportes_fallas_vehiculos (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        VehiculoID INT NOT NULL,
        UsuarioID INT NOT NULL,
        TipoFalla VARCHAR(100) NOT NULL,
        Descripcion TEXT NOT NULL,
        Prioridad ENUM('Baja', 'Media', 'Alta', 'Urgente') DEFAULT 'Media',
        Kilometraje DECIMAL(10,2) DEFAULT 0,
        Fotos TEXT NULL,
        Estado ENUM('Pendiente', 'En Revisión', 'En Reparación', 'Resuelto', 'Cancelado') DEFAULT 'Pendiente',
        FechaReporte DATETIME DEFAULT CURRENT_TIMESTAMP,
        FechaResolucion DATETIME NULL,
        RespuestaTaller TEXT NULL,
        INDEX idx_vehiculo (VehiculoID),
        INDEX idx_usuario (UsuarioID),
        INDEX idx_estado (Estado),
        INDEX idx_fecha_reporte (FechaReporte)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!mysqli_query($conn, $createTable)) {
        error_log("Error al crear tabla reportes_fallas_vehiculos: " . mysqli_error($conn));
        return false;
    }

    // Verificar y agregar foreign keys después de crear la tabla
    // Verificar existencia de tabla ingreso_vehiculos
    $checkTablaVehiculos = "SHOW TABLES LIKE 'ingreso_vehiculos'";
    $resultVehiculos = mysqli_query($conn, $checkTablaVehiculos);
    $tablaVehiculosExiste = ($resultVehiculos && mysqli_num_rows($resultVehiculos) > 0);

    // Verificar existencia de tabla usuarios
    $checkTablaUsuarios = "SHOW TABLES LIKE 'usuarios'";
    $resultUsuarios = mysqli_query($conn, $checkTablaUsuarios);
    $tablaUsuariosExiste = ($resultUsuarios && mysqli_num_rows($resultUsuarios) > 0);

    // Verificar existencia de columna ID en ingreso_vehiculos
    $columnaVehiculoExiste = false;
    if ($tablaVehiculosExiste) {
        $checkColumnaVehiculo = "SELECT COUNT(*) as existe 
                                  FROM INFORMATION_SCHEMA.COLUMNS 
                                  WHERE TABLE_SCHEMA = DATABASE() 
                                    AND TABLE_NAME = 'ingreso_vehiculos' 
                                    AND COLUMN_NAME = 'ID'";
        $resultColumnaVehiculo = mysqli_query($conn, $checkColumnaVehiculo);
        if ($resultColumnaVehiculo) {
            $row = mysqli_fetch_assoc($resultColumnaVehiculo);
            $columnaVehiculoExiste = ($row['existe'] > 0);
        }
    }

    // Verificar existencia de columna UsuarioID en usuarios
    $columnaUsuarioExiste = false;
    if ($tablaUsuariosExiste) {
        $checkColumnaUsuario = "SELECT COUNT(*) as existe 
                                FROM INFORMATION_SCHEMA.COLUMNS 
                                WHERE TABLE_SCHEMA = DATABASE() 
                                  AND TABLE_NAME = 'usuarios' 
                                  AND COLUMN_NAME = 'UsuarioID'";
        $resultColumnaUsuario = mysqli_query($conn, $checkColumnaUsuario);
        if ($resultColumnaUsuario) {
            $row = mysqli_fetch_assoc($resultColumnaUsuario);
            $columnaUsuarioExiste = ($row['existe'] > 0);
        }
    }

    // Verificar si ya existe la foreign key fk_reportes_fallas_vehiculo
    $fkVehiculoExiste = false;
    $checkFKVehiculo = "SELECT COUNT(*) as existe 
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'reportes_fallas_vehiculos' 
                          AND CONSTRAINT_NAME = 'fk_reportes_fallas_vehiculo'";
    $resultFKVehiculo = mysqli_query($conn, $checkFKVehiculo);
    if ($resultFKVehiculo) {
        $row = mysqli_fetch_assoc($resultFKVehiculo);
        $fkVehiculoExiste = ($row['existe'] > 0);
    }

    // Verificar si ya existe la foreign key fk_reportes_fallas_usuario
    $fkUsuarioExiste = false;
    $checkFKUsuario = "SELECT COUNT(*) as existe 
                       FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                       WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = 'reportes_fallas_vehiculos' 
                         AND CONSTRAINT_NAME = 'fk_reportes_fallas_usuario'";
    $resultFKUsuario = mysqli_query($conn, $checkFKUsuario);
    if ($resultFKUsuario) {
        $row = mysqli_fetch_assoc($resultFKUsuario);
        $fkUsuarioExiste = ($row['existe'] > 0);
    }

    // Agregar foreign key para VehiculoID si es posible
    if (!$fkVehiculoExiste && $tablaVehiculosExiste && $columnaVehiculoExiste) {
        $addFKVehiculo = "ALTER TABLE reportes_fallas_vehiculos 
                         ADD CONSTRAINT fk_reportes_fallas_vehiculo 
                         FOREIGN KEY (VehiculoID) 
                         REFERENCES ingreso_vehiculos(ID) 
                         ON DELETE RESTRICT 
                         ON UPDATE CASCADE";
        if (!mysqli_query($conn, $addFKVehiculo)) {
            error_log("Advertencia: No se pudo agregar foreign key fk_reportes_fallas_vehiculo: " . mysqli_error($conn));
        }
    }

    // Agregar foreign key para UsuarioID si es posible
    if (!$fkUsuarioExiste && $tablaUsuariosExiste && $columnaUsuarioExiste) {
        $addFKUsuario = "ALTER TABLE reportes_fallas_vehiculos 
                        ADD CONSTRAINT fk_reportes_fallas_usuario 
                        FOREIGN KEY (UsuarioID) 
                        REFERENCES usuarios(UsuarioID) 
                        ON DELETE RESTRICT 
                        ON UPDATE CASCADE";
        if (!mysqli_query($conn, $addFKUsuario)) {
            error_log("Advertencia: No se pudo agregar foreign key fk_reportes_fallas_usuario: " . mysqli_error($conn));
        }
    }

    return true;
}

