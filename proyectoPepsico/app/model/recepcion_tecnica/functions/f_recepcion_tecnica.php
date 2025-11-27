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
            // Verificar que las tablas referenciadas existan
            $checkIngresoVehiculos = "SHOW TABLES LIKE 'ingreso_vehiculos'";
            $resultIngreso = mysqli_query($conn, $checkIngresoVehiculos);
            $ingresoExiste = ($resultIngreso && mysqli_num_rows($resultIngreso) > 0);
            
            $checkUsuarios = "SHOW TABLES LIKE 'usuarios'";
            $resultUsuarios = mysqli_query($conn, $checkUsuarios);
            $usuariosExiste = ($resultUsuarios && mysqli_num_rows($resultUsuarios) > 0);
            
            if (!$ingresoExiste) {
                throw new Exception('La tabla ingreso_vehiculos no existe. Debe crearse primero.');
            }
            
            if (!$usuariosExiste) {
                throw new Exception('La tabla usuarios no existe. Debe crearse primero.');
            }
            
            // Verificar que las columnas referenciadas existan y tengan el tipo correcto
            $checkColumnaVehiculo = "SHOW COLUMNS FROM ingreso_vehiculos WHERE Field = 'ID'";
            $resultColVehiculo = mysqli_query($conn, $checkColumnaVehiculo);
            if (!$resultColVehiculo || mysqli_num_rows($resultColVehiculo) == 0) {
                throw new Exception('La columna ID no existe en la tabla ingreso_vehiculos.');
            }
            
            $checkColumnaUsuario = "SHOW COLUMNS FROM usuarios WHERE Field = 'UsuarioID'";
            $resultColUsuario = mysqli_query($conn, $checkColumnaUsuario);
            if (!$resultColUsuario || mysqli_num_rows($resultColUsuario) == 0) {
                throw new Exception('La columna UsuarioID no existe en la tabla usuarios.');
            }
            
            // Crear tabla sin foreign keys primero
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
                KEY `Estado` (`Estado`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if (!mysqli_query($conn, $createTable)) {
                throw new Exception('Error al crear tabla ordenes_trabajo: ' . mysqli_error($conn));
            }
            
            // Agregar foreign keys después de crear la tabla
            // Primero verificar si ya existen las constraints
            $checkFKVehiculo = "SELECT CONSTRAINT_NAME 
                               FROM information_schema.TABLE_CONSTRAINTS 
                               WHERE TABLE_SCHEMA = DATABASE() 
                               AND TABLE_NAME = 'ordenes_trabajo' 
                               AND CONSTRAINT_NAME = 'fk_ot_vehiculo'";
            $resultFKVehiculo = mysqli_query($conn, $checkFKVehiculo);
            $fkVehiculoExiste = ($resultFKVehiculo && mysqli_num_rows($resultFKVehiculo) > 0);
            
            if (!$fkVehiculoExiste) {
                $addFKVehiculo = "ALTER TABLE `ordenes_trabajo` 
                                 ADD CONSTRAINT `fk_ot_vehiculo` 
                                 FOREIGN KEY (`VehiculoID`) 
                                 REFERENCES `ingreso_vehiculos` (`ID`) 
                                 ON DELETE RESTRICT 
                                 ON UPDATE CASCADE";
                
                if (!mysqli_query($conn, $addFKVehiculo)) {
                    error_log("Advertencia: No se pudo agregar foreign key fk_ot_vehiculo: " . mysqli_error($conn));
                    // No lanzar excepción, continuar sin la foreign key
                }
            }
            
            $checkFKRecepcionista = "SELECT CONSTRAINT_NAME 
                                    FROM information_schema.TABLE_CONSTRAINTS 
                                    WHERE TABLE_SCHEMA = DATABASE() 
                                    AND TABLE_NAME = 'ordenes_trabajo' 
                                    AND CONSTRAINT_NAME = 'fk_ot_recepcionista'";
            $resultFKRecepcionista = mysqli_query($conn, $checkFKRecepcionista);
            $fkRecepcionistaExiste = ($resultFKRecepcionista && mysqli_num_rows($resultFKRecepcionista) > 0);
            
            if (!$fkRecepcionistaExiste) {
                $addFKRecepcionista = "ALTER TABLE `ordenes_trabajo` 
                                      ADD CONSTRAINT `fk_ot_recepcionista` 
                                      FOREIGN KEY (`RecepcionistaID`) 
                                      REFERENCES `usuarios` (`UsuarioID`) 
                                      ON DELETE RESTRICT 
                                      ON UPDATE CASCADE";
                
                if (!mysqli_query($conn, $addFKRecepcionista)) {
                    error_log("Advertencia: No se pudo agregar foreign key fk_ot_recepcionista: " . mysqli_error($conn));
                    // No lanzar excepción, continuar sin la foreign key
                }
            }
        }

        // Generar número de OT único
        $numeroOT = generarNumeroOT($conn);

        // Preparar datos
        $vehiculoID = intval($datos['vehiculo_id']);
        $recepcionistaID = intval($datos['recepcionista_id']);
        $usuarioCreacionID = intval($datos['usuario_creacion_id']);
        // Procesar campos de texto - usar cadena vacía en lugar de NULL para evitar problemas con bind_param
        $tipoTrabajo = isset($datos['tipo_trabajo']) && $datos['tipo_trabajo'] !== '' 
            ? mysqli_real_escape_string($conn, trim($datos['tipo_trabajo'])) 
            : '';
        $descripcionTrabajo = isset($datos['descripcion_trabajo']) && $datos['descripcion_trabajo'] !== '' 
            ? mysqli_real_escape_string($conn, trim($datos['descripcion_trabajo'])) 
            : '';
        
        // Procesar fotos - convertir arrays a JSON solo si tienen contenido
        $fotos = '';
        if (isset($datos['fotos']) && is_array($datos['fotos']) && count($datos['fotos']) > 0) {
            $fotos_json = json_encode($datos['fotos'], JSON_UNESCAPED_UNICODE);
            if ($fotos_json !== false && $fotos_json !== '[]') {
                $fotos = $fotos_json;
            }
        }

        // Procesar documentos técnicos
        $documentos = '';
        if (isset($datos['documentos']) && is_array($datos['documentos']) && count($datos['documentos']) > 0) {
            $documentos_json = json_encode($datos['documentos'], JSON_UNESCAPED_UNICODE);
            if ($documentos_json !== false && $documentos_json !== '[]') {
                $documentos = $documentos_json;
            }
        }

        // Validación de documentación técnica
        $documentosValidados = isset($datos['documentos_validados']) ? intval($datos['documentos_validados']) : 0;

        // INSERT con campos de documentos y validación
        $query = "INSERT INTO ordenes_trabajo 
                 (VehiculoID, NumeroOT, FechaRecepcion, RecepcionistaID, TipoTrabajo, 
                  DescripcionTrabajo, Fotos, Documentos, DocumentosValidados, UsuarioCreacionID)
                 VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . mysqli_error($conn));
        }

        // Orden de parámetros según VALUES: VehiculoID(i), NumeroOT(s), RecepcionistaID(i), TipoTrabajo(s), 
        // DescripcionTrabajo(s), Fotos(s), Documentos(s), DocumentosValidados(i), UsuarioCreacionID(i)
        // Total: 9 parámetros -> 'isissisii' (9 caracteres)
        
        // Asegurar que todos los valores string sean strings válidos (nunca NULL)
        $tipoTrabajo = (string)$tipoTrabajo;
        $descripcionTrabajo = (string)$descripcionTrabajo;
        $fotos = (string)$fotos;
        $documentos = (string)$documentos;
        
        mysqli_stmt_bind_param($stmt, 'isissisii', 
            $vehiculoID, $numeroOT, $recepcionistaID, $tipoTrabajo, 
            $descripcionTrabajo, $fotos, $documentos, $documentosValidados, $usuarioCreacionID);

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
                
                -- Datos del vehículo
                v.ID AS VehiculoID,
                v.Placa,
                v.Marca,
                v.Modelo,
                v.ConductorNombre,
                
                -- Datos del recepcionista
                u.NombreUsuario AS RecepcionistaNombre,
                
                -- Total de fotos (contar elementos en JSON)
                (CASE 
                    WHEN ot.Fotos IS NULL OR ot.Fotos = '' THEN 0
                    ELSE JSON_LENGTH(ot.Fotos)
                END) AS TotalFotos
                
            FROM ordenes_trabajo ot
            LEFT JOIN ingreso_vehiculos v ON ot.VehiculoID = v.ID
            LEFT JOIN usuarios u ON ot.RecepcionistaID = u.UsuarioID
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
        // Limpiar y normalizar el número de OT para la búsqueda
        $numeroOT = trim($filtros['numero_ot']);
        // Convertir a mayúsculas para coincidir con el formato almacenado
        $numeroOT = strtoupper($numeroOT);
        // Permitir búsqueda exacta o parcial (sin espacios)
        $numeroOT = str_replace(' ', '', $numeroOT);
        // Escapar caracteres especiales
        $numeroOT = mysqli_real_escape_string($conn, $numeroOT);
        
        error_log("Buscando OT con filtro: '$numeroOT'");
        
        $query .= " AND ot.NumeroOT LIKE ?";
        $params[] = '%' . $numeroOT . '%';
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
        $contador = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            // Simplificar conteo de fotos si no está disponible JSON_TABLE
            if ($row['TotalFotos'] === null) {
                $row['TotalFotos'] = 0;
            }
            $ots[] = $row;
            $contador++;
        }
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
        mysqli_free_result($result);
        
        error_log("obtenerOrdenesTrabajo: Se encontraron $contador OTs con los filtros aplicados");
        error_log("Query ejecutada: " . $query);
        if (!empty($filtros['numero_ot'])) {
            error_log("Filtro numero_ot aplicado: '" . $filtros['numero_ot'] . "'");
        }
    } else {
        error_log("Error en la consulta: " . mysqli_error($conn));
    }

    mysqli_close($conn);

    return [
        'status' => 'success',
        'data' => $ots,
        'total' => count($ots)
    ];
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
                v.Placa, v.Marca, v.Modelo, v.ConductorNombre,
                u.NombreUsuario AS RecepcionistaNombre
            FROM ordenes_trabajo ot
            INNER JOIN ingreso_vehiculos v ON ot.VehiculoID = v.ID
            INNER JOIN usuarios u ON ot.RecepcionistaID = u.UsuarioID
            WHERE ot.ID = '$ot_id'";

    $result = mysqli_query($conn, $query);
    $ot = null;

    if ($result && $row = mysqli_fetch_assoc($result)) {
        // Decodificar JSON de fotos
        if ($row['Fotos']) {
            $row['Fotos'] = json_decode($row['Fotos'], true);
        } else {
            $row['Fotos'] = [];
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


