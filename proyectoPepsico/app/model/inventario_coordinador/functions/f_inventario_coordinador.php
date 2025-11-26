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
 * Obtiene el inventario completo de repuestos con filtros
 */
function obtenerInventarioCoordinador($filtros = []) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    $where = ["Estado = 'Activo'"];
    
    if (!empty($filtros['categoria'])) {
        $categoria = mysqli_real_escape_string($conn, $filtros['categoria']);
        $where[] = "Categoria = '$categoria'";
    }
    
    if (!empty($filtros['busqueda'])) {
        $busqueda = mysqli_real_escape_string($conn, $filtros['busqueda']);
        $where[] = "(Codigo LIKE '%$busqueda%' OR Nombre LIKE '%$busqueda%')";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Aplicar filtro de estado de stock
    if (!empty($filtros['estado'])) {
        if ($filtros['estado'] === 'sin') {
            $whereClause .= " AND Stock = 0";
        } elseif ($filtros['estado'] === 'bajo') {
            $whereClause .= " AND Stock > 0 AND Stock <= StockMinimo";
        } elseif ($filtros['estado'] === 'normal') {
            $whereClause .= " AND Stock > StockMinimo";
        }
    }

    $query = "SELECT ID, Codigo, Nombre, Categoria, Stock, StockMinimo, 
                     COALESCE(Precio, 0) as PrecioUnitario,
                     Estado, FechaActualizacion
              FROM repuestos
              WHERE $whereClause
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
    return ['status' => 'success', 'data' => $repuestos];
}

/**
 * Obtiene estadísticas del inventario
 */
function obtenerEstadisticasInventario() {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    $estadisticas = [
        'total_repuestos' => 0,
        'stock_bajo' => 0,
        'sin_stock' => 0,
        'valor_total' => 0
    ];

    // Verificar si existe la tabla repuestos
    $checkTable = "SHOW TABLES LIKE 'repuestos'";
    $resultCheck = mysqli_query($conn, $checkTable);
    if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
        // Total de repuestos activos
        $query = "SELECT COUNT(*) as total FROM repuestos WHERE Estado = 'Activo'";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['total_repuestos'] = $row['total'];
            mysqli_free_result($result);
        }

        // Stock bajo
        $query = "SELECT COUNT(*) as total FROM repuestos 
                  WHERE Estado = 'Activo' AND Stock > 0 AND Stock <= StockMinimo";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['stock_bajo'] = $row['total'];
            mysqli_free_result($result);
        }

        // Sin stock
        $query = "SELECT COUNT(*) as total FROM repuestos 
                  WHERE Estado = 'Activo' AND Stock = 0";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['sin_stock'] = $row['total'];
            mysqli_free_result($result);
        }

        // Valor total del inventario
        $query = "SELECT SUM(Stock * COALESCE(Precio, 0)) as valor_total 
                  FROM repuestos WHERE Estado = 'Activo'";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $estadisticas['valor_total'] = floatval($row['valor_total'] ?? 0);
            mysqli_free_result($result);
        }
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $estadisticas];
}

/**
 * Obtiene las categorías de repuestos
 */
function obtenerCategoriasRepuestos() {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    $query = "SELECT DISTINCT Categoria 
              FROM repuestos 
              WHERE Categoria IS NOT NULL AND Categoria != '' AND Estado = 'Activo'
              ORDER BY Categoria ASC";

    $result = mysqli_query($conn, $query);
    $categorias = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categorias[] = $row['Categoria'];
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $categorias];
}

/**
 * Obtiene los movimientos de stock de un repuesto
 */
function obtenerMovimientosStock($repuesto_id) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    $repuesto_id = intval($repuesto_id);
    $movimientos = [];

    // Verificar si existe la tabla repuestos_asignacion
    $checkTable = "SHOW TABLES LIKE 'repuestos_asignacion'";
    $resultTable = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultTable && mysqli_num_rows($resultTable) > 0);

    if ($tablaExiste) {
        $query = "SELECT 
                    ra.ID,
                    DATE_FORMAT(ra.FechaRegistro, '%d/%m/%Y %H:%i') as Fecha,
                    'Salida' as Tipo,
                    ra.Cantidad,
                    ra.Observaciones,
                    v.Placa
                  FROM repuestos_asignacion ra
                  LEFT JOIN asignaciones_mecanico a ON ra.AsignacionID = a.ID
                  LEFT JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                  WHERE ra.RepuestoID = $repuesto_id
                  ORDER BY ra.FechaRegistro DESC
                  LIMIT 50";
        
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $movimientos[] = [
                    'Fecha' => $row['Fecha'],
                    'Tipo' => 'Salida',
                    'Cantidad' => $row['Cantidad'],
                    'StockAnterior' => '-',
                    'StockNuevo' => '-',
                    'Observaciones' => ($row['Placa'] ? 'Vehículo: ' . $row['Placa'] : '') . ($row['Observaciones'] ? ' - ' . $row['Observaciones'] : '')
                ];
            }
            mysqli_free_result($result);
        }
    }

    // Verificar si existe la tabla recepciones_repuestos_detalle
    $checkRecepciones = "SHOW TABLES LIKE 'recepciones_repuestos_detalle'";
    $resultRecepciones = mysqli_query($conn, $checkRecepciones);
    if ($resultRecepciones && mysqli_num_rows($resultRecepciones) > 0) {
        $query = "SELECT 
                    rd.ID,
                    DATE_FORMAT(r.FechaRecepcion, '%d/%m/%Y %H:%i') as Fecha,
                    'Entrada' as Tipo,
                    rd.Cantidad,
                    r.Observaciones,
                    p.Nombre as ProveedorNombre
                  FROM recepciones_repuestos_detalle rd
                  INNER JOIN recepciones_repuestos r ON rd.RecepcionID = r.ID
                  LEFT JOIN proveedores p ON r.ProveedorID = p.ID
                  WHERE rd.RepuestoID = $repuesto_id
                  ORDER BY r.FechaRecepcion DESC
                  LIMIT 50";
        
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $movimientos[] = [
                    'Fecha' => $row['Fecha'],
                    'Tipo' => 'Entrada',
                    'Cantidad' => $row['Cantidad'],
                    'StockAnterior' => '-',
                    'StockNuevo' => '-',
                    'Observaciones' => ($row['ProveedorNombre'] ? 'Proveedor: ' . $row['ProveedorNombre'] : '') . ($row['Observaciones'] ? ' - ' . $row['Observaciones'] : '')
                ];
            }
            mysqli_free_result($result);
        }
    }

    // Ordenar por fecha descendente
    usort($movimientos, function($a, $b) {
        return strtotime(str_replace('/', '-', $b['Fecha'])) - strtotime(str_replace('/', '-', $a['Fecha']));
    });

    mysqli_close($conn);
    return ['status' => 'success', 'data' => array_slice($movimientos, 0, 50)];
}

