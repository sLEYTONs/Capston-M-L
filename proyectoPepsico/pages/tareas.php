<?php
include 'general/middle.php';

$roles_permitidos = ['Administrador', 'Mecánico', 'Jefe de Taller'];
if (!in_array($usuario_rol, $roles_permitidos)) {
    $pagina_principal = obtener_pagina_principal($usuario_rol);
    header('Location: ' . $pagina_principal);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas - Taller PepsiCo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <?php include 'general/head.php'; ?>
    <link rel="stylesheet" href="tareas/css/tareas.css">
</head>

<body data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" 
      data-pc-theme_contrast="" data-pc-theme="light">
    
    <?php include 'general/sidebar.php'; ?>
    <?php include 'general/header.php'; ?>
    
    <!-- Contenedor para notificaciones -->
    <div class="notification-container" id="notification-container"></div>
    
    <div class="pc-container">
        <div class="custom-page-header" style="top: 60px; min-height: 60px !important; padding: 0.75rem 1rem !important; overflow: visible !important;">
            <div class="page-block" style="padding: 0 !important;">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <h5 class="mb-1" style="margin-bottom: 0.25rem !important; line-height: 1.2 !important;">Mis Tareas Asignadas</h5>
                        <nav aria-label="breadcrumb" style="margin: 0 !important;">
                            <ol class="breadcrumb" style="margin: 0 !important; padding: 0 !important;">
                                <li class="breadcrumb-item"><a href="tareas.php">Inicio</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Tareas</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="pc-content" style="margin-top: 75px !important; padding-top: 0 !important;">
            <?php include 'tareas/components/c_tareas.php'; ?>
        </div>
    </div>
    
    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>


    
    <script src="tareas/js/app.js"></script>
</body>
</html>