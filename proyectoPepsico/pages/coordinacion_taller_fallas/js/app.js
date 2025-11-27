// Aplicación para Coordinación con Taller (Comunicación de Flota)
class CoordinacionTallerFallasApp {
    constructor() {
        this.dataTable = null;
        this.baseUrl = this.getBaseUrl();
        this.usuarioId = this.getUsuarioId();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/comunicacion/scripts/s_comunicacion.php';
        }
        return '../../app/model/comunicacion/scripts/s_comunicacion.php';
    }

    getUsuarioId() {
        return window.usuarioId || 0;
    }

    inicializar() {
        this.inicializarEventos();
        this.inicializarDataTable();
        this.cargarComunicaciones();
    }

    inicializarEventos() {
        // Formulario nueva comunicación
        $('#form-nueva-comunicacion-taller').on('submit', (e) => {
            e.preventDefault();
            this.enviarComunicacion();
        });

        // Formulario responder comunicación
        $('#form-responder-comunicacion-taller').on('submit', (e) => {
            e.preventDefault();
            this.enviarRespuesta();
        });

        // Botón aplicar filtros
        $('#btn-aplicar-filtros-taller').on('click', () => {
            this.cargarComunicaciones();
        });

        // Botón actualizar
        $('#btn-actualizar-comunicaciones-taller').on('click', () => {
            this.cargarComunicaciones();
        });

        // Eventos delegados para botones dinámicos
        $(document).on('click', '.btn-ver-detalles-taller', (e) => {
            const id = $(e.currentTarget).data('id');
            this.verDetalles(id);
        });

        $(document).on('click', '.btn-responder-taller', (e) => {
            const id = $(e.currentTarget).data('id');
            const asunto = $(e.currentTarget).data('asunto');
            this.abrirModalResponder(id, asunto);
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#tabla-comunicaciones-taller').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay comunicaciones con el taller",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron comunicaciones",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            responsive: true,
            ajax: {
                url: this.baseUrl,
                type: 'POST',
                data: () => ({
                    accion: 'obtener_comunicaciones_flota',
                    filtro_placa: $('#filtro-placa-taller').val(),
                    filtro_tipo: $('#filtro-tipo-taller').val(),
                    filtro_estado: $('#filtro-estado-taller').val(),
                    usuario_id: this.usuarioId // Filtrar solo las comunicaciones del usuario actual
                })
            },
            columns: [
                { 
                    data: 'FechaCreacion',
                    render: (data) => data ? new Date(data).toLocaleDateString('es-ES', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    }) : 'N/A'
                },
                { data: 'Placa' },
                { 
                    data: 'Tipo',
                    render: (data) => {
                        const badges = {
                            'Urgente': '<span class="badge bg-danger">Urgente</span>',
                            'Solicitud': '<span class="badge bg-primary">Solicitud</span>',
                            'Notificación': '<span class="badge bg-info">Notificación</span>',
                            'Consulta': '<span class="badge bg-secondary">Consulta</span>'
                        };
                        return badges[data] || data;
                    }
                },
                { data: 'Asunto' },
                { 
                    data: 'Estado',
                    render: (data) => {
                        const badges = {
                            'Pendiente': '<span class="badge bg-warning">Pendiente</span>',
                            'En Proceso': '<span class="badge bg-info">En Proceso</span>',
                            'Respondida': '<span class="badge bg-success">Respondida</span>',
                            'Cerrada': '<span class="badge bg-secondary">Cerrada</span>'
                        };
                        return badges[data] || data;
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data) => {
                        let botones = `
                            <button class="btn btn-sm btn-info btn-ver-detalles-taller me-1" data-id="${data.ID}" title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                        `;
                        // Si no tiene respuesta y no es una respuesta misma, mostrar botón responder
                        if (data.Estado !== 'Respondida' && !data.ComunicacionPadreID) {
                            botones += `
                                <button class="btn btn-sm btn-success btn-responder-taller" 
                                        data-id="${data.ID}" 
                                        data-asunto="${data.Asunto || ''}" 
                                        title="Responder">
                                    <i class="fas fa-reply"></i>
                                </button>
                            `;
                        }
                        return botones;
                    }
                }
            ]
        });
    }

    cargarComunicaciones() {
        if (this.dataTable) {
            this.dataTable.ajax.reload();
        }
    }

    enviarComunicacion() {
        const placa = $('#placa-comunicacion-taller').val().trim().toUpperCase();
        const tipo = $('#tipo-comunicacion-taller').val();
        const asunto = $('#asunto-comunicacion-taller').val().trim();
        const mensaje = $('#mensaje-comunicacion-taller').val().trim();

        if (!placa || !tipo || !asunto || !mensaje) {
            this.mostrarNotificacion('Por favor, complete todos los campos obligatorios', 'error');
            return;
        }

        const btnSubmit = $('#form-nueva-comunicacion-taller button[type="submit"]');
        const textoOriginal = btnSubmit.html();
        btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando...');

        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: {
                accion: 'crear_comunicacion_flota',
                placa: placa,
                tipo: tipo,
                asunto: asunto,
                mensaje: mensaje,
                usuario_id: this.usuarioId
            },
            dataType: 'json',
            success: (response) => {
                btnSubmit.prop('disabled', false).html(textoOriginal);
                
                if (response.status === 'success') {
                    this.mostrarNotificacion('Comunicación enviada correctamente al taller', 'success');
                    $('#modalNuevaComunicacionTaller').modal('hide');
                    $('#form-nueva-comunicacion-taller')[0].reset();
                    this.cargarComunicaciones();
                } else {
                    this.mostrarNotificacion('Error: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                btnSubmit.prop('disabled', false).html(textoOriginal);
                console.error('Error al enviar comunicación:', error);
                this.mostrarNotificacion('Error de conexión. Por favor, intente nuevamente.', 'error');
            }
        });
    }

    verDetalles(id) {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: {
                accion: 'obtener_comunicacion',
                id: id,
                tipo: 'flota'
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    const data = response.data;
                    let html = `
                        <div class="mb-3">
                            <strong>Fecha:</strong> ${data.FechaCreacion ? new Date(data.FechaCreacion).toLocaleString('es-ES') : 'N/A'}
                        </div>
                        <div class="mb-3">
                            <strong>Placa:</strong> ${data.Placa || 'N/A'}
                        </div>
                        <div class="mb-3">
                            <strong>Tipo:</strong> 
                            <span class="badge bg-${data.Tipo === 'Urgente' ? 'danger' : data.Tipo === 'Solicitud' ? 'primary' : 'info'}">
                                ${data.Tipo || 'N/A'}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Asunto:</strong> ${data.Asunto || 'N/A'}
                        </div>
                        <div class="mb-3">
                            <strong>Mensaje:</strong>
                            <div class="alert alert-light mt-2">${data.Mensaje || 'N/A'}</div>
                        </div>
                        <div class="mb-3">
                            <strong>Estado:</strong> 
                            <span class="badge bg-${data.Estado === 'Pendiente' ? 'warning' : data.Estado === 'Respondida' ? 'success' : 'info'}">
                                ${data.Estado || 'N/A'}
                            </span>
                        </div>
                    `;

                    // Mostrar respuestas si existen
                    if (data.respuestas && data.respuestas.length > 0) {
                        html += '<div class="mt-4"><strong>Respuestas del Taller:</strong>';
                        data.respuestas.forEach((respuesta, index) => {
                            html += `
                                <div class="alert alert-success mt-2">
                                    <strong>Respuesta ${index + 1}:</strong><br>
                                    ${respuesta.Mensaje || 'N/A'}<br>
                                    <small class="text-muted">Fecha: ${respuesta.FechaCreacion ? new Date(respuesta.FechaCreacion).toLocaleString('es-ES') : 'N/A'}</small>
                                </div>
                            `;
                        });
                        html += '</div>';
                    }
                    
                    $('#detalles-comunicacion-taller').html(html);
                    
                    // Agregar botón responder si no tiene respuesta
                    let footer = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
                    if (data.Estado !== 'Respondida' && !data.ComunicacionPadreID) {
                        footer = `
                            <button type="button" class="btn btn-success" id="btn-responder-desde-detalles-taller" 
                                    data-id="${data.ID}" data-asunto="${data.Asunto || ''}">
                                <i class="fas fa-reply me-2"></i>Responder
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        `;
                    }
                    $('#footer-detalles-comunicacion-taller').html(footer);
                    
                    // Evento para botón responder desde detalles
                    $('#btn-responder-desde-detalles-taller').off('click').on('click', () => {
                        $('#modalDetallesComunicacionTaller').modal('hide');
                        this.abrirModalResponder(data.ID, data.Asunto);
                    });
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalDetallesComunicacionTaller'));
                    modal.show();
                } else {
                    this.mostrarNotificacion('Error al cargar detalles: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar detalles:', error);
                this.mostrarNotificacion('Error al cargar los detalles', 'error');
            }
        });
    }

    abrirModalResponder(id, asunto = '') {
        $('#comunicacion-id-respuesta-taller').val(id);
        $('#asunto-comunicacion-respuesta-taller').text(asunto || 'Sin asunto');
        $('#mensaje-respuesta-taller').val('');
        $('#modalResponderComunicacionTaller').modal('show');
    }

    enviarRespuesta() {
        const id = $('#comunicacion-id-respuesta-taller').val();
        const mensaje = $('#mensaje-respuesta-taller').val().trim();

        if (!mensaje) {
            this.mostrarNotificacion('Por favor, ingrese un mensaje de respuesta', 'error');
            return;
        }

        const btnSubmit = $('#form-responder-comunicacion-taller button[type="submit"]');
        const textoOriginal = btnSubmit.html();
        btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando...');

        // Obtener datos de la comunicación original para la respuesta
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: {
                accion: 'obtener_comunicacion',
                id: id,
                tipo: 'flota'
            },
            dataType: 'json',
            success: (responseComunicacion) => {
                if (responseComunicacion.status === 'success' && responseComunicacion.data) {
                    const comunicacionOriginal = responseComunicacion.data;
                    
                    // Enviar respuesta
                    $.ajax({
                        url: this.baseUrl,
                        type: 'POST',
                        data: {
                            accion: 'crear_comunicacion_flota',
                            placa: comunicacionOriginal.Placa,
                            tipo: 'Consulta',
                            asunto: 'Re: ' + comunicacionOriginal.Asunto,
                            mensaje: mensaje,
                            usuario_id: this.usuarioId,
                            comunicacion_padre_id: id
                        },
                        dataType: 'json',
                        success: (response) => {
                            btnSubmit.prop('disabled', false).html(textoOriginal);
                            
                            if (response.status === 'success') {
                                this.mostrarNotificacion('Respuesta enviada correctamente', 'success');
                                $('#modalResponderComunicacionTaller').modal('hide');
                                $('#form-responder-comunicacion-taller')[0].reset();
                                this.cargarComunicaciones();
                            } else {
                                this.mostrarNotificacion('Error: ' + (response.message || 'Error desconocido'), 'error');
                            }
                        },
                        error: (xhr, status, error) => {
                            btnSubmit.prop('disabled', false).html(textoOriginal);
                            console.error('Error al enviar respuesta:', error);
                            this.mostrarNotificacion('Error de conexión. Por favor, intente nuevamente.', 'error');
                        }
                    });
                } else {
                    btnSubmit.prop('disabled', false).html(textoOriginal);
                    this.mostrarNotificacion('Error al obtener datos de la comunicación', 'error');
                }
            },
            error: (xhr, status, error) => {
                btnSubmit.prop('disabled', false).html(textoOriginal);
                console.error('Error al obtener comunicación:', error);
                this.mostrarNotificacion('Error al obtener datos de la comunicación', 'error');
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
let coordinacionTallerFallasApp;
document.addEventListener('DOMContentLoaded', () => {
    coordinacionTallerFallasApp = new CoordinacionTallerFallasApp();
});
