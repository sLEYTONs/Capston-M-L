class ConsultaVehiculos {
    constructor() {
        this.dataTable = null;
        this.isLoading = false;
        this.timeoutBusqueda = null;
        this.init();
    }

    init() {
        if (!$('#results-table').length) {
            console.error('No se encontró la tabla results-table');
            return;
        }
        
        this.bindEvents();
        this.inicializarDataTable();
        this.inicializarBotonesLimpiar();
        this.cargarTodosLosVehiculos();
    }

    bindEvents() {
        const $searchBtn = $('#search-btn');
        const $searchInputs = $('.search-input');
        
        if ($searchBtn.length) {
            $searchBtn.on('click', () => this.buscarVehiculos());
        }
        
        if ($searchInputs.length) {
            $searchInputs.on('keypress', (e) => {
                if (e.which === 13) {
                    this.buscarVehiculos();
                }
            });

            $searchInputs.on('input', (e) => {
                this.toggleClearButton(e.target);
                this.debounceBusqueda();
            });

            $searchInputs.on('change', (e) => {
                this.toggleClearButton(e.target);
                this.buscarVehiculos();
            });
        }

        // Botones de limpiar
        $(document).on('click', '.btn-clear', (e) => {
            e.preventDefault();
            this.limpiarCampo(e.currentTarget);
        });
    }

    debounceBusqueda() {
        clearTimeout(this.timeoutBusqueda);
        this.timeoutBusqueda = setTimeout(() => {
            this.buscarVehiculos();
        }, 500);
    }

    inicializarBotonesLimpiar() {
        $('.search-input').each((index, input) => {
            this.toggleClearButton(input);
        });
    }

    toggleClearButton(input) {
        const $input = $(input);
        const $clearBtn = $input.siblings('.btn-clear');
        const hasValue = $input.val().length > 0;
        
        if (hasValue) {
            $clearBtn.removeClass('hidden');
        } else {
            $clearBtn.addClass('hidden');
        }
    }

    limpiarCampo(button) {
        const $button = $(button);
        const targetId = $button.data('target');
        const $input = $('#' + targetId);
        
        $input.val('');
        $button.addClass('hidden');
        this.buscarVehiculos();
    }

    inicializarDataTable() {
        try {
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables no está cargado');
                return;
            }

            this.dataTable = $('#results-table').DataTable({
                language: {
                    "sProcessing": "Procesando...",
                    "sLengthMenu": "Mostrar _MENU_ registros",
                    "sZeroRecords": "No se encontraron resultados",
                    "sEmptyTable": "Ningún dato disponible en esta tabla",
                    "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                    "sInfoPostFix": "",
                    "sSearch": "Buscar:",
                    "sUrl": "",
                    "sInfoThousands": ",",
                    "sLoadingRecords": "Cargando...",
                    "oPaginate": {
                        "sFirst": "Primero",
                        "sLast": "Último",
                        "sNext": "Siguiente",
                        "sPrevious": "Anterior"
                    },
                    "oAria": {
                        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                    }
                },
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                dom: '<"table-header"lf>rt<"table-footer"ip>',
                columns: [
                    { 
                        data: 'Placa',
                        className: 'placa-column'
                    },
                    { data: null,
                        render: (data) => {
                            return `
                                <div class="vehicle-info">
                                    <span class="vehicle-type">${data.TipoVehiculo}</span>
                                    <small class="vehicle-details">${data.Marca} ${data.Modelo}</small>
                                </div>
                            `;
                        }
                    },
                    { data: null,
                        render: (data) => {
                            return `
                                <div class="driver-info">
                                    <strong>${data.ConductorNombre}</strong>
                                </div>
                            `;
                        }
                    },
                    { 
                        data: 'FechaIngresoFormateada',
                        className: 'fecha-column'
                    },
                    { 
                        data: 'MecanicoNombre',
                        render: (data, type, row) => {
                            if (data && data.trim() !== '') {
                                return `
                                    <div class="mechanic-info">
                                        <i class="fas fa-user-cog me-1"></i>
                                        <strong>${data}</strong>
                                    </div>
                                `;
                            } else {
                                return `<span class="text-muted">Sin asignar</span>`;
                            }
                        },
                        className: 'mecanico-column'
                    },
                    { 
                        data: 'Estado',
                        render: (data) => {
                            const estadoClass = this.obtenerClaseEstado(data);
                            const estadoText = this.obtenerTextoEstado(data);
                            return `<span class="status-badge ${estadoClass}">${estadoText}</span>`;
                        }
                    },
                    { 
                        data: null,
                        orderable: false,
                        render: (data) => {
                            let acciones = `
                                <div class="btn-group">
                                    <button class="btn-action btn-view" data-id="${data.ID}" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                            `;

                            // Mostrar botón de seguimiento si tiene mecánico asignado
                            if (data.MecanicoNombre && data.MecanicoNombre.trim() !== '') {
                                acciones += `
                                    <button class="btn-action btn-tracking" data-id="${data.ID}" title="Ver Seguimiento">
                                        <i class="fas fa-clipboard-list"></i>
                                    </button>
                                `;
                            }

                            acciones += `</div>`;
                            return acciones;
                        }
                    }
                ],
                order: [[3, 'desc']],
                responsive: true,
                initComplete: () => {
                    $('.dataTables_length select').addClass('form-select form-select-sm');
                    $('.dataTables_filter input').addClass('form-control form-control-sm');
                    this.bindTableEvents();
                }
            });
        } catch (error) {
            console.error('Error al inicializar DataTables:', error);
        }
    }

    bindTableEvents() {
        $(document).on('click', '.btn-view', (e) => {
            const id = $(e.currentTarget).data('id');
            this.verDetalles(id);
        });

        $(document).on('click', '.btn-tracking', (e) => {
            const id = $(e.currentTarget).data('id');
            this.verSeguimiento(id);
        });
    }

    obtenerFiltros() {
        return {
            placa: $('#search-plate').val().trim(),
            conductor: $('#search-driver').val().trim(),
            fecha: $('#search-date').val()
        };
    }

    buscarVehiculos() {
        if (this.isLoading) return;
        
        const filtros = this.obtenerFiltros();
        
        this.mostrarLoading();

        $.ajax({
            url: '../app/model/consulta/scripts/s_consulta.php',
            type: 'POST',
            data: filtros,
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.cargarDataEnTabla(response.data);
                    this.actualizarContador(response.data.length);
                } else {
                    this.mostrarError(response.message);
                }
            },
            error: (xhr, status, error) => {
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

    cargarTodosLosVehiculos() {
        this.buscarVehiculos();
    }

    actualizarContador(total) {
        const $count = $('#results-count');
        if ($count.length) {
            $count.text(total);
        }
    }

    verDetalles(id) {
        this.mostrarModalDetalles(id);
    }

    editarVehiculo(id) {
        this.cargarDatosParaEdicion(id);
    }

    mostrarModalDetalles(id) {
        $.ajax({
            url: '../app/model/consulta/scripts/s_detalles.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarDetallesEnModal(response.data);
                } else {
                    this.mostrarError('Error al cargar detalles: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión al cargar detalles: ' + error);
            }
        });
    }

    mostrarDetallesEnModal(vehiculo) {
        const modalHtml = `
            <div class="modal fade" id="detallesModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-car me-2"></i>
                                Detalles del Vehículo - ${vehiculo.Placa}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Información del Vehículo</h6>
                                    <p><strong>Placa:</strong> ${vehiculo.Placa}</p>
                                    <p><strong>Tipo:</strong> ${vehiculo.TipoVehiculo}</p>
                                    <p><strong>Marca/Modelo:</strong> ${vehiculo.Marca} ${vehiculo.Modelo}</p>
                                    <p><strong>Año:</strong> ${vehiculo.Anio || 'N/A'}</p>
                                    <p><strong>Kilometraje:</strong> ${vehiculo.Kilometraje || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Información del Conductor</h6>
                                    <p><strong>Nombre:</strong> ${vehiculo.ConductorNombre}</p>
                                    <p><strong>Fecha Ingreso:</strong> ${vehiculo.FechaIngresoFormateada}</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#detallesModal').remove();
        $('body').append(modalHtml);
        
        if (typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(document.getElementById('detallesModal')).show();
        } else {
            $('#detallesModal').modal('show');
        }
    }
    
    cargarDatosParaEdicion(id) {
        // Mostrar loading en el modal
        this.mostrarLoadingModal();

        $.ajax({
            url: '../app/model/consulta/scripts/s_detalles.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarModalEdicion(response.data);
                } else {
                    this.mostrarError('Error al cargar datos para edición: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión al cargar datos: ' + error);
            },
            complete: () => {
                this.ocultarLoadingModal();
            }
        });
    }

    mostrarLoadingModal() {
        const loadingHtml = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando datos...</p>
            </div>
        `;
        $('#editarModal .modal-body').html(loadingHtml);
    }

    ocultarLoadingModal() {
        // Se restaurará el formulario cuando se carguen los datos
    }

    mostrarModalEdicion(vehiculo) {
        // Llenar el formulario con los datos del vehículo
        $('#edit-id').val(vehiculo.ID);
        $('#modal-placa').text(vehiculo.Placa);
        $('#edit-placa').val(vehiculo.Placa);
        $('#edit-tipo-vehiculo').val(vehiculo.TipoVehiculo);
        $('#edit-marca').val(vehiculo.Marca);
        $('#edit-modelo').val(vehiculo.Modelo);
        $('#edit-anio').val(vehiculo.Anio);
        $('#edit-conductor-nombre').val(vehiculo.ConductorNombre);
        $('#edit-estado').val(vehiculo.Estado);

        // Mostrar el modal
        const editarModal = new bootstrap.Modal(document.getElementById('editarModal'));
        editarModal.show();

        // Bindear evento de guardar
        this.bindGuardarEvent();
    }

    bindGuardarEvent() {
        $('#guardar-cambios').off('click').on('click', () => {
            this.guardarCambios();
        });
    }

    guardarCambios() {
        // Obtener los datos directamente del formulario
        const formData = new FormData(document.getElementById('editar-form'));
        
        // Convertir FormData a objeto
        const datos = {};
        for (let [key, value] of formData.entries()) {
            datos[key] = value.trim();
        }

        console.log('Datos a enviar:', datos); // Debug

        // Validación básica
        if (!this.validarFormularioEdicion(datos)) {
            return;
        }

        // Mostrar loading en el botón
        $('#guardar-cambios').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

        $.ajax({
            url: 'app/model/consulta/scripts/s_editar.php',
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                console.log('Respuesta del servidor:', response); // Debug
                if (response.status === 'success') {
                    this.mostrarToast('Éxito', 'Vehículo actualizado correctamente', 'success');
                    this.cerrarModalEdicion();
                    this.buscarVehiculos(); // Refrescar la tabla
                } else {
                    this.mostrarError('Error al guardar: ' + response.message);
                    $('#guardar-cambios').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Cambios');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error en AJAX:', error); // Debug
                this.mostrarError('Error de conexión: ' + error);
                $('#guardar-cambios').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Cambios');
            }
        });
    }

    validarFormularioEdicion(datos) {
        const camposRequeridos = [
            'Placa', 'TipoVehiculo', 'Marca', 'Modelo', 
            'ConductorNombre'
        ];

        for (const campo of camposRequeridos) {
            if (!datos[campo]) {
                this.mostrarError(`El campo ${campo} es requerido`);
                // Enfocar el campo correspondiente
                const campoId = 'edit-' + campo.toLowerCase().replace(/([a-z])([A-Z])/g, '$1-$2');
                $('#' + campoId).focus();
                return false;
            }
        }

        if (datos.Anio && (datos.Anio < 1980 || datos.Anio > 2025)) {
            this.mostrarError('El año debe estar entre 1980 y 2025');
            $('#edit-anio').focus();
            return false;
        }

        return true;
    }

    cerrarModalEdicion() {
        const editarModal = bootstrap.Modal.getInstance(document.getElementById('editarModal'));
        editarModal.hide();
        
        // Limpiar formulario
        $('#editar-form')[0].reset();
        $('#guardar-cambios').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Cambios');
    }

    // Nuevo método para asignar mecánico
    asignarMecanico(id) {
        this.cargarDatosParaAsignacion(id);
    }

    cargarDatosParaAsignacion(id) {
        $.ajax({
            url: '../app/model/consulta/scripts/s_detalles.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarModalAsignacion(response.data);
                } else {
                    this.mostrarError('Error al cargar datos para asignación: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
            }
        });
    }

    mostrarModalAsignacion(vehiculo) {
        $('#asignar-vehiculo-id').val(vehiculo.ID);
        $('#modal-placa-asignar').text(vehiculo.Placa);

        // Cargar lista de mecánicos disponibles
        this.cargarMecanicosDisponibles();

        const asignarModal = new bootstrap.Modal(document.getElementById('asignarModal'));
        asignarModal.show();

        // Bindear evento de confirmación
        this.bindConfirmarAsignacion();
    }

    cargarMecanicosDisponibles() {
        $.ajax({
            url: '../app/model/consulta/scripts/s_mecanicos.php',
            type: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    const $select = $('#asignar-mecanico');
                    $select.empty().append('<option value="">Seleccionar mecánico...</option>');
                    
                    response.data.forEach(mecanico => {
                        $select.append(`<option value="${mecanico.UsuarioID}">${mecanico.NombreUsuario}</option>`);
                    });
                } else {
                    this.mostrarError('Error al cargar mecánicos: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error al cargar mecánicos: ' + error);
            }
        });
    }

    bindConfirmarAsignacion() {
        $('#confirmar-asignacion').off('click').on('click', () => {
            this.confirmarAsignacion();
        });
    }

    confirmarAsignacion() {
        const vehiculoID = $('#asignar-vehiculo-id').val();
        const mecanicoID = $('#asignar-mecanico').val();
        const observaciones = $('#asignar-observaciones').val();

        if (!mecanicoID) {
            this.mostrarError('Seleccione un mecánico');
            return;
        }

        $('#confirmar-asignacion').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Asignando...');

        $.ajax({
            url: '../app/model/consulta/scripts/s_asignar.php',
            type: 'POST',
            data: {
                vehiculo_id: vehiculoID,
                mecanico_id: mecanicoID,
                observaciones: observaciones
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarToast('Éxito', 'Mecánico asignado correctamente', 'success');
                    this.cerrarModalAsignacion();
                    this.buscarVehiculos(); // Refrescar la tabla
                } else {
                    this.mostrarError('Error al asignar: ' + response.message);
                    $('#confirmar-asignacion').prop('disabled', false).html('<i class="fas fa-user-cog me-2"></i>Asignar Mecánico');
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
                $('#confirmar-asignacion').prop('disabled', false).html('<i class="fas fa-user-cog me-2"></i>Asignar Mecánico');
            }
        });
    }

    cerrarModalAsignacion() {
        const asignarModal = bootstrap.Modal.getInstance(document.getElementById('asignarModal'));
        asignarModal.hide();
        
        // Limpiar formulario
        $('#asignar-form')[0].reset();
        $('#confirmar-asignacion').prop('disabled', false).html('<i class="fas fa-user-cog me-2"></i>Asignar Mecánico');
    }

    // Método para ver seguimiento
    verSeguimiento(id) {
        this.cargarSeguimiento(id);
    }

    cargarSeguimiento(id) {
        $.ajax({
            url: '../app/model/consulta/scripts/s_seguimiento.php',
            type: 'POST',
            data: { vehiculo_id: id },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarModalSeguimiento(response.data);
                } else {
                    this.mostrarError('Error al cargar seguimiento: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
            }
        });
    }

    mostrarModalSeguimiento(data) {
        $('#modal-placa-seguimiento').text(data.vehiculo.Placa);
        
        // Mostrar información de asignación
        this.mostrarInfoAsignacion(data.asignacion, data.vehiculo);
        
        // Mostrar avances del mecánico
        this.mostrarAvancesMecanico(data.avances);

        const seguimientoModal = new bootstrap.Modal(document.getElementById('seguimientoModal'));
        seguimientoModal.show();
    }

    mostrarInfoAsignacion(asignacion, vehiculo) {
        if (!asignacion) {
            $('#info-asignacion').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No hay asignación activa para este vehículo.
                </div>
            `);
            return;
        }

        const estadoClass = asignacion.Estado.toLowerCase().replace(/\s+/g, '');
        const estadoColor = {
            'asignado': 'warning',
            'enproceso': 'info',
            'en proceso': 'info',
            'enpausa': 'warning',
            'en pausa': 'warning',
            'completado': 'success'
        }[estadoClass] || 'secondary';

        // Determinar si está en pausa
        const estaEnPausa = asignacion.Estado && asignacion.Estado.toLowerCase().includes('pausa') || 
                           (asignacion.MotivoPausa && asignacion.MotivoPausa.trim && asignacion.MotivoPausa.trim() !== '');

        const html = `
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-user-cog me-2"></i>Información de Asignación
                    </h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="fw-bold" style="width: 40%;">Mecánico:</td>
                            <td>${asignacion.MecanicoNombre}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Fecha Asignación:</td>
                            <td>${asignacion.FechaAsignacion}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Estado Actual:</td>
                            <td>
                                <span class="badge bg-${estadoColor}">
                                    ${estaEnPausa ? '<i class="fas fa-pause-circle me-1"></i>' : ''}
                                    ${asignacion.Estado}
                                </span>
                            </td>
                        </tr>
                    </table>
                    ${estaEnPausa && asignacion.MotivoPausa && asignacion.MotivoPausa.trim && asignacion.MotivoPausa.trim() !== '' ? `
                        <div class="mt-3 pt-3 border-top">
                            <div class="alert alert-warning mb-0">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-pause-circle fa-2x me-3 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <strong class="d-block mb-2">
                                            <i class="fas fa-info-circle me-2"></i>Tarea en Pausa
                                        </strong>
                                        <p class="mb-2"><strong>Motivo:</strong> ${asignacion.MotivoPausa}</p>
                                        ${asignacion.RepuestosSolicitados && Array.isArray(asignacion.RepuestosSolicitados) && asignacion.RepuestosSolicitados.length > 0 ? `
                                            <div class="mt-2">
                                                <strong class="d-block mb-2">
                                                    <i class="fas fa-tools me-2"></i>Repuestos Solicitados:
                                                </strong>
                                                <ul class="list-unstyled mb-0">
                                                    ${asignacion.RepuestosSolicitados.map(repuesto => {
                                                        const urgenciaColor = {
                                                            'Alta': 'danger',
                                                            'Media': 'warning',
                                                            'Baja': 'info'
                                                        }[repuesto.Urgencia] || 'secondary';
                                                        const estadoColor = repuesto.EstadoSolicitud === 'Aprobada' ? 'success' : 'warning';
                                                        return `
                                                            <li class="mb-2 p-2 bg-light rounded">
                                                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                                                    <div class="mb-1 mb-md-0">
                                                                        <strong>${repuesto.RepuestoNombre}</strong>
                                                                        <span class="text-muted ms-2">(Cantidad: ${repuesto.Cantidad})</span>
                                                                    </div>
                                                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                                                        <div>
                                                                            <small class="text-muted d-block d-md-inline me-1">Urgencia:</small>
                                                                            <span class="badge bg-${urgenciaColor}">${repuesto.Urgencia}</span>
                                                                        </div>
                                                                        <div>
                                                                            <small class="text-muted d-block d-md-inline me-1">Estado:</small>
                                                                            <span class="badge bg-${estadoColor}">${repuesto.EstadoSolicitud}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </li>
                                                        `;
                                                    }).join('')}
                                                </ul>
                                            </div>
                                        ` : asignacion.SolicitudesPendientes && parseInt(asignacion.SolicitudesPendientes) > 0 ? `
                                            <div class="mt-2">
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    ${asignacion.SolicitudesPendientes} solicitud(es) de repuestos pendiente(s)
                                                </span>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : estaEnPausa ? `
                        <div class="mt-3 pt-3 border-top">
                            <div class="alert alert-warning mb-0">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-pause-circle fa-2x me-3 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <strong class="d-block mb-2">
                                            <i class="fas fa-info-circle me-2"></i>Tarea en Pausa
                                        </strong>
                                        <p class="mb-2 text-muted">La tarea se encuentra en pausa.</p>
                                        ${asignacion.RepuestosSolicitados && Array.isArray(asignacion.RepuestosSolicitados) && asignacion.RepuestosSolicitados.length > 0 ? `
                                            <div class="mt-2">
                                                <strong class="d-block mb-2">
                                                    <i class="fas fa-tools me-2"></i>Repuestos Solicitados:
                                                </strong>
                                                <ul class="list-unstyled mb-0">
                                                    ${asignacion.RepuestosSolicitados.map(repuesto => {
                                                        const urgenciaColor = {
                                                            'Alta': 'danger',
                                                            'Media': 'warning',
                                                            'Baja': 'info'
                                                        }[repuesto.Urgencia] || 'secondary';
                                                        const estadoColor = repuesto.EstadoSolicitud === 'Aprobada' ? 'success' : 'warning';
                                                        return `
                                                            <li class="mb-2 p-2 bg-light rounded">
                                                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                                                    <div class="mb-1 mb-md-0">
                                                                        <strong>${repuesto.RepuestoNombre}</strong>
                                                                        <span class="text-muted ms-2">(Cantidad: ${repuesto.Cantidad})</span>
                                                                    </div>
                                                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                                                        <div>
                                                                            <small class="text-muted d-block d-md-inline me-1">Urgencia:</small>
                                                                            <span class="badge bg-${urgenciaColor}">${repuesto.Urgencia}</span>
                                                                        </div>
                                                                        <div>
                                                                            <small class="text-muted d-block d-md-inline me-1">Estado:</small>
                                                                            <span class="badge bg-${estadoColor}">${repuesto.EstadoSolicitud}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </li>
                                                        `;
                                                    }).join('')}
                                                </ul>
                                            </div>
                                        ` : asignacion.SolicitudesPendientes && parseInt(asignacion.SolicitudesPendientes) > 0 ? `
                                            <div class="mt-2">
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    ${asignacion.SolicitudesPendientes} solicitud(es) de repuestos pendiente(s)
                                                </span>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    ${asignacion.Observaciones ? `
                        <div class="mt-3 pt-3 border-top">
                            <strong>Observaciones:</strong>
                            <p class="mb-0 text-muted small">${asignacion.Observaciones}</p>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        $('#info-asignacion').html(html);
        
        // Inicializar tooltips
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = $('#info-asignacion [data-bs-toggle="tooltip"]');
            tooltipTriggerList.each((index, element) => {
                new bootstrap.Tooltip(element);
            });
        }
    }

    mostrarAvancesMecanico(avances) {
        let html = '';
        
        if (avances.length === 0) {
            html = '<div class="alert alert-info">No hay avances registrados aún.</div>';
        } else {
            html = '<div class="timeline-avances">';
            avances.forEach((avance, index) => {
                const fotos = avance.Fotos && Array.isArray(avance.Fotos) ? avance.Fotos : [];
                const estadoClass = avance.Estado.toLowerCase().replace(/\s+/g, '');
                const estadoColor = {
                    'asignado': 'warning',
                    'enproceso': 'info',
                    'en progreso': 'info',
                    'enpausa': 'warning',
                    'en pausa': 'warning',
                    'completado': 'success'
                }[estadoClass] || 'secondary';
                
                html += `
                    <div class="avance-item avance-${estadoClass} mb-4">
                        <div class="avance-header d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center">
                                <div class="avance-indicador avance-indicador-${estadoColor} me-3"></div>
                                <div>
                                    <strong class="avance-fecha">${avance.FechaAvance}</strong>
                                    <div class="avance-estado-badge badge bg-${estadoColor} ms-2">${avance.Estado}</div>
                                </div>
                            </div>
                        </div>
                        <div class="avance-contenido">
                            <div class="avance-descripcion mb-2">
                                ${avance.Descripcion || 'Sin descripción'}
                            </div>
                            ${fotos.length > 0 ? `
                                <div class="avance-fotos mt-3">
                                    <small class="text-muted mb-2 d-block">
                                        <i class="fas fa-images me-1"></i>${fotos.length} foto(s) adjunta(s):
                                    </small>
                                    <div class="row g-2">
                                        ${fotos.map((foto, fotoIndex) => {
                                            const fotoData = typeof foto === 'string' ? foto : (foto.ruta || foto.data || foto.foto || foto);
                                            return `
                                                <div class="col-md-3 col-sm-4 col-6">
                                                    <div class="card imagen-avance-card">
                                                        <img src="${fotoData}" 
                                                             class="card-img-top imagen-avance" 
                                                             alt="Foto avance ${fotoIndex + 1}"
                                                             style="height: 100px; object-fit: cover; cursor: pointer;"
                                                             onclick="window.open('${fotoData}', '_blank')"
                                                             data-bs-toggle="tooltip" 
                                                             title="Click para ampliar">
                                                    </div>
                                                </div>
                                            `;
                                        }).join('')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }
        
        $('#avances-mecanico').html(html);
        
        // Inicializar tooltips si Bootstrap está disponible
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = $('#avances-mecanico [data-bs-toggle="tooltip"]');
            tooltipTriggerList.each((index, element) => {
                new bootstrap.Tooltip(element);
            });
        }
    }

    obtenerClaseEstado(estado) {
        const clases = {
            'Ingresado': 'status-Ingresado',
            'En espera': 'status-Enespera',
            'Asignado': 'status-Asignado',
            'En progreso': 'status-Enprogreso',
            'Completado': 'status-Completado'
        };
        return clases[estado] || 'status-unknown';
    }

    obtenerTextoEstado(estado) {
        const textos = {
            'Ingresado': 'Ingresado',
            'En espera': 'En espera',
            'Asignado': 'Asignado',
            'En progreso': 'En progreso',
            'Completado': 'Completado'
        };
        return textos[estado] || 'Desconocido';
    }

    mostrarLoading() {
        this.isLoading = true;
        
        const loadingHtml = `
            <div class="datatable-loading-overlay">
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="loading-text mt-2">Buscando vehículos...</p>
                </div>
            </div>
        `;
        
        $('.datatable-loading-overlay').remove();
        $('#results-table_wrapper').css('position', 'relative');
        $('#results-table_wrapper').append(loadingHtml);
        
        $('#search-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Buscando...');
    }

    ocultarLoading() {
        this.isLoading = false;
        $('.datatable-loading-overlay').remove();
        $('#search-btn').prop('disabled', false).html('<i class="fas fa-search me-2"></i>Buscar');
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

        const $container = $('#toast-container');
        if (!$container.length) {
            $('body').append('<div id="toast-container"></div>');
        }
        
        $('#toast-container').html(toastHtml);
        $('.toast').toast('show');
    }
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    window.consulta = new ConsultaVehiculos();
});