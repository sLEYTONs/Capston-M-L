<?php
include 'general/middle.php';

$roles_permitidos = ['Administrador'];
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
    <title>Mantenimiento - Taller PepsiCo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    

    <?php include 'general/head.php'; ?>
    <link rel="stylesheet" href="mantenimiento/css/mantenimiento.css">
</head>

<body data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" 
      data-pc-theme_contrast="" data-pc-theme="light">
    
    <?php include 'general/sidebar.php'; ?>
    <?php include 'general/header.php'; ?>
    
    <div class="pc-container">
        <div class="custom-page-header" style="top: 75px;">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <h5 class="mb-1">Programación de Mantenimiento</h5>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Mantenimiento</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="pc-content">
            <?php include 'mantenimiento/contents.php'; ?>
            
            <!-- Espaciador para el footer -->
            <div class="pc-footer-fix" style="height: 100px;"></div>
        </div>
    </div>
    
    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>
    
    <script src="mantenimiento/js/app.js"></script>
</body>
</html>