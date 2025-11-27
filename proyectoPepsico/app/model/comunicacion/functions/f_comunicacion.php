<?php
require_once __DIR__ . '../../../../config/conexion.php';

/**
 * Crea una nueva comunicación con flota
 */
function crearComunicacionFlota($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        // Verificar si existe la tabla
        $checkTable = "SHOW TABLES LIKE 'comunicaciones_flota'";
        $resultCheck = mysqli_query($conn, $checkTable);
        $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

        if (!$tablaExiste) {
            $createTable = "CREATE TABLE IF NOT EXISTS `comunicaciones_flota` (
                `ID` INT(11) NOT NULL AUTO_INCREMENT,
                `Placa` VARCHAR(10) NOT NULL,
                `ConductorNombre` VARCHAR(255) DEFAULT NULL,
                `Tipo` ENUM('Solicitud', 'Notificación', 'Consulta', 'Urgente') NOT NULL,
                `Asunto` VARCHAR(255) NOT NULL,
                `Mensaje` TEXT NOT NULL,
                `Estado` ENUM('Pendiente', 'En Proceso', 'Respondida', 'Cerrada') DEFAULT 'Pendiente',
                `UsuarioCreacionID` INT(11) NOT NULL,
                `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`ID`),
                KEY `Placa` (`Placa`),
                KEY `Estado` (`Estado`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if (!mysqli_query($conn, $createTable)) {
                throw new Exception('Error al crear tabla: ' . mysqli_error($conn));
            }
        }

        // Obtener conductor del vehículo si existe
        $conductorNombre = null;
        
        // Si es una respuesta, obtener el conductor de la comunicación padre
        if (!empty($datos['comunicacion_padre_id'])) {
            $padreID = intval($datos['comunicacion_padre_id']);
            $queryPadre = "SELECT ConductorNombre FROM comunicaciones_flota WHERE ID = $padreID LIMIT 1";
            $resultPadre = mysqli_query($conn, $queryPadre);
            if ($resultPadre && $row = mysqli_fetch_assoc($resultPadre)) {
                $conductorNombre = $row['ConductorNombre'];
            }
        }
        
        // Si no es respuesta o no se encontró conductor en la comunicación padre, buscar en ingreso_vehiculos
        if (!$conductorNombre && !empty($datos['placa'])) {
            $placa = mysqli_real_escape_string($conn, $datos['placa']);
            $queryConductor = "SELECT ConductorNombre FROM ingreso_vehiculos WHERE Placa = '$placa' ORDER BY FechaIngreso DESC LIMIT 1";
            $resultConductor = mysqli_query($conn, $queryConductor);
            if ($resultConductor && $row = mysqli_fetch_assoc($resultConductor)) {
                $conductorNombre = $row['ConductorNombre'];
            }
        }

        // Verificar si existe la columna ComunicacionPadreID, si no existe agregarla
        $checkColumn = "SHOW COLUMNS FROM comunicaciones_flota LIKE 'ComunicacionPadreID'";
        $resultCheck = mysqli_query($conn, $checkColumn);
        if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
            $alterTable = "ALTER TABLE comunicaciones_flota ADD COLUMN ComunicacionPadreID INT(11) DEFAULT NULL AFTER UsuarioCreacionID, ADD KEY ComunicacionPadreID (ComunicacionPadreID)";
            mysqli_query($conn, $alterTable);
        }

        $comunicacionPadreID = isset($datos['comunicacion_padre_id']) && !empty($datos['comunicacion_padre_id']) ? intval($datos['comunicacion_padre_id']) : null;
        
        $query = "INSERT INTO comunicaciones_flota 
                 (Placa, ConductorNombre, Tipo, Asunto, Mensaje, UsuarioCreacionID, ComunicacionPadreID)
                 VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . mysqli_error($conn));
        }

        $placa = $datos['placa'];
        $tipo = $datos['tipo'];
        $asunto = $datos['asunto'];
        $mensaje = $datos['mensaje'];
        $usuarioID = $datos['usuario_id'];

        mysqli_stmt_bind_param($stmt, 'sssssii', $placa, $conductorNombre, $tipo, $asunto, $mensaje, $usuarioID, $comunicacionPadreID);
        
        // Si es una respuesta, actualizar el estado de la comunicación padre a "Respondida"
        if ($comunicacionPadreID) {
            $updatePadre = "UPDATE comunicaciones_flota SET Estado = 'Respondida' WHERE ID = ?";
            $stmtUpdate = mysqli_prepare($conn, $updatePadre);
            if ($stmtUpdate) {
                mysqli_stmt_bind_param($stmtUpdate, 'i', $comunicacionPadreID);
                mysqli_stmt_execute($stmtUpdate);
                mysqli_stmt_close($stmtUpdate);
            }
        }

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error ejecutando consulta: ' . mysqli_stmt_error($stmt));
        }

        mysqli_commit($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        return ['status' => 'success', 'message' => 'Comunicación creada correctamente'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_close($conn);
        error_log("Error en crearComunicacionFlota: " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Crea una nueva comunicación con proveedor
 */
function crearComunicacionProveedor($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        // Verificar si existe la tabla
        $checkTable = "SHOW TABLES LIKE 'comunicaciones_proveedores'";
        $resultCheck = mysqli_query($conn, $checkTable);
        $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

        if (!$tablaExiste) {
            $createTable = "CREATE TABLE IF NOT EXISTS `comunicaciones_proveedores` (
                `ID` INT(11) NOT NULL AUTO_INCREMENT,
                `ProveedorNombre` VARCHAR(255) NOT NULL,
                `ContactoNombre` VARCHAR(255) DEFAULT NULL,
                `Email` VARCHAR(255) DEFAULT NULL,
                `Telefono` VARCHAR(50) DEFAULT NULL,
                `Tipo` ENUM('Pedido', 'Consulta', 'Reclamo', 'Cotización') NOT NULL,
                `Asunto` VARCHAR(255) NOT NULL,
                `Mensaje` TEXT NOT NULL,
                `Estado` ENUM('Pendiente', 'En Proceso', 'Respondida', 'Cerrada') DEFAULT 'Pendiente',
                `UsuarioCreacionID` INT(11) NOT NULL,
                `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`ID`),
                KEY `ProveedorNombre` (`ProveedorNombre`),
                KEY `Estado` (`Estado`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if (!mysqli_query($conn, $createTable)) {
                throw new Exception('Error al crear tabla: ' . mysqli_error($conn));
            }
        }

        $query = "INSERT INTO comunicaciones_proveedores 
                 (ProveedorNombre, ContactoNombre, Email, Telefono, Tipo, Asunto, Mensaje, UsuarioCreacionID)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . mysqli_error($conn));
        }

        // Limpiar y normalizar el nombre del proveedor
        $proveedor = trim($datos['proveedor']);
        $contacto = !empty($datos['contacto']) ? trim($datos['contacto']) : null;
        $email = !empty($datos['email']) ? trim($datos['email']) : null;
        $telefono = !empty($datos['telefono']) ? trim($datos['telefono']) : null;
        $tipo = trim($datos['tipo']);
        $asunto = trim($datos['asunto']);
        $mensaje = trim($datos['mensaje']);
        $usuarioID = intval($datos['usuario_id']);

        mysqli_stmt_bind_param($stmt, 'sssssssi', $proveedor, $contacto, $email, $telefono, $tipo, $asunto, $mensaje, $usuarioID);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error ejecutando consulta: ' . mysqli_stmt_error($stmt));
        }

        mysqli_commit($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        return ['status' => 'success', 'message' => 'Comunicación creada correctamente'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_close($conn);
        error_log("Error en crearComunicacionProveedor: " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Obtiene comunicaciones con flota
 */
function obtenerComunicacionesFlota($filtros = []) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión', 'data' => []];
    }

    // Verificar si existe la tabla, si no existe crearla
    $checkTable = "SHOW TABLES LIKE 'comunicaciones_flota'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    if (!$tablaExiste) {
        $createTable = "CREATE TABLE IF NOT EXISTS `comunicaciones_flota` (
            `ID` INT(11) NOT NULL AUTO_INCREMENT,
            `Placa` VARCHAR(10) NOT NULL,
            `ConductorNombre` VARCHAR(255) DEFAULT NULL,
            `Tipo` ENUM('Solicitud', 'Notificación', 'Consulta', 'Urgente') NOT NULL,
            `Asunto` VARCHAR(255) NOT NULL,
            `Mensaje` TEXT NOT NULL,
            `Estado` ENUM('Pendiente', 'En Proceso', 'Respondida', 'Cerrada') DEFAULT 'Pendiente',
            `UsuarioCreacionID` INT(11) NOT NULL,
            `ComunicacionPadreID` INT(11) DEFAULT NULL,
            `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `Placa` (`Placa`),
            KEY `ConductorNombre` (`ConductorNombre`),
            KEY `Estado` (`Estado`),
            KEY `ComunicacionPadreID` (`ComunicacionPadreID`),
            FOREIGN KEY (`ComunicacionPadreID`) REFERENCES `comunicaciones_flota` (`ID`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!mysqli_query($conn, $createTable)) {
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'Error al crear tabla: ' . mysqli_error($conn), 'data' => []];
        }
    }

    $query = "SELECT * FROM comunicaciones_flota WHERE 1=1";
    $params = [];
    $types = '';

    // Si se filtra por usuario_id (para Ejecutivo de Ventas u otros roles específicos)
    if (!empty($filtros['usuario_id'])) {
        $query .= " AND UsuarioCreacionID = ?";
        $params[] = intval($filtros['usuario_id']);
        $types .= 'i';
    }

    // Si se filtra por conductor (para Chofer)
    if (!empty($filtros['conductor'])) {
        $query .= " AND ConductorNombre LIKE ?";
        $params[] = '%' . $filtros['conductor'] . '%';
        $types .= 's';
    }

    if (!empty($filtros['placa'])) {
        $query .= " AND Placa LIKE ?";
        $params[] = '%' . $filtros['placa'] . '%';
        $types .= 's';
    }

    if (!empty($filtros['tipo'])) {
        $query .= " AND Tipo = ?";
        $params[] = $filtros['tipo'];
        $types .= 's';
    }

    if (!empty($filtros['estado'])) {
        $query .= " AND Estado = ?";
        $params[] = $filtros['estado'];
        $types .= 's';
    }

    $query .= " ORDER BY FechaCreacion DESC";

    $comunicaciones = [];

    if (empty($params)) {
        $result = mysqli_query($conn, $query);
    } else {
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'Error preparando consulta', 'data' => []];
        }
    }

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $comunicaciones[] = $row;
        }
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);

    return [
        'status' => 'success',
        'data' => $comunicaciones,
        'total' => count($comunicaciones)
    ];
}

/**
 * Obtiene comunicaciones con proveedores
 */
function obtenerComunicacionesProveedor($filtros = []) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión', 'data' => []];
    }

    // Verificar si existe la tabla, si no existe crearla
    $checkTable = "SHOW TABLES LIKE 'comunicaciones_proveedores'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    if (!$tablaExiste) {
        $createTable = "CREATE TABLE IF NOT EXISTS `comunicaciones_proveedores` (
            `ID` INT(11) NOT NULL AUTO_INCREMENT,
            `ProveedorNombre` VARCHAR(255) NOT NULL,
            `ContactoNombre` VARCHAR(255) DEFAULT NULL,
            `Email` VARCHAR(255) DEFAULT NULL,
            `Telefono` VARCHAR(50) DEFAULT NULL,
            `Tipo` ENUM('Pedido', 'Consulta', 'Reclamo', 'Cotización') NOT NULL,
            `Asunto` VARCHAR(255) NOT NULL,
            `Mensaje` TEXT NOT NULL,
            `Estado` ENUM('Pendiente', 'En Proceso', 'Respondida', 'Cerrada') DEFAULT 'Pendiente',
            `UsuarioCreacionID` INT(11) NOT NULL,
            `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `ProveedorNombre` (`ProveedorNombre`),
            KEY `Estado` (`Estado`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!mysqli_query($conn, $createTable)) {
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'Error al crear tabla: ' . mysqli_error($conn), 'data' => []];
        }
    }

    $query = "SELECT * FROM comunicaciones_proveedores WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($filtros['proveedor'])) {
        // Normalizar el nombre del proveedor para la búsqueda (trim y comparación exacta o LIKE)
        $proveedorFiltro = trim($filtros['proveedor']);
        $query .= " AND TRIM(ProveedorNombre) = ?";
        $params[] = $proveedorFiltro;
        $types .= 's';
    }

    if (!empty($filtros['tipo'])) {
        $query .= " AND Tipo = ?";
        $params[] = $filtros['tipo'];
        $types .= 's';
    }

    if (!empty($filtros['estado'])) {
        $query .= " AND Estado = ?";
        $params[] = $filtros['estado'];
        $types .= 's';
    }

    $query .= " ORDER BY FechaCreacion DESC";

    $comunicaciones = [];

    if (empty($params)) {
        $result = mysqli_query($conn, $query);
    } else {
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'Error preparando consulta', 'data' => []];
        }
    }

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $comunicaciones[] = $row;
        }
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);

    return [
        'status' => 'success',
        'data' => $comunicaciones,
        'total' => count($comunicaciones)
    ];
}

