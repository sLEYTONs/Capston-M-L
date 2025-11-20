<?php
require_once '../../../config/conexion.php';
require_once '../../../../pages/general/funciones_notificaciones.php';

/**
 * Busca un ingreso pendiente por placa (registrado por guardia)
 */
function buscarIngresoPendiente($placa) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT * FROM ingreso_vehiculos 
            WHERE UPPER(REPLACE(Placa, ' ', '')) = UPPER(REPLACE(?, ' ', '')) 
            AND Estado = 'Ingresado'";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $placa);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehiculo = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $vehiculo;
}

/**
 * Actualiza un registro de ingreso existente
 */
function actualizarIngresoVehiculo($datos) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Primero obtener las fotos existentes para no perderlas
    $sql_select = "SELECT Fotos FROM ingreso_vehiculos WHERE ID = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $datos['ingreso_id']);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $row = $result_select->fetch_assoc();
    $fotos_existentes = $row['Fotos'];
    $stmt_select->close();

    $sql = "UPDATE ingreso_vehiculos SET
        TipoVehiculo = ?,
        Marca = ?,
        Modelo = ?,
        Chasis = ?,
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
        EstadoIngreso = ?,
        Kilometraje = ?,
        Combustible = ?,
        Documentos = ?,
        Fotos = ?,
        UsuarioRegistro = ?,
        FechaRegistro = NOW()
    WHERE ID = ? AND Estado = 'Ingresado'";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    // Convertir arrays a JSON para almacenar
    $documentos_json = !empty($datos['documentos']) ? json_encode($datos['documentos']) : NULL;
    
    // Mantener las fotos existentes (tomadas por el guardia)
    $fotos_json = $fotos_existentes;
    
    // Manejar valores NULL
    $anio = !empty($datos['anio']) ? intval($datos['anio']) : NULL;
    $kilometraje = !empty($datos['kilometraje']) ? intval($datos['kilometraje']) : NULL;
    $chasis = !empty($datos['chasis']) ? $datos['chasis'] : NULL;
    
    $stmt->bind_param(
        "sssssisssssssssssissssi",
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
        $datos['usuario_id'],
        $datos['ingreso_id']
    );
    
    $resultado = $stmt->execute();
    
    if (!$resultado) {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        throw new Exception('Error ejecutando la consulta: ' . $error);
    }
    
    $stmt->close();
    $conn->close();
    
    // Enviar notificaciones
    notificarNuevoIngreso($datos, $datos['ingreso_id']);
    
    return $datos['ingreso_id'];
}

/**
 * Verifica si una placa ya existe en otro registro
 */
function placaExisteEnOtroRegistro($placa, $ingreso_id) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT COUNT(*) as total FROM ingreso_vehiculos 
            WHERE UPPER(REPLACE(Placa, ' ', '')) = UPPER(REPLACE(?, ' ', '')) 
            AND Estado = 'Ingresado'
            AND ID != ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("si", $placa, $ingreso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['total'] > 0;
}

/**
 * Verifica si un chasis ya existe en otro registro
 */
