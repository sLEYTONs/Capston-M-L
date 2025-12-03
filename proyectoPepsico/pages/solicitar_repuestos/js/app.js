class SolicitarRepuestos {
    constructor() {
        this.baseUrl = this.getBaseUrl();
        this.repuestos = [];
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
        this.cargarVehiculos();
        this.cargarRepuestos();
        this.inicializarEventos();
    }

    cargarVehiculos() {
        // Solo cargar vehículos si el usuario es mecánico
        const urlParams = new URLSearchParams(window.location.search);
        const asignacionId = urlParams.get('asignacion_id');
        
        // Si ya hay un asignacion_id en la URL, seleccionarlo automáticamente
        if (asignacionId && asignacionId !== '0' && asignacionId !== '') {
            // Cargar vehículos para mostrar opciones, pero preseleccionar el de la URL
            this.cargarVehiculosAsignados(asignacionId);
        } else {
            // Cargar todos los vehículos asignados al mecánico
            this.cargarVehiculosAsignados();
        }
    }

    cargarVehiculosAsignados(preseleccionarAsignacionId = null) {
        fetch(this.baseUrl + '?action=obtenerVehiculosAsignados', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                this.actualizarSelectVehiculos(data.data, preseleccionarAsignacionId);
            }
        })
        .catch(error => {
            console.error('Error al cargar vehículos:', error);
        });
    }

    actualizarSelectVehiculos(vehiculos, preseleccionarAsignacionId = null) {
        const select = document.getElementById('vehiculo-select');
        if (!select) return;

        // Mantener la opción "Seleccionar vehículo (opcional)..."
        select.innerHTML = '<option value="">Seleccionar vehículo (opcional)...</option>';
        
        vehiculos.forEach(vehiculo => {
            const option = document.createElement('option');
            option.value = vehiculo.AsignacionID;
            const vehiculoTexto = `${vehiculo.Placa} - ${vehiculo.Marca} ${vehiculo.Modelo} (${vehiculo.TipoVehiculo})`;
            option.textContent = vehiculoTexto;
            
            // Preseleccionar si coincide con el asignacion_id de la URL
            if (preseleccionarAsignacionId && parseInt(vehiculo.AsignacionID) === parseInt(preseleccionarAsignacionId)) {
                option.selected = true;
            }
            
            select.appendChild(option);
        });
    }

    inicializarEventos() {
        const formSolicitud = document.getElementById('form-solicitud-repuestos');
        if (formSolicitud) {
            formSolicitud.addEventListener('submit', (e) => {
                e.preventDefault();
                this.enviarSolicitud();
            });
        }
    }

    cargarRepuestos() {
        // Obtener asignacion_id de la URL si existe (para mecánicos)
        const urlParams = new URLSearchParams(window.location.search);
        const asignacionId = urlParams.get('asignacion_id');
        
        // Construir URL con parámetros
        let url = this.baseUrl + '?action=obtenerRepuestos';
        if (asignacionId && asignacionId !== '0' && asignacionId !== '') {
            url += '&asignacion_id=' + encodeURIComponent(asignacionId);
        }
        
        fetch(url, {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                this.repuestos = data.data;
                this.actualizarSelectRepuestos(data.data);
            }
        })
        .catch(error => {
            console.error('Error al cargar repuestos:', error);
        });
    }

    actualizarSelectRepuestos(repuestos) {
        const select = document.getElementById('repuesto-select');
        if (!select) return;

        select.innerHTML = '<option value="">Seleccione un repuesto</option>';
        repuestos.forEach(repuesto => {
            const option = document.createElement('option');
            option.value = repuesto.ID;
            option.textContent = `${repuesto.Nombre} (${repuesto.Codigo}) - Stock: ${repuesto.Stock}`;
            select.appendChild(option);
        });
    }

    enviarSolicitud() {
        const form = document.getElementById('form-solicitud-repuestos');
        const formData = new FormData(form);

        // Obtener asignacion_id del selector de vehículo (prioridad) o de la URL
        const asignacionIdSelect = document.getElementById('vehiculo-select')?.value;
        const urlParams = new URLSearchParams(window.location.search);
        const asignacionIdUrl = urlParams.get('asignacion_id');
        
        // Prioridad: selector > URL
        const asignacionId = asignacionIdSelect && asignacionIdSelect !== '' 
            ? asignacionIdSelect 
            : (asignacionIdUrl && asignacionIdUrl !== '0' && asignacionIdUrl !== '' ? asignacionIdUrl : null);
        
        // Si hay asignacion_id, agregarlo al formData
        if (asignacionId) {
            formData.append('asignacion_id', asignacionId);
        }
        // Si no hay asignacion_id, no agregarlo al formData (el backend lo manejará como null)

        formData.append('action', 'crearSolicitudRepuestos');

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarExito('Solicitud de repuestos enviada correctamente');
                form.reset();
            } else if (data.status === 'duplicado') {
                this.mostrarSolicitudDuplicada(data);
            } else {
                this.mostrarError('Error: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error al enviar solicitud:', error);
            this.mostrarError('Error de conexión al enviar solicitud');
        });
    }

    mostrarSolicitudDuplicada(data) {
        const solicitud = data.solicitud_existente;
        const estadoTexto = solicitud.estado === 'Pendiente' ? 'pendiente de aprobación' : 'aprobada y esperando entrega';
        const fechaFormateada = solicitud.fecha;
        
        let mensaje = `Ya existe una solicitud ${estadoTexto} para el repuesto:\n\n`;
        mensaje += `• Repuesto: ${solicitud.repuesto_nombre}`;
        if (solicitud.repuesto_codigo) {
            mensaje += ` (${solicitud.repuesto_codigo})`;
        }
        mensaje += `\n• Cantidad: ${solicitud.cantidad} unidad(es)`;
        mensaje += `\n• Urgencia: ${solicitud.urgencia}`;
        mensaje += `\n• Fecha de solicitud: ${fechaFormateada}`;
        mensaje += `\n• Estado: ${solicitud.estado === 'Pendiente' ? 'Pendiente de aprobación' : 'Aprobada - Esperando entrega'}`;
        
        // Mostrar información de asignación si está disponible
        if (solicitud.tiene_asignacion && solicitud.placa) {
            mensaje += `\n• Vehículo asignado: ${solicitud.placa}`;
        } else {
            mensaje += `\n• Vehículo asignado: No asignado`;
        }
        
        mensaje += `\n\nPor favor, espere la respuesta de esta solicitud antes de crear una nueva.`;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Solicitud ya existente',
                html: mensaje.replace(/\n/g, '<br>'),
                confirmButtonText: 'Entendido',
                width: '600px'
            });
        } else {
            alert(mensaje);
        }
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
}

// Inicializar cuando el DOM esté listo
let solicitarRepuestos;
document.addEventListener('DOMContentLoaded', () => {
    solicitarRepuestos = new SolicitarRepuestos();
});

