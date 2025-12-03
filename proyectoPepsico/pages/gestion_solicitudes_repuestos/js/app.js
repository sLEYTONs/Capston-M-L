class GestionSolicitudesRepuestos {
    constructor() {
        this.dataTable = null;
        this.baseUrl = '../app/model/gestion_pausas_repuestos/scripts/s_gestion_pausas_repuestos.php';
        this.init();
    }

    init() {
        this.inicializarEventos();
        this.inicializarDataTable();
    }

    inicializarEventos() {
        $('#btn-refresh').on('click', () => {
            this.cargarSolicitudes();
        });

        $('#filtro-estado').on('change', () => {
            this.cargarSolicitudes();
        });
    }

    inicializarDataTable() {
        this.dataTable = $('#tabla-solicitudes').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay solicitudes disponibles",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ solicitudes",
                "infoEmpty": "Mostrando 0 a 0 de 0 solicitudes",
                "infoFiltered": "(filtrado de _MAX_ solicitudes totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ solicitudes",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron solicitudes",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            responsive: true,
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });

        this.cargarSolicitudes();
    }

    cargarSolicitudes() {
        const estado = $('#filtro-estado').val();
        const url = this.baseUrl + '?action=obtenerTodasSolicitudesRepuestos' + (estado ? '&estado=' + encodeURIComponent(estado) : '');

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarSolicitudes(data.data);
            } else {
                Swal.fire('Error', data.message || 'Error al cargar solicitudes', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Error de conexión al cargar solicitudes', 'error');
        });
    }

    mostrarSolicitudes(solicitudes) {
        if (!this.dataTable) {
            return;
        }

        this.dataTable.clear();

        solicitudes.forEach(solicitud => {
            const estadoBadge = this.getEstadoBadge(solicitud.Estado);
            const urgenciaBadge = this.getUrgenciaBadge(solicitud.Urgencia);
            // Usar StockRealDisponible si está disponible, sino usar StockDisponible
            const stockParaMostrar = solicitud.StockRealDisponible !== undefined ? solicitud.StockRealDisponible : solicitud.StockDisponible;
            const stockInfo = this.getStockInfo(stockParaMostrar, solicitud.StockMinimo, solicitud.Cantidad);
            const acciones = this.getAcciones(solicitud);

            const row = [
                solicitud.FechaSolicitud || '-',
                solicitud.MecanicoNombre || 'N/A',
                `${solicitud.RepuestoCodigo || ''} - ${solicitud.RepuestoNombre || 'N/A'}`,
                solicitud.Cantidad,
                stockInfo,
                solicitud.Placa || 'N/A',
                urgenciaBadge,
                estadoBadge,
                acciones
            ];

            this.dataTable.row.add(row);
        });

        this.dataTable.draw();
    }

    getEstadoBadge(estado) {
        const badges = {
            'Pendiente': '<span class="badge badge-estado-pendiente">Pendiente</span>',
            'Aprobada': '<span class="badge badge-estado-aprobada">Aprobada</span>',
            'Entregada': '<span class="badge badge-estado-entregada">Entregada</span>',
            'Rechazada': '<span class="badge badge-estado-rechazada">Rechazada</span>'
        };
        return badges[estado] || '<span class="badge bg-secondary">' + estado + '</span>';
    }

    getUrgenciaBadge(urgencia) {
        const badges = {
            'Alta': '<span class="badge badge-urgencia-alta">Alta</span>',
            'Media': '<span class="badge badge-urgencia-media">Media</span>',
            'Baja': '<span class="badge badge-urgencia-baja">Baja</span>'
        };
        return badges[urgencia] || '<span class="badge bg-secondary">' + urgencia + '</span>';
    }

    getStockInfo(stock, stockMinimo, cantidadSolicitada) {
        const stockNum = parseInt(stock) || 0;
        const cantidadNum = parseInt(cantidadSolicitada) || 0;
        let clase = 'stock-suficiente';

        if (stockNum < cantidadNum) {
            clase = 'stock-insuficiente';
        } else if (stockNum <= stockMinimo) {
            clase = 'stock-bajo';
        }

        return `<span class="stock-disponible ${clase}">${stockNum}</span>`;
    }

    getAcciones(solicitud) {
        let acciones = '';

        if (solicitud.Estado === 'Pendiente') {
            // Usar StockRealDisponible si está disponible, sino usar StockDisponible
            const stockDisponible = parseInt(solicitud.StockRealDisponible !== undefined ? solicitud.StockRealDisponible : solicitud.StockDisponible) || 0;
            const cantidadSolicitada = parseInt(solicitud.Cantidad) || 0;
            const cantidadAprobadaPendiente = parseInt(solicitud.CantidadAprobadaPendiente) || 0;
            
            // Si el stock real disponible es 0 o insuficiente, mostrar botón amarillo para notificar
            if (stockDisponible === 0 || stockDisponible < cantidadSolicitada) {
                let titleText = `Stock insuficiente. Disponible: ${stockDisponible} unidad(es)`;
                if (cantidadAprobadaPendiente > 0) {
                    titleText += ` (${cantidadAprobadaPendiente} ya aprobado pendiente de entrega)`;
                }
                acciones += `<button class="btn btn-sm btn-warning btn-action btn-notificar-jefe btn-alerta-stock" data-id="${solicitud.ID}" data-stock="${stockDisponible}" data-cantidad="${cantidadSolicitada}" title="${titleText}">
                    <i class="fas fa-exclamation-triangle me-1"></i> Notificar
                </button>`;
            } else {
                acciones += `<button class="btn btn-sm btn-success btn-action btn-aprobar" data-id="${solicitud.ID}" title="Aprobar solicitud">
                    <i class="fas fa-check"></i> Aprobar
                </button>`;
            }
        }

        if (solicitud.Estado === 'Aprobada') {
            acciones += `<button class="btn btn-sm btn-primary btn-action btn-entregar" data-id="${solicitud.ID}" title="Entregar repuestos">
                <i class="fas fa-hand-holding"></i> Entregar
            </button>`;
        }

        return acciones;
    }

    aprobarSolicitud(solicitudId) {
        Swal.fire({
            title: '¿Aprobar solicitud?',
            text: 'Esta acción aprobará la solicitud. El mecánico será notificado.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'aprobarSolicitudRepuestos');
                formData.append('solicitud_id', solicitudId);

                fetch(this.baseUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Éxito', data.message || 'Solicitud aprobada correctamente', 'success');
                        this.cargarSolicitudes();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            html: (data.message || 'Error al aprobar la solicitud').replace(/\n/g, '<br>'),
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error de conexión al aprobar solicitud', 'error');
                });
            }
        });
    }

    notificarJefeTaller(solicitudId, stockDisponible, cantidadSolicitada) {
        Swal.fire({
            title: '⚠️ Notificar al Jefe de Taller',
            html: `<div class="text-start">
                <p><strong>Se enviará una notificación al Jefe de Taller sobre la falta de stock.</strong></p>
                <ul class="text-start mt-3 mb-3">
                    <li>Stock disponible: <strong>${stockDisponible}</strong> unidad(es)</li>
                    <li>Cantidad solicitada: <strong>${cantidadSolicitada}</strong> unidad(es)</li>
                </ul>
                <p class="text-info"><i class="fas fa-info-circle me-2"></i>El Jefe de Taller será notificado para solicitar más repuestos al proveedor.</p>
            </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Enviar Notificación',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'notificarJefeTallerStock');
                formData.append('solicitud_id', solicitudId);

                fetch(this.baseUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Notificación Enviada',
                            html: data.message || 'La notificación al Jefe de Taller ha sido enviada correctamente.',
                            icon: 'success',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#ffc107'
                        });
                        this.cargarSolicitudes();
                    } else {
                        const mensaje = data.message || 'Error al enviar la notificación';
                        const yaNotificado = mensaje.toLowerCase().includes('ya fue enviada');
                        
                        Swal.fire({
                            title: yaNotificado ? 'Notificación Ya Enviada' : 'Error',
                            html: mensaje.replace(/\n/g, '<br>'),
                            icon: yaNotificado ? 'info' : 'error',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: yaNotificado ? '#17a2b8' : '#dc3545'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error de conexión al enviar notificación', 'error');
                });
            }
        });
    }

    entregarSolicitud(solicitudId) {
        Swal.fire({
            title: '¿Entregar repuestos?',
            text: 'Esta acción descontará el stock y entregará los repuestos al mecánico.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, entregar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'entregarSolicitudRepuestos');
                formData.append('solicitud_id', solicitudId);

                fetch(this.baseUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Éxito', data.message || 'Repuestos entregados correctamente', 'success');
                        this.cargarSolicitudes();
                    } else {
                        Swal.fire('Error', data.message || 'Error al entregar los repuestos', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error de conexión al entregar repuestos', 'error');
                });
            }
        });
    }
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    const gestion = new GestionSolicitudesRepuestos();

    // Delegar eventos para botones dinámicos
    $(document).on('click', '.btn-aprobar', function() {
        const solicitudId = $(this).data('id');
        gestion.aprobarSolicitud(solicitudId);
    });

    $(document).on('click', '.btn-notificar-jefe', function() {
        const solicitudId = $(this).data('id');
        const stockDisponible = $(this).data('stock');
        const cantidadSolicitada = $(this).data('cantidad');
        gestion.notificarJefeTaller(solicitudId, stockDisponible, cantidadSolicitada);
    });

    $(document).on('click', '.btn-entregar', function() {
        const solicitudId = $(this).data('id');
        gestion.entregarSolicitud(solicitudId);
    });
});

