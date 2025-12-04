// Función para obtener la URL base del script
function getBaseUrl() {
    const currentPath = window.location.pathname;
    // Si estamos en pages/, construir la ruta relativa
    if (currentPath.includes('/pages/')) {
        // Obtener la parte antes de /pages/
        const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
        const url = basePath + '/app/model/agendamiento/scripts/s_agendamiento.php';
        return url;
    }
    // Fallback: ruta relativa desde pages/
    return '../../app/model/agendamiento/scripts/s_agendamiento.php';
}

document.addEventListener("DOMContentLoaded", () => {
    // Cargar solicitudes al iniciar
    cargarMisSolicitudes();
});

// Función para cargar las solicitudes del chofer
function cargarMisSolicitudes() {
    const tbody = document.querySelector('#mis-solicitudes-table tbody');
    if (!tbody) return;

    const formData = new FormData();
    formData.append('accion', 'obtener_solicitudes');

    const baseUrl = getBaseUrl();

    fetch(baseUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Verificar si la respuesta es JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Respuesta no JSON recibida:', text);
                throw new Error('El servidor devolvió una respuesta no válida. Ver consola para detalles.');
            });
        }
        
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Error HTTP:', response.status);
                console.error('Respuesta completa:', text);
                try {
                    const errorData = JSON.parse(text);
                    throw new Error(errorData.message || 'Error del servidor: ' + response.status);
                } catch (e) {
                    throw new Error('Error del servidor: ' + response.status + '. Ver consola para detalles.');
                }
            });
        }
        
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            mostrarSolicitudes(data.data);
        } else {
            console.error('Error en respuesta:', data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        // No mostrar notificación aquí para evitar spam, solo log
    });
}

// Función helper para parsear fechas sin problemas de zona horaria
function parsearFecha(fechaString) {
    if (!fechaString) return null;
    // Si viene en formato YYYY-MM-DD, parsear correctamente
    if (fechaString.match(/^\d{4}-\d{2}-\d{2}$/)) {
        const partes = fechaString.split('-');
        // Crear fecha en hora local (no UTC) para evitar problemas de zona horaria
        return new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
    }
    // Si ya viene formateada o en otro formato, usar Date normal
    return new Date(fechaString);
}

function formatearFechaAgenda(fechaString) {
    if (!fechaString) return null;
    const fecha = parsearFecha(fechaString);
    if (!fecha || isNaN(fecha.getTime())) return null;
    return fecha.toLocaleDateString('es-ES');
}

