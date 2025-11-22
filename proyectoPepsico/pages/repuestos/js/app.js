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
                            <div class="btn-group">
                                <button class="btn btn-sm btn-primary btn-editar" data-id="${data.ID}" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-eliminar" data-id="${data.ID}" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            order: [[1, 'asc']],
            responsive: true
        });
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
}

// Inicializar cuando el DOM esté listo
let inventarioRepuestos;
document.addEventListener('DOMContentLoaded', () => {
    inventarioRepuestos = new InventarioRepuestos();
});
