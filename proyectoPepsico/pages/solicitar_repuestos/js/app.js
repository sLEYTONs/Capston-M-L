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
        fetch(this.baseUrl + '?action=obtenerRepuestos', {
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
            } else {
                this.mostrarError('Error: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error al enviar solicitud:', error);
            this.mostrarError('Error de conexión al enviar solicitud');
        });
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

