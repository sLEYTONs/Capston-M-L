class CalidadApp {
    constructor() {
        this.dataTable = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        if (typeof jQuery === 'undefined') {
            console.error('jQuery no está cargado');
            setTimeout(() => this.init(), 100);
            return;
        }

        if (!$('#tabla-asignaciones').length) {
            console.error('No se encontró la tabla tabla-asignaciones');
            return;
        }
        
        this.bindEvents();
        this.inicializarDataTable();
        this.cargarEstadisticas();
        this.cargarAsignaciones();
    }

    bindEvents() {
        $('#btn-aplicar-filtros').on('click', () => this.cargarAsignaciones());
        $('#btn-limpiar-filtros').on('click', () => this.limpiarFiltros());
        
        // Ver detalles y revisar calidad
        $(document).on('click', '.btn-revisar-calidad', (e) => {
            const asignacionId = $(e.currentTarget).data('id');
            this.mostrarModalRevisar(asignacionId);
        });

        // Guardar revisión de calidad
        $('#form-revision-calidad').on('submit', (e) => {
            e.preventDefault();
            this.guardarRevision();
        });

        // Limpiar formulario al cerrar modal
        $('#modalRevisarCalidad').on('hidden.bs.modal', () => {
            $('#form-revision-calidad')[0].reset();
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#tabla-asignaciones').DataTable({
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
                    data: 'Placa',
                    render: (data) => `<span class="badge bg-info">${data}</span>`
                },
                { 
                    data: null,
                    render: (data) => {
                        return `${data.Marca} ${data.Modelo}`.trim();
                    }
                },
                { data: 'MecanicoNombre' },
                { data: 'FechaAsignacion' },
                { 
                    data: 'EstadoAsignacion',
                    render: (data) => {
                        const estados = {
                            'Asignado': 'warning',
                            'En Proceso': 'primary',
                            'Completado': 'success'
                        };
                        const color = estados[data] || 'secondary';
                        return `<span class="badge bg-${color}">${data}</span>`;
                    }
                },
                { 
                    data: 'EstadoCalidad',
                    render: (data) => {
                        const estado = data || 'Pendiente';
                        const clases = {
                            'Pendiente': 'pendiente',
                            'Aprobado': 'aprobado',
                            'Rechazado': 'rechazado',
                            'En Revisión': 'en-revision'
                        };
                        const clase = clases[estado] || 'pendiente';
                        return `<span class="badge badge-calidad ${clase}">${estado}</span>`;
                    }
                },
                { 
                    data: 'DiagnosticoInicial',
                    render: (data) => {
                        if (!data) return '-';
                        const texto = data.length > 50 ? data.substring(0, 50) + '...' : data;
                        return `<span title="${data}">${texto}</span>`;
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data) => {
                        return `
                            <button class="btn btn-sm btn-success btn-revisar-calidad" 
                                    data-id="${data.AsignacionID}" 
                                    title="Revisar Calidad">
                                <i class="fas fa-clipboard-check"></i>
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
            fecha_inicio: $('#filtro-fecha-inicio').val(),
            fecha_fin: $('#filtro-fecha-fin').val(),
            estado: $('#filtro-estado').val(),
            estado_calidad: $('#filtro-estado-calidad').val()
        };
    }

    cargarAsignaciones() {
        if (this.isLoading) return;

        this.isLoading = true;
        const filtros = this.obtenerFiltros();

        $.ajax({
            url: '../app/model/calidad/scripts/s_calidad.php',
            type: 'POST',
            data: {
                accion: 'obtener_asignaciones',
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
                    this.mostrarNotificacion('Error al cargar asignaciones: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                this.isLoading = false;
                console.error('Error al cargar asignaciones:', error);
                let mensaje = 'Error al cargar asignaciones. Por favor, intente nuevamente.';
                
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            mensaje = 'Error del servidor: ' + response.message;
                        }
                    } catch (e) {
                        mensaje = 'Error de conexión: ' + xhr.statusText;
                    }
                }
                
                this.mostrarNotificacion(mensaje, 'error');
            }
        });
    }

    cargarEstadisticas() {
        $.ajax({
            url: '../app/model/calidad/scripts/s_calidad.php',
            type: 'POST',
            data: {
                accion: 'obtener_estadisticas'
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    $('#total-revisiones').text(response.total_revisiones || 0);
                    $('#aprobadas').text(response.aprobadas || 0);
                    $('#rechazadas').text(response.rechazadas || 0);
                    $('#pendientes-revision').text(response.pendientes_revision || 0);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar estadísticas:', error);
            }
        });
    }

    mostrarModalRevisar(asignacionId) {
        if (!asignacionId) return;

        // Cargar historial completo
        $.ajax({
            url: '../app/model/calidad/scripts/s_calidad.php',
            type: 'POST',
            data: {
                accion: 'obtener_historial',
                asignacion_id: asignacionId
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarDetallesEnModal(asignacionId, response.data || []);
                } else {
                    this.mostrarNotificacion('Error al cargar detalles: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar historial:', error);
                this.mostrarNotificacion('Error al cargar historial', 'error');
            }
        });
    }

    mostrarDetallesEnModal(asignacionId, avances) {
        // Obtener datos de la asignación desde la tabla
        const rowData = this.dataTable.rows().data().toArray().find(row => row.AsignacionID == asignacionId);
        
        if (!rowData) {
            this.mostrarNotificacion('No se encontraron datos de la asignación', 'error');
            return;
        }

        // Mostrar información del vehículo
        const infoVehiculo = `
            <p><strong>Placa:</strong> ${rowData.Placa}</p>
            <p><strong>Vehículo:</strong> ${rowData.Marca} ${rowData.Modelo}</p>
            <p><strong>Tipo:</strong> ${rowData.TipoVehiculo || '-'}</p>
            <p><strong>Color:</strong> ${rowData.Color || '-'}</p>
            <p><strong>Año:</strong> ${rowData.Anio || '-'}</p>
            <p><strong>Conductor:</strong> ${rowData.ConductorNombre || '-'}</p>
        `;
        $('#info-vehiculo').html(infoVehiculo);

        // Mostrar información del mecánico
        const infoMecanico = `
            <p><strong>Nombre:</strong> ${rowData.MecanicoNombre || '-'}</p>
            <p><strong>Correo:</strong> ${rowData.MecanicoCorreo || '-'}</p>
            <p><strong>Fecha Asignación:</strong> ${rowData.FechaAsignacion}</p>
            <p><strong>Observaciones:</strong> ${rowData.ObservacionesAsignacion || 'Sin observaciones'}</p>
        `;
        $('#info-mecanico').html(infoMecanico);

        // Mostrar historial de avances
        let historialHtml = '';
        if (avances && avances.length > 0) {
            avances.forEach(avance => {
                const estadoClass = avance.Estado === 'Completado' ? 'completado' : 
                                   avance.Estado === 'En Proceso' ? 'en-proceso' : '';
                historialHtml += `
                    <div class="avance-item ${estadoClass}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${avance.Estado}</strong>
                                <span class="avance-fecha ms-2">${avance.FechaAvance}</span>
                            </div>
                            <span class="badge bg-${avance.Estado === 'Completado' ? 'success' : 'warning'}">
                                ${avance.Estado}
                            </span>
                        </div>
                        <div class="avance-descripcion mt-2">
                            ${avance.Descripcion || 'Sin descripción'}
                        </div>
                    </div>
                `;
            });
        } else {
            historialHtml = '<p class="text-muted">No hay avances registrados</p>';
        }
        $('#historial-avances').html(historialHtml);

        // Llenar formulario si ya hay revisión
        $('#revision-asignacion-id').val(asignacionId);
        if (rowData.DiagnosticoInicial) {
            $('#diagnostico-falla').val(rowData.DiagnosticoInicial);
        }
        if (rowData.EstadoCalidad && rowData.EstadoCalidad !== 'Pendiente') {
            $('#estado-calidad').val(rowData.EstadoCalidad);
        }
        if (rowData.ObservacionesCalidad) {
            $('#observaciones-calidad').val(rowData.ObservacionesCalidad);
        }

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalRevisarCalidad'));
        modal.show();
    }

    guardarRevision() {
        const asignacionId = $('#revision-asignacion-id').val();
        const diagnosticoFalla = $('#diagnostico-falla').val().trim();
        const estadoCalidad = $('#estado-calidad').val();
        const observaciones = $('#observaciones-calidad').val().trim();

        if (!asignacionId || !estadoCalidad) {
            this.mostrarNotificacion('Por favor, complete todos los campos requeridos', 'error');
            return;
        }

        $.ajax({
            url: '../app/model/calidad/scripts/s_calidad.php',
            type: 'POST',
            data: {
                accion: 'registrar_revision',
                asignacion_id: asignacionId,
                diagnostico_falla: diagnosticoFalla,
                estado_calidad: estadoCalidad,
                observaciones: observaciones
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarNotificacion('Revisión de calidad registrada correctamente', 'success');
                    $('#modalRevisarCalidad').modal('hide');
                    this.cargarAsignaciones();
                    this.cargarEstadisticas();
                } else {
                    this.mostrarNotificacion('Error: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al guardar revisión:', error);
                let mensaje = 'Error al guardar revisión. Por favor, intente nuevamente.';
                
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            mensaje = 'Error del servidor: ' + response.message;
                        }
                    } catch (e) {
                        mensaje = 'Error de conexión: ' + xhr.statusText;
                    }
                }
                
                this.mostrarNotificacion(mensaje, 'error');
            }
        });
    }

    limpiarFiltros() {
        $('#filtros-form')[0].reset();
        this.cargarAsignaciones();
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        // Usar SweetAlert2 si está disponible, sino usar alert
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
function initCalidad() {
    if (typeof jQuery !== 'undefined') {
        $(document).ready(function() {
            new CalidadApp();
        });
    } else {
        setTimeout(initCalidad, 100);
    }
}

initCalidad();

