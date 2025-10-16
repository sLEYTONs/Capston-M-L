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
?>