<?php
if (!defined('FUNCIONES_NOTIFICACIONES_INCLUIDO')) {
    define('FUNCIONES_NOTIFICACIONES_INCLUIDO', true);
    require_once __DIR__.'/../../app/config/conexion.php';

/**
 * Crea una nueva notificación para usuarios específicos
 */
function crearNotificacion($usuarios_destino, $titulo, $mensaje, $modulo, $enlace = null) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        error_log("Error de conexión al crear notificación");
        return false;
    }

    try {
        foreach ($usuarios_destino as $usuario_id) {
            $sql = "INSERT INTO notificaciones (usuario_id, titulo, mensaje, modulo, enlace, leida, fecha_creacion) 
                    VALUES (?, ?, ?, ?, ?, 0, NOW())";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                error_log("Error preparando consulta de notificación: " . $conn->error);
                continue;
            }
            
            $stmt->bind_param("issss", $usuario_id, $titulo, $mensaje, $modulo, $enlace);
            
            if (!$stmt->execute()) {
                error_log("Error ejecutando consulta de notificación: " . $stmt->error);
            }
            
            $stmt->close();
        }
        
        $conn->close();
        return true;
        
    } catch (Exception $e) {
        error_log("Excepción en crearNotificacion: " . $e->getMessage());
        $conn->close();
        return false;
    }
}

/**
 * Obtiene los IDs de usuarios por roles
 */
function obtenerUsuariosPorRoles($roles) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        return [];
    }

    $usuarios = [];
    
    try {
        // Crear placeholders para la consulta IN
        $placeholders = str_repeat('?,', count($roles) - 1) . '?';
        $sql = "SELECT UsuarioID FROM usuarios WHERE Rol IN ($placeholders) AND Estado = 1";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparando consulta obtenerUsuariosPorRoles: " . $conn->error);
            $conn->close();
            return [];
        }
        
        // Vincular parámetros
        $types = str_repeat('s', count($roles));
        $stmt->bind_param($types, ...$roles);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row['UsuarioID'];
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Error obteniendo usuarios por roles: " . $e->getMessage());
    }
    
    return $usuarios;
}

/**
 * Obtiene notificaciones no leídas para un usuario
 */
function obtenerNotificacionesUsuario($usuario_id, $limite = 10) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        return [];
    }

    $notificaciones = [];
    
    try {
        $sql = "SELECT id, titulo, mensaje, modulo, enlace, fecha_creacion 
                FROM notificaciones 
                WHERE usuario_id = ? AND leida = 0 
                ORDER BY fecha_creacion DESC 
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            return [];
        }
        
        $stmt->bind_param("ii", $usuario_id, $limite);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $notificaciones[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Error obteniendo notificaciones: " . $e->getMessage());
    }
    
    return $notificaciones;
}

/**
 * Marca una notificación como leída
 */
function marcarNotificacionLeida($notificacion_id, $usuario_id) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        return false;
    }

    try {
        $sql = "UPDATE notificaciones SET leida = 1, fecha_leida = NOW() 
                WHERE id = ? AND usuario_id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            return false;
        }
        
        $stmt->bind_param("ii", $notificacion_id, $usuario_id);
        $result = $stmt->execute();
        
        $stmt->close();
        $conn->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error marcando notificación como leída: " . $e->getMessage());
        $conn->close();
        return false;
    }
}

/**
 * Obtiene el conteo de notificaciones no leídas para un usuario
 */
function obtenerContadorNotificaciones($usuario_id) {
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        return 0;
    }

    $contador = 0;
    
    try {
        $sql = "SELECT COUNT(*) as total FROM notificaciones 
                WHERE usuario_id = ? AND leida = 0";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            return 0;
        }
        
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $contador = $row['total'] ?? 0;
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Error obteniendo contador de notificaciones: " . $e->getMessage());
    }
    
    return $contador;
}
} // Fin del if de protección contra doble inclusión
?>