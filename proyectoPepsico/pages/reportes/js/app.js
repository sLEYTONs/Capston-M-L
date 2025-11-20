class ReportesMantenimientos {
    constructor() {
        this.dataTable = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        if (!$('#reportes-table').length) {
            console.error('No se encontró la tabla reportes-table');
            return;
        }
        
        this.bindEvents();
        this.inicializarDataTable();
        this.cargarMecanicos();
        this.cargarReportes();
        this.cargarEstadisticas();
    }

    bindEvents() {
        $('#btn-generar-reporte').on('click', () => this.cargarReportes());
        $('#btn-limpiar-filtros').on('click', () => this.limpiarFiltros());
        $('#btn-exportar-excel').on('click', () => this.exportarExcel());
        
        // Ver detalles
        $(document).on('click', '.btn-ver-detalles', (e) => {
            const asignacionId = $(e.currentTarget).data('id');
            this.mostrarDetalles(asignacionId);
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#reportes-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            dom: '<"table-header"lf>rt<"table-footer"ip>',
            processing: true,
            serverSide: false,
            columns: [
                { data: 'AsignacionID' },
                { 
                    data: null,
                    render: (data) => {
                        return `${data.Marca} ${data.Modelo} ${data.Color || ''}`.trim();
                    }
                },
                { 
                    data: 'Placa',
                    render: (data) => `<span class="badge bg-info">${data}</span>`
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
                    data: 'TiempoMantenimientoFormateado',
                    render: (data) => data || '-'
                },
                { 
                    data: 'CostoRepuestos',
                    render: (data) => {
                        return `$${parseFloat(data || 0).toFixed(2)}`;
                    }
                },
                { 
                    data: 'CantidadRepuestos',
                    render: (data) => {
                        return `<span class="badge bg-secondary">${data || 0}</span>`;
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data) => {
                        return `
                            <button class="btn btn-sm btn-info btn-ver-detalles" 
                                    data-id="${data.AsignacionID}" 
                                    title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                        `;
                    }
                }
            ],
            order: [[0, 'desc']],
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
            mecanico_id: $('#filtro-mecanico').val(),
            estado: $('#filtro-estado').val()
        };
    }

    cargarReportes() {
        if (this.isLoading) return;

        this.isLoading = true;
        const filtros = this.obtenerFiltros();

        $.ajax({
            url: '../app/model/reportes/scripts/s_reportes.php',
            type: 'POST',
            data: {
                accion: 'obtener_reportes',
                ...filtros
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.dataTable.clear();
                    if (response.data && response.data.length > 0) {
                        this.dataTable.rows.add(response.data);
                    }
                    this.dataTable.draw();
                    this.cargarEstadisticas();
                } else {
                    this.mostrarNotificacion('Error al cargar reportes: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar reportes:', error);
                this.mostrarNotificacion('Error al cargar reportes. Por favor, intente nuevamente.', 'error');
            },
            complete: () => {
                this.isLoading = false;
            }
        });
    }

    cargarEstadisticas() {
        const filtros = this.obtenerFiltros();

        $.ajax({
            url: '../app/model/reportes/scripts/s_reportes.php',
            type: 'POST',
            data: {
                accion: 'obtener_estadisticas',
                ...filtros
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    $('#total-mantenimientos').text(response.total_mantenimientos || 0);
                    $('#completados').text(response.completados || 0);
                    $('#costo-total').text('$' + parseFloat(response.costo_total || 0).toFixed(2));
                    const tiempoPromedio = response.tiempo_promedio || 0;
                    $('#tiempo-promedio').text(tiempoPromedio.toFixed(2) + ' hrs');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar estadísticas:', error);
            }
        });
    }

    cargarMecanicos() {
        $.ajax({
            url: '../app/model/consulta/scripts/s_mecanicos.php',
            type: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    const select = $('#filtro-mecanico');
                    response.data.forEach(mecanico => {
                        select.append(`<option value="${mecanico.UsuarioID}">${mecanico.NombreUsuario}</option>`);
                    });
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar mecánicos:', error);
            }
        });
    }

    limpiarFiltros() {
        $('#filtro-fecha-inicio').val('');
        $('#filtro-fecha-fin').val('');
        $('#filtro-mecanico').val('');
        $('#filtro-estado').val('');
        this.cargarReportes();
    }

    mostrarDetalles(asignacionId) {
        // Obtener datos de la fila
        const row = this.dataTable.row((idx, data) => data.AsignacionID == asignacionId);
        const data = row.data();

        if (!data) return;

        // Construir HTML de detalles
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Información del Vehículo</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Placa:</strong></td><td>${data.Placa}</td></tr>
                        <tr><td><strong>Marca:</strong></td><td>${data.Marca}</td></tr>
                        <tr><td><strong>Modelo:</strong></td><td>${data.Modelo}</td></tr>
                        <tr><td><strong>Tipo:</strong></td><td>${data.TipoVehiculo}</td></tr>
                        <tr><td><strong>Color:</strong></td><td>${data.Color || 'N/A'}</td></tr>
                        <tr><td><strong>Conductor:</strong></td><td>${data.ConductorNombre || 'N/A'}</td></tr>
                        <tr><td><strong>Empresa:</strong></td><td>${data.EmpresaNombre || 'N/A'}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Información del Mantenimiento</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Mecánico:</strong></td><td>${data.MecanicoNombre}</td></tr>
                        <tr><td><strong>Fecha Asignación:</strong></td><td>${data.FechaAsignacion}</td></tr>
                        <tr><td><strong>Estado:</strong></td><td><span class="badge bg-${this.getEstadoColor(data.EstadoAsignacion)}">${data.EstadoAsignacion}</span></td></tr>
                        <tr><td><strong>Tiempo:</strong></td><td>${data.TiempoMantenimientoFormateado || 'En proceso'}</td></tr>
                        <tr><td><strong>Costo Repuestos:</strong></td><td>$${parseFloat(data.CostoRepuestos || 0).toFixed(2)}</td></tr>
                        <tr><td><strong>Cantidad Repuestos:</strong></td><td>${data.CantidadRepuestos || 0}</td></tr>
                    </table>
                </div>
            </div>
        `;

        if (data.Observaciones) {
            html += `
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6>Observaciones</h6>
                        <p>${data.Observaciones}</p>
                    </div>
                </div>
            `;
        }

        if (data.UltimoAvance) {
            html += `
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6>Último Avance</h6>
                        <p>${data.UltimoAvance}</p>
                    </div>
                </div>
            `;
        }

        $('#detalles-content').html(html);
        $('#detallesModal').modal('show');
    }

    getEstadoColor(estado) {
        const colores = {
            'Asignado': 'warning',
            'En Proceso': 'primary',
            'Completado': 'success'
        };
        return colores[estado] || 'secondary';
    }

    exportarExcel() {
        const filtros = this.obtenerFiltros();
        const params = new URLSearchParams({
            accion: 'obtener_reportes',
            ...filtros
        });
        
        window.open(`../app/model/reportes/scripts/s_reportes.php?${params.toString()}&export=excel`, '_blank');
    }

    mostrarNotificacion(mensaje, tipo) {
        // Usar SweetAlert o similar
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: tipo === 'error' ? 'error' : 'success',
                title: tipo === 'error' ? 'Error' : 'Éxito',
                text: mensaje,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert(mensaje);
        }
    }
}

// Inicializar cuando el documento esté listo y jQuery esté disponible
(function() {
    function initReportes() {
        if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
            $(document).ready(function() {
                new ReportesMantenimientos();
            });
        } else {
            // Esperar a que jQuery esté disponible
            setTimeout(initReportes, 100);
        }
    }
    
    // Intentar inicializar inmediatamente si jQuery ya está cargado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReportes);
    } else {
        initReportes();
    }
})();

