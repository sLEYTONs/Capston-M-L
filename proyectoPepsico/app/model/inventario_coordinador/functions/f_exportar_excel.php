<?php
/**
 * Genera un archivo Excel con formato visual mejorado para el inventario
 */
function exportarInventarioExcel($datos, $filtros = []) {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers para Excel
    $filename = 'inventario_repuestos_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Calcular resumen
    $totalRepuestos = count($datos);
    $totalStock = 0;
    $stockBajo = 0;
    $sinStock = 0;
    $valorTotal = 0;
    
    foreach ($datos as $repuesto) {
        $stock = intval($repuesto['Stock'] ?? 0);
        $minimo = intval($repuesto['StockMinimo'] ?? 0);
        $precio = floatval($repuesto['PrecioUnitario'] ?? 0);
        
        $totalStock += $stock;
        $valorTotal += $stock * $precio;
        
        if ($stock === 0) {
            $sinStock++;
        } elseif ($stock <= $minimo) {
            $stockBajo++;
        }
    }
    
    // Generar HTML simple que Excel puede interpretar
    echo '<html>';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo '.titulo { font-size: 18px; font-weight: bold; color: #1f4788; background-color: #d9e1f2; padding: 15px; text-align: center; }';
    echo '.subtitulo { font-size: 14px; font-weight: bold; color: #2e75b6; background-color: #e7f3ff; padding: 10px; margin-top: 20px; }';
    echo '.resumen { background-color: #f2f2f2; padding: 10px; margin: 10px 0; }';
    echo '.resumen-item { padding: 5px; }';
    echo '.resumen-label { font-weight: bold; color: #333; }';
    echo '.resumen-valor { color: #0066cc; font-size: 14px; }';
    echo 'table { border-collapse: collapse; width: 100%; margin: 10px 0; }';
    echo 'th { background-color: #4472C4; color: white; font-weight: bold; padding: 10px; text-align: left; border: 1px solid #2e5a8a; }';
    echo 'td { padding: 8px; border: 1px solid #d0d0d0; }';
    echo 'tr:nth-child(even) { background-color: #f9f9f9; }';
    echo '.numero { text-align: right; }';
    echo '.moneda { text-align: right; color: #006600; font-weight: bold; }';
    echo '.centro { text-align: center; }';
    echo '.badge-sin-stock { background-color: #dc3545; color: white; padding: 3px 8px; }';
    echo '.badge-stock-bajo { background-color: #ffc107; color: #000; padding: 3px 8px; }';
    echo '.badge-normal { background-color: #28a745; color: white; padding: 3px 8px; }';
    echo '.separador { height: 20px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Título principal
    echo '<div class="titulo">INVENTARIO DE REPUESTOS</div>';
    echo '<div class="separador"></div>';
    
    // Resumen
    echo '<div class="subtitulo">RESUMEN EJECUTIVO</div>';
    echo '<div class="resumen">';
    echo '<div class="resumen-item"><span class="resumen-label">Total Repuestos:</span> <span class="resumen-valor">' . $totalRepuestos . '</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Total Stock:</span> <span class="resumen-valor">' . number_format($totalStock, 0, '.', ',') . ' unidades</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Stock Bajo:</span> <span class="resumen-valor">' . $stockBajo . ' repuestos</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Sin Stock:</span> <span class="resumen-valor">' . $sinStock . ' repuestos</span></div>';
    echo '<div class="resumen-item"><span class="resumen-label">Valor Total del Inventario:</span> <span class="resumen-valor">$' . number_format($valorTotal, 2, '.', ',') . '</span></div>';
    echo '</div>';
    echo '<div class="separador"></div>';
    
    // Tabla de inventario
    if (!empty($datos)) {
        echo '<div class="subtitulo">DETALLE DE REPUESTOS</div>';
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>Código</th>';
        echo '<th>Nombre</th>';
        echo '<th>Categoría</th>';
        echo '<th class="centro">Stock</th>';
        echo '<th class="centro">Stock Mínimo</th>';
        echo '<th class="moneda">Precio Unitario</th>';
        echo '<th class="moneda">Valor Total</th>';
        echo '<th class="centro">Estado</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($datos as $repuesto) {
            $stock = intval($repuesto['Stock'] ?? 0);
            $minimo = intval($repuesto['StockMinimo'] ?? 0);
            $precio = floatval($repuesto['PrecioUnitario'] ?? 0);
            $valorTotalItem = $stock * $precio;
            
            $estado = '';
            $badgeClass = '';
            if ($stock === 0) {
                $estado = 'Sin Stock';
                $badgeClass = 'badge-sin-stock';
            } elseif ($stock <= $minimo) {
                $estado = 'Stock Bajo';
                $badgeClass = 'badge-stock-bajo';
            } else {
                $estado = 'Normal';
                $badgeClass = 'badge-normal';
            }
            
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($repuesto['Codigo'] ?? '-', ENT_QUOTES, 'UTF-8') . '</strong></td>';
            echo '<td>' . htmlspecialchars($repuesto['Nombre'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($repuesto['Categoria'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td class="centro numero">' . $stock . '</td>';
            echo '<td class="centro numero">' . $minimo . '</td>';
            echo '<td class="moneda">$' . number_format($precio, 2, '.', ',') . '</td>';
            echo '<td class="moneda"><strong>$' . number_format($valorTotalItem, 2, '.', ',') . '</strong></td>';
            echo '<td class="centro"><span class="' . $badgeClass . '">' . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . '</span></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    // Pie de página
    echo '<div class="separador"></div>';
    echo '<div style="text-align: center; color: #666; font-size: 11px; margin-top: 30px;">';
    echo 'Generado el ' . date('d/m/Y H:i:s') . ' - Sistema PepsiCo';
    if (!empty($filtros['categoria']) || !empty($filtros['estado']) || !empty($filtros['busqueda'])) {
        echo '<br>Filtros aplicados: ';
        $filtrosAplicados = [];
        if (!empty($filtros['categoria'])) {
            $filtrosAplicados[] = 'Categoría: ' . htmlspecialchars($filtros['categoria'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($filtros['estado'])) {
            $estadoTexto = $filtros['estado'] === 'sin' ? 'Sin Stock' : ($filtros['estado'] === 'bajo' ? 'Stock Bajo' : 'Stock Normal');
            $filtrosAplicados[] = 'Estado: ' . $estadoTexto;
        }
        if (!empty($filtros['busqueda'])) {
            $filtrosAplicados[] = 'Búsqueda: ' . htmlspecialchars($filtros['busqueda'], ENT_QUOTES, 'UTF-8');
        }
        echo implode(' | ', $filtrosAplicados);
    }
    echo '</div>';
    
    echo '</body></html>';
    exit;
}

