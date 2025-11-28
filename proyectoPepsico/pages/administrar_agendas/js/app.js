$(document).ready(function() {
    let tablaAgendas;
    let calendario;
    let vistaActual = 'timeGridWeek'; // Vista por defecto: semana
    
    // Calcular la ruta base correcta
    function getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/agendamiento/scripts/s_agendamiento.php';
        }
        return '../../app/model/agendamiento/scripts/s_agendamiento.php';
    }
    
    const baseUrl = getBaseUrl();

    // Inicializar calendario
    function inicializarCalendario() {
        const calendarEl = document.getElementById('calendario-agendas');
        if (!calendarEl) return;

        // Destruir calendario existente si existe
        if (calendario) {
            calendario.destroy();
        }

        calendario = new FullCalendar.Calendar(calendarEl, {
            initialView: vistaActual,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'es',
            firstDay: 1, // Lunes
            slotMinTime: '08:00:00',
            slotMaxTime: '23:00:00',
            slotDuration: '00:30:00',
            allDaySlot: false,
            height: 'auto',
            editable: false,
            selectable: false,
            eventClick: function(info) {
                // Al hacer clic en un evento, abrir modal de edición
                editarAgendaDesdeCalendario(info.event);
            },
            eventDisplay: 'block',
            eventDidMount: function(info) {
                // Personalizar colores según disponibilidad
                if (info.event.extendedProps.disponible === 0) {
                    info.event.setProp('backgroundColor', '#dc3545');
                    info.event.setProp('borderColor', '#bb2d3b');
                } else if (info.event.extendedProps.asignada) {
                    info.event.setProp('backgroundColor', '#ffc107');
                    info.event.setProp('borderColor', '#ffb300');
                } else {
                    info.event.setProp('backgroundColor', '#28a745');
                    info.event.setProp('borderColor', '#1e7e34');
                }
            },
            datesSet: function() {
                // Cargar eventos cuando cambia la vista
                cargarEventosCalendario();
            }
        });

        calendario.render();
        cargarEventosCalendario();
    }

    // Cargar eventos en el calendario
    function cargarEventosCalendario() {
        if (!calendario) return;

        const view = calendario.view;
        const start = view.activeStart;
        const end = view.activeEnd;

        const fechaDesde = start.toISOString().split('T')[0];
        const fechaHasta = end.toISOString().split('T')[0];

        const formData = new FormData();
        formData.append('accion', 'obtener_horarios_por_rango');
        formData.append('fecha_desde', fechaDesde);
        formData.append('fecha_hasta', fechaHasta);

        fetch(baseUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && Array.isArray(data.data)) {
                const eventos = [];
                
                data.data.forEach(agenda => {
                    if (!agenda.Fecha || !agenda.HoraInicio || !agenda.HoraFin) return;

                    // Crear fecha y hora local
                    const [año, mes, dia] = agenda.Fecha.split('-').map(Number);
                    const [hInicio, mInicio] = agenda.HoraInicio.split(':').map(Number);
                    const [hFin, mFin] = agenda.HoraFin.split(':').map(Number);

                    const fechaHoraInicio = new Date(año, mes - 1, dia, hInicio, mInicio || 0);
                    const fechaHoraFin = new Date(año, mes - 1, dia, hFin, mFin || 0);

                    const evento = {
                        id: agenda.ID.toString(),
                        title: `${agenda.HoraInicio.substring(0, 5)} - ${agenda.HoraFin.substring(0, 5)}`,
                        start: fechaHoraInicio,
                        end: fechaHoraFin,
                        extendedProps: {
                            agendaId: agenda.ID,
                            disponible: agenda.Disponible ? 1 : 0,
                            asignada: agenda.SolicitudesAprobadas > 0,
                            observaciones: agenda.Observaciones || ''
                        }
                    };

                    eventos.push(evento);
                });

                calendario.removeAllEvents();
                eventos.forEach(evento => {
                    calendario.addEvent(evento);
                });
            }
        })
        .catch(error => {
            console.error('Error al cargar eventos:', error);
        });
    }

    // Editar agenda desde el calendario
    function editarAgendaDesdeCalendario(evento) {
        const agendaId = evento.extendedProps.agendaId;
        
        $.ajax({
            url: baseUrl,
            type: 'POST',
            data: {
                accion: 'obtener_agenda',
                id: agendaId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    const agenda = response.data;
                    $('#agenda-id').val(agenda.ID);
                    $('#agenda-fecha').val(agenda.Fecha);
                    $('#agenda-hora-inicio').val(agenda.HoraInicio ? agenda.HoraInicio.substring(0, 5) : '');
                    $('#agenda-hora-fin').val(agenda.HoraFin ? agenda.HoraFin.substring(0, 5) : '');
                    $('#agenda-disponible').prop('checked', agenda.Disponible == 1 || agenda.Disponible === true || agenda.Disponible === '1');
                    $('#agenda-observaciones').val(agenda.Observaciones || '');
                    $('#modalAgendaTitulo').html('<i class="fas fa-edit me-2"></i>Editar Agenda');
                    const modalElement = document.getElementById('modalAgenda');
                    if (modalElement) {
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    }
                } else {
                    mostrarAlerta(response.message || 'Error al cargar la agenda', 'error');
                }
            },
            error: function(xhr, status, error) {
                let mensaje = 'Error al cargar la agenda';
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        mensaje = response.message || mensaje;
                    } catch (e) {
                        mensaje = error || mensaje;
                    }
                }
                mostrarAlerta(mensaje, 'error');
            }
        });
    }

    // Cambiar vista del calendario
    $('#btn-vista-mes').on('click', function() {
        if (calendario) {
            calendario.changeView('dayGridMonth');
            vistaActual = 'dayGridMonth';
            $(this).addClass('active').siblings().removeClass('active');
        }
    });

    $('#btn-vista-semana').on('click', function() {
        if (calendario) {
            calendario.changeView('timeGridWeek');
            vistaActual = 'timeGridWeek';
            $(this).addClass('active').siblings().removeClass('active');
        }
    });

    $('#btn-vista-dia').on('click', function() {
        if (calendario) {
            calendario.changeView('timeGridDay');
            vistaActual = 'timeGridDay';
            $(this).addClass('active').siblings().removeClass('active');
        }
    });

    // Inicializar DataTable
    function inicializarTabla() {
        if (tablaAgendas) {
            tablaAgendas.destroy();
        }

        tablaAgendas = $('#tabla-agendas').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[1, 'desc'], [2, 'asc']],
            pageLength: 25,
            processing: true,
            serverSide: false,
            ajax: {
                url: baseUrl,
                type: 'POST',
                data: function(d) {
                    return {
                        accion: 'obtener_todas_agendas'
                    };
                },
                dataSrc: function(json) {
                    if (json.status === 'success') {
                        return json.data || [];
                    } else {
                        mostrarAlerta(json.message || 'Error al cargar agendas', 'error');
                        return [];
                    }
                },
                error: function(xhr, error, thrown) {
                    let mensaje = 'Error al cargar las agendas';
                    if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            mensaje = response.message || mensaje;
                        } catch (e) {
                            mensaje = 'Error de conexión con el servidor';
                        }
                    }
                    mostrarAlerta(mensaje, 'error');
                }
            },
            columns: [
                { data: 'ID' },
                { 
                    data: 'Fecha',
                    render: function(data) {
                        if (!data) return '-';
                        const fecha = new Date(data + 'T00:00:00');
                        return fecha.toLocaleDateString('es-ES', { 
                            year: 'numeric', 
                            month: '2-digit', 
                            day: '2-digit' 
                        });
                    }
                },
                { 
                    data: 'HoraInicio',
                    render: function(data) {
                        return data ? data.substring(0, 5) : '-';
                    }
                },
                { 
                    data: 'HoraFin',
                    render: function(data) {
                        return data ? data.substring(0, 5) : '-';
                    }
                },
                { 
                    data: 'Disponible',
                    render: function(data) {
                        if (data == 1 || data === true || data === '1') {
                            return '<span class="badge bg-success">Disponible</span>';
                        } else {
                            return '<span class="badge bg-danger">No Disponible</span>';
                        }
                    }
                },
                { 
                    data: 'Observaciones',
                    render: function(data) {
                        return data || '-';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-warning btn-editar" data-id="${row.ID}" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-eliminar" data-id="${row.ID}" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ]
        });
    }

    // Cargar agendas
    function cargarAgendas() {
        if (tablaAgendas) {
            tablaAgendas.ajax.reload(null, false);
        } else {
            inicializarTabla();
        }
        if (calendario) {
            cargarEventosCalendario();
        }
    }

    // Mostrar alerta
    function mostrarAlerta(mensaje, tipo = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: tipo,
                title: tipo === 'error' ? 'Error' : tipo === 'success' ? 'Éxito' : 'Información',
                text: mensaje,
                confirmButtonText: 'Aceptar'
            });
        } else {
            alert(mensaje);
        }
    }

    // Limpiar formulario
    function limpiarFormulario() {
        $('#agenda-id').val('');
        $('#agenda-fecha').val('');
        $('#agenda-hora-inicio').val('');
        $('#agenda-hora-fin').val('');
        $('#agenda-disponible').prop('checked', true);
        $('#agenda-observaciones').val('');
        $('#modalAgendaTitulo').html('<i class="fas fa-calendar-plus me-2"></i>Nueva Agenda');
    }

    // Abrir modal para nueva agenda
    $(document).on('click', '#btn-nueva-agenda', function(e) {
        e.preventDefault();
        limpiarFormulario();
        const modalElement = document.getElementById('modalAgenda');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    });

    // Guardar agenda
    $(document).on('click', '#btn-guardar-agenda', function() {
        const id = $('#agenda-id').val();
        const fecha = $('#agenda-fecha').val();
        const horaInicio = $('#agenda-hora-inicio').val();
        const horaFin = $('#agenda-hora-fin').val();
        const disponible = $('#agenda-disponible').is(':checked') ? 1 : 0;
        const observaciones = $('#agenda-observaciones').val();

        if (!fecha || !horaInicio || !horaFin) {
            mostrarAlerta('Por favor complete todos los campos obligatorios', 'warning');
            return;
        }

        if (horaInicio >= horaFin) {
            mostrarAlerta('La hora de inicio debe ser menor que la hora de fin', 'warning');
            return;
        }

        const datos = {
            accion: 'gestionar_agenda',
            id: id || '',
            fecha: fecha,
            hora_inicio: horaInicio,
            hora_fin: horaFin,
            disponible: disponible,
            observaciones: observaciones
        };

        $.ajax({
            url: baseUrl,
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    mostrarAlerta(response.message || 'Agenda guardada correctamente', 'success');
                    const modalElement = document.getElementById('modalAgenda');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                    }
                    cargarAgendas();
                } else {
                    mostrarAlerta(response.message || 'Error al guardar la agenda', 'error');
                }
            },
            error: function(xhr, status, error) {
                let mensaje = 'Error al guardar la agenda';
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        mensaje = response.message || mensaje;
                    } catch (e) {
                        mensaje = error || mensaje;
                    }
                }
                mostrarAlerta(mensaje, 'error');
            }
        });
    });

    // Editar agenda
    $(document).on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: baseUrl,
            type: 'POST',
            data: {
                accion: 'obtener_agenda',
                id: id
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    const agenda = response.data;
                    $('#agenda-id').val(agenda.ID);
                    $('#agenda-fecha').val(agenda.Fecha);
                    $('#agenda-hora-inicio').val(agenda.HoraInicio ? agenda.HoraInicio.substring(0, 5) : '');
                    $('#agenda-hora-fin').val(agenda.HoraFin ? agenda.HoraFin.substring(0, 5) : '');
                    $('#agenda-disponible').prop('checked', agenda.Disponible == 1 || agenda.Disponible === true || agenda.Disponible === '1');
                    $('#agenda-observaciones').val(agenda.Observaciones || '');
                    $('#modalAgendaTitulo').html('<i class="fas fa-edit me-2"></i>Editar Agenda');
                    const modalElement = document.getElementById('modalAgenda');
                    if (modalElement) {
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    }
                } else {
                    mostrarAlerta(response.message || 'Error al cargar la agenda', 'error');
                }
            },
            error: function(xhr, status, error) {
                let mensaje = 'Error al cargar la agenda';
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        mensaje = response.message || mensaje;
                    } catch (e) {
                        mensaje = error || mensaje;
                    }
                }
                mostrarAlerta(mensaje, 'error');
            }
        });
    });

    // Eliminar agenda
    $(document).on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Está seguro?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    eliminarAgenda(id);
                }
            });
        } else {
            if (confirm('¿Está seguro de eliminar esta agenda?')) {
                eliminarAgenda(id);
            }
        }
    });

    function eliminarAgenda(id) {
        $.ajax({
            url: baseUrl,
            type: 'POST',
            data: {
                accion: 'eliminar_agenda',
                id: id
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    mostrarAlerta(response.message || 'Agenda eliminada correctamente', 'success');
                    cargarAgendas();
                } else {
                    mostrarAlerta(response.message || 'Error al eliminar la agenda', 'error');
                }
            },
            error: function(xhr, status, error) {
                let mensaje = 'Error al eliminar la agenda';
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        mensaje = response.message || mensaje;
                    } catch (e) {
                        mensaje = error || mensaje;
                    }
                }
                mostrarAlerta(mensaje, 'error');
            }
        });
    }

    // Refrescar
    $(document).on('click', '#btn-refrescar', function(e) {
        e.preventDefault();
        cargarAgendas();
    });

    // Inicializar
    inicializarCalendario();
    inicializarTabla();
});
