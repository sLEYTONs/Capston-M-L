// Aplicación para Coordinación con Jefe de Taller
class CoordinacionJefeTaller {
    constructor() {
        this.dataTable = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/coordinacion_jefe_taller/scripts/s_coordinacion_jefe_taller.php';
        }
        return '../../app/model/coordinacion_jefe_taller/scripts/s_coordinacion_jefe_taller.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.inicializarDataTable();
        this.cargarEstadisticas();
        this.cargarComunicaciones();
    }

    inicializarEventos() {
        $('#form-comunicacion-jefe').on('submit', (e) => {
            e.preventDefault();
            this.enviarComunicacion();
        });

        $('#btn-actualizar-comunicaciones').on('click', () => {
            this.cargarComunicaciones();
            this.cargarEstadisticas();
        });

        $(document).on('click', '.btn-ver-detalles', (e) => {
            const id = $(e.currentTarget).data('id');
            this.verDetalles(id);
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#tabla-comunicaciones').DataTable({
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
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            responsive: true,
            ajax: {
                url: this.baseUrl,
                type: 'POST',
                data: { accion: 'obtener_comunicaciones' }
            },
            columns: [
                { 
                    data: 'FechaCreacion',
                    render: (data) => new Date(data).toLocaleDateString('es-ES', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    })
                },
                { data: 'TipoSolicitud' },
                { data: 'Asunto' },
                { 
                    data: 'Prioridad',
                    render: (data) => {
                        const badges = {
                            'urgente': '<span class="badge bg-danger">Urgente</span>',
                            'alta': '<span class="badge bg-warning">Alta</span>',
                            'media': '<span class="badge bg-info">Media</span>',
                            'baja': '<span class="badge bg-secondary">Baja</span>'
                        };
                        return badges[data] || data;
                    }
                },
                { 
                    data: 'Estado',
                    render: (data) => {
                        const badges = {
                            'Pendiente': '<span class="badge bg-warning">Pendiente</span>',
                            'Aprobada': '<span class="badge bg-success">Aprobada</span>',
                            'Rechazada': '<span class="badge bg-danger">Rechazada</span>',
                            'En Proceso': '<span class="badge bg-info">En Proceso</span>'
                        };
                        return badges[data] || data;
                    }
                },
                { 
                    data: 'Respuesta',
                    render: (data) => data ? (data.substring(0, 50) + '...') : '<span class="text-muted">Sin respuesta</span>'
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data) => {
                        return `
                            <button class="btn btn-sm btn-info btn-ver-detalles" data-id="${data.ID}" title="Ver Detalles">
                                <i class="fas fa-eye"></i>
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
                    $('#solicitudes-pendientes').text(response.data.pendientes || 0);
                    $('#en-proceso').text(response.data.en_proceso || 0);
                    $('#completadas').text(response.data.completadas || 0);
                    $('#urgentes').text(response.data.urgentes || 0);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar estadísticas:', error);
            }
        });
    }

    cargarComunicaciones() {
        if (this.dataTable) {
            this.dataTable.ajax.reload();
        }
    }

    enviarComunicacion() {
        const tipo = $('#tipo-comunicacion').val();
        const prioridad = $('#prioridad-comunicacion').val();
        const asunto = $('#asunto-comunicacion').val().trim();
        const mensaje = $('#mensaje-comunicacion').val().trim();

        if (!tipo || !prioridad || !asunto || !mensaje) {
            this.mostrarNotificacion('Por favor, complete todos los campos obligatorios', 'error');
            return;
        }

        const btnSubmit = $('#form-comunicacion-jefe button[type="submit"]');
        const textoOriginal = btnSubmit.html();
        btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando...');

        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: {
                accion: 'crear_comunicacion',
                tipo_solicitud: tipo,
                prioridad: prioridad,
                asunto: asunto,
                descripcion: mensaje
            },
            dataType: 'json',
            success: (response) => {
                btnSubmit.prop('disabled', false).html(textoOriginal);
                
                if (response.status === 'success') {
                    this.mostrarNotificacion('Comunicación enviada correctamente', 'success');
                    $('#form-comunicacion-jefe')[0].reset();
                    this.cargarComunicaciones();
                    this.cargarEstadisticas();
                } else {
                    this.mostrarNotificacion('Error: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                btnSubmit.prop('disabled', false).html(textoOriginal);
                this.mostrarNotificacion('Error de conexión. Por favor, intente nuevamente.', 'error');
            }
        });
    }

    verDetalles(id) {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { 
                accion: 'obtener_detalles',
                id: id
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    const data = response.data;
                    const html = `
                        <div class="mb-3">
                            <strong>Fecha:</strong> ${new Date(data.FechaCreacion).toLocaleString('es-ES')}
                        </div>
                        <div class="mb-3">
                            <strong>Tipo:</strong> ${data.TipoSolicitud}
                        </div>
                        <div class="mb-3">
                            <strong>Prioridad:</strong> 
                            <span class="badge bg-${data.Prioridad === 'urgente' ? 'danger' : data.Prioridad === 'alta' ? 'warning' : 'info'}">
                                ${data.Prioridad}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Asunto:</strong> ${data.Asunto}
                        </div>
                        <div class="mb-3">
                            <strong>Descripción:</strong>
                            <p class="mt-2">${data.Descripcion}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Estado:</strong> 
                            <span class="badge bg-${data.Estado === 'Pendiente' ? 'warning' : data.Estado === 'Aprobada' ? 'success' : 'danger'}">
                                ${data.Estado}
                            </span>
                        </div>
                        ${data.Respuesta ? `
                            <div class="mb-3">
                                <strong>Respuesta:</strong>
                                <div class="alert alert-info mt-2">${data.Respuesta}</div>
                                <small class="text-muted">Fecha: ${data.FechaRespuesta ? new Date(data.FechaRespuesta).toLocaleString('es-ES') : '-'}</small>
                            </div>
                        ` : ''}
                    `;
                    
                    $('#detalles-comunicacion').html(html);
                    const modal = new bootstrap.Modal(document.getElementById('modal-detalles-comunicacion'));
                    modal.show();
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar detalles:', error);
                this.mostrarNotificacion('Error al cargar los detalles', 'error');
            }
        });
    }

    mostrarNotificacion(mensaje, tipo) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: tipo === 'success' ? 'success' : 'error',
                title: tipo === 'success' ? 'Éxito' : 'Error',
                text: mensaje,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert(mensaje);
        }
    }
}

// Inicializar cuando el DOM esté listo
let coordinacionJefeTaller;
document.addEventListener('DOMContentLoaded', () => {
    coordinacionJefeTaller = new CoordinacionJefeTaller();
});
