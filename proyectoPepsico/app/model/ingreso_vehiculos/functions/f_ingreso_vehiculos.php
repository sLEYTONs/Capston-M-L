<?php
require_once '../../../config/conexion.php';

/**
 * Registra el ingreso de un vehículo
 */
function registrarIngresoVehiculo($datos) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $sql = "INSERT INTO ingreso_vehiculos (
        Placa, TipoVehiculo, Marca, Modelo, Chasis, Color, Anio, 
        ConductorNombre, ConductorCedula, ConductorTelefono, Licencia,
        EmpresaCodigo, EmpresaNombre, Proposito, Area, PersonaContacto,
        Observaciones, EstadoIngreso, Kilometraje, Combustible, 
        Documentos, Fotos, UsuarioRegistro, FechaIngreso, Estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Ingresado')";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    // Convertir arrays a JSON para almacenar
    $documentos_json = !empty($datos['documentos']) ? json_encode($datos['documentos']) : NULL;
    $fotos_json = !empty($datos['fotos']) ? json_encode($datos['fotos']) : NULL;
    
    // Manejar valores NULL para año, kilometraje y chasis
    $anio = !empty($datos['anio']) ? intval($datos['anio']) : NULL;
    $kilometraje = !empty($datos['kilometraje']) ? intval($datos['kilometraje']) : NULL;
    $chasis = !empty($datos['chasis']) ? $datos['chasis'] : NULL;
    
    $stmt->bind_param(
        "ssssssisssssssssssisssi",
        $datos['placa'],
        $datos['tipo_vehiculo'],
        $datos['marca'],
        $datos['modelo'],
        $chasis,
        $datos['color'],
        $anio,
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
        $kilometraje,
        $datos['combustible'],
        $documentos_json,
        $fotos_json,
        $datos['usuario_id']
    );
    
    $resultado = $stmt->execute();
    $id_insertado = $conn->insert_id;
    
    if (!$resultado) {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        throw new Exception('Error ejecutando la consulta: ' . $error);
    }
    
    $stmt->close();
    $conn->close();
    
    return $id_insertado;
}

/**
 * Verifica si una placa ya está registrada y activa
 */
function placaExiste($placa) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT COUNT(*) as total FROM ingreso_vehiculos 
            WHERE Placa = ? AND Estado = 'Ingresado'";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $placa);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['total'] > 0;
}

/**
 * Verifica si un chasis ya está registrado (solo si no está vacío)
 */
function chasisExiste($chasis) {
    if (empty($chasis) || $chasis === '') {
        return false; // No validar chasis vacíos
    }
    
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT COUNT(*) as total FROM ingreso_vehiculos 
            WHERE Chasis = ? AND Estado = 'active'";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $chasis);
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
    // Directorios de uploads
    $directorio_base = '../../uploads/';
    $directorio_tipo = $tipo === 'foto' ? 'fotos/' : 'documentos/';
    $directorio_completo = $directorio_base . $directorio_tipo;
    
    // Crear directorios si no existen
    if (!file_exists($directorio_base)) {
        mkdir($directorio_base, 0755, true);
    }
    if (!file_exists($directorio_completo)) {
        mkdir($directorio_completo, 0755, true);
    }
    
    // Validar tipo de archivo
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    if ($tipo === 'foto') {
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    } else {
        $extensiones_permitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    }
    
    if (!in_array($extension, $extensiones_permitidas)) {
        return [
            'success' => false,
            'message' => 'Tipo de archivo no permitido. Extensiones permitidas: ' . implode(', ', $extensiones_permitidas)
        ];
    }
    
    // Validar tamaño (en bytes)
    $tamanio_maximo = $tipo === 'foto' ? 5 * 1024 * 1024 : 10 * 1024 * 1024; // 5MB o 10MB
    if ($archivo['size'] > $tamanio_maximo) {
        return [
            'success' => false,
            'message' => 'El archivo es demasiado grande. Máximo: ' . ($tipo === 'foto' ? '5MB' : '10MB')
        ];
    }
    
    // Generar nombre único
    $nombre_guardado = uniqid() . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo['name']);
    $ruta_completa = $directorio_completo . $nombre_guardado;
    
    if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        return [
            'success' => true,
            'ruta' => $ruta_completa,
            'nombre_guardado' => $nombre_guardado,
            'nombre_original' => $archivo['name'],
            'tipo' => $tipo,
            'extension' => $extension
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Error al mover el archivo subido'
    ];
}

?>