<?php

/**
 * Valida un usuario y contraseña con bcrypt
 * @param string $usuario
 * @param string $password
 * @return array|bool
 */
function verificarUsuario($usuario, $password) {
    require_once '../app/config/conexion.php';
    
    // Validar que usuario y password tengan datos
    if (empty($usuario) || empty($password)) {
        return false;
    }

    // Conectar usando tu función existente
    $conn = conectar_Pepsico();
    
    if (!$conn) {
        error_log("Error conectando a la base de datos");
        return false;
    }

    // USAR LOS NOMBRES CORRECTOS DE COLUMNAS según tu tabla
    $stmt = $conn->prepare("SELECT  UsuarioID, 
                                    NombreUsuario, 
                                    ClaveHash, 
                                    Rol, 
                                    Estado 
                            FROM USUARIOS 
                            WHERE NombreUsuario = ? 
                            LIMIT 1");
    
    if (!$stmt) {
        error_log("Error preparando consulta: " . $conn->error);
        return false;
    }

    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verificar la contraseña usando password_verify (para bcrypt)
        if (password_verify($password, $user['ClaveHash'])) {
            // Verificar estado del usuario
            if ($user['Estado'] == 0) {
                return false;
            }
            
            $stmt->close();
            return $user;
        }
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

/**
 * Verifica si el usuario está logeado
 * @return bool
 */
function estoy_logeado() {
    @session_start();
    
    if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
        return false;
    }
    
    return true;
}

function logout() {
    @session_start();
    unset($_SESSION['usuario']);
    session_destroy();
    return true;
}
?>