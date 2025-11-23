// Aplicación para Registro de Insumos por Vehículo
class RegistroInsumosVehiculo {
    constructor() {
        this.dataTable = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/registro_insumos_vehiculo/scripts/s_registro_insumos.php';
        }
        return '../../app/model/registro_insumos_vehiculo/scripts/s_registro_insumos.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.inicializarDataTable();
        this.cargarDatos();
    }

    inicializarEventos() {
        $('#btn-nuevo-registro').on('click', () => {
            this.abrirModalRegistro();
        });

        $('#btn-filtrar').on('click', () => {
            this.filtrarRegistros();
        });

        $('#form-registro-insumos').on('submit', (e) => {
            e.preventDefault();
            this.guardarRegistro();
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#tabla-insumos').DataTable({
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
        console.log('Cargando datos...');
    }

    abrirModalRegistro() {
        $('#modal-registro').modal('show');
    }

    filtrarRegistros() {
        console.log('Filtrando registros...');
    }

    guardarRegistro() {
        console.log('Guardando registro...');
    }
}

// Inicializar cuando el DOM esté listo
let registroInsumos;
document.addEventListener('DOMContentLoaded', () => {
    registroInsumos = new RegistroInsumosVehiculo();
});

