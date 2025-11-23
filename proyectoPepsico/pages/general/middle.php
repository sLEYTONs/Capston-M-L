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
        'ingreso_vehiculos.php', 'reportes.php'
    ],
    'Jefe de Taller' => [
        'consulta.php', 'reportes.php', 'base_datos.php',
        'control_ingreso.php', 'repuestos.php', 'calidad.php',
        'ingreso_vehiculos.php', 'recepcion_tecnica.php',
        'tareas.php', 'gestion_pausas_repuestos.php'
    ],
    'Mecánico' => [
        'tareas.php',
        'gestion_pausas_repuestos.php',
        'solicitar_repuestos.php',
        'estado_solicitudes_repuestos.php',
        'repuestos.php'
    ],
    'Recepcionista' => [
        'consulta.php', 'base_datos.php', 'ingreso_vehiculos.php', 'recepcion_tecnica.php',
        'reportes.php'
    ],
    'Guardia' => [
        'control_ingreso.php',
        'vehiculos_agendados.php'
    ],
    'Supervisor' => [
        'consulta.php',
        'gestion_solicitudes.php',
        'reportes.php',
        'base_datos.php'
    ],
    'Chofer' => [
        'solicitudes_agendamiento.php',
        'mis_solicitudes.php'
    ],
    'Asistente de Repuestos' => [
        'repuestos.php',
        'recepcion_entrega_repuestos.php',
        'registro_insumos_vehiculo.php',
        'comunicacion_proveedores.php',
        'gestion_repuestos_jefe.php',
        'estado_solicitudes_repuestos.php'
    ],
    'Coordinador de Zona' => [
        'inventario_coordinador.php',
        'coordinacion_jefe_taller.php',
        'control_gastos_vehiculos.php',
        'reportes_semanales.php',
        'reportes.php',
        'consulta.php',
        'base_datos.php'
    ],
    'Ejecutivo/a de Ventas' => [
        'recepcion_devolucion_vehiculos.php',
        'coordinacion_taller_fallas.php',
        'vehiculos_asignados.php',
        'consulta.php'
    ],
    'Supervisor de Flotas' => [
        'supervisar_politicas_uso.php',
        'gestion_incidentes_siniestros.php',
        'coordinacion_jefe_flota.php',
        'consulta.php',
        'reportes.php',
        'base_datos.php'
    ],
    'Encargado de Llaves' => [
        'control_llaves.php',
        'registro_prestamos_temporales.php',
        'control_duplicados_chapas.php',
        'gestion_cambios_perdidas.php',
        'consulta.php'
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
        'Mecánico' => 'tareas.php',
        'Recepcionista' => 'consulta.php',
        'Guardia' => 'control_ingreso.php',
        'Supervisor' => 'consulta.php',
        'Chofer' => 'solicitudes_agendamiento.php',
        'Asistente de Repuestos' => 'repuestos.php',
        'Coordinador de Zona' => 'inventario_coordinador.php',
        'Ejecutivo/a de Ventas' => 'vehiculos_asignados.php',
        'Supervisor de Flotas' => 'supervisar_politicas_uso.php',
        'Encargado de Llaves' => 'control_llaves.php'
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
