<?php
require_once __DIR__ . '../../../../config/conexion.php';

/**
 * Valida formato de teléfono chileno
 * Acepta: +56 9 1234 5678, +56912345678, 56912345678, 912345678, 9 1234 5678
 * @param string $telefono
 * @return bool
 */
function validarTelefonoChileno($telefono) {
    // Limpiar el teléfono de espacios, guiones y paréntesis
    $telefonoLimpio = preg_replace('/[\s\-\(\)]/', '', $telefono);
    
    // Patrones válidos:
    // +56912345678 (con código país y sin espacios) - móvil
    // 56912345678 (sin + pero con código país) - móvil
    // 912345678 (solo número móvil chileno) - 9 dígitos
    // +5622123456 (fijo con código país) - fijo
    // 22123456 (fijo con código de área) - 8 dígitos
    
    // Validar formato chileno
    // Móvil: 9XXXXXXXX (9 dígitos totales: 9 + 8 dígitos)
    // Fijo: 2XXXXXX (8 dígitos totales: 2 + 6 dígitos) o 32XXXXXX (8 dígitos: 32 + 6 dígitos)
    
    // Si tiene código país (+56 o 56), removerlo primero
    if (preg_match('/^(\+?56)(.+)$/', $telefonoLimpio, $matches)) {
        $telefonoLimpio = $matches[2];
    }
    
    // Validar móvil: debe empezar con 9 y tener 9 dígitos totales
    if (preg_match('/^9\d{8}$/', $telefonoLimpio)) {
        return true;
    }
    
    // Validar fijo: debe empezar con 2 o 3 y tener 8 dígitos totales
    if (preg_match('/^[23]\d{7}$/', $telefonoLimpio)) {
        return true;
    }
    
    return false;
}

/**
 * Normaliza teléfono chileno a formato estándar (9 dígitos sin código país)
 * @param string $telefono
 * @return string
 */
function normalizarTelefonoChileno($telefono) {
    // Limpiar el teléfono de espacios, guiones y paréntesis
    $telefonoLimpio = preg_replace('/[\s\-\(\)]/', '', $telefono);
    
    // Si empieza con +56 o 56, removerlo
    if (preg_match('/^(\+?56)(.+)$/', $telefonoLimpio, $matches)) {
        $telefonoLimpio = $matches[2];
    }
    
    // Retornar el teléfono normalizado (sin código país)
    return $telefonoLimpio;
}

/**
 * Valida formato de RUT chileno (personas y empresas)
 * Acepta: 12.345.678-9, 1.234.567-8, 12345678-9, 1234567-8, 123456789, 12345678
 * RUT de empresa: 7 u 8 dígitos + dígito verificador (0-9 o K)
 * @param string $rut
 * @return bool
 */
function validarRUTChileno($rut) {
    if (empty($rut)) {
        return true; // RUT es opcional
    }
    
    // Limpiar el RUT: eliminar puntos, guiones y espacios
    $rutLimpio = preg_replace('/[\.\-\s]/', '', trim($rut));
    
    // Convertir a mayúsculas para manejar 'k' minúscula
    $rutLimpio = strtoupper($rutLimpio);
    
    // Validar formato básico: 7 u 8 dígitos + 1 dígito verificador (0-9 o K)
    // El RUT debe tener entre 8 y 9 caracteres en total (7-8 dígitos + 1 DV)
    if (!preg_match('/^(\d{7,8})([0-9K])$/', $rutLimpio, $matches)) {
        return false;
    }
    
    $numero = $matches[1];
    $dv = $matches[2];
    
    // Validar que el número tenga exactamente 7 u 8 dígitos
    $longitudNumero = strlen($numero);
    if ($longitudNumero < 7 || $longitudNumero > 8) {
        return false;
    }
    
    // Calcular dígito verificador usando algoritmo módulo 11
    $suma = 0;
    $multiplier = 2;
    
    // Recorrer de derecha a izquierda
    for ($i = $longitudNumero - 1; $i >= 0; $i--) {
        $suma += intval($numero[$i]) * $multiplier;
        $multiplier++;
        // Reiniciar multiplicador cuando llega a 7
        if ($multiplier > 7) {
            $multiplier = 2;
        }
    }
    
    $resto = $suma % 11;
    $dvCalculado = 11 - $resto;
    
    // Ajustar casos especiales
    if ($dvCalculado == 11) {
        $dvCalculado = '0';
    } elseif ($dvCalculado == 10) {
        $dvCalculado = 'K';
    } else {
        $dvCalculado = strval($dvCalculado);
    }
    
    // Comparar dígito verificador
    return $dv === $dvCalculado;
}

