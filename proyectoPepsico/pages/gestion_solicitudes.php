<?php
require_once 'general/middle.php';

// Verificar acceso - Solo Supervisor y Administrador
if (!in_array($usuario_rol, ['Supervisor', 'Administrador'])) {
    redirigir_no_autorizado();
}

$titulo_pagina = "Gestión de Solicitudes de Agendamiento";
include 'general/head.php';
include 'general/header.php';
include 'general/sidebar.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <?php include 'gestion_solicitudes/contents.php'; ?>
    </div>
    <?php include 'general/footer.php'; ?>
</div>

<?php include 'general/script.php'; ?>

