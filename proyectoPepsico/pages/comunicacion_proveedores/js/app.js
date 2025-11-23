// Aplicación para Comunicación con Proveedores
class ComunicacionProveedores {
    constructor() {
        this.dataTable = null;
        this.proveedorSeleccionado = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/comunicacion_proveedores/scripts/s_comunicacion_proveedores.php';
        }
        return '../../app/model/comunicacion_proveedores/scripts/s_comunicacion_proveedores.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.inicializarDataTable();
        this.cargarProveedores();
    }

    inicializarEventos() {
        $('#btn-nuevo-proveedor').on('click', () => {
            this.abrirModalProveedor();
        });

        $('#form-comunicacion').on('submit', (e) => {
            e.preventDefault();
            this.enviarComunicacion();
        });

        $('#form-proveedor').on('submit', (e) => {
            e.preventDefault();
            this.guardarProveedor();
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#tabla-comunicaciones').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            responsive: true
        });
    }

    cargarProveedores() {
        console.log('Cargando proveedores...');
    }

    abrirModalProveedor() {
        $('#modal-proveedor').modal('show');
    }

    enviarComunicacion() {
        console.log('Enviando comunicación...');
    }

    guardarProveedor() {
        console.log('Guardando proveedor...');
    }
}

// Inicializar cuando el DOM esté listo
let comunicacionProveedores;
document.addEventListener('DOMContentLoaded', () => {
    comunicacionProveedores = new ComunicacionProveedores();
});