/**
 * Formatea RUT chileno con puntos y guión
 * Formato: XX.XXX.XXX-X (8 dígitos) o X.XXX.XXX-X (7 dígitos)
 * @param string $rut
 * @return string
 */
function formatearRUTChileno($rut) {
    if (empty($rut)) {
        return '';
    }
    
    // Limpiar el RUT: eliminar puntos, guiones y espacios
    $rutLimpio = preg_replace('/[\.\-\s]/', '', trim($rut));
    $rutLimpio = strtoupper($rutLimpio);
    
    // Separar número y dígito verificador
    if (preg_match('/^(\d{7,8})([0-9K])$/', $rutLimpio, $matches)) {
        $numero = $matches[1];
        $dv = $matches[2];
        
        // Formatear número con puntos según la longitud
        $longitud = strlen($numero);
        
        if ($longitud == 8) {
            // RUT de 8 dígitos: XX.XXX.XXX
            $numeroFormateado = substr($numero, 0, 2) . '.' . 
                                substr($numero, 2, 3) . '.' . 
                                substr($numero, 5, 3);
        } elseif ($longitud == 7) {
            // RUT de 7 dígitos: X.XXX.XXX
            $numeroFormateado = substr($numero, 0, 1) . '.' . 
                                substr($numero, 1, 3) . '.' . 
                                substr($numero, 4, 3);
        } else {
            // Si no tiene 7 u 8 dígitos, retornar sin formatear
            return $rut;
        }
        
        return $numeroFormateado . '-' . $dv;
    }
    
    // Si no coincide con el formato esperado, retornar tal cual
    return $rut;
}

/**
 * Valida formato de email
 * @param string $email
 * @return bool
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Verifica y crea la tabla de proveedores si no existe
 * @param mysqli $conn
 * @return bool
 */
function verificarTablaProveedores($conn) {
    $checkTable = "SHOW TABLES LIKE 'proveedores'";
    $resultCheck = mysqli_query($conn, $checkTable);
    $tablaExiste = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
    
    if ($resultCheck) {
        mysqli_free_result($resultCheck);
    }
    
    if (!$tablaExiste) {
        $createTable = "CREATE TABLE IF NOT EXISTS `proveedores` (
            `ID` INT(11) NOT NULL AUTO_INCREMENT,
            `Nombre` VARCHAR(255) NOT NULL COMMENT 'Nombre del proveedor',
            `Contacto` VARCHAR(255) NOT NULL COMMENT 'Nombre de la persona de contacto',
            `Email` VARCHAR(255) NOT NULL COMMENT 'Correo electrónico del proveedor',
            `Telefono` VARCHAR(20) NOT NULL COMMENT 'Teléfono de contacto (formato chileno)',
            `RUT` VARCHAR(20) DEFAULT NULL COMMENT 'RUT del proveedor (formato chileno)',
            `Direccion` TEXT DEFAULT NULL COMMENT 'Dirección del proveedor',
            `Estado` ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
            `FechaCreacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `FechaActualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            UNIQUE KEY `Email` (`Email`),
            KEY `Estado` (`Estado`),
            KEY `Nombre` (`Nombre`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!mysqli_query($conn, $createTable)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Obtiene todos los proveedores
 * @return array
 */
function obtenerProveedores() {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }
    
    // Verificar y crear tabla si no existe
    if (!verificarTablaProveedores($conn)) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al verificar/crear tabla de proveedores'];
    }
    
    $query = "SELECT ID, Nombre, Contacto, Email, Telefono, RUT, Direccion, Estado, 
                     FechaCreacion, FechaActualizacion 
              FROM proveedores 
              ORDER BY Nombre ASC";
    
    $result = mysqli_query($conn, $query);
    $proveedores = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $proveedores[] = $row;
        }
        mysqli_free_result($result);
    }
    
    mysqli_close($conn);
    return ['status' => 'success', 'data' => $proveedores];
}

/**
 * Obtiene un proveedor por ID
 * @param int $id
 * @return array
 */
