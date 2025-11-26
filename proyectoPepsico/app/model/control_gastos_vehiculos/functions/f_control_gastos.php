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
 * Obtiene gastos de talleres internos
 */
function obtenerGastosInternos($filtros = []) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    $where = [];
    
    if (!empty($filtros['fecha_desde'])) {
        $fechaDesde = mysqli_real_escape_string($conn, $filtros['fecha_desde']);
        $where[] = "DATE(a.FechaAsignacion) >= '$fechaDesde'";
    }
    
    if (!empty($filtros['fecha_hasta'])) {
        $fechaHasta = mysqli_real_escape_string($conn, $filtros['fecha_hasta']);
        $where[] = "DATE(a.FechaAsignacion) <= '$fechaHasta'";
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Obtener asignaciones con costos de repuestos
    $query = "SELECT 
                a.ID,
                a.FechaAsignacion as Fecha,
                a.Estado,
                v.Placa,
                v.Marca,
                v.Modelo,
                COALESCE(SUM(ra.Cantidad * ra.PrecioUnitario), 0) as CostoRepuestos,
                COUNT(DISTINCT ra.RepuestoID) as CantidadRepuestos,
                0 as CostoManoObra,
                'Mantenimiento' as Servicio
              FROM asignaciones_mecanico a
              INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
              LEFT JOIN repuestos_asignacion ra ON a.ID = ra.AsignacionID
              $whereClause
              GROUP BY a.ID, a.FechaAsignacion, a.Estado, v.Placa, v.Marca, v.Modelo
              ORDER BY a.FechaAsignacion DESC
              LIMIT 100";

    $result = mysqli_query($conn, $query);
    $gastos = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $gastos[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $gastos];
}

/**
 * Obtiene gastos de talleres externos
 */
function obtenerGastosExternos($filtros = []) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Verificar y crear tabla si no existe
    verificarTablaGastosExternos($conn);

    $where = [];
    
    if (!empty($filtros['fecha_desde'])) {
        $fechaDesde = mysqli_real_escape_string($conn, $filtros['fecha_desde']);
        $where[] = "DATE(ge.Fecha) >= '$fechaDesde'";
    }
    
    if (!empty($filtros['fecha_hasta'])) {
        $fechaHasta = mysqli_real_escape_string($conn, $filtros['fecha_hasta']);
        $where[] = "DATE(ge.Fecha) <= '$fechaHasta'";
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $query = "SELECT 
                ge.ID,
                ge.Fecha,
                ge.TallerNombre,
                ge.Servicio,
                ge.CostoTotal,
                ge.Estado,
                ge.Observaciones,
                v.Placa,
                v.Marca,
                v.Modelo
              FROM gastos_externos ge
              INNER JOIN ingreso_vehiculos v ON ge.VehiculoID = v.ID
              $whereClause
              ORDER BY ge.Fecha DESC
              LIMIT 100";

    $result = mysqli_query($conn, $query);
    $gastos = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $gastos[] = $row;
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $gastos];
}

/**
 * Registra un gasto en taller externo
 */
function registrarGastoExterno($datos) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    verificarTablaGastosExternos($conn);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $usuarioId = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0;

    $vehiculoId = intval($datos['vehiculo_id']);
    $tallerNombre = mysqli_real_escape_string($conn, $datos['taller_nombre']);
    $servicio = mysqli_real_escape_string($conn, $datos['servicio']);
    $costoTotal = floatval($datos['costo_total']);
    $fecha = mysqli_real_escape_string($conn, $datos['fecha']);
    $observaciones = mysqli_real_escape_string($conn, $datos['observaciones'] ?? '');

    $query = "INSERT INTO gastos_externos 
              (VehiculoID, TallerNombre, Servicio, CostoTotal, Fecha, Observaciones, Estado, UsuarioRegistro, FechaRegistro) 
              VALUES ($vehiculoId, '$tallerNombre', '$servicio', $costoTotal, '$fecha', '$observaciones', 'Registrado', $usuarioId, NOW())";

    if (mysqli_query($conn, $query)) {
        $gastoId = mysqli_insert_id($conn);
        mysqli_close($conn);
        return ['status' => 'success', 'message' => 'Gasto registrado correctamente', 'id' => $gastoId];
    } else {
        $error = mysqli_error($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al registrar gasto: ' . $error];
    }
}

/**
 * Obtiene vehículos activos
 */
