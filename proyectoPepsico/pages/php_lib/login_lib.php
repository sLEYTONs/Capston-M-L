<?php
/**
 * Valida un usuario y contraseña con bcrypt
 * @param string $usuario
 * @param string $password
 * @return bool
 */
function login($usuario, $password) {
    require_once '../app/conexion.php';
    
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

    // Preparar consulta usando sentencias preparadas para evitar SQL injection
    $stmt = $conn->prepare("SELECT UsuarioID, usuario, pass, Rol, Estado FROM USUARIOS WHERE usuario = ? AND Estado = 1 LIMIT 1");
    
    if (!$stmt) {
        error_log("Error preparando consulta: " . $conn->error);
        return false;
    }

    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        // Verificar la contraseña usando password_verify (para bcrypt)
        if (password_verify($password, $row['pass'])) {
            // Iniciar sesión y guardar datos del usuario
            @session_start();
            $_SESSION['user_wsh'] = array(
                'user' => $row['usuario'],
                'id' => $row['UsuarioID'],
                'rol' => $row['Rol']
            );
            
            // Actualizar último acceso
            $update_stmt = $conn->prepare("UPDATE USUARIOS SET UltimoAcceso = NOW() WHERE UsuarioID = ?");
            $update_stmt->bind_param("i", $row['UsuarioID']);
            $update_stmt->execute();
            $update_stmt->close();
            
            $stmt->close();
            $conn->close();
            return true;
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
    
    if (!isset($_SESSION['user_wsh']) || !is_array($_SESSION['user_wsh']) || empty($_SESSION['user_wsh']['user'])) {
        return false;
    }
    
    return true;
}

/**
 * Retorna usuario en la sesion
 * @return mixed
 */
function get_user() {
    @session_start();
    
    if (!isset($_SESSION['user_wsh']) || !is_array($_SESSION['user_wsh']) || empty($_SESSION['user_wsh']['user'])) {
        return false;
    }
    
    return $_SESSION['user_wsh']['user'];
}

/**
 * Retorna el rol del usuario en la sesion
 * @return mixed
 */
function get_rol() {
    @session_start();
    
    if (!isset($_SESSION['user_wsh']) || !is_array($_SESSION['user_wsh']) || empty($_SESSION['user_wsh']['rol'])) {
        return false;
    }
    
    return $_SESSION['user_wsh']['rol'];
}

/**
 * Vacia la sesion con los datos del usuario validado
 */
function logout() {
    @session_start();
    unset($_SESSION['user_wsh']);
    session_destroy();
    
    echo "<script type='text/javascript'>parent.location.href = './login.php'</script>";
    return true;
}

function logout_rpt() {
    @session_start();
    unset($_SESSION['user_wsh']);
    session_destroy();
    
    echo "<script type='text/javascript'>parent.location.href = './index.php'</script>";
    return true;
}
?>