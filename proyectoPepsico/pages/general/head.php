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

            /* Page Header fijo arriba a la izquierda (después del sidebar) */
            .page-header {
            position: fixed;
            top: 0;
            left: 280px; /* Ancho del sidebar */
            right: 0;
            z-index: 1025; /* Por encima de otros elementos */
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1.25rem 0; /* Solo padding vertical */
            margin-top: 0;
            margin-left: 2rem; /* Margen izquierdo para separación visual */
            transition: left 0.3s ease, margin-left 0.3s ease; /* Transición suave al cambiar tamaño */
            }

            /* Page-block dentro del header para mejor control */
            .page-header .page-block {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 0 2rem; /* Padding horizontal dentro del page-block */
            }

            /* Contenido del header fijo en su posición */
            .page-header .page-block .row {
            margin-left: 3rem !important; /* Margen izquierdo fijo para mantener posición */
            margin-right: 0;
            }

            .page-header .page-block .col-md-12 {
            padding-left: 0;
            padding-right: 0;
            }

            .page-header h5 {
            margin-left: 0 !important;
            margin-bottom: 0.5rem !important;
            }

            .page-header .breadcrumb {
            margin-left: 0 !important;
            margin-bottom: 0 !important;
            padding-left: 0;
            }

            /* Container con margen para compensar el sidebar */
            .pc-container {
            margin-left: 280px; /* Compensar el sidebar fijo */
            width: calc(100% - 280px);
            min-height: calc(100vh - 140px);
            transition: margin-left 0.3s ease, width 0.3s ease;
            }

            /* Contenido centrado en la página */
            .pc-content {
            margin-top: 90px; /* Altura del page-header con padding */
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 1rem;
            width: 100%;
            }

            /* Responsive para tablets */
            @media (max-width: 1200px) and (min-width: 1025px) {
            .page-header {
                padding: 1rem 0;
                margin-left: 1.5rem;
            }
            .page-header .page-block {
                padding: 0 1.5rem;
            }
            .page-header .page-block .row {
                margin-left: 3rem !important; /* Mantener posición fija */
            }
            .pc-content {
                margin-top: 85px;
            }
            }

            /* Responsive para tablets pequeñas */
            @media (max-width: 1024px) and (min-width: 769px) {
            .page-header {
                left: 0;
                padding: 1rem 0;
                margin-left: 1.5rem;
            }
            .page-header .page-block {
                padding: 0 1.5rem;
            }
            .page-header .page-block .row {
                margin-left: 3rem !important; /* Mantener posición fija */
            }
            .pc-container {
                margin-left: 0;
                width: 100%;
            }
            .pc-content {
                margin-top: 80px;
                padding: 0 1rem;
            }
            }

            /* Responsive para móviles */
            @media (max-width: 768px) {
            .page-header {
                left: 0;
                padding: 0.875rem 0;
                margin-left: 1rem;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            }
            .page-header .page-block {
                padding: 0 1rem;
            }
            .page-header .page-block .row {
                margin-left: 2rem !important; /* Posición fija en móviles */
            }
            .pc-container {
                margin-left: 0;
                width: 100%;
            }
            .pc-content {
                margin-top: 75px;
                padding: 0 0.75rem;
            }
            .page-header h5 {
                font-size: 1rem;
                margin-bottom: 0.25rem !important;
            }
            .page-header .breadcrumb {
                font-size: 0.8rem;
                margin-bottom: 0;
            }
            }

            /* Responsive para móviles pequeños */
            @media (max-width: 480px) {
            .page-header {
                padding: 0.75rem 0;
                margin-left: 0.75rem;
            }
            .page-header .page-block {
                padding: 0 0.75rem;
            }
            .page-header .page-block .row {
                margin-left: 1rem !important; /* Posición fija en móviles pequeños */
            }
            .pc-content {
                margin-top: 70px;
                padding: 0 0.5rem;
            }
            .page-header h5 {
                font-size: 0.9rem;
            }
            .page-header .breadcrumb {
                font-size: 0.75rem;
            }
            }
</style>

    <title>Portal | PepsiCo</title>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">   

    <!-- [Meta Tags] -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
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

</head>
