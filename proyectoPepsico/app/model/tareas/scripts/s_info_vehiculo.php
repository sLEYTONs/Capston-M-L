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
                    COALESCE(sa.Placa, iv.Placa) AS Placa, 
                    COALESCE(sa.TipoVehiculo, iv.TipoVehiculo) AS TipoVehiculo, 
                    COALESCE(sa.Marca, iv.Marca) AS Marca, 
                    COALESCE(sa.Modelo, iv.Modelo) AS Modelo, 
                    COALESCE(sa.Anio, iv.Anio) AS Anio,
                    COALESCE(sa.ConductorNombre, iv.ConductorNombre) AS ConductorNombre,
                    sa.Proposito,
                    iv.Kilometraje, 
                    sa.Observaciones
                FROM ingreso_vehiculos iv
                LEFT JOIN solicitudes_agendamiento sa ON iv.Placa COLLATE utf8mb4_unicode_ci = sa.Placa COLLATE utf8mb4_unicode_ci
                    AND sa.Estado IN ('Aprobada', 'Ingresado')
                WHERE iv.ID = '$vehiculo_id'
                ORDER BY sa.FechaCreacion DESC
                LIMIT 1";

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