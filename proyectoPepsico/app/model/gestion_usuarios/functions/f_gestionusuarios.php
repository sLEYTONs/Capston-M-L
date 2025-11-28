<?php
require_once '../../../config/conexion.php';

/**
 * Obtiene todos los usuarios de la base de datos
 */
function obtenerTodosUsuarios() {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT UsuarioID, NombreUsuario, Correo, Rol, Estado, FechaCreacion, UltimoAcceso 
            FROM usuarios 
            ORDER BY FechaCreacion DESC";
    
    $result = $conn->query($sql);
    $usuarios = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
    
    $conn->close();
    return $usuarios;
}

/**
 * Crea un nuevo usuario en la base de datos
 */
function crearNuevoUsuario($datos) {
    $conn = conectar_Pepsico();
    
    $sql = "INSERT INTO usuarios (NombreUsuario, Correo, ClaveHash, Rol, Estado) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    // Hash de la contraseña
    $clave_hash = password_hash($datos['clave'], PASSWORD_BCRYPT);
    
    $stmt->bind_param("ssssi", 
        $datos['nombre_usuario'],
        $datos['correo'],
        $clave_hash,
        $datos['rol'],
        $datos['estado']
    );
    
    $resultado = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $resultado;
}

/**
 * Obtiene un usuario por su ID
 */
function obtenerUsuarioPorId($usuario_id) {
    $conn = conectar_Pepsico();
    
    $sql = "SELECT UsuarioID, NombreUsuario, Correo, Rol, Estado 
            FROM usuarios 
            WHERE UsuarioID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $usuario;
}

/**
 * Actualiza un usuario existente
 */
function actualizarUsuario($datos) {
    $conn = conectar_Pepsico();
    
    if (!empty($datos['clave'])) {
        // Si se proporciona nueva contraseña
        $sql = "UPDATE usuarios 
                SET NombreUsuario = ?, Correo = ?, ClaveHash = ?, Rol = ?, Estado = ? 
                WHERE UsuarioID = ?";
        
        $stmt = $conn->prepare($sql);
        $clave_hash = password_hash($datos['clave'], PASSWORD_BCRYPT);
        $stmt->bind_param("ssssii", 
            $datos['nombre_usuario'],
            $datos['correo'],
            $clave_hash,
            $datos['rol'],
            $datos['estado'],
            $datos['usuario_id']
        );
    } else {
        // Sin cambiar contraseña
        $sql = "UPDATE usuarios 
                SET NombreUsuario = ?, Correo = ?, Rol = ?, Estado = ? 
                WHERE UsuarioID = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", 
            $datos['nombre_usuario'],
            $datos['correo'],
            $datos['rol'],
            $datos['estado'],
            $datos['usuario_id']
        );
    }
    
    $resultado = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $resultado;
}

/**
 * Verifica si un nombre de usuario ya existe
 */
function existeNombreUsuario($nombre_usuario, $excluir_id = null) {
    $conn = conectar_Pepsico();
    
    if ($excluir_id) {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE NombreUsuario = ? AND UsuarioID != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nombre_usuario, $excluir_id);
    } else {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE NombreUsuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nombre_usuario);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['total'] > 0;
}

/**
 * Verifica si un correo ya existe
 */
function existeCorreo($correo, $excluir_id = null) {
    $conn = conectar_Pepsico();
    
    if ($excluir_id) {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE Correo = ? AND UsuarioID != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $correo, $excluir_id);
    } else {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE Correo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $correo);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $row['total'] > 0;
}
?>