<?php
require_once __DIR__ . '../../../../config/conexion.php';
require_once '../../../../pages/general/funciones_notificaciones.php';

function buscarVehiculos($filtros) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos en buscarVehiculos");
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Construir la consulta base
    $query = "SELECT 
                ID,
                Placa,
                TipoVehiculo,
                Marca,
                Modelo,
                Color,
                Anio,
                ConductorNombre,
                ConductorTelefono,
                DATE_FORMAT(FechaIngreso, '%d/%m/%Y %H:%i') as FechaIngresoFormateada,
                Proposito,
                Area,
                PersonaContacto,
                Observaciones,
                Estado,
                EstadoIngreso,
                Kilometraje,
                DATE_FORMAT(FechaRegistro, '%d/%m/%Y %H:%i') as FechaRegistroFormateada
            FROM ingreso_vehiculos 
            WHERE 1=1";

    $params = [];

    // Aplicar filtros
    if (!empty($filtros['placa'])) {
        $placa = mysqli_real_escape_string($conn, $filtros['placa']);
        $query .= " AND Placa LIKE ?";
        $params[] = "%$placa%";
    }

    if (!empty($filtros['conductor'])) {
        $conductor = mysqli_real_escape_string($conn, $filtros['conductor']);
        $query .= " AND ConductorNombre LIKE ?";
        $params[] = "%$conductor%";
    }

    if (!empty($filtros['fecha'])) {
        $fecha = mysqli_real_escape_string($conn, $filtros['fecha']);
        $query .= " AND DATE(FechaIngreso) = ?";
        $params[] = $fecha;
    }

    // Ordenar por fecha de ingreso descendente
    $query .= " ORDER BY FechaIngreso DESC";

    // Preparar y ejecutar la consulta
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt && !empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    $result = [];
    if ($stmt && mysqli_stmt_execute($stmt)) {
        $resultado = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($resultado)) {
            $result[] = $row;
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error en consulta buscarVehiculos: " . mysqli_error($conn));
    }

    mysqli_close($conn);
    return $result;
}

function obtenerVehiculoPorID($id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos en obtenerVehiculoPorID");
        return null;
    }

    $query = "SELECT 
                ID,
                Placa,
                TipoVehiculo,
                Marca,
                Modelo,
                Fotos,
                Color,
                Anio,
                ConductorNombre,
                ConductorTelefono,
                DATE_FORMAT(FechaIngreso, '%d/%m/%Y %H:%i') as FechaIngresoFormateada,
                Proposito,
                Area,
                PersonaContacto,
                Observaciones,
                Estado,
                EstadoIngreso,
                Kilometraje,
                DATE_FORMAT(FechaRegistro, '%d/%m/%Y %H:%i') as FechaRegistroFormateada
            FROM ingreso_vehiculos 
            WHERE ID = ?";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $vehiculo = null;
    if ($row = mysqli_fetch_assoc($result)) {
        // Decodificar JSON de fotos si existe
        if (!empty($row['Fotos'])) {
            $row['Fotos'] = json_decode($row['Fotos'], true);
        } else {
            $row['Fotos'] = [];
        }
        $vehiculo = $row;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $vehiculo;
}

function obtenerTodosLosVehiculos() {
    return buscarVehiculos([]);
}

function actualizarVehiculo($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos en actualizarVehiculo");
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Verificar si la placa ya existe en otro vehículo
    if (!empty($datos['Placa'])) {
        $checkQuery = "SELECT ID FROM ingreso_vehiculos WHERE Placa = ? AND ID != ?";
        $stmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($stmt, 'si', $datos['Placa'], $datos['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return ['status' => 'error', 'message' => 'La placa ya existe en otro vehículo'];
        }
        mysqli_stmt_close($stmt);
    }

    // Construir la consulta de actualización
    $query = "UPDATE ingreso_vehiculos SET 
                Placa = ?,
                TipoVehiculo = ?,
                Marca = ?,
                Modelo = ?,
                Color = ?,
                Anio = ?,
                ConductorNombre = ?,
                ConductorTelefono = ?,
                Proposito = ?,
                Area = ?,
                PersonaContacto = ?,
                Observaciones = ?,
                Estado = ?
            WHERE ID = ?";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error en prepare: ' . mysqli_error($conn)];
    }

    // Bind parameters - USANDO LOS NOMBRES CORRECTOS
    mysqli_stmt_bind_param(
        $stmt,
        'sssssississsssi',
        $datos['Placa'],
        $datos['TipoVehiculo'],
        $datos['Marca'],
        $datos['Modelo'],
        $datos['Color'],
        $datos['Anio'],
        $datos['ConductorNombre'],
        $datos['ConductorTelefono'],
        $datos['Proposito'],
        $datos['Area'],
        $datos['PersonaContacto'],
        $datos['Observaciones'],
        $datos['Estado'],
        $datos['id']
    );

    if (mysqli_stmt_execute($stmt)) {
        $response = ['status' => 'success', 'message' => 'Vehículo actualizado correctamente'];
    } else {
        $response = ['status' => 'error', 'message' => 'Error al actualizar: ' . mysqli_stmt_error($stmt)];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $response;
}

// Nuevas funciones para mecánicos
function obtenerMecanicosDisponibles() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    $query = "SELECT UsuarioID, NombreUsuario, Correo 
              FROM usuarios 
              WHERE Rol = 'Mecanico' AND Estado = 1 
              ORDER BY NombreUsuario";
    $result = mysqli_query($conn, $query);
    $mecanicos = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $mecanicos[] = $row;
    }

    mysqli_close($conn);
    return $mecanicos;
}

