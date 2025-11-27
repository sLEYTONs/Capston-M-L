class ComunicacionFlotaProveedoresApp {
    constructor() {
        this.dataTableFlota = null;
        this.dataTableProveedor = null;
        this.tablaProveedores = null;
        this.proveedorSeleccionado = null;
        this.baseUrlProveedores = this.getBaseUrlProveedores();
        this.init();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/';
        }
        return '../../';
    }

    getBaseUrlProveedores() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/comunicacion_proveedores/scripts/s_comunicacion_proveedores.php';
        }
        return '../../app/model/comunicacion_proveedores/scripts/s_comunicacion_proveedores.php';
    }

    init() {
        if (typeof jQuery === 'undefined') {
            console.error('jQuery no está cargado');
            setTimeout(() => this.init(), 100);
            return;
        }

        this.bindEvents();
        this.inicializarDataTables();
        this.cargarComunicacionesFlota();
        
        // Solo cargar proveedores si el tab existe (no es Chofer)
        // Esperar un momento para asegurar que las tablas estén inicializadas
        setTimeout(() => {
            if ($('#tab-proveedores').length > 0 && $('#tabla-proveedores').length > 0) {
                console.log('Cargando proveedores...');
                this.cargarProveedores();
            }
        }, 500);
    }

    bindEvents() {
        // Filtros Flota
        $('#btn-aplicar-filtros-flota').on('click', () => this.cargarComunicacionesFlota());
        $('#filtro-placa-flota, #filtro-conductor-flota, #filtro-tipo-flota, #filtro-estado-flota').on('keypress', (e) => {
            if (e.which === 13) {
                this.cargarComunicacionesFlota();
            }
        });

        // Gestión de Proveedores
        $('#btn-nuevo-proveedor').on('click', () => {
            this.abrirModalProveedor();
        });

        $('#btn-guardar-proveedor').on('click', () => {
            this.guardarProveedor();
        });

        $('#form-comunicacion-proveedores').on('submit', (e) => {
            e.preventDefault();
            this.enviarComunicacionProveedor();
        });
        
        // Formulario de respuesta
        $('#form-responder-comunicacion').on('submit', (e) => {
            e.preventDefault();
            this.enviarRespuesta();
        });

        // Eventos para botones de acciones en la tabla de proveedores
        $(document).on('click', '.btn-editar-proveedor', (e) => {
            const id = $(e.currentTarget).data('id');
            this.editarProveedor(id);
        });

        $(document).on('click', '.btn-eliminar-proveedor', (e) => {
            const id = $(e.currentTarget).data('id');
            this.eliminarProveedor(id);
        });

        $(document).on('click', '.btn-seleccionar-proveedor', (e) => {
            e.preventDefault();
            const id = $(e.currentTarget).data('id');
            console.log('Botón seleccionar proveedor clickeado, ID:', id);
            if (id) {
                this.seleccionarProveedor(id);
            } else {
                console.error('No se pudo obtener el ID del proveedor');
                this.mostrarNotificacion('Error: No se pudo obtener el ID del proveedor', 'error');
            }
        });

        // Limpiar formulario al cerrar modal de proveedor
        $('#modal-proveedor').on('hidden.bs.modal', () => {
            this.limpiarFormularioProveedor();
        });

        // Filtros Proveedor
        $('#btn-aplicar-filtros-proveedor').on('click', () => this.cargarComunicacionesProveedor());
        $('#filtro-proveedor, #filtro-tipo-proveedor, #filtro-estado-proveedor').on('keypress', (e) => {
            if (e.which === 13) {
                this.cargarComunicacionesProveedor();
            }
        });

        // Formularios
        $('#form-nueva-comunicacion-flota').on('submit', (e) => {
            e.preventDefault();
            this.guardarComunicacionFlota();
        });

        $('#form-nueva-comunicacion-proveedor').on('submit', (e) => {
            e.preventDefault();
            this.guardarComunicacionProveedor();
        });

        // Ver detalles
        $(document).on('click', '.btn-ver-comunicacion', (e) => {
            const id = $(e.currentTarget).data('id');
            const tipo = $(e.currentTarget).data('tipo');
            this.mostrarDetallesComunicacion(id, tipo);
        });

        $(document).on('click', '.btn-ver-comunicacion-proveedor', (e) => {
            const id = $(e.currentTarget).data('id');
            this.mostrarDetallesComunicacionProveedor(id);
        });

        // Limpiar formularios al cerrar modales
        $('#modalNuevaComunicacionFlota').on('hidden.bs.modal', () => {
            $('#form-nueva-comunicacion-flota')[0].reset();
        });

        $('#modalNuevaComunicacionProveedor').on('hidden.bs.modal', () => {
            $('#form-nueva-comunicacion-proveedor')[0].reset();
        });
    }

    inicializarDataTables() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        // DataTable Flota
        this.dataTableFlota = $('#tabla-comunicaciones-flota').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay comunicaciones registradas",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros en total)",
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
                }
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            processing: true,
            serverSide: false,
            columns: [
                { 
                    data: 'FechaCreacion',
                    render: (data) => {
                        if (!data) return '-';
                        const fecha = new Date(data);
                        return fecha.toLocaleDateString('es-ES') + ' ' + fecha.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
                    }
                },
                { data: 'Placa' },
                { data: 'ConductorNombre' },
                { 
                    data: 'Tipo',
                    render: (data) => {
                        const tipos = {
                            'Solicitud': 'info',
                            'Notificación': 'primary',
                            'Consulta': 'secondary',
                            'Urgente': 'danger'
                        };
                        const color = tipos[data] || 'secondary';
                        return `<span class="badge bg-${color}">${data}</span>`;
                    }
                },
                { data: 'Asunto' },
                { 
                    data: 'Estado',
                    render: (data) => {
                        const estados = {
                            'Pendiente': 'warning',
                            'En Proceso': 'info',
                            'Respondida': 'success',
                            'Cerrada': 'secondary'
                        };
                        const color = estados[data] || 'secondary';
                        return `<span class="badge bg-${color}">${data}</span>`;
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data) => {
                        return `
                            <button class="btn btn-sm btn-info btn-ver-comunicacion" data-id="${data.ID}" data-tipo="flota">
                                <i class="fas fa-eye"></i>
                            </button>
                        `;
                    }
                }
            ]
        });

        // DataTable Proveedores (lista de proveedores) - Solo si el tab existe
        if ($('#tabla-proveedores').length > 0) {
            this.tablaProveedores = $('#tabla-proveedores').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay proveedores registrados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros en total)",
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
                }
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[1, 'asc']],
            responsive: true,
            columns: [
                { data: 'ID' },
                { data: 'Nombre' },
                { data: 'Contacto' },
                { 
                    data: 'Email',
                    render: (data) => data ? `<a href="mailto:${data}">${data}</a>` : '-'
                },
                { data: 'Telefono' },
                { data: 'RUT' },
                { data: 'Direccion' },
                { 
                    data: 'Estado',
                    render: (data) => {
                        const color = data === 'Activo' ? 'success' : 'secondary';
                        return `<span class="badge bg-${color}">${data}</span>`;
                    }
                },
                { 
                    data: 'FechaCreacion',
                    render: (data) => {
                        if (!data) return '-';
                        const fecha = new Date(data);
                        return fecha.toLocaleDateString('es-ES');
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data) => {
                        return `
                            <button class="btn btn-sm btn-info btn-seleccionar-proveedor me-1" data-id="${data.ID}" title="Seleccionar">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-warning btn-editar-proveedor me-1" data-id="${data.ID}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-eliminar-proveedor" data-id="${data.ID}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                    }
                }
            ]
            });
        } else {
            this.tablaProveedores = null;
        }

        // DataTable Comunicaciones Proveedor (historial) - Solo si el tab existe
        if ($('#tabla-comunicaciones-proveedor').length > 0) {
            this.dataTableProveedor = $('#tabla-comunicaciones-proveedor').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay comunicaciones registradas. Seleccione un proveedor para ver su historial.",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros en total)",
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
                }
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            processing: true,
            serverSide: false,
            columns: [
                { 
                    data: 'FechaCreacion',
                    title: 'Fecha',
                    render: (data) => {
                        if (!data) return '-';
                        const fecha = new Date(data);
                        return fecha.toLocaleDateString('es-ES') + ' ' + fecha.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
                    }
                },
                { 
                    data: 'ProveedorNombre',
                    title: 'Proveedor',
                    render: (data) => data || '-'
                },
                { 
                    data: 'Tipo',
                    title: 'Tipo',
                    render: (data) => {
                        const tipos = {
                            'solicitud': 'info',
                            'pedido': 'primary',
                            'consulta': 'secondary',
                            'reclamo': 'danger',
                            'seguimiento': 'success',
                            'cotización': 'success'
                        };
                        const tipoLower = (data || '').toLowerCase();
                        const color = tipos[tipoLower] || 'secondary';
                        return `<span class="badge bg-${color}">${data || '-'}</span>`;
                    }
                },
                { 
                    data: 'Asunto',
                    title: 'Asunto'
                },
                { 
                    data: 'Estado',
                    title: 'Estado',
                    render: (data) => {
                        const estados = {
                            'Pendiente': 'warning',
                            'En Proceso': 'info',
                            'Respondida': 'success',
                            'Cerrada': 'secondary'
                        };
                        const color = estados[data] || 'secondary';
                        return `<span class="badge bg-${color}">${data || '-'}</span>`;
                    }
                },
                { 
                    data: null,
                    title: 'Acciones',
                    orderable: false,
                    render: (data) => {
                        return `
                            <button class="btn btn-sm btn-info btn-ver-comunicacion-proveedor" data-id="${data.ID}">
                                <i class="fas fa-eye"></i>
                            </button>
                        `;
                    }
                }
            ],
            columnDefs: [
                { width: "15%", targets: 0 }, // Fecha
                { width: "20%", targets: 1 }, // Proveedor
                { width: "12%", targets: 2 }, // Tipo
                { width: "30%", targets: 3 }, // Asunto
                { width: "13%", targets: 4 }, // Estado
                { width: "10%", targets: 5 }  // Acciones
            ],
            autoWidth: false,
            scrollX: false
            });
        } else {
            this.dataTableProveedor = null;
        }
    }

    cargarComunicacionesFlota() {
        const filtros = {
            placa: $('#filtro-placa-flota').val(),
            conductor: $('#filtro-conductor-flota').val(),
            tipo: $('#filtro-tipo-flota').val(),
            estado: $('#filtro-estado-flota').val()
        };

        $.ajax({
            url: '../app/model/comunicacion/scripts/s_comunicacion.php',
            type: 'POST',
            data: {
                accion: 'obtener_comunicaciones_flota',
                ...filtros
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.dataTableFlota.clear().rows.add(response.data).draw();
                } else {
                    this.mostrarNotificacion('Error al cargar comunicaciones: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: () => {
                this.mostrarNotificacion('Error al cargar comunicaciones. Por favor, intente nuevamente.', 'error');
            }
        });
    }

    cargarComunicacionesProveedor() {
        // Usar el nombre del proveedor seleccionado, no el ID
        let proveedorNombre = this.proveedorSeleccionado?.Nombre || '';
        
        // Si no hay proveedor seleccionado en memoria, intentar obtenerlo del campo oculto
        if (!proveedorNombre) {
            const proveedorId = $('#proveedor-id-comunicacion').val();
            if (proveedorId) {
                // Si tenemos el ID pero no el objeto completo, no podemos cargar sin el nombre
                console.warn('Proveedor seleccionado perdido, necesitamos recargarlo');
                if (this.dataTableProveedor) {
                    this.dataTableProveedor.clear().draw();
                }
                return;
            } else {
                // Si no hay proveedor seleccionado, limpiar tabla
                if (this.dataTableProveedor) {
                    this.dataTableProveedor.clear().draw();
                }
                return;
            }
        }

        // Limpiar espacios y normalizar el nombre
        proveedorNombre = proveedorNombre.trim();

        // Usar el script de comunicaciones general, no el de proveedores
        const urlComunicaciones = this.getBaseUrl() + 'app/model/comunicacion/scripts/s_comunicacion.php';
        
        console.log('cargarComunicacionesProveedor - Proveedor:', proveedorNombre);
        console.log('Proveedor seleccionado completo:', this.proveedorSeleccionado);
        
        $.ajax({
            url: urlComunicaciones,
            type: 'POST',
            data: {
                accion: 'obtener_comunicaciones_proveedor',
                proveedor: proveedorNombre  // El filtro espera el nombre del proveedor
            },
            dataType: 'json',
            success: (response) => {
                console.log('Respuesta obtener_comunicaciones_proveedor:', response);
                console.log('Datos recibidos:', response.data?.length || 0, 'comunicaciones');
                if (response.status === 'success') {
                    if (this.dataTableProveedor) {
                        this.dataTableProveedor.clear().rows.add(response.data || []).draw();
                        console.log('Tabla actualizada con', response.data?.length || 0, 'comunicaciones');
                    } else {
                        console.error('DataTable de proveedor no inicializada');
                    }
                } else {
                    this.mostrarNotificacion('Error al cargar comunicaciones: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error en cargarComunicacionesProveedor:', {xhr, status, error, responseText: xhr.responseText});
                this.mostrarNotificacion('Error al cargar comunicaciones. Por favor, intente nuevamente.', 'error');
            }
        });
    }

    guardarComunicacionFlota() {
        const datos = {
            accion: 'crear_comunicacion_flota',
            placa: $('#placa-comunicacion-flota').val().trim().toUpperCase(),
            tipo: $('#tipo-comunicacion-flota').val(),
            asunto: $('#asunto-comunicacion-flota').val().trim(),
            mensaje: $('#mensaje-comunicacion-flota').val().trim()
        };

        $.ajax({
            url: '../app/model/comunicacion/scripts/s_comunicacion.php',
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarNotificacion('Comunicación enviada correctamente', 'success');
                    $('#modalNuevaComunicacionFlota').modal('hide');
                    this.cargarComunicacionesFlota();
                } else {
                    this.mostrarNotificacion('Error: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: () => {
                this.mostrarNotificacion('Error al enviar comunicación. Por favor, intente nuevamente.', 'error');
            }
        });
    }

    guardarComunicacionProveedor() {
        const datos = {
            accion: 'crear_comunicacion_proveedor',
            proveedor: $('#proveedor-comunicacion').val().trim(),
            contacto: $('#contacto-proveedor').val().trim(),
            email: $('#email-proveedor').val().trim(),
            telefono: $('#telefono-proveedor').val().trim(),
            tipo: $('#tipo-comunicacion-proveedor').val(),
            asunto: $('#asunto-comunicacion-proveedor').val().trim(),
            mensaje: $('#mensaje-comunicacion-proveedor').val().trim()
        };

        $.ajax({
            url: '../app/model/comunicacion/scripts/s_comunicacion.php',
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarNotificacion('Comunicación enviada correctamente', 'success');
                    $('#modalNuevaComunicacionProveedor').modal('hide');
                    this.cargarComunicacionesProveedor();
                } else {
                    this.mostrarNotificacion('Error: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: () => {
                this.mostrarNotificacion('Error al enviar comunicación. Por favor, intente nuevamente.', 'error');
            }
        });
    }

    mostrarDetallesComunicacion(id, tipo) {
        $.ajax({
            url: '../app/model/comunicacion/scripts/s_comunicacion.php',
            type: 'POST',
            data: {
                accion: 'obtener_comunicacion',
                id: id,
                tipo: tipo
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    const com = response.data;
                    let contenido = '';
                    let footer = '';

                    if (tipo === 'flota') {
                        // Mostrar comunicación original
                        contenido = `
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-envelope me-2"></i>Mensaje Original</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Placa:</strong> ${com.Placa || '-'}</p>
                                            <p><strong>Conductor:</strong> ${com.ConductorNombre || '-'}</p>
                                            <p><strong>Tipo:</strong> <span class="badge bg-info">${com.Tipo || '-'}</span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Fecha:</strong> ${com.FechaCreacion ? new Date(com.FechaCreacion).toLocaleString('es-ES') : '-'}</p>
                                            <p><strong>Estado:</strong> <span class="badge bg-${com.Estado === 'Respondida' ? 'success' : com.Estado === 'En Proceso' ? 'info' : 'warning'}">${com.Estado || '-'}</span></p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mb-2">
                                        <strong>Asunto:</strong>
                                        <p>${com.Asunto || '-'}</p>
                                    </div>
                                    <div class="mb-0">
                                        <strong>Mensaje:</strong>
                                        <p class="mb-0">${com.Mensaje || '-'}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Mostrar respuestas si existen
                        if (com.respuestas && com.respuestas.length > 0) {
                            contenido += `<h6 class="mb-3"><i class="fas fa-comments me-2"></i>Respuestas (${com.respuestas.length})</h6>`;
                            com.respuestas.forEach((respuesta, index) => {
                                contenido += `
                                    <div class="card mb-2 border-start border-3 border-success">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    ${respuesta.FechaCreacion ? new Date(respuesta.FechaCreacion).toLocaleString('es-ES') : '-'}
                                                </small>
                                                <span class="badge bg-success">Respuesta ${index + 1}</span>
                                            </div>
                                            <p class="mb-0">${respuesta.Mensaje || '-'}</p>
                                        </div>
                                    </div>
                                `;
                            });
                        }
                        
                        // Botón de responder (solo si no es una respuesta)
                        // Cualquier usuario puede responder a una comunicación
                        if (!com.ComunicacionPadreID) {
                            footer = `
                                <button type="button" class="btn btn-primary" id="btn-responder-comunicacion" data-id="${com.ID}" data-placa="${com.Placa}">
                                    <i class="fas fa-reply me-2"></i>Responder
                                </button>
                            `;
                        }
                    } else {
                        contenido = `
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Proveedor:</strong> ${com.ProveedorNombre || '-'}</p>
                                    <p><strong>Contacto:</strong> ${com.ContactoNombre || '-'}</p>
                                    <p><strong>Email:</strong> ${com.Email || '-'}</p>
                                    <p><strong>Teléfono:</strong> ${com.Telefono || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Tipo:</strong> <span class="badge bg-primary">${com.Tipo || '-'}</span></p>
                                    <p><strong>Fecha:</strong> ${com.FechaCreacion ? new Date(com.FechaCreacion).toLocaleString('es-ES') : '-'}</p>
                                    <p><strong>Estado:</strong> <span class="badge bg-warning">${com.Estado || '-'}</span></p>
                                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <strong>Asunto:</strong>
                                <p>${com.Asunto || '-'}</p>
                            </div>
                            <div class="mb-3">
                                <strong>Mensaje:</strong>
                                <p>${com.Mensaje || '-'}</p>
                            </div>
                        `;
                    }

                    $('#contenido-comunicacion').html(contenido);
                    $('#footer-comunicacion').html(footer);
                    
                    // Evento para botón responder
                    if (footer) {
                        $('#btn-responder-comunicacion').off('click').on('click', () => {
                            this.abrirModalResponder(com.ID, com.Placa, com.Asunto);
                        });
                    }
                    
                    $('#modalVerComunicacion').modal('show');
                } else {
                    this.mostrarNotificacion('Error al cargar detalles: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: () => {
                this.mostrarNotificacion('Error al cargar detalles. Por favor, intente nuevamente.', 'error');
            }
        });
    }
    
    getUsuarioRol() {
        // Obtener el rol del usuario desde algún lugar (puede ser desde el HTML o una variable global)
        return window.usuarioRol || '';
    }
    
    abrirModalResponder(comunicacionId, placa, asunto) {
        $('#comunicacion-padre-id').val(comunicacionId);
        $('#placa-respuesta').val(placa);
        $('#asunto-original-respuesta').text(asunto || 'Sin asunto');
        $('#mensaje-respuesta').val('');
        $('#modalVerComunicacion').modal('hide');
        $('#modalResponderComunicacion').modal('show');
    }
    
    enviarRespuesta() {
        const comunicacionPadreId = $('#comunicacion-padre-id').val();
        const placa = $('#placa-respuesta').val();
        const mensaje = $('#mensaje-respuesta').val().trim();
        
        if (!mensaje) {
            this.mostrarNotificacion('Por favor, ingrese un mensaje de respuesta', 'error');
            return;
        }
        
        const datos = {
            accion: 'crear_comunicacion_flota',
            placa: placa,
            tipo: 'Consulta', // Tipo por defecto para respuestas
            asunto: 'Re: ' + $('#asunto-original-respuesta').text(),
            mensaje: mensaje,
            comunicacion_padre_id: comunicacionPadreId
        };
        
        $.ajax({
            url: '../app/model/comunicacion/scripts/s_comunicacion.php',
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarNotificacion('Respuesta enviada correctamente', 'success');
                    $('#modalResponderComunicacion').modal('hide');
                    $('#form-responder-comunicacion')[0].reset();
                    this.cargarComunicacionesFlota();
                } else {
                    this.mostrarNotificacion('Error: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: () => {
                this.mostrarNotificacion('Error al enviar respuesta. Por favor, intente nuevamente.', 'error');
            }
        });
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

    limpiarFormularioProveedor() {
        $('#form-proveedor')[0].reset();
        $('#proveedor-id').val('');
        $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $('.invalid-feedback').text('');
    }

    mostrarDetallesComunicacionProveedor(id) {
        // Usar el script de comunicaciones general, no el de proveedores
        const urlComunicaciones = this.getBaseUrl() + 'app/model/comunicacion/scripts/s_comunicacion.php';
        
        console.log('mostrarDetallesComunicacionProveedor - ID:', id);
        
        $.ajax({
            url: urlComunicaciones,
            type: 'POST',
            data: {
                accion: 'obtener_comunicacion',
                id: id,
                tipo: 'proveedor'  // El backend requiere el tipo
            },
            dataType: 'json',
            success: (response) => {
                console.log('Respuesta obtener_comunicacion proveedor:', response);
                if (response.status === 'success' && response.data) {
                    const com = response.data;
                    const contenido = `
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Proveedor:</strong> ${com.ProveedorNombre || '-'}</p>
                                <p><strong>Contacto:</strong> ${com.ContactoNombre || '-'}</p>
                                <p><strong>Email:</strong> ${com.Email ? `<a href="mailto:${com.Email}">${com.Email}</a>` : '-'}</p>
                                <p><strong>Teléfono:</strong> ${com.Telefono || '-'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Tipo:</strong> <span class="badge bg-primary">${com.Tipo || '-'}</span></p>
                                <p><strong>Fecha:</strong> ${com.FechaCreacion ? new Date(com.FechaCreacion).toLocaleString('es-ES') : '-'}</p>
                                <p><strong>Estado:</strong> <span class="badge bg-${com.Estado === 'Pendiente' ? 'warning' : com.Estado === 'Respondida' ? 'success' : com.Estado === 'En Proceso' ? 'info' : 'secondary'}">${com.Estado || '-'}</span></p>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <strong>Asunto:</strong>
                            <p>${com.Asunto || '-'}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Mensaje:</strong>
                            <p>${com.Mensaje || '-'}</p>
                        </div>
                    `;
                    $('#contenido-comunicacion').html(contenido);
                    $('#modalVerComunicacion').modal('show');
                } else {
                    this.mostrarNotificacion('Error al cargar detalles: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error en mostrarDetallesComunicacionProveedor:', {xhr, status, error, responseText: xhr.responseText});
                this.mostrarNotificacion('Error al cargar detalles. Por favor, intente nuevamente.', 'error');
            }
        });
    }

    // Funciones para gestión de proveedores
    cargarProveedores() {
        console.log('cargarProveedores llamado, URL:', this.baseUrlProveedores);
        $.ajax({
            url: this.baseUrlProveedores,
            type: 'POST',
            data: {
                accion: 'obtener_proveedores'
            },
            dataType: 'json',
            success: (response) => {
                console.log('Respuesta obtener_proveedores:', response);
                if (response.status === 'success') {
                    if (this.tablaProveedores) {
                        this.tablaProveedores.clear().rows.add(response.data || []).draw();
                        console.log('Proveedores cargados:', response.data?.length || 0);
                    } else {
                        console.error('Tabla de proveedores no inicializada');
                    }
                } else {
                    console.error('Error al cargar proveedores:', response.message);
                    this.mostrarNotificacion('Error al cargar proveedores: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error de conexión al cargar proveedores:', {xhr, status, error, responseText: xhr.responseText});
                this.mostrarNotificacion('Error de conexión al cargar proveedores', 'error');
            }
        });
    }

    abrirModalProveedor() {
        $('#modal-proveedor-title').html('<i class="fas fa-building me-2"></i>Nuevo Proveedor');
        $('#proveedor-id').val('');
        this.limpiarFormularioProveedor();
        $('#modal-proveedor').modal('show');
    }

    editarProveedor(id) {
        $.ajax({
            url: this.baseUrlProveedores,
            type: 'POST',
            data: {
                accion: 'obtener_proveedor',
                id: id
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    const proveedor = response.data;
                    $('#modal-proveedor-title').html('<i class="fas fa-edit me-2"></i>Editar Proveedor');
                    $('#proveedor-id').val(proveedor.ID);
                    $('#nombre-proveedor').val(proveedor.Nombre);
                    $('#contacto-proveedor-modal').val(proveedor.Contacto);
                    $('#email-proveedor-modal').val(proveedor.Email);
                    $('#telefono-proveedor-modal').val(proveedor.Telefono);
                    $('#rut-proveedor-modal').val(proveedor.RUT || '');
                    $('#direccion-proveedor-modal').val(proveedor.Direccion || '');
                    $('#modal-proveedor').modal('show');
                } else {
                    this.mostrarNotificacion('Error al cargar los datos del proveedor', 'error');
                }
            },
            error: () => {
                this.mostrarNotificacion('Error de conexión', 'error');
            }
        });
    }

    eliminarProveedor(id) {
        if (!confirm('¿Está seguro de que desea eliminar este proveedor?')) {
            return;
        }

        $.ajax({
            url: this.baseUrlProveedores,
            type: 'POST',
            data: {
                accion: 'eliminar_proveedor',
                id: id
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarNotificacion('Proveedor eliminado correctamente', 'success');
                    this.cargarProveedores();
                } else {
                    this.mostrarNotificacion(response.message || 'Error al eliminar el proveedor', 'error');
                }
            },
            error: () => {
                this.mostrarNotificacion('Error de conexión', 'error');
            }
        });
    }

    seleccionarProveedor(id) {
        console.log('seleccionarProveedor llamado con ID:', id);
        console.log('URL:', this.baseUrlProveedores);
        $.ajax({
            url: this.baseUrlProveedores,
            type: 'POST',
            data: {
                accion: 'obtener_proveedor',
                id: id
            },
            dataType: 'json',
            success: (response) => {
                console.log('Respuesta seleccionarProveedor:', response);
                if (response.status === 'success' && response.data) {
                    this.proveedorSeleccionado = response.data;
                    $('#proveedor-id-comunicacion').val(response.data.ID);
                    $('#form-nueva-comunicacion-proveedores').show();
                    $('#area-comunicacion-proveedores').html(`
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Proveedor seleccionado: <strong>${response.data.Nombre}</strong><br>
                            <small>Contacto: ${response.data.Contacto} | Email: ${response.data.Email}</small>
                        </div>
                    `);
                    this.cargarComunicacionesProveedor();
                } else {
                    this.mostrarNotificacion(response.message || 'Error al seleccionar el proveedor', 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error en seleccionarProveedor:', {xhr, status, error, responseText: xhr.responseText});
                this.mostrarNotificacion('Error al cargar el proveedor: ' + (xhr.responseText || error), 'error');
            }
        });
    }

    guardarProveedor() {
        const datos = {
            accion: $('#proveedor-id').val() ? 'actualizar_proveedor' : 'crear_proveedor',
            id: $('#proveedor-id').val() || '',
            nombre: $('#nombre-proveedor').val().trim(),
            contacto: $('#contacto-proveedor-modal').val().trim(),
            email: $('#email-proveedor-modal').val().trim(),
            telefono: $('#telefono-proveedor-modal').val().trim(),
            rut: $('#rut-proveedor-modal').val().trim(),
            direccion: $('#direccion-proveedor-modal').val().trim()
        };

        $.ajax({
            url: this.baseUrlProveedores,
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarNotificacion(response.message || 'Proveedor guardado correctamente', 'success');
                    $('#modal-proveedor').modal('hide');
                    this.cargarProveedores();
                } else {
                    this.mostrarNotificacion(response.message || 'Error al guardar el proveedor', 'error');
                }
            },
            error: () => {
                this.mostrarNotificacion('Error de conexión', 'error');
            }
        });
    }

    enviarComunicacionProveedor() {
        // Verificar que haya un proveedor seleccionado
        if (!this.proveedorSeleccionado || !this.proveedorSeleccionado.Nombre) {
            this.mostrarNotificacion('Por favor, seleccione un proveedor primero', 'error');
            return;
        }

        const tipo = $('#tipo-comunicacion-proveedores').val();
        const asunto = $('#asunto-proveedores').val().trim();
        const mensaje = $('#mensaje-proveedores').val().trim();

        // Validar campos requeridos
        if (!tipo || !asunto || !mensaje) {
            this.mostrarNotificacion('Por favor, complete todos los campos requeridos', 'error');
            return;
        }

        // Usar el script de comunicaciones general, no el de proveedores
        const urlComunicaciones = this.getBaseUrl() + 'app/model/comunicacion/scripts/s_comunicacion.php';

        const datos = {
            accion: 'crear_comunicacion_proveedor',
            proveedor: this.proveedorSeleccionado.Nombre,
            contacto: this.proveedorSeleccionado.Contacto || '',
            email: this.proveedorSeleccionado.Email || '',
            telefono: this.proveedorSeleccionado.Telefono || '',
            tipo: tipo,
            asunto: asunto,
            mensaje: mensaje
        };

        console.log('Enviando comunicación proveedor:', datos);

        $.ajax({
            url: urlComunicaciones,
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                console.log('Respuesta crear_comunicacion_proveedor:', response);
                if (response.status === 'success') {
                    this.mostrarNotificacion('Comunicación enviada correctamente', 'success');
                    $('#form-comunicacion-proveedores')[0].reset();
                    // Esperar un momento antes de recargar para asegurar que la BD se actualizó
                    setTimeout(() => {
                        console.log('Recargando comunicaciones para proveedor:', this.proveedorSeleccionado?.Nombre);
                        this.cargarComunicacionesProveedor();
                    }, 500);
                } else {
                    this.mostrarNotificacion(response.message || 'Error al enviar comunicación', 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error en enviarComunicacionProveedor:', {xhr, status, error, responseText: xhr.responseText});
                this.mostrarNotificacion('Error de conexión al enviar comunicación', 'error');
            }
        });
    }
}

// Inicializar cuando el DOM esté listo
function initComunicacionFlotaProveedores() {
    if (typeof jQuery !== 'undefined') {
        $(document).ready(function() {
            new ComunicacionFlotaProveedoresApp();
        });
    } else {
        setTimeout(initComunicacionFlotaProveedores, 100);
    }
}

initComunicacionFlotaProveedores();

