class EstadoSolicitudesRepuestos {
    constructor() {
        this.solicitudes = [];
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/gestion_pausas_repuestos/scripts/s_gestion_pausas_repuestos.php';
        }
        return '../../app/model/gestion_pausas_repuestos/scripts/s_gestion_pausas_repuestos.php';
    }

    inicializar() {
        this.inicializarDataTable();
        this.inicializarEventos();
        this.cargarSolicitudes();
    }

    inicializarDataTable() {
        if ($.fn.DataTable) {
            $('#solicitudes-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                responsive: true,
                order: [[6, 'desc']],
                pageLength: 10,
                columnDefs: [
                    { orderable: false, targets: 7 }
                ]
            });
        }
    }

    inicializarEventos() {
        const btnRefrescar = document.getElementById('btn-refrescar');
        if (btnRefrescar) {
            btnRefrescar.addEventListener('click', () => {
                this.cargarSolicitudes();
            });
        }
    }

    cargarSolicitudes() {
        fetch(this.baseUrl + '?action=obtenerSolicitudes', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                this.solicitudes = data.data;
                this.mostrarSolicitudes(data.data);
                this.actualizarResumen(data.data);
            }
        })
        .catch(error => {
            console.error('Error al cargar solicitudes:', error);
            this.mostrarError('Error de conexión al cargar solicitudes');
        });
    }

    mostrarSolicitudes(solicitudes) {
        const tbody = document.querySelector('#solicitudes-table tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (solicitudes.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay solicitudes de repuestos</p>
                    </td>
                </tr>
            `;
            return;
        }

        solicitudes.forEach(solicitud => {
            const row = document.createElement('tr');
            const estadoClass = this.getEstadoClass(solicitud.Estado);
            const urgenciaClass = this.getUrgenciaClass(solicitud.Urgencia);

            row.innerHTML = `
                <td><strong>#${solicitud.ID}</strong></td>
                <td>${solicitud.RepuestoNombre} (${solicitud.RepuestoCodigo})</td>
                <td>${solicitud.Cantidad}</td>
                <td><span class="badge ${urgenciaClass}">${solicitud.Urgencia}</span></td>
                <td>${solicitud.Placa}</td>
                <td><span class="badge ${estadoClass}">${solicitud.Estado}</span></td>
                <td>${solicitud.FechaSolicitud}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="estadoSolicitudes.verDetallesSolicitud(${solicitud.ID})" title="Ver detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });

        if ($.fn.DataTable) {
            $('#solicitudes-table').DataTable().clear().rows.add(Array.from(tbody.querySelectorAll('tr'))).draw();
        }
    }

    verDetallesSolicitud(solicitudId) {
        const solicitud = this.solicitudes.find(s => s.ID == solicitudId);
        if (!solicitud) return;

        const content = document.getElementById('detalles-solicitud-content');
        if (!content) return;

        // Timeline del estado
        const timelineSteps = [
            { estado: 'Pendiente', icon: 'clock', class: solicitud.Estado === 'Pendiente' ? 'active' : (['Aprobada', 'Entregada'].includes(solicitud.Estado) ? 'completed' : '') },
            { estado: 'Aprobada', icon: 'check-circle', class: solicitud.Estado === 'Aprobada' ? 'active' : (solicitud.Estado === 'Entregada' ? 'completed' : '') },
            { estado: 'Entregada', icon: 'truck', class: solicitud.Estado === 'Entregada' ? 'active completed' : '' }
        ];

        const timelineHtml = timelineSteps.map(step => `
            <div class="timeline-step ${step.class}">
                <div class="timeline-icon">
                    <i class="fas fa-${step.icon}"></i>
                </div>
                <div class="timeline-label">${step.estado}</div>
            </div>
        `).join('');

        content.innerHTML = `
            <!-- Timeline del Estado -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">
                        <i class="fas fa-route me-2"></i>Progreso de la Solicitud
                    </h6>
                    <div class="timeline-container">
                        ${timelineHtml}
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Información Principal -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-box me-2 text-primary"></i>
                                Información del Repuesto
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-hashtag me-2 text-muted"></i>ID Solicitud
                                        </label>
                                        <div class="info-value">
                                            <span class="badge bg-primary fs-6">#${solicitud.ID}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-cog me-2 text-muted"></i>Repuesto
                                        </label>
                                        <div class="info-value">
                                            <strong>${solicitud.RepuestoNombre}</strong>
                                            <small class="text-muted d-block">Código: ${solicitud.RepuestoCodigo}</small>
                                            ${solicitud.RepuestoCategoria ? `<small class="text-muted">Categoría: ${solicitud.RepuestoCategoria}</small>` : ''}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-sort-numeric-up me-2 text-muted"></i>Cantidad Solicitada
                                        </label>
                                        <div class="info-value">
                                            <span class="badge bg-info fs-6">${solicitud.Cantidad} unidad(es)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-exclamation-triangle me-2 text-muted"></i>Urgencia
                                        </label>
                                        <div class="info-value">
                                            <span class="badge ${this.getUrgenciaClass(solicitud.Urgencia)} fs-6">${solicitud.Urgencia}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    ${solicitud.Motivo ? `
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-comment-dots me-2 text-primary"></i>
                                Motivo de la Solicitud
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0 text-muted">${solicitud.Motivo}</p>
                        </div>
                    </div>
                    ` : ''}
                </div>

                <!-- Información Secundaria -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                Estado Actual
                            </h6>
                        </div>
                        <div class="card-body text-center">
                            <span class="badge ${this.getEstadoClass(solicitud.Estado)} fs-5 px-4 py-2">
                                ${solicitud.Estado}
                            </span>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                Fechas Importantes
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="date-item mb-3">
                                <div class="date-label">
                                    <i class="fas fa-paper-plane me-2 text-success"></i>
                                    Solicitud
                                </div>
                                <div class="date-value">
                                    ${solicitud.FechaSolicitud || 'N/A'}
                                </div>
                            </div>
                            <div class="date-item mb-3">
                                <div class="date-label">
                                    <i class="fas fa-check me-2 text-info"></i>
                                    Aprobación
                                </div>
                                <div class="date-value">
                                    ${solicitud.FechaAprobacion || '<span class="text-muted">Pendiente</span>'}
                                </div>
                            </div>
                            <div class="date-item">
                                <div class="date-label">
                                    <i class="fas fa-truck me-2 text-primary"></i>
                                    Entrega
                                </div>
                                <div class="date-value">
                                    ${solicitud.FechaEntrega || '<span class="text-muted">Pendiente</span>'}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-car me-2 text-primary"></i>
                                Vehículo Relacionado
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <i class="fas fa-car fa-3x text-muted mb-2"></i>
                                <div class="fw-bold">${solicitud.Placa}</div>
                                <small class="text-muted">Placa del Vehículo</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Mostrar modal usando Bootstrap
        const modalElement = document.getElementById('detalles-solicitud-modal');
        if (modalElement && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            // Fallback si Bootstrap no está disponible
            modalElement.style.display = 'block';
        }
    }

    actualizarResumen(solicitudes) {
        const pendientes = solicitudes.filter(s => s.Estado === 'Pendiente').length;
        const aprobadas = solicitudes.filter(s => s.Estado === 'Aprobada').length;
        const entregadas = solicitudes.filter(s => s.Estado === 'Entregada').length;
        const rechazadas = solicitudes.filter(s => s.Estado === 'Rechazada').length;

        document.getElementById('solicitudes-pendientes').textContent = pendientes;
        document.getElementById('solicitudes-aprobadas').textContent = aprobadas;
        document.getElementById('solicitudes-entregadas').textContent = entregadas;
        document.getElementById('solicitudes-rechazadas').textContent = rechazadas;
    }

    getEstadoClass(estado) {
        const clases = {
            'Pendiente': 'bg-warning',
            'Aprobada': 'bg-info',
            'Rechazada': 'bg-danger',
            'Entregada': 'bg-success',
            'Cancelada': 'bg-secondary'
        };
        return clases[estado] || 'bg-secondary';
    }

    getUrgenciaClass(urgencia) {
        const clases = {
            'Baja': 'bg-success',
            'Media': 'bg-warning',
            'Alta': 'bg-danger'
        };
        return clases[urgencia] || 'bg-secondary';
    }

    mostrarError(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje
            });
        } else {
            alert(mensaje);
        }
    }
}

// Inicializar cuando el DOM esté listo
let estadoSolicitudes;
document.addEventListener('DOMContentLoaded', () => {
    estadoSolicitudes = new EstadoSolicitudesRepuestos();
});