function obtenerVehiculosActivos() {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    $query = "SELECT ID, Placa, Marca, Modelo 
              FROM ingreso_vehiculos 
              WHERE Estado IN ('Ingresado', 'Asignado', 'En Proceso')
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
 * Obtiene estadísticas de gastos
 */
function obtenerEstadisticasGastos($filtros = []) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    $where = [];
    $mesActual = date('Y-m');
    
    if (!empty($filtros['fecha_desde'])) {
        $fechaDesde = mysqli_real_escape_string($conn, $filtros['fecha_desde']);
        $where[] = "DATE(fecha) >= '$fechaDesde'";
    } else {
        $where[] = "DATE(fecha) >= '$mesActual-01'";
    }
    
    if (!empty($filtros['fecha_hasta'])) {
        $fechaHasta = mysqli_real_escape_string($conn, $filtros['fecha_hasta']);
        $where[] = "DATE(fecha) <= '$fechaHasta'";
    } else {
        $where[] = "DATE(fecha) <= LAST_DAY('$mesActual-01')";
    }
    
    $whereClause = implode(' AND ', $where);

    $estadisticas = [
        'gastos_mes' => 0,
        'talleres_internos' => 0,
        'talleres_externos' => 0,
        'vehiculos_taller' => 0
    ];

    // Gastos del mes (internos + externos)
    $query = "SELECT COALESCE(SUM(COALESCE(ra.Cantidad * ra.PrecioUnitario, 0)), 0) as total
              FROM asignaciones_mecanico a
              LEFT JOIN repuestos_asignacion ra ON a.ID = ra.AsignacionID
              WHERE $whereClause";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $estadisticas['gastos_mes'] += floatval($row['total']);
        mysqli_free_result($result);
    }

    // Gastos externos
    $checkTable = "SHOW TABLES LIKE 'gastos_externos'";
    $resultCheck = mysqli_query($conn, $checkTable);
    if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
        $query = "SELECT COALESCE(SUM(CostoTotal), 0) as total
                  FROM gastos_externos
                  WHERE $whereClause";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['gastos_mes'] += floatval($row['total']);
            mysqli_free_result($result);
        }

        $query = "SELECT COUNT(DISTINCT ID) as total FROM gastos_externos WHERE $whereClause";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['talleres_externos'] = $row['total'];
            mysqli_free_result($result);
        }
    }

    // Talleres internos (asignaciones)
    $query = "SELECT COUNT(DISTINCT a.ID) as total
              FROM asignaciones_mecanico a
              WHERE $whereClause";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $estadisticas['talleres_internos'] = $row['total'];
        mysqli_free_result($result);
    }

    // Vehículos en taller
    $query = "SELECT COUNT(DISTINCT v.ID) as total
              FROM ingreso_vehiculos v
              INNER JOIN asignaciones_mecanico a ON v.ID = a.VehiculoID
              WHERE v.Estado IN ('Asignado', 'En Proceso') AND a.Estado IN ('Asignado', 'En Proceso')";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $estadisticas['vehiculos_taller'] = $row['total'];
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $estadisticas];
}

/**
 * Verifica y crea la tabla de gastos externos si no existe
 */
function verificarTablaGastosExternos($conn) {
    $checkTable = "SHOW TABLES LIKE 'gastos_externos'";
    $result = mysqli_query($conn, $checkTable);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return true;
    }

    // Crear tabla
    $createTable = "CREATE TABLE IF NOT EXISTS gastos_externos (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        VehiculoID INT NOT NULL,
        TallerNombre VARCHAR(255) NOT NULL,
        Servicio TEXT NOT NULL,
        CostoTotal DECIMAL(10,2) NOT NULL,
        Fecha DATE NOT NULL,
        Observaciones TEXT NULL,
        Estado VARCHAR(20) DEFAULT 'Registrado',
        UsuarioRegistro INT NOT NULL,
        FechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_vehiculo (VehiculoID),
        INDEX idx_fecha (Fecha),
        INDEX idx_estado (Estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return mysqli_query($conn, $createTable);
}

/**
 * Obtiene los detalles de un gasto externo
 */
function obtenerDetallesGastoExterno($gastoId) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    verificarTablaGastosExternos($conn);

    $gastoId = intval($gastoId);
    $query = "SELECT 
                ge.*,
                v.Placa,
                v.Marca,
                v.Modelo
              FROM gastos_externos ge
              INNER JOIN ingreso_vehiculos v ON ge.VehiculoID = v.ID
              WHERE ge.ID = $gastoId
              LIMIT 1";

    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $gasto = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        mysqli_close($conn);
        return ['status' => 'success', 'data' => $gasto];
    } else {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Gasto no encontrado'];
    }
}

/**
 * Actualiza un gasto externo
 */
function actualizarGastoExterno($datos) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    verificarTablaGastosExternos($conn);

    $gastoId = intval($datos['gasto_id']);
    $vehiculoId = intval($datos['vehiculo_id']);
    $tallerNombre = mysqli_real_escape_string($conn, $datos['taller_nombre']);
    $servicio = mysqli_real_escape_string($conn, $datos['servicio']);
    $costoTotal = floatval($datos['costo_total']);
    $fecha = mysqli_real_escape_string($conn, $datos['fecha']);
    $observaciones = mysqli_real_escape_string($conn, $datos['observaciones'] ?? '');

    $query = "UPDATE gastos_externos 
              SET VehiculoID = $vehiculoId,
                  TallerNombre = '$tallerNombre',
                  Servicio = '$servicio',
                  CostoTotal = $costoTotal,
                  Fecha = '$fecha',
                  Observaciones = '$observaciones'
              WHERE ID = $gastoId";

    if (mysqli_query($conn, $query)) {
        mysqli_close($conn);
        return ['status' => 'success', 'message' => 'Gasto actualizado correctamente'];
    } else {
        $error = mysqli_error($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al actualizar gasto: ' . $error];
    }
}

/**
 * Elimina un gasto externo
 */
function eliminarGastoExterno($gastoId) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    verificarTablaGastosExternos($conn);

    $gastoId = intval($gastoId);
    $query = "DELETE FROM gastos_externos WHERE ID = $gastoId";

    if (mysqli_query($conn, $query)) {
        mysqli_close($conn);
        return ['status' => 'success', 'message' => 'Gasto eliminado correctamente'];
    } else {
        $error = mysqli_error($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al eliminar gasto: ' . $error];
    }
}

