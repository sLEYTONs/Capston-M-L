<?php
include 'general/middle.php';

// Verificar acceso - Solo Mecánicos pueden solicitar repuestos
$roles_permitidos = ['Mecánico'];
if (!in_array($usuario_rol, $roles_permitidos)) {
    redirigir_no_autorizado();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Repuestos - PepsiCo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php include 'general/head.php'; ?>
    <link rel="stylesheet" href="solicitar_repuestos/css/solicitar_repuestos.css">
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
                            <h5 class="mb-1">Solicitar Repuestos</h5>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                                    <li class="breadcrumb-item"><a href="tareas.php">Tareas</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Solicitar Repuestos</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'solicitar_repuestos/components/c_solicitar_repuestos.php'; ?>
        </div>
    </div>
    
    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>
    
    <script src="solicitar_repuestos/js/app.js"></script>
</body>
</html>

