<?php
require_once '../../config/conexion.php';

/**
 * Registra el ingreso de un vehículo
 */
function registrarIngresoVehiculo($datos) {
    $conn = conectar_Pepsico();
    
    $sql = "INSERT INTO ingreso_vehiculos (
        Placa, TipoVehiculo, Marca, Modelo, Color, Anio, 
        ConductorNombre, ConductorCedula, ConductorTelefono, Licencia,
        EmpresaCodigo, EmpresaNombre, Proposito, Area, PersonaContacto,
        Observaciones, EstadoIngreso, Kilometraje, Combustible, 
        Documentos, Fotos, UsuarioRegistro
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    // Convertir arrays a JSON para almacenar
    $documentos_json = !empty($datos['documentos']) ? json_encode($datos['documentos']) : NULL;
    $fotos_json = !empty($datos['fotos']) ? json_encode($datos['fotos']) : NULL;
    
    $stmt->bind_param(
        "sssssisssssssssssisssi",
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
        $documentos_json,
        $fotos_json,
        $datos['usuario_id']
    );
    
    $resultado = $stmt->execute();
    $id_insertado = $conn->insert_id;
    
    $stmt->close();
    $conn->close();
    
    return $resultado ? $id_insertado : false;
}

/**
 * Verifica si una placa ya está registrada y activa
 */
function placaExiste($placa) {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT COUNT(*) as total FROM ingreso_vehiculos 
            WHERE Placa = ? AND Estado = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $placa);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['total'] > 0;
}

/**
 * Obtiene los roles que deben ser notificados según el propósito
 */
function obtenerRolesParaNotificacion($proposito) {
    $notificaciones = [
        'Mantenimiento' => ['Jefe de Taller', 'Mecánico'],
        'Reparación' => ['Jefe de Taller', 'Mecánico', 'Administrador'],
        'Accidentado' => ['Jefe de Taller', 'Administrador', 'Supervisor'],
        'Inspección' => ['Jefe de Taller', 'Supervisor'],
        'Lavado' => ['Recepcionista'],
        'Revisión Técnica' => ['Jefe de Taller', 'Mecánico']
    ];
    
    return $notificaciones[$proposito] ?? ['Jefe de Taller', 'Administrador'];
}

/**
 * Guarda una notificación en el sistema
 */
function guardarNotificacion($ingreso_id, $roles, $mensaje) {
    $conn = conectar_Pepsico();
    
    // Aquí puedes implementar la lógica para guardar notificaciones
    // Por ahora, solo retornamos true como placeholder
    $conn->close();
    return true;
}

/**
 * Sube un archivo al servidor
 */
function subirArchivo($archivo, $tipo = 'documento') {
    $directorio = $tipo === 'foto' ? '../../uploads/fotos/' : '../../uploads/documentos/';
    
    // Crear directorio si no existe
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }
    
    $nombre_archivo = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo['name']);
    $ruta_completa = $directorio . $nombre_archivo;
    
    if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        return [
            'success' => true,
            'ruta' => $ruta_completa,
            'nombre' => $nombre_archivo,
            'tipo' => $tipo
        ];
    }
    
    return ['success' => false];
}
?>