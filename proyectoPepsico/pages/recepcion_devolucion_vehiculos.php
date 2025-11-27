<?php
include 'general/middle.php';
$roles_permitidos = ['Ejecutivo/a de Ventas', 'Administrador'];
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
    <title>Recepción y Devolución de Vehículos - PepsiCo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php include 'general/head.php'; ?>
    <link rel="stylesheet" href="recepcion_devolucion_vehiculos/css/recepcion_devolucion_vehiculos.css">
</head>
<body data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" data-pc-theme_contrast="" data-pc-theme="light">
    <?php include 'general/sidebar.php'; ?>
    <?php include 'general/header.php'; ?>
    <div class="pc-container">
        <div class="custom-page-header" style="top: 75px;">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <h5 class="mb-1">Recepción y Devolución de Vehículos</h5>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                                <li class="breadcrumb-item active">Recepción/Devolución</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="pc-content">
            <?php include 'recepcion_devolucion_vehiculos/components/c_recepcion_devolucion_vehiculos.php'; ?>
        </div>
    </div>
    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>
    <script src="recepcion_devolucion_vehiculos/js/app.js"></script>
</body>
</html>

