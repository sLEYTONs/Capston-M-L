<?php
include 'general/middle.php';
$roles_permitidos = ['Administrador', 'Recepcionista'];
if (!in_array($usuario_rol, $roles_permitidos)) {
    header('Location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
  <head>
    <title>Marcas Registradas - PepsiCo</title>
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

      include 'marcas/contents.php';

      ?>
    </div>
  </div>
  
  <?php

  include 'general/footer.php';
  include 'general/script.php';
  
  ?>

</body>

</html>