class InventarioRepuestos {
    constructor() {
        this.dataTable = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    /**
     * Formatea un número al formato chileno (punto para miles, sin decimales)
     */
    formatearPrecioChileno(valor) {
        const numero = parseFloat(valor) || 0;
        return Math.round(numero).toLocaleString('es-CL', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/gestion_pausas_repuestos/scripts/s_gestion_pausas_repuestos.php';
        }
        return '../../app/model/gestion_pausas_repuestos/scripts/s_gestion_pausas_repuestos.php';
    }

    inicializar() {
        this.inicializarEventos();
        // Esperar un poco para asegurar que el DOM esté listo
        setTimeout(() => {
            this.inicializarDataTable();
            // Cargar datos después de inicializar DataTable
            setTimeout(() => {
                this.cargarRepuestos();
                this.cargarResumen();
                this.cargarCategorias();
                if (document.getElementById('alertas-stock')) {
                    this.cargarStockBajo();
                }
            }, 100);
        }, 100);
    }

    inicializarEventos() {
        $('#btn-refresh').on('click', () => {
            this.cargarRepuestos();
            this.cargarResumen();
            if (document.getElementById('alertas-stock')) {
                this.cargarStockBajo();
            }
        });

        $('#btn-alertar-stock').on('click', () => {
            this.enviarAlertaStockBajo();
        });

        // Filtros (si existen en el DOM)
        if ($('#filtro-busqueda').length) {
            $('#filtro-busqueda').on('keyup', () => {
                if (this.dataTable) {
                    this.dataTable.column(0).search($('#filtro-busqueda').val()).draw();
                }
            });
        }

        if ($('#filtro-categoria').length) {
            $('#filtro-categoria').on('change', () => {
                if (this.dataTable) {
                    const categoria = $('#filtro-categoria').val();
                    this.dataTable.column(1).search(categoria).draw();
                }
            });
        }

        if ($('#filtro-estado-stock').length) {
            $('#filtro-estado-stock').on('change', () => {
                this.aplicarFiltroEstadoStock();
            });
        }

        if ($('#btn-limpiar-filtros').length) {
            $('#btn-limpiar-filtros').on('click', () => {
                $('#filtro-busqueda').val('');
                $('#filtro-categoria').val('');
                $('#filtro-estado-stock').val('');
                if (this.dataTable) {
                    this.dataTable.search('').columns().search('').draw();
                }
            });
        }

        // Event listener para ver movimientos del stock (usando delegación de eventos)
        $(document).on('click', '.btn-ver-movimientos', (e) => {
            const repuestoId = $(e.currentTarget).data('id');
            const codigo = $(e.currentTarget).data('codigo');
            const nombre = $(e.currentTarget).data('nombre');
            this.verMovimientosStock(repuestoId, codigo, nombre);
        });
    }

    aplicarFiltroEstadoStock() {
        if (!this.dataTable) return;
        
        const estado = $('#filtro-estado-stock').val();
        $.fn.dataTable.ext.search.push(
            (settings, data, dataIndex) => {
                if (!estado) return true;
                
                // Las columnas ahora son: Nombre(0), Categoria(1), Stock(2), StockMinimo(3), Precio(4), ValorTotal(5), Estado(6), Acciones(7)
                const stock = parseInt(data[2]) || 0;
                const stockMinimo = parseInt(data[3]) || 0;
                
                if (estado === 'sin') {
                    return stock === 0;
                } else if (estado === 'bajo') {
                    return stock > 0 && stock <= stockMinimo;
                } else if (estado === 'normal') {
                    return stock > stockMinimo;
                }
                return true;
            }
        );
        this.dataTable.draw();
        $.fn.dataTable.ext.search.pop();
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            alert('Error: DataTables no está cargado. Verifica que las librerías estén incluidas.');
            return;
        }

        // Verificar que la tabla existe
        const $table = $('#repuestos-table');
        if ($table.length === 0) {
            console.error('La tabla #repuestos-table no existe en el DOM');
            alert('Error: La tabla no existe en el DOM');
            return;
        }

        // Limpiar el tbody antes de inicializar
        $table.find('tbody').empty();

        this.dataTable = $table.DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles en la tabla",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "infoPostFix": "",
                "thousands": ".",
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
            columns: [
                { 
                    data: 'Nombre',
                    className: 'text-left',
                    defaultContent: '-'
                },
                { 
                    data: 'Categoria',
                    className: 'text-left',
                    defaultContent: '-'
                },
                { 
                    data: 'Stock',
                    className: 'text-right',
                    defaultContent: '0',
                    render: (data, type) => {
                        if (type === 'display' || type === 'type') {
                            const stock = parseInt(data) || 0;
                            return stock.toLocaleString('es-CL');
                        }
                        return parseInt(data) || 0;
                    }
                },
                { 
                    data: 'StockMinimo',
                    className: 'text-right',
                    defaultContent: '0',
                    render: (data, type) => {
                        if (type === 'display' || type === 'type') {
                            const minimo = parseInt(data) || 0;
                            return minimo.toLocaleString('es-CL');
                        }
                        return parseInt(data) || 0;
                    }
                },
                { 
                    data: 'Precio',
                    className: 'text-right',
                    defaultContent: '0',
                    render: (data, type) => {
                        if (type === 'display' || type === 'type') {
                            return `$${this.formatearPrecioChileno(data || 0)}`;
                        }
                        return parseFloat(data) || 0;
                    }
                },
                { 
                    data: null,
                    className: 'text-right',
                    orderable: false,
                    defaultContent: '$0',
                    render: (data, type, row) => {
                        if (type === 'display' || type === 'type') {
                            const stock = parseInt(row.Stock) || 0;
                            const precio = parseFloat(row.Precio) || 0;
                            const valorTotal = stock * precio;
                            return `$${this.formatearPrecioChileno(valorTotal)}`;
                        }
                        const stock = parseInt(row.Stock) || 0;
                        const precio = parseFloat(row.Precio) || 0;
                        return stock * precio;
                    }
                },
                { 
                    data: null,
                    className: 'text-left',
                    orderable: false,
                    defaultContent: '',
                    render: (data, type, row) => {
                        const stock = parseInt(row.Stock) || 0;
                        const minimo = parseInt(row.StockMinimo) || 0;
                        
                        if (type === 'display' || type === 'type') {
                            let badgeClass = 'success';
                            let estadoTexto = 'Stock Normal';
                            
                            if (stock === 0) {
                                badgeClass = 'danger';
                                estadoTexto = 'Sin Stock';
                            } else if (stock <= minimo) {
                                badgeClass = 'warning';
                                estadoTexto = 'Stock Bajo';
                            }
                            
                            return `<span class="badge bg-${badgeClass}">${estadoTexto}</span>`;
                        }
                        return stock;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    defaultContent: '',
                    render: (data, type, row) => {
                        if (type === 'display' || type === 'type') {
                            return `
                                <button class="btn btn-sm btn-info btn-ver-movimientos" 
                                        data-id="${row.ID || ''}" 
                                        data-codigo="${row.Codigo || ''}" 
                                        data-nombre="${row.Nombre || ''}" 
                                        title="Ver Movimientos de Stock">
                                    <i class="fas fa-history me-1"></i> Movimientos
                                </button>
                            `;
                        }
                        return '';
                    }
                }
            ],
            order: [[0, 'asc']],
            responsive: true,
            scrollX: false,
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            processing: true,
            serverSide: false,
            paging: true,
            pagingType: 'full_numbers',
            autoWidth: false
        });
    }

    cargarRepuestos() {
        fetch(this.baseUrl + '?action=obtenerTodosRepuestos', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error HTTP: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta completa recibida:', data);
            console.log('Status:', data?.status);
            console.log('Data:', data?.data);
            console.log('Es array?', Array.isArray(data?.data));
            console.log('Cantidad de items:', data?.data?.length);
            
            if (data && data.status === 'success') {
                if (this.dataTable) {
                    this.dataTable.clear();
                    if (data.data && Array.isArray(data.data)) {
                        console.log('Agregando', data.data.length, 'filas a la tabla');
                        if (data.data.length > 0) {
                            this.dataTable.rows.add(data.data);
                        }
                    } else {
                        console.warn('data.data no es un array válido:', data.data);
                    }
                    this.dataTable.draw();
                } else {
                    console.error('DataTable no está inicializado');
                    this.mostrarError('Error: La tabla no está inicializada');
                }
            } else {
                console.error('Error en respuesta:', data);
                const mensaje = data && data.message ? data.message : 'Error desconocido';
                this.mostrarError('Error al cargar repuestos: ' + mensaje);
                // Limpiar tabla en caso de error
                if (this.dataTable) {
                    this.dataTable.clear().draw();
                }
            }
        })
        .catch(error => {
            console.error('Error al cargar repuestos:', error);
            this.mostrarError('Error de conexión al cargar repuestos. Verifique la conexión a la base de datos.');
            // Limpiar tabla en caso de error
            if (this.dataTable) {
                this.dataTable.clear().draw();
            }
        });
    }

    cargarCategorias() {
        fetch(this.baseUrl + '?action=obtenerTodosRepuestos', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                const categorias = [...new Set(data.data.map(r => r.Categoria).filter(c => c))].sort();
                const select = $('#filtro-categoria');
                select.empty().append('<option value="">Todas las categorías</option>');
                categorias.forEach(cat => {
                    select.append(`<option value="${this.escapeHtml(cat)}">${this.escapeHtml(cat)}</option>`);
                });
            }
        })
        .catch(error => {
            console.error('Error al cargar categorías:', error);
        });
    }

    cargarResumen() {
        fetch(this.baseUrl + '?action=obtenerTodosRepuestos', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                this.actualizarResumen(data.data);
            }
        })
        .catch(error => {
            console.error('Error al cargar resumen:', error);
            $('#resumen-inventario').html('<div class="alert alert-danger">Error al cargar resumen</div>');
        });
    }

    actualizarResumen(repuestos) {
        console.log('Repuestos recibidos:', repuestos);
        console.log('Primer repuesto:', repuestos[0]);
        
        const total = repuestos.length;
        const activos = repuestos.filter(r => (r.Estado || 'Activo') === 'Activo').length;
        const stockBajo = repuestos.filter(r => {
            const stock = parseInt(r.Stock) || 0;
            const minimo = parseInt(r.StockMinimo) || 0;
            return (r.Estado || 'Activo') === 'Activo' && stock > 0 && stock <= minimo;
        }).length;
        const sinStock = repuestos.filter(r => {
            const stock = parseInt(r.Stock) || 0;
            return (r.Estado || 'Activo') === 'Activo' && stock === 0;
        }).length;
        
        // Calcular valor total del inventario
        const valorTotal = repuestos.reduce((sum, r) => {
            const stock = parseInt(r.Stock) || 0;
            const precio = parseFloat(r.Precio) || 0;
            return sum + (stock * precio);
        }, 0);

        // Calcular por categoría
        const porCategoria = {};
        repuestos.forEach(r => {
            // Intentar diferentes nombres de campo para categoría
            const cat = r.Categoria || r.categoria || r.RepuestoCategoria || 'Sin categoría';
            if (!porCategoria[cat]) {
                porCategoria[cat] = { total: 0, activos: 0 };
            }
            porCategoria[cat].total++;
            if ((r.Estado || r.estado || 'Activo') === 'Activo') {
                porCategoria[cat].activos++;
            }
        });

        console.log('Categorías calculadas:', porCategoria);
        console.log('Claves de categorías:', Object.keys(porCategoria));

        let categoriasHtml = '';
        const categoriasOrdenadas = Object.keys(porCategoria).sort();
        console.log('Categorías ordenadas:', categoriasOrdenadas);
        
        if (categoriasOrdenadas.length > 0) {
            categoriasOrdenadas.forEach(cat => {
                categoriasHtml += `
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom" style="display: flex !important; visibility: visible !important; opacity: 1 !important;">
                        <div>
                            <strong class="d-block" style="font-size: 0.85rem; color: #495057;">${this.escapeHtml(cat)}</strong>
                            <small class="text-muted" style="font-size: 0.8rem;">${porCategoria[cat].activos} activos</small>
                        </div>
                        <span class="badge bg-primary" style="font-size: 0.75rem;">${porCategoria[cat].total}</span>
                    </div>
                `;
            });
        }
        
        console.log('HTML de categorías generado:', categoriasHtml);

        const html = `
            <div style="display: flex; flex-direction: column; height: 100%; min-height: 0;">
                <div class="mb-3" style="flex-shrink: 0;">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <strong class="d-block">Total Repuestos</strong>
                            <small class="text-muted">En inventario</small>
                        </div>
                        <h4 class="mb-0 text-primary">${total}</h4>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <strong class="d-block text-success">Activos</strong>
                            <small class="text-muted">Disponibles</small>
                        </div>
                        <h5 class="mb-0 text-success">${activos}</h5>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <strong class="d-block text-warning">Stock Bajo</strong>
                            <small class="text-muted">Requieren atención</small>
                        </div>
                        <h5 class="mb-0 text-warning">${stockBajo}</h5>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <strong class="d-block text-danger">Sin Stock</strong>
                            <small class="text-muted">Agotados</small>
                        </div>
                        <h5 class="mb-0 text-danger">${sinStock}</h5>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <strong class="d-block">Valor Total</strong>
                            <small class="text-muted">Del inventario</small>
                        </div>
                        <h5 class="mb-0 text-primary">$${this.formatearPrecioChileno(valorTotal)}</h5>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top" style="display: flex; flex-direction: column; min-height: 0;">
                    <h6 class="mb-2" style="flex-shrink: 0; font-weight: 600; color: #495057;">
                        <i class="fas fa-tags me-1"></i>
                        Por Categoría
                    </h6>
                    <div style="overflow: visible; min-height: 0; max-height: none; padding: 0.5rem 0; height: auto;">
                        ${categoriasHtml || '<p class="text-muted small mb-0">No hay categorías disponibles</p>'}
                    </div>
                </div>
            </div>
        `;
        
        $('#resumen-inventario').html(html);
    }

    cargarStockBajo() {
        fetch(this.baseUrl + '?action=obtenerRepuestosStockBajo', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                this.mostrarStockBajo(data.data);
            }
        })
        .catch(error => {
            console.error('Error al cargar stock bajo:', error);
        });
    }

    mostrarStockBajo(repuestos) {
        const container = $('#alertas-stock');
        
        if (repuestos.length === 0) {
            container.html(`
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    <small>No hay repuestos con stock bajo</small>
                </div>
            `);
            return;
        }

        let html = '<ul class="list-unstyled mb-0">';
        repuestos.slice(0, 5).forEach(repuesto => {
            const stock = parseInt(repuesto.Stock) || 0;
            const minimo = parseInt(repuesto.StockMinimo) || 0;
            
            html += `
                <li class="mb-2 p-2 border rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <strong class="d-block" style="font-size: 0.85rem;">${this.escapeHtml(repuesto.Nombre || '')}</strong>
                            <small class="text-muted d-block">${this.escapeHtml(repuesto.Codigo || '')}</small>
                        </div>
                        <span class="badge bg-${stock === 0 ? 'danger' : 'warning'} ms-2">
                            ${stock}/${minimo}
                        </span>
                    </div>
                </li>
            `;
        });
        if (repuestos.length > 5) {
            html += `<li class="text-center mt-2"><small class="text-muted">Y ${repuestos.length - 5} más...</small></li>`;
        }
        html += '</ul>';
        
        container.html(html);
    }

    enviarAlertaStockBajo() {
        if (typeof Swal === 'undefined') {
            if (!confirm('¿Desea enviar una alerta de stock bajo al jefe de taller?')) {
                return;
            }
        } else {
            Swal.fire({
                title: '¿Enviar Alerta?',
                text: 'Se enviará una notificación al jefe de taller sobre los repuestos con stock bajo',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.procesarAlerta();
                }
            });
            return;
        }
        
        this.procesarAlerta();
    }

    procesarAlerta() {
        $('#btn-alertar-stock').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Enviando...');

        fetch(this.baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=enviarAlertaStockBajo',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarExito(data.message);
            } else {
                this.mostrarError(data.message || 'Error al enviar alerta');
            }
        })
        .catch(error => {
            console.error('Error al enviar alerta:', error);
            this.mostrarError('Error de conexión al enviar alerta');
        })
        .finally(() => {
            $('#btn-alertar-stock').prop('disabled', false).html('<i class="fas fa-bell me-1"></i>Alertar Jefe');
        });
    }

    mostrarExito(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: mensaje,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert(mensaje);
        }
    }

    mostrarError(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje
            });
        } else {
            alert(mensaje);
        }
    }

    verMovimientosStock(repuestoId, codigo, nombre) {
        fetch(this.baseUrl + '?action=obtenerMovimientosStock&repuesto_id=' + repuestoId, {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                this.mostrarModalMovimientos(repuestoId, codigo, nombre, data.data);
            } else {
                this.mostrarError('Error al cargar movimientos: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error al cargar movimientos:', error);
            this.mostrarError('Error de conexión al cargar movimientos');
        });
    }

    mostrarModalMovimientos(repuestoId, codigo, nombre, movimientos) {
        const modalAnterior = $('#modalMovimientosStock');
        if (modalAnterior.length > 0) {
            const tablaAnterior = $('#tabla-movimientos');
            if (tablaAnterior.length > 0 && $.fn.DataTable.isDataTable('#tabla-movimientos')) {
                try {
                    tablaAnterior.DataTable().destroy();
                } catch (e) {}
            }
            modalAnterior.remove();
        }

        let tbodyContent = '';
        if (movimientos && movimientos.length > 0) {
            movimientos.forEach(movimiento => {
                const tipoClass = movimiento.Tipo === 'Entrada' ? 'success' : movimiento.Tipo === 'Salida' ? 'danger' : 'info';
                const fecha = movimiento.Fecha || 'N/A';
                const tipo = movimiento.Tipo || 'N/A';
                const cantidad = movimiento.Cantidad || '0';
                const stockAnterior = movimiento.StockAnterior || '-';
                const stockNuevo = movimiento.StockNuevo || '-';
                const usuario = movimiento.Usuario || 'N/A';
                const observaciones = movimiento.Observaciones || '-';
                
                tbodyContent += `
                    <tr>
                        <td>${this.escapeHtml(fecha)}</td>
                        <td><span class="badge bg-${tipoClass}">${this.escapeHtml(tipo)}</span></td>
                        <td>${this.escapeHtml(cantidad)}</td>
                        <td>${this.escapeHtml(stockAnterior)}</td>
                        <td>${this.escapeHtml(stockNuevo)}</td>
                        <td>${this.escapeHtml(usuario)}</td>
                        <td>${this.escapeHtml(observaciones)}</td>
                    </tr>
                `;
            });
        } else {
            tbodyContent = `
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        No hay movimientos registrados para este repuesto
                    </td>
                </tr>
            `;
        }

        const modalHtml = `
            <div class="modal fade" id="modalMovimientosStock" tabindex="-1" aria-labelledby="modalMovimientosStockLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalMovimientosStockLabel">
                                <i class="fas fa-history me-2"></i>
                                Movimientos de Stock - ${this.escapeHtml(nombre)} (${this.escapeHtml(codigo)})
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table id="tabla-movimientos" class="table table-striped table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo de Movimiento</th>
                                            <th>Cantidad</th>
                                            <th>Stock Anterior</th>
                                            <th>Stock Nuevo</th>
                                            <th>Usuario</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${tbodyContent}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        const modalElement = document.getElementById('modalMovimientosStock');
        if (!modalElement) {
            console.error('No se pudo crear el modal');
            return;
        }

        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true
        });
        
        $(modalElement).one('shown.bs.modal', function () {
            const tabla = $('#tabla-movimientos');
            
            if (tabla.length === 0) {
                console.error('La tabla no existe en el DOM');
                return;
            }

            const filas = tabla.find('tbody tr');
            if (filas.length === 0 || (filas.length === 1 && filas.find('td[colspan]').length > 0)) {
                return;
            }

            if ($.fn.DataTable.isDataTable('#tabla-movimientos')) {
                try {
                    tabla.DataTable().destroy();
                } catch (e) {}
            }

            if (typeof $.fn.DataTable !== 'undefined') {
                try {
                    tabla.DataTable({
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                        },
                        pageLength: 10,
                        lengthMenu: [10, 25, 50, 100],
                        order: [[0, 'desc']],
                        responsive: true,
                        destroy: true,
                        autoWidth: false
                    });
                } catch (error) {
                    console.error('Error al inicializar DataTable:', error);
                }
            }
        });

        $(modalElement).on('hidden.bs.modal', function () {
            const tabla = $('#tabla-movimientos');
            if (tabla.length > 0 && $.fn.DataTable.isDataTable('#tabla-movimientos')) {
                try {
                    tabla.DataTable().destroy();
                } catch (e) {}
            }
            $(this).remove();
        });

        modal.show();
    }

    escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
}

// Inicializar cuando el DOM esté listo
let inventarioRepuestos;
document.addEventListener('DOMContentLoaded', () => {
    inventarioRepuestos = new InventarioRepuestos();
});
