<head>
    <style>
        html {
            box-sizing: border-box;
            }

            *, *:before, *:after {
            box-sizing: inherit;
            }

            /* Prevenir scroll horizontal */
            body {
            overflow-x: hidden;
            }

            /* Asegurar que los contenedores se expandan correctamente */
            .pc-container {
            min-height: calc(100vh - 140px); /* Ajusta según la altura de header + footer */
            }

            /* Para tablas largas dentro de cards */
            .card-body .table-responsive {
            max-height: 60vh;
            overflow-y: auto;
            }

            /* Para formularios largos */
            .form-container {
            min-height: 400px;
            }

            /* Utilidad para contenido que necesita espacio extra */
            .content-with-footer-space {
            padding-bottom: 2rem;
            }

            /* Custom Page Header fijo arriba a la izquierda (después del sidebar) */
            .custom-page-header-wrapper {
            height: 55px; /* La altura de tu custom-page-header, incluyendo padding */
            }
            
            /* Estilos personalizados para custom-page-header */
            html body .pc-container .custom-page-header,
            html body .custom-page-header,
            body .pc-container .custom-page-header,
            body .custom-page-header,
            .custom-page-header {
            display: flex !important;
            align-items: center !important;
            position: fixed !important;
            top: var(--header-height, 60px) !important; /* Pegado al header principal */
            left: 280px !important;
            right: 0 !important;
            z-index: 1023 !important;
            min-height: 55px !important;
            padding: 13px 0px !important;
            background: #f8f9fa !important;
            border-radius: 8px !important;
            width: auto !important;
            margin: 0 !important;
            margin-top: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            margin-bottom: 0 !important;
            box-shadow: none !important;
            }

            /* Page-block dentro del header para mejor control */
            .custom-page-header .page-block {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 0 2rem; /* Padding horizontal dentro del page-block */
            }

            /* Contenido del header fijo en su posición */
            .custom-page-header .page-block .row {
            margin-left: 3rem !important; /* Margen izquierdo fijo para mantener posición */
            margin-right: 0;
            }

            .custom-page-header .page-block .col-md-12 {
            padding-left: 0;
            padding-right: 0;
            }

            .custom-page-header h5 {
            margin-left: 0 !important;
            margin-bottom: 0.5rem !important;
            }

            .custom-page-header .breadcrumb {
            margin-left: 0 !important;
            margin-bottom: 0 !important;
            padding-left: 0;
            }

            /* Container con margen para compensar el sidebar */
            .pc-container {
            margin-left: 0; /* Iniciar sin margen (sidebar oculto por defecto) */
            width: 100%;
            min-height: calc(100vh - 140px);
            transition: margin-left 0.3s ease, width 0.3s ease; /* Transición suave */
            }
            
            /* Cuando el sidebar está visible, ajustar el contenedor */
            .pc-sidebar:not(.pc-sidebar-hide) ~ .pc-container,
            body:has(.pc-sidebar:not(.pc-sidebar-hide)) .pc-container {
            margin-left: 280px;
            width: calc(100% - 280px);
            }
            
            /* Transición suave del sidebar para hover */
            @media (min-width: 1025px) {
            .pc-sidebar {
                transition: transform 0.3s ease !important; /* Transición más rápida */
                will-change: transform; /* Optimización de rendimiento */
            }
            .pc-sidebar.pc-sidebar-hide {
                transform: translateX(-100%) !important; /* Ocultar con transform sin cambiar width */
            }
            .pc-sidebar:not(.pc-sidebar-hide) {
                transform: translateX(0) !important; /* Mostrar sidebar */
            }
            .pc-sidebar ~ .pc-header {
                transition: left 0.3s ease !important;
            }
            .pc-sidebar ~ .pc-footer,
            .pc-sidebar ~ .pc-container {
                transition: margin-left 0.3s ease, width 0.3s ease !important; /* Transición suave */
            }
            
            /* Ajustar contenedor cuando sidebar está oculto */
            .pc-sidebar.pc-sidebar-hide ~ .pc-container {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            /* Ajustar contenedor cuando sidebar está visible */
            .pc-sidebar:not(.pc-sidebar-hide) ~ .pc-container {
                margin-left: 280px !important;
                width: calc(100% - 280px) !important;
            }
            
            /* Ocultar botones del header en desktop */
            .pc-sidebar-collapse,
            #sidebar-hide {
                display: none !important;
            }
            }

            /* Contenido centrado en la página */
            .pc-content {
            margin-top: calc(var(--header-height, 60px) + var(--page-header-height, 55px)); /* Altura del pc-header + custom-page-header */
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 1rem;
            padding-top: 1rem; /* Pequeño espacio después del header */
            padding-bottom: 6rem !important; /* Espacio amplio para separar del footer */
            width: 100%;
            }

            /* Responsive para tablets */
            @media (max-width: 1200px) and (min-width: 1025px) {
            .custom-page-header {
                top: var(--header-height, 60px);
                padding: 1rem 0;
                margin-left: 1.5rem;
            }
            .custom-page-header .page-block {
                padding: 0 1.5rem;
            }
            .custom-page-header .page-block .row {
                margin-left: 3rem !important; /* Mantener posición fija */
            }
            .pc-content {
                margin-top: calc(var(--header-height, 60px) + var(--page-header-height, 85px));
                padding-bottom: 6rem !important; /* Espacio amplio para separar del footer */
            }
            }

            /* Responsive para tablets pequeñas */
            @media (max-width: 1024px) and (min-width: 769px) {
            .custom-page-header {
                top: var(--header-height, 60px) !important;
                left: auto !important;
                padding: 1rem 0;
                margin-left: 1.5rem;
            }
            .custom-page-header .page-block {
                padding: 0 1.5rem;
            }
            .custom-page-header .page-block .row {
                margin-left: 3rem !important; /* Mantener posición fija */
            }
            .pc-container {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .pc-content {
                margin-top: calc(var(--header-height, 60px) + var(--page-header-height, 80px));
                padding: 0 1rem;
                padding-bottom: 5rem !important; /* Espacio amplio para separar del footer */
            }
            }

            /* Responsive para móviles */
            @media (max-width: 768px) {
            .custom-page-header {
                top: var(--header-height, 60px) !important;
                left: auto !important;
                padding: 0.875rem 0;
                margin-left: 1rem;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            }
            .custom-page-header .page-block {
                padding: 0 1rem;
            }
            .custom-page-header .page-block .row {
                margin-left: 2rem !important; /* Posición fija en móviles */
            }
            .pc-container {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .pc-content {
                margin-top: calc(var(--header-height, 60px) + var(--page-header-height, 75px));
                padding: 0 0.75rem;
                padding-bottom: 5rem !important; /* Espacio amplio para separar del footer */
            }
            .custom-page-header h5 {
                font-size: 1rem;
                margin-bottom: 0.25rem !important;
            }
            .custom-page-header .breadcrumb {
                font-size: 0.8rem;
                margin-bottom: 0;
            }
            }

            /* Responsive para móviles pequeños */
            @media (max-width: 480px) {
            .custom-page-header {
                top: var(--header-height, 60px) !important;
                padding: 0.75rem 0;
                margin-left: 0.75rem;
            }
            .custom-page-header .page-block {
                padding: 0 0.75rem;
            }
            .custom-page-header .page-block .row {
                margin-left: 1rem !important; /* Posición fija en móviles pequeños */
            }
            .pc-content {
                margin-top: calc(var(--header-height, 60px) + var(--page-header-height, 70px));
                padding: 0 0.5rem;
                padding-bottom: 4rem !important; /* Espacio amplio para separar del footer */
            }
            .custom-page-header h5 {
                font-size: 0.9rem;
            }
            .custom-page-header .breadcrumb {
                font-size: 0.75rem;
            }
            }
</style>

    <title>Portal | PepsiCo</title>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">   

    <!-- [Meta Tags] -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- [Favicon] -->
    <link rel="icon" href="../assets/images/pepsicoLogo.png" type="image/svg+xml">

    <!-- [Fonts] -->
    <link rel="stylesheet" href="../assets/fonts/inter/inter.css" id="main-font-link">
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css">  <!-- Tabler Icons -->
    <link rel="stylesheet" href="../assets/fonts/feather.css">            <!-- Feather Icons -->
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css">       <!-- Font Awesome -->
    <link rel="stylesheet" href="../assets/fonts/material.css">          <!-- Material Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- [CSS Templates / Styles] -->
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link">
    <link rel="stylesheet" href="../assets/css/style-preset.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    

        <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">

    <!-- [SweetAlert2 CSS] -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- [Highcharts] -->
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
    
    <!-- [Responsive DataTables Helper] -->
    <script src="../assets/js/responsive-datatables.js"></script>

    <!-- CSS personalizado para custom-page-header - debe ir al final para sobrescribir todos los estilos -->
    <style>
        /* Estilos personalizados para custom-page-header con máxima especificidad y prioridad */
        html body .pc-container .custom-page-header,
        html body .custom-page-header,
        body .pc-container .custom-page-header,
        body .custom-page-header,
        .custom-page-header {
            display: flex !important;
            align-items: center !important;
            position: fixed !important;
            top: var(--header-height, 60px) !important; /* Pegado al header principal */
            left: 280px !important;
            right: 0 !important;
            z-index: 1023 !important;
            min-height: 55px !important;
            padding: 13px 0px !important;
            background: #f8f9fa !important;
            border-radius: 8px !important;
            width: auto !important;
            margin: 0 !important;
            margin-top: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            margin-bottom: 0 !important;
            box-shadow: none !important;
        }
    </style>

</head>
