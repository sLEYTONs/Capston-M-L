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
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
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
                                    <small class="driver-id">${data.ConductorCedula}</small>
                                </div>
                            `;
                        }
                    },
                    { data: 'EmpresaNombre',
                        render: (data) => {
                            return `<span class="company-badge">${data}</span>`;
                        }
                    },
                    { 
                        data: 'FechaIngresoFormateada',
                        className: 'fecha-column'
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
                            return `
                                <div class="btn-group">
                                    <button class="btn-action btn-view" data-id="${data.ID}" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action btn-edit" data-id="${data.ID}" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[4, 'desc']],
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

        $(document).on('click', '.btn-edit', (e) => {
            const id = $(e.currentTarget).data('id');
            this.editarVehiculo(id);
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
        console.log('Editar vehículo:', id);
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
                                    <p><strong>Color:</strong> ${vehiculo.Color}</p>
                                    <p><strong>Año:</strong> ${vehiculo.Anio || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Información del Conductor</h6>
                                    <p><strong>Nombre:</strong> ${vehiculo.ConductorNombre}</p>
                                    <p><strong>Cédula:</strong> ${vehiculo.ConductorCedula}</p>
                                    <p><strong>Teléfono:</strong> ${vehiculo.ConductorTelefono}</p>
                                    <p><strong>Licencia:</strong> ${vehiculo.Licencia}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Información de la Visita</h6>
                                    <p><strong>Empresa:</strong> ${vehiculo.EmpresaNombre}</p>
                                    <p><strong>Propósito:</strong> ${vehiculo.Proposito}</p>
                                    <p><strong>Área:</strong> ${vehiculo.Area}</p>
                                    <p><strong>Persona de Contacto:</strong> ${vehiculo.PersonaContacto}</p>
                                    <p><strong>Fecha Ingreso:</strong> ${vehiculo.FechaIngresoFormateada}</p>
                                    ${vehiculo.Observaciones ? `<p><strong>Observaciones:</strong> ${vehiculo.Observaciones}</p>` : ''}
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
    
    editarVehiculo(id) {
        this.cargarDatosParaEdicion(id);
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
        $('#edit-color').val(vehiculo.Color);
        $('#edit-anio').val(vehiculo.Anio);
        $('#edit-conductor-nombre').val(vehiculo.ConductorNombre);
        $('#edit-conductor-cedula').val(vehiculo.ConductorCedula);
        $('#edit-conductor-telefono').val(vehiculo.ConductorTelefono);
        $('#edit-licencia').val(vehiculo.Licencia);
        $('#edit-empresa-codigo').val(vehiculo.EmpresaCodigo);
        $('#edit-empresa-nombre').val(vehiculo.EmpresaNombre);
        $('#edit-proposito').val(vehiculo.Proposito);
        $('#edit-area').val(vehiculo.Area);
        $('#edit-persona-contacto').val(vehiculo.PersonaContacto);
        $('#edit-observaciones').val(vehiculo.Observaciones);
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
            'ConductorNombre', 'ConductorCedula', 
            'EmpresaCodigo', 'EmpresaNombre', 'Proposito'
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







    obtenerClaseEstado(estado) {
        const clases = {
            'active': 'status-active',
            'inactive': 'status-inactive',
            'retired': 'status-retired'
        };
        return clases[estado] || 'status-unknown';
    }

    obtenerTextoEstado(estado) {
        const textos = {
            'active': 'Activo',
            'inactive': 'Inactivo',
            'retired': 'Retirado'
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