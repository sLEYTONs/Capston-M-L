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
        // Ocultar mensajes de error/loading
        const loadingEl = document.getElementById('calendario-loading');
        const errorEl = document.getElementById('calendario-error');
        if (loadingEl) loadingEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        
        const calendarEl = document.getElementById('calendario-agendas');
        if (!calendarEl) {
            if (errorEl) {
                errorEl.style.display = 'block';
                errorEl.innerHTML = '<i class="fas fa-exclamation-triangle fa-3x mb-3"></i><p>Elemento del calendario no encontrado en el DOM.</p>';
            }
            return;
        }

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
            if (errorEl) {
                errorEl.style.display = 'block';
                errorEl.innerHTML = '<i class="fas fa-exclamation-triangle fa-3x mb-3"></i><p>FullCalendar no se ha cargado correctamente. Verifica que el archivo main.min.js esté en assets/js/fullcalendar/</p><button class="btn btn-primary mt-2" onclick="location.reload()">Recargar Página</button>';
            }
            calendarEl.style.display = 'none';
            return;
        }
        
        // Mostrar el calendario
        calendarEl.style.display = 'block';

        // Destruir calendario existente si existe
        if (calendario) {
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
                selectMirror: false, // DESACTIVADO: no mostrar overlay visual al seleccionar
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
            // Definir el contenido HTML del evento de forma nativa
            eventContent: function(arg) {
                const evento = arg.event;
                
                // Obtener horas del evento
                const start = new Date(evento.start);
                const end = new Date(evento.end);
                    
                    // Formatear horas (HH:MM)
                    const horaInicio = String(start.getHours()).padStart(2, '0') + ':' + 
                                      String(start.getMinutes()).padStart(2, '0');
                    const horaFin = String(end.getHours()).padStart(2, '0') + ':' + 
                                   String(end.getMinutes()).padStart(2, '0');
                    
                // Obtener estado
                const disponible = evento.extendedProps && evento.extendedProps.disponible !== undefined 
                    ? evento.extendedProps.disponible 
                    : 1;
                    const estado = disponible === 1 ? 'Disponible' : 'Ocupado';
                    
                // Construir título - SIEMPRE debe tener un valor
                let titulo = evento.title || '';
                
                // Si el título está vacío o es muy genérico, construir uno nuevo
                if (!titulo || titulo.trim() === '' || titulo === 'Agenda' || titulo === 'Nueva Agenda') {
                    titulo = `${horaInicio} - ${horaFin} (${estado})`;
                } 
                // Si el título no contiene horas, agregarlas
                else if (!titulo.includes(' - ') || !titulo.includes(horaInicio)) {
                    titulo = `${horaInicio} - ${horaFin} (${estado})`;
                }
                // Si tiene horas pero no tiene estado, agregarlo
                else if (!titulo.includes(estado) && !titulo.includes('Disponible') && !titulo.includes('Ocupado')) {
                    titulo = `${titulo.split('(')[0].trim()} (${estado})`;
                }
                
                // Asegurar que el título nunca esté vacío
                if (!titulo || titulo.trim() === '') {
                    titulo = `${horaInicio} - ${horaFin} (${estado})`;
                }
                
                // Actualizar el título en el objeto del evento
                if (evento.title !== titulo) {
                    evento.setProp('title', titulo);
                }
                
                // Retornar HTML del evento - FullCalendar lo renderizará automáticamente
                return {
                    html: `<span class="fc-event-title">${titulo}</span>`
                };
            },
            eventDidMount: function(info) {
                // Obtener estado disponible desde extendedProps
                const disponible = info.event.extendedProps.disponible !== undefined ? info.event.extendedProps.disponible : 1;
                
                // Agregar atributo data-disponible para estilos CSS (CRÍTICO para aplicar colores)
                $(info.el).attr('data-disponible', disponible);
                
                // PRIORIDAD 1: Usar colores del objeto evento si están definidos explícitamente
                // (Esto asegura que eventos nuevos dinámicos tengan los colores correctos)
                if (info.event.backgroundColor && info.event.borderColor) {
                    $(info.el).css({
                        'background-color': info.event.backgroundColor,
                        'border-left-color': info.event.borderColor,
                        'color': info.event.textColor || (disponible === 1 ? '#155724' : '#721c24')
                    });
                } 
                // PRIORIDAD 2: Aplicar colores directamente según estado (fallback)
                else {
                    const esDisponible = disponible === 1;
                    if (esDisponible) {
                        $(info.el).css({
                            'background-color': '#e8f5e9',
                            'border-left-color': '#34a853',
                            'color': '#155724'
                        });
                    } else {
                        $(info.el).css({
                            'background-color': '#ffebee',
                            'border-left-color': '#ea4335',
                            'color': '#721c24'
                        });
                    }
                }
                
                // Asegurar que el evento NO sea de día completo
                if (info.event.allDay !== false) {
                    info.event.setProp('allDay', false);
                }
                
                // Obtener horas para tooltip
                const start = new Date(info.event.start);
                const end = new Date(info.event.end);
                const horaInicio = String(start.getHours()).padStart(2, '0') + ':' + 
                                  String(start.getMinutes()).padStart(2, '0');
                const horaFin = String(end.getHours()).padStart(2, '0') + ':' + 
                               String(end.getMinutes()).padStart(2, '0');
                const estado = disponible === 1 ? 'Disponible' : 'Ocupado';
                
                // Agregar tooltip
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
                    // Estado de carga del calendario
                }
            });

            calendario.render();
            
            // Cargar eventos después de un breve delay para asegurar que el calendario esté completamente renderizado
            setTimeout(function() {
                // Siempre cargar desde el servidor para tener datos actualizados
                cargarEventosCalendario();
            }, 500);
            
        } catch (error) {
            
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
    // Retorna una promesa para poder esperarla
    function cargarEventosCalendario() {
        return new Promise(function(resolve, reject) {
        if (!calendario) {
            reject(new Error('Calendario no inicializado'));
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

                                // Usar función común para construir el evento con estructura estandarizada
                                const evento = construirEventoDesdeAgenda(agenda);
                                if (evento) {
                    eventos.push(evento);
                                }
                });

                        // Remover eventos existentes y agregar nuevos
                calendario.removeAllEvents();
                eventos.forEach(evento => {
                            try {
                    calendario.addEvent(evento);
                            } catch (e) {
                                // Error al agregar evento
                            }
                        });
                        
                            // FORZAR renderizado del calendario para asegurar que los eventos se muestren
                            calendario.render();
                            
                            resolve(eventos);
                    } else if (data.status === 'error') {
                            reject(new Error(data.message || 'Error al cargar eventos'));
                        } else {
                            resolve([]);
                    }
                },
                error: function(xhr, status, error) {
                        reject(new Error('Error al cargar eventos del calendario'));
                }
            });
        } catch (error) {
                reject(error);
        }
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

    // Los botones de vista están integrados en el header del calendario de FullCalendar
    // No se necesitan botones adicionales

    // Función helper para sumar 60 minutos a una hora (REGLA DE NEGOCIO: siempre 1 hora de duración)
    function calcularHoraFin(horaInicio) {
        if (!horaInicio) return null;
        
        // Normalizar formato (HH:MM o HH:MM:SS)
        let hora = String(horaInicio).trim();
        if (hora.split(':').length === 2) {
            hora += ':00';
        }
        
        // Crear objeto Date con la hora de inicio
        const partes = hora.split(':');
        const horas = parseInt(partes[0], 10);
        const minutos = parseInt(partes[1], 10);
        
        // Crear fecha de referencia (puede ser cualquier fecha)
        const fecha = new Date(2000, 0, 1, horas, minutos, 0);
        
        // Sumar 60 minutos (1 hora)
        fecha.setMinutes(fecha.getMinutes() + 60);
        
        // Formatear como HH:MM
        const horaFin = String(fecha.getHours()).padStart(2, '0') + ':' + 
                       String(fecha.getMinutes()).padStart(2, '0');
        
        return horaFin;
    }

    // Función común para construir un evento de calendario desde datos de agenda
    // Esta función asegura que todos los eventos tengan la misma estructura
    // INDISTINGUIBLE de los eventos que vienen del servidor
    function construirEventoDesdeAgenda(agendaData) {
        if (!agendaData || !agendaData.Fecha || !agendaData.HoraInicio || !agendaData.HoraFin) {
            return null;
        }
        
        // Normalizar formato de hora (HH:MM:SS)
        let horaInicio = String(agendaData.HoraInicio).trim();
        let horaFin = String(agendaData.HoraFin).trim();
        
        // Asegurar formato completo (HH:MM:SS)
        if (horaInicio.split(':').length === 2) {
            horaInicio += ':00';
        }
        if (horaFin.split(':').length === 2) {
            horaFin += ':00';
        }
        
        // Validar formato de fecha (YYYY-MM-DD)
        const fecha = String(agendaData.Fecha).trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
            return null;
        }
        
        // Crear string ISO estricto (YYYY-MM-DDTHH:mm:ss)
        // Este formato es el requerido por FullCalendar para eventos con tiempo
        const startISO = fecha + 'T' + horaInicio;
        const endISO = fecha + 'T' + horaFin;
        
        // Validar que las fechas ISO sean válidas
        if (isNaN(new Date(startISO).getTime()) || isNaN(new Date(endISO).getTime())) {
            return null;
        }
        
        // Extraer horas para el título (HH:MM)
        const horaInicioDisplay = horaInicio.substring(0, 5);
        const horaFinDisplay = horaFin.substring(0, 5);
        
        // Determinar estado (igual que cuando viene del servidor)
        const disponible = agendaData.Disponible === 1 || agendaData.Disponible === true || agendaData.Disponible === '1';
        const estado = disponible ? 'Disponible' : 'Ocupado';
        
        // Crear título con el mismo formato que cuando viene del servidor
        const titulo = `${horaInicioDisplay} - ${horaFinDisplay} (${estado})`;
        
        // Colores según el estado (mismos valores que CSS)
        const coloresDisponible = {
            backgroundColor: '#e8f5e9',  // var(--success-bg)
            borderColor: '#34a853',       // var(--success)
            textColor: '#155724'
        };
        
        const coloresOcupado = {
            backgroundColor: '#ffebee',   // var(--danger-bg)
            borderColor: '#ea4335',       // var(--danger)
            textColor: '#721c24'
        };
        
        const colores = disponible ? coloresDisponible : coloresOcupado;
        
        // Construir objeto evento con la MISMA estructura que cuando viene del servidor
        // Añadir propiedades de color EXPLÍCITAS para garantizar que se muestren correctamente
        const evento = {
            id: String(agendaData.ID), // ID como string (igual que del servidor)
            title: titulo,
            start: startISO, // Formato ISO estricto: YYYY-MM-DDTHH:mm:ss
            end: endISO,     // Formato ISO estricto: YYYY-MM-DDTHH:mm:ss
            allDay: false,   // CRÍTICO: debe ser false para respetar slots de horas
            backgroundColor: colores.backgroundColor,  // Color de fondo explícito
            borderColor: colores.borderColor,          // Color de borde explícito
            textColor: colores.textColor,              // Color de texto explícito
            extendedProps: {
                agendaId: parseInt(agendaData.ID),
                disponible: disponible ? 1 : 0, // 1 = Disponible (verde), 0 = Ocupado (rojo)
                observaciones: agendaData.Observaciones || ''
            }
        };
        
        return evento;
    }

    // Función helper para verificar si una agenda está vencida
    function verificarAgendaVencida(fecha, horaFin) {
        if (!fecha || !horaFin) {
            return false;
        }
        
        try {
            // Formatear hora si no tiene segundos
            let horaFinFormat = horaFin;
            if (horaFinFormat.split(':').length === 2) {
                horaFinFormat += ':00';
            }
            
            // Crear fecha/hora de fin de la agenda
            const fechaHoraFin = new Date(fecha + 'T' + horaFinFormat);
            const ahora = new Date();
            
            // Si la fecha/hora de fin ya pasó, está vencida
            return fechaHoraFin < ahora;
        } catch (e) {
            return false;
        }
    }

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
            order: [[0, 'desc']],
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
                { 
                    data: 'ID',
                    className: 'text-center'
                },
                { 
                    data: 'Fecha',
                    className: 'text-center',
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
                    className: 'text-center',
                    render: function(data) {
                        return data ? data.substring(0, 5) : '-';
                    }
                },
                { 
                    data: 'HoraFin',
                    className: 'text-center',
                    render: function(data) {
                        return data ? data.substring(0, 5) : '-';
                    }
                },
                { 
                    data: null,
                    className: 'text-center',
                    render: function(data, type, row) {
                        // Verificar si la agenda está vencida
                        const vencida = verificarAgendaVencida(row.Fecha, row.HoraFin);
                        const disponible = (row.Disponible == 1 || row.Disponible === true || row.Disponible === '1');
                        
                        if (vencida) {
                            return '<span class="badge badge-estado bg-secondary"><i class="fas fa-clock me-1"></i>Vencida</span>';
                        } else if (disponible) {
                            return '<span class="badge badge-estado bg-success"><i class="fas fa-check-circle me-1"></i>Disponible</span>';
                        } else {
                            return '<span class="badge badge-estado bg-danger"><i class="fas fa-times-circle me-1"></i>Ocupado</span>';
                        }
                    }
                },
                { 
                    data: 'Observaciones',
                    className: 'text-left',
                    render: function(data) {
                        return data || '-';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function(data, type, row) {
                        // Verificar si la agenda está vencida
                        const vencida = verificarAgendaVencida(row.Fecha, row.HoraFin);
                        
                        if (vencida) {
                            return '<span class="text-muted" title="Agenda vencida - No se pueden realizar acciones"><i class="fas fa-lock"></i></span>';
                        } else {
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

    // Función helper para recargar TODO: calendario + tabla de agendas
    function recargarTodo() {
        try {
            // PASO 1: Actualizar tabla de agendas de forma segura
            if (tablaAgendas) {
                try {
                    if (tablaAgendas.ajax && typeof tablaAgendas.ajax.reload === 'function') {
                        tablaAgendas.ajax.reload(null, false);
                    } else {
                        // Reinicializar tabla de forma segura
                        if (typeof tablaAgendas.destroy === 'function') {
                            tablaAgendas.destroy();
                        }
                        inicializarTabla();
                    }
                } catch (tablaError) {
                    // Continuar con el calendario aunque falle la tabla
                }
            } else {
                try {
                    inicializarTabla();
                } catch (tablaError) {
                    // Error al inicializar tabla
                }
            }
            
            // PASO 2: Actualizar calendario de forma segura
            if (!calendario) {
                return;
            }
            
            cargarEventosCalendario()
                .then(function(eventos) {
                    // Eventos cargados
                })
                .catch(function(error) {
                    // Error al cargar calendario
                });
                
        } catch (error) {
            // Error crítico en recargarTodo
        }
    }
    
    // Alias para compatibilidad
    function recargarCalendario() {
        recargarTodo();
    }
    
    // Función helper para limpiar overlays residuales de SweetAlert (solo si quedan)
    function limpiarOverlayResidual() {
        // Solo limpiar si realmente quedan elementos de SweetAlert después de que se cierre
        setTimeout(function() {
            try {
                // Buscar contenedores de SweetAlert específicamente
                const swalContainers = document.querySelectorAll('.swal2-container');
                swalContainers.forEach(function(container) {
                    // Verificar que realmente sea un contenedor de SweetAlert antes de eliminar
                    if (container && container.classList.contains('swal2-container') && container.parentNode) {
                        container.remove();
                    }
                });
                
                // Buscar backdrops de SweetAlert específicamente
                const swalBackdrops = document.querySelectorAll('.swal2-backdrop-show, .swal2-backdrop');
                swalBackdrops.forEach(function(backdrop) {
                    // Verificar que realmente sea un backdrop de SweetAlert antes de eliminar
                    if (backdrop && backdrop.classList.contains('swal2-backdrop') && backdrop.parentNode) {
                        backdrop.remove();
                    }
                });
                
                // Limpiar clases solo si existen y son de SweetAlert
                if (document.body) {
                    const bodyClasses = document.body.classList;
                    if (bodyClasses.contains('swal2-shown')) {
                        bodyClasses.remove('swal2-shown');
                        bodyClasses.remove('swal2-height-auto');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }
                }
                
                // Asegurar que el html no tenga clases bloqueantes
                if (document.documentElement) {
                    document.documentElement.classList.remove('swal2-shown', 'swal2-height-auto');
                    document.documentElement.style.overflow = '';
                    document.documentElement.style.paddingRight = '';
                }
            } catch (e) {
                // No hacer nada más si hay error, para no romper la página
            }
        }, 300);
    }

    // Mostrar alerta simple
    function mostrarAlerta(mensaje, tipo = 'info', callback = null) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: tipo,
                title: tipo === 'error' ? 'Error' : tipo === 'success' ? 'Éxito' : 'Información',
                text: mensaje,
                confirmButtonText: 'Aceptar'
            }).then((result) => {
                if (result.isConfirmed && callback && typeof callback === 'function') {
                    callback();
                }
            });
        } else {
            alert(mensaje);
            if (callback && typeof callback === 'function') {
                callback();
            }
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

    // Función para limpiar backdrop y restaurar estado del body
    function limpiarBackdropModal() {
        // Remover TODOS los backdrops que puedan existir (puede haber múltiples)
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(function(backdrop) {
            backdrop.remove();
        });
        
        // También remover cualquier backdrop que pueda estar oculto pero presente
        const allBackdrops = document.querySelectorAll('[class*="backdrop"]');
        allBackdrops.forEach(function(backdrop) {
            if (backdrop.classList.contains('modal-backdrop') || backdrop.classList.contains('swal2-backdrop')) {
                backdrop.remove();
            }
        });
        
        // Restaurar estado del body
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        document.body.classList.remove('modal-open');
        
        // Limpiar cualquier clase residual
        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
        document.documentElement.classList.remove('swal2-shown', 'swal2-height-auto', 'modal-open');
    }

    // Limpiar backdrop cuando se cierra el modal completamente (evento hidden.bs.modal)
    $('#modalAgenda').on('hidden.bs.modal', function() {
        limpiarBackdropModal();
    });

    // También limpiar cuando se intenta cerrar (evento hide.bs.modal) como medida de seguridad
    $('#modalAgenda').on('hide.bs.modal', function() {
        // Esto se ejecuta antes de que el modal se oculte completamente
        // Puede ayudar a prevenir que el backdrop quede atrapado
    });

    // Función simplificada para agregar evento al calendario
    // Usa la función común construirEventoDesdeAgenda para garantizar estructura idéntica
    function agregarEventoAlCalendario(agendaData) {
        if (!calendario || !agendaData) {
            return Promise.reject(new Error('Calendario o datos faltantes'));
        }
        
        return new Promise(function(resolve, reject) {
            try {
                // Usar función común para construir el evento con estructura estandarizada
                // Esto garantiza que tenga EXACTAMENTE la misma estructura que los eventos del servidor
                const evento = construirEventoDesdeAgenda(agendaData);
                
                if (!evento) {
                    reject(new Error('No se pudo construir el evento'));
                    return;
                }
                
                // Remover evento existente si existe (para actualización)
                const eventoExistente = calendario.getEventById(evento.id);
                if (eventoExistente) {
                    eventoExistente.remove();
                }
                
                // Agregar evento - FullCalendar lo renderizará usando eventContent y eventDidMount
                const eventoAgregado = calendario.addEvent(evento);
                
                if (!eventoAgregado) {
                    reject(new Error('No se pudo agregar el evento'));
                    return;
                }
                
                // Forzar renderizado inmediato
                calendario.render();
                
                // Esperar un breve momento para que FullCalendar complete el renderizado
                // y que eventDidMount se ejecute y aplique los estilos
                setTimeout(function() {
                    // Verificar que el evento se haya renderizado correctamente
                    const eventoRenderizado = calendario.getEventById(evento.id);
                    if (eventoRenderizado) {
                        resolve(eventoAgregado);
                    } else {
                        resolve(eventoAgregado); // Resolver de todos modos
                    }
                }, 100);
                
            } catch (error) {
                reject(error);
            }
        });
    }

    // Guardar agenda
    $(document).on('click', '#btn-guardar-agenda', function(e) {
        e.preventDefault();
        
        // ============================================
        // PASO 1: CAPTURAR DATOS DEL FORMULARIO ANTES DE CUALQUIER OTRA ACCIÓN
        // ============================================
        const formData = {
            id: $('#agenda-id').val() || '',
            fecha: $('#agenda-fecha').val() || '',
            horaInicio: $('#agenda-hora-inicio').val() || '',
            horaFin: $('#agenda-hora-fin').val() || '',
            disponible: $('#agenda-disponible').is(':checked') ? 1 : 0,
            observaciones: $('#agenda-observaciones').val() || ''
        };
        
        // Validaciones ANTES de enviar
        if (!formData.fecha || !formData.horaInicio) {
            mostrarAlerta('Por favor complete todos los campos obligatorios', 'warning');
            return;
        }

        // ============================================
        // REGLA DE NEGOCIO: Las agendas deben ser SIEMPRE de 1 HORA (60 minutos)
        // ============================================
        // FORZAR: hora_fin = hora_inicio + 60 minutos
        const horaFinCalculada = calcularHoraFin(formData.horaInicio);
        if (horaFinCalculada) {
            formData.horaFin = horaFinCalculada;
            // Actualizar el campo en el formulario visualmente (opcional)
            $('#agenda-hora-fin').val(horaFinCalculada);
        } else {
            mostrarAlerta('Error al calcular la hora de fin. Por favor, verifique la hora de inicio.', 'error');
            return;
        }

        // Preparar datos para enviar al servidor
        const datosEnvio = {
            accion: 'gestionar_agenda',
            id: formData.id,
            fecha: formData.fecha,
            hora_inicio: formData.horaInicio,
            hora_fin: formData.horaFin,
            disponible: formData.disponible,
            observaciones: formData.observaciones
        };
        
        // ============================================
        // PASO 2: ENVIAR AL SERVIDOR
        // ============================================
        $.ajax({
            url: baseUrl,
            type: 'POST',
            data: datosEnvio,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // ============================================
                    // PASO 3: CONSTRUIR OBJETO DE AGENDA CON DATOS COMPLETOS
                    // ============================================
                    let agendaCompleta = null;
                    
                    // Prioridad 1: Usar datos del backend si están completos
                    if (response.data && response.data.ID && response.data.Fecha && response.data.HoraInicio) {
                        // REGLA DE NEGOCIO: Forzar hora fin = hora inicio + 60 minutos (siempre)
                        const horaFinCalculada = calcularHoraFin(response.data.HoraInicio);
                        agendaCompleta = {
                            ID: parseInt(response.data.ID),
                            Fecha: response.data.Fecha,
                            HoraInicio: response.data.HoraInicio,
                            HoraFin: horaFinCalculada || response.data.HoraFin, // Usar calculada o la del servidor como fallback
                            Disponible: parseInt(response.data.Disponible) || 0,
                            Observaciones: response.data.Observaciones || ''
                        };
                    } 
                    // Prioridad 2: Construir desde datos del formulario + ID del servidor
                    else if (response.agenda_id) {
                        // REGLA DE NEGOCIO: Forzar hora fin = hora inicio + 60 minutos
                        const horaFinCalculada = calcularHoraFin(formData.horaInicio);
                        agendaCompleta = {
                            ID: parseInt(response.agenda_id),
                            Fecha: formData.fecha,
                            HoraInicio: formData.horaInicio.length === 5 ? formData.horaInicio + ':00' : formData.horaInicio,
                            HoraFin: horaFinCalculada || (formData.horaFin.length === 5 ? formData.horaFin + ':00' : formData.horaFin),
                            Disponible: formData.disponible,
                            Observaciones: formData.observaciones
                        };
                    } 
                    // Prioridad 3: Usar solo datos del formulario (último recurso)
                    else {
                        // REGLA DE NEGOCIO: Forzar hora fin = hora inicio + 60 minutos
                        const horaFinCalculada = calcularHoraFin(formData.horaInicio);
                        agendaCompleta = {
                            ID: formData.id ? parseInt(formData.id) : Date.now(), // ID temporal
                            Fecha: formData.fecha,
                            HoraInicio: formData.horaInicio.length === 5 ? formData.horaInicio + ':00' : formData.horaInicio,
                            HoraFin: horaFinCalculada || (formData.horaFin.length === 5 ? formData.horaFin + ':00' : formData.horaFin),
                            Disponible: formData.disponible,
                            Observaciones: formData.observaciones
                        };
                    }
                    
                    // ============================================
                    // PASO 4: ACTUALIZAR TABLA INMEDIATAMENTE
                    // ============================================
                    if (tablaAgendas) {
                        if (tablaAgendas.ajax) {
                            tablaAgendas.ajax.reload(null, false);
                        } else {
                            inicializarTabla();
                        }
                    } else {
                        inicializarTabla();
                    }
                    
                    // ============================================
                    // PASO 5: CERRAR MODAL
                    // ============================================
                    const modalElement = document.getElementById('modalAgenda');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                    }
                    
                    // Limpiar formulario después de cerrar el modal
                    setTimeout(function() {
                        limpiarFormulario();
                    }, 300);
                    
                    // ============================================
                    // PASO 6: RECARGAR CALENDARIO INMEDIATAMENTE (sin esperar al botón Aceptar)
                    // ============================================
                    if (calendario) {
                        cargarEventosCalendario()
                            .then(function(eventos) {
                                // Calendario actualizado
                            })
                            .catch(function(error) {
                                // Error al recargar calendario
                            });
                    }
                    
                    // Mostrar mensaje de éxito y recargar página al hacer clic en Aceptar
                    mostrarAlerta(
                        response.message || 'Agenda guardada correctamente', 
                        'success',
                        function() {
                            // Recargar página para eliminar overlay y mostrar datos frescos
                            window.location.reload();
                        }
                    );
                    
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
                    // PASO 1: Eliminar evento del calendario INMEDIATAMENTE usando su ID
                    if (calendario) {
                        try {
                            const eventoAEliminar = calendario.getEventById(String(id));
                            if (eventoAEliminar) {
                                eventoAEliminar.remove();
                                // Forzar renderizado para que se vea el cambio
                                calendario.render();
                            }
                        } catch (error) {
                            // Error al eliminar evento del calendario
                        }
                    }
                    
                    // PASO 2: Actualizar tabla inmediatamente
                    if (tablaAgendas) {
                        if (tablaAgendas.ajax) {
                            tablaAgendas.ajax.reload(null, false);
                        } else {
                            inicializarTabla();
                        }
                    } else {
                        inicializarTabla();
                    }
                    
                    // PASO 3: Recargar todos los eventos del calendario para asegurar sincronización
                    if (calendario) {
                        cargarEventosCalendario()
                            .then(function(eventos) {
                                // Calendario recargado completamente
                            })
                            .catch(function(error) {
                                // Error al recargar calendario
                            });
                    }
                    
                    // PASO 4: Mostrar mensaje de éxito y recargar página al hacer clic en Aceptar
                    mostrarAlerta(
                        response.message || 'Agenda eliminada correctamente', 
                        'success',
                        function() {
                            // Recargar página para eliminar overlay y mostrar datos frescos
                            window.location.reload();
                        }
                    );
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
                setTimeout(verificarYInicializar, 100);
            } else {
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
                mostrarErrorCalendario('El elemento del calendario no se encontró en la página.');
            }
            return;
        }
        
        // Ocultar loading
        const loadingEl = document.getElementById('calendario-loading');
        if (loadingEl) loadingEl.style.display = 'none';

    // Inicializar
        try {
    inicializarCalendario();
    inicializarTabla();
        } catch (error) {
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
