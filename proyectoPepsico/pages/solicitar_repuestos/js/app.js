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
        this.cargarRepuestos();
        this.inicializarEventos();
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

        // Obtener asignacion_id de la URL si existe (para mecánicos)
        const urlParams = new URLSearchParams(window.location.search);
        const asignacionId = urlParams.get('asignacion_id');
        
        // Si hay asignacion_id en la URL, agregarlo al formData
        // Si no hay (como para Asistente de Repuestos), no agregar nada (será null)
        if (asignacionId && asignacionId !== '0' && asignacionId !== '') {
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

