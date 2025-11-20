class TareasMecanico {
    constructor() {
        this.dataTable = null;
        this.isLoading = false;
        this.fotosSeleccionadas = [];
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

        $(document).on('click', '.btn-ver-info', (e) => {
            const vehiculoId = $(e.currentTarget).data('id');
            this.mostrarInfoVehiculo(vehiculoId);
        });

        $(document).on('click', '.btn-ver-fotos', (e) => {
            const vehiculoId = $(e.currentTarget).data('id');
            this.mostrarFotosVehiculo(vehiculoId);
        });

        $('#guardar-avance').on('click', () => {
            this.guardarAvance();
        });

        // Manejar selección de fotos
        $('#avance-fotos').on('change', (e) => {
            this.manejarSeleccionFotos(e);
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
                                <button class="btn btn-info btn-sm btn-ver-info" 
                                        data-id="${data.VehiculoID}" 
                                        title="Ver Información">
                                    <i class="fas fa-info-circle me-1"></i>
                                </button>
                                <button class="btn btn-warning btn-sm btn-ver-fotos" 
                                        data-id="${data.VehiculoID}" 
                                        title="Ver Fotos">
                                    <i class="fas fa-images me-1"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm btn-ver-historial" 
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

        console.log("Cargando tareas...");

        $.ajax({
            url: '../app/model/tareas/scripts/s_tareas.php',
            type: 'GET',
            dataType: 'json',
            success: (response) => {
                console.log("Respuesta del servidor:", response);
                if (response.status === 'success') {
                    this.cargarDataEnTabla(response.data);
                    this.actualizarResumen(response.data);
                    if (response.debug) {
                        console.log("Debug info:", response.debug);
                    }
                } else {
                    console.error("Error del servidor:", response);
                    this.mostrarError(response.message || 'Error desconocido');
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
                    $('#info-anio').text(data.Anio || 'No especificado');
                    $('#info-combustible').text(data.Combustible || 'No especificado');
                    
                    // Limpiar formulario
                    $('#avance-descripcion').val('');
                    $('#avance-estado').val('En progreso');
                    $('#avance-fotos').val('');
                    $('#fotos-preview-container').hide();
                    $('#fotos-preview').empty();
                    this.fotosSeleccionadas = [];
                    
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

    manejarSeleccionFotos(event) {
        const files = event.target.files;
        this.fotosSeleccionadas = Array.from(files);
        
        const previewContainer = $('#fotos-preview-container');
        const preview = $('#fotos-preview');
        
        preview.empty();
        
        if (this.fotosSeleccionadas.length > 0) {
            previewContainer.show();
            
            this.fotosSeleccionadas.forEach((file, index) => {
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    const previewHtml = `
                        <div class="col-3">
                            <div class="foto-preview-card">
                                <img src="${e.target.result}" class="img-thumbnail" alt="Preview">
                                <button type="button" class="btn-remove-foto" data-index="${index}">
                                    <i class="fas fa-times"></i>
                                </button>
                                <div class="foto-info">
                                    <small>${file.name}</small>
                                </div>
                            </div>
                        </div>
                    `;
                    preview.append(previewHtml);
                };
                
                reader.readAsDataURL(file);
            });
            
            // Agregar evento para eliminar fotos
            $(document).on('click', '.btn-remove-foto', (e) => {
                const index = $(e.currentTarget).data('index');
                this.eliminarFoto(index);
            });
        } else {
            previewContainer.hide();
        }
    }

    eliminarFoto(index) {
        this.fotosSeleccionadas.splice(index, 1);
        
        // Actualizar input file
        const dt = new DataTransfer();
        this.fotosSeleccionadas.forEach(file => dt.items.add(file));
        $('#avance-fotos')[0].files = dt.files;
        
        // Volver a generar preview
        this.manejarSeleccionFotos({ target: $('#avance-fotos')[0] });
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
                // Mostrar fotos si existen
                let fotosHtml = '';
                if (avance.Fotos && avance.Fotos.length > 0) {
                    fotosHtml = `
                        <div class="mt-2">
                            <strong>Fotos:</strong>
                            <div class="row g-2 mt-1">
                    `;
                    avance.Fotos.forEach(foto => {
                        fotosHtml += `
                            <div class="col-3">
                                <img src="${foto.ruta}" class="img-thumbnail" style="width: 100px; height: 80px; object-fit: cover;" 
                                     data-bs-toggle="modal" data-bs-target="#fotoModal" 
                                     onclick="tareas.mostrarFotoModal('${foto.ruta}')">
                            </div>
                        `;
                    });
                    fotosHtml += `</div></div>`;
                }
                
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
                            <p class="mb-2">${avance.Descripcion}</p>
                            ${fotosHtml}
                        </div>
                    </div>
                `;
            });
        }
        
        $('#historial-avances').html(html);
        
        const historialModal = new bootstrap.Modal(document.getElementById('historialModal'));
        historialModal.show();
    }

    mostrarInfoVehiculo(vehiculoId) {
        $.ajax({
            url: '../app/model/tareas/scripts/s_info_vehiculo.php',
            type: 'POST',
            data: { vehiculo_id: vehiculoId },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarInfoEnModal(response.data);
                } else {
                    this.mostrarError('Error al cargar información: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
            }
        });
    }

    mostrarInfoEnModal(data) {
        $('#modal-placa-info').text(data.Placa);
        
        const html = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-car me-2"></i>Información del Vehículo</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Placa:</strong></td>
                                    <td>${data.Placa}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo:</strong></td>
                                    <td>${data.TipoVehiculo}</td>
                                </tr>
                                <tr>
                                    <td><strong>Marca/Modelo:</strong></td>
                                    <td>${data.Marca} ${data.Modelo}</td>
                                </tr>
                                <tr>
                                    <td><strong>Color:</strong></td>
                                    <td>${data.Color}</td>
                                </tr>
                                <tr>
                                    <td><strong>Año:</strong></td>
                                    <td>${data.Anio || 'No especificado'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Chasis:</strong></td>
                                    <td>${data.Chasis || 'No especificado'}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Información del Conductor</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td>${data.ConductorNombre}</td>
                                </tr>
                                <tr>
                                    <td><strong>Cédula:</strong></td>
                                    <td>${data.ConductorCedula}</td>
                                </tr>
                                <tr>
                                    <td><strong>Teléfono:</strong></td>
                                    <td>${data.ConductorTelefono || 'No registrado'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Licencia:</strong></td>
                                    <td>${data.Licencia || 'No registrada'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Empresa:</strong></td>
                                    <td>${data.EmpresaNombre}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Detalles del Ingreso</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Propósito:</strong></td>
                                    <td>${data.Proposito}</td>
                                </tr>
                                <tr>
                                    <td><strong>Área:</strong></td>
                                    <td>${data.Area}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td><span class="badge bg-${data.EstadoIngreso === 'Bueno' ? 'success' : data.EstadoIngreso === 'Regular' ? 'warning' : 'danger'}">${data.EstadoIngreso}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Combustible:</strong></td>
                                    <td>${data.Combustible}</td>
                                </tr>
                                <tr>
                                    <td><strong>Kilometraje:</strong></td>
                                    <td>${data.Kilometraje || 'No registrado'} km</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Observaciones</h6>
                        </div>
                        <div class="card-body">
                            <p>${data.Observaciones || 'Sin observaciones'}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#info-vehiculo-completa').html(html);
        
        const infoModal = new bootstrap.Modal(document.getElementById('infoVehiculoModal'));
        infoModal.show();
    }

    mostrarFotosVehiculo(vehiculoId) {
        $.ajax({
            url: '../app/model/tareas/scripts/s_fotos_vehiculo.php',
            type: 'POST',
            data: { vehiculo_id: vehiculoId },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarFotosEnModal(response.data);
                } else {
                    this.mostrarError('Error al cargar fotos: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
            }
        });
    }

    mostrarFotosEnModal(data) {
        $('#modal-placa-fotos').text(data.Placa);
        
        let html = '';
        
        if (data.fotos.length === 0) {
            html = '<div class="alert alert-info">No hay fotos registradas para este vehículo.</div>';
        } else {
            html = '<div class="row g-3">';
            data.fotos.forEach((foto, index) => {
                html += `
                    <div class="col-md-4 col-sm-6">
                        <div class="card foto-card">
                            <img src="${foto.foto}" class="card-img-top" 
                                 style="height: 200px; object-fit: cover; cursor: pointer;"
                                 onclick="tareas.mostrarFotoModal('${foto.foto}')">
                            <div class="card-body">
                                <small class="text-muted">
                                    <strong>${foto.angulo}</strong><br>
                                    ${foto.fecha}
                                </small>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }
        
        $('#galeria-fotos-vehiculo').html(html);
        
        const fotosModal = new bootstrap.Modal(document.getElementById('fotosVehiculoModal'));
        fotosModal.show();
    }

    mostrarFotoModal(src) {
        // Crear modal dinámico para foto grande
        let fotoModal = $('#fotoModal');
        if (!fotoModal.length) {
            $('body').append(`
                <div class="modal fade" id="fotoModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-body text-center">
                                <img src="" id="foto-grande" class="img-fluid" style="max-height: 80vh;">
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }
        
        $('#foto-grande').attr('src', src);
        const modal = new bootstrap.Modal(document.getElementById('fotoModal'));
        modal.show();
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

        // Crear FormData para enviar archivos
        const formData = new FormData();
        formData.append('asignacion_id', asignacionId);
        formData.append('descripcion', descripcion);
        formData.append('estado', estado);

        // Agregar fotos al FormData
        this.fotosSeleccionadas.forEach((foto, index) => {
            formData.append('avance_fotos[]', foto);
        });

        $.ajax({
            url: '../app/model/tareas/scripts/s_guardar_avance.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
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
        $('#fotos-preview-container').hide();
        $('#fotos-preview').empty();
        this.fotosSeleccionadas = [];
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
    
    // Reinicializar dropdowns de Bootstrap después de cargar DataTables
    setTimeout(() => {
        const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
        const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl)
        });
    }, 1000);
});