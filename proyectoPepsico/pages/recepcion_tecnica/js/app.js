class RecepcionTecnicaApp {
    constructor() {
        this.dataTable = null;
        this.isLoading = false;
        this.fotosSeleccionadas = [];
        this.documentosSeleccionados = [];
        this.init();
    }

    init() {
        if (typeof jQuery === 'undefined') {
            console.error('jQuery no está cargado');
            setTimeout(() => this.init(), 100);
            return;
        }

        if (!$('#tabla-ots').length) {
            console.error('No se encontró la tabla tabla-ots');
            return;
        }
        
        this.bindEvents();
        this.inicializarDataTable();
        this.cargarOTs();
    }

    bindEvents() {
        // Filtros
        $('#btn-aplicar-filtros').on('click', () => this.cargarOTs());
        $('#filtro-placa, #filtro-numero-ot, #filtro-estado').on('keypress', (e) => {
            if (e.which === 13) {
                this.cargarOTs();
            }
        });

        // Nueva OT
        $('#btn-nueva-ot').on('click', () => {
            this.limpiarFormularioNuevaOT();
        });

        // Buscar vehículo para OT
        $('#btn-buscar-placa-ot').on('click', () => this.buscarVehiculoParaOT());
        $('#buscador-placa-ot').on('keypress', (e) => {
            if (e.which === 13) {
                this.buscarVehiculoParaOT();
            }
        });

        // Preview de fotos
        $('#fotos-vehiculo').on('change', (e) => this.mostrarPreviewFotos(e.target.files));
        
        // Preview de documentos
        $('#documentos-tecnicos').on('change', (e) => this.mostrarPreviewDocumentos(e.target.files));

        // Guardar nueva OT
        $('#form-nueva-ot').on('submit', (e) => {
            e.preventDefault();
            this.guardarNuevaOT();
        });

        // Ver detalles de OT
        $(document).on('click', '.btn-ver-ot', (e) => {
            const otId = $(e.currentTarget).data('id');
            this.mostrarDetallesOT(otId);
        });

        // Limpiar formulario al cerrar modal
        $('#modalNuevaOT').on('hidden.bs.modal', () => {
            this.limpiarFormularioNuevaOT();
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#tabla-ots').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles en la tabla",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros en total)",
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
                    "sortAscending": ": activar para ordenar la columna de forma ascendente",
                    "sortDescending": ": activar para ordenar la columna de forma descendente"
                }
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            dom: '<"table-header"lf>rt<"table-footer"ip>',
            processing: true,
            serverSide: false,
            columns: [
                { 
                    data: 'NumeroOT',
                    render: (data) => `<span class="badge bg-info">${data}</span>`
                },
                { data: 'Placa' },
                { 
                    data: null,
                    render: (data) => `${data.Marca} ${data.Modelo}`.trim()
                },
                { 
                    data: 'FechaCreacion',
                    render: (data) => {
                        if (!data) return '-';
                        const fecha = new Date(data);
                        return fecha.toLocaleDateString('es-ES') + ' ' + fecha.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
                    }
                },
                { 
                    data: 'EstadoOT',
                    render: (data) => {
                        const estado = data || 'Pendiente';
                        const clases = {
                            'Pendiente': 'pendiente',
                            'En Proceso': 'en-proceso',
                            'Completada': 'completada',
                            'Cancelada': 'cancelada'
                        };
                        const clase = clases[estado] || 'pendiente';
                        return `<span class="badge badge-ot ${clase}">${estado}</span>`;
                    }
                },
                { data: 'TipoTrabajo' },
                { 
                    data: 'TotalFotos',
                    render: (data) => {
                        const total = data || 0;
                        return total > 0 ? `<span class="badge bg-info"><i class="fas fa-image"></i> ${total}</span>` : '-';
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data) => {
                        return `
                            <button class="btn btn-sm btn-info btn-ver-ot" 
                                    data-id="${data.OTID}" 
                                    title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                        `;
                    }
                }
            ],
            order: [[3, 'desc']],
            responsive: true,
            initComplete: () => {
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            }
        });
    }

    obtenerFiltros() {
        return {
            placa: $('#filtro-placa').val().trim(),
            numero_ot: $('#filtro-numero-ot').val().trim().toUpperCase(), // Normalizar a mayúsculas
            estado: $('#filtro-estado').val()
        };
    }

    cargarOTs() {
        if (this.isLoading) return;

        this.isLoading = true;
        const filtros = this.obtenerFiltros();

        $.ajax({
            url: '../app/model/recepcion_tecnica/scripts/s_recepcion_tecnica.php',
            type: 'POST',
            data: {
                accion: 'obtener_ots',
                ...filtros
            },
            dataType: 'json',
            success: (response) => {
                this.isLoading = false;
                if (response.status === 'success') {
                    this.dataTable.clear();
                    if (response.data && response.data.length > 0) {
                        this.dataTable.rows.add(response.data);
                    }
                    this.dataTable.draw();
                } else {
                    this.mostrarNotificacion('Error al cargar OTs: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                this.isLoading = false;
                console.error('Error al cargar OTs:', error);
                this.mostrarNotificacion('Error al cargar OTs. Por favor, intente nuevamente.', 'error');
            }
        });
    }

    buscarVehiculoParaOT() {
        const placa = $('#buscador-placa-ot').val().trim().toUpperCase();
        
        if (!placa) {
            this.mostrarNotificacion('Por favor, ingrese una placa', 'error');
            return;
        }

        // Mostrar indicador de carga
        $('#info-vehiculo-ot').html(
            '<i class="fas fa-spinner fa-spin"></i> Buscando vehículo...'
        ).removeClass('alert-info alert-warning alert-danger').addClass('alert-info').show();
        $('#vehiculo-id-ot').val('');

        // Calcular la ruta base dinámicamente
        const currentPath = window.location.pathname;
        let baseUrl = '../app/model/ingreso_vehiculos/scripts/s_ingreso_vehiculos.php';
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            baseUrl = basePath + '/app/model/ingreso_vehiculos/scripts/s_ingreso_vehiculos.php';
        }

        console.log('Buscando vehículo con placa:', placa, 'URL:', baseUrl);

        $.ajax({
            url: baseUrl,
            type: 'POST',
            data: {
                action: 'buscar_por_placa',
                placa: placa
            },
            dataType: 'json',
            success: (response) => {
                console.log('Respuesta del servidor:', response);
                if (response.success && response.data) {
                    const vehiculo = response.data;
                    const vehiculoId = vehiculo.ID || vehiculo.id;
                    
                    console.log('Vehículo encontrado - ID:', vehiculoId, 'Datos completos:', vehiculo);
                    
                    if (!vehiculoId) {
                        $('#info-vehiculo-ot').html(
                            '<i class="fas fa-exclamation-triangle"></i> Error: El vehículo no tiene un ID válido.'
                        ).removeClass('alert-info').addClass('alert-danger').show();
                        $('#vehiculo-id-ot').val('');
                        return;
                    }
                    
                    $('#vehiculo-id-ot').val(vehiculoId);
                    console.log('vehiculo-id-ot establecido a:', $('#vehiculo-id-ot').val());
                    
                    $('#info-vehiculo-ot').html(`
                        <strong><i class="fas fa-check-circle"></i> Vehículo encontrado:</strong><br>
                        ${vehiculo.Marca || 'N/A'} ${vehiculo.Modelo || 'N/A'} - ${vehiculo.Placa}<br>
                        <small>Conductor: ${vehiculo.ConductorNombre || 'N/A'}</small>
                    `).removeClass('alert-warning alert-danger').addClass('alert-info').show();
                } else {
                    $('#info-vehiculo-ot').html(
                        `<i class="fas fa-exclamation-triangle"></i> ${response.message || 'Vehículo no encontrado. Verifique que la placa esté correcta y que el vehículo haya ingresado al taller con estado "Ingresado".'}`
                    ).removeClass('alert-info').addClass('alert-warning').show();
                    $('#vehiculo-id-ot').val('');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al buscar vehículo:', {xhr, status, error, responseText: xhr.responseText, url: baseUrl});
                let mensaje = 'Error al buscar vehículo.';
                try {
                    if (xhr.responseText) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            mensaje = response.message;
                        }
                    }
                } catch (e) {
                    if (xhr.status === 404) {
                        mensaje = 'No se encontró el servicio de búsqueda. Verifique la conexión.';
                    } else if (xhr.status === 500) {
                        mensaje = 'Error interno del servidor. Intente nuevamente.';
                    } else if (xhr.status === 0) {
                        mensaje = 'Error de conexión. Verifique su conexión a internet.';
                    }
                }
                $('#info-vehiculo-ot').html(
                    `<i class="fas fa-exclamation-triangle"></i> ${mensaje}`
                ).removeClass('alert-info alert-warning').addClass('alert-danger').show();
                $('#vehiculo-id-ot').val('');
            }
        });
    }

    mostrarPreviewFotos(files) {
        const preview = $('#preview-fotos');
        preview.empty();
        this.fotosSeleccionadas = [];

        if (!files || files.length === 0) return;

        Array.from(files).forEach((file, index) => {
            if (!file.type.startsWith('image/')) {
                this.mostrarNotificacion(`El archivo ${file.name} no es una imagen`, 'error');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const div = $(`
                    <div class="col-md-3 preview-imagen">
                        <img src="${e.target.result}" alt="${file.name}">
                        <button type="button" class="btn-eliminar-foto" data-index="${index}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `);
                
                div.find('.btn-eliminar-foto').on('click', () => {
                    this.eliminarFoto(index);
                });
                
                preview.append(div);
                
                this.fotosSeleccionadas.push({
                    nombre: file.name,
                    tipo: file.type,
                    data: e.target.result
                });
            };
            reader.readAsDataURL(file);
        });
    }

    eliminarFoto(index) {
        this.fotosSeleccionadas.splice(index, 1);
        this.actualizarPreviewFotos();
    }

    actualizarPreviewFotos() {
        $('#preview-fotos').empty();
        this.fotosSeleccionadas.forEach((foto, index) => {
            const div = $(`
                <div class="col-md-3 preview-imagen">
                    <img src="${foto.data}" alt="${foto.nombre}">
                    <button type="button" class="btn-eliminar-foto" data-index="${index}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `);
            
            div.find('.btn-eliminar-foto').on('click', () => {
                this.eliminarFoto(index);
            });
            
            $('#preview-fotos').append(div);
        });
    }


    guardarNuevaOT() {
        // Prevenir doble envío
        if (this.isLoading) {
            return;
        }

        const vehiculoId = $('#vehiculo-id-ot').val();
        
        console.log('guardarNuevaOT - vehiculoId:', vehiculoId);
        
        if (!vehiculoId || vehiculoId === '' || vehiculoId === '0') {
            this.mostrarNotificacion('Por favor, busque y seleccione un vehículo', 'error');
            $('#info-vehiculo-ot').html(
                '<i class="fas fa-exclamation-triangle"></i> Por favor, busque y seleccione un vehículo antes de crear la orden de trabajo.'
            ).removeClass('alert-info').addClass('alert-warning').show();
            return;
        }

        this.isLoading = true;
        const btnSubmit = $('#form-nueva-ot').find('button[type="submit"]');
        const btnTextOriginal = btnSubmit.html();
        btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        // Obtener valores del formulario
        const tipoTrabajo = String($('#tipo-trabajo').val() || '');
        const descripcionTrabajo = String($('#descripcion-trabajo').val() || '');
        const documentosValidados = $('#documentos-validados').is(':checked') ? 1 : 0;

        const datos = {
            accion: 'crear_ot',
            vehiculo_id: vehiculoId,
            tipo_trabajo: tipoTrabajo,
            descripcion_trabajo: descripcionTrabajo,
            fotos: JSON.stringify(this.fotosSeleccionadas),
            documentos: JSON.stringify(this.documentosSeleccionados),
            documentos_validados: documentosValidados
        };

        console.log('guardarNuevaOT - Datos a enviar:', datos);

        $.ajax({
            url: '../app/model/recepcion_tecnica/scripts/s_recepcion_tecnica.php',
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                this.isLoading = false;
                btnSubmit.prop('disabled', false).html(btnTextOriginal);
                
                console.log('guardarNuevaOT - Respuesta del servidor:', response);
                
                if (response.status === 'success') {
                    this.mostrarNotificacion('Orden de Trabajo creada correctamente', 'success');
                    $('#modalNuevaOT').modal('hide');
                    this.limpiarFormularioNuevaOT();
                    this.cargarOTs();
                } else {
                    this.mostrarNotificacion('Error: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                this.isLoading = false;
                btnSubmit.prop('disabled', false).html(btnTextOriginal);
                
                console.error('Error al guardar OT:', {xhr, status, error, responseText: xhr.responseText});
                
                let mensaje = 'Error al guardar OT. Por favor, intente nuevamente.';
                try {
                    if (xhr.responseText) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            mensaje = response.message;
                        }
                    }
                } catch (e) {
                    // Mantener mensaje por defecto
                }
                
                this.mostrarNotificacion(mensaje, 'error');
            }
        });
    }

    mostrarDetallesOT(otId) {
        $.ajax({
            url: '../app/model/recepcion_tecnica/scripts/s_recepcion_tecnica.php',
            type: 'POST',
            data: {
                accion: 'obtener_ot',
                ot_id: otId
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    this.renderizarDetallesOT(response.data);
                    $('#modalVerOT').modal('show');
                } else {
                    this.mostrarNotificacion('Error al cargar detalles de OT', 'error');
                }
            },
            error: () => {
                this.mostrarNotificacion('Error al cargar detalles de OT', 'error');
            }
        });
    }

    renderizarDetallesOT(ot) {
        const contenido = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Información de la OT</h6>
                    <table class="table table-bordered">
                        <tr><th>Número OT:</th><td>${ot.NumeroOT}</td></tr>
                        <tr><th>Estado:</th><td><span class="badge badge-ot ${ot.Estado.toLowerCase().replace(' ', '-')}">${ot.Estado}</span></td></tr>
                        <tr><th>Tipo de Trabajo:</th><td>${ot.TipoTrabajo || '-'}</td></tr>
                        <tr><th>Fecha Creación:</th><td>${new Date(ot.FechaCreacion).toLocaleString('es-ES')}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Información del Vehículo</h6>
                    <table class="table table-bordered">
                        <tr><th>Placa:</th><td>${ot.Placa}</td></tr>
                        <tr><th>Vehículo:</th><td>${ot.Marca} ${ot.Modelo}</td></tr>
                        <tr><th>Conductor:</th><td>${ot.ConductorNombre || '-'}</td></tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <h6>Descripción del Trabajo</h6>
                    <p>${ot.DescripcionTrabajo || '-'}</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <h6>Fotos</h6>
                    ${ot.Fotos && ot.Fotos.length > 0 ? `
                        <div class="row">
                            ${ot.Fotos.map(foto => `
                                <div class="col-md-4 mb-2">
                                    <img src="${foto.data || ''}" class="img-thumbnail" style="max-width: 100%; height: auto;">
                                </div>
                            `).join('')}
                        </div>
                    ` : '<p class="text-muted">No hay fotos</p>'}
                </div>
            </div>
        `;
        
        $('#contenido-ot').html(contenido);
    }

    mostrarPreviewDocumentos(files) {
        const preview = $('#preview-documentos');
        preview.empty();
        this.documentosSeleccionados = [];

        if (!files || files.length === 0) return;

        Array.from(files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const icono = this.obtenerIconoDocumento(file.type);
                const div = $(`
                    <div class="col-md-4 mb-2">
                        <div class="card">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <i class="${icono} fa-2x me-2"></i>
                                    <div class="flex-grow-1">
                                        <small class="d-block text-truncate" style="max-width: 150px;" title="${file.name}">${file.name}</small>
                                        <small class="text-muted">${this.formatearTamaño(file.size)}</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger btn-eliminar-doc" data-index="${index}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                
                div.find('.btn-eliminar-doc').on('click', () => {
                    this.eliminarDocumento(index);
                });
                
                preview.append(div);
                
                this.documentosSeleccionados.push({
                    nombre: file.name,
                    tipo: file.type,
                    tamaño: file.size,
                    data: e.target.result
                });
            };
            reader.readAsDataURL(file);
        });
    }

    obtenerIconoDocumento(tipo) {
        if (tipo.includes('pdf')) return 'fas fa-file-pdf text-danger';
        if (tipo.includes('word') || tipo.includes('document')) return 'fas fa-file-word text-primary';
        if (tipo.includes('excel') || tipo.includes('spreadsheet')) return 'fas fa-file-excel text-success';
        if (tipo.includes('image')) return 'fas fa-file-image text-info';
        return 'fas fa-file text-secondary';
    }

    formatearTamaño(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    eliminarDocumento(index) {
        this.documentosSeleccionados.splice(index, 1);
        this.actualizarPreviewDocumentos();
    }

    actualizarPreviewDocumentos() {
        $('#preview-documentos').empty();
        this.documentosSeleccionados.forEach((doc, index) => {
            const icono = this.obtenerIconoDocumento(doc.tipo);
            const div = $(`
                <div class="col-md-4 mb-2">
                    <div class="card">
                        <div class="card-body p-2">
                            <div class="d-flex align-items-center">
                                <i class="${icono} fa-2x me-2"></i>
                                <div class="flex-grow-1">
                                    <small class="d-block text-truncate" style="max-width: 150px;" title="${doc.nombre}">${doc.nombre}</small>
                                    <small class="text-muted">${this.formatearTamaño(doc.tamaño)}</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger btn-eliminar-doc" data-index="${index}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            div.find('.btn-eliminar-doc').on('click', () => {
                this.eliminarDocumento(index);
            });
            
            $('#preview-documentos').append(div);
        });
    }

    limpiarFormularioNuevaOT() {
        // Limpiar campos del formulario
        $('#tipo-trabajo').val('');
        $('#descripcion-trabajo').val('');
        $('#buscador-placa-ot').val('');
        $('#vehiculo-id-ot').val('');
        $('#info-vehiculo-ot').hide().removeClass('alert-warning alert-danger alert-info').addClass('alert-info').html('');
        $('#documentos-validados').prop('checked', false);
        this.fotosSeleccionadas = [];
        this.documentosSeleccionados = [];
        $('#preview-fotos').empty();
        $('#preview-documentos').empty();
        $('#fotos-vehiculo').val('');
        $('#documentos-tecnicos').val('');
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: tipo === 'error' ? 'error' : tipo === 'success' ? 'success' : 'info',
                title: tipo === 'error' ? 'Error' : tipo === 'success' ? 'Éxito' : 'Información',
                text: mensaje,
                timer: tipo === 'success' ? 3000 : 5000,
                showConfirmButton: true
            });
        } else {
            alert(mensaje);
        }
    }
}

// Inicializar cuando el DOM esté listo
function initRecepcionTecnica() {
    if (typeof jQuery !== 'undefined') {
        $(document).ready(function() {
            new RecepcionTecnicaApp();
        });
    } else {
        setTimeout(initRecepcionTecnica, 100);
    }
}

initRecepcionTecnica();

