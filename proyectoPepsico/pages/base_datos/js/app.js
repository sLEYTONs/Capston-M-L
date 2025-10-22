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
        
        // Exportación
        $('#export-csv-completo').click(() => this.exportarCSVCompleto());
        $('#export-excel-completo').click(() => this.exportarExcelCompleto());
        $('#export-json-completo').click(() => this.exportarJSONCompleto());
        $('#export-vehiculos').click(() => this.exportarVehiculos());
        $('#export-marcas').click(() => this.exportarMarcas());
        $('#export-empresas').click(() => this.exportarEmpresas());
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
        $('#marcas-unicas').text(data.marcasUnicas.toLocaleString());
        $('#empresas-registradas').text(data.empresasRegistradas.toLocaleString());
    }

    inicializarDataTables() {
        // Tabla de Vehículos
        this.dataTables.vehiculos = $('#vehiculos-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '../app/model/base_datos/scripts/s_base_datos.php',
                type: 'POST',
                data: { action: 'obtenerVehiculos' }
            },
            columns: [
                { data: 'Placa' },
                { data: 'MarcaModelo' },
                { data: 'ConductorNombre' },
                { data: 'EmpresaNombre' },
                { 
                    data: 'Estado',
                    render: (data) => {
                        const badgeClass = data === 'active' ? 'badge-activo' : 'badge-inactivo';
                        const texto = data === 'active' ? 'Activo' : 'Inactivo';
                        return `<span class="badge badge-estado ${badgeClass}">${texto}</span>`;
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
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
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
        // Cargar empresas
        $.ajax({
            url: '../app/model/base_datos/scripts/s_base_datos.php',
            type: 'POST',
            data: { action: 'obtenerEmpresas' },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    const select = $('#filter-company');
                    select.empty().append('<option value="">Todas las empresas</option>');
                    response.data.forEach(empresa => {
                        select.append(`<option value="${empresa}">${empresa}</option>`);
                    });
                }
            }
        });

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
            empresa: $('#filter-company').val(),
            marca: $('#filter-brand').val()
        };

        this.dataTables.vehiculos.ajax.url('../app/model/base_datos/scripts/s_base_datos.php?action=obtenerVehiculos&' + $.param(filtros)).load();
        this.mostrarNotificacion('Filtros aplicados correctamente', 'success');
    }

    limpiarFiltros() {
        $('#global-search').val('');
        $('#filter-status').val('');
        $('#filter-company').val('');
        $('#filter-brand').val('');
        this.dataTables.vehiculos.ajax.url('../app/model/base_datos/scripts/s_base_datos.php?action=obtenerVehiculos').load();
        this.mostrarNotificacion('Filtros limpiados', 'info');
    }

    actualizarTablaVehiculos() {
        this.dataTables.vehiculos.ajax.reload();
        this.cargarEstadisticas();
        this.mostrarNotificacion('Datos actualizados', 'success');
    }

    onTabChange(tab) {
        const tabId = $(tab).attr('data-bs-target').substring(1);
        
        switch(tabId) {
            case 'marcas':
                this.cargarDatosMarcas();
                break;
            case 'empresas':
                this.cargarDatosEmpresas();
                break;
            case 'conductores':
                this.cargarDatosConductores();
                break;
            case 'graficos':
                this.inicializarGraficos();
                break;
        }
    }

    cargarDatosMarcas() {
        $.ajax({
            url: '../app/model/base_datos/scripts/s_base_datos.php',
            type: 'POST',
            data: { action: 'obtenerAnalisisMarcas' },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.mostrarDatosMarcas(response.data);
                    this.crearGraficoMarcas(response.data);
                }
            }
        });
    }

    mostrarDatosMarcas(datos) {
        // Implementar lógica para mostrar datos de marcas
        console.log('Datos de marcas:', datos);
    }

    crearGraficoMarcas(datos) {
        const ctx = document.getElementById('marcas-chart').getContext('2d');
        
        if (this.charts.marcas) {
            this.charts.marcas.destroy();
        }

        this.charts.marcas = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: datos.map(item => item.Marca),
                datasets: [{
                    data: datos.map(item => item.Total),
                    backgroundColor: [
                        '#004B93', '#0066CC', '#0080FF', '#3399FF', '#66B2FF',
                        '#99CCFF', '#CCE5FF', '#E6F2FF', '#FF6B6B', '#4ECDC4'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    inicializarGraficos() {
        this.cargarGraficos();
    }

    cargarGraficos() {
        $.ajax({
            url: '../app/model/base_datos/scripts/s_base_datos.php',
            type: 'POST',
            data: { action: 'obtenerDatosGraficos' },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.crearTodosLosGraficos(response.data);
                }
            }
        });
    }

    crearTodosLosGraficos(datos) {
        this.crearGrafico('chart-marcas', 'doughnut', datos.marcas);
        this.crearGrafico('chart-empresas', 'bar', datos.empresas);
        this.crearGrafico('chart-estados', 'pie', datos.estados);
        this.crearGrafico('chart-mensual', 'line', datos.mensual);
    }

    crearGrafico(canvasId, type, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }

        this.charts[canvasId] = new Chart(ctx, {
            type: type,
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
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
                        <tr><td><strong>Placa:</strong></td><td>${vehiculo.Placa}</td></tr>
                        <tr><td><strong>Marca/Modelo:</strong></td><td>${vehiculo.Marca} ${vehiculo.Modelo}</td></tr>
                        <tr><td><strong>Color:</strong></td><td>${vehiculo.Color}</td></tr>
                        <tr><td><strong>Año:</strong></td><td>${vehiculo.Anio}</td></tr>
                        <tr><td><strong>Tipo:</strong></td><td>${vehiculo.TipoVehiculo}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Información del Conductor</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Nombre:</strong></td><td>${vehiculo.ConductorNombre}</td></tr>
                        <tr><td><strong>Cédula:</strong></td><td>${vehiculo.ConductorCedula}</td></tr>
                        <tr><td><strong>Teléfono:</strong></td><td>${vehiculo.ConductorTelefono}</td></tr>
                        <tr><td><strong>Licencia:</strong></td><td>${vehiculo.Licencia}</td></tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Información de la Empresa</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Empresa:</strong></td><td>${vehiculo.EmpresaNombre}</td></tr>
                        <tr><td><strong>Código:</strong></td><td>${vehiculo.EmpresaCodigo}</td></tr>
                        <tr><td><strong>Propósito:</strong></td><td>${vehiculo.Proposito}</td></tr>
                        <tr><td><strong>Área:</strong></td><td>${vehiculo.Area}</td></tr>
                    </table>
                </div>
            </div>
        `;
    }

    // Métodos de exportación
    exportarCSVCompleto() {
        window.open('base_datos/scripts/s_base_datos.php?action=exportarCSV', '_blank');
        this.mostrarNotificacion('Exportando CSV completo...', 'info');
    }

    exportarExcelCompleto() {
        window.open('base_datos/scripts/s_base_datos.php?action=exportarExcel', '_blank');
        this.mostrarNotificacion('Exportando Excel completo...', 'info');
    }

    exportarJSONCompleto() {
        window.open('base_datos/scripts/s_base_datos.php?action=exportarJSON', '_blank');
        this.mostrarNotificacion('Exportando JSON completo...', 'info');
    }

    exportarVehiculos() {
        window.open('base_datos/scripts/s_base_datos.php?action=exportarVehiculos', '_blank');
        this.mostrarNotificacion('Exportando datos de vehículos...', 'info');
    }

    exportarMarcas() {
        window.open('base_datos/scripts/s_base_datos.php?action=exportarMarcas', '_blank');
        this.mostrarNotificacion('Exportando datos de marcas...', 'info');
    }

    exportarEmpresas() {
        window.open('base_datos/scripts/s_base_datos.php?action=exportarEmpresas', '_blank');
        this.mostrarNotificacion('Exportando datos de empresas...', 'info');
    }

    exportarConductores() {
        window.open('base_datos/scripts/s_base_datos.php?action=exportarConductores', '_blank');
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