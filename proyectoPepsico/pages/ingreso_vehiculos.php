<?php
include 'general/middle.php';

$roles_permitidos = ['Administrador', 'Chofer'];
if (!in_array($usuario_rol, $roles_permitidos)) {
    // En lugar de redirigir a index.php, redirige a la página principal del usuario
    $pagina_principal = obtener_pagina_principal($usuario_rol);
    header('Location: ' . $pagina_principal);
    exit();
}
?>

<!DOCTYPE html>
  <head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Ingreso de Vehículos - PepsiCo</title>
    <link rel="stylesheet" href="ingreso_vehiculos/css/ingreso_vehiculos.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <?php
    include 'general/head.php';
    ?>

</head>

<body data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" data-pc-theme_contrast=""
  data-pc-theme="light">
  <?php

  include 'general/sidebar.php';
  include 'general/header.php';

  ?>
  <div class="pc-container">
    <div class="pc-content">
      <?php

      include 'ingreso_vehiculos/components/c_ingresovehiculos.php';

      ?>
    </div>
  </div>
  
  <?php

  include 'general/footer.php';
  include 'general/script.php';
  
  ?>
</body>
<script src="ingreso_vehiculos/js/app.js"></script>
</html>