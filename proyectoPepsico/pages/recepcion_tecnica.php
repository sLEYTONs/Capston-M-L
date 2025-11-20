<?php
include 'general/middle.php';

$roles_permitidos = ['Administrador', 'Recepcionista'];
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
    <title>Recepción Técnica - PepsiCo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
    
    <?php include 'general/head.php'; ?>
    <link rel="stylesheet" href="recepcion_tecnica/css/recepcion_tecnica.css">
</head>

<body data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" 
      data-pc-theme_contrast="" data-pc-theme="light">
    
    <?php include 'general/sidebar.php'; ?>
    <?php include 'general/header.php'; ?>
    
    <div class="pc-container">
        <div class="pc-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <h5 class="mb-1">Recepción Técnica de Vehículos</h5>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Recepción Técnica</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'recepcion_tecnica/contents.php'; ?>
            
            <div class="pc-footer-fix" style="height: 100px;"></div>
        </div>
    </div>
    
    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>
    
    <script src="recepcion_tecnica/js/app.js"></script>
</body>
</html>

