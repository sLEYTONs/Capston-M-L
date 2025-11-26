// Aplicación para Inventario del Coordinador de Zona
class InventarioCoordinador {
    constructor() {
        this.dataTable = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/inventario_coordinador/scripts/s_inventario_coordinador.php';
        }
        return '../../app/model/inventario_coordinador/scripts/s_inventario_coordinador.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.cargarEstadisticas();
        this.cargarCategorias();
        // El DataTable carga automáticamente los datos al inicializarse
        this.inicializarDataTable();
    }

    inicializarEventos() {
        $('#btn-actualizar-inventario').on('click', () => {
            this.cargarInventario();
            this.cargarEstadisticas();
        });

        $('#filtro-categoria, #filtro-estado, #filtro-busqueda').on('change keyup', () => {
            if (this.dataTable) {
                this.dataTable.draw();
            }
        });

        $('#btn-exportar').on('click', () => {
            this.exportarInventario();
        });

        $(document).on('click', '.btn-ver-movimientos', (e) => {
            const repuestoId = $(e.currentTarget).data('id');
            const repuestoNombre = $(e.currentTarget).data('nombre');
            this.verMovimientos(repuestoId, repuestoNombre);
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#tabla-inventario').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles en la tabla",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros coincidentes",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "aria": {
                    "sortAscending": ": activar para ordenar la columna de manera ascendente",
                    "sortDescending": ": activar para ordenar la columna de manera descendente"
                }
            },
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [[3, 'asc']], // Ordenar por stock
            responsive: true,
            processing: true,
            serverSide: false,
            ajax: {
                url: this.baseUrl,
                type: 'POST',
                data: (d) => {
                    d.accion = 'obtener_inventario';
                    d.categoria = $('#filtro-categoria').val();
                    d.estado = $('#filtro-estado').val();
                    d.busqueda = $('#filtro-busqueda').val();
                },
                dataSrc: (json) => {
                    if (json.status === 'success' && json.data && Array.isArray(json.data)) {
                        return json.data;
                    } else {
                        console.error('Error en respuesta:', json);
                        return [];
                    }
                },
                error: (xhr, error, thrown) => {
                    // Ignorar errores de abort (cuando se cancela una petición)
                    if (error !== 'abort' && thrown !== 'abort') {
                        console.error('Error AJAX:', error, thrown);
                        if (xhr.responseText) {
                            console.error('Respuesta:', xhr.responseText);
                        }
                    }
                }
            },
            columns: [
                { data: 'Codigo' },
                { data: 'Nombre' },
                { data: 'Categoria' },
                { 
                    data: 'Stock',
                    render: (data, type, row) => {
                        const stock = parseInt(data);
                        const minimo = parseInt(row.StockMinimo);
                        let badgeClass = 'success';
                        let icon = 'check-circle';
                        
                        if (stock === 0) {
                            badgeClass = 'danger';
                            icon = 'times-circle';
                        } else if (stock <= minimo) {
                            badgeClass = 'warning';
                            icon = 'exclamation-circle';
                        }
                        return `<span class="badge bg-${badgeClass}"><i class="fas fa-${icon} me-1"></i>${stock}</span>`;
                    }
                },
                { data: 'StockMinimo' },
                { 
                    data: 'PrecioUnitario',
                    render: (data) => `$${parseFloat(data || 0).toFixed(2)}`
                },
                { 
                    data: null,
                    render: (data) => {
                        const valor = parseFloat(data.Stock || 0) * parseFloat(data.PrecioUnitario || 0);
                        return `$${valor.toFixed(2)}`;
                    }
                },
                { 
                    data: null,
                    render: (data) => {
                        const stock = parseInt(data.Stock);
                        const minimo = parseInt(data.StockMinimo);
                        if (stock === 0) return '<span class="badge bg-danger">Sin Stock</span>';
                        if (stock <= minimo) return '<span class="badge bg-warning">Stock Bajo</span>';
                        return '<span class="badge bg-success">Normal</span>';
                    }
                },
                { 
                    data: 'FechaActualizacion',
                    render: (data) => {
                        if (!data) return '-';
                        return new Date(data).toLocaleDateString('es-ES');
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data) => {
                        return `
                            <button class="btn btn-sm btn-info btn-ver-movimientos" 
                                    data-id="${data.ID}" 
                                    data-nombre="${data.Nombre}"
                                    title="Ver Movimientos">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        `;
                    }
                }
            ]
        });
    }

    cargarEstadisticas() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_estadisticas' },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    $('#total-repuestos').text(response.data.total_repuestos || 0);
                    $('#stock-bajo').text(response.data.stock_bajo || 0);
                    $('#sin-stock').text(response.data.sin_stock || 0);
                    $('#valor-total').text('$' + parseFloat(response.data.valor_total || 0).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar estadísticas:', error);
            }
        });
    }

    cargarCategorias() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_categorias' },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    const select = $('#filtro-categoria');
                    response.data.forEach(categoria => {
                        select.append(`<option value="${categoria}">${categoria}</option>`);
                    });
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar categorías:', error);
            }
        });
    }

    cargarInventario() {
        if (this.dataTable) {
            // Usar callback para evitar errores de abort
            this.dataTable.ajax.reload(null, false);
        }
    }

    verMovimientos(repuestoId, repuestoNombre) {
        $('#modal-repuesto-nombre').text(repuestoNombre);
        
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { 
                accion: 'obtener_movimientos',
                repuesto_id: repuestoId
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    const tbody = $('#tabla-movimientos tbody');
                    tbody.empty();
                    
                    if (response.data.length === 0) {
                        tbody.append('<tr><td colspan="6" class="text-center">No hay movimientos registrados</td></tr>');
                    } else {
                        response.data.forEach(movimiento => {
                            tbody.append(`
                                <tr>
                                    <td>${movimiento.Fecha}</td>
                                    <td><span class="badge bg-${movimiento.Tipo === 'Salida' ? 'danger' : 'success'}">${movimiento.Tipo}</span></td>
                                    <td>${movimiento.Cantidad}</td>
                                    <td>${movimiento.StockAnterior}</td>
                                    <td>${movimiento.StockNuevo}</td>
                                    <td>${movimiento.Observaciones || '-'}</td>
                                </tr>
                            `);
                        });
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('modal-movimientos'));
                    modal.show();
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar movimientos:', error);
                alert('Error al cargar los movimientos del repuesto');
            }
        });
    }

    exportarInventario() {
        window.location.href = this.baseUrl + '?accion=exportar_inventario&categoria=' + 
                              encodeURIComponent($('#filtro-categoria').val()) + 
                              '&estado=' + encodeURIComponent($('#filtro-estado').val()) +
                              '&busqueda=' + encodeURIComponent($('#filtro-busqueda').val());
    }
}

// Inicializar cuando el DOM esté listo
let inventarioCoordinador;
document.addEventListener('DOMContentLoaded', () => {
    inventarioCoordinador = new InventarioCoordinador();
});
