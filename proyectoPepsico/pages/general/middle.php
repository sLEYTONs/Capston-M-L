<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Obtener datos del usuario de la sesión
$usuario_actual = $_SESSION['usuario']['nombre'];
$usuario_id = $_SESSION['usuario']['id'];
$usuario_rol = $_SESSION['usuario']['rol'];

// Definir páginas permitidas para cada rol
$paginas_por_rol = [
    'Administrador' => [
        'base_datos.php', 'consulta.php', 'gestion_usuarios.php', 
        'ingreso_vehiculos.php', 'marcas.php', 'reportes.php'
    ],
    'Jefe de Taller' => [
        'consulta.php', 'marcas.php', 'reportes.php', 'base_datos.php'
    ],
    'Mecánico' => [
        'consulta.php'
    ],
    'Recepcionista' => [
        'consulta.php', 'base_datos.php'
    ],
    'Guardia' => [
        'control_ingreso.php'
    ],
    'Supervisor' => [
        'consulta.php', 'reportes.php', 'base_datos.php'
    ],
    'Chofer' => [
        'ingreso_vehiculos.php'
    ]
];

// Función para verificar si el usuario tiene acceso a una página
function tiene_acceso($pagina) {
    global $usuario_rol, $paginas_por_rol;
    
    // Administrador tiene acceso a todo
    if ($usuario_rol === 'Administrador') {
        return true;
    }
    
    // Verificar si el rol tiene acceso a la página
    return isset($paginas_por_rol[$usuario_rol]) && 
           in_array($pagina, $paginas_por_rol[$usuario_rol]);
}

// Función para obtener la página principal según el rol
function obtener_pagina_principal($rol) {
    $paginas_principales = [
        'Administrador' => 'gestion_usuarios.php',
        'Jefe de Taller' => 'consulta.php',
        'Mecánico' => 'consulta.php',
        'Recepcionista' => 'consulta.php',
        'Guardia' => 'consulta.php',
        'Supervisor' => 'reportes.php',
        'Chofer' => 'ingreso_vehiculos.php'
    ];
    
    return $paginas_principales[$rol] ?? 'consulta.php';
}

// Función para obtener datos del usuario actual
function get_usuario_actual() {
    if (isset($_SESSION['usuario'])) {
        return $_SESSION['usuario'];
    }
    return null;
}

// Función para redirigir a página no autorizada
function redirigir_no_autorizado() {
    global $usuario_rol;
    $pagina_principal = obtener_pagina_principal($usuario_rol);
    header('Location: ' . $pagina_principal);
    exit();
}

?>
