<?php
    // Incluir el componente de gestión de solicitudes
    $componentPath = __DIR__ . '/components/c_gestion_solicitudes.php';
    if (file_exists($componentPath)) {
        include $componentPath;
    } else {
        echo '<div class="alert alert-danger">Error: No se pudo cargar el componente de gestión de solicitudes.</div>';
    }
?>

<link rel="stylesheet" href="gestion_solicitudes/css/gestion.css">

