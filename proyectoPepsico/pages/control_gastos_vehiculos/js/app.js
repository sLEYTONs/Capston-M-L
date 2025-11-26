// Aplicación para Control de Gastos y Vehículos
class ControlGastosVehiculos {
    constructor() {
        this.dataTableInternos = null;
        this.dataTableExternos = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/control_gastos_vehiculos/scripts/s_control_gastos.php';
        }
        return '../../app/model/control_gastos_vehiculos/scripts/s_control_gastos.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.inicializarDataTables();
        this.cargarEstadisticas();
        this.cargarVehiculos();
        this.cargarGastos();
    }

    inicializarEventos() {
        $('#btn-filtrar').on('click', () => {
            this.cargarGastos();
            this.cargarEstadisticas();
        });

        $('#tab-internos, #tab-externos').on('shown.bs.tab', () => {
            if (this.dataTableInternos) this.dataTableInternos.columns.adjust();
            if (this.dataTableExternos) this.dataTableExternos.columns.adjust();
        });

        $('#btn-guardar-gasto').on('click', () => {
            this.guardarGastoExterno();
        });

        // Botón para abrir modal de nuevo gasto (si existe)
        $(document).on('click', '#btn-nuevo-gasto-externo', () => {
            $('#form-gasto-externo')[0].reset();
            $('#modal-gasto-externo').removeData('gasto-id');
            $('#modal-gasto-externo .modal-title').html('<i class="fas fa-plus me-2"></i>Registrar Gasto en Taller Externo');
            $('#btn-guardar-gasto').html('<i class="fas fa-save me-2"></i>Guardar');
            $('#modal-gasto-externo').modal('show');
        });

        // Limpiar modal al cerrar
        $('#modal-gasto-externo').on('hidden.bs.modal', () => {
            $('#form-gasto-externo')[0].reset();
            $('#modal-gasto-externo').removeData('gasto-id');
            $('#modal-gasto-externo .modal-title').html('<i class="fas fa-plus me-2"></i>Registrar Gasto en Taller Externo');
            $('#btn-guardar-gasto').html('<i class="fas fa-save me-2"></i>Guardar');
        });

        // Ver detalles de gasto interno
        $(document).on('click', '.btn-ver-detalles-interno', (e) => {
            const btn = $(e.currentTarget);
            this.mostrarDetallesInterno({
                id: btn.data('id'),
                placa: btn.data('placa'),
                vehiculo: btn.data('vehiculo'),
                fecha: btn.data('fecha'),
                servicio: btn.data('servicio'),
                repuestos: btn.data('repuestos'),
                costoRepuestos: btn.data('costo-repuestos'),
                costoMano: btn.data('costo-mano'),
                estado: btn.data('estado')
            });
        });

        // Ver detalles de gasto externo
        $(document).on('click', '.btn-ver-detalles-externo', (e) => {
            const gastoId = $(e.currentTarget).data('id');
            this.obtenerDetallesExterno(gastoId);
        });

        // Editar gasto externo
        $(document).on('click', '.btn-editar-gasto', (e) => {
            const gastoId = $(e.currentTarget).data('id');
            this.editarGastoExterno(gastoId);
        });

        // Eliminar gasto externo
        $(document).on('click', '.btn-eliminar-gasto', (e) => {
            const gastoId = $(e.currentTarget).data('id');
            this.eliminarGastoExterno(gastoId);
        });
    }

    inicializarDataTables() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        const languageConfig = {
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
        };

        // Tabla Talleres Internos
        this.dataTableInternos = $('#tabla-gastos-internos').DataTable({
            language: languageConfig,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            responsive: true,
            ajax: {
                url: this.baseUrl,
                type: 'POST',
                data: (d) => {
                    d.accion = 'obtener_gastos_internos';
                    d.fecha_desde = $('#filtro-fecha-desde').val();
                    d.fecha_hasta = $('#filtro-fecha-hasta').val();
                }
            },
            columns: [
                { 
                    data: 'Fecha',
                    render: (data) => new Date(data).toLocaleDateString('es-ES')
                },
                { 
                    data: null,
                    render: (data) => `${data.Marca || ''} ${data.Modelo || ''}`.trim()
                },
                { data: 'Placa' },
                { data: 'Servicio' },
                { data: 'CantidadRepuestos' },
                { 
                    data: 'CostoRepuestos',
                    render: (data) => `$${parseFloat(data || 0).toFixed(2)}`
                },
                { 
                    data: 'CostoManoObra',
                    render: (data) => `$${parseFloat(data || 0).toFixed(2)}`
                },
                { 
                    data: null,
                    render: (data) => {
                        const total = parseFloat(data.CostoRepuestos || 0) + parseFloat(data.CostoManoObra || 0);
                        return `$${total.toFixed(2)}`;
                    }
                },
                { 
                    data: 'Estado',
                    render: (data) => {
                        const badges = {
                            'Completado': '<span class="badge bg-success">Completado</span>',
                            'En Proceso': '<span class="badge bg-info">En Proceso</span>',
                            'Pendiente': '<span class="badge bg-warning">Pendiente</span>'
                        };
                        return badges[data] || data;
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data) => {
                        return `
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-info btn-ver-detalles-interno" 
                                        data-id="${data.ID}" 
                                        data-placa="${data.Placa || ''}"
                                        data-vehiculo="${(data.Marca || '') + ' ' + (data.Modelo || '')}"
                                        data-fecha="${data.Fecha || ''}"
                                        data-servicio="${data.Servicio || ''}"
                                        data-repuestos="${data.CantidadRepuestos || 0}"
                                        data-costo-repuestos="${data.CostoRepuestos || 0}"
                                        data-costo-mano="${data.CostoManoObra || 0}"
                                        data-estado="${data.Estado || ''}"
                                        title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ]
        });

        // Tabla Talleres Externos
        this.dataTableExternos = $('#tabla-gastos-externos').DataTable({
            language: languageConfig,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            responsive: true,
            ajax: {
                url: this.baseUrl,
                type: 'POST',
                data: (d) => {
                    d.accion = 'obtener_gastos_externos';
                    d.fecha_desde = $('#filtro-fecha-desde').val();
                    d.fecha_hasta = $('#filtro-fecha-hasta').val();
                }
            },
            columns: [
                { 
                    data: 'Fecha',
                    render: (data) => new Date(data).toLocaleDateString('es-ES')
                },
                { 
                    data: null,
                    render: (data) => `${data.Marca || ''} ${data.Modelo || ''}`.trim()
                },
                { data: 'Placa' },
                { data: 'TallerNombre' },
                { data: 'Servicio' },
                { 
                    data: 'CostoTotal',
                    render: (data) => `$${parseFloat(data || 0).toFixed(2)}`
                },
                { 
                    data: 'Estado',
                    render: (data) => {
                        const badges = {
                            'Completado': '<span class="badge bg-success">Completado</span>',
                            'En Proceso': '<span class="badge bg-info">En Proceso</span>',
                            'Pendiente': '<span class="badge bg-warning">Pendiente</span>'
                        };
                        return badges[data] || data;
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data) => {
                        return `
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-info btn-ver-detalles-externo" 
                                        data-id="${data.ID}" 
                                        title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning btn-editar-gasto" 
                                        data-id="${data.ID}" 
                                        title="Editar Gasto">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-eliminar-gasto" 
                                        data-id="${data.ID}" 
                                        title="Eliminar Gasto">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
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
            data: { 
                accion: 'obtener_estadisticas',
                fecha_desde: $('#filtro-fecha-desde').val(),
                fecha_hasta: $('#filtro-fecha-hasta').val()
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    $('#gastos-mes').text('$' + parseFloat(response.data.gastos_mes || 0).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $('#talleres-internos').text(response.data.talleres_internos || 0);
                    $('#talleres-externos').text(response.data.talleres_externos || 0);
                    $('#vehiculos-taller').text(response.data.vehiculos_taller || 0);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar estadísticas:', error);
            }
        });
    }

    cargarVehiculos() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_vehiculos' },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    const select = $('#vehiculo-gasto');
                    select.empty().append('<option value="">Seleccionar...</option>');
                    response.data.forEach(vehiculo => {
                        select.append(`<option value="${vehiculo.ID}">${vehiculo.Placa} - ${vehiculo.Marca} ${vehiculo.Modelo}</option>`);
                    });
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar vehículos:', error);
            }
        });
    }

    cargarGastos() {
        if (this.dataTableInternos) {
            this.dataTableInternos.ajax.reload();
        }
        if (this.dataTableExternos) {
            this.dataTableExternos.ajax.reload();
        }
    }

    guardarGastoExterno() {
        const gastoId = $('#modal-gasto-externo').data('gasto-id');
        const vehiculoId = $('#vehiculo-gasto').val();
        const taller = $('#taller-externo').val().trim();
        const servicio = $('#servicio-gasto').val().trim();
        const costo = $('#costo-gasto').val();
        const fecha = $('#fecha-gasto').val();
        const observaciones = $('#observaciones-gasto').val().trim();

        if (!vehiculoId || !taller || !servicio || !costo || !fecha) {
            this.mostrarNotificacion('Por favor, complete todos los campos obligatorios', 'error');
            return;
        }

        const btnGuardar = $('#btn-guardar-gasto');
        const textoOriginal = btnGuardar.html();
        btnGuardar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

        const accion = gastoId ? 'actualizar_gasto_externo' : 'registrar_gasto_externo';
        const datos = {
            accion: accion,
            vehiculo_id: vehiculoId,
            taller_nombre: taller,
            servicio: servicio,
            costo_total: costo,
            fecha: fecha,
            observaciones: observaciones
        };

        if (gastoId) {
            datos.gasto_id = gastoId;
        }

        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                btnGuardar.prop('disabled', false).html(textoOriginal);
                
                if (response.status === 'success') {
                    this.mostrarNotificacion(gastoId ? 'Gasto actualizado correctamente' : 'Gasto registrado correctamente', 'success');
                    $('#modal-gasto-externo').modal('hide');
                    $('#form-gasto-externo')[0].reset();
                    $('#modal-gasto-externo').removeData('gasto-id');
                    $('#modal-gasto-externo .modal-title').html('<i class="fas fa-plus me-2"></i>Registrar Gasto en Taller Externo');
                    $('#btn-guardar-gasto').html('<i class="fas fa-save me-2"></i>Guardar');
                    this.cargarGastos();
                    this.cargarEstadisticas();
                } else {
                    this.mostrarNotificacion('Error: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                btnGuardar.prop('disabled', false).html(textoOriginal);
                this.mostrarNotificacion('Error de conexión. Por favor, intente nuevamente.', 'error');
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

    mostrarDetallesInterno(datos) {
        const total = parseFloat(datos.costoRepuestos || 0) + parseFloat(datos.costoMano || 0);
        const fechaFormateada = datos.fecha ? new Date(datos.fecha).toLocaleDateString('es-ES') : '-';
        
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Placa:</strong> ${datos.placa || '-'}</p>
                    <p><strong>Vehículo:</strong> ${datos.vehiculo || '-'}</p>
                    <p><strong>Fecha:</strong> ${fechaFormateada}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Servicio:</strong> ${datos.servicio || '-'}</p>
                    <p><strong>Estado:</strong> <span class="badge bg-info">${datos.estado || '-'}</span></p>
                    <p><strong>Cantidad Repuestos:</strong> ${datos.repuestos || 0}</p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-12">
                    <h6>Desglose de Costos</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>Costo Repuestos:</td>
                            <td class="text-end"><strong>$${parseFloat(datos.costoRepuestos || 0).toFixed(2)}</strong></td>
                        </tr>
                        <tr>
                            <td>Costo Mano de Obra:</td>
                            <td class="text-end"><strong>$${parseFloat(datos.costoMano || 0).toFixed(2)}</strong></td>
                        </tr>
                        <tr class="table-primary">
                            <td><strong>Total:</strong></td>
                            <td class="text-end"><strong>$${total.toFixed(2)}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        `;

        Swal.fire({
            title: 'Detalles del Gasto - Taller Interno',
            html: html,
            width: '700px',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#3085d6'
        });
    }

    obtenerDetallesExterno(gastoId) {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { 
                accion: 'obtener_detalles_gasto',
                gasto_id: gastoId
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    this.mostrarDetallesExterno(response.data);
                } else {
                    this.mostrarNotificacion('Error al obtener detalles del gasto', 'error');
                }
            },
            error: () => {
                this.mostrarNotificacion('Error de conexión', 'error');
            }
        });
    }

    mostrarDetallesExterno(datos) {
        const fechaFormateada = datos.Fecha ? new Date(datos.Fecha).toLocaleDateString('es-ES') : '-';
        const vehiculo = `${datos.Marca || ''} ${datos.Modelo || ''}`.trim();
        
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Placa:</strong> ${datos.Placa || '-'}</p>
                    <p><strong>Vehículo:</strong> ${vehiculo || '-'}</p>
                    <p><strong>Fecha:</strong> ${fechaFormateada}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Taller:</strong> ${datos.TallerNombre || '-'}</p>
                    <p><strong>Estado:</strong> <span class="badge bg-info">${datos.Estado || '-'}</span></p>
                    <p><strong>Costo Total:</strong> <span class="text-success"><strong>$${parseFloat(datos.CostoTotal || 0).toFixed(2)}</strong></span></p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-12">
                    <p><strong>Servicio Realizado:</strong></p>
                    <p class="bg-light p-3 rounded">${datos.Servicio || '-'}</p>
                </div>
            </div>
        `;

        if (datos.Observaciones) {
            html += `
                <div class="row mt-3">
                    <div class="col-md-12">
                        <p><strong>Observaciones:</strong></p>
                        <p class="bg-light p-3 rounded">${datos.Observaciones}</p>
                    </div>
                </div>
            `;
        }

        Swal.fire({
            title: 'Detalles del Gasto - Taller Externo',
            html: html,
            width: '700px',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#3085d6'
        });
    }

    editarGastoExterno(gastoId) {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { 
                accion: 'obtener_detalles_gasto',
                gasto_id: gastoId
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    const datos = response.data;
                    $('#vehiculo-gasto').val(datos.VehiculoID);
                    $('#taller-externo').val(datos.TallerNombre || '');
                    $('#servicio-gasto').val(datos.Servicio || '');
                    $('#costo-gasto').val(datos.CostoTotal || '');
                    $('#fecha-gasto').val(datos.Fecha ? datos.Fecha.split(' ')[0] : '');
                    $('#observaciones-gasto').val(datos.Observaciones || '');
                    
                    // Guardar el ID del gasto para actualización
                    $('#modal-gasto-externo').data('gasto-id', gastoId);
                    $('#modal-gasto-externo .modal-title').html('<i class="fas fa-edit me-2"></i>Editar Gasto en Taller Externo');
                    $('#btn-guardar-gasto').html('<i class="fas fa-save me-2"></i>Actualizar');
                    $('#modal-gasto-externo').modal('show');
                } else {
                    this.mostrarNotificacion('Error al obtener datos del gasto', 'error');
                }
            },
            error: () => {
                this.mostrarNotificacion('Error de conexión', 'error');
            }
        });
    }

    eliminarGastoExterno(gastoId) {
        Swal.fire({
            title: '¿Está seguro?',
            text: 'Esta acción eliminará el gasto registrado. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: this.baseUrl,
                    type: 'POST',
                    data: { 
                        accion: 'eliminar_gasto_externo',
                        gasto_id: gastoId
                    },
                    dataType: 'json',
                    success: (response) => {
                        if (response.status === 'success') {
                            this.mostrarNotificacion('Gasto eliminado correctamente', 'success');
                            this.cargarGastos();
                            this.cargarEstadisticas();
                        } else {
                            this.mostrarNotificacion('Error: ' + (response.message || 'Error desconocido'), 'error');
                        }
                    },
                    error: () => {
                        this.mostrarNotificacion('Error de conexión', 'error');
                    }
                });
            }
        });
    }
}

// Inicializar cuando el DOM esté listo
let controlGastosVehiculos;
document.addEventListener('DOMContentLoaded', () => {
    controlGastosVehiculos = new ControlGastosVehiculos();
});
