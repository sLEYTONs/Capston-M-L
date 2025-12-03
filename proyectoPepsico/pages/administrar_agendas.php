<?php
include 'general/middle.php';

// Verificar acceso - Solo Supervisor y Administrador
if (!in_array($usuario_rol, ['Supervisor', 'Administrador'])) {
    redirigir_no_autorizado();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Agendas - PepsiCo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php include 'general/head.php'; ?>
    <link rel="stylesheet" href="administrar_agendas/css/style.css">
    
    <!-- FullCalendar CSS - Local -->
    <link href='../assets/css/fullcalendar/main.min.css' rel='stylesheet' />
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
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
                        <h5 class="mb-1" style="margin-bottom: 0.25rem !important; line-height: 1.2 !important;">Administrar Agendas</h5>
                        <nav aria-label="breadcrumb" style="margin: 0 !important;">
                            <ol class="breadcrumb mb-0" style="margin: 0 !important; padding: 0 !important;">
                                <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Administrar Agendas</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="pc-content" style="margin-top: 75px !important; padding-top: 0 !important;">
            <?php include 'administrar_agendas/components/c_administrar_agendas.php'; ?>
            
            <div class="pc-footer-fix" style="height: 100px;"></div>
        </div>
    </div>
    
    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>
    
    <!-- FullCalendar JS - Local -->
    <script src='../assets/js/fullcalendar/main.min.js'></script>
    <script>
        // Verificar que FullCalendar se cargó
        if (typeof FullCalendar !== 'undefined') {
            // Asegurar que esté disponible globalmente
            if (typeof window.FullCalendar === 'undefined') {
                window.FullCalendar = FullCalendar;
            }
        }
    </script>
    <script src='../assets/js/fullcalendar/es.js'></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script src="administrar_agendas/js/app.js"></script>
</body>
</html>

