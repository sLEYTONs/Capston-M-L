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
        console.log('🔧 Iniciando inicialización del calendario...');
        
        // Ocultar mensajes de error/loading
        const loadingEl = document.getElementById('calendario-loading');
        const errorEl = document.getElementById('calendario-error');
        if (loadingEl) loadingEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        
        const calendarEl = document.getElementById('calendario-agendas');
        if (!calendarEl) {
            console.error('❌ Elemento calendario-agendas no encontrado');
            if (errorEl) {
                errorEl.style.display = 'block';
                errorEl.innerHTML = '<i class="fas fa-exclamation-triangle fa-3x mb-3"></i><p>Elemento del calendario no encontrado en el DOM.</p>';
            }
            return;
        }
        
        console.log('✅ Elemento calendario-agendas encontrado');

        // Verificar que FullCalendar esté disponible
        // El archivo index.global.min.js expone FullCalendar como objeto global
        let FullCalendarLib = null;
        
        // Intentar diferentes formas de acceso
        if (typeof window.FullCalendar !== 'undefined') {
            FullCalendarLib = window.FullCalendar;
        } else if (typeof FullCalendar !== 'undefined') {
            FullCalendarLib = FullCalendar;
        } else if (typeof window.fullCalendar !== 'undefined') {
            FullCalendarLib = window.fullCalendar;
        }
        
        if (!FullCalendarLib) {
            console.error('❌ FullCalendar no está disponible');
            console.log('Variables disponibles:', {
                'window.FullCalendar': typeof window.FullCalendar,
                'FullCalendar': typeof FullCalendar,
                'window.fullCalendar': typeof window.fullCalendar
            });
            if (errorEl) {
                errorEl.style.display = 'block';
                errorEl.innerHTML = '<i class="fas fa-exclamation-triangle fa-3x mb-3"></i><p>FullCalendar no se ha cargado correctamente. Verifica que el archivo main.min.js esté en assets/js/fullcalendar/</p><button class="btn btn-primary mt-2" onclick="location.reload()">Recargar Página</button>';
            }
            calendarEl.style.display = 'none';
            return;
        }
        
        console.log('✅ FullCalendar está disponible:', FullCalendarLib);
        
        // Mostrar el calendario
        calendarEl.style.display = 'block';

        // Destruir calendario existente si existe
        if (calendario) {
            console.log('🗑️ Destruyendo calendario existente...');
            calendario.destroy();
            calendario = null;
        }

        // Obtener eventos iniciales desde PHP si están disponibles
        let eventosIniciales = [];
        if (typeof window.eventosCalendarioIniciales !== 'undefined' && Array.isArray(window.eventosCalendarioIniciales)) {
            eventosIniciales = window.eventosCalendarioIniciales.map(evento => {
                // Asegurar formato correcto de fecha/hora
                let start = evento.start;
                let end = evento.end;
                
                // Si no tiene formato ISO completo, formatearlo
                if (start && !start.includes('T')) {
                    start = start.replace(' ', 'T');
                }
                if (end && !end.includes('T')) {
                    end = end.replace(' ', 'T');
                }
                
                return {
                    id: evento.id.toString(),
                    title: evento.title || 'Agenda',
                    start: start,
                    end: end,
                    allDay: false, // Asegurar que no sea evento de todo el día
                    extendedProps: {
                        agendaId: parseInt(evento.id),
                        disponible: evento.disponible !== undefined ? evento.disponible : 1,
                        observaciones: evento.observaciones || ''
                    }
                };
            });
        }

        try {
            // Obtener referencia a FullCalendar
            let FullCalendarLib = null;
            if (typeof window.FullCalendar !== 'undefined') {
                FullCalendarLib = window.FullCalendar;
            } else if (typeof FullCalendar !== 'undefined') {
                FullCalendarLib = FullCalendar;
            } else if (typeof window.fullCalendar !== 'undefined') {
                FullCalendarLib = window.fullCalendar;
            }
            
            if (!FullCalendarLib) {
                throw new Error('FullCalendar no está disponible');
            }
            
            calendario = new FullCalendarLib.Calendar(calendarEl, {
            initialView: vistaActual,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'es',
            timeZone: 'local',
            firstDay: 1, // Lunes
            // Configuración para vistas de tiempo (semana y día)
            slotMinTime: '09:00:00',
            slotMaxTime: '24:00:00',
            slotDuration: '00:30:00',
            slotLabelInterval: '01:00:00',
            snapDuration: '00:30:00',
            allDaySlot: false,
            height: 'auto',
            contentHeight: 'auto', // Altura automática para que quepan todas las horas
            scrollTime: '09:00:00',
            scrollTimeReset: false,
            slotLabelFormat: {
                hour: 'numeric',
                minute: '2-digit',
                omitZeroMinute: true,
                meridiem: 'short'
            },
            slotLabelContent: function(arg) {
                const date = arg.date;
                const hours = date.getHours();
                const minutes = date.getMinutes();
                
                if (minutes === 0) {
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    const displayHours = hours % 12 || 12;
                    return displayHours + ' ' + ampm;
                }
                return '';
            },
            editable: false,
                selectable: true, // Permitir selección para crear nuevas agendas
                selectMirror: true,
                selectMinDistance: 0, // Permitir selección con un solo clic
                dayMaxEvents: true,
                weekends: true,
                events: eventosIniciales, // Cargar eventos iniciales
                // Manejar clic directo en un slot de tiempo (más preciso)
                dateClick: function(info) {
                    // Abrir modal para crear nueva agenda con la fecha/hora del slot clickeado
                    limpiarFormulario();
                    
                    // info.date ya está en hora local gracias a timeZone: 'local'
                    const clickedDate = new Date(info.date);
                    
                    // Obtener componentes de fecha/hora en hora local
                    const year = clickedDate.getFullYear();
                    const month = String(clickedDate.getMonth() + 1).padStart(2, '0');
                    const day = String(clickedDate.getDate()).padStart(2, '0');
                    let hours = clickedDate.getHours(); // Hora local (0-23)
                    const minutes = clickedDate.getMinutes(); // Minutos locales (0-59)
                    
                    // Redondear a la hora completa
                    // Si estamos en la segunda mitad del slot (minutos >= 30), avanzar a la siguiente hora
                    if (minutes >= 30) {
                        hours = hours + 1;
                        if (hours >= 24) {
                            hours = 0;
                        }
                    }
                    
                    // Formatear fecha (YYYY-MM-DD)
                    const fecha = `${year}-${month}-${day}`;
                    
                    // Formatear hora inicio (HH:MM) - siempre será hora completa (MM = 00)
                    const horaInicio = String(hours).padStart(2, '0') + ':00';
                    
                    // Calcular hora de fin: inicio + 1 hora exacta
                    let horaFinHours = hours + 1;
                    if (horaFinHours >= 24) {
                        horaFinHours = 0;
                    }
                    const horaFin = String(horaFinHours).padStart(2, '0') + ':00';
                    
                    // Rellenar formulario
                    $('#agenda-fecha').val(fecha);
                    $('#agenda-hora-inicio').val(horaInicio);
                    $('#agenda-hora-fin').val(horaFin);
                    
                    // Abrir modal
                    const modalElement = document.getElementById('modalAgenda');
                    if (modalElement) {
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    }
                },
                // Permitir crear agenda haciendo clic y arrastrando (selección)
                select: function(selectInfo) {
                    // Abrir modal para crear nueva agenda con la fecha/hora seleccionada
                    limpiarFormulario();
                    
                    // Obtener la fecha/hora del slot seleccionado
                    // Usar selectInfo.start que ya está en hora local gracias a timeZone: 'local'
                    const start = new Date(selectInfo.start);
                    
                    // Obtener componentes de fecha/hora en hora local
                    // Estos métodos siempre devuelven valores en hora local
                    const year = start.getFullYear();
                    const month = String(start.getMonth() + 1).padStart(2, '0');
                    const day = String(start.getDate()).padStart(2, '0');
                    let hours = start.getHours(); // Hora local (0-23)
                    const minutes = start.getMinutes(); // Minutos locales (0-59)
                    const seconds = start.getSeconds(); // Segundos locales
                    
                    // Redondear a la hora completa basándose en el slot
                    // Si estamos en la segunda mitad del slot de 30 minutos (minutos >= 30), avanzar a la siguiente hora
                    if (minutes >= 30) {
                        hours = hours + 1;
                        // Manejar cambio de día si es necesario
                        if (hours >= 24) {
                            hours = 0;
                            // Nota: No manejamos cambio de día aquí porque sería complejo
                            // En la práctica, esto raramente ocurre con horarios de trabajo normales
                        }
                    }
                    
                    // Formatear fecha (YYYY-MM-DD)
                    const fecha = `${year}-${month}-${day}`;
                    
                    // Formatear hora inicio (HH:MM) - siempre será hora completa (MM = 00)
                    const horaInicio = String(hours).padStart(2, '0') + ':00';
                    
                    // Calcular hora de fin: inicio + 1 hora exacta
                    let horaFinHours = hours + 1;
                    if (horaFinHours >= 24) {
                        horaFinHours = 0;
                    }
                    const horaFin = String(horaFinHours).padStart(2, '0') + ':00';
                    
                    // Debug: mostrar en consola para verificar (puede comentarse después)
                    console.log('Selección de slot:', {
                        startOriginal: selectInfo.start,
                        startLocal: start,
                        year, month, day,
                        hoursOriginal: start.getHours(),
                        minutesOriginal: minutes,
                        hoursRedondeado: hours,
                        fecha,
                        horaInicio,
                        horaFin
                    });
                    
                    // Rellenar formulario
                    $('#agenda-fecha').val(fecha);
                    $('#agenda-hora-inicio').val(horaInicio);
                    $('#agenda-hora-fin').val(horaFin);
                    
                    // Abrir modal
                    const modalElement = document.getElementById('modalAgenda');
                    if (modalElement) {
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    }
                    
                    calendario.unselect();
                },
            eventClick: function(info) {
                // Al hacer clic en un evento, abrir modal de edición
                    info.jsEvent.preventDefault();
                editarAgendaDesdeCalendario(info.event);
            },
            eventDisplay: 'block',
            eventDidMount: function(info) {
                    // Agregar atributo data-disponible para estilos CSS
                    const disponible = info.event.extendedProps.disponible !== undefined ? info.event.extendedProps.disponible : 1;
                    $(info.el).attr('data-disponible', disponible);
                    
                    // Obtener las horas del evento para mostrarlas en el título
                    const start = new Date(info.event.start);
                    const end = new Date(info.event.end);
                    
                    // Formatear horas (HH:MM)
                    const horaInicio = String(start.getHours()).padStart(2, '0') + ':' + 
                                      String(start.getMinutes()).padStart(2, '0');
                    const horaFin = String(end.getHours()).padStart(2, '0') + ':' + 
                                   String(end.getMinutes()).padStart(2, '0');
                    
                    // Verificar si el título ya tiene el formato correcto (contiene horas)
                    const tituloActual = info.event.title || '';
                    const estado = disponible === 1 ? 'Disponible' : 'Ocupado';
                    
                    // Solo actualizar el título si no tiene el formato correcto o si es muy genérico
                    if (!tituloActual.includes('-') || tituloActual === 'Agenda' || tituloActual === 'Disponible' || tituloActual === 'Ocupado') {
                        const nuevoTitulo = `${horaInicio} - ${horaFin} (${estado})`;
                        info.event.setProp('title', nuevoTitulo);
                    } else if (!tituloActual.includes(estado)) {
                        // Si tiene horas pero no tiene el estado, agregarlo
                        const nuevoTitulo = `${tituloActual.split('(')[0].trim()} (${estado})`;
                        info.event.setProp('title', nuevoTitulo);
                    }
                    
                    // Agregar tooltip con información completa
                    let tooltip = `${horaInicio} - ${horaFin}\nEstado: ${estado}`;
                    if (info.event.extendedProps.observaciones) {
                        tooltip += `\nObservaciones: ${info.event.extendedProps.observaciones}`;
                    }
                    $(info.el).attr('title', tooltip);
            },
            datesSet: function() {
                // Cargar eventos cuando cambia la vista
                    setTimeout(function() {
                        cargarEventosCalendario();
                    }, 100);
                },
                loading: function(isLoading) {
                    if (isLoading) {
                        console.log('Calendario cargando...');
                    } else {
                        console.log('Calendario cargado');
                    }
                }
            });

            calendario.render();
            console.log('✅ Calendario renderizado correctamente');
            console.log('📅 Vista inicial:', vistaActual);
            
            // Cargar eventos después de un breve delay para asegurar que el calendario esté completamente renderizado
            setTimeout(function() {
                console.log('📥 Cargando eventos del calendario...');
                // Siempre cargar desde el servidor para tener datos actualizados
                cargarEventosCalendario();
            }, 500);
            
        } catch (error) {
            console.error('❌ Error al inicializar el calendario:', error);
            console.error('Stack trace:', error.stack);
            
            const errorEl = document.getElementById('calendario-error');
            const calendarEl = document.getElementById('calendario-agendas');
            if (errorEl) {
                errorEl.style.display = 'block';
                errorEl.innerHTML = `<i class="fas fa-exclamation-triangle fa-3x mb-3"></i><p>Error al inicializar el calendario: ${error.message}</p><button class="btn btn-primary mt-2" onclick="location.reload()">Recargar Página</button>`;
            }
            if (calendarEl) {
                calendarEl.style.display = 'none';
            }
        }
    }
    

    // Cargar eventos en el calendario desde la misma fuente que la tabla
    function cargarEventosCalendario() {
        if (!calendario) {
            console.warn('Calendario no inicializado');
            return;
        }

        try {
            // Usar la misma acción que la tabla para obtener todos los datos
            $.ajax({
                url: baseUrl,
                type: 'POST',
                data: {
                    accion: 'obtener_todas_agendas'
                },
                dataType: 'json',
                success: function(data) {
            if (data.status === 'success' && Array.isArray(data.data)) {
                const eventos = [];
                
                data.data.forEach(agenda => {
                    if (!agenda.Fecha || !agenda.HoraInicio || !agenda.HoraFin) return;

                            // Formato ISO: concatenar fecha + hora (YYYY-MM-DDTHH:MM:SS)
                            let horaInicio = agenda.HoraInicio;
                            let horaFin = agenda.HoraFin;
                            
                            // Asegurar formato completo (HH:MM:SS)
                            if (horaInicio.split(':').length === 2) {
                                horaInicio += ':00';
                            }
                            if (horaFin.split(':').length === 2) {
                                horaFin += ':00';
                            }

                            // Crear string ISO directamente
                            const startISO = agenda.Fecha + 'T' + horaInicio;
                            const endISO = agenda.Fecha + 'T' + horaFin;

                            // Determinar título con horas y estado
                            const estado = agenda.Disponible ? 'Disponible' : 'Ocupado';
                            let titulo = `${agenda.HoraInicio.substring(0, 5)} - ${agenda.HoraFin.substring(0, 5)} (${estado})`;
                            // No agregar observaciones al título para mantenerlo corto y legible

                    const evento = {
                        id: agenda.ID.toString(),
                                title: titulo,
                                start: startISO,
                                end: endISO,
                                allDay: false, // Asegurar que no sea evento de todo el día
                        extendedProps: {
                            agendaId: agenda.ID,
                            disponible: agenda.Disponible ? 1 : 0,
                            observaciones: agenda.Observaciones || ''
                        }
                    };

                    eventos.push(evento);
                });

                        // Remover eventos existentes y agregar nuevos
                calendario.removeAllEvents();
                eventos.forEach(evento => {
                            try {
                    calendario.addEvent(evento);
                            } catch (e) {
                                console.error('Error al agregar evento:', e, evento);
                            }
                        });
                        
                        console.log(`✅ Eventos cargados en calendario: ${eventos.length}`);
                    } else if (data.status === 'error') {
                        console.error('Error al cargar eventos:', data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar eventos del calendario:', error);
                }
            });
        } catch (error) {
            console.error('Error en cargarEventosCalendario:', error);
        }
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

    // Los botones de vista están integrados en el header del calendario de FullCalendar
    // No se necesitan botones adicionales

    // Inicializar DataTable
    function inicializarTabla() {
        if (tablaAgendas) {
            tablaAgendas.destroy();
        }

        // Limpiar el tbody para evitar problemas de conteo de columnas
        // Siempre usar AJAX para garantizar estructura consistente
        $('#tabla-agendas tbody').empty();

        // Inicializar DataTables siempre con AJAX para evitar problemas de conteo
        tablaAgendas = $('#tabla-agendas').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[1, 'desc'], [2, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
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
                            return '<span class="badge bg-danger">Ocupado</span>';
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
                    searchable: false,
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
        // Recargar tabla
        if (tablaAgendas) {
            // Si la tabla usa AJAX, recargar
            if (tablaAgendas.ajax) {
            tablaAgendas.ajax.reload(null, false);
            } else {
                // Si no usa AJAX, destruir y reinicializar
                tablaAgendas.destroy();
                inicializarTabla();
            }
        } else {
            inicializarTabla();
        }
        
        // Recargar calendario
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
                    // Actualizar tabla y calendario sin recargar página
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
                    // Actualizar tabla y calendario sin recargar página
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
        // Recargar la página para obtener datos frescos desde PHP
        window.location.reload();
    });

    // Verificar que todo esté listo antes de inicializar
    let intentosInicializacion = 0;
    const maxIntentos = 50; // 5 segundos máximo
    
    function verificarYInicializar() {
        intentosInicializacion++;
        
        // Verificar que jQuery esté cargado
        if (typeof jQuery === 'undefined') {
            if (intentosInicializacion < maxIntentos) {
                setTimeout(verificarYInicializar, 100);
            } else {
                console.error('❌ jQuery no se cargó después de varios intentos');
                mostrarErrorCalendario('jQuery no se ha cargado correctamente.');
            }
            return;
        }
        
        // Verificar que FullCalendar esté cargado
        let FullCalendarLib = null;
        if (typeof window.FullCalendar !== 'undefined') {
            FullCalendarLib = window.FullCalendar;
        } else if (typeof FullCalendar !== 'undefined') {
            FullCalendarLib = FullCalendar;
        } else if (typeof window.fullCalendar !== 'undefined') {
            FullCalendarLib = window.fullCalendar;
        }
        
        if (!FullCalendarLib) {
            if (intentosInicializacion < maxIntentos) {
                console.log(`⏳ Esperando FullCalendar... (intento ${intentosInicializacion}/${maxIntentos})`);
                setTimeout(verificarYInicializar, 100);
            } else {
                console.error('❌ FullCalendar no se cargó después de varios intentos');
                console.log('Debug - Variables disponibles:', {
                    'window.FullCalendar': typeof window.FullCalendar,
                    'FullCalendar': typeof FullCalendar,
                    'window.fullCalendar': typeof window.fullCalendar
                });
                mostrarErrorCalendario('FullCalendar no se ha cargado correctamente. Verifica que el archivo main.min.js esté en assets/js/fullcalendar/ y que se esté cargando correctamente.');
            }
            return;
        }
        
        // Verificar que el elemento del calendario exista
        const calendarEl = document.getElementById('calendario-agendas');
        if (!calendarEl) {
            if (intentosInicializacion < maxIntentos) {
                setTimeout(verificarYInicializar, 100);
            } else {
                console.error('❌ Elemento calendario-agendas no encontrado en el DOM');
                mostrarErrorCalendario('El elemento del calendario no se encontró en la página.');
            }
            return;
        }
        
        console.log('✅ Todo listo, inicializando calendario y tabla...');
        
        // Ocultar loading
        const loadingEl = document.getElementById('calendario-loading');
        if (loadingEl) loadingEl.style.display = 'none';

    // Inicializar
        try {
    inicializarCalendario();
    inicializarTabla();
        } catch (error) {
            console.error('❌ Error al inicializar:', error);
            mostrarErrorCalendario('Error al inicializar: ' + error.message);
        }
    }
    
    function mostrarErrorCalendario(mensaje) {
        const errorEl = document.getElementById('calendario-error');
        const calendarEl = document.getElementById('calendario-agendas');
        const loadingEl = document.getElementById('calendario-loading');
        
        if (loadingEl) loadingEl.style.display = 'none';
        if (errorEl) {
            errorEl.style.display = 'block';
            errorEl.innerHTML = `<i class="fas fa-exclamation-triangle fa-3x mb-3"></i><p>${mensaje}</p><button class="btn btn-primary mt-2" onclick="location.reload()">Recargar Página</button>`;
        }
        if (calendarEl) {
            calendarEl.style.display = 'none';
        }
    }
    
    // Mostrar loading inicialmente
    const loadingEl = document.getElementById('calendario-loading');
    if (loadingEl) loadingEl.style.display = 'block';
    
    // Iniciar verificación
    verificarYInicializar();
});
