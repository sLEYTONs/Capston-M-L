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
 * Obtiene todos los repuestos de la base de datos
 * @return array
 */
function obtenerTodosRepuestos() {
    try {
        $conn = conectarPepsicoSeguro();
        if (!$conn) {
            return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
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

        // Obtener todos los repuestos con todas las columnas
        $query = "SELECT 
                    id as ID,
                    codigo as Codigo,
                    nombre as Nombre,
                    categoria as Categoria,
                    stock as Stock,
                    precio as Precio,
                    stockminimo as StockMinimo,
                    descripcion as Descripcion,
                    estado as Estado,
                    fechacreacion as FechaCreacion,
                    fechaactualizacion as FechaActualizacion
                  FROM repuestos
                  ORDER BY nombre ASC";

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
 * Genera un reporte Excel completo con diseño mejorado
 * @return void
 */
function generarReporteExcelCompleto() {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    $conn = conectar_Pepsico();
    if (!$conn) {
        die('Error de conexión a la base de datos');
    }
    
    // Headers para Excel
    $filename = 'reporte_gestion_repuestos_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Obtener datos
    $estadisticas = obtenerEstadisticasJefe();
    $repuestos = obtenerTodosRepuestos();
    $solicitudesPendientes = obtenerSolicitudesPendientes();
    $comunicaciones = obtenerComunicacionesJefe();
    
    $estadisticasData = $estadisticas['status'] === 'success' ? $estadisticas['data'] : [];
    $repuestosData = $repuestos['status'] === 'success' ? $repuestos['data'] : [];
    $solicitudesData = $solicitudesPendientes['status'] === 'success' ? $solicitudesPendientes['data'] : [];
    $comunicacionesData = $comunicaciones['status'] === 'success' ? $comunicaciones['data'] : [];
    
    // Calcular totales
    $totalStock = 0;
    $valorTotal = 0;
    foreach ($repuestosData as $rep) {
        $stock = intval($rep['Stock'] ?? 0);
        $precio = floatval($rep['Precio'] ?? 0);
        $totalStock += $stock;
        $valorTotal += ($stock * $precio);
    }
    
    // Generar HTML que Excel puede interpretar
    echo '<html>';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo '.titulo { font-size: 20px; font-weight: bold; color: #FFFFFF; background: linear-gradient(135deg, #004B93 0%, #E21C21 100%); padding: 20px; text-align: center; border: 2px solid #003366; }';
    echo '.subtitulo { font-size: 16px; font-weight: bold; color: #004B93; background-color: #E7F3FF; padding: 12px; margin-top: 25px; border-left: 5px solid #004B93; }';
    echo '.resumen { background-color: #F8F9FA; padding: 15px; margin: 15px 0; border: 1px solid #DEE2E6; border-radius: 5px; }';
    echo '.resumen-grid { display: table; width: 100%; }';
    echo '.resumen-item { display: table-cell; padding: 10px; text-align: center; border-right: 1px solid #DEE2E6; }';
    echo '.resumen-item:last-child { border-right: none; }';
    echo '.resumen-label { font-weight: bold; color: #495057; font-size: 12px; margin-bottom: 5px; }';
    echo '.resumen-valor { color: #004B93; font-size: 18px; font-weight: bold; }';
    echo 'table { border-collapse: collapse; width: 100%; margin: 15px 0; font-size: 11px; }';
    echo 'th { background: linear-gradient(135deg, #004B93 0%, #0056b3 100%); color: white; font-weight: bold; padding: 12px 8px; text-align: left; border: 1px solid #003366; font-size: 11px; }';
    echo 'td { padding: 10px 8px; border: 1px solid #D0D0D0; background-color: #FFFFFF; }';
    echo 'tr:nth-child(even) td { background-color: #F8F9FA; }';
    echo 'tr:hover td { background-color: #E7F3FF; }';
    echo '.numero { text-align: right; }';
    echo '.moneda { text-align: right; color: #006600; font-weight: bold; }';
    echo '.centro { text-align: center; }';
    echo '.badge { padding: 4px 8px; border-radius: 3px; font-weight: bold; font-size: 10px; }';
    echo '.badge-urgente { background-color: #DC3545; color: white; }';
    echo '.badge-alta { background-color: #FFC107; color: #000; }';
    echo '.badge-media { background-color: #17A2B8; color: white; }';
    echo '.badge-baja { background-color: #6C757D; color: white; }';
    echo '.badge-pendiente { background-color: #FFC107; color: #000; }';
    echo '.badge-aprobada { background-color: #28A745; color: white; }';
    echo '.badge-rechazada { background-color: #DC3545; color: white; }';
    echo '.badge-proceso { background-color: #17A2B8; color: white; }';
    echo '.footer { text-align: center; color: #666; font-size: 10px; margin-top: 30px; padding-top: 15px; border-top: 1px solid #DEE2E6; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Título principal
    echo '<div class="titulo">';
    echo '<div style="font-size: 24px; margin-bottom: 5px;">REPORTE COMPLETO DE GESTIÓN DE REPUESTOS</div>';
    echo '<div style="font-size: 14px; font-weight: normal;">Sistema PepsiCo - Gestión con Jefe de Taller</div>';
    echo '</div>';
    
    // Estadísticas resumidas
    echo '<div class="subtitulo">📊 ESTADÍSTICAS GENERALES</div>';
    echo '<div class="resumen">';
    echo '<div class="resumen-grid">';
    echo '<div class="resumen-item">';
    echo '<div class="resumen-label">Total Repuestos</div>';
    echo '<div class="resumen-valor">' . ($estadisticasData['total_repuestos'] ?? 0) . '</div>';
    echo '</div>';
    echo '<div class="resumen-item">';
    echo '<div class="resumen-label">Stock Bajo</div>';
    echo '<div class="resumen-valor" style="color: #FFC107;">' . ($estadisticasData['stock_bajo'] ?? 0) . '</div>';
    echo '</div>';
    echo '<div class="resumen-item">';
    echo '<div class="resumen-label">Solicitudes Pendientes</div>';
    echo '<div class="resumen-valor" style="color: #DC3545;">' . ($estadisticasData['solicitudes_pendientes'] ?? 0) . '</div>';
    echo '</div>';
    echo '<div class="resumen-item">';
    echo '<div class="resumen-label">Entregas del Mes</div>';
    echo '<div class="resumen-valor" style="color: #28A745;">' . ($estadisticasData['entregas_mes'] ?? 0) . '</div>';
    echo '</div>';
    echo '<div class="resumen-item">';
    echo '<div class="resumen-label">Total Stock</div>';
    echo '<div class="resumen-valor">' . number_format($totalStock, 0, ',', '.') . '</div>';
    echo '</div>';
    echo '<div class="resumen-item">';
    echo '<div class="resumen-label">Valor Total Inventario</div>';
    echo '<div class="resumen-valor" style="color: #006600;">$' . number_format($valorTotal, 2, ',', '.') . '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Tabla de Repuestos
    if (!empty($repuestosData)) {
        echo '<div class="subtitulo">📦 INVENTARIO DE REPUESTOS</div>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Código</th>';
        echo '<th>Nombre</th>';
        echo '<th>Categoría</th>';
        echo '<th class="numero">Stock</th>';
        echo '<th class="numero">Stock Mín.</th>';
        echo '<th class="moneda">Precio Unit.</th>';
        echo '<th class="moneda">Valor Total</th>';
        echo '<th class="centro">Estado</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($repuestosData as $rep) {
            $stock = intval($rep['Stock'] ?? 0);
            $stockMin = intval($rep['StockMinimo'] ?? 0);
            $precio = floatval($rep['Precio'] ?? 0);
            $valor = $stock * $precio;
            $estado = $rep['Estado'] ?? 'Inactivo';
            $estadoClass = $estado === 'Activo' ? 'badge-aprobada' : 'badge-rechazada';
            $stockClass = ($stock <= $stockMin) ? ' style="background-color: #FFF3CD; color: #856404; font-weight: bold;"' : '';
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($rep['Codigo'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($rep['Nombre'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($rep['Categoria'] ?? '-') . '</td>';
            echo '<td class="numero"' . $stockClass . '>' . number_format($stock, 0, ',', '.') . '</td>';
            echo '<td class="numero">' . number_format($stockMin, 0, ',', '.') . '</td>';
            echo '<td class="moneda">$' . number_format($precio, 2, ',', '.') . '</td>';
            echo '<td class="moneda">$' . number_format($valor, 2, ',', '.') . '</td>';
            echo '<td class="centro"><span class="badge ' . $estadoClass . '">' . htmlspecialchars($estado) . '</span></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    // Tabla de Solicitudes Pendientes
    if (!empty($solicitudesData)) {
        echo '<div class="subtitulo">⚠️ SOLICITUDES PENDIENTES DE APROBACIÓN</div>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Fecha</th>';
        echo '<th>Tipo Solicitud</th>';
        echo '<th>Asunto</th>';
        echo '<th>Prioridad</th>';
        echo '<th>Usuario</th>';
        echo '<th>Descripción</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($solicitudesData as $sol) {
            $prioridad = strtolower($sol['Prioridad'] ?? 'media');
            $prioridadClass = 'badge-' . $prioridad;
            $fecha = !empty($sol['FechaCreacion']) ? date('d/m/Y H:i', strtotime($sol['FechaCreacion'])) : '-';
            $descripcion = substr($sol['Descripcion'] ?? '', 0, 100);
            if (strlen($sol['Descripcion'] ?? '') > 100) {
                $descripcion .= '...';
            }
            
            echo '<tr>';
            echo '<td>' . $fecha . '</td>';
            echo '<td>' . htmlspecialchars($sol['TipoSolicitud'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($sol['Asunto'] ?? '-') . '</td>';
            echo '<td class="centro"><span class="badge ' . $prioridadClass . '">' . strtoupper($prioridad) . '</span></td>';
            echo '<td>' . htmlspecialchars($sol['UsuarioNombre'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($descripcion) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    // Tabla de Historial de Comunicaciones
    if (!empty($comunicacionesData)) {
        echo '<div class="subtitulo">💬 HISTORIAL DE COMUNICACIONES</div>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Fecha</th>';
        echo '<th>Tipo</th>';
        echo '<th>Asunto</th>';
        echo '<th>Prioridad</th>';
        echo '<th>Estado</th>';
        echo '<th>Usuario</th>';
        echo '<th>Respuesta</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($comunicacionesData as $com) {
            $prioridad = strtolower($com['Prioridad'] ?? 'media');
            $prioridadClass = 'badge-' . $prioridad;
            $estado = $com['Estado'] ?? 'Pendiente';
            $estadoClass = 'badge-' . strtolower(str_replace(' ', '-', $estado));
            $fecha = !empty($com['FechaCreacion']) ? date('d/m/Y H:i', strtotime($com['FechaCreacion'])) : '-';
            $respuesta = !empty($com['Respuesta']) ? substr($com['Respuesta'], 0, 80) . '...' : 'Sin respuesta';
            
            echo '<tr>';
            echo '<td>' . $fecha . '</td>';
            echo '<td>' . htmlspecialchars($com['TipoSolicitud'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($com['Asunto'] ?? '-') . '</td>';
            echo '<td class="centro"><span class="badge ' . $prioridadClass . '">' . strtoupper($prioridad) . '</span></td>';
            echo '<td class="centro"><span class="badge ' . $estadoClass . '">' . htmlspecialchars($estado) . '</span></td>';
            echo '<td>' . htmlspecialchars($com['UsuarioNombre'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($respuesta) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    // Footer
    echo '<div class="footer">';
    echo 'Generado el ' . date('d/m/Y H:i:s') . ' - Sistema PepsiCo<br>';
    echo 'Reporte de Gestión de Repuestos con Jefe de Taller';
    echo '</div>';
    
    echo '</body>';
    echo '</html>';
    
    mysqli_close($conn);
    exit;
}

