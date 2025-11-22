/**
 * Gestión de Solicitudes de Agendamiento
 * Solo visible para Supervisores y Administradores
 */
class GestionAgendamiento {
    constructor() {
        this.solicitudActual = null;
        this.dataTable = null;
        this.init();
    }

    init() {
        // Solo inicializar si el usuario es supervisor o administrador
        if (!this.esSupervisor()) {
            return;
        }

        this.inicializarEventos();
        this.cargarSolicitudes();
    }

    esSupervisor() {
        // Verificar si existe la sección de solicitudes
        return document.getElementById('solicitudes-section') !== null;
    }

    inicializarEventos() {
        // Botón buscar solicitudes
        const btnBuscar = document.getElementById('btn-buscar-solicitudes');
        if (btnBuscar) {
            btnBuscar.addEventListener('click', () => this.cargarSolicitudes());
        }

        // Botón aprobar solicitud
        const btnAprobar = document.getElementById('btn-aprobar-solicitud');
        if (btnAprobar) {
            btnAprobar.addEventListener('click', () => this.aprobarSolicitud());
        }

        // Botón rechazar solicitud
        const btnRechazar = document.getElementById('btn-rechazar-solicitud');
        if (btnRechazar) {
            btnRechazar.addEventListener('click', () => this.rechazarSolicitud());
        }

        // Botón guardar agenda
        const btnGuardarAgenda = document.getElementById('btn-guardar-agenda');
        if (btnGuardarAgenda) {
            btnGuardarAgenda.addEventListener('click', () => this.guardarAgenda());
        }

        // Limpiar formulario de agenda al cerrar modal
        const agendaModal = document.getElementById('agendaModal');
        if (agendaModal) {
            agendaModal.addEventListener('hidden.bs.modal', () => {
                document.getElementById('agenda-form').reset();
                document.getElementById('agenda-id').value = '';
            });
        }
    }

    cargarSolicitudes() {
        const estado = document.getElementById('filtro-estado-solicitud')?.value || '';
        const fechaDesde = document.getElementById('filtro-fecha-desde')?.value || '';
        const fechaHasta = document.getElementById('filtro-fecha-hasta')?.value || '';

        const formData = new FormData();
        formData.append('accion', 'obtener_solicitudes');
        if (estado) formData.append('estado', estado);
        if (fechaDesde) formData.append('fecha_desde', fechaDesde);
        if (fechaHasta) formData.append('fecha_hasta', fechaHasta);

        fetch('../../app/model/agendamiento/scripts/s_agendamiento.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarSolicitudes(data.data);
            } else {
                this.mostrarToast('Error', data.message || 'Error al cargar solicitudes', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.mostrarToast('Error', 'Error de conexión con el servidor', 'error');
        });
    }

    mostrarSolicitudes(solicitudes) {
        const tbody = document.querySelector('#solicitudes-table tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (solicitudes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No hay solicitudes disponibles</td></tr>';
            return;
        }

        solicitudes.forEach(solicitud => {
            const row = document.createElement('tr');
            const estadoClass = this.getEstadoClass(solicitud.Estado);
            const fechaSolicitada = new Date(solicitud.FechaSolicitada).toLocaleDateString('es-ES');
            const horaSolicitada = solicitud.HoraSolicitada || 'N/A';

            row.innerHTML = `
                <td>${solicitud.ID}</td>
                <td>${solicitud.Placa}</td>
                <td>${solicitud.Marca} ${solicitud.Modelo}</td>
                <td>${solicitud.ConductorNombre}</td>
                <td>${fechaSolicitada} ${horaSolicitada}</td>
                <td><span class="badge ${estadoClass}">${solicitud.Estado}</span></td>
                <td>${solicitud.ChoferNombre || 'N/A'}</td>
                <td>
                    ${solicitud.Estado === 'Pendiente' ? `
                        <button class="btn btn-sm btn-primary" onclick="gestionAgendamiento.abrirModalGestionar(${solicitud.ID})">
                            <i class="fas fa-edit"></i> Gestionar
                        </button>
                    ` : `
                        <button class="btn btn-sm btn-info" onclick="gestionAgendamiento.verDetalles(${solicitud.ID})">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                    `}
                </td>
            `;
            tbody.appendChild(row);
        });

        // Inicializar DataTable si no existe
        if (!this.dataTable) {
            this.dataTable = $('#solicitudes-table').DataTable({
                language: {
                    "sProcessing": "Procesando...",
                    "sLengthMenu": "Mostrar _MENU_ registros",
                    "sZeroRecords": "No se encontraron resultados",
                    "sEmptyTable": "Ningún dato disponible en esta tabla",
                    "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                    "sInfoPostFix": "",
                    "sSearch": "Buscar:",
                    "sUrl": "",
                    "sInfoThousands": ",",
                    "sLoadingRecords": "Cargando...",
                    "oPaginate": {
                        "sFirst": "Primero",
                        "sLast": "Último",
                        "sNext": "Siguiente",
                        "sPrevious": "Anterior"
                    },
                    "oAria": {
                        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                    }
                },
                order: [[0, 'desc']],
                pageLength: 10
            });
        } else {
            this.dataTable.clear().rows.add($(tbody).find('tr')).draw();
        }
    }

