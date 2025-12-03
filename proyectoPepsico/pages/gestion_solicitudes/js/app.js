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
                return response.text().then(text => {
                    // Intentar parsear como JSON
                    try {
                        const data = JSON.parse(text);
                        if (!response.ok) {
                            throw new Error(data.message || 'Error del servidor: ' + response.status);
                        }
                        return data;
                    } catch (e) {
                        // Si no es JSON, es un error del servidor
                        console.error('Respuesta no JSON:', text);
                        throw new Error('Error del servidor. Ver consola para detalles.');
                    }
                });
            })
            .then(data => {
                if (data.status === 'success') {
                    this.mostrarSolicitudes(Array.isArray(data.data) ? data.data : []);
                } else {
                    this.mostrarToast('Error', data.message || 'Error al cargar solicitudes', 'error');
                }
            })
            .catch(error => {
                console.error('Error al cargar solicitudes:', error);
                this.mostrarToast('Error', error.message || 'Error de conexión con el servidor', 'error');
            });
    }

    mostrarSolicitudes(solicitudes) {
        const tbody = document.querySelector('#solicitudes-table tbody');
        if (!tbody) {
            console.error('No se encontró el tbody de la tabla');
            return;
        }

        tbody.innerHTML = '';

        if (!Array.isArray(solicitudes) || solicitudes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay solicitudes disponibles</td></tr>';
            // Destruir DataTable si existe
            if (this.dataTable) {
                this.dataTable.destroy();
                this.dataTable = null;
            }
            return;
        }

        solicitudes.forEach(solicitud => {
            const row = document.createElement('tr');
            const estadoClass = this.getEstadoClass(solicitud.Estado || 'Pendiente');
            const estado = solicitud.Estado || 'Pendiente';
            const id = solicitud.ID || 0;
            const placa = solicitud.Placa || 'N/A';
            const marca = solicitud.Marca || '';
            const modelo = solicitud.Modelo || '';
            const proposito = solicitud.Proposito || '';
            const choferNombre = solicitud.ChoferNombre || solicitud.ChoferNombre || 'N/A';
            
            // Formatear hora de creación de la solicitud
            let horaSolicitud = 'N/A';
            if (solicitud.FechaCreacion) {
                try {
                    const fechaCreacion = new Date(solicitud.FechaCreacion);
                    if (!isNaN(fechaCreacion.getTime())) {
                        horaSolicitud = fechaCreacion.toLocaleTimeString('es-ES', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                } catch (e) {
                    console.error('Error al formatear fecha de creación:', e);
                }
            }

            row.innerHTML = `
                <td>${id}</td>
                <td>${placa}</td>
                <td>${marca} ${modelo}</td>
                <td>${proposito}</td>
                <td><span class="badge ${estadoClass}">${estado}</span></td>
                <td>${choferNombre}</td>
                <td>${horaSolicitud}</td>
                <td>
                    ${estado === 'Pendiente' ? `
                        <button class="btn btn-sm btn-primary" onclick="gestionSolicitudes.abrirModalGestionar(${id})">
                            <i class="fas fa-edit"></i> Gestionar
                        </button>
                    ` : `
                        <button class="btn btn-sm btn-info" onclick="gestionSolicitudes.verDetalles(${id})">
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
                pageLength: 10,
                responsive: true,
                autoWidth: true,
                scrollX: false
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

    actualizarFilaSolicitud(solicitudId, nuevoEstado) {
        if (!this.dataTable) {
            // Si no hay DataTable, recargar directamente
            this.cargarSolicitudes();
            return;
        }

        // Buscar la fila en el tbody por el ID
        const tbody = document.querySelector('#solicitudes-table tbody');
        if (!tbody) return;

        const filas = tbody.querySelectorAll('tr');
        let filaEncontrada = null;

        for (let fila of filas) {
            const primeraCelda = fila.cells[0];
            if (primeraCelda && primeraCelda.textContent.trim() == solicitudId) {
                filaEncontrada = fila;
                break;
            }
        }

        if (filaEncontrada) {
            // Actualizar el badge de estado (columna 4, índice 4)
            const estadoClass = this.getEstadoClass(nuevoEstado);
            filaEncontrada.cells[4].innerHTML = `<span class="badge ${estadoClass}">${nuevoEstado}</span>`;

            // Actualizar el botón de acciones (columna 6, índice 6)
            if (nuevoEstado === 'Pendiente') {
                filaEncontrada.cells[6].innerHTML = `
                    <button class="btn btn-sm btn-primary" onclick="gestionSolicitudes.abrirModalGestionar(${solicitudId})">
                        <i class="fas fa-edit"></i> Gestionar
                    </button>
                `;
            } else {
                filaEncontrada.cells[6].innerHTML = `
                    <button class="btn btn-sm btn-info" onclick="gestionSolicitudes.verDetalles(${solicitudId})">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                `;
            }

            // Redibujar el DataTable sin recargar datos
            this.dataTable.draw(false);
        } else {
            // Si no se encuentra la fila, recargar todas las solicitudes
            this.cargarSolicitudes();
        }
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

                    // Inicializar y cargar horas cuando el modal esté completamente visible
                    modal._element.addEventListener('shown.bs.modal', () => {
                        // Delay para asegurar que el DOM esté completamente renderizado
                        setTimeout(() => {
                            console.log('Inicializando listado...');
                            this.inicializarCalendario();
                            // Cargar horas disponibles
                            console.log('Cargando horas disponibles...');
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
        // Ya no usamos FullCalendar, solo inicializamos el listado
        console.log('Inicializando listado de horas disponibles...');
        const loadingEl = document.getElementById('calendario-loading');
        const listadoEl = document.getElementById('listado-horas-disponibles');
        
        if (loadingEl) {
            loadingEl.style.display = 'flex';
        }
        if (listadoEl) {
            listadoEl.style.display = 'none';
        }
    }

    cargarHorasDisponiblesCalendario() {
        console.log('Iniciando carga de horas disponibles...');
        const loadingEl = document.getElementById('calendario-loading');
        if (loadingEl) {
            loadingEl.style.display = 'flex';
        }

        // Usar la misma función que "Administrar Agendas" - obtener todas las agendas
        const formData = new FormData();
        formData.append('accion', 'obtener_todas_agendas');

        fetch(this.baseUrl, {
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
                console.log('Respuesta obtener_todas_agendas:', data);
                if (data.status === 'success' && Array.isArray(data.data)) {
                    console.log('Total de agendas recibidas:', data.data.length);
                    
                    // Filtrar solo las disponibles (Disponible = 1) y que estén en el rango de 9 AM - 11 PM
                    const hoy = new Date();
                    hoy.setHours(0, 0, 0, 0);
                    
                    // Obtener la hora actual
                    const ahora = new Date();
                    const horaActual = ahora.getHours();
                    const minutoActual = ahora.getMinutes();
                    const fechaActual = new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate());
                    
                    let horasFiltradas = data.data.filter(agenda => {
                        // Solo disponibles
                        if (agenda.Disponible != 1 && agenda.Disponible !== true && agenda.Disponible !== '1') {
                            return false;
                        }
                        
                        // Verificar que la fecha sea hoy o futura
                        if (!agenda.Fecha) {
                            return false;
                        }
                        const fechaAgenda = new Date(agenda.Fecha + 'T00:00:00');
                        if (fechaAgenda < fechaActual) {
                            return false;
                        }
                        
                        // Verificar que la hora de inicio esté entre 9 AM y 11 PM (permitir hasta 23:59)
                        const horaInicio = agenda.HoraInicio || '';
                        if (!horaInicio) {
                            return false;
                        }
                        // Convertir a formato comparable (HH:MM:SS)
                        const horaParts = horaInicio.split(':');
                        const horaNum = parseInt(horaParts[0]) || 0;
                        const minutoNum = parseInt(horaParts[1]) || 0;
                        
                        // Si la fecha es hoy, verificar que la hora no haya pasado
                        const esHoy = fechaAgenda.getTime() === fechaActual.getTime();
                        if (esHoy) {
                            // Si ya son las 21:00 o más tarde, solo mostrar horas desde las 21:00
                            if (horaActual >= 21) {
                                if (horaNum < 21) {
                                    return false;
                                }
                                // Si es exactamente las 21:00, verificar que el minuto no haya pasado
                                if (horaNum === 21 && minutoNum < minutoActual) {
                                    return false;
                                }
                            } else {
                                // Si es antes de las 21:00, aplicar el filtro normal (9 AM - 11 PM)
                                if (horaNum < 9 || horaNum > 23) {
                                    return false;
                                }
                                // Si es hoy y la hora ya pasó, no mostrarla
                                if (horaNum < horaActual || (horaNum === horaActual && minutoNum < minutoActual)) {
                                    return false;
                                }
                            }
                        } else {
                            // Para fechas futuras, aplicar el filtro normal (9 AM - 11 PM)
                            if (horaNum < 9 || horaNum > 23) {
                                return false;
                            }
                        }
                        
                        return true;
                    });

                    console.log('Horas filtradas (disponibles y rango):', horasFiltradas.length);

                    // Si no hay horas filtradas, mostrar mensaje
                    if (horasFiltradas.length === 0) {
                        console.warn('No hay horas disponibles después del filtrado');
                        this.horasDisponibles = [];
                        this.mostrarHorasEnListado([]);
                        return;
                    }

                    console.log('Horas filtradas (disponibles y rango 9 AM - 11 PM):', horasFiltradas.length);
                    console.log('Ejemplos de horas filtradas:', horasFiltradas.slice(0, 5));

                    // Mostrar directamente las horas filtradas
                    // La verificación de asignaciones se hará al momento de aprobar la solicitud
                    this.horasDisponibles = horasFiltradas;
                    this.mostrarHorasEnListado(horasFiltradas);
                } else {
                    console.warn('No se encontraron agendas o respuesta inválida:', data);
                    this.mostrarHorasEnListado([]);
                }
            })
            .catch(error => {
                console.error('Error al cargar horas disponibles:', error);
                this.mostrarHorasEnListado([]);
            });
    }

    mostrarHorasEnListado(horas) {
        const loadingEl = document.getElementById('calendario-loading');
        const listadoEl = document.getElementById('listado-horas-disponibles');
        const tbodyEl = document.getElementById('tbody-horas-disponibles');
        const sinHorasEl = document.getElementById('sin-horas-disponibles');

        if (loadingEl) {
            loadingEl.style.display = 'none';
        }

        if (!horas || horas.length === 0) {
            if (listadoEl) listadoEl.style.display = 'none';
            if (sinHorasEl) sinHorasEl.style.display = 'block';
            return;
        }

        if (sinHorasEl) sinHorasEl.style.display = 'none';
        if (listadoEl) listadoEl.style.display = 'block';
        if (!tbodyEl) return;

        // Limpiar tbody
        tbodyEl.innerHTML = '';

        // Agrupar por fecha
        const horasPorFecha = {};
        horas.forEach(hora => {
            const fecha = hora.fecha || hora.Fecha;
            if (!horasPorFecha[fecha]) {
                horasPorFecha[fecha] = [];
            }
            horasPorFecha[fecha].push(hora);
        });

        // Ordenar fechas
        const fechasOrdenadas = Object.keys(horasPorFecha).sort();

        // Ordenar horas dentro de cada fecha por HoraInicio (de más temprana a más tarde)
        fechasOrdenadas.forEach(fecha => {
            horasPorFecha[fecha].sort((a, b) => {
                const horaA = a.HoraInicio || a.horaInicio || '';
                const horaB = b.HoraInicio || b.horaInicio || '';
                return horaA.localeCompare(horaB);
            });
        });

        // Crear filas
        fechasOrdenadas.forEach(fecha => {
            horasPorFecha[fecha].forEach(hora => {
                const tr = document.createElement('tr');
                tr.style.cursor = 'pointer';
                tr.onclick = () => this.seleccionarHoraDesdeListado(hora);
                tr.onmouseenter = () => tr.style.backgroundColor = '#f0f0f0';
                tr.onmouseleave = () => tr.style.backgroundColor = '';

                // Formatear fecha
                const fechaObj = new Date(fecha + 'T00:00:00');
                const fechaFormateada = fechaObj.toLocaleDateString('es-ES', {
                    weekday: 'short',
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                });

                // Formatear horas
                const horaInicio = hora.HoraInicio.substring(0, 5);
                const horaFin = hora.HoraFin.substring(0, 5);

                const horaData = JSON.stringify(hora).replace(/"/g, '&quot;');
                tr.innerHTML = `
                    <td><strong>${fechaFormateada}</strong></td>
                    <td>${horaInicio}</td>
                    <td>${horaFin}</td>
                    <td>${hora.Observaciones || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-success" data-agenda-id="${hora.ID}">
                            <i class="fas fa-check me-1"></i>Seleccionar
                        </button>
                    </td>
                `;
                
                // Agregar evento al botón
                const btn = tr.querySelector('button');
                if (btn) {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.seleccionarHoraDesdeListado(hora);
                    });
                }

                tbodyEl.appendChild(tr);
            });
        });

        console.log('Listado de horas mostrado:', horas.length);
    }

    seleccionarHoraDesdeListado(hora) {
        const agendaId = hora.ID;
        const horaInicio = hora.HoraInicio;
        const horaFin = hora.HoraFin;
        let fecha = hora.fecha || hora.Fecha;

        console.log('Seleccionando hora desde listado - AgendaID:', agendaId, 'Fecha:', fecha);

        if (!fecha) {
            // Fallback: buscar en horasDisponibles usando el agendaId
            const horaEncontrada = this.horasDisponibles.find(h => h.ID == agendaId);
            if (horaEncontrada && (horaEncontrada.fecha || horaEncontrada.Fecha)) {
                fecha = horaEncontrada.fecha || horaEncontrada.Fecha;
                console.log('Fecha encontrada en horasDisponibles:', fecha);
            }
        }

        // Formatear hora para mostrar
        function formatearHora(hora) {
            const [horas, minutos] = hora.split(':');
            const hora24 = parseInt(horas);
            const ampm = hora24 >= 12 ? 'PM' : 'AM';
            const hora12 = hora24 % 12 || 12;
            return `${hora12}:${minutos} ${ampm}`;
        }

        // Formatear fecha para mostrar
        const [año, mes, dia] = fecha.split('-').map(Number);
        const fechaLocal = new Date(año, mes - 1, dia);
        const fechaFormateada = fechaLocal.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Guardar selección
        const agendaInput = document.getElementById('agenda-id-seleccionada');
        if (agendaInput) {
            agendaInput.value = agendaId;
        }

        // Mostrar información de selección
        const horaTexto = document.getElementById('hora-seleccionada-texto');
        const infoSeleccion = document.getElementById('info-seleccion-hora');

        if (horaTexto) {
            horaTexto.textContent = `${fechaFormateada} de ${formatearHora(horaInicio)} a ${formatearHora(horaFin)}`;
        }

        if (infoSeleccion) {
            infoSeleccion.style.display = 'block';
        }

        // Resaltar la fila seleccionada en el listado
        const tbodyEl = document.getElementById('tbody-horas-disponibles');
        if (tbodyEl) {
            const filas = tbodyEl.querySelectorAll('tr');
            filas.forEach(fila => {
                fila.classList.remove('table-success');
                const btn = fila.querySelector('button');
                if (btn) {
                    const btnOnClick = btn.getAttribute('onclick') || '';
                    if (btnOnClick.includes(agendaId.toString())) {
                        fila.classList.add('table-success');
                    }
                }
            });
        }

        console.log('Hora seleccionada:', { agendaId, fecha, horaInicio, horaFin });

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

                    // Actualizar la tabla inmediatamente
                    this.actualizarFilaSolicitud(this.solicitudActual.ID, 'Aprobada');

                    // Cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('gestionarSolicitudModal'));
                    if (modal) {
                        modal.hide();
                    }

                    // Recargar todas las solicitudes para asegurar datos actualizados
                    setTimeout(() => {
                        this.cargarSolicitudes();
                    }, 300);
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

                    // Actualizar la tabla inmediatamente
                    this.actualizarFilaSolicitud(this.solicitudActual.ID, 'Rechazada');

                    // Cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('gestionarSolicitudModal'));
                    if (modal) {
                        modal.hide();
                    }

                    // Recargar todas las solicitudes para asegurar datos actualizados
                    setTimeout(() => {
                        this.cargarSolicitudes();
                    }, 300);
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

        // Función helper para parsear fechas sin problemas de zona horaria
        const parsearFecha = (fechaString) => {
            if (!fechaString) return null;
            // Si viene en formato YYYY-MM-DD, parsear correctamente
            if (fechaString.match(/^\d{4}-\d{2}-\d{2}$/)) {
                const partes = fechaString.split('-');
                // Crear fecha en hora local (no UTC) para evitar problemas de zona horaria
                return new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
            }
            // Si ya viene formateada o en otro formato, usar Date normal
            return new Date(fechaString);
        };

        const fechaAgenda = (solicitud.FechaAgenda || solicitud.Fecha)
            ? (() => {
                const fecha = parsearFecha(solicitud.FechaAgenda || solicitud.Fecha);
                if (!fecha || isNaN(fecha.getTime())) return 'N/A';
                return fecha.toLocaleDateString('es-ES', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            })()
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
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
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

