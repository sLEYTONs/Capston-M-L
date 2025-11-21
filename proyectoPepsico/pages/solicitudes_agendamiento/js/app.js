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
    const form = document.getElementById("solicitud-agendamiento-form");
    const modal = document.getElementById("confirmation-modal");
    const confirmBtn = document.getElementById("confirm-registration");
    const cancelBtn = document.getElementById("cancel-registration");
    const notification = document.getElementById("notification");

    let formDataCache = null;
    const baseUrl = getBaseUrl();

    // Set default date for fecha solicitada (mínimo hoy)
    const fechaSolicitada = document.getElementById("fecha-solicitada");
    if (fechaSolicitada) {
        const hoy = new Date();
        fechaSolicitada.min = hoy.toISOString().split('T')[0];
    }

    // Limpieza del formulario
    const clearBtn = document.getElementById("clear-form");
    if (clearBtn) {
        clearBtn.addEventListener("click", () => {
            form.reset();
            if (fechaSolicitada) {
                const hoy = new Date();
                fechaSolicitada.min = hoy.toISOString().split('T')[0];
            }
        });
    }

    // Envío del formulario con modal
    if (form) {
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            if (validateForm()) {
                formDataCache = new FormData(form);
                modal.style.display = "block";
            }
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener("click", () => modal.style.display = "none");
    }

    if (confirmBtn) {
        confirmBtn.addEventListener("click", async () => {
            if (!formDataCache) return;
            modal.style.display = "none";

            try {
                // Mapear nombres de campos del formulario a los nombres esperados por el backend
                const fieldMapping = {
                    'plate': 'placa',
                    'vehicleType': 'tipo_vehiculo',
                    'brand': 'marca',
                    'model': 'modelo',
                    'color': 'color',
                    'year': 'anio',
                    'driverName': 'conductor_nombre',
                    'driverPhone': 'conductor_telefono',
                    'purpose': 'proposito',
                    'area': 'area',
                    'contactPerson': 'persona_contacto',
                    'observations': 'observaciones',
                    // Fecha y hora se asignarán cuando el supervisor apruebe
                    'fechaSolicitada': 'fecha_solicitada',
                    'horaSolicitada': 'hora_solicitada'
                };
                
                // Si no hay fecha/hora, usar valores por defecto (serán actualizados por el supervisor)
                if (!formDataObj['fecha_solicitada']) {
                    formDataObj['fecha_solicitada'] = new Date().toISOString().split('T')[0];
                }
                if (!formDataObj['hora_solicitada']) {
                    formDataObj['hora_solicitada'] = '08:00';
                }

                // Convertir FormData a objeto y mapear campos
                const formDataObj = {};
                formDataCache.forEach((value, key) => {
                    const mappedKey = fieldMapping[key] || key;
                    formDataObj[mappedKey] = value;
                });
                formDataObj['accion'] = 'crear_solicitud';

                // Convertir a FormData nuevamente
                const newFormData = new FormData();
                Object.keys(formDataObj).forEach(key => {
                    newFormData.append(key, formDataObj[key]);
                });

                const response = await fetch(baseUrl, {
                    method: "POST",
                    body: newFormData
                });

                // Verificar si la respuesta es JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Respuesta no JSON recibida:', text);
                    showNotification("Error: El servidor devolvió una respuesta no válida", "error");
                    return;
                }

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error HTTP:', response.status, errorText);
                    showNotification("Error del servidor: " + response.status, "error");
                    return;
                }

                const result = await response.json();

                if (result.status === "success") {
                    showNotification("Solicitud de agendamiento enviada correctamente. El supervisor revisará su solicitud y le notificará la respuesta.", "success");
                    form.reset();
                    if (fechaSolicitada) {
                        const hoy = new Date();
                        fechaSolicitada.min = hoy.toISOString().split('T')[0];
                    }
                    // Recargar tabla de solicitudes
                    cargarMisSolicitudes();
                } else {
                    showNotification("Error: " + result.message, "error");
                }
            } catch (error) {
                console.error("Error:", error);
                showNotification("Error de conexión con el servidor", "error");
            }
        });
    }

    function validateForm() {
        const requiredFields = ['plate', 'vehicle-type', 'brand', 'model', 'driver-name', 'driver-id', 'company', 'purpose'];
        let valid = true;
        requiredFields.forEach(id => {
            const field = document.getElementById(id);
            if (field && !field.value.trim()) {
                field.classList.add("error");
                valid = false;
            } else if (field) {
                field.classList.remove("error");
            }
        });

        if (!valid) showNotification("Por favor complete los campos obligatorios", "error");
        return valid;
    }

    function showNotification(msg, type) {
        if (!notification) return;
        notification.textContent = msg;
        notification.className = `notification show ${type}`;
        setTimeout(() => notification.classList.remove("show"), 5000);
    }

    // Cerrar modal con la X
    const closeBtn = document.querySelector(".close");
    if (closeBtn) {
        closeBtn.addEventListener("click", () => modal.style.display = "none");
    }

    // Cargar mis solicitudes al iniciar
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
        const fechaSolicitada = new Date(solicitud.FechaSolicitada).toLocaleDateString('es-ES');
        const horaSolicitada = solicitud.HoraSolicitada || 'N/A';
        const fechaActualizacion = solicitud.FechaActualizacion ? 
            new Date(solicitud.FechaActualizacion).toLocaleDateString('es-ES') : '-';

        row.innerHTML = `
            <td>${solicitud.ID}</td>
            <td>${solicitud.Placa}</td>
            <td>${solicitud.Marca} ${solicitud.Modelo}</td>
            <td>${fechaSolicitada} ${horaSolicitada}</td>
            <td><span class="badge ${estadoClass}">${solicitud.Estado}</span></td>
            <td>${fechaActualizacion}</td>
            <td>
                ${solicitud.MotivoRechazo ? `
                    <button class="btn btn-sm btn-info" onclick="verMotivoRechazo('${solicitud.MotivoRechazo.replace(/'/g, "\\'")}')">
                        <i class="fas fa-info-circle"></i> Ver motivo
                    </button>
                ` : '-'}
            </td>
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
    alert('Motivo de rechazo:\n\n' + motivo);
}

