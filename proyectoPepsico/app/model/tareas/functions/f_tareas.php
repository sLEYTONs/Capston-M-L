<?php
require_once __DIR__ . '../../../../config/conexion.php';

function obtenerTareasMecanico($mecanico_id) {
    error_log("=== obtenerTareasMecanico ===");
    error_log("Mecánico ID recibido: " . $mecanico_id);
    
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos");
        return [];
    }

    $mecanico_id = mysqli_real_escape_string($conn, $mecanico_id);
    error_log("Mecánico ID escapado: " . $mecanico_id);

    $query = "SELECT 
                a.ID AS AsignacionID,
                v.ID AS VehiculoID,
                v.Placa,
                v.TipoVehiculo,
                v.Marca,
                v.Modelo,
                v.Color,
                v.ConductorNombre,
                v.EmpresaNombre,
                DATE_FORMAT(a.FechaAsignacion, '%d/%m/%Y %H:%i') as FechaAsignacion,
                a.Estado,
                a.Observaciones,
                (SELECT DATE_FORMAT(am.FechaAvance, '%d/%m/%Y %H:%i') 
                 FROM avances_mecanico am 
                 WHERE am.AsignacionID = a.ID 
                 ORDER BY am.FechaAvance DESC LIMIT 1) as UltimaFechaAvance,
                (SELECT am.Descripcion 
                 FROM avances_mecanico am 
                 WHERE am.AsignacionID = a.ID 
                 ORDER BY am.FechaAvance DESC LIMIT 1) as UltimaDescripcion
            FROM asignaciones_mecanico a
            INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
            WHERE a.MecanicoID = '$mecanico_id'
            ORDER BY a.FechaAsignacion DESC";

    error_log("Query ejecutada: " . $query);

    $result = mysqli_query($conn, $query);

    if (!$result) {
        error_log("Error en consulta obtenerTareasMecanico: " . mysqli_error($conn));
        mysqli_close($conn);
        return [];
    }

    $tareas = [];
    $count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $count++;
        // Formatear último avance
        $row['UltimoAvance'] = null;
        if ($row['UltimaFechaAvance'] && $row['UltimaDescripcion']) {
            $row['UltimoAvance'] = [
                'Fecha' => $row['UltimaFechaAvance'],
                'Descripcion' => $row['UltimaDescripcion']
            ];
        }
        $tareas[] = $row;
        
        // Log de la primera tarea para debug
        if ($count === 1) {
            error_log("Primera tarea encontrada: " . json_encode($row));
        }
    }

    error_log("Total de tareas encontradas: " . $count);
    mysqli_free_result($result);
    mysqli_close($conn);
    return $tareas;
}
function obtenerDetallesAsignacion($asignacion_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return null;
    }

    $asignacion_id = mysqli_real_escape_string($conn, $asignacion_id);

    $query = "SELECT 
                a.ID,
                v.ID AS VehiculoID,
                v.Placa,
                v.TipoVehiculo,
                v.Marca,
                v.Modelo,
                v.Color,
                v.ConductorNombre,
                v.EmpresaNombre,
                a.Estado,
                a.Observaciones
            FROM asignaciones_mecanico a
            INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
            WHERE a.ID = '$asignacion_id'";

    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("Error en consulta obtenerDetallesAsignacion: " . mysqli_error($conn));
        mysqli_close($conn);
        return null;
    }

    $asignacion = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_close($conn);
    
    return $asignacion;
}

function obtenerHistorialAvances($asignacion_id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['vehiculo' => null, 'avances' => []];
    }

    $asignacion_id = mysqli_real_escape_string($conn, $asignacion_id);

    // Obtener información del vehículo
    $queryVehiculo = "SELECT 
                        v.Placa,
                        v.Marca,
                        v.Modelo
                    FROM asignaciones_mecanico a
                    INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
                    WHERE a.ID = '$asignacion_id'";
    
    $resultVehiculo = mysqli_query($conn, $queryVehiculo);
    
    if (!$resultVehiculo) {
        error_log("Error en consulta obtenerHistorialAvances (vehiculo): " . mysqli_error($conn));
        mysqli_close($conn);
        return ['vehiculo' => null, 'avances' => []];
    }
    
    $vehiculo = mysqli_fetch_assoc($resultVehiculo);
    mysqli_free_result($resultVehiculo);

    // Obtener avances
    $queryAvances = "SELECT 
                        Descripcion,
                        Estado,
                        DATE_FORMAT(FechaAvance, '%d/%m/%Y %H:%i') as FechaAvance
                    FROM avances_mecanico 
                    WHERE AsignacionID = '$asignacion_id' 
                    ORDER BY FechaAvance DESC";
    
    $resultAvances = mysqli_query($conn, $queryAvances);
    
    $avances = [];
    if ($resultAvances) {
        while ($row = mysqli_fetch_assoc($resultAvances)) {
            $avances[] = $row;
        }
        mysqli_free_result($resultAvances);
    } else {
        error_log("Error en consulta obtenerHistorialAvances (avances): " . mysqli_error($conn));
    }

    mysqli_close($conn);
    return [
        'vehiculo' => $vehiculo,
        'avances' => $avances
    ];
}

function registrarAvance($asignacion_id, $descripcion, $estado) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión'];
    }

    // Escapar datos
    $asignacion_id = mysqli_real_escape_string($conn, $asignacion_id);
    $descripcion = mysqli_real_escape_string($conn, $descripcion);
    $estado = mysqli_real_escape_string($conn, $estado);

    mysqli_begin_transaction($conn);

    try {
        // 1. Insertar el avance
        $queryAvance = "INSERT INTO avances_mecanico (AsignacionID, Descripcion, Estado) VALUES ('$asignacion_id', '$descripcion', '$estado')";
        $resultAvance = mysqli_query($conn, $queryAvance);

        if (!$resultAvance) {
            throw new Exception('Error al insertar avance: ' . mysqli_error($conn));
        }

        // 2. Actualizar el estado de la asignación
        $queryAsignacion = "UPDATE asignaciones_mecanico SET Estado = '$estado' WHERE ID = '$asignacion_id'";
        $resultAsignacion = mysqli_query($conn, $queryAsignacion);

        if (!$resultAsignacion) {
            throw new Exception('Error al actualizar asignación: ' . mysqli_error($conn));
        }

        // 3. Si el estado es "Completado", actualizar el estado del vehículo
        if ($estado === 'Completado') {
            // Primero obtener el VehiculoID
            $queryGetVehiculo = "SELECT VehiculoID FROM asignaciones_mecanico WHERE ID = '$asignacion_id'";
            $resultVehiculo = mysqli_query($conn, $queryGetVehiculo);
            
            if ($resultVehiculo && $row = mysqli_fetch_assoc($resultVehiculo)) {
                $vehiculo_id = $row['VehiculoID'];
                $queryUpdateVehiculo = "UPDATE ingreso_vehiculos SET Estado = 'Completado' WHERE ID = '$vehiculo_id'";
                $resultUpdateVehiculo = mysqli_query($conn, $queryUpdateVehiculo);
                
                if (!$resultUpdateVehiculo) {
                    throw new Exception('Error al actualizar estado del vehículo: ' . mysqli_error($conn));
                }
            }
            mysqli_free_result($resultVehiculo);
        }

        mysqli_commit($conn);
        return ['status' => 'success', 'message' => 'Avance registrado correctamente'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['status' => 'error', 'message' => $e->getMessage()];
    } finally {
        mysqli_close($conn);
    }
}
?>