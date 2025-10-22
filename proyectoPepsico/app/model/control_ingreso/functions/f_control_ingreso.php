<?php
require_once '../../../config/conexion.php';

/**
 * Busca un vehículo por placa o cédula del conductor
 */
function buscarVehiculo($tipo, $valor) {
    $conn = conectar_Pepsico();
    
    if ($tipo === 'placa') {
        $sql = "SELECT * FROM ingreso_vehiculos WHERE Placa = ? AND Estado = 'active'";
    } else {
        $sql = "SELECT * FROM ingreso_vehiculos WHERE ConductorCedula = ? AND Estado = 'active'";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $valor);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vehiculo = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $vehiculo;
}

/**
 * Obtiene estadísticas del patio
 */
function obtenerEstadisticasPatio() {
    $conn = conectar_Pepsico();
    
    // Vehículos activos
    $sql1 = "SELECT COUNT(*) as total FROM ingreso_vehiculos WHERE Estado = 'active'";
    $result1 = $conn->query($sql1);
    $vehiculosActivos = $result1->fetch_assoc()['total'];
    
    // Ingresos de hoy
    $sql2 = "SELECT COUNT(*) as total FROM ingreso_vehiculos 
             WHERE DATE(FechaIngreso) = CURDATE() AND Estado = 'active'";
    $result2 = $conn->query($sql2);
    $ingresosHoy = $result2->fetch_assoc()['total'];
    
    // Novedades pendientes
    $sql3 = "SELECT COUNT(*) as total FROM novedades_guardia WHERE Estado = 'Pendiente'";
    $result3 = $conn->query($sql3);
    $novedadesPendientes = $result3->fetch_assoc()['total'];
    
    $conn->close();
    
    return [
        'vehiculosActivos' => $vehiculosActivos,
        'ingresosHoy' => $ingresosHoy,
        'novedadesPendientes' => $novedadesPendientes
    ];
}

/**
 * Reporta una novedad/incidente
 */
function reportarNovedad($placa, $tipo, $descripcion, $gravedad, $usuario_id) {
    $conn = conectar_Pepsico();
    
    $sql = "INSERT INTO novedades_guardia (Placa, Tipo, Descripcion, Gravedad, UsuarioReporta) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $placa, $tipo, $descripcion, $gravedad, $usuario_id);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Obtiene novedades recientes
 */
function obtenerNovedadesRecientes() {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT ng.*, u.NombreUsuario as Reportador, iv.ConductorNombre
            FROM novedades_guardia ng 
            LEFT JOIN USUARIOS u ON ng.UsuarioReporta = u.UsuarioID 
            LEFT JOIN ingreso_vehiculos iv ON ng.Placa = iv.Placa
            WHERE ng.FechaReporte >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY ng.FechaReporte DESC 
            LIMIT 10";
    
    $result = $conn->query($sql);
    $novedades = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $novedades[] = [
                'placa' => $row['Placa'],
                'tipo' => $row['Tipo'],
                'descripcion' => $row['Descripcion'],
                'gravedad' => $row['Gravedad'],
                'fecha' => $row['FechaReporte'],
                'reportador' => $row['Reportador'],
                'conductor' => $row['ConductorNombre'],
                'estado' => $row['Estado']
            ];
        }
    }
    
    $conn->close();
    
    return $novedades;
}

/**
 * Guarda foto del vehículo
 */
function guardarFotoVehiculo($placa, $fotoData, $usuario_id) {
    $conn = conectar_Pepsico();
    
    // Primero obtenemos las fotos actuales
    $sqlSelect = "SELECT Fotos FROM ingreso_vehiculos WHERE Placa = ?";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->bind_param("s", $placa);
    $stmtSelect->execute();
    $result = $stmtSelect->get_result();
    $row = $result->fetch_assoc();
    
    $fotosActuales = [];
    if ($row && $row['Fotos']) {
        $fotosActuales = json_decode($row['Fotos'], true) ?? [];
    }
    
    // Agregar nueva foto
    $nuevaFoto = [
        'foto' => $fotoData,
        'fecha' => date('Y-m-d H:i:s'),
        'usuario' => $usuario_id,
        'tipo' => 'registro_guardia'
    ];
    
    $fotosActuales[] = $nuevaFoto;
    
    // Actualizar en base de datos
    $fotosJson = json_encode($fotosActuales);
    
    $sqlUpdate = "UPDATE ingreso_vehiculos SET Fotos = ? WHERE Placa = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ss", $fotosJson, $placa);
    $result = $stmtUpdate->execute();
    
    $stmtSelect->close();
    $stmtUpdate->close();
    $conn->close();
    
    return $result;
}

/**
 * Registra ingreso de vehículo
 */
function registrarIngresoVehiculo($datos) {
    $conn = conectar_Pepsico();
    
    $sql = "INSERT INTO ingreso_vehiculos (
        Placa, TipoVehiculo, Marca, Modelo, Color, Anio, 
        ConductorNombre, ConductorCedula, ConductorTelefono, Licencia,
        EmpresaCodigo, EmpresaNombre, Proposito, Area, PersonaContacto,
        Observaciones, EstadoIngreso, Kilometraje, Combustible, Documentos,
        UsuarioRegistro
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssisssssssssssissi",
        $datos['placa'],
        $datos['tipo_vehiculo'],
        $datos['marca'],
        $datos['modelo'],
        $datos['color'],
        $datos['anio'],
        $datos['conductor_nombre'],
        $datos['conductor_cedula'],
        $datos['conductor_telefono'],
        $datos['licencia'],
        $datos['empresa_codigo'],
        $datos['empresa_nombre'],
        $datos['proposito'],
        $datos['area'],
        $datos['persona_contacto'],
        $datos['observaciones'],
        $datos['estado_ingreso'],
        $datos['kilometraje'],
        $datos['combustible'],
        $datos['documentos'],
        $datos['usuario_registro']
    );
    
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Registra salida de vehículo
 */
function registrarSalidaVehiculo($placa, $usuario_id) {
    $conn = conectar_Pepsico();
    
    $sql = "UPDATE ingreso_vehiculos SET Estado = 'inactive', FechaSalida = NOW() WHERE Placa = ? AND Estado = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $placa);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Obtiene historial de ingresos recientes
 */
function obtenerIngresosRecientes() {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT Placa, ConductorNombre, EmpresaNombre, Proposito, FechaIngreso 
            FROM ingreso_vehiculos 
            WHERE Estado = 'active' 
            ORDER BY FechaIngreso DESC 
            LIMIT 5";
    
    $result = $conn->query($sql);
    $ingresos = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ingresos[] = $row;
        }
    }
    
    $conn->close();
    
    return $ingresos;
}

/**
 * Verifica si un vehículo ya está registrado y activo
 */
function verificarVehiculoActivo($placa) {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT COUNT(*) as total FROM ingreso_vehiculos WHERE Placa = ? AND Estado = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $placa);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['total'] > 0;
}
?>