<?php
function conectar_Pepsico()
{
    $mysqli = new mysqli("localhost", "root", "", "Pepsico");

    // Verificar conexión
    if ($mysqli->connect_errno) {
        die("Error de conexión a la base de datos: " . $mysqli->connect_error);
    }

    // Forzar charset a UTF-8
    if (!$mysqli->set_charset("utf8mb4")) {
        die("Error cargando el conjunto de caracteres utf8mb4: " . $mysqli->error);
    }

    return $mysqli;
}
?>