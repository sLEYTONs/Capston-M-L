<?php
// Evitar mostrar errores en la salida
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../config/conexion.php';

/**
 * Función de conexión segura que no usa die()
 * @return mysqli|null
 */
function conectarPepsicoSeguro() {
    $mysqli = @new mysqli("localhost", "root", "", "Pepsico");
    
    if ($mysqli->connect_errno) {
        return null;
    }
    
    if (!$mysqli->set_charset("utf8mb4")) {
        $mysqli->close();
        return null;
    }
    
    return $mysqli;
}

/**
 * Obtiene todos los proveedores activos
 * @return array
 */
function obtenerProveedoresActivos() {
    try {
        $conn = conectarPepsicoSeguro();
        if (!$conn) {
            return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
        }
        
        // Verificar si existe la tabla proveedores
        $checkTable = "SHOW TABLES LIKE 'proveedores'";
        $resultCheck = mysqli_query($conn, $checkTable);
        $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
        
        if ($resultCheck) {
            mysqli_free_result($resultCheck);
        }
        
        if (!$tablaExiste) {
            mysqli_close($conn);
            return ['status' => 'success', 'data' => []];
        }
        
        $query = "SELECT ID, Nombre, Contacto, Email, Telefono, Estado
                  FROM proveedores 
                  ORDER BY Nombre ASC";
        
        $result = mysqli_query($conn, $query);
        $proveedores = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $proveedores[] = $row;
            }
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'Error en consulta: ' . $error];
        }
        
        mysqli_close($conn);
        return ['status' => 'success', 'data' => $proveedores];
    } catch (Exception $e) {
        if (isset($conn)) {
            mysqli_close($conn);
        }
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Obtiene todos los vehículos activos
 * @return array
 */
function obtenerVehiculosActivos() {
    try {
        $conn = conectarPepsicoSeguro();
        if (!$conn) {
            return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
        }
        
        $query = "SELECT ID, Placa, Marca, Modelo, TipoVehiculo 
                  FROM ingreso_vehiculos 
                  WHERE Estado IN ('Ingresado', 'En Proceso', 'Completado')
                  ORDER BY Placa ASC";
        
        $result = mysqli_query($conn, $query);
        $vehiculos = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $vehiculos[] = $row;
            }
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'Error en consulta: ' . $error];
        }
        
        mysqli_close($conn);
        return ['status' => 'success', 'data' => $vehiculos];
    } catch (Exception $e) {
        if (isset($conn)) {
            mysqli_close($conn);
        }
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Obtiene todos los mecánicos activos
 * @return array
 */
function obtenerMecanicosActivos() {
    try {
        $conn = conectarPepsicoSeguro();
        if (!$conn) {
            return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
        }
        
        // Verificar si la tabla usuarios existe
        $checkTable = "SHOW TABLES LIKE 'usuarios'";
        $resultCheck = mysqli_query($conn, $checkTable);
        $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
        
        if ($resultCheck) {
            mysqli_free_result($resultCheck);
        }
        
        // Intentar con diferentes nombres de tabla
        $tablaNombre = 'usuarios';
        if (!$tablaExiste) {
            $checkTable2 = "SHOW TABLES LIKE 'USUARIOS'";
            $resultCheck2 = mysqli_query($conn, $checkTable2);
            $tablaExiste = ($resultCheck2 && mysqli_num_rows($resultCheck2) > 0);
            if ($tablaExiste) {
                $tablaNombre = 'USUARIOS';
            }
            if ($resultCheck2) {
                mysqli_free_result($resultCheck2);
            }
        }
        
        if (!$tablaExiste) {
            mysqli_close($conn);
            return ['status' => 'success', 'data' => []];
        }
        
        $query = "SELECT UsuarioID, NombreUsuario, Correo 
                  FROM $tablaNombre 
                  WHERE Rol = 'Mecánico' AND Estado = 1 
                  ORDER BY NombreUsuario ASC";
        
        $result = mysqli_query($conn, $query);
        $mecanicos = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $mecanicos[] = $row;
            }
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'Error en consulta: ' . $error];
        }
        
        mysqli_close($conn);
        return ['status' => 'success', 'data' => $mecanicos];
    } catch (Exception $e) {
        if (isset($conn)) {
            mysqli_close($conn);
        }
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Obtiene todos los repuestos disponibles
 * @return array
 */
function obtenerRepuestosDisponibles() {
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
        
        // Obtener todos los repuestos disponibles
        // Solo obtener las columnas que existen en la tabla
        $query = "SELECT ID, Codigo, Nombre, Categoria, Stock, StockMinimo 
                  FROM repuestos 
                  ORDER BY Nombre ASC";
        
        $result = mysqli_query($conn, $query);
        $repuestos = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Asegurar que todos los campos estén presentes
                $repuestos[] = [
                    'ID' => intval($row['ID']),
                    'Codigo' => $row['Codigo'] ?? '',
                    'Nombre' => $row['Nombre'] ?? '',
                    'Categoria' => $row['Categoria'] ?? '',
                    'Stock' => intval($row['Stock'] ?? 0),
                    'StockMinimo' => intval($row['StockMinimo'] ?? 0)
                ];
            }
            mysqli_free_result($result);
        } else {
            // Si hay error en la consulta, retornar error
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
 * Verifica y crea la tabla de recepciones_repuestos si no existe
 * @param mysqli $conn
 * @return bool
 */
function verificarTablaRecepciones($conn) {
    $checkTable = "SHOW TABLES LIKE 'recepciones_repuestos'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
    
    if ($resultCheck) {
        mysqli_free_result($resultCheck);
    }
    
    if (!$tablaExiste) {
        $createTable = "CREATE TABLE IF NOT EXISTS `recepciones_repuestos` (
            `ID` INT(11) NOT NULL AUTO_INCREMENT,
            `ProveedorID` INT(11) NOT NULL,
            `NumeroFactura` VARCHAR(100) NOT NULL,
            `FechaRecepcion` DATETIME NOT NULL,
            `Observaciones` TEXT DEFAULT NULL,
            `UsuarioID` INT(11) NOT NULL,
            `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `ProveedorID` (`ProveedorID`),
            KEY `UsuarioID` (`UsuarioID`),
            KEY `FechaRecepcion` (`FechaRecepcion`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!mysqli_query($conn, $createTable)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Verifica y crea la tabla de recepciones_repuestos_detalle si no existe
 * @param mysqli $conn
 * @return bool
 */
function verificarTablaRecepcionesDetalle($conn) {
    $checkTable = "SHOW TABLES LIKE 'recepciones_repuestos_detalle'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
    
    if ($resultCheck) {
        mysqli_free_result($resultCheck);
    }
    
    if (!$tablaExiste) {
        $createTable = "CREATE TABLE IF NOT EXISTS `recepciones_repuestos_detalle` (
            `ID` INT(11) NOT NULL AUTO_INCREMENT,
            `RecepcionID` INT(11) NOT NULL,
            `RepuestoID` INT(11) NOT NULL,
            `Cantidad` INT(11) NOT NULL,
            `PrecioUnitario` DECIMAL(10,2) DEFAULT NULL,
            PRIMARY KEY (`ID`),
            KEY `RecepcionID` (`RecepcionID`),
            KEY `RepuestoID` (`RepuestoID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!mysqli_query($conn, $createTable)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Verifica y crea la tabla de entregas_repuestos si no existe
 * @param mysqli $conn
 * @return bool
 */
function verificarTablaEntregas($conn) {
    $checkTable = "SHOW TABLES LIKE 'entregas_repuestos'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
    
    if ($resultCheck) {
        mysqli_free_result($resultCheck);
    }
    
    if (!$tablaExiste) {
        $createTable = "CREATE TABLE IF NOT EXISTS `entregas_repuestos` (
            `ID` INT(11) NOT NULL AUTO_INCREMENT,
            `VehiculoID` INT(11) NOT NULL,
            `MecanicoID` INT(11) NOT NULL,
            `FechaEntrega` DATETIME NOT NULL,
            `Observaciones` TEXT DEFAULT NULL,
            `UsuarioID` INT(11) NOT NULL,
            `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `VehiculoID` (`VehiculoID`),
            KEY `MecanicoID` (`MecanicoID`),
            KEY `UsuarioID` (`UsuarioID`),
            KEY `FechaEntrega` (`FechaEntrega`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!mysqli_query($conn, $createTable)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Verifica y crea la tabla de entregas_repuestos_detalle si no existe
 * @param mysqli $conn
 * @return bool
 */
function verificarTablaEntregasDetalle($conn) {
    $checkTable = "SHOW TABLES LIKE 'entregas_repuestos_detalle'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
    
    if ($resultCheck) {
        mysqli_free_result($resultCheck);
    }
    
    if (!$tablaExiste) {
        $createTable = "CREATE TABLE IF NOT EXISTS `entregas_repuestos_detalle` (
            `ID` INT(11) NOT NULL AUTO_INCREMENT,
            `EntregaID` INT(11) NOT NULL,
            `RepuestoID` INT(11) NOT NULL,
            `Cantidad` INT(11) NOT NULL,
            PRIMARY KEY (`ID`),
            KEY `EntregaID` (`EntregaID`),
            KEY `RepuestoID` (`RepuestoID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!mysqli_query($conn, $createTable)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Registra una recepción de repuestos
 * @param array $datos
 * @return array
 */
function registrarRecepcion($datos) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        // Verificar y crear tablas si no existen
        if (!verificarTablaRecepciones($conn) || !verificarTablaRecepcionesDetalle($conn)) {
            throw new Exception('Error al verificar/crear tablas de recepciones');
        }
        
        // Validar datos
        if (empty($datos['proveedor_id']) || empty($datos['numero_factura']) || empty($datos['fecha_recepcion'])) {
            throw new Exception('Faltan datos obligatorios');
        }
        
        if (empty($datos['repuestos']) || !is_array($datos['repuestos']) || count($datos['repuestos']) === 0) {
            throw new Exception('Debe seleccionar al menos un repuesto');
        }
        
        // Escapar datos
        $proveedorId = intval($datos['proveedor_id']);
        $numeroFactura = mysqli_real_escape_string($conn, trim($datos['numero_factura']));
        $fechaRecepcion = mysqli_real_escape_string($conn, $datos['fecha_recepcion']);
        $observaciones = !empty($datos['observaciones']) ? mysqli_real_escape_string($conn, trim($datos['observaciones'])) : NULL;
        $usuarioId = intval($datos['usuario_id']);
        
        // Insertar recepción
        $queryRecepcion = "INSERT INTO recepciones_repuestos 
                          (ProveedorID, NumeroFactura, FechaRecepcion, Observaciones, UsuarioID) 
                          VALUES ($proveedorId, '$numeroFactura', '$fechaRecepcion', " . 
                          ($observaciones ? "'$observaciones'" : "NULL") . ", $usuarioId)";
        
        if (!mysqli_query($conn, $queryRecepcion)) {
            throw new Exception('Error al registrar recepción: ' . mysqli_error($conn));
        }
        
        $recepcionId = mysqli_insert_id($conn);
        
        // Insertar detalles de repuestos y actualizar stock
        foreach ($datos['repuestos'] as $repuesto) {
            $repuestoId = intval($repuesto['id']);
            $cantidad = intval($repuesto['cantidad']);
            $precioUnitario = !empty($repuesto['precio']) ? floatval($repuesto['precio']) : NULL;
            
            if ($cantidad <= 0) {
                continue;
            }
            
            // Insertar detalle
            $queryDetalle = "INSERT INTO recepciones_repuestos_detalle 
                            (RecepcionID, RepuestoID, Cantidad, PrecioUnitario) 
                            VALUES ($recepcionId, $repuestoId, $cantidad, " . 
                            ($precioUnitario ? "$precioUnitario" : "NULL") . ")";
            
            if (!mysqli_query($conn, $queryDetalle)) {
                throw new Exception('Error al registrar detalle de repuesto: ' . mysqli_error($conn));
            }
            
            // Actualizar stock del repuesto
            $queryUpdateStock = "UPDATE repuestos SET Stock = Stock + $cantidad WHERE ID = $repuestoId";
            if (!mysqli_query($conn, $queryUpdateStock)) {
                throw new Exception('Error al actualizar stock: ' . mysqli_error($conn));
            }
        }
        
        mysqli_commit($conn);
        mysqli_close($conn);
        
        return ['status' => 'success', 'message' => 'Recepción registrada correctamente', 'id' => $recepcionId];
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Registra una entrega de repuestos
 * @param array $datos
 * @return array
 */
function registrarEntrega($datos) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        // Verificar y crear tablas si no existen
        if (!verificarTablaEntregas($conn) || !verificarTablaEntregasDetalle($conn)) {
            throw new Exception('Error al verificar/crear tablas de entregas');
        }
        
        // Validar datos
        if (empty($datos['vehiculo_id']) || empty($datos['mecanico_id']) || empty($datos['fecha_entrega'])) {
            throw new Exception('Faltan datos obligatorios');
        }
        
        if (empty($datos['repuestos']) || !is_array($datos['repuestos']) || count($datos['repuestos']) === 0) {
            throw new Exception('Debe seleccionar al menos un repuesto');
        }
        
        // Verificar stock disponible antes de entregar
        foreach ($datos['repuestos'] as $repuesto) {
            $repuestoId = intval($repuesto['id']);
            $cantidad = intval($repuesto['cantidad']);
            
            $queryStock = "SELECT Stock FROM repuestos WHERE ID = $repuestoId";
            $resultStock = mysqli_query($conn, $queryStock);
            
            if (!$resultStock || mysqli_num_rows($resultStock) === 0) {
                throw new Exception('Repuesto no encontrado');
            }
            
            $row = mysqli_fetch_assoc($resultStock);
            $stockDisponible = intval($row['Stock']);
            
            if ($stockDisponible < $cantidad) {
                throw new Exception("Stock insuficiente para el repuesto seleccionado. Disponible: $stockDisponible, Solicitado: $cantidad");
            }
            
            mysqli_free_result($resultStock);
        }
        
        // Escapar datos
        $vehiculoId = intval($datos['vehiculo_id']);
        $mecanicoId = intval($datos['mecanico_id']);
        $fechaEntrega = mysqli_real_escape_string($conn, $datos['fecha_entrega']);
        $observaciones = !empty($datos['observaciones']) ? mysqli_real_escape_string($conn, trim($datos['observaciones'])) : NULL;
        $usuarioId = intval($datos['usuario_id']);
        
        // Insertar entrega
        $queryEntrega = "INSERT INTO entregas_repuestos 
                        (VehiculoID, MecanicoID, FechaEntrega, Observaciones, UsuarioID) 
                        VALUES ($vehiculoId, $mecanicoId, '$fechaEntrega', " . 
                        ($observaciones ? "'$observaciones'" : "NULL") . ", $usuarioId)";
        
        if (!mysqli_query($conn, $queryEntrega)) {
            throw new Exception('Error al registrar entrega: ' . mysqli_error($conn));
        }
        
        $entregaId = mysqli_insert_id($conn);
        
        // Insertar detalles y actualizar stock
        foreach ($datos['repuestos'] as $repuesto) {
            $repuestoId = intval($repuesto['id']);
            $cantidad = intval($repuesto['cantidad']);
            
            if ($cantidad <= 0) {
                continue;
            }
            
            // Insertar detalle
            $queryDetalle = "INSERT INTO entregas_repuestos_detalle 
                            (EntregaID, RepuestoID, Cantidad) 
                            VALUES ($entregaId, $repuestoId, $cantidad)";
            
            if (!mysqli_query($conn, $queryDetalle)) {
                throw new Exception('Error al registrar detalle de entrega: ' . mysqli_error($conn));
            }
            
            // Actualizar stock (reducir)
            $queryUpdateStock = "UPDATE repuestos SET Stock = Stock - $cantidad WHERE ID = $repuestoId";
            if (!mysqli_query($conn, $queryUpdateStock)) {
                throw new Exception('Error al actualizar stock: ' . mysqli_error($conn));
            }
        }
        
        mysqli_commit($conn);
        mysqli_close($conn);
        
        return ['status' => 'success', 'message' => 'Entrega registrada correctamente', 'id' => $entregaId];
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * Obtiene el historial de recepciones y entregas
 * @return array
 */
function obtenerHistorial() {
    try {
        $conn = conectarPepsicoSeguro();
        if (!$conn) {
            return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
        }
        
        $historial = [];
        
        // Obtener recepciones
        $checkTable = "SHOW TABLES LIKE 'recepciones_repuestos'";
        $resultCheck = mysqli_query($conn, $checkTable);
        $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
        
        if ($resultCheck) {
            mysqli_free_result($resultCheck);
        }
        
        if ($tablaExiste) {
            // Verificar nombre de tabla usuarios
            $checkUsuarios = "SHOW TABLES LIKE 'usuarios'";
            $resultUsuarios = mysqli_query($conn, $checkUsuarios);
            $tablaUsuarios = ($resultUsuarios && mysqli_num_rows($resultUsuarios) > 0) ? 'usuarios' : 'USUARIOS';
            if ($resultUsuarios) {
                mysqli_free_result($resultUsuarios);
            }
            
            $queryRecepciones = "SELECT 
                                    r.ID,
                                    r.FechaRecepcion as Fecha,
                                    'Recepción' as Tipo,
                                    p.Nombre as ProveedorVehiculo,
                                    r.NumeroFactura as Referencia,
                                    r.Observaciones,
                                    u.NombreUsuario as UsuarioNombre,
                                    GROUP_CONCAT(CONCAT(rep.Nombre, ' (', rd.Cantidad, ')') SEPARATOR ', ') as Repuestos,
                                    SUM(rd.Cantidad) as CantidadTotal
                                FROM recepciones_repuestos r
                                INNER JOIN proveedores p ON r.ProveedorID = p.ID
                                LEFT JOIN $tablaUsuarios u ON r.UsuarioID = u.UsuarioID
                                LEFT JOIN recepciones_repuestos_detalle rd ON r.ID = rd.RecepcionID
                                LEFT JOIN repuestos rep ON rd.RepuestoID = rep.ID
                                GROUP BY r.ID
                                ORDER BY r.FechaRecepcion DESC";
            
            $result = mysqli_query($conn, $queryRecepciones);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $historial[] = $row;
                }
                mysqli_free_result($result);
            }
        }
        
        // Obtener entregas
        $checkTable = "SHOW TABLES LIKE 'entregas_repuestos'";
        $resultCheck = mysqli_query($conn, $checkTable);
        $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
        
        if ($resultCheck) {
            mysqli_free_result($resultCheck);
        }
        
        if ($tablaExiste) {
            // Verificar nombre de tabla usuarios
            $checkUsuarios = "SHOW TABLES LIKE 'usuarios'";
            $resultUsuarios = mysqli_query($conn, $checkUsuarios);
            $tablaUsuarios = ($resultUsuarios && mysqli_num_rows($resultUsuarios) > 0) ? 'usuarios' : 'USUARIOS';
            if ($resultUsuarios) {
                mysqli_free_result($resultUsuarios);
            }
            
            $queryEntregas = "SELECT 
                                e.ID,
                                e.FechaEntrega as Fecha,
                                'Entrega' as Tipo,
                                CONCAT(v.Marca, ' ', v.Modelo, ' - ', v.Placa) as ProveedorVehiculo,
                                CONCAT('Mecánico: ', u2.NombreUsuario) as Referencia,
                                e.Observaciones,
                                u.NombreUsuario as UsuarioNombre,
                                GROUP_CONCAT(CONCAT(rep.Nombre, ' (', ed.Cantidad, ')') SEPARATOR ', ') as Repuestos,
                                SUM(ed.Cantidad) as CantidadTotal
                            FROM entregas_repuestos e
                            INNER JOIN ingreso_vehiculos v ON e.VehiculoID = v.ID
                            LEFT JOIN $tablaUsuarios u ON e.UsuarioID = u.UsuarioID
                            LEFT JOIN $tablaUsuarios u2 ON e.MecanicoID = u2.UsuarioID
                            LEFT JOIN entregas_repuestos_detalle ed ON e.ID = ed.EntregaID
                            LEFT JOIN repuestos rep ON ed.RepuestoID = rep.ID
                            GROUP BY e.ID
                            ORDER BY e.FechaEntrega DESC";
            
            $result = mysqli_query($conn, $queryEntregas);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $historial[] = $row;
                }
                mysqli_free_result($result);
            }
        }
        
        // Ordenar por fecha descendente
        usort($historial, function($a, $b) {
            return strtotime($b['Fecha']) - strtotime($a['Fecha']);
        });
        
        mysqli_close($conn);
        return ['status' => 'success', 'data' => $historial];
    } catch (Exception $e) {
        if (isset($conn)) {
            mysqli_close($conn);
        }
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
}

