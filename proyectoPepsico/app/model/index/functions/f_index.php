<?php
require_once __DIR__ . '../../../../config/conexion.php';

function obtenerVehiculo() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        error_log("Error: No se pudo conectar a la base de datos en obtenerVehiculo");
        return array();
    }

    $query = "  SELECT  ID,
                        Placa,
                        TipoVehiculo,
                        Marca,
                        Modelo,
                        Anio,
                        ConductorNombre,
                        ConductorTelefono,
                        FechaIngreso,
                        Proposito,
                        Area,
                        PersonaContacto,
                        Observaciones,
                        Estado,
                        FechaRegistro
                FROM ingreso_vehiculos
                WHERE Estado = 'active'";

    $result = mysqli_query($conn, $query);
    $data = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        mysqli_free_result($result);
    } else {
        error_log("Error en consulta obtenerVehiculo: " . mysqli_error($conn));
    }

    mysqli_close($conn);
    return $data;
}

function ingresoVehiculo($vehiculo) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'No se pudo conectar a la base de datos'];
    }

    // Verificar si la placa ya existe
    $placa = mysqli_real_escape_string($conn, $vehiculo['Placa']);
    $checkQuery = "SELECT ID FROM ingreso_vehiculos WHERE Placa = '$placa' AND Estado = 'active'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'La placa del vehículo ya existe en el sistema'];
    }

    // Escapar todos los valores
    $tipoVehiculo = mysqli_real_escape_string($conn, $vehiculo['TipoVehiculo']);
    $marca = mysqli_real_escape_string($conn, $vehiculo['Marca']);
    $modelo = mysqli_real_escape_string($conn, $vehiculo['Modelo']);
    $anio = mysqli_real_escape_string($conn, $vehiculo['Anio']);
    $conductorNombre = mysqli_real_escape_string($conn, $vehiculo['ConductorNombre']);
    $conductorTelefono = mysqli_real_escape_string($conn, $vehiculo['ConductorTelefono']);
    $proposito = mysqli_real_escape_string($conn, $vehiculo['Proposito']);
    $area = mysqli_real_escape_string($conn, $vehiculo['Area']);
    $personaContacto = mysqli_real_escape_string($conn, $vehiculo['PersonaContacto']);
    $observaciones = mysqli_real_escape_string($conn, $vehiculo['Observaciones']);
    
    // Manejar FechaIngreso
    $fechaIngreso = mysqli_real_escape_string($conn, $vehiculo['FechaIngreso'] ?? date('Y-m-d H:i:s'));

    // Construir consulta adaptada a la nueva estructura
    $query = "
        INSERT INTO ingreso_vehiculos (
            Placa, 
            TipoVehiculo, 
            Marca, 
            Modelo, 
            Anio,
            ConductorNombre, 
            ConductorTelefono, 
            FechaIngreso,
            Proposito, 
            Area,
            PersonaContacto, 
            Observaciones,
            Estado
        ) VALUES (
            '$placa',
            '$tipoVehiculo',
            '$marca',
            '$modelo',
            " . ($anio ? "'$anio'" : "NULL") . ",
            '$conductorNombre',
            " . ($conductorTelefono ? "'$conductorTelefono'" : "DEFAULT") . ",
            '$fechaIngreso',
            '$proposito',
            " . ($area ? "'$area'" : "DEFAULT") . ",
            " . ($personaContacto ? "'$personaContacto'" : "DEFAULT") . ",
            " . ($observaciones ? "'$observaciones'" : "NULL") . ",
            'active'
        )
    ";

    error_log("Consulta SQL ejecutada: " . $query);

    if (mysqli_query($conn, $query)) {
        $id_insertado = mysqli_insert_id($conn);
        $response = [
            'status' => 'success', 
            'message' => 'Vehículo registrado correctamente',
            'id' => $id_insertado
        ];
    } else {
        $error = mysqli_error($conn);
        error_log("Error en ingresoVehiculo: " . $error);
        $response = ['status' => 'error', 'message' => 'Error al insertar: ' . $error];
    }

    mysqli_close($conn);
    return $response;
}

// Nueva función para actualizar estado de vehículo
function actualizarEstadoVehiculo($id, $estado) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'No se pudo conectar a la base de datos'];
    }

    $id = mysqli_real_escape_string($conn, $id);
    $estado = mysqli_real_escape_string($conn, $estado);

    $query = "UPDATE ingreso_vehiculos SET Estado = '$estado' WHERE ID = '$id'";

    if (mysqli_query($conn, $query)) {
        $response = ['status' => 'success', 'message' => 'Estado actualizado correctamente'];
    } else {
        $error = mysqli_error($conn);
        $response = ['status' => 'error', 'message' => 'Error al actualizar: ' . $error];
    }

    mysqli_close($conn);
    return $response;
}
?>