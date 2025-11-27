<?php
// Configurar zona horaria para Chile (America/Santiago)
// Esto asegura que todas las fechas y horas se manejen en la zona horaria correcta
date_default_timezone_set('America/Santiago');

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

    // Configurar zona horaria de MySQL para que coincida con PHP
    // Obtener el offset de la zona horaria actual de PHP (respeta horario de verano automáticamente)
    try {
        $timezone = new DateTimeZone('America/Santiago');
        $datetime = new DateTime('now', $timezone);
        $offset = $datetime->format('P'); // Formato: +HH:MM o -HH:MM (ej: -03:00 o -04:00)
        $mysqli->query("SET time_zone = '$offset'");
    } catch (Exception $e) {
        // Si falla la configuración de zona horaria, usar UTC-3 como fallback
        // Esto no debería pasar, pero es una medida de seguridad
        error_log("Error configurando zona horaria de MySQL: " . $e->getMessage());
        $mysqli->query("SET time_zone = '-03:00'");
    }

    return $mysqli;
}
?>