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

        // Obtener información básica del vehículo
        $queryVehiculo = "SELECT Placa FROM ingreso_vehiculos WHERE ID = '$vehiculo_id'";
        $resultVehiculo = mysqli_query($conn, $queryVehiculo);
        
        if (!$resultVehiculo) {
            throw new Exception('Error en consulta de vehículo: ' . mysqli_error($conn));
        }

        $vehiculo = mysqli_fetch_assoc($resultVehiculo);
        mysqli_free_result($resultVehiculo);

        if (!$vehiculo) {
            throw new Exception('Vehículo no encontrado');
        }

        // Obtener fotos del vehículo
        $queryFotos = "SELECT Fotos FROM ingreso_vehiculos WHERE ID = '$vehiculo_id'";
        $resultFotos = mysqli_query($conn, $queryFotos);
        
        $fotos = [];
        if ($resultFotos && $row = mysqli_fetch_assoc($resultFotos)) {
            if ($row['Fotos']) {
                $fotosData = json_decode($row['Fotos'], true);
                if (is_array($fotosData)) {
                    $fotos = $fotosData;
                }
            }
        }
        mysqli_free_result($resultFotos);
        mysqli_close($conn);

        echo json_encode([
            'status' => 'success', 
            'data' => [
                'Placa' => $vehiculo['Placa'],
                'fotos' => $fotos
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al cargar fotos: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
?>