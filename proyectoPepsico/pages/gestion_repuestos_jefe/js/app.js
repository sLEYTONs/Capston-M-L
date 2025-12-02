// Aplicación para Gestión de Repuestos con Jefe de Taller
class GestionRepuestosJefe {
    constructor() {
        this.dataTablePendientes = null;
        this.dataTableComunicaciones = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/gestion_repuestos_jefe/scripts/s_gestion_jefe.php';
        }
        return '../../app/model/gestion_repuestos_jefe/scripts/s_gestion_jefe.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.inicializarDataTables();
        this.cargarDatos();
    }

    inicializarEventos() {
        $('#form-solicitud-jefe').on('submit', (e) => {
            e.preventDefault();
            this.enviarSolicitud();
        });

        $('#btn-generar-reporte').on('click', () => {
            this.generarReporte();
        });
    }

    inicializarDataTables() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTablePendientes = $('#tabla-solicitudes-pendientes').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles en la tabla",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros coincidentes",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "aria": {
                    "sortAscending": ": activar para ordenar la columna de manera ascendente",
                    "sortDescending": ": activar para ordenar la columna de manera descendente"
                }
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            responsive: true
        });

        this.dataTableComunicaciones = $('#tabla-comunicaciones-jefe').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles en la tabla",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros coincidentes",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "aria": {
                    "sortAscending": ": activar para ordenar la columna de manera ascendente",
                    "sortDescending": ": activar para ordenar la columna de manera descendente"
                }
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            responsive: true
        });

    }

    cargarDatos() {
        this.cargarEstadisticas();
        this.cargarSolicitudesPendientes();
        this.cargarComunicaciones();
    }

    cargarEstadisticas() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_estadisticas' },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    $('#total-repuestos').text(response.data.total_repuestos || 0);
                    $('#stock-bajo').text(response.data.stock_bajo || 0);
                    $('#solicitudes-pendientes').text(response.data.solicitudes_pendientes || 0);
                    $('#entregas-mes').text(response.data.entregas_mes || 0);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar estadísticas:', error);
            }
        });
    }

    cargarSolicitudesPendientes() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_solicitudes_pendientes' },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.actualizarTablaPendientes(response.data);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar solicitudes pendientes:', error);
            }
        });
    }

    cargarComunicaciones() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_comunicaciones' },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.actualizarTablaComunicaciones(response.data);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar comunicaciones:', error);
            }
        });
    }

    actualizarTablaPendientes(datos) {
        if (this.dataTablePendientes) {
            this.dataTablePendientes.clear();
            this.dataTablePendientes.rows.add(datos.map(item => [
                new Date(item.FechaCreacion).toLocaleDateString('es-ES'),
                item.TipoSolicitud,
                item.Descripcion.substring(0, 50) + (item.Descripcion.length > 50 ? '...' : ''),
                this.getBadgePrioridad(item.Prioridad),
                `<button class="btn btn-sm btn-info" onclick="gestionJefe.verDetalle(${item.ID})">Ver</button>`
            ]));
            this.dataTablePendientes.draw();
        }
    }

    actualizarTablaComunicaciones(datos) {
        if (this.dataTableComunicaciones) {
            this.dataTableComunicaciones.clear();
            this.dataTableComunicaciones.rows.add(datos.map(item => [
                new Date(item.FechaCreacion).toLocaleDateString('es-ES'),
                item.TipoSolicitud,
                item.Asunto,
                this.getBadgePrioridad(item.Prioridad),
                this.getBadgeEstado(item.Estado),
                item.Respuesta ? (item.Respuesta.substring(0, 30) + '...') : '-',
                `<button class="btn btn-sm btn-info" onclick="gestionJefe.verDetalle(${item.ID})">Ver</button>`
            ]));
            this.dataTableComunicaciones.draw();
        }
    }

    getBadgePrioridad(prioridad) {
        const badges = {
            'urgente': '<span class="badge bg-danger">Urgente</span>',
            'alta': '<span class="badge bg-warning">Alta</span>',
            'media': '<span class="badge bg-info">Media</span>',
            'baja': '<span class="badge bg-secondary">Baja</span>'
        };
        return badges[prioridad] || prioridad;
    }

    getBadgeEstado(estado) {
        const badges = {
            'Pendiente': '<span class="badge bg-warning">Pendiente</span>',
            'Aprobada': '<span class="badge bg-success">Aprobada</span>',
            'Rechazada': '<span class="badge bg-danger">Rechazada</span>',
            'En Proceso': '<span class="badge bg-info">En Proceso</span>'
        };
        return badges[estado] || estado;
    }

    verDetalle(id) {
        // Implementar modal de detalles
        console.log('Ver detalle de solicitud:', id);
    }

    enviarSolicitud() {
        const tipoSolicitud = $('#tipo-solicitud').val();
        const prioridad = $('#prioridad-solicitud').val();
        const asunto = $('#asunto-solicitud').val().trim();
        const descripcion = $('#descripcion-solicitud').val().trim();
        const archivos = $('#archivos-solicitud')[0].files;

        // Validar campos obligatorios
        if (!tipoSolicitud || !prioridad || !asunto || !descripcion) {
            this.mostrarNotificacion('Por favor, complete todos los campos obligatorios', 'error');
            return;
        }

        // Preparar datos
        const formData = new FormData();
        formData.append('accion', 'crear_solicitud');
        formData.append('tipo_solicitud', tipoSolicitud);
        formData.append('prioridad', prioridad);
        formData.append('asunto', asunto);
        formData.append('descripcion', descripcion);

        // Agregar archivos si existen
        if (archivos.length > 0) {
            for (let i = 0; i < archivos.length; i++) {
                formData.append('archivos[]', archivos[i]);
            }
        }

        // Deshabilitar botón mientras se procesa
        const btnSubmit = $('#form-solicitud-jefe button[type="submit"]');
        const textoOriginal = btnSubmit.html();
        btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando...');

        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: (response) => {
                btnSubmit.prop('disabled', false).html(textoOriginal);
                
                if (response.status === 'success') {
                    this.mostrarNotificacion('Solicitud enviada correctamente', 'success');
                    $('#form-solicitud-jefe')[0].reset();
                    this.cargarDatos(); // Recargar datos
                } else {
                    this.mostrarNotificacion('Error: ' + (response.message || 'Error desconocido'), 'error');
                }
            },
            error: (xhr, status, error) => {
                btnSubmit.prop('disabled', false).html(textoOriginal);
                console.error('Error al enviar solicitud:', error);
                this.mostrarNotificacion('Error de conexión. Por favor, intente nuevamente.', 'error');
            }
        });
    }

    mostrarNotificacion(mensaje, tipo) {
        // Usar SweetAlert2 si está disponible, sino usar alert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: tipo === 'success' ? 'success' : 'error',
                title: tipo === 'success' ? 'Éxito' : 'Error',
                text: mensaje,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert(mensaje);
        }
    }

    generarReporte() {
        console.log('Generando reporte...');
    }
}

// Inicializar cuando el DOM esté listo
let gestionJefe;
document.addEventListener('DOMContentLoaded', () => {
    gestionJefe = new GestionRepuestosJefe();
    // Hacer disponible globalmente para los botones
    window.gestionJefe = gestionJefe;
});

