class VehiculosAgendados {
    constructor() {
        this.table = null;
        this.fechaActual = new Date().toISOString().split('T')[0];
        this.baseUrl = this.getBaseUrl();
        this.vehiculosData = []; // Almacenar datos de vehículos para el modal
        this.inicializar();
    }

    getBaseUrl() {
        // Obtener la ruta actual y construir la ruta al script
        const currentPath = window.location.pathname;
        
        // Si estamos en pages/vehiculos_agendados.php, construir la ruta relativa
        if (currentPath.includes('/pages/')) {
            // Obtener la parte antes de /pages/
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/control_ingreso/scripts/s_control_ingreso.php';
        }
        
        // Fallback: ruta relativa desde pages/vehiculos_agendados/js/
        // Subimos 2 niveles: ../ -> vehiculos_agendados/, ../../ -> pages/, luego app/
        return '../../app/model/control_ingreso/scripts/s_control_ingreso.php';
    }

    inicializar() {
        this.inicializarDataTable();
        this.inicializarEventos();
        this.cargarVehiculos();
    }

    inicializarEventos() {
        const fechaFiltro = document.getElementById('fecha-filtro');
        const btnRefrescar = document.getElementById('btn-refrescar');

        if (fechaFiltro) {
            fechaFiltro.addEventListener('change', () => {
                this.cargarVehiculos();
            });
        }

        if (btnRefrescar) {
            btnRefrescar.addEventListener('click', () => {
                this.cargarVehiculos();
            });
        }
    }

