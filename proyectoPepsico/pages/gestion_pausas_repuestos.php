<?php
include 'general/middle.php';

// Verificar acceso - Solo Mecánico y Administrador
$roles_permitidos = ['Mecánico', 'Administrador'];
if (!in_array($usuario_rol, $roles_permitidos)) {
    redirigir_no_autorizado();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pausas y Solicitudes de Repuestos - PepsiCo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php include 'general/head.php'; ?>
    <link rel="stylesheet" href="gestion_pausas_repuestos/css/gestion_pausas_repuestos.css">
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
                            <h5 class="mb-1">Gestión de Pausas y Solicitudes de Repuestos</h5>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                                    <li class="breadcrumb-item"><a href="tareas.php">Tareas</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Gestión de Pausas</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'gestion_pausas_repuestos/components/c_gestion_pausas_repuestos.php'; ?>
            
            <div class="pc-footer-fix" style="height: 100px;"></div>
        </div>
    </div>
    
    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>
    
    <script src="gestion_pausas_repuestos/js/app.js"></script>
</body>
</html>

