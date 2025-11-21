<?php
require_once __DIR__ . '../../../../config/conexion.php';

/**
 * Crea una nueva Orden de Trabajo (OT)
 * @param array $datos - Datos de la OT
 * @return array - Resultado de la operación
 */
function crearOrdenTrabajo($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        // Verificar si existe la tabla ordenes_trabajo
        $checkTable = "SHOW TABLES LIKE 'ordenes_trabajo'";
        $resultCheck = mysqli_query($conn, $checkTable);
        $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

        if (!$tablaExiste) {
            // Crear tabla si no existe
            $createTable = "CREATE TABLE IF NOT EXISTS `ordenes_trabajo` (
                `ID` INT(11) NOT NULL AUTO_INCREMENT,
                `VehiculoID` INT(11) NOT NULL,
                `NumeroOT` VARCHAR(50) NOT NULL,
                `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `FechaRecepcion` DATETIME DEFAULT NULL,
                `RecepcionistaID` INT(11) NOT NULL,
                `Estado` ENUM('Pendiente', 'En Proceso', 'Completada', 'Cancelada') DEFAULT 'Pendiente',
                `TipoTrabajo` VARCHAR(100) DEFAULT NULL,
                `DescripcionTrabajo` TEXT DEFAULT NULL,
                `Observaciones` TEXT DEFAULT NULL,
                `DocumentosValidados` TINYINT(1) DEFAULT 0,
                `Fotos` TEXT DEFAULT NULL,
                `Documentos` TEXT DEFAULT NULL,
                `UsuarioCreacionID` INT(11) NOT NULL,
                PRIMARY KEY (`ID`),
                UNIQUE KEY `NumeroOT` (`NumeroOT`),
                KEY `VehiculoID` (`VehiculoID`),
                KEY `RecepcionistaID` (`RecepcionistaID`),
                KEY `Estado` (`Estado`),
                CONSTRAINT `fk_ot_vehiculo` FOREIGN KEY (`VehiculoID`)
                    REFERENCES `ingreso_vehiculos` (`ID`)
                    ON DELETE RESTRICT
                    ON UPDATE CASCADE,
                CONSTRAINT `fk_ot_recepcionista` FOREIGN KEY (`RecepcionistaID`)
                    REFERENCES `usuarios` (`UsuarioID`)
                    ON DELETE RESTRICT
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if (!mysqli_query($conn, $createTable)) {
                throw new Exception('Error al crear tabla ordenes_trabajo: ' . mysqli_error($conn));
            }
        }

        // Generar número de OT único
        $numeroOT = generarNumeroOT($conn);

        // Preparar datos
        $vehiculoID = intval($datos['vehiculo_id']);
        $recepcionistaID = intval($datos['recepcionista_id']);
        $usuarioCreacionID = intval($datos['usuario_creacion_id']);
        $tipoTrabajo = mysqli_real_escape_string($conn, $datos['tipo_trabajo'] ?? '');
        $descripcionTrabajo = mysqli_real_escape_string($conn, $datos['descripcion_trabajo'] ?? '');
        $observaciones = mysqli_real_escape_string($conn, $datos['observaciones'] ?? '');
        $documentosValidados = isset($datos['documentos_validados']) ? (int)$datos['documentos_validados'] : 0;
        $fotos = !empty($datos['fotos']) ? json_encode($datos['fotos']) : NULL;
        $documentos = !empty($datos['documentos']) ? json_encode($datos['documentos']) : NULL;

        $query = "INSERT INTO ordenes_trabajo 
                 (VehiculoID, NumeroOT, FechaRecepcion, RecepcionistaID, TipoTrabajo, 
                  DescripcionTrabajo, Observaciones, DocumentosValidados, Fotos, Documentos, UsuarioCreacionID)
                 VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'isississsi', 
            $vehiculoID, $numeroOT, $recepcionistaID, $tipoTrabajo, 
            $descripcionTrabajo, $observaciones, $documentosValidados, $fotos, $documentos, $usuarioCreacionID);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error ejecutando consulta: ' . mysqli_stmt_error($stmt));
        }

        $otID = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        return [
            'status' => 'success',
            'message' => 'Orden de Trabajo creada correctamente',
            'ot_id' => $otID,
            'numero_ot' => $numeroOT
        ];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
        mysqli_close($conn);
    }
}

/**
 * Genera un número de OT único
 */
function generarNumeroOT($conn) {
    $anio = date('Y');
    $mes = date('m');
    
    // Buscar el último número de OT del mes
    $query = "SELECT NumeroOT FROM ordenes_trabajo 
             WHERE NumeroOT LIKE 'OT-{$anio}{$mes}%' 
             ORDER BY NumeroOT DESC LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $ultimoNumero = intval(substr($row['NumeroOT'], -4));
        $nuevoNumero = str_pad($ultimoNumero + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $nuevoNumero = '0001';
    }
    
    return "OT-{$anio}{$mes}-{$nuevoNumero}";
}

/**
 * Obtiene todas las OTs para el recepcionista
 */
function obtenerOrdenesTrabajo($filtros = []) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    // Verificar si existe la tabla
    $checkTable = "SHOW TABLES LIKE 'ordenes_trabajo'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    if (!$tablaExiste) {
        mysqli_close($conn);
        return [
            'status' => 'success',
            'data' => [],
            'total' => 0
        ];
    }

    $query = "SELECT 
                ot.ID AS OTID,
                ot.NumeroOT,
                ot.FechaCreacion,
                ot.FechaRecepcion,
                ot.Estado AS EstadoOT,
                ot.TipoTrabajo,
                ot.DescripcionTrabajo,
                ot.Observaciones,
                ot.DocumentosValidados,
                
                -- Datos del vehículo
                v.ID AS VehiculoID,
                v.Placa,
                v.Marca,
                v.Modelo,
                v.Color,
                v.ConductorNombre,
                
                -- Datos del recepcionista
                u.NombreUsuario AS RecepcionistaNombre,
                
                -- Total de fotos (contar elementos en JSON)
                (CASE 
                    WHEN ot.Fotos IS NULL OR ot.Fotos = '' THEN 0
                    ELSE JSON_LENGTH(ot.Fotos)
                END) AS TotalFotos,
                
                -- Total de documentos (contar elementos en JSON)
                (CASE 
                    WHEN ot.Documentos IS NULL OR ot.Documentos = '' THEN 0
                    ELSE JSON_LENGTH(ot.Documentos)
                END) AS TotalDocumentos
                
            FROM ordenes_trabajo ot
            INNER JOIN ingreso_vehiculos v ON ot.VehiculoID = v.ID
            INNER JOIN usuarios u ON ot.RecepcionistaID = u.UsuarioID
            WHERE 1=1";

    $params = [];
    $types = '';

    // Aplicar filtros
    if (!empty($filtros['estado'])) {
        $query .= " AND ot.Estado = ?";
        $params[] = $filtros['estado'];
        $types .= 's';
    }

    if (!empty($filtros['recepcionista_id'])) {
        $query .= " AND ot.RecepcionistaID = ?";
        $params[] = intval($filtros['recepcionista_id']);
        $types .= 'i';
    }

    if (!empty($filtros['placa'])) {
        $query .= " AND v.Placa LIKE ?";
        $params[] = '%' . $filtros['placa'] . '%';
        $types .= 's';
    }

    if (!empty($filtros['numero_ot'])) {
        $query .= " AND ot.NumeroOT LIKE ?";
        $params[] = '%' . $filtros['numero_ot'] . '%';
        $types .= 's';
    }

    if (!empty($filtros['fecha_inicio'])) {
        $query .= " AND DATE(ot.FechaCreacion) >= ?";
        $params[] = $filtros['fecha_inicio'];
        $types .= 's';
    }

    if (!empty($filtros['fecha_fin'])) {
        $query .= " AND DATE(ot.FechaCreacion) <= ?";
        $params[] = $filtros['fecha_fin'];
        $types .= 's';
    }

    $query .= " ORDER BY ot.FechaCreacion DESC";

    $ots = [];

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
            return ['status' => 'error', 'message' => 'Error preparando consulta: ' . mysqli_error($conn)];
        }
    }

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Simplificar conteo de fotos y documentos si no está disponible JSON_TABLE
            if ($row['TotalFotos'] === null) {
                $row['TotalFotos'] = 0;
            }
            if ($row['TotalDocumentos'] === null) {
                $row['TotalDocumentos'] = 0;
            }
            $ots[] = $row;
        }
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
        mysqli_free_result($result);
    }

    mysqli_close($conn);

    return [
        'status' => 'success',
        'data' => $ots,
        'total' => count($ots)
    ];
}

/**
 * Valida documentación técnica de un vehículo/OT
 */
function validarDocumentacion($ot_id, $documentos_validados, $observaciones, $usuario_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        $ot_id = intval($ot_id);
        $documentos_validados = (int)$documentos_validados;
        $observaciones = mysqli_real_escape_string($conn, $observaciones);
        $usuario_id = intval($usuario_id);

        $query = "UPDATE ordenes_trabajo 
                 SET DocumentosValidados = ?, 
                     Observaciones = ?,
                     FechaRecepcion = NOW()
                 WHERE ID = ? AND RecepcionistaID = ?";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'isii', $documentos_validados, $observaciones, $ot_id, $usuario_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error ejecutando consulta: ' . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
        mysqli_commit($conn);

        return [
            'status' => 'success',
            'message' => 'Documentación validada correctamente'
        ];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
        mysqli_close($conn);
    }
}

/**
 * Obtiene una OT específica por ID
 */
function obtenerOTPorID($ot_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    $ot_id = mysqli_real_escape_string($conn, intval($ot_id));

    $query = "SELECT 
                ot.*,
                v.Placa, v.Marca, v.Modelo, v.Color, v.ConductorNombre,
                u.NombreUsuario AS RecepcionistaNombre
            FROM ordenes_trabajo ot
            INNER JOIN ingreso_vehiculos v ON ot.VehiculoID = v.ID
            INNER JOIN usuarios u ON ot.RecepcionistaID = u.UsuarioID
            WHERE ot.ID = '$ot_id'";

    $result = mysqli_query($conn, $query);
    $ot = null;

    if ($result && $row = mysqli_fetch_assoc($result)) {
        // Decodificar JSON de fotos y documentos
        if ($row['Fotos']) {
            $row['Fotos'] = json_decode($row['Fotos'], true);
        } else {
            $row['Fotos'] = [];
        }
        
        if ($row['Documentos']) {
            $row['Documentos'] = json_decode($row['Documentos'], true);
        } else {
            $row['Documentos'] = [];
        }
        
        $ot = $row;
    }

    mysqli_free_result($result);
    mysqli_close($conn);

    return [
        'status' => 'success',
        'data' => $ot
    ];
}

/**
 * Actualiza fotos de una OT
 */
function actualizarFotosOT($ot_id, $fotos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    $ot_id = intval($ot_id);
    $fotos_json = json_encode($fotos);

    $query = "UPDATE ordenes_trabajo SET Fotos = ? WHERE ID = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error preparando consulta: ' . mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, 'si', $fotos_json, $ot_id);
    $resultado = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    return [
        'status' => $resultado ? 'success' : 'error',
        'message' => $resultado ? 'Fotos actualizadas correctamente' : 'Error al actualizar fotos'
    ];
}

/**
 * Actualiza documentos de una OT
 */
function actualizarDocumentosOT($ot_id, $documentos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    $ot_id = intval($ot_id);
    $documentos_json = json_encode($documentos);

    $query = "UPDATE ordenes_trabajo SET Documentos = ? WHERE ID = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error preparando consulta: ' . mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, 'si', $documentos_json, $ot_id);
    $resultado = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    return [
        'status' => $resultado ? 'success' : 'error',
        'message' => $resultado ? 'Documentos actualizados correctamente' : 'Error al actualizar documentos'
    ];
}

