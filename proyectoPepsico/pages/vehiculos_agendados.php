<?php
include 'general/middle.php';

// Verificar acceso - Solo Guardia, Administrador y Jefe de Taller
$roles_permitidos = ['Guardia', 'Administrador', 'Jefe de Taller'];
if (!in_array($usuario_rol, $roles_permitidos)) {
    redirigir_no_autorizado();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehículos Agendados - PepsiCo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php include 'general/head.php'; ?>
    <link rel="stylesheet" href="vehiculos_agendados/css/vehiculos_agendados.css">
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
                            <h5 class="mb-1">Vehículos Agendados</h5>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Vehículos Agendados</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'vehiculos_agendados/components/c_vehiculos_agendados.php'; ?>
            
            <div class="pc-footer-fix" style="height: 100px;"></div>
        </div>
    </div>
    
    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>
    
    <script src="vehiculos_agendados/js/app.js"></script>
</body>
</html>

