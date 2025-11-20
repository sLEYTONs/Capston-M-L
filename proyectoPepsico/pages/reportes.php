<?php
include 'general/middle.php';

$roles_permitidos = ['Administrador', 'Jefe de Taller', 'Recepcionista'];
if (!in_array($usuario_rol, $roles_permitidos)) {
    $pagina_principal = obtener_pagina_principal($usuario_rol);
    header('Location: ' . $pagina_principal);
    exit();
}
?>

<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Reportes - PepsiCo</title>
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

        include 'reportes/contents.php';

        ?>
    </div>
    </div>

    <?php

    include 'general/footer.php';
    include 'general/script.php';

?>
</body>

</html>