/**
 * Obtiene una comunicación específica
 */
function obtenerComunicacionPorID($id, $tipo) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    $tabla = $tipo === 'flota' ? 'comunicaciones_flota' : 'comunicaciones_proveedores';
    $id = intval($id);

    // Verificar si existe la tabla, si no existe crearla
    $checkTable = "SHOW TABLES LIKE '$tabla'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    if (!$tablaExiste) {
        if ($tipo === 'flota') {
            $createTable = "CREATE TABLE IF NOT EXISTS `comunicaciones_flota` (
                `ID` INT(11) NOT NULL AUTO_INCREMENT,
                `Placa` VARCHAR(10) NOT NULL,
                `ConductorNombre` VARCHAR(255) DEFAULT NULL,
                `Tipo` ENUM('Solicitud', 'Notificación', 'Consulta', 'Urgente') NOT NULL,
                `Asunto` VARCHAR(255) NOT NULL,
                `Mensaje` TEXT NOT NULL,
                `Estado` ENUM('Pendiente', 'En Proceso', 'Respondida', 'Cerrada') DEFAULT 'Pendiente',
                `UsuarioCreacionID` INT(11) NOT NULL,
                `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`ID`),
                KEY `Placa` (`Placa`),
                KEY `Estado` (`Estado`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        } else {
            $createTable = "CREATE TABLE IF NOT EXISTS `comunicaciones_proveedores` (
                `ID` INT(11) NOT NULL AUTO_INCREMENT,
                `ProveedorNombre` VARCHAR(255) NOT NULL,
                `ContactoNombre` VARCHAR(255) DEFAULT NULL,
                `Email` VARCHAR(255) DEFAULT NULL,
                `Telefono` VARCHAR(50) DEFAULT NULL,
                `Tipo` ENUM('Pedido', 'Consulta', 'Reclamo', 'Cotización') NOT NULL,
                `Asunto` VARCHAR(255) NOT NULL,
                `Mensaje` TEXT NOT NULL,
                `Estado` ENUM('Pendiente', 'En Proceso', 'Respondida', 'Cerrada') DEFAULT 'Pendiente',
                `UsuarioCreacionID` INT(11) NOT NULL,
                `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`ID`),
                KEY `ProveedorNombre` (`ProveedorNombre`),
                KEY `Estado` (`Estado`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        
        if (!mysqli_query($conn, $createTable)) {
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'Error al crear tabla: ' . mysqli_error($conn)];
        }
    }

    // Si es una comunicación de flota, verificar y crear la columna ComunicacionPadreID si no existe
    if ($tipo === 'flota') {
        $checkColumn = "SHOW COLUMNS FROM comunicaciones_flota LIKE 'ComunicacionPadreID'";
        $resultCheck = mysqli_query($conn, $checkColumn);
        if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
            $alterTable = "ALTER TABLE comunicaciones_flota ADD COLUMN ComunicacionPadreID INT(11) DEFAULT NULL AFTER UsuarioCreacionID, ADD KEY ComunicacionPadreID (ComunicacionPadreID)";
            if (!mysqli_query($conn, $alterTable)) {
                error_log("Error al agregar columna ComunicacionPadreID: " . mysqli_error($conn));
            }
        }
    }

    $query = "SELECT * FROM $tabla WHERE ID = $id";
    $result = mysqli_query($conn, $query);
    $comunicacion = null;

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $comunicacion = $row;
        
        // Si es una comunicación de flota, obtener respuestas relacionadas
        if ($tipo === 'flota') {
            $queryRespuestas = "SELECT * FROM comunicaciones_flota WHERE ComunicacionPadreID = $id ORDER BY FechaCreacion ASC";
            $resultRespuestas = mysqli_query($conn, $queryRespuestas);
            $respuestas = [];
            if ($resultRespuestas) {
                while ($respuesta = mysqli_fetch_assoc($resultRespuestas)) {
                    $respuestas[] = $respuesta;
                }
                mysqli_free_result($resultRespuestas);
            }
            $comunicacion['respuestas'] = $respuestas;
        }
    }

    if ($result) {
        mysqli_free_result($result);
    }
    mysqli_close($conn);

    if ($comunicacion) {
        return ['status' => 'success', 'data' => $comunicacion];
    } else {
        return ['status' => 'error', 'message' => 'Comunicación no encontrada'];
    }
}