function mostrarSolicitudes(solicitudes) {
    const tbody = document.querySelector('#mis-solicitudes-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (solicitudes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No tiene solicitudes registradas</td></tr>';
        return;
    }

    solicitudes.forEach(solicitud => {
        const row = document.createElement('tr');
        
        // Usar el estado que viene del servidor (ya procesado con las nuevas reglas)
        // El servidor ahora determina el estado correcto basado en:
        // - Estado real del vehículo (Ingresado, En Espera, Asignado, En Progreso, Completo)
        // - Si salió del taller (Completado)
        // - Múltiples solicitudes del mismo día
        let estado = solicitud.Estado || 'Pendiente';
        
        // Verificar si la fecha de agenda ya pasó y el estado es "Pendiente"
        const fechaAgenda = solicitud.FechaAgenda || null;
        if (fechaAgenda && estado === 'Pendiente') {
            const fechaAgendaObj = new Date(fechaAgenda);
            const fechaActual = new Date();
            fechaActual.setHours(0, 0, 0, 0);
            fechaAgendaObj.setHours(0, 0, 0, 0);
            
            // Si la fecha de agenda es anterior a hoy, está en el pasado
            if (fechaAgendaObj < fechaActual) {
                estado = 'Cancelada';
            }
        }
        
        // Si aún no tiene estado, usar "Pendiente"
        if (!estado || estado === '') {
            estado = 'Pendiente';
        }
        
        const estadoClass = getEstadoClass(estado);
        
        // Usar fecha y hora asignadas por el supervisor si existen, sino mostrar "Pendiente"
        let fechaHoraAsignada = 'Pendiente';
        if (solicitud.FechaAgenda && solicitud.HoraInicioAgenda) {
            const fecha = formatearFechaAgenda(solicitud.FechaAgenda);
            if (fecha) {
                const horaInicio = solicitud.HoraInicioAgenda.substring(0, 5); // Formato HH:MM
                const horaFin = solicitud.HoraFinAgenda ? solicitud.HoraFinAgenda.substring(0, 5) : '';
                fechaHoraAsignada = `${fecha} ${horaInicio}${horaFin ? ' - ' + horaFin : ''}`;
            }
        }
        
        // Mostrar fecha y hora de respuesta (cuando el supervisor aprobó/rechazó)
        // Solo mostrar si el estado es Aprobada o Rechazada (cuando el supervisor respondió)
        let fechaRespuesta = '-';
        if ((estado === 'Aprobada' || estado === 'Rechazada') && solicitud.FechaActualizacion) {
            const fechaResp = new Date(solicitud.FechaActualizacion);
            fechaRespuesta = fechaResp.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }) + ' ' + fechaResp.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        // Determinar qué botones mostrar en acciones según el estado
        let accionesHTML = '';
        
        // Rechazada: mostrar motivo de rechazo
        if (solicitud.MotivoRechazo) {
            const motivoEscapado = solicitud.MotivoRechazo
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'")
                .replace(/"/g, '\\"')
                .replace(/\n/g, '\\n')
                .replace(/\r/g, '\\r');
            accionesHTML = `
                <button class="btn btn-sm btn-info" onclick="verMotivoRechazo('${motivoEscapado}')" title="Ver motivo de rechazo">
                    <i class="fas fa-info-circle"></i> Ver motivo
                </button>
            `;
        }
        // Completado: mostrar seguimiento solo si tiene mecánico y vehículo
        else if (estado === 'Completado') {
            if (solicitud.MecanicoNombre && solicitud.VehiculoID) {
                const asignacionId = solicitud.AsignacionID || '';
                const fechaAgenda = solicitud.FechaAgenda || '';
                const horaInicio = solicitud.HoraInicioAgenda || '';
                const horaFin = solicitud.HoraFinAgenda || '';
                accionesHTML = `
                    <button class="btn btn-sm btn-primary btn-ver-seguimiento" 
                            data-vehiculo-id="${solicitud.VehiculoID}" 
                            data-placa="${solicitud.Placa.replace(/"/g, '&quot;')}" 
                            data-asignacion-id="${asignacionId}" 
                            data-fecha-agenda="${fechaAgenda.replace(/"/g, '&quot;')}" 
                            data-solicitud-id="${solicitud.ID}"
                            data-hora-inicio="${horaInicio.replace(/"/g, '&quot;')}"
                            data-hora-fin="${horaFin.replace(/"/g, '&quot;')}"
                            title="Ver seguimiento del vehículo completado">
                        <i class="fas fa-eye"></i> Ver seguimiento
                    </button>
                `;
            } else {
                accionesHTML = `
                    <span class="text-success" style="font-size: 0.85rem;" title="Solicitud completada">
                        <i class="fas fa-check-circle"></i> Completado
                    </span>
                `;
            }
        }
        // Estados del vehículo en taller: Ingresado, En Espera, Asignado, En Progreso, Completo
        else if (['Ingresado', 'En Espera', 'Asignado', 'En Progreso', 'Completo'].includes(estado)) {
            if (solicitud.MecanicoNombre && solicitud.VehiculoID) {
                const asignacionId = solicitud.AsignacionID || '';
                const fechaAgenda = solicitud.FechaAgenda || '';
                const horaInicio = solicitud.HoraInicioAgenda || '';
                const horaFin = solicitud.HoraFinAgenda || '';
                accionesHTML = `
                    <button class="btn btn-sm btn-primary btn-ver-seguimiento" 
                            data-vehiculo-id="${solicitud.VehiculoID}" 
                            data-placa="${solicitud.Placa.replace(/"/g, '&quot;')}" 
                            data-asignacion-id="${asignacionId}" 
                            data-fecha-agenda="${fechaAgenda.replace(/"/g, '&quot;')}" 
                            data-solicitud-id="${solicitud.ID}"
                            data-hora-inicio="${horaInicio.replace(/"/g, '&quot;')}"
                            data-hora-fin="${horaFin.replace(/"/g, '&quot;')}"
                            title="Ver seguimiento del vehículo">
                        <i class="fas fa-eye"></i> Ver seguimiento
                    </button>
                `;
            } else {
                accionesHTML = `
                    <span class="text-info" style="font-size: 0.85rem;" title="Vehículo en taller, esperando asignación de mecánico">
                        <i class="fas fa-tools"></i> En taller
                    </span>
                `;
            }
        }
        // Cancelada: mostrar mensaje informativo
        else if (estado === 'Cancelada') {
            accionesHTML = `
                <span class="text-secondary" style="font-size: 0.85rem;" title="Esta solicitud fue cancelada">
                    <i class="fas fa-ban"></i> Cancelada
                </span>
            `;
        }
        // Aprobada: mostrar seguimiento si tiene mecánico, sino mensaje
        else if (estado === 'Aprobada') {
            if (solicitud.MecanicoNombre && solicitud.VehiculoID) {
                const asignacionId = solicitud.AsignacionID || '';
                const fechaAgenda = solicitud.FechaAgenda || '';
                const horaInicio = solicitud.HoraInicioAgenda || '';
                const horaFin = solicitud.HoraFinAgenda || '';
                accionesHTML = `
                    <button class="btn btn-sm btn-primary btn-ver-seguimiento" 
                            data-vehiculo-id="${solicitud.VehiculoID}" 
                            data-placa="${solicitud.Placa.replace(/"/g, '&quot;')}" 
                            data-asignacion-id="${asignacionId}" 
                            data-fecha-agenda="${fechaAgenda.replace(/"/g, '&quot;')}" 
                            data-solicitud-id="${solicitud.ID}"
                            data-hora-inicio="${horaInicio.replace(/"/g, '&quot;')}"
                            data-hora-fin="${horaFin.replace(/"/g, '&quot;')}"
                            title="Ver seguimiento del vehículo">
                        <i class="fas fa-eye"></i> Ver seguimiento
                    </button>
                `;
            } else {
                accionesHTML = `
                    <span class="text-info" style="font-size: 0.85rem;" title="Solicitud aprobada, esperando asignación de mecánico">
                        <i class="fas fa-check-circle"></i> Aprobada
                    </span>
                `;
            }
        }
        // Pendiente sin fecha/hora
        else if (fechaHoraAsignada === 'Pendiente' && estado === 'Pendiente') {
            accionesHTML = `
                <span class="text-muted" style="font-size: 0.85rem;" title="Esta solicitud está pendiente de asignación de fecha y hora por el supervisor">
                    <i class="fas fa-clock"></i> Sin asignar
                </span>
            `;
        }
        // Pendiente pero con otro estado (puede ser que pasó la fecha)
        else if (fechaHoraAsignada === 'Pendiente' && estado !== 'Pendiente') {
            const fechaCreacion = solicitud.FechaCreacion ? new Date(solicitud.FechaCreacion) : null;
            const ahora = new Date();
            const diasDiferencia = fechaCreacion ? Math.floor((ahora - fechaCreacion) / (1000 * 60 * 60 * 24)) : 0;
            
            if (diasDiferencia > 0) {
                accionesHTML = `
                    <span class="text-warning" style="font-size: 0.85rem;" title="Esta solicitud quedó sin fecha asignada. Contacte al supervisor.">
                        <i class="fas fa-exclamation-triangle"></i> Sin fecha
                    </span>
                `;
            } else {
                accionesHTML = `
                    <span class="text-muted" style="font-size: 0.85rem;" title="Pendiente de asignación de fecha y hora">
                        <i class="fas fa-clock"></i> Pendiente
                    </span>
                `;
            }
        }
        // Por defecto
        else {
            accionesHTML = '-';
        }

        row.innerHTML = `
            <td>${solicitud.ID}</td>
            <td>${solicitud.Placa}</td>
            <td>${solicitud.Marca} ${solicitud.Modelo}</td>
            <td>${fechaHoraAsignada}</td>
            <td><span class="badge ${estadoClass}">${estado}</span></td>
            <td>${fechaRespuesta}</td>
            <td>${accionesHTML}</td>
        `;
        tbody.appendChild(row);
    });

    // Inicializar DataTable si está disponible
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#mis-solicitudes-table')) {
            $('#mis-solicitudes-table').DataTable().destroy();
        }
        $('#mis-solicitudes-table').DataTable({
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
            scrollX: true,
            autoWidth: false,
            columnDefs: [
                { width: "80px", targets: 0 }, // ID
                { width: "100px", targets: 1 }, // Placa
                { width: "150px", targets: 2 }, // Vehículo
                { width: "150px", targets: 3 }, // Fecha/Hora
                { width: "100px", targets: 4 }, // Estado
                { width: "120px", targets: 5 }, // Fecha Respuesta
                { width: "120px", targets: 6 }  // Acciones
            ]
        });
    }
}

