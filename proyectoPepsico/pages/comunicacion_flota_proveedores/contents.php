<?php 
// Incluir middle para tener acceso a variables de sesión
if (!isset($usuario_rol)) {
    $usuario_rol = $_SESSION['usuario']['rol'] ?? '';
}
include 'comunicacion_flota_proveedores/components/c_comunicacion.php'; 
?>

