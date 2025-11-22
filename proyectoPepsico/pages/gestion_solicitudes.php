<?php
require_once 'general/middle.php';

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
    <title>Gestión de Solicitudes de Agendamiento - PepsiCo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />
    <style>
        .fc-event {
            cursor: pointer !important;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .fc-event:hover {
            opacity: 0.8;
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .fc-event.fc-event-selected {
            background-color: #007bff !important;
            border-color: #0056b3 !important;
            z-index: 10;
        }
        #calendario-horas-disponibles {
            min-height: 400px;
        }
        #info-seleccion-hora {
            margin-top: 15px;
        }
    </style>
    
    <?php include 'general/head.php'; ?>
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
                            <h5 class="mb-1">Gestión de Solicitudes de Agendamiento</h5>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Gestión de Solicitudes</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'gestion_solicitudes/contents.php'; ?>
            
            <div class="pc-footer-fix" style="height: 100px;"></div>
        </div>
    </div>
    
    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>
    
    <!-- FullCalendar JS -->
    <script src='../assets/js/plugins/index.global.min.js'></script>
    
    <script src="gestion_solicitudes/js/app.js"></script>
</body>
</html>
