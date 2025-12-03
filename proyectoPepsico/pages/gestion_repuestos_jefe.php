<?php
include 'general/middle.php';

$roles_permitidos = ['Asistente de Repuestos', 'Administrador', 'Jefe de Taller'];
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
    <title>Gestión de Repuestos con Jefe de Taller - PepsiCo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
    
    <?php include 'general/head.php'; ?>
    <link rel="stylesheet" href="../assets/css/repuestos-layout-shared.css">
    <link rel="stylesheet" href="gestion_repuestos_jefe/css/gestion_jefe.css">
</head>

<body data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" 
      data-pc-theme_contrast="" data-pc-theme="light">
    
    <?php include 'general/sidebar.php'; ?>
    <?php include 'general/header.php'; ?>
    
    <div class="pc-container">
        <div class="custom-page-header" style="top: 60px; min-height: 60px !important; padding: 0.75rem 1rem !important; overflow: visible !important;">
            <div class="page-block" style="padding: 0 !important;">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <h5 class="mb-1" style="margin-bottom: 0.25rem !important; line-height: 1.2 !important;">Gestión de Repuestos con Jefe de Taller</h5>
                        <nav aria-label="breadcrumb" style="margin: 0 !important;">
                            <ol class="breadcrumb mb-0" style="margin: 0 !important; padding: 0 !important;">
                                <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Gestión con Jefe</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="pc-content" style="margin-top: 75px !important; padding-top: 0 !important;">
            <?php include 'gestion_repuestos_jefe/components/c_gestion_jefe.php'; ?>
        </div>
    </div>
    
    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>
    
    <!-- DataTables JavaScript -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
    
    <script src="gestion_repuestos_jefe/js/app.js"></script>
</body>
</html>