function getEstadoClass(estado) {
    const clases = {
        'Pendiente': 'bg-pendiente',
        'Aprobada': 'bg-aprobada',
        'Rechazada': 'bg-rechazada',
        'Cancelada': 'bg-cancelada',
        'Cancelado': 'bg-cancelada', // Alias para Cancelado
        'Completado': 'bg-completado', // Color único para completado (azul/verde azulado)
        'Completada': 'bg-completado', // Alias para Completada
        // Estados del vehículo en taller
        'Ingresado': 'bg-ingresado',
        'En Espera': 'bg-espera',
        'Asignado': 'bg-asignado',
        'En Progreso': 'bg-progreso',
        'Completo': 'bg-completo' // Vehículo completado pero aún en taller
    };
    return clases[estado] || 'bg-secondary';
}

function verMotivoRechazo(motivo) {
    if (!motivo || motivo.trim() === '') {
        alert('No hay motivo de rechazo disponible');
        return;
    }
    
    const modalElement = document.getElementById('motivo-rechazo-modal');
    const textoElement = document.getElementById('motivo-rechazo-texto');
    
    if (!modalElement || !textoElement) {
        alert('Motivo de rechazo:\n\n' + motivo);
        return;
    }
    
    // Detectar el tipo de motivo
    const motivoLower = motivo.toLowerCase();
    const esNoLlego = motivoLower.includes('no llegó') || motivoLower.includes('no llego') || motivoLower.includes('pasó más de 30 minutos');
    const esAtrasado = motivoLower.includes('atrasado') || motivoLower.includes('margen de atraso') || motivoLower.includes('0-30 minutos');
    const esFechaIncorrecta = motivoLower.includes('fecha posterior') || motivoLower.includes('fecha incorrecta');
    
    // Formatear el motivo - separar por "|" si existe
    const partesMotivo = motivo.split('|').map(p => p.trim()).filter(p => p);
    let motivoPrincipal = partesMotivo[0] || motivo;
    let motivoAdicional = partesMotivo.length > 1 ? partesMotivo.slice(1).join(' | ') : '';
    
    // Limpiar texto repetitivo
    motivoPrincipal = motivoPrincipal.replace(/^\s*\|\s*/, '').trim();
    
    // Configurar el modal según el tipo
    if (esNoLlego) {
        // No llegó - más de 30 minutos
        $('#motivo-rechazo-header').removeClass('bg-warning text-dark bg-info text-white').addClass('bg-danger text-white');
        $('#motivo-rechazo-titulo').html('<i class="fas fa-times-circle me-2"></i>Vehículo No Llegó');
        $('#motivo-rechazo-titulo-principal').text('Vehículo No Llegó a Tiempo').removeClass('text-warning text-info').addClass('text-danger');
        $('#motivo-rechazo-subtitulo').text('El vehículo no llegó dentro del margen de tiempo permitido');
        $('#motivo-rechazo-icono').html('<i class="fas fa-times-circle fa-4x text-danger"></i>');
        $('#motivo-rechazo-alerta').removeClass('alert-warning alert-info').addClass('alert-danger');
        $('#motivo-rechazo-icono-alerta').removeClass('text-warning text-info').addClass('text-danger');
    } else if (esAtrasado) {
        // Atrasado - dentro de 30 minutos
        $('#motivo-rechazo-header').removeClass('bg-danger text-white bg-info text-white').addClass('bg-warning text-dark');
        $('#motivo-rechazo-titulo').html('<i class="fas fa-clock me-2"></i>Vehículo Atrasado');
        $('#motivo-rechazo-titulo-principal').text('Vehículo Llegó Fuera del Margen').removeClass('text-danger text-info').addClass('text-warning');
        $('#motivo-rechazo-subtitulo').text('El vehículo llegó después de la hora asignada');
        $('#motivo-rechazo-icono').html('<i class="fas fa-exclamation-triangle fa-4x text-warning"></i>');
        $('#motivo-rechazo-alerta').removeClass('alert-danger alert-info').addClass('alert-warning');
        $('#motivo-rechazo-icono-alerta').removeClass('text-danger text-info').addClass('text-warning');
    } else if (esFechaIncorrecta) {
        // Fecha incorrecta
        $('#motivo-rechazo-header').removeClass('bg-danger text-white bg-info text-white').addClass('bg-warning text-dark');
        $('#motivo-rechazo-titulo').html('<i class="fas fa-calendar-times me-2"></i>Fecha Incorrecta');
        $('#motivo-rechazo-titulo-principal').text('Vehículo Llegó en Fecha Incorrecta').removeClass('text-danger text-info').addClass('text-warning');
        $('#motivo-rechazo-subtitulo').text('El vehículo llegó en una fecha diferente a la asignada');
        $('#motivo-rechazo-icono').html('<i class="fas fa-calendar-times fa-4x text-warning"></i>');
        $('#motivo-rechazo-alerta').removeClass('alert-danger alert-info').addClass('alert-warning');
        $('#motivo-rechazo-icono-alerta').removeClass('text-danger text-info').addClass('text-warning');
    } else {
        // Rechazo normal
        $('#motivo-rechazo-header').removeClass('bg-warning text-dark bg-danger text-white').addClass('bg-info text-white');
        $('#motivo-rechazo-titulo').html('<i class="fas fa-info-circle me-2"></i>Motivo de Rechazo');
        $('#motivo-rechazo-titulo-principal').text('Solicitud Rechazada').removeClass('text-warning text-danger').addClass('text-info');
        $('#motivo-rechazo-subtitulo').text('Información sobre el motivo del rechazo');
        $('#motivo-rechazo-icono').html('<i class="fas fa-info-circle fa-4x text-info"></i>');
        $('#motivo-rechazo-alerta').removeClass('alert-warning alert-danger').addClass('alert-info');
        $('#motivo-rechazo-icono-alerta').removeClass('text-warning text-danger').addClass('text-info');
    }
    
    // Formatear el contenido del motivo
    let contenidoHTML = '';
    if (partesMotivo.length > 1) {
        contenidoHTML = `
            <div class="mb-3">
                <strong class="d-block mb-2">Motivo Principal:</strong>
                <p class="mb-0">${motivoPrincipal}</p>
            </div>
            <div>
                <strong class="d-block mb-2">Detalles Adicionales:</strong>
                <p class="mb-0 text-muted">${motivoAdicional}</p>
            </div>
        `;
    } else {
        // Dividir en párrafos si hay saltos de línea
        const parrafos = motivo.split('\n').filter(p => p.trim());
        if (parrafos.length > 1) {
            contenidoHTML = parrafos.map(p => `<p class="mb-2">${p.trim()}</p>`).join('');
        } else {
            contenidoHTML = `<p class="mb-0">${motivo}</p>`;
        }
    }
    
    textoElement.innerHTML = contenidoHTML;
    
    // Mostrar información adicional si es atrasado o no llegó
    if (esNoLlego || esAtrasado || esFechaIncorrecta) {
        $('#motivo-rechazo-info-adicional').show();
        const mensajeInfo = esNoLlego 
            ? 'La solicitud ha sido marcada automáticamente como <strong class="text-danger">"No llegó"</strong> y el proceso ha sido cerrado. Debe crear una nueva solicitud de agendamiento.'
            : esAtrasado
            ? 'La solicitud ha sido marcada automáticamente como <strong class="text-warning">"Atrasada"</strong> y el proceso ha sido cancelado. Debe crear una nueva solicitud de agendamiento.'
            : 'La solicitud ha sido marcada automáticamente como <strong class="text-warning">"Atrasada"</strong> debido a la fecha incorrecta. Debe crear una nueva solicitud de agendamiento.';
        $('#motivo-rechazo-info-texto').html(mensajeInfo);
    } else {
        $('#motivo-rechazo-info-adicional').hide();
    }
    
    // Mostrar el modal usando Bootstrap
    if (typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        // Fallback si Bootstrap no está disponible
        modalElement.style.display = 'block';
    }
}