function asignarMecanico($vehiculo_id, $mecanico_id, $observaciones) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    mysqli_begin_transaction($conn);

    try {
        // 1. Obtener información del vehículo antes de actualizar
        $selectVehiculo = "SELECT Placa, Marca, Modelo, TipoVehiculo FROM ingreso_vehiculos WHERE ID = ?";
        $stmt_select = mysqli_prepare($conn, $selectVehiculo);
        mysqli_stmt_bind_param($stmt_select, 'i', $vehiculo_id);
        mysqli_stmt_execute($stmt_select);
        $result_vehiculo = mysqli_stmt_get_result($stmt_select);
        $vehiculo = mysqli_fetch_assoc($result_vehiculo);
        mysqli_stmt_close($stmt_select);

        // 2. Actualizar estado del vehículo
        $updateVehiculo = "UPDATE ingreso_vehiculos SET Estado = 'Asignado' WHERE ID = ?";
        $stmt = mysqli_prepare($conn, $updateVehiculo);
        mysqli_stmt_bind_param($stmt, 'i', $vehiculo_id);
        mysqli_stmt_execute($stmt);

        // 3. Crear asignación
        $insertAsignacion = "INSERT INTO asignaciones_mecanico 
                            (VehiculoID, MecanicoID, Observaciones) 
                            VALUES (?, ?, ?)";
        $stmt2 = mysqli_prepare($conn, $insertAsignacion);
        mysqli_stmt_bind_param($stmt2, 'iis', $vehiculo_id, $mecanico_id, $observaciones);
        mysqli_stmt_execute($stmt2);
        $asignacion_id = mysqli_insert_id($conn);

        mysqli_commit($conn);
        
        // 4. Notificar al mecánico después de la asignación exitosa
        if ($vehiculo && $asignacion_id) {
            notificarAsignacionMecanico($mecanico_id, $vehiculo, $observaciones, $asignacion_id);
        }
        
        return ['status' => 'success', 'message' => 'Mecánico asignado correctamente'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => 'error', 'message' => 'Error al asignar: ' . $e->getMessage()];
    } finally {
        mysqli_close($conn);
    }
}

function obtenerAsignacionActiva($vehiculo_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return null;
    }

    $query = "SELECT 
                a.ID,
                a.VehiculoID,
                a.MecanicoID,
                u.NombreUsuario as MecanicoNombre,
                a.Observaciones,
                a.Estado,
                DATE_FORMAT(a.FechaAsignacion, '%d/%m/%Y %H:%i') as FechaAsignacion
            FROM asignaciones_mecanico a
            INNER JOIN usuarios u ON a.MecanicoID = u.UsuarioID
            WHERE a.VehiculoID = ? 
            ORDER BY a.FechaAsignacion DESC LIMIT 1";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $vehiculo_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $asignacion = mysqli_fetch_assoc($result);

    mysqli_close($conn);
    return $asignacion;
}

/**
 * Notifica al mecánico sobre una nueva tarea asignada
 */
function notificarAsignacionMecanico($mecanico_id, $vehiculo, $observaciones, $asignacion_id) {
    if (empty($mecanico_id) || empty($vehiculo)) {
        error_log("Error: Datos incompletos para notificar asignación al mecánico");
        return false;
    }
    
    // Crear mensaje de notificación
    $titulo = "Nueva Tarea Asignada";
    $mensaje = "Se te ha asignado el vehículo {$vehiculo['Placa']} - {$vehiculo['Marca']} {$vehiculo['Modelo']}";
    if (!empty($observaciones)) {
        $mensaje .= ". Observaciones: " . substr($observaciones, 0, 100);
        if (strlen($observaciones) > 100) {
            $mensaje .= "...";
        }
    }
    $modulo = "tareas";
    $enlace = "tareas.php";
    
    // Crear notificación para el mecánico específico
    $resultado = crearNotificacion([$mecanico_id], $titulo, $mensaje, $modulo, $enlace);
    
    if ($resultado) {
        error_log("Notificación enviada al mecánico ID: {$mecanico_id} para vehículo {$vehiculo['Placa']}");
    } else {
        error_log("Error al enviar notificación al mecánico ID: {$mecanico_id}");
    }
    
    return $resultado;
}

function obtenerAvancesMecanico($asignacion_id) {
    if (!$asignacion_id) return [];

    $conn = conectar_Pepsico();
    if (!$conn) {
        return [];
    }

    // Verificar si existe la columna Fotos
    $checkColumn = "SHOW COLUMNS FROM avances_mecanico LIKE 'Fotos'";
    $resultCheck = mysqli_query($conn, $checkColumn);
    $columnaFotosExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);

    $query = "SELECT 
                ID,
                Descripcion,
                Estado,
                " . ($columnaFotosExiste ? "Fotos," : "NULL AS Fotos,") . "
                DATE_FORMAT(FechaAvance, '%d/%m/%Y %H:%i') as FechaAvance,
                DATE_FORMAT(FechaAvance, '%Y-%m-%d %H:%i:%s') as FechaAvanceRaw
            FROM avances_mecanico 
            WHERE AsignacionID = ? 
            ORDER BY FechaAvance DESC";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $asignacion_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $avances = [];

    while ($row = mysqli_fetch_assoc($result)) {
        // Decodificar JSON de fotos si existe
        if (!empty($row['Fotos']) && $row['Fotos'] !== 'NULL' && $row['Fotos'] !== null) {
            $fotosDecoded = json_decode($row['Fotos'], true);
            $row['Fotos'] = is_array($fotosDecoded) ? $fotosDecoded : [];
        } else {
            $row['Fotos'] = [];
        }
        $avances[] = $row;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $avances;
}
?>