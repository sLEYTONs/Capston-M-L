// Aplicación para Reportes Semanales
class ReportesSemanales {
    constructor() {
        this.dataTableVehiculos = null;
        this.dataTableGastos = null;
        this.dataTableRepuestos = null;
        this.baseUrl = this.getBaseUrl();
        this.semanaActual = null;
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/reportes_semanales/scripts/s_reportes_semanales.php';
        }
        return '../../app/model/reportes_semanales/scripts/s_reportes_semanales.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.inicializarDataTables();
        this.establecerSemanaActual();
    }

    inicializarEventos() {
        $('#btn-generar-reporte').on('click', () => {
            this.generarReporte();
        });

        $('#btn-exportar-excel').on('click', () => {
            this.exportarExcel();
        });
    }

    establecerSemanaActual() {
        const hoy = new Date();
        const año = hoy.getFullYear();
        const inicioAño = new Date(año, 0, 1);
        const dias = Math.floor((hoy - inicioAño) / (24 * 60 * 60 * 1000));
        const semana = Math.ceil((dias + inicioAño.getDay() + 1) / 7);
        const semanaFormato = `${año}-W${String(semana).padStart(2, '0')}`;
        $('#semana-reporte').val(semanaFormato);
        this.semanaActual = semanaFormato;
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

        // Tabla Vehículos
        this.dataTableVehiculos = $('#tabla-vehiculos-reporte').DataTable({
            language: languageConfig,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[2, 'desc']],
            responsive: true,
            data: [],
            columns: [
                { data: 'Placa' },
                { 
                    data: null,
                    render: (data) => `${data.Marca || ''} ${data.Modelo || ''}`.trim() || '-'
                },
                { 
                    data: 'FechaIngreso',
                    render: (data) => {
                        if (!data) return '-';
                        return new Date(data).toLocaleDateString('es-ES');
                    }
                },
                { 
                    data: 'FechaSalida',
                    render: (data) => {
                        if (!data) return '-';
                        return new Date(data).toLocaleDateString('es-ES');
                    }
                },
                { data: 'DiasEnTaller' },
                { 
                    data: 'Estado',
                    render: (data) => {
                        const badges = {
                            'Completado': '<span class="badge bg-success">Completado</span>',
                            'En Proceso': '<span class="badge bg-info">En Proceso</span>',
                            'Asignado': '<span class="badge bg-warning">Asignado</span>'
                        };
                        return badges[data] || data || '-';
                    }
                },
                { data: 'Servicio' }
            ]
        });

        // Tabla Gastos
        this.dataTableGastos = $('#tabla-gastos-reporte').DataTable({
            language: languageConfig,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            responsive: true,
            data: [],
            columns: [
                { 
                    data: 'Fecha',
                    render: (data) => {
                        if (!data) return '-';
                        return new Date(data).toLocaleDateString('es-ES');
                    }
                },
                { data: 'Vehiculo' },
                { 
                    data: 'Tipo',
                    render: (data) => {
                        const badges = {
                            'Interno': '<span class="badge bg-primary">Interno</span>',
                            'Externo': '<span class="badge bg-warning">Externo</span>'
                        };
                        return badges[data] || data || '-';
                    }
                },
                { data: 'Concepto' },
                { 
                    data: 'Costo',
                    render: (data) => `$${parseFloat(data || 0).toFixed(2)}`
                }
            ]
        });

        // Tabla Repuestos
        this.dataTableRepuestos = $('#tabla-repuestos-reporte').DataTable({
            language: languageConfig,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[4, 'desc']],
            responsive: true,
            data: [],
            columns: [
                { data: 'Codigo' },
                { data: 'Nombre' },
                { data: 'Cantidad' },
                { 
                    data: 'PrecioUnitario',
                    render: (data) => `$${parseFloat(data || 0).toFixed(2)}`
                },
                { 
                    data: 'Total',
                    render: (data) => `$${parseFloat(data || 0).toFixed(2)}`
                },
                { data: 'Vehiculo' }
            ]
        });
    }

    generarReporte() {
        const semana = $('#semana-reporte').val();
        if (!semana) {
            this.mostrarNotificacion('Por favor, seleccione una semana', 'error');
            return;
        }

        const btnGenerar = $('#btn-generar-reporte');
        const textoOriginal = btnGenerar.html();
        btnGenerar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Generando...');

        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: {
                accion: 'generar_reporte_semanal',
                semana: semana
            },
            dataType: 'json',
            success: (response) => {
                btnGenerar.prop('disabled', false).html(textoOriginal);
                
                if (response.status === 'success' && response.data) {
                    this.mostrarReporte(response.data);
                    this.semanaActual = semana;
                } else {
                    this.mostrarNotificacion('Error: ' + (response.message || 'Error al generar el reporte'), 'error');
                }
            },
            error: (xhr, status, error) => {
                btnGenerar.prop('disabled', false).html(textoOriginal);
                this.mostrarNotificacion('Error de conexión. Por favor, intente nuevamente.', 'error');
            }
        });
    }

    mostrarReporte(datos) {
        // Mostrar resumen
        $('#vehiculos-atendidos').text(datos.resumen?.vehiculos_atendidos || 0);
        $('#total-gastos').text('$' + parseFloat(datos.resumen?.total_gastos || 0).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#repuestos-utilizados').text(datos.resumen?.repuestos_utilizados || 0);
        $('#tiempo-promedio').text((datos.resumen?.tiempo_promedio || 0) + ' días');
        $('#resumen-reporte').show();

        // Cargar datos en tablas
        if (this.dataTableVehiculos) {
            this.dataTableVehiculos.clear();
            if (datos.vehiculos && datos.vehiculos.length > 0) {
                this.dataTableVehiculos.rows.add(datos.vehiculos);
            }
            this.dataTableVehiculos.draw();
        }

        if (this.dataTableGastos) {
            this.dataTableGastos.clear();
            if (datos.gastos && datos.gastos.length > 0) {
                this.dataTableGastos.rows.add(datos.gastos);
            }
            this.dataTableGastos.draw();
        }

        if (this.dataTableRepuestos) {
            this.dataTableRepuestos.clear();
            if (datos.repuestos && datos.repuestos.length > 0) {
                this.dataTableRepuestos.rows.add(datos.repuestos);
            }
            this.dataTableRepuestos.draw();
        }

        $('#detalles-reporte').show();
    }

    exportarExcel() {
        if (!this.semanaActual) {
            this.mostrarNotificacion('Por favor, genere un reporte primero', 'error');
            return;
        }

        window.location.href = this.baseUrl + '?accion=exportar_excel&semana=' + encodeURIComponent(this.semanaActual);
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
}

// Inicializar cuando el DOM esté listo
let reportesSemanales;
document.addEventListener('DOMContentLoaded', () => {
    reportesSemanales = new ReportesSemanales();
});
