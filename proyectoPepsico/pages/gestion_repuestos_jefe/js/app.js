// Aplicación para Gestión de Repuestos con Jefe de Taller
class GestionRepuestosJefe {
    constructor() {
        this.dataTablePendientes = null;
        this.dataTableComunicaciones = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/gestion_repuestos_jefe/scripts/s_gestion_jefe.php';
        }
        return '../../app/model/gestion_repuestos_jefe/scripts/s_gestion_jefe.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.inicializarDataTables();
        this.cargarDatos();
    }

    inicializarEventos() {
        $('#form-solicitud-jefe').on('submit', (e) => {
            e.preventDefault();
            this.enviarSolicitud();
        });

        $('#btn-generar-reporte').on('click', () => {
            this.generarReporte();
        });
    }

    inicializarDataTables() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTablePendientes = $('#tabla-solicitudes-pendientes').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            responsive: true
        });

        this.dataTableComunicaciones = $('#tabla-comunicaciones-jefe').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            responsive: true
        });
    }

    cargarDatos() {
        this.cargarEstadisticas();
        this.cargarSolicitudesPendientes();
        this.cargarComunicaciones();
    }

    cargarEstadisticas() {
        console.log('Cargando estadísticas...');
    }

    cargarSolicitudesPendientes() {
        console.log('Cargando solicitudes pendientes...');
    }

    cargarComunicaciones() {
        console.log('Cargando comunicaciones...');
    }

    enviarSolicitud() {
        console.log('Enviando solicitud al jefe...');
    }

    generarReporte() {
        console.log('Generando reporte...');
    }
}

// Inicializar cuando el DOM esté listo
let gestionJefe;
document.addEventListener('DOMContentLoaded', () => {
    gestionJefe = new GestionRepuestosJefe();
});