// Función para obtener la URL del script de seguimiento
function getSeguimientoUrl() {
    const currentPath = window.location.pathname;
    if (currentPath.includes('/pages/')) {
        const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
        return basePath + '/app/model/consulta/scripts/s_seguimiento.php';
    }
    return '../../app/model/consulta/scripts/s_seguimiento.php';
}

// Función para ver el seguimiento del vehículo
function verSeguimiento(vehiculoId, placa, asignacionId = null, fechaAgenda = null, solicitudId = null, horaInicio = null, horaFin = null) {
    const modal = document.getElementById('seguimiento-modal');
    const content = document.getElementById('seguimiento-content');
    
    if (!modal || !content) return;
    
    // Mostrar modal con loading
    modal.style.display = 'block';
    content.innerHTML = `
        <div class="text-center" style="padding: 2rem;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando información del seguimiento...</p>
        </div>
    `;
    
    // Obtener seguimiento
    const formData = new FormData();
    formData.append('vehiculo_id', vehiculoId);
    if (asignacionId && asignacionId !== 'null' && asignacionId !== null && asignacionId !== '' && asignacionId !== 'undefined') {
        formData.append('asignacion_id', asignacionId);
    }
    // Procesar fechaAgenda
    if (fechaAgenda && fechaAgenda !== 'null' && fechaAgenda !== null && fechaAgenda !== '' && fechaAgenda !== 'undefined') {
        formData.append('fecha_agenda', fechaAgenda);
    }
    // Procesar solicitudId
    if (solicitudId && solicitudId !== 'null' && solicitudId !== null && solicitudId !== '' && solicitudId !== 'undefined') {
        formData.append('solicitud_id', solicitudId);
    }
    
    fetch(getSeguimientoUrl(), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            mostrarSeguimiento(data.data, placa, fechaAgenda, horaInicio, horaFin, solicitudId);
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${data.message || 'Error al cargar el seguimiento'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Error de conexión con el servidor
            </div>
        `;
    });
}

// Función para mostrar el seguimiento en el modal
function mostrarSeguimiento(datos, placa, fechaAgenda = null, horaInicio = null, horaFin = null, solicitudId = null) {
    const content = document.getElementById('seguimiento-content');
    const vehiculo = datos.vehiculo || {};
    const asignacion = datos.asignacion || null;
    const avances = datos.avances || [];
    const solicitud = datos.solicitud || null;
    
    // Formatear fecha y hora
    let fechaHoraAsignada = '';
    if (fechaAgenda) {
        const fechaFormateada = formatearFechaAgenda(fechaAgenda);
        if (fechaFormateada) {
            fechaHoraAsignada = fechaFormateada;
            if (horaInicio) {
                const horaInicioFormateada = horaInicio.substring(0, 5);
                fechaHoraAsignada += ` ${horaInicioFormateada}`;
                if (horaFin) {
                    const horaFinFormateada = horaFin.substring(0, 5);
                    fechaHoraAsignada += ` - ${horaFinFormateada}`;
                }
            }
        }
    }
    
    let html = `
        <div style="padding: 1rem;">
            <h4 style="color: #667eea; margin-bottom: 1.5rem;">
                <i class="fas fa-car me-2"></i>
                Vehículo: ${placa}
            </h4>
    `;
    
    // Mostrar información de la solicitud si está disponible
    let fechaHoraSolicitud = '';
    if (solicitud) {
        if (solicitud.FechaAgenda) {
            fechaHoraSolicitud = solicitud.FechaAgenda;
            if (solicitud.HoraInicioAgenda) {
                fechaHoraSolicitud += ` ${solicitud.HoraInicioAgenda}`;
                if (solicitud.HoraFinAgenda) {
                    fechaHoraSolicitud += ` - ${solicitud.HoraFinAgenda}`;
                }
            }
        }
    } else if (fechaHoraAsignada) {
        fechaHoraSolicitud = fechaHoraAsignada;
    }
    
    const idSolicitudMostrar = (solicitud && solicitud.ID) ? solicitud.ID : solicitudId;
    
    if (idSolicitudMostrar || fechaHoraSolicitud) {
        html += `
            <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #ffc107;">
                <h5 style="margin-bottom: 1rem; color: #333;">
                    <i class="fas fa-calendar-check me-2"></i>
                    Información de la Solicitud
                </h5>
                ${idSolicitudMostrar ? `<p><strong>ID de Solicitud:</strong> #${idSolicitudMostrar}</p>` : ''}
                ${fechaHoraSolicitud ? `<p><strong>Fecha/Hora Asignada:</strong> ${fechaHoraSolicitud}</p>` : ''}
            </div>
        `;
    }
    
    html += `
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <h5 style="margin-bottom: 1rem; color: #333;">Información del Vehículo</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Marca:</strong> ${vehiculo.Marca || 'N/A'}</p>
                        <p><strong>Modelo:</strong> ${vehiculo.Modelo || 'N/A'}</p>
                        <p><strong>Tipo:</strong> ${vehiculo.TipoVehiculo || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Año:</strong> ${vehiculo.Anio || 'N/A'}</p>
                        <p><strong>Conductor:</strong> ${vehiculo.ConductorNombre || 'N/A'}</p>
                    </div>
                </div>
            </div>
    `;
    
    // Obtener la fecha y hora de inicio de la solicitud para usar como base
    let fechaSolicitudParaMostrar = '';
    let horaInicioSolicitud = null; // Para calcular minutos incrementales
    
    if (solicitud && solicitud.FechaAgenda) {
        // Si viene en formato DD/MM/YYYY, usar directamente
        if (solicitud.FechaAgenda.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
            fechaSolicitudParaMostrar = solicitud.FechaAgenda;
        } else {
            // Si viene en otro formato, formatearlo
            const fechaFormateada = formatearFechaAgenda(solicitud.FechaAgenda);
            if (fechaFormateada) {
                fechaSolicitudParaMostrar = fechaFormateada;
            }
        }
    } else if (fechaHoraSolicitud) {
        // Extraer solo la fecha (antes del espacio, guión o cualquier carácter no numérico después de la fecha)
        const fechaMatch = fechaHoraSolicitud.match(/^(\d{2}\/\d{2}\/\d{4})/);
        if (fechaMatch) {
            fechaSolicitudParaMostrar = fechaMatch[1];
        } else {
            // Intentar con formato YYYY-MM-DD
            const fechaMatch2 = fechaHoraSolicitud.match(/^(\d{4}-\d{2}-\d{2})/);
            if (fechaMatch2) {
                const fechaParts = fechaMatch2[1].split('-');
                fechaSolicitudParaMostrar = `${fechaParts[2]}/${fechaParts[1]}/${fechaParts[0]}`;
            }
        }
    }
    
    // Extraer la hora de inicio de la solicitud (ej: "22:00" de "03/12/2025 22:00 - 23:00")
    if (fechaHoraSolicitud) {
        const horaMatch = fechaHoraSolicitud.match(/\s(\d{2}):(\d{2})/);
        if (horaMatch) {
            horaInicioSolicitud = {
                horas: parseInt(horaMatch[1]),
                minutos: parseInt(horaMatch[2])
            };
        }
    } else if (solicitud && solicitud.HoraInicioAgenda) {
        const horaMatch = solicitud.HoraInicioAgenda.match(/(\d{2}):(\d{2})/);
        if (horaMatch) {
            horaInicioSolicitud = {
                horas: parseInt(horaMatch[1]),
                minutos: parseInt(horaMatch[2])
            };
        }
    }
    
    // Función helper para agregar minutos a una hora
    function agregarMinutos(horas, minutos, minutosAAgregar) {
        let totalMinutos = horas * 60 + minutos + minutosAAgregar;
        let nuevasHoras = Math.floor(totalMinutos / 60) % 24;
        let nuevosMinutos = totalMinutos % 60;
        return `${String(nuevasHoras).padStart(2, '0')}:${String(nuevosMinutos).padStart(2, '0')}`;
    }
    
    if (asignacion) {
        // Usar la fecha de la solicitud con minutos incrementales (5 minutos después de la hora de inicio)
        let fechaAsignacionMostrar = 'N/A';
        if (fechaSolicitudParaMostrar) {
            if (horaInicioSolicitud) {
                // Agregar 5 minutos a la hora de inicio
                const horaAsignacion = agregarMinutos(horaInicioSolicitud.horas, horaInicioSolicitud.minutos, 5);
                fechaAsignacionMostrar = `${fechaSolicitudParaMostrar} ${horaAsignacion}`;
            } else {
                fechaAsignacionMostrar = fechaSolicitudParaMostrar;
            }
        } else if (asignacion.FechaAsignacion) {
            fechaAsignacionMostrar = asignacion.FechaAsignacion;
        }
        
        html += `
            <div style="background: #e3f2fd; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #2196f3;">
                <h5 style="margin-bottom: 1rem; color: #333;">
                    <i class="fas fa-user-cog me-2"></i>
                    Mecánico Asignado
                </h5>
                <p><strong>Nombre:</strong> ${asignacion.MecanicoNombre || 'N/A'}</p>
                <p><strong>Estado:</strong> <span class="badge ${getEstadoAsignacionClass(asignacion.Estado)}">${asignacion.Estado || 'N/A'}</span></p>
                <p><strong>Fecha de Asignación:</strong> ${fechaAsignacionMostrar}</p>
                ${asignacion.Observaciones ? `<p><strong>Observaciones:</strong> ${asignacion.Observaciones}</p>` : ''}
            </div>
        `;
        
        if (avances.length > 0) {
            html += `
                <div>
                    <h5 style="margin-bottom: 1rem; color: #333;">
                        <i class="fas fa-history me-2"></i>
                        Historial de Avances
                    </h5>
                    <div style="max-height: 300px; overflow-y: auto;">
            `;
            
            avances.forEach((avance, index) => {
                // Usar la fecha de la solicitud con minutos incrementales para cada avance
                // El primer avance será 10 minutos después, el segundo 15, el tercero 20, etc.
                let fechaAvanceMostrar = 'N/A';
                if (fechaSolicitudParaMostrar) {
                    if (horaInicioSolicitud) {
                        // Calcular minutos incrementales: 10, 15, 20, 25, etc. (10 + index * 5)
                        const minutosIncrementales = 10 + (index * 5);
                        const horaAvance = agregarMinutos(horaInicioSolicitud.horas, horaInicioSolicitud.minutos, minutosIncrementales);
                        fechaAvanceMostrar = `${fechaSolicitudParaMostrar} ${horaAvance}`;
                    } else {
                        fechaAvanceMostrar = fechaSolicitudParaMostrar;
                    }
                } else if (avance.FechaAvance) {
                    // Fallback: usar la fecha del avance si no hay fecha de solicitud
                    fechaAvanceMostrar = avance.FechaAvance;
                }
                
                html += `
                    <div style="background: #fff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #28a745; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <strong style="color: #667eea;">Avance #${avances.length - index}</strong>
                            <span class="badge ${getEstadoAsignacionClass(avance.Estado)}">${avance.Estado || 'N/A'}</span>
                        </div>
                        <p style="color: #666; margin-bottom: 0.5rem;"><strong>Fecha:</strong> ${fechaAvanceMostrar}</p>
                        <p style="color: #333;">${avance.Descripcion || 'Sin descripción'}</p>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        } else {
            html += `
                <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <p style="margin: 0; color: #856404;">
                        <i class="fas fa-info-circle me-2"></i>
                        Aún no hay avances registrados para este vehículo.
                    </p>
                </div>
            `;
        }
    } else {
        html += `
            <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107;">
                <p style="margin: 0; color: #856404;">
                    <i class="fas fa-info-circle me-2"></i>
                    Este vehículo aún no tiene un mecánico asignado.
                </p>
            </div>
        `;
    }
    
    html += `</div>`;
    content.innerHTML = html;
}

function getEstadoAsignacionClass(estado) {
    const clases = {
        'Asignado': 'bg-info',
        'En Proceso': 'bg-primary',
        'En Revisión': 'bg-warning',
        'Completado': 'bg-success',
        'Cancelado': 'bg-danger'
    };
    return clases[estado] || 'bg-secondary';
}

// Event listeners para cerrar el modal de seguimiento y botones de ver seguimiento
document.addEventListener("DOMContentLoaded", () => {
    const seguimientoModal = document.getElementById('seguimiento-modal');
    const closeSeguimientoBtn = document.getElementById('close-seguimiento-modal');
    const cerrarSeguimientoBtn = document.getElementById('cerrar-seguimiento-modal');
    
    if (closeSeguimientoBtn) {
        closeSeguimientoBtn.addEventListener('click', () => {
            if (seguimientoModal) seguimientoModal.style.display = 'none';
        });
    }
    
    if (cerrarSeguimientoBtn) {
        cerrarSeguimientoBtn.addEventListener('click', () => {
            if (seguimientoModal) seguimientoModal.style.display = 'none';
        });
    }
    
    // Cerrar al hacer clic fuera del modal
    if (seguimientoModal) {
        window.addEventListener('click', (event) => {
            if (event.target === seguimientoModal) {
                seguimientoModal.style.display = 'none';
            }
        });
    }
    
});

// Event listener delegado para botones de ver seguimiento (fuera de DOMContentLoaded para que funcione con elementos dinámicos)
document.addEventListener('click', (event) => {
    if (event.target.closest('.btn-ver-seguimiento')) {
        const button = event.target.closest('.btn-ver-seguimiento');
        const vehiculoId = parseInt(button.getAttribute('data-vehiculo-id'));
        const placa = button.getAttribute('data-placa');
        const asignacionId = button.getAttribute('data-asignacion-id') || null;
        const fechaAgenda = button.getAttribute('data-fecha-agenda') || null;
        const solicitudId = button.getAttribute('data-solicitud-id') || null;
        const horaInicio = button.getAttribute('data-hora-inicio') || null;
        const horaFin = button.getAttribute('data-hora-fin') || null;
        
        if (vehiculoId && placa) {
            verSeguimiento(vehiculoId, placa, asignacionId, fechaAgenda, solicitudId, horaInicio, horaFin);
        }
    }
});

