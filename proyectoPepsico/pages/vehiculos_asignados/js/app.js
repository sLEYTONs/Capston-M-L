// Aplicación para Vehículos Asignados
class VehiculosAsignadosApp {
    constructor() {
        this.dataTable = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/ejecutivo_ventas/scripts/s_ejecutivo_ventas.php';
        }
        return '../../app/model/ejecutivo_ventas/scripts/s_ejecutivo_ventas.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.inicializarDataTable();
        this.cargarVehiculos();
    }

    inicializarEventos() {
        $('#btn-actualizar-vehiculos').on('click', () => {
            this.cargarVehiculos();
        });

        $(document).on('click', '.btn-ver-detalles', (e) => {
            const vehiculoId = $(e.currentTarget).data('id');
            this.verDetalles(vehiculoId);
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#tabla-vehiculos-asignados').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay vehículos asignados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron vehículos",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[5, 'desc']],
            responsive: true,
            columns: [
                { data: 'Placa' },
                { data: 'Marca' },
                { data: 'Modelo' },
                { data: 'TipoVehiculo' },
                { 
                    data: 'Kilometraje',
                    render: (data) => data ? parseFloat(data).toLocaleString('es-ES') + ' km' : '0 km'
                },
                { 
                    data: 'FechaAsignacion',
                    render: (data) => data ? new Date(data).toLocaleDateString('es-ES', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    }) : 'N/A'
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data) => `
                        <button class="btn btn-sm btn-info btn-ver-detalles" data-id="${data.ID}" title="Ver Detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                    `
                }
            ]
        });
    }

    cargarVehiculos() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_vehiculos_asignados' },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    this.dataTable.clear();
                    this.dataTable.rows.add(response.data);
                    this.dataTable.draw();
                    $('#total-vehiculos').text(response.data.length);
                } else {
                    this.mostrarNotificacion('Error al cargar vehículos: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar vehículos:', error);
                this.mostrarNotificacion('Error de conexión al cargar vehículos', 'error');
            }
        });
    }

    verDetalles(vehiculoId) {
        // Obtener datos del vehículo desde la tabla
        const rowData = this.dataTable.rows().data().toArray().find(v => v.ID == vehiculoId);
        
        if (!rowData) {
            this.mostrarNotificacion('Vehículo no encontrado', 'error');
            return;
        }

        const html = `
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Placa:</strong> ${rowData.Placa}</p>
                    <p><strong>Marca:</strong> ${rowData.Marca || 'N/A'}</p>
                    <p><strong>Modelo:</strong> ${rowData.Modelo || 'N/A'}</p>
                    <p><strong>Tipo:</strong> ${rowData.TipoVehiculo || 'N/A'}</p>
                    <p><strong>Año:</strong> ${rowData.Anio || 'N/A'}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Conductor:</strong> ${rowData.ConductorNombre || 'N/A'}</p>
                    <p><strong>Kilometraje:</strong> ${rowData.Kilometraje ? parseFloat(rowData.Kilometraje).toLocaleString('es-ES') + ' km' : '0 km'}</p>
                    <p><strong>Estado:</strong> <span class="badge bg-success">${rowData.Estado || 'Asignado'}</span></p>
                    <p><strong>Fecha Asignación:</strong> ${rowData.FechaAsignacion ? new Date(rowData.FechaAsignacion).toLocaleString('es-ES') : 'N/A'}</p>
                </div>
            </div>
            ${rowData.ObservacionesAsignacion ? `<div class="mt-3"><strong>Observaciones:</strong><p>${rowData.ObservacionesAsignacion}</p></div>` : ''}
        `;
        
        $('#detalles-vehiculo').html(html);
        const modal = new bootstrap.Modal(document.getElementById('modal-detalles-vehiculo'));
        modal.show();
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
let vehiculosAsignadosApp;
document.addEventListener('DOMContentLoaded', () => {
    vehiculosAsignadosApp = new VehiculosAsignadosApp();
});