    getEstadoClass(estado) {
        const clases = {
            'Pendiente': 'bg-warning',
            'Aprobada': 'bg-success',
            'Rechazada': 'bg-danger',
            'Cancelada': 'bg-secondary'
        };
        return clases[estado] || 'bg-secondary';
    }

    abrirModalGestionar(solicitudId) {
        // Cargar detalles de la solicitud
        const formData = new FormData();
        formData.append('accion', 'obtener_solicitudes');
        formData.append('solicitud_id', solicitudId.toString());

        fetch('../../app/model/agendamiento/scripts/s_agendamiento.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data.length > 0) {
                const solicitud = data.data[0];
                this.solicitudActual = solicitud;
                this.mostrarDetallesSolicitud(solicitud);
                this.cargarHorasDisponibles(solicitud.FechaSolicitada);
                this.cargarMecanicosDisponibles();
                
                const modal = new bootstrap.Modal(document.getElementById('gestionarSolicitudModal'));
                modal.show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.mostrarToast('Error', 'Error al cargar detalles de la solicitud', 'error');
        });
    }

    mostrarDetallesSolicitud(solicitud) {
        const container = document.getElementById('info-solicitud-detalle');
        container.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Información del Vehículo</h6>
                    <p><strong>Placa:</strong> ${solicitud.Placa}</p>
                    <p><strong>Vehículo:</strong> ${solicitud.Marca} ${solicitud.Modelo} ${solicitud.Color || ''}</p>
                    <p><strong>Tipo:</strong> ${solicitud.TipoVehiculo}</p>
                </div>
                <div class="col-md-6">
                    <h6>Información del Conductor</h6>
                    <p><strong>Nombre:</strong> ${solicitud.ConductorNombre}</p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <h6>Información de la Visita</h6>
                    <p><strong>Propósito:</strong> ${solicitud.Proposito}</p>
                </div>
                <div class="col-md-6">
                    <h6>Fecha y Hora Solicitada</h6>
                    <p><strong>Fecha:</strong> ${new Date(solicitud.FechaSolicitada).toLocaleDateString('es-ES')}</p>
                    <p><strong>Hora:</strong> ${solicitud.HoraSolicitada}</p>
                </div>
            </div>
            ${solicitud.Observaciones ? `<div class="row mt-3"><div class="col-12"><p><strong>Observaciones:</strong> ${solicitud.Observaciones}</p></div></div>` : ''}
        `;
    }

    cargarHorasDisponibles(fecha) {
        const formData = new FormData();
        formData.append('accion', 'obtener_horas_disponibles');
        formData.append('fecha', fecha);

        fetch('../../app/model/agendamiento/scripts/s_agendamiento.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarHorasDisponibles(data.data);
            } else {
                document.getElementById('horas-disponibles-container').innerHTML = 
                    '<p class="text-warning">No hay horas disponibles para esta fecha. Puede crear nuevas horas en la agenda.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    mostrarHorasDisponibles(horas) {
        const container = document.getElementById('horas-disponibles-container');
        
        if (horas.length === 0) {
            container.innerHTML = '<p class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i>No hay horas disponibles para esta fecha. Puede crear nuevas horas en la agenda.</p>';
            return;
        }

        let html = '<div class="row">';
        horas.forEach(hora => {
            html += `
                <div class="col-md-4 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="hora-disponible" 
                               id="hora-${hora.ID}" value="${hora.ID}" required>
                        <label class="form-check-label" for="hora-${hora.ID}">
                            <i class="fas fa-clock me-1"></i>${hora.HoraInicio} - ${hora.HoraFin}
                        </label>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    cargarMecanicosDisponibles() {
        const formData = new FormData();
        formData.append('accion', 'obtener_mecanicos_disponibles');

        fetch('../../app/model/agendamiento/scripts/s_agendamiento.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarMecanicosDisponibles(data.data);
            } else {
                document.getElementById('mecanicos-disponibles-container').innerHTML = 
                    '<p class="text-warning">No se pudieron cargar los mecánicos disponibles.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('mecanicos-disponibles-container').innerHTML = 
                '<p class="text-danger">Error al cargar mecánicos disponibles.</p>';
        });
    }

    mostrarMecanicosDisponibles(mecanicos) {
        const container = document.getElementById('mecanicos-disponibles-container');
        
        if (mecanicos.length === 0) {
            container.innerHTML = '<p class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i>No hay mecánicos disponibles en este momento.</p>';
            return;
        }

        let html = '<div class="row">';
        mecanicos.forEach(mecanico => {
            html += `
                <div class="col-md-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="mecanico-disponible" 
                               id="mecanico-${mecanico.UsuarioID}" value="${mecanico.UsuarioID}">
                        <label class="form-check-label" for="mecanico-${mecanico.UsuarioID}">
                            <i class="fas fa-user-cog me-1"></i>${mecanico.NombreUsuario}
                            ${mecanico.Correo ? `<br><small class="text-muted">${mecanico.Correo}</small>` : ''}
                        </label>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        html += '<p class="text-muted mt-2"><small><i class="fas fa-info-circle me-1"></i>Seleccione un mecánico para asignarlo cuando apruebe la solicitud (opcional).</small></p>';
        container.innerHTML = html;
    }

    aprobarSolicitud() {
        if (!this.solicitudActual) return;

        const horaSeleccionada = document.querySelector('input[name="hora-disponible"]:checked');
        const agendaId = horaSeleccionada ? horaSeleccionada.value : null;

        if (!agendaId) {
            this.mostrarToast('Advertencia', 'Por favor seleccione una hora disponible', 'warning');
            return;
        }

        const mecanicoSeleccionado = document.querySelector('input[name="mecanico-disponible"]:checked');
        const mecanicoId = mecanicoSeleccionado ? mecanicoSeleccionado.value : null;

        const formData = new FormData();
        formData.append('accion', 'aprobar_solicitud');
        formData.append('solicitud_id', this.solicitudActual.ID);
        formData.append('agenda_id', agendaId);
        if (mecanicoId) {
            formData.append('mecanico_id', mecanicoId);
        }

        fetch('../../app/model/agendamiento/scripts/s_agendamiento.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarToast('Éxito', 'Solicitud aprobada correctamente' + (mecanicoId ? ' y mecánico asignado' : ''), 'success');
                bootstrap.Modal.getInstance(document.getElementById('gestionarSolicitudModal')).hide();
                this.cargarSolicitudes();
            } else {
                this.mostrarToast('Error', data.message || 'Error al aprobar la solicitud', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.mostrarToast('Error', 'Error de conexión con el servidor', 'error');
        });
    }

    rechazarSolicitud() {
        if (!this.solicitudActual) return;

        const motivoRechazo = document.getElementById('motivo-rechazo').value.trim();
        if (!motivoRechazo) {
            this.mostrarToast('Advertencia', 'Por favor ingrese el motivo del rechazo', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('accion', 'rechazar_solicitud');
        formData.append('solicitud_id', this.solicitudActual.ID);
        formData.append('motivo_rechazo', motivoRechazo);

        fetch('../../app/model/agendamiento/scripts/s_agendamiento.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarToast('Éxito', 'Solicitud rechazada correctamente', 'success');
                bootstrap.Modal.getInstance(document.getElementById('gestionarSolicitudModal')).hide();
                this.cargarSolicitudes();
            } else {
                this.mostrarToast('Error', data.message || 'Error al rechazar la solicitud', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.mostrarToast('Error', 'Error de conexión con el servidor', 'error');
        });
    }

    guardarAgenda() {
        const form = document.getElementById('agenda-form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData();
        formData.append('accion', 'gestionar_agenda');
        const agendaId = document.getElementById('agenda-id').value;
        if (agendaId) formData.append('id', agendaId);
        formData.append('fecha', document.getElementById('agenda-fecha').value);
        formData.append('hora_inicio', document.getElementById('agenda-hora-inicio').value);
        formData.append('hora_fin', document.getElementById('agenda-hora-fin').value);
        formData.append('disponible', document.getElementById('agenda-disponible').checked ? 1 : 0);
        formData.append('observaciones', document.getElementById('agenda-observaciones').value);

        fetch('../../app/model/agendamiento/scripts/s_agendamiento.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarToast('Éxito', 'Agenda guardada correctamente', 'success');
                bootstrap.Modal.getInstance(document.getElementById('agendaModal')).hide();
            } else {
                this.mostrarToast('Error', data.message || 'Error al guardar la agenda', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.mostrarToast('Error', 'Error de conexión con el servidor', 'error');
        });
    }

    verDetalles(solicitudId) {
        // Implementar vista de detalles para solicitudes ya procesadas
        this.mostrarToast('Info', 'Funcionalidad de detalles en desarrollo', 'info');
    }

    mostrarToast(titulo, mensaje, tipo = 'info') {
        // Usar el sistema de toast existente si está disponible
        if (typeof ConsultaVehiculos !== 'undefined' && ConsultaVehiculos.prototype.mostrarToast) {
            const consulta = new ConsultaVehiculos();
            consulta.mostrarToast(titulo, mensaje, tipo);
        } else {
            alert(`${titulo}: ${mensaje}`);
        }
    }
}

// Inicializar cuando el DOM esté listo
let gestionAgendamiento;
document.addEventListener('DOMContentLoaded', () => {
    gestionAgendamiento = new GestionAgendamiento();
});