function chasisExisteEnOtroRegistro($chasis, $ingreso_id) {
    if (empty($chasis)) {
        return false;
    }
    
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT COUNT(*) as total FROM ingreso_vehiculos 
            WHERE UPPER(REPLACE(Chasis, ' ', '')) = UPPER(REPLACE(?, ' ', '')) 
            AND Estado = 'Ingresado'
            AND ID != ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("si", $chasis, $ingreso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['total'] > 0;
}

/**
 * Verifica si una cédula ya existe en otro registro
 */
function cedulaExisteEnOtroRegistro($cedula, $ingreso_id) {
    if (empty($cedula)) {
        return false;
    }
    
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT COUNT(*) as total FROM ingreso_vehiculos 
            WHERE REPLACE(ConductorCedula, ' ', '') = REPLACE(?, ' ', '') 
            AND Estado = 'Ingresado'
            AND ID != ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("si", $cedula, $ingreso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['total'] > 0;
}

/**
 * Verifica si una licencia ya existe en otro registro
 */
function licenciaExisteEnOtroRegistro($licencia, $ingreso_id) {
    if (empty($licencia)) {
        return false;
    }
    
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT COUNT(*) as total FROM ingreso_vehiculos 
            WHERE UPPER(REPLACE(Licencia, ' ', '')) = UPPER(REPLACE(?, ' ', '')) 
            AND Estado = 'Ingresado'
            AND ID != ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("si", $licencia, $ingreso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['total'] > 0;
}

// ... (mantener todas las demás funciones existentes del f_ingreso_vehiculos.php original)

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
    
    // ✅ NUEVO: ENVIAR NOTIFICACIONES DESPUÉS DEL REGISTRO EXITOSO
    if ($id_insertado) {
        notificarNuevoIngreso($datos, $id_insertado);
    }
    
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
            WHERE UPPER(REPLACE(Placa, ' ', '')) = UPPER(REPLACE(?, ' ', '')) 
            AND Estado = 'Ingresado'";
    
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
 * Obtiene información del vehículo con la placa duplicada
 */
function obtenerInfoPlacaDuplicada($placa) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT Placa, Marca, Modelo, ConductorNombre, FechaIngreso 
            FROM ingreso_vehiculos 
            WHERE UPPER(REPLACE(Placa, ' ', '')) = UPPER(REPLACE(?, ' ', '')) 
            AND Estado = 'Ingresado' 
            LIMIT 1";
    
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
    
    return $row;
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
            WHERE UPPER(REPLACE(Chasis, ' ', '')) = UPPER(REPLACE(?, ' ', '')) 
            AND Estado = 'Ingresado'";
    
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
 * Obtiene información del vehículo con el chasis duplicado
 */
function obtenerInfoChasisDuplicado($chasis) {
    if (empty($chasis)) {
        return null;
    }
    
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT Placa, Marca, Modelo, ConductorNombre, FechaIngreso 
            FROM ingreso_vehiculos 
            WHERE UPPER(REPLACE(Chasis, ' ', '')) = UPPER(REPLACE(?, ' ', '')) 
            AND Estado = 'Ingresado' 
            LIMIT 1";
    
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
    
    return $row;
}

/**
 * Verifica si una cédula ya está registrada
 */
function cedulaExiste($cedula) {
    if (empty($cedula) || $cedula === '') {
        return false; // No validar cédulas vacías
    }
    
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT COUNT(*) as total FROM ingreso_vehiculos 
            WHERE REPLACE(ConductorCedula, ' ', '') = REPLACE(?, ' ', '') 
            AND Estado = 'Ingresado'";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['total'] > 0;
}

/**
 * Obtiene información del conductor con cédula duplicada
 */
function obtenerInfoCedulaDuplicada($cedula) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT ConductorNombre, ConductorCedula, Placa, FechaIngreso 
            FROM ingreso_vehiculos 
            WHERE REPLACE(ConductorCedula, ' ', '') = REPLACE(?, ' ', '') 
            AND Estado = 'Ingresado' 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row;
}

/**
 * Verifica si una licencia ya está registrada (solo si no está vacía)
 */
function licenciaExiste($licencia) {
    if (empty($licencia) || $licencia === '') {
        return false; // No validar licencias vacías
    }
    
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT COUNT(*) as total FROM ingreso_vehiculos 
            WHERE UPPER(REPLACE(Licencia, ' ', '')) = UPPER(REPLACE(?, ' ', '')) 
            AND Estado = 'Ingresado'";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $licencia);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['total'] > 0;
}

/**
 * Obtiene información del conductor con licencia duplicada
 */
function obtenerInfoLicenciaDuplicada($licencia) {
    if (empty($licencia)) {
        return null;
    }
    
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $sql = "SELECT ConductorNombre, Licencia, Placa, FechaIngreso 
            FROM ingreso_vehiculos 
            WHERE UPPER(REPLACE(Licencia, ' ', '')) = UPPER(REPLACE(?, ' ', '')) 
            AND Estado = 'Ingresado' 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Error preparando la consulta: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $licencia);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row;
}

/**
 * Notifica a supervisores y jefes de taller sobre nuevo ingreso
 */
function notificarNuevoIngreso($datos_vehiculo, $ingreso_id) {
    // Definir los roles que deben ser notificados
    $roles_notificar = ['Supervisor', 'Jefe de Taller'];
    
    // Obtener usuarios con esos roles
    $usuarios_destino = obtenerUsuariosPorRoles($roles_notificar);
    
    if (empty($usuarios_destino)) {
        error_log("No se encontraron usuarios para notificar con roles: " . implode(', ', $roles_notificar));
        return false;
    }
    
    // Crear mensaje de notificación
    $titulo = "Nuevo Ingreso de Vehículo";
    $mensaje = "Vehículo {$datos_vehiculo['placa']} - {$datos_vehiculo['marca']} {$datos_vehiculo['modelo']} ingresado por {$datos_vehiculo['conductor_nombre']}";
    $modulo = "ingreso_vehiculos";
    $enlace = "ingresos_vehiculos.php?ver={$ingreso_id}";
    
    // Crear notificación
    $resultado = crearNotificacion($usuarios_destino, $titulo, $mensaje, $modulo, $enlace);
    
    if ($resultado) {
        error_log("Notificaciones enviadas a " . count($usuarios_destino) . " usuarios");
    } else {
        error_log("Error al enviar notificaciones");
    }
    
    return $resultado;
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