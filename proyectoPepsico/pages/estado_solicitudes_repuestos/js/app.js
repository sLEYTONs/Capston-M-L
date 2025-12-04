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
            // Asegurar que el estado nunca esté vacío
            let estado = solicitud.Estado || '';
            if (!estado || estado.trim() === '') {
                // Si el estado está vacío pero hay FechaAprobacion, es "Entregada"
                if (solicitud.FechaAprobacion) {
                    estado = 'Entregada';
                } else {
                    estado = 'Pendiente';
                }
            }
            const estadoClass = this.getEstadoClass(estado);
            const urgenciaClass = this.getUrgenciaClass(solicitud.Urgencia);

            // Verificar si hay vehículo asignado
            const tienePlaca = solicitud.Placa && 
                              solicitud.Placa.trim() !== '' && 
                              solicitud.Placa !== 'null' && 
                              solicitud.Placa !== 'undefined' &&
                              solicitud.Placa !== null;
            const placaHTML = tienePlaca
                ? `<span class="badge bg-info">${solicitud.Placa}</span>`
                : `<span class="text-muted"><i class="fas fa-minus-circle me-1"></i>No asignado</span>`;

            // Botón para asignar vehículo si está aprobada y no tiene vehículo asignado
            let botonesAccion = `
                <button class="btn btn-sm btn-info" onclick="estadoSolicitudes.verDetallesSolicitud(${solicitud.ID})" title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </button>
            `;
            
            // Mostrar botón de asignar vehículo si está entregada y no tiene placa asignada
            const estadoParaVerificar = estado || solicitud.Estado || '';
            const estaEntregada = estadoParaVerificar === 'Entregada' || 
                                 estadoParaVerificar === 'entregada' ||
                                 (solicitud.FechaAprobacion !== null && solicitud.FechaAprobacion !== '');
            const sinVehiculo = !tienePlaca;
            
            // Debug: descomentar para verificar condiciones
            // console.log('Solicitud ID:', solicitud.ID, 'Estado:', solicitud.Estado, 'Placa:', solicitud.Placa, 'EstaAprobada:', estaAprobada, 'SinVehiculo:', sinVehiculo);
            
            if (estaEntregada && sinVehiculo) {
                botonesAccion += `
                    <button class="btn btn-sm btn-success ms-1" onclick="estadoSolicitudes.mostrarModalAsignarVehiculo(${solicitud.ID})" title="Asignar vehículo">
                        <i class="fas fa-car me-1"></i>Asignar Vehículo
                    </button>
                `;
            }

            row.innerHTML = `
                <td><strong>#${solicitud.ID}</strong></td>
                <td>${solicitud.RepuestoNombre} (${solicitud.RepuestoCodigo})</td>
                <td>${solicitud.Cantidad}</td>
                <td><span class="badge ${urgenciaClass}">${solicitud.Urgencia}</span></td>
                <td>${placaHTML}</td>
                <td><span class="badge ${estadoClass}">${estado}</span></td>
                <td>${solicitud.FechaSolicitud}</td>
                <td>
                    <div class="btn-group">
                        ${botonesAccion}
                    </div>
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

        // Asegurar que el estado nunca esté vacío
        let estado = solicitud.Estado || '';
        if (!estado || estado.trim() === '') {
            if (solicitud.FechaAprobacion) {
                estado = 'Entregada';
            } else {
                estado = 'Pendiente';
            }
        }

        // Timeline del estado
        const timelineSteps = [
            { estado: 'Pendiente', icon: 'clock', class: estado === 'Pendiente' ? 'active' : (['Aprobada', 'Aceptada', 'En Proceso', 'En Tránsito', 'Recibido', 'Entregada'].includes(estado) ? 'completed' : '') },
            { estado: 'Aprobada', icon: 'check-circle', class: ['Aprobada', 'Aceptada', 'En Proceso', 'En Tránsito', 'Recibido', 'Entregada'].includes(estado) ? 'completed' : '' },
            { estado: 'Entregada', icon: 'truck', class: estado === 'Entregada' ? 'active completed' : '' }
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
                            <span class="badge ${this.getEstadoClass(estado)} fs-5 px-4 py-2">
                                ${estado}
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

                    ${solicitud.Placa && solicitud.Placa.trim() !== '' ? `
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
                    ` : ''}
                </div>
            </div>
        `;

        // Mostrar modal usando Bootstrap
        const modalElement = document.getElementById('detalles-solicitud-modal');
        if (modalElement && typeof bootstrap !== 'undefined') {
            // Cerrar cualquier instancia previa del modal
            const existingModal = bootstrap.Modal.getInstance(modalElement);
            if (existingModal) {
                existingModal.dispose();
            }
            
            // Crear nueva instancia del modal
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            
            // Mostrar el modal
            modal.show();
            
            // Limpiar el contenido cuando se cierre el modal
            modalElement.addEventListener('hidden.bs.modal', function onModalHidden() {
                const content = document.getElementById('detalles-solicitud-content');
                if (content) {
                    content.innerHTML = '';
                }
                modalElement.removeEventListener('hidden.bs.modal', onModalHidden);
            }, { once: true });
        } else {
            // Fallback si Bootstrap no está disponible
            if (modalElement) {
                modalElement.style.display = 'block';
            }
        }
    }

    actualizarResumen(solicitudes) {
        const pendientes = solicitudes.filter(s => {
            const estado = s.Estado || '';
            return estado === 'Pendiente' || (estado === '' && !s.FechaAprobacion);
        }).length;
        const aprobadas = solicitudes.filter(s => {
            const estado = s.Estado || '';
            return estado === 'Aprobada' || estado === 'Aceptada' || estado === 'En Proceso' || estado === 'En Tránsito' || estado === 'Recibido' || estado === 'Entregada' || (estado === '' && s.FechaAprobacion);
        }).length;
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
            'Aceptada': 'bg-info', // Alias para Aprobada
            'En Proceso': 'bg-primary',
            'En Tránsito': 'bg-primary',
            'Recibido': 'bg-info',
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

    mostrarModalAsignarVehiculo(solicitudId) {
        const solicitud = this.solicitudes.find(s => s.ID == solicitudId);
        if (!solicitud) return;

        // Cargar vehículos asignados al mecánico
        fetch(this.baseUrl + '?action=obtenerVehiculosAsignados', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                this.mostrarModalSeleccionVehiculo(solicitudId, data.data);
            } else {
                this.mostrarError('Error al cargar vehículos asignados');
            }
        })
        .catch(error => {
            console.error('Error al cargar vehículos:', error);
            this.mostrarError('Error de conexión al cargar vehículos');
        });
    }

    mostrarModalSeleccionVehiculo(solicitudId, vehiculos) {
        if (vehiculos.length === 0) {
            this.mostrarError('No tiene vehículos asignados para asociar a esta solicitud');
            return;
        }

        let opcionesHTML = '<option value="">Seleccione un vehículo...</option>';
        vehiculos.forEach(vehiculo => {
            const texto = `${vehiculo.Placa} - ${vehiculo.Marca} ${vehiculo.Modelo} (${vehiculo.TipoVehiculo})`;
            opcionesHTML += `<option value="${vehiculo.AsignacionID}">${texto}</option>`;
        });

        const modalHTML = `
            <div class="modal fade" id="modal-asignar-vehiculo" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-car me-2"></i>
                                Asignar Vehículo a Solicitud
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Seleccione el vehículo al que corresponde esta solicitud de repuestos:</p>
                            <div class="mb-3">
                                <label for="select-vehiculo-asignar" class="form-label">Vehículo *</label>
                                <select class="form-select" id="select-vehiculo-asignar" required>
                                    ${opcionesHTML}
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-success" id="btn-confirmar-asignar-vehiculo" data-solicitud-id="${solicitudId}">
                                <i class="fas fa-check me-2"></i>Asignar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modal anterior si existe
        const modalAnterior = document.getElementById('modal-asignar-vehiculo');
        if (modalAnterior) {
            modalAnterior.remove();
        }

        // Agregar modal al body
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Inicializar modal
        const modalElement = document.getElementById('modal-asignar-vehiculo');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        // Event listener para el botón de confirmar
        document.getElementById('btn-confirmar-asignar-vehiculo').addEventListener('click', () => {
            this.confirmarAsignarVehiculo(solicitudId);
        });

        // Limpiar modal al cerrar
        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
        });
    }

    confirmarAsignarVehiculo(solicitudId) {
        const asignacionId = document.getElementById('select-vehiculo-asignar').value;
        
        if (!asignacionId) {
            this.mostrarError('Seleccione un vehículo');
            return;
        }

        const btn = document.getElementById('btn-confirmar-asignar-vehiculo');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Asignando...';

        const formData = new FormData();
        formData.append('action', 'asignarVehiculoASolicitud');
        formData.append('solicitud_id', solicitudId);
        formData.append('asignacion_id', asignacionId);

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarExito(data.message);
                const modalElement = document.getElementById('modal-asignar-vehiculo');
                const modal = bootstrap.Modal.getInstance(modalElement);
                modal.hide();
                this.cargarSolicitudes(); // Recargar solicitudes
            } else {
                this.mostrarError('Error: ' + (data.message || 'Error desconocido'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-2"></i>Asignar';
            }
        })
        .catch(error => {
            console.error('Error al asignar vehículo:', error);
            this.mostrarError('Error de conexión al asignar vehículo');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-2"></i>Asignar';
        });
    }

    mostrarExito(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
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
let estadoSolicitudes;
document.addEventListener('DOMContentLoaded', () => {
    estadoSolicitudes = new EstadoSolicitudesRepuestos();
});

