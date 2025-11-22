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

        // Obtener información básica del vehículo y fotos desde solicitudes_agendamiento
        $queryVehiculo = "SELECT 
                            COALESCE(sa.Placa, iv.Placa) AS Placa,
                            sa.Fotos
                        FROM ingreso_vehiculos iv
                        LEFT JOIN solicitudes_agendamiento sa ON iv.Placa COLLATE utf8mb4_unicode_ci = sa.Placa COLLATE utf8mb4_unicode_ci
                            AND sa.Estado IN ('Aprobada', 'Ingresado')
                        WHERE iv.ID = '$vehiculo_id'
                        ORDER BY sa.FechaCreacion DESC
                        LIMIT 1";
        $resultVehiculo = mysqli_query($conn, $queryVehiculo);
        
        if (!$resultVehiculo) {
            throw new Exception('Error en consulta de vehículo: ' . mysqli_error($conn));
        }

        $vehiculo = mysqli_fetch_assoc($resultVehiculo);
        mysqli_free_result($resultVehiculo);

        if (!$vehiculo) {
            throw new Exception('Vehículo no encontrado');
        }

        // Procesar fotos desde solicitudes_agendamiento
        $fotos = [];
        if (!empty($vehiculo['Fotos'])) {
            $fotosData = json_decode($vehiculo['Fotos'], true);
            if (is_array($fotosData)) {
                // Transformar el formato de las fotos al formato esperado por el frontend
                foreach ($fotosData as $fotoItem) {
                    // Si ya tiene el formato esperado (foto, angulo, fecha), usarlo tal cual
                    if (isset($fotoItem['foto'])) {
                        $fotos[] = $fotoItem;
                    } 
                    // Si tiene el formato de solicitudes_agendamiento (ruta, nombre_guardado, etc.)
                    elseif (isset($fotoItem['ruta']) || isset($fotoItem['success'])) {
                        // Construir la ruta completa
                        $rutaFoto = '';
                        if (isset($fotoItem['ruta'])) {
                            // La ruta ya viene como "uploads/fotos/archivo.jpg", necesitamos hacerla relativa desde la raíz
                            // Desde pages/tareas/, la ruta sería ../../uploads/fotos/...
                            // Pero mejor usar la ruta tal como está y que el frontend la maneje
                            $ruta = $fotoItem['ruta'];
                            // Si no empieza con ../, agregarlo para que sea relativa desde pages/
                            if (strpos($ruta, '../') !== 0 && strpos($ruta, 'http') !== 0) {
                                $rutaFoto = '../../' . $ruta;
                            } else {
                                $rutaFoto = $ruta;
                            }
                        } elseif (isset($fotoItem['nombre_guardado'])) {
                            // Si solo tiene nombre_guardado, construir la ruta
                            $rutaFoto = '../../uploads/fotos/' . $fotoItem['nombre_guardado'];
                        }
                        
                        // Obtener fecha del nombre del archivo o usar fecha actual
                        $fecha = 'Fecha no disponible';
                        if (isset($fotoItem['nombre_guardado'])) {
                            // Intentar extraer timestamp del nombre (formato: hash_timestamp_nombre.ext)
                            $nombre = $fotoItem['nombre_guardado'];
                            // Buscar timestamp en el formato: hash_timestamp_nombre.ext
                            if (preg_match('/_(\d{10})_/', $nombre, $matches)) {
                                $timestamp = intval($matches[1]);
                                $fecha = date('d/m/Y H:i', $timestamp);
                            } 
                            // Si no encuentra timestamp de 10 dígitos, buscar cualquier número largo
                            elseif (preg_match('/_(\d{8,})_/', $nombre, $matches)) {
                                $timestamp = intval($matches[1]);
                                // Verificar si es un timestamp válido (entre 2000 y 2100)
                                if ($timestamp > 946684800 && $timestamp < 4102444800) {
                                    $fecha = date('d/m/Y H:i', $timestamp);
                                }
                            }
                        }
                        
                        // Obtener ángulo del nombre original si está disponible
                        $angulo = 'General';
                        if (isset($fotoItem['nombre_original'])) {
                            $nombreOriginal = strtolower($fotoItem['nombre_original']);
                            // Intentar detectar el ángulo del nombre del archivo
                            if (strpos($nombreOriginal, 'frontal') !== false) {
                                $angulo = 'Frontal';
                            } elseif (strpos($nombreOriginal, 'trasera') !== false || strpos($nombreOriginal, 'posterior') !== false) {
                                $angulo = 'Trasera';
                            } elseif (strpos($nombreOriginal, 'lateral') !== false || strpos($nombreOriginal, 'lado') !== false) {
                                $angulo = 'Lateral';
                            } elseif (strpos($nombreOriginal, 'interior') !== false) {
                                $angulo = 'Interior';
                            }
                        }
                        
                        $fotos[] = [
                            'foto' => $rutaFoto,
                            'angulo' => $angulo,
                            'fecha' => $fecha,
                            'nombre_original' => $fotoItem['nombre_original'] ?? $fotoItem['nombre_guardado'] ?? 'Sin nombre'
                        ];
                    }
                }
            }
        }
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