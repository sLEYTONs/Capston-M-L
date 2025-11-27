class TareasMecanico {
    constructor() {
        this.dataTable = null;
        this.isLoading = false;
        this.fotosSeleccionadas = [];
        this.basePath = this.getBasePath();
        this.init();
    }

    getBasePath() {
        // Obtener la ruta base del proyecto desde la URL actual
        const currentPath = window.location.pathname;
        // Si estamos en pages/tareas/, obtener la parte antes de /pages/
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath; // Retorna /Capston-M-L/proyectoPepsico
        }
        // Fallback: asumir que estamos en la raíz
        return '';
    }

    construirRutaImagen(ruta) {
        // Si ya es una URL completa, retornarla tal cual
        if (ruta.startsWith('http://') || ruta.startsWith('https://')) {
            return ruta;
        }
        
        // Limpiar la ruta
        ruta = ruta.trim();
        
        // Remover cualquier prefijo de dominio o ruta absoluta incorrecta
        ruta = ruta.replace(/^https?:\/\/[^\/]+\//, '');
        
        // Si la ruta ya empieza con /, puede ser una ruta absoluta - verificar si tiene el basePath
        if (ruta.startsWith('/')) {
            // Si ya contiene el basePath, retornarla tal cual
            if (this.basePath && ruta.startsWith(this.basePath)) {
                return ruta;
            }
            // Si no tiene basePath pero empieza con /, puede ser que falte el basePath
            // Remover el / inicial y continuar con la lógica normal
            ruta = ruta.substring(1);
        }
        
        // Si la ruta contiene uploads/, construir la ruta absoluta desde la raíz del proyecto
        if (ruta.includes('uploads/')) {
            // Extraer solo la parte de uploads/...
            const match = ruta.match(/(uploads\/.+)$/);
            if (match) {
                // Construir ruta absoluta: /proyectoPepsico/uploads/fotos/...
                const separator = this.basePath ? '/' : '';
                return this.basePath + separator + match[1];
            }
        }
        
        // Si solo tiene el nombre del archivo, construir la ruta completa
        if (!ruta.includes('/')) {
            const separator = this.basePath ? '/' : '';
            return this.basePath + separator + 'uploads/fotos/' + ruta;
        }
        
        // Si tiene alguna estructura, intentar construir la ruta
        const separator = this.basePath ? '/' : '';
        return this.basePath + separator + ruta;
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

        $(document).on('click', '.btn-pausar-tarea', (e) => {
            const asignacionId = $(e.currentTarget).data('id');
            const placa = $(e.currentTarget).data('placa');
            this.mostrarModalPausar(asignacionId, placa);
        });

        $('#guardar-avance').on('click', () => {
            this.guardarAvance();
        });

        $('#confirmar-pausa').on('click', () => {
            this.confirmarPausa();
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
                scrollX: true,
                autoWidth: false,
                columnDefs: [
                    { width: "200px", targets: 0 }, // Vehículo
                    { width: "100px", targets: 1 }, // Placa
                    { width: "150px", targets: 2 }, // Fecha
                    { width: "120px", targets: 3 }, // Estado
                    { width: "300px", targets: 4 }, // Observaciones
                    { width: "250px", targets: 5 }, // Último Avance
                    { width: "280px", targets: 6 }  // Acciones
                ],
                columns: [
                    { 
                        data: null,
                        render: (data) => {
                            return `
                                <div class="vehicle-info">
                                    <strong>${data.Marca} ${data.Modelo}</strong>
                                    <div class="vehicle-details">
                                        <small class="text-muted">${data.TipoVehiculo}</small>
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
                            const estadoClass = 'status-' + data.replace(/\s+/g, '');
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
                            
                            if (data.Estado !== 'Completado' && data.Estado !== 'En Pausa') {
                                botones += `
                                    <button class="btn btn-primary btn-sm btn-registrar-avance" 
                                            data-id="${data.AsignacionID}" 
                                            title="Registrar Avance">
                                        <i class="fas fa-clipboard-check me-1"></i>Avance
                                    </button>
                                    <button class="btn btn-warning btn-sm btn-pausar-tarea" 
                                            data-id="${data.AsignacionID}" 
                                            data-placa="${data.Placa}"
                                            title="Pausar Tarea">
                                        <i class="fas fa-pause me-1"></i>Pausar
                                    </button>
                                `;
                            } else if (data.Estado === 'En Pausa') {
                                // Cuando está en pausa, solo mostrar el badge, sin botones de acción
                                botones += `
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-pause-circle me-1"></i>En Pausa
                                    </span>
                                    <a href="gestion_pausas_repuestos.php" class="btn btn-sm btn-info ms-2" title="Ver en Gestión de Pausas">
                                        <i class="fas fa-external-link-alt me-1"></i>Gestionar
                                    </a>
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
        const enProgreso = data.filter(item => item.Estado === 'En progreso' || item.Estado === 'En Proceso').length;
        const pendientes = data.filter(item => item.Estado === 'Asignado').length;
        const completados = data.filter(item => item.Estado === 'Completado').length;
        const enPausa = data.filter(item => item.Estado === 'En Pausa').length;

        $('#total-asignados').text(total);
        $('#en-progreso').text(enProgreso);
        $('#pendientes').text(pendientes);
        $('#completados').text(completados);
        
        // Actualizar contador de pausas si existe
        if ($('#en-pausa').length) {
            $('#en-pausa').text(enPausa);
        }
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
                    $('#info-conductor').text(data.ConductorNombre);
                    $('#info-estado').text(data.Estado);
                    $('#info-anio').text(data.Anio || 'No especificado');
                    
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
                                    <td><strong>Año:</strong></td>
                                    <td>${data.Anio || 'No especificado'}</td>
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
                                    <td>${data.Proposito || 'No especificado'}</td>
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
                    console.log('Respuesta del servidor:', response.data);
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
                // Construir la ruta absoluta correcta desde la raíz del proyecto
                let rutaFoto = foto.foto || '';
                rutaFoto = this.construirRutaImagen(rutaFoto);
                
                // Escapar correctamente para el atributo HTML
                const rutaEscapada = rutaFoto.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                
                html += `
                    <div class="col-md-4 col-sm-6">
                        <div class="card foto-card">
                            <img src="${rutaEscapada}" class="card-img-top foto-thumbnail" 
                                 style="height: 200px; object-fit: cover; cursor: pointer;"
                                 data-foto-ruta="${rutaEscapada}"
                                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27200%27 height=%27200%27%3E%3Crect fill=%27%23ddd%27 width=%27200%27 height=%27200%27/%3E%3Ctext fill=%27%23999%27 font-family=%27sans-serif%27 font-size=%2714%27 x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27 dy=%27.3em%27%3EImagen no encontrada%3C/text%3E%3C/svg%3E'">
                            <div class="card-body">
                                <small class="text-muted">
                                    <strong>${foto.angulo || 'General'}</strong><br>
                                    ${foto.fecha || 'Fecha no disponible'}
                                </small>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }
        
        $('#galeria-fotos-vehiculo').html(html);
        
        // Agregar event listener para las imágenes usando delegación de eventos
        $('#galeria-fotos-vehiculo').off('click', '.foto-thumbnail').on('click', '.foto-thumbnail', (e) => {
            const ruta = $(e.currentTarget).attr('data-foto-ruta');
            if (ruta) {
                this.mostrarFotoModal(ruta);
            }
        });
        
        const fotosModal = new bootstrap.Modal(document.getElementById('fotosVehiculoModal'));
        fotosModal.show();
    }

    mostrarFotoModal(src) {
        if (!src) {
            console.error('No se proporcionó ruta de foto');
            return;
        }
        
        // Construir la ruta absoluta correcta
        src = this.construirRutaImagen(src);
        
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

        const url = (this.basePath || '') + '/app/model/tareas/scripts/s_guardar_avances.php';
        $.ajax({
            url: url,
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

    mostrarModalPausar(asignacionId, placa) {
        $('#pausa-asignacion-id').val(asignacionId);
        $('#modal-placa-pausa').text(placa);
        $('#pausa-motivo').val('');
        $('#pausa-motivo-custom').val('');
        $('#pausa-motivo-custom-group').hide();
        
        const pausaModal = new bootstrap.Modal(document.getElementById('pausaModal'));
        pausaModal.show();
    }

    confirmarPausa() {
        const asignacionId = $('#pausa-asignacion-id').val();
        const motivoSeleccionado = $('#pausa-motivo').val();
        const motivoCustom = $('#pausa-motivo-custom').val().trim();
        
        let motivoPausa = motivoSeleccionado;
        
        if (motivoSeleccionado === 'Otro') {
            if (!motivoCustom) {
                this.mostrarError('Por favor, especifica el motivo de la pausa');
                return;
            }
            motivoPausa = motivoCustom;
        } else if (!motivoSeleccionado) {
            this.mostrarError('Por favor, selecciona un motivo para pausar la tarea');
            return;
        }

        $('#confirmar-pausa').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Pausando...');

        $.ajax({
            url: '../app/model/gestion_pausas_repuestos/scripts/s_gestion_pausas_repuestos.php',
            type: 'POST',
            data: {
                action: 'pausarTarea',
                asignacion_id: asignacionId,
                motivo_pausa: motivoPausa
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarToast('Éxito', 'Tarea pausada correctamente', 'success');
                    this.cerrarModalPausa();
                    this.cargarTareas(); // Recargar la tabla
                    
                    // Notificar a otras pestañas/páginas que hay un cambio
                    if (typeof Storage !== 'undefined') {
                        localStorage.setItem('tareaPausada', Date.now().toString());
                        localStorage.removeItem('tareaPausada'); // Limpiar inmediatamente
                    }
                    
                    // Disparar evento personalizado
                    window.dispatchEvent(new CustomEvent('tareaPausada', { 
                        detail: { asignacionId: asignacionId } 
                    }));
                } else {
                    this.mostrarError('Error al pausar: ' + response.message);
                    $('#confirmar-pausa').prop('disabled', false).html('<i class="fas fa-pause me-2"></i>Confirmar Pausa');
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
                $('#confirmar-pausa').prop('disabled', false).html('<i class="fas fa-pause me-2"></i>Confirmar Pausa');
            }
        });
    }

    cerrarModalPausa() {
        const pausaModal = bootstrap.Modal.getInstance(document.getElementById('pausaModal'));
        pausaModal.hide();
        
        // Limpiar formulario
        $('#pausa-form')[0].reset();
        $('#pausa-motivo-custom-group').hide();
        $('#confirmar-pausa').prop('disabled', false).html('<i class="fas fa-pause me-2"></i>Confirmar Pausa');
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
    
    // Manejar cambio en el select de motivo de pausa
    $('#pausa-motivo').on('change', function() {
        if ($(this).val() === 'Otro') {
            $('#pausa-motivo-custom-group').show();
            $('#pausa-motivo-custom').prop('required', true);
        } else {
            $('#pausa-motivo-custom-group').hide();
            $('#pausa-motivo-custom').prop('required', false).val('');
        }
    });
    
    // Reinicializar dropdowns de Bootstrap después de cargar DataTables
    setTimeout(() => {
        const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
        const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl)
        });
    }, 1000);
});