// Aplicación para Recepción y Entrega de Repuestos
class RecepcionEntregaRepuestos {
    constructor() {
        this.dataTable = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/recepcion_entrega_repuestos/scripts/s_recepcion_entrega.php';
        }
        return '../../app/model/recepcion_entrega_repuestos/scripts/s_recepcion_entrega.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.inicializarDataTable();
        this.cargarDatos();
    }

    inicializarEventos() {
        $('#form-recepcion').on('submit', (e) => {
            e.preventDefault();
            this.registrarRecepcion();
        });

        $('#form-entrega').on('submit', (e) => {
            e.preventDefault();
            this.registrarEntrega();
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#tabla-historial').DataTable({
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
        // Cargar proveedores, vehículos, mecánicos, etc.
        console.log('Cargando datos...');
    }

    registrarRecepcion() {
        console.log('Registrando recepción...');
    }

    registrarEntrega() {
        console.log('Registrando entrega...');
    }
}

// Inicializar cuando el DOM esté listo
let recepcionEntrega;
document.addEventListener('DOMContentLoaded', () => {
    recepcionEntrega = new RecepcionEntregaRepuestos();
});

