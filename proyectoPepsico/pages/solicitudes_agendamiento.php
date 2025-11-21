<?php
require_once 'general/middle.php';

// Verificar acceso
if (!tiene_acceso('solicitudes_agendamiento.php')) {
    redirigir_no_autorizado();
}

$titulo_pagina = "Solicitudes de Agendamiento";
include 'general/head.php';
include 'general/header.php';
include 'general/sidebar.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <?php include 'solicitudes_agendamiento/contents.php'; ?>
    </div>
    <?php include 'general/footer.php'; ?>
</div>

<?php include 'general/script.php'; ?>