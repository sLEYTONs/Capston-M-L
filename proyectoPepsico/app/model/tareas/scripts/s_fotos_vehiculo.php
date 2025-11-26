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
        // Retorna solo la ruta relativa desde la raíz del proyecto (uploads/fotos/...)
        // El frontend construirá la ruta absoluta completa
        $construirRuta = function($ruta) {
            // Si ya es una URL completa, usarla tal cual
            if (strpos($ruta, 'http://') === 0 || strpos($ruta, 'https://') === 0) {
                return $ruta;
            }
            
            // Limpiar la ruta de espacios y caracteres especiales
            $ruta = trim($ruta);
            
            // Remover cualquier ruta absoluta o dominio
            $ruta = preg_replace('/^https?:\/\/[^\/]+\//', '', $ruta);
            
            // Si contiene rutas relativas como ../../uploads/, extraer solo uploads/...
            if (preg_match('/\.\.\/\.\.\/uploads\/(.+)$/', $ruta, $matches)) {
                return 'uploads/' . $matches[1];
            }
            
            // Si contiene ../uploads/, extraer solo uploads/...
            if (preg_match('/\.\.\/uploads\/(.+)$/', $ruta, $matches)) {
                return 'uploads/' . $matches[1];
            }
            
            // CASO MÁS COMÚN: Si empieza directamente con "uploads/" (sin / al inicio)
            // Esto es lo que viene de la BD: "uploads/fotos/archivo.jpg"
            if (strpos($ruta, 'uploads/') === 0) {
                return $ruta;
            }
            
            // Si contiene "uploads/" pero no empieza con uploads/
            if (strpos($ruta, 'uploads/') !== false) {
                // Extraer solo la parte de uploads/...
                if (preg_match('/(uploads\/.+)$/', $ruta, $matches)) {
                    return $matches[1];
                }
            }
            
            // Si empieza con /, removerlo y buscar uploads/
            if (strpos($ruta, '/') === 0) {
                $ruta = ltrim($ruta, '/');
                // Si contiene Capston-M-L o proyectoPepsico, extraer solo la parte de uploads
                if (preg_match('/(?:Capston-M-L|proyectoPepsico)[\/\\\\]uploads[\/\\\\](.+)$/i', $ruta, $matches)) {
                    return 'uploads/' . str_replace('\\', '/', $matches[1]);
                }
                // Si tiene uploads/ después de remover /
                if (preg_match('/(uploads\/.+)$/', $ruta, $matches)) {
                    return $matches[1];
                }
            }
            
            // Si contiene rutas de Windows o rutas absolutas del proyecto
            if (strpos($ruta, 'Capston-M-L') !== false || strpos($ruta, 'proyectoPepsico') !== false) {
                // Extraer solo la parte de uploads
                if (preg_match('/(uploads[\/\\\\].+)$/i', $ruta, $matches)) {
                    return str_replace('\\', '/', $matches[1]);
                }
            }
            
            // Si solo tiene el nombre del archivo, construir la ruta completa
            if (strpos($ruta, '/') === false && strpos($ruta, '\\') === false) {
                return 'uploads/fotos/' . $ruta;
            }
            
            // Por defecto, asumir que es desde uploads/
            return 'uploads/fotos/' . basename($ruta);
        };

        // Función auxiliar para procesar fotos de un JSON
        $procesarFotosJSON = function($fotosJson) use ($construirRuta) {
            $fotos = [];
            if (empty($fotosJson)) {
                return $fotos;
            }
            
            $fotosData = json_decode($fotosJson, true);
            
            // Si no es un array, puede ser un objeto único
            if (!is_array($fotosData)) {
                return $fotos;
            }
            
            // Verificar si es un objeto único (array asociativo con claves como 'ruta', 'success', etc.)
            // en lugar de un array numérico
            $esObjetoUnico = false;
            if (isset($fotosData['ruta']) || (isset($fotosData['success']) && isset($fotosData['nombre_guardado']))) {
                $esObjetoUnico = true;
                $fotosData = [$fotosData]; // Convertir a array para procesarlo
            }
            
            foreach ($fotosData as $fotoItem) {
                // Asegurar que $fotoItem es un array
                if (!is_array($fotoItem)) {
                    continue;
                }
                // Si ya tiene el formato esperado (foto, angulo, fecha), usarlo tal cual
                if (isset($fotoItem['foto'])) {
                    $fotoItem['foto'] = $construirRuta($fotoItem['foto']);
                    $fotos[] = $fotoItem;
                } 
                // Si tiene el formato de solicitudes_agendamiento (ruta, nombre_guardado, etc.)
                elseif (isset($fotoItem['ruta']) || isset($fotoItem['success']) || isset($fotoItem['nombre_guardado'])) {
                    $rutaFoto = '';
                    
                    // Prioridad: ruta > success.ruta > nombre_guardado
                    if (isset($fotoItem['ruta']) && !empty($fotoItem['ruta'])) {
                        $rutaFoto = $fotoItem['ruta'];
                    } elseif (isset($fotoItem['success'])) {
                        // Si success es un array con ruta
                        if (is_array($fotoItem['success']) && isset($fotoItem['success']['ruta'])) {
                            $rutaFoto = $fotoItem['success']['ruta'];
                        }
                        // Si success es true y hay ruta en el mismo nivel
                        elseif ($fotoItem['success'] === true && isset($fotoItem['ruta'])) {
                            $rutaFoto = $fotoItem['ruta'];
                        }
                    }
                    
                    // Si aún no tenemos ruta, usar nombre_guardado
                    if (empty($rutaFoto) && isset($fotoItem['nombre_guardado']) && !empty($fotoItem['nombre_guardado'])) {
                        // Si solo tenemos el nombre, construir la ruta completa
                        $rutaFoto = 'uploads/fotos/' . $fotoItem['nombre_guardado'];
                    }
                    
                    if (empty($rutaFoto)) {
                        continue;
                    }
                    
                    // Limpiar la ruta: remover barras invertidas de escape JSON
                    $rutaFoto = str_replace('\\/', '/', $rutaFoto);
                    $rutaFoto = str_replace('\\', '/', $rutaFoto);
                    $rutaFoto = trim($rutaFoto);
                    
                    // Remover cualquier ruta absoluta o dominio
                    $rutaFoto = preg_replace('/^https?:\/\/[^\/]+\//', '', $rutaFoto);
                    $rutaFoto = preg_replace('/^\/[^\/]+/', '', $rutaFoto); // Remover /Capston-M-L o /proyectoPepsico
                    
                    // Aplicar construcción de ruta relativa
                    $rutaFoto = $construirRuta($rutaFoto);
                    
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
?>