<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Obtener datos del usuario
$usuario_actual = $_SESSION['usuario']['nombre'];
$usuario_id = $_SESSION['usuario']['id'];
$usuario_rol = $_SESSION['usuario']['rol'];

// Definir roles permitidos para cada módulo
$roles_permisos = [
    'Administrador' => ['todos_los_modulos'],
    'Jefe de Taller' => ['vehiculos', 'tareas', 'usuarios_operativos'],
    'Mecánico' => ['tareas_propias', 'vehiculos_asignados'],
    'Recepcionista' => ['registro_vehiculos'],
    'Guardia' => ['registro_entrada_salida'],
    'Supervisor' => ['lectura_todos_modulos']
];

function tiene_permiso($modulo_requerido) {
    global $usuario_rol, $roles_permisos;
    
    if ($usuario_rol === 'Administrador') return true;
    
    return isset($roles_permisos[$usuario_rol]) && 
           in_array($modulo_requerido, $roles_permisos[$usuario_rol]);
}

// Función para obtener datos del usuario actual
function get_usuario_actual() {
    if (isset($_SESSION['usuario'])) {
        return $_SESSION['usuario'];
    }
    return null;
}
?>