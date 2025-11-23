/**
 * Gestión de Solicitudes de Agendamiento - Supervisor
 */
class GestionSolicitudes {
    constructor() {
        this.solicitudActual = null;
        this.dataTable = null;
        this.calendario = null;
        this.horasDisponibles = [];
        // Calcular la ruta base del proyecto
        this.baseUrl = this.getBaseUrl();
        this.init();
    }

    getBaseUrl() {
        // Obtener la ruta actual y construir la ruta al script
        const currentPath = window.location.pathname;
        // Si estamos en pages/gestion_solicitudes.php, construir la ruta relativa
        // Desde pages/gestion_solicitudes.php necesitamos subir un nivel para llegar a la raíz del proyecto
        if (currentPath.includes('/pages/')) {
            // Obtener la parte antes de /pages/
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            const url = basePath + '/app/model/agendamiento/scripts/s_agendamiento.php';
            console.log('Base URL calculada:', url);
            return url;
        }
        // Fallback: ruta relativa desde pages/
        return '../../app/model/agendamiento/scripts/s_agendamiento.php';
    }

    init() {
        this.inicializarEventos();
        this.cargarSolicitudes();
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

        // Limpiar selección al cerrar modal
        const gestionarModal = document.getElementById('gestionarSolicitudModal');
        if (gestionarModal) {
            gestionarModal.addEventListener('hidden.bs.modal', () => {
                document.getElementById('agenda-id-seleccionada').value = '';
                document.getElementById('info-seleccion-hora').style.display = 'none';
                if (this.calendario) {
                    this.calendario.removeAllEvents();
                }
            });
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

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Verificar si la respuesta es JSON válido
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // Si no es JSON, leer como texto para ver el error
                return response.text().then(text => {
                    console.error('Respuesta no JSON recibida:', text);
                    throw new Error('El servidor devolvió una respuesta no válida. Ver consola para detalles.');
                });
            }
            // Verificar si la respuesta fue exitosa
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Error HTTP:', response.status);
                    console.error('Respuesta completa:', text);
                    // Intentar parsear como JSON para obtener el mensaje de error
                    try {
                        const errorData = JSON.parse(text);
                        throw new Error(errorData.message || 'Error del servidor: ' + response.status);
                    } catch (e) {
                        // Si no es JSON, mostrar el texto completo
                        throw new Error('Error del servidor: ' + response.status + '. Ver consola para detalles.');
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                this.mostrarSolicitudes(data.data);
            } else {
                this.mostrarToast('Error', data.message || 'Error al cargar solicitudes', 'error');
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            console.error('URL base:', this.baseUrl);
            // Mostrar mensaje más descriptivo
            let mensaje = error.message || 'Error de conexión con el servidor';
            if (error.message && error.message.includes('JSON')) {
                mensaje = 'El servidor devolvió una respuesta inválida. Verifique que las tablas existan en la base de datos.';
            }
            this.mostrarToast('Error', mensaje, 'error');
        });
    }

    mostrarSolicitudes(solicitudes) {
        const tbody = document.querySelector('#solicitudes-table tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (solicitudes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay solicitudes disponibles</td></tr>';
            return;
        }

        solicitudes.forEach(solicitud => {
            const row = document.createElement('tr');
            const estadoClass = this.getEstadoClass(solicitud.Estado);

            row.innerHTML = `
                <td>${solicitud.ID}</td>
                <td>${solicitud.Placa}</td>
                <td>${solicitud.Marca} ${solicitud.Modelo}</td>
                <td>${solicitud.Proposito}</td>
                <td><span class="badge ${estadoClass}">${solicitud.Estado}</span></td>
                <td>${solicitud.ChoferNombre || 'N/A'}</td>
                <td>
                    ${solicitud.Estado === 'Pendiente' ? `
                        <button class="btn btn-sm btn-primary" onclick="gestionSolicitudes.abrirModalGestionar(${solicitud.ID})">
                            <i class="fas fa-edit"></i> Gestionar
                        </button>
                    ` : `
                        <button class="btn btn-sm btn-info" onclick="gestionSolicitudes.verDetalles(${solicitud.ID})">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                    `}
                </td>
            `;
            tbody.appendChild(row);
        });

        // Destruir DataTable existente si existe
        if (this.dataTable) {
            this.dataTable.destroy();
            this.dataTable = null;
        }

        // Inicializar DataTable
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            this.dataTable = $('#solicitudes-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                order: [[0, 'desc']],
                pageLength: 10,
                responsive: true,
                autoWidth: false,
                columnDefs: [
                    { width: "60px", targets: 0 }, // ID
                    { width: "100px", targets: 1 }, // Placa
                    { width: "150px", targets: 2 }, // Vehículo
                    { width: "120px", targets: 3 }, // Propósito
                    { width: "100px", targets: 4 }, // Estado
                    { width: "120px", targets: 5 }, // Chofer
                    { width: "120px", targets: 6 }  // Acciones
                ]
            });
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
        const formData = new FormData();
        formData.append('accion', 'obtener_solicitudes');
        formData.append('solicitud_id', solicitudId.toString());

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data.length > 0) {
                const solicitud = data.data[0];
                this.solicitudActual = solicitud;
                this.mostrarDetallesSolicitud(solicitud);
                this.cargarMecanicosDisponibles();
                
                const modal = new bootstrap.Modal(document.getElementById('gestionarSolicitudModal'));
                modal.show();
                
                // Inicializar calendario y cargar horas cuando el modal esté completamente visible
                modal._element.addEventListener('shown.bs.modal', () => {
                    // Pequeño delay para asegurar que el DOM esté completamente renderizado
                    setTimeout(() => {
                        this.inicializarCalendario();
                        this.cargarHorasDisponiblesCalendario();
                    }, 100);
                }, { once: true });
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
                    <p><strong>Vehículo:</strong> ${solicitud.Marca} ${solicitud.Modelo}</p>
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
            </div>
            ${solicitud.Observaciones ? `<div class="row mt-3"><div class="col-12"><p><strong>Observaciones:</strong> ${solicitud.Observaciones}</p></div></div>` : ''}
        `;
    }

    inicializarCalendario() {
        const calendarEl = document.getElementById('calendario-horas-disponibles');
        if (!calendarEl) return;

        // Destruir calendario existente si existe
        if (this.calendario) {
            this.calendario.destroy();
        }

        this.calendario = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridWeek,timeGridDay'
            },
            slotMinTime: '09:00:00',
            slotMaxTime: '18:00:00',
            slotDuration: '01:00:00',
            allDaySlot: false,
            height: 'auto',
            locale: 'es',
            firstDay: 1, // Lunes
            weekends: true,
            selectable: false,
            editable: false,
            eventClick: (info) => {
                info.jsEvent.preventDefault();
                this.seleccionarHora(info.event);
            },
            eventDisplay: 'block',
            eventColor: '#28a745',
            eventTextColor: '#fff',
            eventCursor: 'pointer',
            eventInteractive: true,
            eventDidMount: (info) => {
                // Agregar estilo de cursor pointer y hover
                info.el.style.cursor = 'pointer';
                info.el.title = 'Click para seleccionar esta hora';
            }
        });

        this.calendario.render();
    }

    cargarHorasDisponiblesCalendario() {
        // Cargar horas disponibles para los próximos 14 días
        const hoy = new Date();
        const fechaFin = new Date();
        fechaFin.setDate(hoy.getDate() + 14);

        const fechas = [];
        for (let d = new Date(hoy); d <= fechaFin; d.setDate(d.getDate() + 1)) {
            fechas.push(new Date(d).toISOString().split('T')[0]);
        }

        // Cargar horas para todas las fechas
        const promesas = fechas.map(fecha => {
            const formData = new FormData();
            formData.append('accion', 'obtener_horas_disponibles');
            formData.append('fecha', fecha);

            return fetch(this.baseUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(`Respuesta para fecha ${fecha}:`, data);
                if (data.status === 'success' && Array.isArray(data.data)) {
                    const horasConFecha = data.data.map(hora => ({
                        ...hora,
                        fecha: fecha
                    }));
                    console.log(`Horas encontradas para ${fecha}:`, horasConFecha.length);
                    return horasConFecha;
                } else {
                    console.warn(`No se encontraron horas para ${fecha} o respuesta inválida:`, data);
                    return [];
                }
            })
            .catch(error => {
                console.error('Error cargando horas para fecha', fecha, error);
                return [];
            });
        });

            Promise.all(promesas).then(todasLasHoras => {
            const todasLasHorasFlat = todasLasHoras.flat();
            console.log('Total de horas disponibles recibidas:', todasLasHorasFlat.length);
            console.log('Horas por fecha:', todasLasHorasFlat);
            
            const eventos = [];
            todasLasHorasFlat.forEach(hora => {
                if (!hora.ID || !hora.HoraInicio || !hora.HoraFin || !hora.fecha) {
                    console.warn('Hora con datos incompletos:', hora);
                    return;
                }
                
                // Asegurar que la hora tenga el formato correcto (HH:MM:SS)
                let horaInicio = hora.HoraInicio;
                let horaFin = hora.HoraFin;
                
                // Si la hora no tiene segundos, agregarlos
                if (horaInicio.split(':').length === 2) {
                    horaInicio += ':00';
                }
                if (horaFin.split(':').length === 2) {
                    horaFin += ':00';
                }
                
                const fechaHora = `${hora.fecha}T${horaInicio}`;
                const fechaHoraFin = `${hora.fecha}T${horaFin}`;
                
                const evento = {
                    id: hora.ID.toString(),
                    title: `${horaInicio.substring(0, 5)} - ${horaFin.substring(0, 5)}`,
                    start: fechaHora,
                    end: fechaHoraFin,
                    backgroundColor: '#28a745',
                    borderColor: '#1e7e34',
                    textColor: '#fff',
                    extendedProps: {
                        agendaId: hora.ID,
                        horaInicio: horaInicio,
                        horaFin: horaFin,
                        observaciones: hora.Observaciones || ''
                    }
                };
                
                eventos.push(evento);
                console.log('Evento creado:', evento);
            });

            this.horasDisponibles = todasLasHorasFlat;
            console.log('Total de eventos creados:', eventos.length);
            
            if (this.calendario) {
                // Limpiar eventos anteriores
                this.calendario.removeAllEvents();
                
                // Agregar eventos - en FullCalendar v6 usamos addEvent (singular) para cada evento
                if (eventos.length > 0) {
                    eventos.forEach(evento => {
                        try {
                            this.calendario.addEvent(evento);
                        } catch (error) {
                            console.error('Error al agregar evento:', evento, error);
                        }
                    });
                    console.log('Eventos agregados al calendario:', eventos.length);
                } else {
                    console.warn('No hay eventos para mostrar. Verifica que haya horas disponibles en la base de datos.');
                }
            } else {
                console.error('Calendario no está inicializado');
            }
        }).catch(error => {
            console.error('Error al cargar horas disponibles:', error);
        });
    }

    seleccionarHora(evento) {
        const agendaId = evento.extendedProps.agendaId;
        const horaInicio = evento.extendedProps.horaInicio;
        const horaFin = evento.extendedProps.horaFin;
        const fecha = evento.start.toISOString().split('T')[0];

        // Formatear hora para mostrar
        function formatearHora(hora) {
            const [horas, minutos] = hora.split(':');
            const hora24 = parseInt(horas);
            const ampm = hora24 >= 12 ? 'PM' : 'AM';
            const hora12 = hora24 % 12 || 12;
            return `${hora12}:${minutos} ${ampm}`;
        }

        // Guardar selección
        const agendaInput = document.getElementById('agenda-id-seleccionada');
        if (agendaInput) {
            agendaInput.value = agendaId;
        }
        
        // Mostrar información de selección
        const fechaFormateada = new Date(fecha).toLocaleDateString('es-ES', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        const horaTexto = document.getElementById('hora-seleccionada-texto');
        const infoSeleccion = document.getElementById('info-seleccion-hora');
        
        if (horaTexto) {
            horaTexto.textContent = `${fechaFormateada} de ${formatearHora(horaInicio)} a ${formatearHora(horaFin)}`;
        }
        
        if (infoSeleccion) {
            infoSeleccion.style.display = 'block';
        }

        // Resaltar evento seleccionado - remover selección anterior
        this.calendario.getEvents().forEach(evt => {
            if (evt.id === agendaId.toString()) {
                // Evento seleccionado - cambiar a azul
                evt.setProp('backgroundColor', '#007bff');
                evt.setProp('borderColor', '#0056b3');
                evt.setProp('classNames', ['fc-event-selected']);
            } else {
                // Otros eventos - volver a verde
                evt.setProp('backgroundColor', '#28a745');
                evt.setProp('borderColor', '#1e7e34');
                // Remover classNames de selección si existe
                try {
                    // Intentar obtener classNames de diferentes formas según la versión de FullCalendar
                    let currentClassNames = [];
                    if (evt.extendedProps && evt.extendedProps.classNames) {
                        currentClassNames = evt.extendedProps.classNames;
                    } else if (evt.classNames) {
                        currentClassNames = Array.isArray(evt.classNames) ? evt.classNames : [evt.classNames];
                    }
                    
                    if (Array.isArray(currentClassNames) && currentClassNames.length > 0) {
                        const filteredClassNames = currentClassNames.filter(cn => cn !== 'fc-event-selected');
                        evt.setProp('classNames', filteredClassNames.length > 0 ? filteredClassNames : null);
                    } else {
                        evt.setProp('classNames', null);
                    }
                } catch (e) {
                    // Si no se puede obtener classNames, simplemente establecer null
                    evt.setProp('classNames', null);
                }
            }
        });
        
        // Scroll suave a la información de selección
        if (infoSeleccion) {
            infoSeleccion.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    cargarHorasDisponibles(fecha) {
        // Esta función se mantiene para compatibilidad pero ya no se usa
        // El calendario carga todas las horas automáticamente
    }


    cargarMecanicosDisponibles() {
        const formData = new FormData();
        formData.append('accion', 'obtener_mecanicos_disponibles');

        fetch(this.baseUrl, {
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

        const agendaId = document.getElementById('agenda-id-seleccionada').value;

        if (!agendaId) {
            this.mostrarToast('Advertencia', 'Por favor seleccione una hora disponible del calendario', 'warning');
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

        fetch(this.baseUrl, {
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

        fetch(this.baseUrl, {
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

        fetch(this.baseUrl, {
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
        const formData = new FormData();
        formData.append('accion', 'obtener_solicitudes');
        formData.append('solicitud_id', solicitudId.toString());

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data.length > 0) {
                const solicitud = data.data[0];
                this.mostrarModalDetalles(solicitud);
            } else {
                this.mostrarToast('Error', 'No se pudieron cargar los detalles de la solicitud', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.mostrarToast('Error', 'Error al cargar detalles de la solicitud', 'error');
        });
    }

    mostrarModalDetalles(solicitud) {
        // Formatear fechas
        const fechaCreacion = solicitud.FechaCreacion 
            ? new Date(solicitud.FechaCreacion).toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
            : 'N/A';

        const fechaSolicitada = solicitud.FechaSolicitada
            ? new Date(solicitud.FechaSolicitada).toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
            : 'N/A';

        const fechaAgenda = (solicitud.FechaAgenda || solicitud.Fecha)
            ? new Date(solicitud.FechaAgenda || solicitud.Fecha).toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })
            : 'N/A';

        // Formatear horas
        const horaSolicitada = solicitud.HoraSolicitada 
            ? solicitud.HoraSolicitada.substring(0, 5)
            : 'N/A';

        const horaInicio = (solicitud.HoraInicioAgenda || solicitud.HoraInicio)
            ? (solicitud.HoraInicioAgenda || solicitud.HoraInicio).substring(0, 5)
            : 'N/A';

        const horaFin = (solicitud.HoraFinAgenda || solicitud.HoraFin)
            ? (solicitud.HoraFinAgenda || solicitud.HoraFin).substring(0, 5)
            : 'N/A';

        // Estado badge
        const estadoClass = this.getEstadoClass(solicitud.Estado);
        const estadoIcon = {
            'Pendiente': 'fa-clock',
            'Aprobada': 'fa-check-circle',
            'Rechazada': 'fa-times-circle',
            'Cancelada': 'fa-ban'
        }[solicitud.Estado] || 'fa-info-circle';

        // Construir HTML del modal
        const modalHtml = `
            <div class="modal fade" id="detallesSolicitudModal" tabindex="-1" aria-labelledby="detallesSolicitudModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="detallesSolicitudModalLabel">
                                <i class="fas fa-info-circle me-2"></i>Detalles de la Solicitud #${solicitud.ID}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
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
                                                    <td><strong class="text-dark">${solicitud.Placa}</strong></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Vehículo:</th>
                                                    <td>${solicitud.Marca} ${solicitud.Modelo}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Tipo:</th>
                                                    <td>${solicitud.TipoVehiculo || 'N/A'}</td>
                                                </tr>
                                                ${solicitud.Anio ? `
                                                <tr>
                                                    <th class="text-muted">Año:</th>
                                                    <td>${solicitud.Anio}</td>
                                                </tr>
                                                ` : ''}
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 text-primary">
                                                <i class="fas fa-user me-2"></i>Información del Conductor
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless table-sm mb-0">
                                                <tr>
                                                    <th width="40%" class="text-muted">Nombre:</th>
                                                    <td>${solicitud.ConductorNombre || 'N/A'}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Chofer:</th>
                                                    <td>${solicitud.ChoferNombre || 'N/A'}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 text-primary">
                                                <i class="fas fa-clipboard-list me-2"></i>Información de la Solicitud
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless table-sm mb-0">
                                                <tr>
                                                    <th width="40%" class="text-muted">Estado:</th>
                                                    <td>
                                                        <span class="badge ${estadoClass}">
                                                            <i class="fas ${estadoIcon} me-1"></i>${solicitud.Estado}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Propósito:</th>
                                                    <td>${solicitud.Proposito || 'N/A'}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Fecha Creación:</th>
                                                    <td><i class="fas fa-calendar-alt me-1 text-primary"></i>${fechaCreacion}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Fecha Solicitada:</th>
                                                    <td><i class="fas fa-calendar-check me-1 text-primary"></i>${fechaSolicitada}</td>
                                                </tr>
                                                ${solicitud.HoraSolicitada ? `
                                                <tr>
                                                    <th class="text-muted">Hora Solicitada:</th>
                                                    <td><i class="fas fa-clock me-1 text-primary"></i>${horaSolicitada}</td>
                                                </tr>
                                                ` : ''}
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                ${solicitud.Estado === 'Aprobada' && solicitud.AgendaID ? `
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100 border-success">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">
                                                <i class="fas fa-calendar-check me-2"></i>Agenda Asignada
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless table-sm mb-0">
                                                <tr>
                                                    <th width="40%" class="text-muted">Fecha:</th>
                                                    <td><i class="fas fa-calendar-alt me-1 text-success"></i>${fechaAgenda}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Hora:</th>
                                                    <td><i class="fas fa-clock me-1 text-success"></i>${horaInicio} - ${horaFin}</td>
                                                </tr>
                                                ${solicitud.SupervisorNombre ? `
                                                <tr>
                                                    <th class="text-muted">Supervisor:</th>
                                                    <td><i class="fas fa-user-tie me-1 text-success"></i>${solicitud.SupervisorNombre}</td>
                                                </tr>
                                                ` : ''}
                                                ${solicitud.MecanicoNombre ? `
                                                <tr>
                                                    <th class="text-muted">Mecánico:</th>
                                                    <td><i class="fas fa-wrench me-1 text-success"></i>${solicitud.MecanicoNombre}</td>
                                                </tr>
                                                ` : ''}
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                ` : solicitud.Estado === 'Rechazada' ? `
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100 border-danger">
                                        <div class="card-header bg-danger text-white">
                                            <h6 class="mb-0">
                                                <i class="fas fa-times-circle me-2"></i>Motivo de Rechazo
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">${solicitud.MotivoRechazo || 'No se especificó motivo de rechazo'}</p>
                                            ${solicitud.SupervisorNombre ? `
                                            <hr>
                                            <small class="text-muted">
                                                <i class="fas fa-user-tie me-1"></i>Rechazado por: ${solicitud.SupervisorNombre}
                                            </small>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                            ${solicitud.Observaciones ? `
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 text-primary">
                                                <i class="fas fa-sticky-note me-2"></i>Observaciones
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">${solicitud.Observaciones}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modal anterior si existe
        const modalAnterior = document.getElementById('detallesSolicitudModal');
        if (modalAnterior) {
            modalAnterior.remove();
        }

        // Agregar modal al body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('detallesSolicitudModal'));
        modal.show();

        // Limpiar modal al cerrar
        const modalElement = document.getElementById('detallesSolicitudModal');
        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
        }, { once: true });
    }

    mostrarToast(titulo, mensaje, tipo = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: titulo,
                text: mensaje,
                icon: tipo,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            alert(`${titulo}: ${mensaje}`);
        }
    }
}

// Inicializar cuando el DOM esté listo
let gestionSolicitudes;
document.addEventListener('DOMContentLoaded', () => {
    gestionSolicitudes = new GestionSolicitudes();
});

