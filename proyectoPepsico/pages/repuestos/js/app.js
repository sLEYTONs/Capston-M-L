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
        this.inicializarDataTable();
        this.cargarRepuestos();
        this.cargarStockBajo();
    }

    inicializarEventos() {
        $('#btn-refresh').on('click', () => {
            this.cargarRepuestos();
            this.cargarStockBajo();
        });

        $('#btn-alertar-stock').on('click', () => {
            this.enviarAlertaStockBajo();
        });

        // Event listener para ver movimientos del stock (usando delegación de eventos)
        $(document).on('click', '.btn-ver-movimientos', (e) => {
            const repuestoId = $(e.currentTarget).data('id');
            const codigo = $(e.currentTarget).data('codigo');
            const nombre = $(e.currentTarget).data('nombre');
            this.verMovimientosStock(repuestoId, codigo, nombre);
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#repuestos-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
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
                            icon = 'exclamation-triangle';
                        }
                        
                        return `<span class="badge bg-${badgeClass}">
                                    <i class="fas fa-${icon} me-1"></i>${stock}
                                </span>`;
                    }
                },
                { 
                    data: 'Precio',
                    render: (data) => {
                        return `$${this.formatearPrecioChileno(data)}`;
                    }
                },
                { 
                    data: 'Estado',
                    render: (data) => {
                        const estadoClass = data === 'Activo' ? 'success' : 'secondary';
                        return `<span class="badge bg-${estadoClass}">${data}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: (data) => {
                        return `
                            <button class="btn btn-sm btn-info btn-ver-movimientos" data-id="${data.ID}" data-codigo="${data.Codigo}" data-nombre="${data.Nombre}" title="Ver Movimientos de Stock">
                                <i class="fas fa-history me-1"></i> Ver Movimientos
                            </button>
                        `;
                    }
                }
            ],
            order: [[1, 'asc']],
            responsive: true,
            drawCallback: () => {
                // Recalcular espacio después de cada redibujado
                setTimeout(() => {
                    this.ajustarEspacioFooter();
                }, 100);
            }
        });

        // Event listeners para cambios en el DataTable
        $(this.dataTable.table().container()).on('length.dt', () => {
            setTimeout(() => {
                this.ajustarEspacioFooter();
            }, 100);
        });

        $(this.dataTable.table().container()).on('page.dt', () => {
            setTimeout(() => {
                this.ajustarEspacioFooter();
            }, 100);
        });

        // Ajustar espacio inicial
        setTimeout(() => {
            this.ajustarEspacioFooter();
        }, 500);
    }

    ajustarEspacioFooter() {
        const pcContent = document.querySelector('.pc-content');
        const dataTableWrapper = document.querySelector('#repuestos-table_wrapper');
        const cardBody = document.querySelector('.repuestos-container .card-body');
        const footer = document.querySelector('.pc-footer');
        
        if (!pcContent || !dataTableWrapper || !footer) {
            return;
        }

        // Ajustar el padding-bottom del card-body según la altura del DataTable
        if (cardBody) {
            const dataTableHeight = dataTableWrapper.offsetHeight;
            const espacioMinimoCardBody = 50; // Espacio mínimo en el card-body para la paginación
            
            // Calcular padding-bottom dinámico para el card-body
            // Si el DataTable es muy alto, necesitamos más espacio
            let paddingBottomCardBody = 2; // Base de 2rem
            
            if (dataTableHeight > 400) {
                paddingBottomCardBody = 3; // 3rem para tablas medianas
            }
            if (dataTableHeight > 600) {
                paddingBottomCardBody = 4; // 4rem para tablas grandes
            }
            if (dataTableHeight > 800) {
                paddingBottomCardBody = 5; // 5rem para tablas muy grandes
            }
            
            cardBody.style.paddingBottom = `${paddingBottomCardBody}rem`;
        }

        // Obtener posiciones después de ajustar el card-body
        setTimeout(() => {
            const dataTableBottom = dataTableWrapper.getBoundingClientRect().bottom;
            const footerTop = footer.getBoundingClientRect().top;
            
            // Calcular espacio necesario
            // Espacio mínimo deseado entre DataTable y footer (en píxeles)
            const espacioMinimo = 100;
            
            // Calcular cuánto espacio necesitamos
            const espacioActual = footerTop - dataTableBottom;
            const espacioNecesario = espacioMinimo - espacioActual;
            
            // Obtener el padding-bottom actual (convertir rem a px)
            const paddingBottomActual = parseFloat(getComputedStyle(pcContent).paddingBottom) || 0;
            
            // Calcular nuevo padding-bottom
            // Si el espacio actual es menor al mínimo, aumentar el padding
            let nuevoPaddingBottom = paddingBottomActual;
            
            if (espacioActual < espacioMinimo) {
                nuevoPaddingBottom = paddingBottomActual + espacioNecesario;
            } else {
                // Mantener un mínimo de padding para evitar cambios bruscos
                nuevoPaddingBottom = Math.max(paddingBottomActual, 192); // 12rem = 192px mínimo
            }
            
            // Convertir a rem y aplicar
            const nuevoPaddingRem = nuevoPaddingBottom / 16; // 1rem = 16px
            pcContent.style.paddingBottom = `${nuevoPaddingRem}rem`;
        }, 50);
    }

    cargarRepuestos() {
        fetch(this.baseUrl + '?action=obtenerTodosRepuestos', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                if (this.dataTable) {
                    this.dataTable.clear();
                    this.dataTable.rows.add(data.data);
                    this.dataTable.draw();
                }
                this.actualizarResumen(data.data);
                // Ajustar espacio después de cargar datos
                setTimeout(() => {
                    this.ajustarEspacioFooter();
                }, 200);
            } else {
                this.mostrarError('Error al cargar repuestos: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error al cargar repuestos:', error);
            this.mostrarError('Error de conexión al cargar repuestos');
        });
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
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    No hay repuestos con stock bajo
                </div>
            `);
            return;
        }

        let html = '<ul class="list-unstyled mb-0">';
        repuestos.forEach(repuesto => {
            const stock = parseInt(repuesto.Stock);
            const minimo = parseInt(repuesto.StockMinimo);
            const porcentaje = minimo > 0 ? (stock / minimo) * 100 : 0;
            
            html += `
                <li class="mb-2 p-2 border rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${repuesto.Nombre}</strong>
                            <small class="text-muted d-block">${repuesto.Codigo}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-${stock === 0 ? 'danger' : 'warning'}">
                                Stock: ${stock} / ${minimo}
                            </span>
                        </div>
                    </div>
                </li>
            `;
        });
        html += '</ul>';
        
        container.html(html);
    }

    actualizarResumen(repuestos) {
        const total = repuestos.length;
        const activos = repuestos.filter(r => r.Estado === 'Activo').length;
        const stockBajo = repuestos.filter(r => r.Estado === 'Activo' && parseInt(r.Stock) <= parseInt(r.StockMinimo)).length;
        const sinStock = repuestos.filter(r => r.Estado === 'Activo' && parseInt(r.Stock) === 0).length;
        
        const html = `
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Repuestos:</span>
                    <strong>${total}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Activos:</span>
                    <strong class="text-success">${activos}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Stock Bajo:</span>
                    <strong class="text-warning">${stockBajo}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Sin Stock:</span>
                    <strong class="text-danger">${sinStock}</strong>
                </div>
            </div>
        `;
        
        $('#resumen-inventario').html(html);
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
        // Cargar movimientos del stock desde el servidor
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
        // Eliminar modal anterior si existe y destruir DataTable si está inicializado
        const modalAnterior = $('#modalMovimientosStock');
        if (modalAnterior.length > 0) {
            const tablaAnterior = $('#tabla-movimientos');
            if (tablaAnterior.length > 0 && $.fn.DataTable.isDataTable('#tabla-movimientos')) {
                try {
                    tablaAnterior.DataTable().destroy();
                } catch (e) {
                    // Ignorar errores al destruir
                }
            }
            modalAnterior.remove();
        }

        // Crear el contenido del tbody
        let tbodyContent = '';
        if (movimientos && movimientos.length > 0) {
            movimientos.forEach(movimiento => {
                const tipoClass = movimiento.Tipo === 'Entrada' ? 'success' : movimiento.Tipo === 'Salida' ? 'danger' : 'info';
                const fecha = movimiento.Fecha || 'N/A';
                const tipo = movimiento.Tipo || 'N/A';
                const cantidad = movimiento.Cantidad || '0';
                const stockAnterior = movimiento.StockAnterior || '0';
                const stockNuevo = movimiento.StockNuevo || '0';
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

        // Crear el HTML del modal
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
        
        // Agregar el nuevo modal al body
        $('body').append(modalHtml);
        
        // Obtener el elemento del modal
        const modalElement = document.getElementById('modalMovimientosStock');
        if (!modalElement) {
            console.error('No se pudo crear el modal');
            return;
        }

        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true
        });
        
        // Inicializar DataTable cuando el modal esté completamente visible
        $(modalElement).one('shown.bs.modal', function () {
            const tabla = $('#tabla-movimientos');
            
            // Verificar que la tabla existe
            if (tabla.length === 0) {
                console.error('La tabla no existe en el DOM');
                return;
            }

            // Solo inicializar DataTable si hay datos (más de una fila y no es la fila de "sin datos")
            const filas = tabla.find('tbody tr');
            if (filas.length === 0 || (filas.length === 1 && filas.find('td[colspan]').length > 0)) {
                // No hay datos, no inicializar DataTable
                return;
            }

            // Verificar que no hay una instancia previa de DataTable
            if ($.fn.DataTable.isDataTable('#tabla-movimientos')) {
                try {
                    tabla.DataTable().destroy();
                } catch (e) {
                    // Ignorar errores
                }
            }

            // Inicializar DataTable
            if (typeof $.fn.DataTable !== 'undefined') {
                try {
                    tabla.DataTable({
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                        },
                        pageLength: 10,
                        lengthMenu: [10, 25, 50, 100],
                        order: [], // Sin ordenamiento inicial
                        responsive: true,
                        destroy: true,
                        autoWidth: false,
                        columnDefs: [
                            { targets: '_all', orderable: true }
                        ]
                    });
                } catch (error) {
                    console.error('Error al inicializar DataTable:', error);
                }
            }
        });

        // Limpiar el modal cuando se cierre
        $(modalElement).on('hidden.bs.modal', function () {
            const tabla = $('#tabla-movimientos');
            if (tabla.length > 0 && $.fn.DataTable.isDataTable('#tabla-movimientos')) {
                try {
                    tabla.DataTable().destroy();
                } catch (e) {
                    // Ignorar errores
                }
            }
            $(this).remove();
        });

        // Mostrar el modal
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
    
    // Ajustar espacio cuando se redimensiona la ventana
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (inventarioRepuestos && inventarioRepuestos.ajustarEspacioFooter) {
                inventarioRepuestos.ajustarEspacioFooter();
            }
        }, 250);
    });
});
