<?php
require_once __DIR__ . '/../../../config/conexion.php';

/**
 * Genera un reporte semanal
 */
/**
 * Conexión segura sin die() - retorna recurso mysqli
 */
function conectarPepsicoSeguro() {
    try {
        $mysqli = @mysqli_connect("localhost", "root", "", "Pepsico");
        
        if (!$mysqli || mysqli_connect_errno()) {
            error_log("Error de conexión: " . (mysqli_connect_error() ?: "Error desconocido"));
            return null;
        }
        
        if (!mysqli_set_charset($mysqli, "utf8mb4")) {
            error_log("Error cargando charset: " . mysqli_error($mysqli));
            mysqli_close($mysqli);
            return null;
        }
        
        return $mysqli;
    } catch (Exception $e) {
        error_log("Excepción en conexión: " . $e->getMessage());
        return null;
    }
}

/**
 * Genera un reporte semanal
 */
function generarReporteSemanal($semana) {
    $conn = conectarPepsicoSeguro();
    if (!$conn) {
        return ['status' => 'error', 'message' => 'Error de conexión a la base de datos'];
    }

    // Parsear semana (formato: YYYY-Www)
    $partes = explode('-W', $semana);
    if (count($partes) !== 2) {
        mysqli_close($conn);
        return ['status' => 'error', 'message' => 'Formato de semana inválido'];
    }

    $año = intval($partes[0]);
    $numeroSemana = intval($partes[1]);

    // Calcular fecha inicio y fin de semana
    $fechaInicio = new DateTime();
    $fechaInicio->setISODate($año, $numeroSemana, 1);
    $fechaInicio->setTime(0, 0, 0);
    
    $fechaFin = clone $fechaInicio;
    $fechaFin->modify('+6 days');
    $fechaFin->setTime(23, 59, 59);

    $fechaInicioStr = $fechaInicio->format('Y-m-d H:i:s');
    $fechaFinStr = $fechaFin->format('Y-m-d H:i:s');

    $reporte = [
        'resumen' => [
            'vehiculos_atendidos' => 0,
            'total_gastos' => 0,
            'repuestos_utilizados' => 0,
            'tiempo_promedio' => 0
        ],
        'vehiculos' => [],
        'gastos' => [],
        'repuestos' => []
    ];

    // Vehículos atendidos en la semana
    // Usar la fecha del último avance con estado 'Completado' o NOW() si no está completado
    $query = "SELECT DISTINCT v.ID, v.Placa, v.Marca, v.Modelo,
                     a.FechaAsignacion as FechaIngreso,
                     COALESCE(
                         (SELECT MAX(am.FechaAvance) 
                          FROM avances_mecanico am 
                          WHERE am.AsignacionID = a.ID 
                          AND am.Estado = 'Completado'),
                         NOW()
                     ) as FechaSalida,
                     a.Estado,
                     DATEDIFF(
                         COALESCE(
                             (SELECT MAX(am.FechaAvance) 
                              FROM avances_mecanico am 
                              WHERE am.AsignacionID = a.ID 
                              AND am.Estado = 'Completado'),
                             NOW()
                         ),
                         a.FechaAsignacion
                     ) as DiasEnTaller,
                     'Mantenimiento' as Servicio
              FROM asignaciones_mecanico a
              INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
              WHERE a.FechaAsignacion >= '$fechaInicioStr' 
                AND a.FechaAsignacion <= '$fechaFinStr'
              ORDER BY a.FechaAsignacion DESC";

    $result = mysqli_query($conn, $query);
    $vehiculos = [];
    $totalDias = 0;
    $contadorVehiculos = 0;

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $vehiculos[] = $row;
            $totalDias += intval($row['DiasEnTaller']);
            $contadorVehiculos++;
        }
        mysqli_free_result($result);
    }

    $reporte['vehiculos'] = $vehiculos;
    $reporte['resumen']['vehiculos_atendidos'] = $contadorVehiculos;
    $reporte['resumen']['tiempo_promedio'] = $contadorVehiculos > 0 ? round($totalDias / $contadorVehiculos, 1) : 0;

    // Gastos (internos y externos)
    $gastos = [];

    // Gastos internos (repuestos)
    $query = "SELECT 
                a.FechaAsignacion as Fecha,
                v.Placa,
                CONCAT(v.Marca, ' ', v.Modelo) as Vehiculo,
                'Interno' as Tipo,
                CONCAT('Repuestos: ', GROUP_CONCAT(r.Nombre SEPARATOR ', ')) as Concepto,
                SUM(ra.Cantidad * ra.PrecioUnitario) as Costo
              FROM repuestos_asignacion ra
              INNER JOIN asignaciones_mecanico a ON ra.AsignacionID = a.ID
              INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
              INNER JOIN repuestos r ON ra.RepuestoID = r.ID
              WHERE a.FechaAsignacion >= '$fechaInicioStr' 
                AND a.FechaAsignacion <= '$fechaFinStr'
              GROUP BY a.ID, a.FechaAsignacion, v.Placa, v.Marca, v.Modelo";

    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $gastos[] = $row;
            $reporte['resumen']['total_gastos'] += floatval($row['Costo']);
        }
        mysqli_free_result($result);
    }

    // Gastos externos
    $checkTable = "SHOW TABLES LIKE 'gastos_externos'";
    $resultCheck = mysqli_query($conn, $checkTable);
    if ($resultCheck && mysqli_num_rows($resultCheck) > 0) {
        $query = "SELECT 
                    ge.Fecha,
                    v.Placa,
                    CONCAT(v.Marca, ' ', v.Modelo) as Vehiculo,
                    'Externo' as Tipo,
                    CONCAT(ge.TallerNombre, ' - ', ge.Servicio) as Concepto,
                    ge.CostoTotal as Costo
                  FROM gastos_externos ge
                  INNER JOIN ingreso_vehiculos v ON ge.VehiculoID = v.ID
                  WHERE ge.Fecha >= DATE('$fechaInicioStr') 
                    AND ge.Fecha <= DATE('$fechaFinStr')";

        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $gastos[] = $row;
                $reporte['resumen']['total_gastos'] += floatval($row['Costo']);
            }
            mysqli_free_result($result);
        }
    }

    $reporte['gastos'] = $gastos;

    // Repuestos utilizados
    $query = "SELECT 
                r.Codigo,
                r.Nombre,
                SUM(ra.Cantidad) as Cantidad,
                ra.PrecioUnitario,
                SUM(ra.Cantidad * ra.PrecioUnitario) as Total,
                v.Placa as Vehiculo
              FROM repuestos_asignacion ra
              INNER JOIN asignaciones_mecanico a ON ra.AsignacionID = a.ID
              INNER JOIN repuestos r ON ra.RepuestoID = r.ID
              INNER JOIN ingreso_vehiculos v ON a.VehiculoID = v.ID
              WHERE a.FechaAsignacion >= '$fechaInicioStr' 
                AND a.FechaAsignacion <= '$fechaFinStr'
              GROUP BY r.ID, r.Codigo, r.Nombre, ra.PrecioUnitario, v.Placa
              ORDER BY Total DESC";

    $result = mysqli_query($conn, $query);
    $repuestos = [];
    $totalRepuestos = 0;

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $repuestos[] = $row;
            $totalRepuestos += intval($row['Cantidad']);
        }
        mysqli_free_result($result);
    }

    $reporte['repuestos'] = $repuestos;
    $reporte['resumen']['repuestos_utilizados'] = $totalRepuestos;

    mysqli_close($conn);
    return ['status' => 'success', 'data' => $reporte];
}