function obtenerProveedorPorId($id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }
    
    // Verificar y crear tabla si no existe
    if (!verificarTablaProveedores($conn)) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al verificar/crear tabla de proveedores'];
    }
    
    $id = intval($id);
    $query = "SELECT ID, Nombre, Contacto, Email, Telefono, RUT, Direccion, Estado, 
                     FechaCreacion, FechaActualizacion 
              FROM proveedores 
              WHERE ID = $id 
              LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    $proveedor = null;
    
    if ($result && mysqli_num_rows($result) > 0) {
        $proveedor = mysqli_fetch_assoc($result);
    }
    
    mysqli_free_result($result);
    mysqli_close($conn);
    
    if ($proveedor) {
        return ['status' => 'success', 'data' => $proveedor];
    } else {
        return ['status' => 'error', 'message' => 'Proveedor no encontrado'];
    }
}

/**
 * Crea un nuevo proveedor
 * @param array $datos
 * @return array
 */
function crearProveedor($datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }
    
    // Verificar y crear tabla si no existe
    if (!verificarTablaProveedores($conn)) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al verificar/crear tabla de proveedores'];
    }
    
    // Validaciones
    $errores = [];
    
    // Validar campos obligatorios
    if (empty($datos['nombre'])) {
        $errores[] = 'El nombre es obligatorio';
    }
    if (empty($datos['contacto'])) {
        $errores[] = 'El contacto es obligatorio';
    }
    if (empty($datos['email'])) {
        $errores[] = 'El email es obligatorio';
    }
    if (empty($datos['telefono'])) {
        $errores[] = 'El teléfono es obligatorio';
    }
    
    // Validar formato de email
    if (!empty($datos['email']) && !validarEmail($datos['email'])) {
        $errores[] = 'El formato del email no es válido';
    }
    
    // Validar formato de teléfono chileno
    if (!empty($datos['telefono']) && !validarTelefonoChileno($datos['telefono'])) {
        $errores[] = 'El formato del teléfono no es válido. Use formato chileno (ej: +56 9 1234 5678)';
    }
    
    // Validar RUT si se proporciona
    if (!empty($datos['rut']) && !validarRUTChileno($datos['rut'])) {
        $errores[] = 'El formato del RUT no es válido';
    }
    
    // Verificar si el email ya existe
    if (!empty($datos['email'])) {
        $email = mysqli_real_escape_string($conn, $datos['email']);
        $checkEmail = "SELECT ID FROM proveedores WHERE Email = '$email'";
        $resultCheck = mysqli_query($conn, $checkEmail);
        if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
            $errores[] = 'El email ya está registrado';
        }
        if ($resultCheck) {
            mysqli_free_result($resultCheck);
        }
    }
    
    if (!empty($errores)) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => implode('. ', $errores)];
    }
    
    // Normalizar y escapar datos
    $nombre = mysqli_real_escape_string($conn, trim($datos['nombre']));
    $contacto = mysqli_real_escape_string($conn, trim($datos['contacto']));
    $email = mysqli_real_escape_string($conn, trim(strtolower($datos['email'])));
    $telefono = mysqli_real_escape_string($conn, normalizarTelefonoChileno($datos['telefono']));
    $rut = !empty($datos['rut']) ? mysqli_real_escape_string($conn, formatearRUTChileno($datos['rut'])) : NULL;
    $direccion = !empty($datos['direccion']) ? mysqli_real_escape_string($conn, trim($datos['direccion'])) : NULL;
    $estado = isset($datos['estado']) ? mysqli_real_escape_string($conn, $datos['estado']) : 'Activo';
    
    // Insertar proveedor
    $query = "INSERT INTO proveedores (Nombre, Contacto, Email, Telefono, RUT, Direccion, Estado) 
              VALUES ('$nombre', '$contacto', '$email', '$telefono', " . 
              ($rut ? "'$rut'" : "NULL") . ", " . 
              ($direccion ? "'$direccion'" : "NULL") . ", '$estado')";
    
    if (mysqli_query($conn, $query)) {
        $idInsertado = mysqli_insert_id($conn);
        mysqli_close($conn);
        return ['status' => 'success', 'message' => 'Proveedor creado correctamente', 'id' => $idInsertado];
    } else {
        $error = mysqli_error($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al crear proveedor: ' . $error];
    }
}

/**
 * Actualiza un proveedor existente
 * @param int $id
 * @param array $datos
 * @return array
 */
function actualizarProveedor($id, $datos) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }
    
    // Verificar y crear tabla si no existe
    if (!verificarTablaProveedores($conn)) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al verificar/crear tabla de proveedores'];
    }
    
    $id = intval($id);
    
    // Validaciones
    $errores = [];
    
    // Validar campos obligatorios
    if (empty($datos['nombre'])) {
        $errores[] = 'El nombre es obligatorio';
    }
    if (empty($datos['contacto'])) {
        $errores[] = 'El contacto es obligatorio';
    }
    if (empty($datos['email'])) {
        $errores[] = 'El email es obligatorio';
    }
    if (empty($datos['telefono'])) {
        $errores[] = 'El teléfono es obligatorio';
    }
    
    // Validar formato de email
    if (!empty($datos['email']) && !validarEmail($datos['email'])) {
        $errores[] = 'El formato del email no es válido';
    }
    
    // Validar formato de teléfono chileno
    if (!empty($datos['telefono']) && !validarTelefonoChileno($datos['telefono'])) {
        $errores[] = 'El formato del teléfono no es válido. Use formato chileno (ej: +56 9 1234 5678)';
    }
    
    // Validar RUT si se proporciona
    if (!empty($datos['rut']) && !validarRUTChileno($datos['rut'])) {
        $errores[] = 'El formato del RUT no es válido';
    }
    
    // Verificar si el email ya existe en otro proveedor
    if (!empty($datos['email'])) {
        $email = mysqli_real_escape_string($conn, $datos['email']);
        $checkEmail = "SELECT ID FROM proveedores WHERE Email = '$email' AND ID != $id";
        $resultCheck = mysqli_query($conn, $checkEmail);
        if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
            $errores[] = 'El email ya está registrado en otro proveedor';
        }
        if ($resultCheck) {
            mysqli_free_result($resultCheck);
        }
    }
    
    if (!empty($errores)) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => implode('. ', $errores)];
    }
    
    // Normalizar y escapar datos
    $nombre = mysqli_real_escape_string($conn, trim($datos['nombre']));
    $contacto = mysqli_real_escape_string($conn, trim($datos['contacto']));
    $email = mysqli_real_escape_string($conn, trim(strtolower($datos['email'])));
    $telefono = mysqli_real_escape_string($conn, normalizarTelefonoChileno($datos['telefono']));
    $rut = !empty($datos['rut']) ? mysqli_real_escape_string($conn, formatearRUTChileno($datos['rut'])) : NULL;
    $direccion = !empty($datos['direccion']) ? mysqli_real_escape_string($conn, trim($datos['direccion'])) : NULL;
    $estado = isset($datos['estado']) ? mysqli_real_escape_string($conn, $datos['estado']) : 'Activo';
    
    // Actualizar proveedor
    $query = "UPDATE proveedores 
              SET Nombre = '$nombre', 
                  Contacto = '$contacto', 
                  Email = '$email', 
                  Telefono = '$telefono', 
                  RUT = " . ($rut ? "'$rut'" : "NULL") . ", 
                  Direccion = " . ($direccion ? "'$direccion'" : "NULL") . ", 
                  Estado = '$estado',
                  FechaActualizacion = NOW()
              WHERE ID = $id";
    
    if (mysqli_query($conn, $query)) {
        mysqli_close($conn);
        return ['status' => 'success', 'message' => 'Proveedor actualizado correctamente'];
    } else {
        $error = mysqli_error($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al actualizar proveedor: ' . $error];
    }
}

/**
 * Elimina un proveedor (soft delete cambiando estado a Inactivo)
 * @param int $id
 * @return array
 */
function eliminarProveedor($id) {
    $conn = conectar_Pepsico();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }
    
    // Verificar y crear tabla si no existe
    if (!verificarTablaProveedores($conn)) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al verificar/crear tabla de proveedores'];
    }
    
    $id = intval($id);
    $query = "UPDATE proveedores SET Estado = 'Inactivo' WHERE ID = $id";
    
    if (mysqli_query($conn, $query)) {
        mysqli_close($conn);
        return ['status' => 'success', 'message' => 'Proveedor eliminado correctamente'];
    } else {
        $error = mysqli_error($conn);
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Error al eliminar proveedor: ' . $error];
    }
}

