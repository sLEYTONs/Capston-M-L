<?php
require '../functions/f_tareas.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehiculo_id'])) {
    $vehiculo_id = intval($_POST['vehiculo_id']);

    try {
        $conn = conectar_Pepsico();
        if (!$conn) {
            throw new Exception('Error de conexión a la base de datos');
        }

        $vehiculo_id = mysqli_real_escape_string($conn, $vehiculo_id);

        $query = "SELECT 
                    Placa, TipoVehiculo, Marca, Modelo, Color, Anio,
                    ConductorNombre, ConductorTelefono,
                    Proposito, Area, EstadoIngreso, 
                    Kilometraje, Observaciones
                FROM ingreso_vehiculos 
                WHERE ID = '$vehiculo_id'";

        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception('Error en consulta: ' . mysqli_error($conn));
        }

        $vehiculo = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        mysqli_close($conn);

        if ($vehiculo) {
            echo json_encode(['status' => 'success', 'data' => $vehiculo]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Vehículo no encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al cargar información: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
?>