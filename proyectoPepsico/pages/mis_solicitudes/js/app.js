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
        const estadoClass = getEstadoClass(solicitud.Estado);
        
        // Usar fecha y hora asignadas por el supervisor si existen, sino mostrar "Pendiente"
        let fechaHoraAsignada = 'Pendiente';
        if (solicitud.FechaAgenda && solicitud.HoraInicioAgenda) {
            const fecha = new Date(solicitud.FechaAgenda).toLocaleDateString('es-ES');
            const horaInicio = solicitud.HoraInicioAgenda.substring(0, 5); // Formato HH:MM
            const horaFin = solicitud.HoraFinAgenda ? solicitud.HoraFinAgenda.substring(0, 5) : '';
            fechaHoraAsignada = `${fecha} ${horaInicio}${horaFin ? ' - ' + horaFin : ''}`;
        }
        
        const fechaActualizacion = solicitud.FechaActualizacion ? 
            new Date(solicitud.FechaActualizacion).toLocaleDateString('es-ES') : '-';

        // Determinar qué botones mostrar en acciones
        let accionesHTML = '';
        
        if (solicitud.MotivoRechazo) {
            accionesHTML = `
                <button class="btn btn-sm btn-info" onclick="verMotivoRechazo('${solicitud.MotivoRechazo.replace(/'/g, "\\'")}')" title="Ver motivo de rechazo">
                    <i class="fas fa-info-circle"></i> Ver motivo
                </button>
            `;
        } else if (solicitud.MecanicoNombre && solicitud.VehiculoID) {
            // Si hay mecánico asignado, mostrar botón de seguimiento
            accionesHTML = `
                <button class="btn btn-sm btn-primary" onclick="verSeguimiento(${solicitud.VehiculoID}, '${solicitud.Placa}')" title="Ver seguimiento del vehículo">
                    <i class="fas fa-eye"></i> Ver
                </button>
            `;
        } else {
            accionesHTML = '-';
        }

        row.innerHTML = `
            <td>${solicitud.ID}</td>
            <td>${solicitud.Placa}</td>
            <td>${solicitud.Marca} ${solicitud.Modelo}</td>
            <td>${fechaHoraAsignada}</td>
            <td><span class="badge ${estadoClass}">${solicitud.Estado}</span></td>
            <td>${fechaActualizacion}</td>
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
        'Pendiente': 'bg-warning',
        'Aprobada': 'bg-success',
        'Rechazada': 'bg-danger',
        'Cancelada': 'bg-secondary'
    };
    return clases[estado] || 'bg-secondary';
}

function verMotivoRechazo(motivo) {
    const modal = document.getElementById('motivo-rechazo-modal');
    const texto = document.getElementById('motivo-rechazo-texto');
    if (modal && texto) {
        texto.textContent = motivo;
        modal.style.display = 'block';
    } else {
        alert('Motivo de rechazo:\n\n' + motivo);
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
function verSeguimiento(vehiculoId, placa) {
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
    
    fetch(getSeguimientoUrl(), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            mostrarSeguimiento(data.data, placa);
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
function mostrarSeguimiento(datos, placa) {
    const content = document.getElementById('seguimiento-content');
    const vehiculo = datos.vehiculo || {};
    const asignacion = datos.asignacion || null;
    const avances = datos.avances || [];
    
    let html = `
        <div style="padding: 1rem;">
            <h4 style="color: #667eea; margin-bottom: 1.5rem;">
                <i class="fas fa-car me-2"></i>
                Vehículo: ${placa}
            </h4>
            
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <h5 style="margin-bottom: 1rem; color: #333;">Información del Vehículo</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Marca:</strong> ${vehiculo.Marca || 'N/A'}</p>
                        <p><strong>Modelo:</strong> ${vehiculo.Modelo || 'N/A'}</p>
                        <p><strong>Tipo:</strong> ${vehiculo.TipoVehiculo || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Color:</strong> ${vehiculo.Color || 'N/A'}</p>
                        <p><strong>Año:</strong> ${vehiculo.Anio || 'N/A'}</p>
                        <p><strong>Conductor:</strong> ${vehiculo.ConductorNombre || 'N/A'}</p>
                    </div>
                </div>
            </div>
    `;
    
    if (asignacion) {
        html += `
            <div style="background: #e3f2fd; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #2196f3;">
                <h5 style="margin-bottom: 1rem; color: #333;">
                    <i class="fas fa-user-cog me-2"></i>
                    Mecánico Asignado
                </h5>
                <p><strong>Nombre:</strong> ${asignacion.MecanicoNombre || 'N/A'}</p>
                <p><strong>Estado:</strong> <span class="badge ${getEstadoAsignacionClass(asignacion.Estado)}">${asignacion.Estado || 'N/A'}</span></p>
                <p><strong>Fecha de Asignación:</strong> ${asignacion.FechaAsignacion || 'N/A'}</p>
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
                html += `
                    <div style="background: #fff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #28a745; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <strong style="color: #667eea;">Avance #${avances.length - index}</strong>
                            <span class="badge ${getEstadoAsignacionClass(avance.Estado)}">${avance.Estado || 'N/A'}</span>
                        </div>
                        <p style="color: #666; margin-bottom: 0.5rem;"><strong>Fecha:</strong> ${avance.FechaAvance || 'N/A'}</p>
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

// Event listeners para cerrar el modal de seguimiento
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

