class TareasMecanico {
    constructor() {
        this.dataTable = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        if (!$('#tareas-table').length) {
            console.error('No se encontró la tabla tareas-table');
            return;
        }
        
        this.bindEvents();
        this.inicializarDataTable();
        this.cargarTareas();
    }

    bindEvents() {
        $('#btn-refresh').on('click', () => {
            this.cargarTareas();
        });

        $(document).on('click', '.btn-registrar-avance', (e) => {
            const asignacionId = $(e.currentTarget).data('id');
            this.mostrarModalAvance(asignacionId);
        });

        $(document).on('click', '.btn-ver-historial', (e) => {
            const asignacionId = $(e.currentTarget).data('id');
            this.mostrarHistorialAvances(asignacionId);
        });

        $('#guardar-avance').on('click', () => {
            this.guardarAvance();
        });
    }

    inicializarDataTable() {
        try {
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables no está cargado');
                return;
            }

            this.dataTable = $('#tareas-table').DataTable({
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                },
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                dom: '<"table-header"lf>rt<"table-footer"ip>',
                columns: [
                    { 
                        data: null,
                        render: (data) => {
                            return `
                                <div class="vehicle-info">
                                    <strong>${data.Marca} ${data.Modelo}</strong>
                                    <div class="vehicle-details">
                                        <small class="text-muted">${data.TipoVehiculo} • ${data.Color}</small>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    { 
                        data: 'Placa',
                        render: (data) => {
                            return `<span class="placa-badge">${data}</span>`;
                        }
                    },
                    { 
                        data: 'FechaAsignacion',
                        className: 'fecha-column'
                    },
                    { 
                        data: 'Estado',
                        render: (data) => {
                            const estadoClass = 'status-' + data.replace(' ', '');
                            return `<span class="${estadoClass}">${data}</span>`;
                        }
                    },
                    { 
                        data: 'Observaciones',
                        render: (data) => {
                            return data || '<span class="text-muted">Sin observaciones</span>';
                        }
                    },
                    { 
                        data: 'UltimoAvance',
                        render: (data) => {
                            if (!data) return '<span class="text-muted">Sin avances</span>';
                            return `
                                <small>
                                    <strong>${data.Fecha}</strong><br>
                                    ${data.Descripcion.substring(0, 50)}...
                                </small>
                            `;
                        }
                    },
                    { 
                        data: null,
                        orderable: false,
                        render: (data) => {
                            let botones = `
                                <button class="btn btn-info btn-sm btn-ver-historial" 
                                        data-id="${data.AsignacionID}" 
                                        title="Ver Historial">
                                    <i class="fas fa-history me-1"></i>
                                </button>
                            `;
                            
                            if (data.Estado !== 'Completado') {
                                botones += `
                                    <button class="btn btn-primary btn-sm btn-registrar-avance" 
                                            data-id="${data.AsignacionID}" 
                                            title="Registrar Avance">
                                        <i class="fas fa-clipboard-check me-1"></i>Avance
                                    </button>
                                `;
                            } else {
                                botones += `
                                    <span class="badge bg-success">Completado</span>
                                `;
                            }
                            
                            return `<div class="btn-group">${botones}</div>`;
                        }
                    }
                ],
                order: [[2, 'desc']],
                responsive: true,
                initComplete: () => {
                    $('.dataTables_length select').addClass('form-select form-select-sm');
                    $('.dataTables_filter input').addClass('form-control form-control-sm');
                }
            });
        } catch (error) {
            console.error('Error al inicializar DataTables:', error);
        }
    }

    cargarTareas() {
        if (this.isLoading) return;
        
        this.mostrarLoading();

        console.log("Cargando tareas..."); // Debug en consola del navegador

        $.ajax({
            url: '../app/model/tareas/scripts/s_tareas.php',
            type: 'GET',
            dataType: 'json',
            success: (response) => {
                console.log("Respuesta del servidor:", response); // Debug en consola
                if (response.status === 'success') {
                    this.cargarDataEnTabla(response.data);
                    this.actualizarResumen(response.data);
                    if (response.debug) {
                        console.log("Debug info:", response.debug);
                    }
                } else {
                    console.error("Error del servidor:", response);
                    this.mostrarError(response.message || 'Error desconocido');
                    // Mostrar información de debug si está disponible
                    if (response.session_data) {
                        console.error("Datos de sesión:", response.session_data);
                    }
                }
            },
            error: (xhr, status, error) => {
                console.error("Error en AJAX:", xhr.responseText, status, error);
                this.mostrarError('Error de conexión: ' + error);
            },
            complete: () => {
                this.ocultarLoading();
            }
        });
    }

    cargarDataEnTabla(data) {
        if (this.dataTable) {
            this.dataTable.clear();
            this.dataTable.rows.add(data).draw();
        }
    }

    actualizarResumen(data) {
        const total = data.length;
        const enProgreso = data.filter(item => item.Estado === 'En progreso').length;
        const pendientes = data.filter(item => item.Estado === 'Asignado').length;
        const completados = data.filter(item => item.Estado === 'Completado').length;

        $('#total-asignados').text(total);
        $('#en-progreso').text(enProgreso);
        $('#pendientes').text(pendientes);
        $('#completados').text(completados);
    }

    mostrarModalAvance(asignacionId) {
        // Cargar datos de la asignación y vehículo
        $.ajax({
            url: '../app/model/tareas/scripts/s_detalles_asignacion.php',
            type: 'POST',
            data: { asignacion_id: asignacionId },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    const data = response.data;
                    
                    // Llenar datos del modal
                    $('#avance-asignacion-id').val(asignacionId);
                    $('#avance-vehiculo-id').val(data.VehiculoID);
                    $('#modal-placa-avance').text(data.Placa);
                    
                    // Información del vehículo
                    $('#info-placa').text(data.Placa);
                    $('#info-vehiculo').text(`${data.Marca} ${data.Modelo} - ${data.TipoVehiculo}`);
                    $('#info-color').text(data.Color);
                    $('#info-conductor').text(data.ConductorNombre);
                    $('#info-empresa').text(data.EmpresaNombre);
                    $('#info-estado').text(data.Estado);
                    
                    // Limpiar formulario
                    $('#avance-descripcion').val('');
                    $('#avance-estado').val('En progreso');
                    
                    const avanceModal = new bootstrap.Modal(document.getElementById('avanceModal'));
                    avanceModal.show();
                } else {
                    this.mostrarError('Error al cargar datos: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
            }
        });
    }

    mostrarHistorialAvances(asignacionId) {
        $.ajax({
            url: '../app/model/tareas/scripts/s_historial_avances.php',
            type: 'POST',
            data: { asignacion_id: asignacionId },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarHistorialEnModal(response.data);
                } else {
                    this.mostrarError('Error al cargar historial: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
            }
        });
    }

    mostrarHistorialEnModal(data) {
        $('#modal-placa-historial').text(data.vehiculo.Placa);
        
        let html = '';
        
        if (data.avances.length === 0) {
            html = '<div class="alert alert-info">No hay avances registrados para este vehículo.</div>';
        } else {
            data.avances.forEach(avance => {
                html += `
                    <div class="card mb-3">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>${avance.FechaAvance}</strong>
                                <span class="badge bg-${avance.Estado === 'Completado' ? 'success' : 'info'}">
                                    ${avance.Estado}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">${avance.Descripcion}</p>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#historial-avances').html(html);
        
        const historialModal = new bootstrap.Modal(document.getElementById('historialModal'));
        historialModal.show();
    }

    guardarAvance() {
        const asignacionId = $('#avance-asignacion-id').val();
        const descripcion = $('#avance-descripcion').val().trim();
        const estado = $('#avance-estado').val();

        if (!descripcion) {
            this.mostrarError('La descripción del avance es requerida');
            return;
        }

        $('#guardar-avance').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

        $.ajax({
            url: '../app/model/tareas/scripts/s_guardar_avance.php',
            type: 'POST',
            data: {
                asignacion_id: asignacionId,
                descripcion: descripcion,
                estado: estado
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarToast('Éxito', 'Avance registrado correctamente', 'success');
                    this.cerrarModalAvance();
                    this.cargarTareas(); // Recargar la tabla
                } else {
                    this.mostrarError('Error al guardar: ' + response.message);
                    $('#guardar-avance').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Avance');
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
                $('#guardar-avance').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Avance');
            }
        });
    }

    cerrarModalAvance() {
        const avanceModal = bootstrap.Modal.getInstance(document.getElementById('avanceModal'));
        avanceModal.hide();
        
        // Limpiar formulario
        $('#avance-form')[0].reset();
        $('#guardar-avance').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Avance');
    }

    mostrarLoading() {
        this.isLoading = true;
        $('#btn-refresh').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Actualizando...');
    }

    ocultarLoading() {
        this.isLoading = false;
        $('#btn-refresh').prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i>Actualizar');
    }

    mostrarError(mensaje) {
        this.mostrarToast('Error', mensaje, 'danger');
    }

    mostrarToast(titulo, mensaje, tipo = 'info') {
        const toastHtml = `
            <div class="toast align-items-center text-bg-${tipo} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${titulo}:</strong> ${mensaje}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        // Crear contenedor de toasts en la esquina superior derecha
        let $container = $('#toast-container');
        if (!$container.length) {
            $('body').append('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>');
            $container = $('#toast-container');
        }
        
        $container.append(toastHtml);
        $('.toast').toast('show');
    }
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    window.tareas = new TareasMecanico();
});