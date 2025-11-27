class BaseDatosApp {
    constructor() {
        this.charts = {};
        this.dataTables = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.cargarEstadisticas();
        this.inicializarDataTables();
        this.cargarFiltros();
    }

    bindEvents() {
        // Filtros
        $('#apply-filters').click(() => this.aplicarFiltros());
        $('#reset-filters').click(() => this.limpiarFiltros());
        
        // Actualización
        $('#refresh-vehicles').click(() => this.actualizarTablaVehiculos());
        $('#refresh-agendas').click(() => this.actualizarTablaAgendas());
        $('#refresh-repuestos').click(() => this.actualizarTablaRepuestos());
        $('#refresh-usuarios').click(() => this.actualizarTablaUsuarios());
        $('#refresh-conductores').click(() => this.actualizarTablaConductores());
        
        // Exportación
        $('#export-csv-completo').click(() => this.exportarCSVCompleto());
        $('#export-excel-completo').click(() => this.exportarExcelCompleto());
        $('#export-json-completo').click(() => this.exportarJSONCompleto());
        $('#export-vehiculos').click(() => this.exportarVehiculos());
        $('#export-conductores').click(() => this.exportarConductores());
        
        // Eventos de pestañas
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', (e) => {
            this.onTabChange(e.target);
        });
    }

    cargarEstadisticas() {
        $.ajax({
            url: '../app/model/base_datos/scripts/s_base_datos.php',
            type: 'POST',
            data: { action: 'obtenerEstadisticas' },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.mostrarEstadisticas(response.data);
                }
            },
            error: () => this.mostrarNotificacion('Error al cargar estadísticas', 'error')
        });
    }

    mostrarEstadisticas(data) {
        $('#total-registros').text(data.totalRegistros.toLocaleString());
        $('#vehiculos-activos').text(data.vehiculosActivos.toLocaleString());
        $('#repuestos-stock-bajo').text((data.repuestosStockBajo || 0).toLocaleString());
        
        // Resaltar si hay repuestos con stock bajo
        if (data.repuestosStockBajo > 0) {
            $('#repuestos-stock-bajo').parent().parent().addClass('stat-card-warning');
        }
    }

    inicializarDataTables() {
        // Tabla de Vehículos (ahora trae todos los vehículos ingresados)
        this.dataTables.vehiculos = $('#vehiculos-table').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '../app/model/base_datos/scripts/s_base_datos.php',
                type: 'POST',
                data: { action: 'obtenerVehiculos' },
                dataSrc: 'data'
            },
            columns: [
                { data: 'ID' },
                { data: 'Placa' },
                { data: 'MarcaModelo' },
                { data: 'ConductorNombre' },
                { 
                    data: 'Estado',
                    render: (data) => {
                        // Mapear estados a clases y textos
                        const estadoMap = {
                            'Ingresado': { class: 'badge-info', texto: 'Ingresado' },
                            'Asignado': { class: 'badge-warning', texto: 'Asignado' },
                            'En Proceso': { class: 'badge-primary', texto: 'En Proceso' },
                            'Completado': { class: 'badge-success', texto: 'Completado' },
                            'active': { class: 'badge-activo', texto: 'Activo' },  // Compatibilidad con datos antiguos
                            'inactive': { class: 'badge-inactivo', texto: 'Inactivo' }
                        };
                        
                        const estado = estadoMap[data] || { class: 'badge-secondary', texto: data || 'Desconocido' };
                        return `<span class="badge badge-estado ${estado.class}">${estado.texto}</span>`;
                    }
                },
                { 
                    data: 'FechaIngreso',
                    render: (data) => new Date(data).toLocaleDateString('es-ES')
                },
                {
                    data: null,
                    render: (data) => `
                        <button class="btn btn-sm btn-outline-primary btn-action ver-detalle" data-placa="${data.Placa}">
                            <i class="fas fa-eye"></i>
                        </button>
                    `,
                    orderable: false
                }
            ],
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
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            pageLength: 10,
            responsive: true
        });

        // Delegación de eventos para botones de acción
        $('#vehiculos-table').on('click', '.ver-detalle', (e) => {
            const placa = $(e.currentTarget).data('placa');
            this.mostrarDetalleVehiculo(placa);
        });
    }

    cargarFiltros() {
        // Cargar empresas - deshabilitado (columna eliminada)
        // $.ajax({...});

        // Cargar marcas
        $.ajax({
            url: '../app/model/base_datos/scripts/s_base_datos.php',
            type: 'POST',
            data: { action: 'obtenerMarcas' },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    const select = $('#filter-brand');
                    select.empty().append('<option value="">Todas las marcas</option>');
                    response.data.forEach(marca => {
                        select.append(`<option value="${marca}">${marca}</option>`);
                    });
                }
            }
        });
    }

    aplicarFiltros() {
        const filtros = {
            busqueda: $('#global-search').val(),
            estado: $('#filter-status').val(),
            // empresa: $('#filter-company').val(), // Columna eliminada
            marca: $('#filter-brand').val()
        };

        this.dataTables.vehiculos.ajax.url('../app/model/base_datos/scripts/s_base_datos.php?action=obtenerVehiculos&' + $.param(filtros)).load();
        this.mostrarNotificacion('Filtros aplicados correctamente', 'success');
    }

    limpiarFiltros() {
        $('#global-search').val('');
        $('#filter-status').val('');
        // $('#filter-company').val(''); // Columna eliminada
        $('#filter-brand').val('');
        this.dataTables.vehiculos.ajax.url('../app/model/base_datos/scripts/s_base_datos.php?action=obtenerVehiculos').load();
        this.mostrarNotificacion('Filtros limpiados', 'info');
    }

    actualizarTablaVehiculos() {
        this.dataTables.vehiculos.ajax.reload();
        this.cargarEstadisticas();
        this.mostrarNotificacion('Datos actualizados', 'success');
    }

    inicializarTablaAgendas() {
        if (this.dataTables.agendas) {
            return;
        }

        this.dataTables.agendas = $('#agendas-table').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '../app/model/base_datos/scripts/s_base_datos.php',
                type: 'POST',
                data: { action: 'obtenerAgendas' },
                dataSrc: 'data'
            },
            columns: [
                { data: 'ID' },
                { 
                    data: 'Fecha',
                    render: (data) => data ? new Date(data).toLocaleDateString('es-ES') : 'N/A'
                },
                { data: 'HoraInicio' },
                { data: 'HoraFin' },
                { 
                    data: 'Disponible',
                    render: (data) => {
                        return data == 1 
                            ? '<span class="badge badge-success">Disponible</span>'
                            : '<span class="badge badge-danger">No Disponible</span>';
                    }
                },
                { data: 'Observaciones' || 'N/A' }
            ],
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
            responsive: true
        });
    }

    inicializarTablaRepuestos() {
        if (this.dataTables.repuestos) {
            return;
        }

        this.dataTables.repuestos = $('#repuestos-table').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '../app/model/base_datos/scripts/s_base_datos.php',
                type: 'POST',
                data: { action: 'obtenerRepuestos' },
                dataSrc: 'data'
            },
            columns: [
                { data: 'ID' },
                { data: 'Nombre' },
                { data: 'Descripcion' || 'N/A' },
                { data: 'Stock' },
                { data: 'StockMinimo' },
                { 
                    data: 'Precio',
                    render: (data) => {
                        const precio = parseFloat(data) || 0;
                        return '$' + Math.round(precio).toLocaleString('es-CL');
                    }
                },
                { 
                    data: 'Estado',
                    render: (data) => {
                        return data === 'Activo' 
                            ? '<span class="badge badge-success">Activo</span>'
                            : '<span class="badge badge-danger">Inactivo</span>';
                    }
                }
            ],
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
            responsive: true
        });
    }

    inicializarTablaUsuarios() {
        if (this.dataTables.usuarios) {
            return;
        }

        this.dataTables.usuarios = $('#usuarios-table').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '../app/model/base_datos/scripts/s_base_datos.php',
                type: 'POST',
                data: { action: 'obtenerUsuarios' },
                dataSrc: 'data'
            },
            columns: [
                { data: 'ID' },
                { data: 'NombreUsuario' },
                { data: 'Correo' },
                { data: 'Rol' },
                { 
                    data: 'Estado',
                    render: (data) => {
                        return data == 1 
                            ? '<span class="badge badge-primary">Activo</span>'
                            : '<span class="badge badge-danger">Inactivo</span>';
                    }
                },
                { 
                    data: 'FechaCreacion',
                    render: (data) => data ? new Date(data).toLocaleDateString('es-ES') : 'N/A'
                },
                { 
                    data: 'UltimoAcceso',
                    render: (data) => {
                        if (data) {
                            return new Date(data).toLocaleString('es-ES');
                        }
                        return 'Nunca';
                    }
                }
            ],
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
            responsive: true
        });
    }

    inicializarTablaConductores() {
        if (this.dataTables.conductores) {
            return;
        }

        this.dataTables.conductores = $('#conductores-table').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '../app/model/base_datos/scripts/s_base_datos.php',
                type: 'POST',
                data: { action: 'obtenerAnalisisConductores' },
                dataSrc: 'data'
            },
            columns: [
                { data: 'ID' },
                { data: 'Nombre' },
                { data: 'Correo' },
                { 
                    data: 'Estado',
                    render: (data) => {
                        return data == 1 
                            ? '<span class="badge badge-primary">Activo</span>'
                            : '<span class="badge badge-danger">Inactivo</span>';
                    }
                },
                { data: 'Vehiculos' },
                { 
                    data: 'FechaCreacion',
                    render: (data) => data ? new Date(data).toLocaleDateString('es-ES') : 'N/A'
                },
                { 
                    data: 'UltimaVisita',
                    render: (data) => {
                        if (data) {
                            return new Date(data).toLocaleString('es-ES');
                        }
                        return 'Nunca';
                    }
                }
            ],
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
            responsive: true
        });
    }

    actualizarTablaAgendas() {
        if (this.dataTables.agendas) {
            this.dataTables.agendas.ajax.reload();
            this.mostrarNotificacion('Agendas actualizadas', 'success');
        }
    }

    actualizarTablaConductores() {
        if (this.dataTables.conductores) {
            this.dataTables.conductores.ajax.reload();
            this.mostrarNotificacion('Conductores actualizados', 'success');
        }
    }

    actualizarTablaRepuestos() {
        if (this.dataTables.repuestos) {
            this.dataTables.repuestos.ajax.reload();
            this.mostrarNotificacion('Repuestos actualizados', 'success');
        }
    }

    actualizarTablaUsuarios() {
        if (this.dataTables.usuarios) {
            this.dataTables.usuarios.ajax.reload();
            this.mostrarNotificacion('Usuarios actualizados', 'success');
        }
    }

    onTabChange(tab) {
        const tabId = $(tab).attr('data-bs-target').substring(1);
        
        switch(tabId) {
            case 'agendas':
                if (!this.dataTables.agendas) {
                    this.inicializarTablaAgendas();
                }
                break;
            case 'repuestos':
                if (!this.dataTables.repuestos) {
                    this.inicializarTablaRepuestos();
                }
                break;
            case 'usuarios':
                if (!this.dataTables.usuarios) {
                    this.inicializarTablaUsuarios();
                }
                break;
            case 'conductores':
                if (!this.dataTables.conductores) {
                    this.inicializarTablaConductores();
                }
                break;
        }
    }


    mostrarDetalleVehiculo(placa) {
        $.ajax({
            url: '../app/model/base_datos/scripts/s_base_datos.php',
            type: 'POST',
            data: { action: 'obtenerDetalleVehiculo', placa: placa },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    $('#detalle-vehiculo-content').html(this.generarHTMLDetalle(response.data));
                    $('#modalDetalleVehiculo').modal('show');
                } else {
                    this.mostrarNotificacion('Error al cargar detalles', 'error');
                }
            }
        });
    }

    generarHTMLDetalle(vehiculo) {
        return `
            <div class="row">
                <div class="col-md-6">
                    <h6>Información del Vehículo</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Placa:</strong></td><td>${vehiculo.Placa || '-'}</td></tr>
                        <tr><td><strong>Marca:</strong></td><td>${vehiculo.Marca || '-'}</td></tr>
                        <tr><td><strong>Modelo:</strong></td><td>${vehiculo.Modelo || '-'}</td></tr>
                        <tr><td><strong>Año:</strong></td><td>${vehiculo.Anio || '-'}</td></tr>
                        <tr><td><strong>Tipo:</strong></td><td>${vehiculo.TipoVehiculo || '-'}</td></tr>
                        <tr><td><strong>Kilometraje:</strong></td><td>${vehiculo.Kilometraje || '-'}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Información del Conductor</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Nombre:</strong></td><td>${vehiculo.ConductorNombre || '-'}</td></tr>
                    </table>
                    <h6 class="mt-3">Información de Ingreso</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Estado:</strong></td><td>${vehiculo.Estado || '-'}</td></tr>
                        <tr><td><strong>Fecha Ingreso:</strong></td><td>${vehiculo.FechaIngreso ? new Date(vehiculo.FechaIngreso).toLocaleString('es-ES') : '-'}</td></tr>
                        <tr><td><strong>Fecha Registro:</strong></td><td>${vehiculo.FechaRegistro ? new Date(vehiculo.FechaRegistro).toLocaleString('es-ES') : '-'}</td></tr>
                    </table>
                </div>
            </div>
        `;
    }

    // Métodos de exportación
    exportarCSVCompleto() {
        window.location.href = '../app/model/base_datos/scripts/s_base_datos.php?action=exportarCSV';
        this.mostrarNotificacion('Exportando CSV completo...', 'info');
    }

    exportarExcelCompleto() {
        window.location.href = '../app/model/base_datos/scripts/s_base_datos.php?action=exportarExcel';
        this.mostrarNotificacion('Exportando Excel completo...', 'info');
    }

    exportarJSONCompleto() {
        window.location.href = '../app/model/base_datos/scripts/s_base_datos.php?action=exportarJSON';
        this.mostrarNotificacion('Exportando JSON completo...', 'info');
    }

    exportarVehiculos() {
        window.location.href = '../app/model/base_datos/scripts/s_base_datos.php?action=exportarVehiculos';
        this.mostrarNotificacion('Exportando datos de vehículos...', 'info');
    }

    exportarConductores() {
        window.location.href = '../app/model/base_datos/scripts/s_base_datos.php?action=exportarConductores';
        this.mostrarNotificacion('Exportando datos de conductores...', 'info');
    }

    mostrarNotificacion(mensaje, tipo) {
        const notification = $('#notification');
        notification.removeClass('success error info show')
                   .addClass(`${tipo} show`)
                   .text(mensaje);

        setTimeout(() => {
            notification.removeClass('show');
        }, 3000);
    }
}

// Inicializar la aplicación cuando el documento esté listo
$(document).ready(function() {
    window.baseDatosApp = new BaseDatosApp();
});