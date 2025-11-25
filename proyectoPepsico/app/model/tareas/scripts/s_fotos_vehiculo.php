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
        $queryVehiculo = "SELECT Placa FROM ingreso_vehiculos WHERE ID = '$vehiculo_id' LIMIT 1";
        $resultVehiculo = mysqli_query($conn, $queryVehiculo);
        
        if (!$resultVehiculo) {
            throw new Exception('Error en consulta de vehículo: ' . mysqli_error($conn));
        }

        $vehiculo = mysqli_fetch_assoc($resultVehiculo);
        mysqli_free_result($resultVehiculo);

        if (!$vehiculo) {
            throw new Exception('Vehículo no encontrado');
        }

        $placa = $vehiculo['Placa'];

        // Función auxiliar para construir ruta correcta
        // Las rutas deben ser relativas desde pages/tareas/ hacia la raíz del proyecto
        // Desde pages/tareas/ a la raíz: ../../uploads/fotos/...
        $construirRuta = function($ruta) {
            // Si ya es una URL completa, usarla tal cual
            if (strpos($ruta, 'http://') === 0 || strpos($ruta, 'https://') === 0) {
                return $ruta;
            }
            
            // Si ya empieza con ../, mantenerla (ya es relativa correcta desde pages/)
            if (strpos($ruta, '../') === 0) {
                return $ruta;
            }
            
            // Si contiene "uploads/" pero no empieza con ../
            if (strpos($ruta, 'uploads/') !== false) {
                // Si no tiene ../ al inicio, agregarlo
                if (strpos($ruta, '../') !== 0) {
                    return '../../' . $ruta;
                }
                return $ruta;
            }
            
            // Si solo tiene el nombre del archivo, construir la ruta completa relativa
            if (strpos($ruta, '/') === false && strpos($ruta, '\\') === false) {
                return '../../uploads/fotos/' . $ruta;
            }
            
            // Si empieza con /, es ruta absoluta - convertir a relativa
            if (strpos($ruta, '/') === 0) {
                // Extraer la parte después de /uploads/ si existe
                if (preg_match('/\/uploads\/(.+)$/', $ruta, $matches)) {
                    return '../../uploads/' . $matches[1];
                }
                // Si no tiene uploads, asumir que es desde la raíz del proyecto
                return '../..' . $ruta;
            }
            
            // Por defecto, asumir que es relativa desde pages/tareas/
            return '../../' . $ruta;
        };

        // Función auxiliar para procesar fotos de un JSON
        $procesarFotosJSON = function($fotosJson) use ($construirRuta) {
            $fotos = [];
            if (empty($fotosJson)) {
                return $fotos;
            }
            
            $fotosData = json_decode($fotosJson, true);
            if (!is_array($fotosData)) {
                return $fotos;
            }
            
            foreach ($fotosData as $fotoItem) {
                // Si ya tiene el formato esperado (foto, angulo, fecha), usarlo tal cual
                if (isset($fotoItem['foto'])) {
                    $fotoItem['foto'] = $construirRuta($fotoItem['foto']);
                    $fotos[] = $fotoItem;
                } 
                // Si tiene el formato de solicitudes_agendamiento (ruta, nombre_guardado, etc.)
                elseif (isset($fotoItem['ruta']) || isset($fotoItem['success']) || isset($fotoItem['nombre_guardado'])) {
                    $rutaFoto = '';
                    
                    if (isset($fotoItem['ruta'])) {
                        $rutaFoto = $construirRuta($fotoItem['ruta']);
                    } elseif (isset($fotoItem['success']) && isset($fotoItem['success']['ruta'])) {
                        $rutaFoto = $construirRuta($fotoItem['success']['ruta']);
                    } elseif (isset($fotoItem['nombre_guardado'])) {
                        $rutaFoto = $construirRuta($fotoItem['nombre_guardado']);
                    }
                    
                    if (empty($rutaFoto)) {
                        continue;
                    }
                    
                    // Obtener fecha
                    $fecha = 'Fecha no disponible';
                    if (isset($fotoItem['fecha'])) {
                        $fecha = $fotoItem['fecha'];
                    } elseif (isset($fotoItem['nombre_guardado'])) {
                        $nombre = $fotoItem['nombre_guardado'];
                        if (preg_match('/_(\d{10})_/', $nombre, $matches)) {
                            $timestamp = intval($matches[1]);
                            $fecha = date('d/m/Y H:i', $timestamp);
                        } elseif (preg_match('/_(\d{8,})_/', $nombre, $matches)) {
                            $timestamp = intval($matches[1]);
                            if ($timestamp > 946684800 && $timestamp < 4102444800) {
                                $fecha = date('d/m/Y H:i', $timestamp);
                            }
                        }
                    }
                    
                    // Obtener ángulo
                    $angulo = $fotoItem['angulo'] ?? 'General';
                    if ($angulo === 'General' && isset($fotoItem['nombre_original'])) {
                        $nombreOriginal = strtolower($fotoItem['nombre_original']);
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
            
            return $fotos;
        };

        // Función auxiliar para verificar si una columna existe en una tabla
        $verificarColumna = function($tabla, $columna) use ($conn) {
            $checkColumna = "SHOW COLUMNS FROM `$tabla` LIKE '$columna'";
            $resultCheck = mysqli_query($conn, $checkColumna);
            $existe = ($resultCheck && mysqli_num_rows($resultCheck) > 0);
            if ($resultCheck) {
                mysqli_free_result($resultCheck);
            }
            return $existe;
        };
        
        // Verificar existencia de columnas Fotos en cada tabla
        $columnaFotosSA = $verificarColumna('solicitudes_agendamiento', 'Fotos');
        $columnaFotosIV = $verificarColumna('ingreso_vehiculos', 'Fotos');
        $columnaFotosAM = $verificarColumna('avances_mecanico', 'Fotos');
        
        // Buscar fotos en múltiples fuentes
        $todasLasFotos = [];
        
        // 1. Fotos desde solicitudes_agendamiento (solo si la columna existe)
        if ($columnaFotosSA) {
            $querySA = "SELECT Fotos FROM solicitudes_agendamiento 
                        WHERE Placa = '$placa' 
                        AND Fotos IS NOT NULL 
                        AND Fotos != ''
                        ORDER BY FechaCreacion DESC";
            $resultSA = mysqli_query($conn, $querySA);
            if ($resultSA) {
                while ($row = mysqli_fetch_assoc($resultSA)) {
                    $fotosSA = $procesarFotosJSON($row['Fotos']);
                    $todasLasFotos = array_merge($todasLasFotos, $fotosSA);
                }
                mysqli_free_result($resultSA);
            }
        }
        
        // 2. Fotos desde ingreso_vehiculos (solo si la columna existe)
        if ($columnaFotosIV) {
            $queryIV = "SELECT Fotos FROM ingreso_vehiculos 
                        WHERE Placa = '$placa' 
                        AND Fotos IS NOT NULL 
                        AND Fotos != ''
                        ORDER BY FechaRegistro DESC";
            $resultIV = mysqli_query($conn, $queryIV);
            if ($resultIV) {
                while ($row = mysqli_fetch_assoc($resultIV)) {
                    $fotosIV = $procesarFotosJSON($row['Fotos']);
                    $todasLasFotos = array_merge($todasLasFotos, $fotosIV);
                }
                mysqli_free_result($resultIV);
            }
        }
        
        // 3. Fotos desde avances_mecanico (a través de asignaciones, solo si la columna existe)
        if ($columnaFotosAM) {
            $queryAvances = "SELECT am.Fotos 
                            FROM avances_mecanico am
                            INNER JOIN asignaciones_mecanico a ON am.AsignacionID = a.ID
                            INNER JOIN ingreso_vehiculos iv ON a.VehiculoID = iv.ID
                            WHERE iv.Placa = '$placa'
                            AND am.Fotos IS NOT NULL 
                            AND am.Fotos != ''
                            ORDER BY am.FechaAvance DESC";
            $resultAvances = mysqli_query($conn, $queryAvances);
            if ($resultAvances) {
                while ($row = mysqli_fetch_assoc($resultAvances)) {
                    $fotosAvances = $procesarFotosJSON($row['Fotos']);
                    $todasLasFotos = array_merge($todasLasFotos, $fotosAvances);
                }
                mysqli_free_result($resultAvances);
            }
        }
        
        // Eliminar duplicados basándose en la ruta de la foto
        $fotosUnicas = [];
        $rutasVistas = [];
        foreach ($todasLasFotos as $foto) {
            $ruta = $foto['foto'];
            if (!in_array($ruta, $rutasVistas)) {
                $fotosUnicas[] = $foto;
                $rutasVistas[] = $ruta;
            }
        }
        
        $fotos = $fotosUnicas;
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