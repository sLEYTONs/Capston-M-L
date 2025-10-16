<?php
require_once __DIR__ . '../../../../config/conexion.php';

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
                ConductorCedula,
                ConductorTelefono,
                Licencia,
                EmpresaCodigo,
                EmpresaNombre,
                DATE_FORMAT(FechaIngreso, '%d/%m/%Y %H:%i') as FechaIngresoFormateada,
                Proposito,
                Area,
                PersonaContacto,
                Observaciones,
                Estado,
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
                Color,
                Anio,
                ConductorNombre,
                ConductorCedula,
                ConductorTelefono,
                Licencia,
                EmpresaCodigo,
                EmpresaNombre,
                DATE_FORMAT(FechaIngreso, '%d/%m/%Y %H:%i') as FechaIngresoFormateada,
                Proposito,
                Area,
                PersonaContacto,
                Observaciones,
                Estado,
                DATE_FORMAT(FechaRegistro, '%d/%m/%Y %H:%i') as FechaRegistroFormateada
            FROM ingreso_vehiculos 
            WHERE ID = ?";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $vehiculo = null;
    if ($row = mysqli_fetch_assoc($result)) {
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
                ConductorCedula = ?,
                ConductorTelefono = ?,
                Licencia = ?,
                EmpresaCodigo = ?,
                EmpresaNombre = ?,
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
        'sssssisssssssssssi',
        $datos['Placa'],
        $datos['TipoVehiculo'],
        $datos['Marca'],
        $datos['Modelo'],
        $datos['Color'],
        $datos['Anio'],
        $datos['ConductorNombre'],
        $datos['ConductorCedula'],
        $datos['ConductorTelefono'],
        $datos['Licencia'],
        $datos['EmpresaCodigo'],
        $datos['EmpresaNombre'],
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

?>