    inicializarDataTable() {
        if ($.fn.DataTable) {
            this.table = $('#vehiculos-agendados-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                responsive: true,
                order: [[3, 'asc'], [4, 'asc']], // Ordenar por fecha y hora
                pageLength: 25,
                columnDefs: [
                    { orderable: false, targets: 7 } // Columna de acciones no ordenable
                ]
            });
        }
    }

    cargarVehiculos() {
        const fechaFiltro = document.getElementById('fecha-filtro');
        const fecha = fechaFiltro ? fechaFiltro.value : this.fechaActual;

        const formData = new FormData();
        formData.append('action', 'obtenerVehiculosAgendados');
        formData.append('fecha', fecha);

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                this.vehiculosData = data.data; // Guardar datos para el modal
                this.mostrarVehiculos(data.data);
            } else {
                this.mostrarError(data.message || 'Error al cargar vehículos agendados');
            }
        })
        .catch(error => {
            console.error('Error al cargar vehículos:', error);
            this.mostrarError('Error de conexión al cargar vehículos agendados');
        });
    }

    mostrarVehiculos(vehiculos) {
        const tbody = document.querySelector('#vehiculos-agendados-table tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (vehiculos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay vehículos agendados para la fecha seleccionada</p>
                    </td>
                </tr>
            `;
            return;
        }

        vehiculos.forEach(vehiculo => {
            const row = document.createElement('tr');
            
            // Formatear fecha
            const fechaAgenda = new Date(vehiculo.FechaAgenda);
            const fechaFormateada = fechaAgenda.toLocaleDateString('es-ES', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

            // Formatear hora
            const horaInicio = vehiculo.HoraInicio ? vehiculo.HoraInicio.substring(0, 5) : 'N/A';
            const horaFin = vehiculo.HoraFin ? vehiculo.HoraFin.substring(0, 5) : '';
            const horaFormateada = horaFin ? `${horaInicio} - ${horaFin}` : horaInicio;

            // Estado badge
            const estadoClass = vehiculo.EstadoIngreso === 'Ingresado' ? 'success' : 'warning';
            const estadoIcon = vehiculo.EstadoIngreso === 'Ingresado' ? 'fa-check-circle' : 'fa-clock';
            const estadoTexto = vehiculo.EstadoIngreso === 'Ingresado' ? 'Ingresado' : 'Pendiente';

            // Vehículo completo
            const vehiculoCompleto = `${vehiculo.Marca || ''} ${vehiculo.Modelo || ''} ${vehiculo.TipoVehiculo || ''}`.trim() || 'N/A';

            row.innerHTML = `
                <td><strong>${vehiculo.Placa}</strong></td>
                <td>${vehiculoCompleto}</td>
                <td>${vehiculo.ConductorNombre || 'N/A'}</td>
                <td>${fechaFormateada}</td>
                <td><i class="fas fa-clock me-1"></i>${horaFormateada}</td>
                <td>${vehiculo.Proposito || 'N/A'}</td>
                <td>
                    <span class="badge bg-${estadoClass}">
                        <i class="fas ${estadoIcon} me-1"></i>${estadoTexto}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="vehiculosAgendados.verDetalles(${vehiculo.SolicitudID})" title="Ver detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(row);
        });

        // Redibujar DataTable si existe
        if (this.table) {
            this.table.clear();
            this.table.rows.add(Array.from(tbody.querySelectorAll('tr')));
            this.table.draw();
        }
    }

    verDetalles(solicitudId) {
        const modalElement = document.getElementById('detalles-modal');
        const content = document.getElementById('detalles-content');

        if (!modalElement || !content) return;

        content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-2">Cargando detalles...</p></div>';
        
        // Mostrar modal usando Bootstrap
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        // Buscar el vehículo en los datos cargados
        let vehiculo = null;
        if (this.vehiculosData) {
            vehiculo = this.vehiculosData.find(v => v.SolicitudID == solicitudId);
        }

        if (!vehiculo) {
            content.innerHTML = '<p class="text-danger">No se pudieron cargar los detalles del vehículo.</p>';
            return;
        }

        // Formatear fecha
        const fechaAgenda = new Date(vehiculo.FechaAgenda);
        const fechaFormateada = fechaAgenda.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Formatear hora
        const horaInicio = vehiculo.HoraInicio ? vehiculo.HoraInicio.substring(0, 5) : 'N/A';
        const horaFin = vehiculo.HoraFin ? vehiculo.HoraFin.substring(0, 5) : '';
        const horaFormateada = horaFin ? `${horaInicio} - ${horaFin}` : horaInicio;

        // Vehículo completo
        const vehiculoCompleto = `${vehiculo.Marca || ''} ${vehiculo.Modelo || ''} ${vehiculo.TipoVehiculo || ''}`.trim() || 'N/A';

        // Estado badge
        const estadoClass = vehiculo.EstadoIngreso === 'Ingresado' ? 'success' : 'warning';
        const estadoTexto = vehiculo.EstadoIngreso === 'Ingresado' ? 'Ingresado' : 'Pendiente Ingreso';

        content.innerHTML = `
            <div class="detalles-vehiculo">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-car me-2"></i>Información del Vehículo
                                </h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th width="40%" class="text-muted">Placa:</th>
                                        <td><strong class="text-dark">${vehiculo.Placa}</strong></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Vehículo:</th>
                                        <td>${vehiculoCompleto}</td>
                                    </tr>
                                    ${vehiculo.Anio ? `
                                    <tr>
                                        <th class="text-muted">Año:</th>
                                        <td>${vehiculo.Anio}</td>
                                    </tr>
                                    ` : ''}
                                    <tr>
                                        <th class="text-muted">Conductor:</th>
                                        <td>${vehiculo.ConductorNombre || 'N/A'}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-calendar me-2"></i>Información de Agenda
                                </h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th width="40%" class="text-muted">Fecha:</th>
                                        <td><i class="fas fa-calendar-alt me-1 text-primary"></i>${fechaFormateada}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Hora:</th>
                                        <td><i class="fas fa-clock me-1 text-primary"></i>${horaFormateada}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Estado:</th>
                                        <td><span class="badge bg-${estadoClass}">${estadoTexto}</span></td>
                                    </tr>
                                    ${vehiculo.SupervisorNombre ? `
                                    <tr>
                                        <th class="text-muted">Supervisor:</th>
                                        <td><i class="fas fa-user-tie me-1 text-primary"></i>${vehiculo.SupervisorNombre}</td>
                                    </tr>
                                    ` : ''}
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-info-circle me-2"></i>Propósito
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">${vehiculo.Proposito || 'No especificado'}</p>
                            </div>
                        </div>
                    </div>
                    ${vehiculo.Observaciones ? `
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-sticky-note me-2"></i>Observaciones
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">${vehiculo.Observaciones}</p>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    mostrarError(mensaje) {
        const tbody = document.querySelector('#vehiculos-agendados-table tbody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                        <p>${mensaje}</p>
                    </td>
                </tr>
            `;
        }
    }
}

// Inicializar cuando el DOM esté listo
let vehiculosAgendados;
document.addEventListener('DOMContentLoaded', () => {
    vehiculosAgendados = new VehiculosAgendados();
});

