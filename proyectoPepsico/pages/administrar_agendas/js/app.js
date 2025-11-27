/**
 * Administración de Agendas del Taller
 */
class AdministrarAgendas {
    constructor() {
        this.agendaActual = null;
        this.agendaAEliminar = null;
        this.tabla = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        // Intentar detectar la ruta base del proyecto
        const path = window.location.pathname;
        const basePath = path.substring(0, path.indexOf('/pages/'));
        
        if (basePath) {
            const url = basePath + '/app/model/agendamiento/scripts/s_agendamiento.php';
            return url;
        }
        
        // Fallback: ruta relativa
        return '../../app/model/agendamiento/scripts/s_agendamiento.php';
    }

    inicializar() {
        this.inicializarDataTable();
        this.cargarAgendas();
        this.configurarEventos();
    }

    inicializarDataTable() {
        this.tabla = $('#tabla-agendas').DataTable({
            language: {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible en esta tabla",
                "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                "sSearch": "Buscar:",
                "sInfoThousands": ",",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                }
            },
            responsive: true,
            order: [[1, 'desc'], [2, 'asc']],
            pageLength: 25,
            columnDefs: [
                { orderable: false, targets: 8 }
            ]
        });
    }

    configurarEventos() {
        $('#btn-nueva-agenda').on('click', () => this.abrirModalNueva());
        $('#btn-guardar-agenda').on('click', () => this.guardarAgenda());
        $('#btn-aplicar-filtros').on('click', () => this.aplicarFiltros());
        $('#btn-limpiar-filtros').on('click', () => this.limpiarFiltros());
        $('#btn-confirmar-eliminar').on('click', () => this.eliminarAgenda());

        // Delegación de eventos para botones de acción
        $(document).on('click', '.btn-editar-agenda', (e) => {
            const agendaId = $(e.currentTarget).data('id');
            this.editarAgenda(agendaId);
        });

        $(document).on('click', '.btn-eliminar-agenda', (e) => {
            const agendaId = $(e.currentTarget).data('id');
            this.confirmarEliminar(agendaId);
        });
    }

    cargarAgendas(filtroFecha = null, filtroDisponible = null) {
        const formData = new FormData();
        formData.append('accion', 'obtener_todas_agendas');
        if (filtroFecha) formData.append('filtro_fecha', filtroFecha);
        if (filtroDisponible !== null) formData.append('filtro_disponible', filtroDisponible);

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarAgendas(data.data);
            } else {
                this.mostrarAlerta('Error al cargar agendas: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.mostrarAlerta('Error al cargar agendas', 'error');
        });
    }

    mostrarAgendas(agendas) {
        this.tabla.clear();

        agendas.forEach(agenda => {
            const fechaFormateada = this.formatearFecha(agenda.Fecha);
            const horaInicio = agenda.HoraInicio.substring(0, 5);
            const horaFin = agenda.HoraFin.substring(0, 5);
            const disponible = agenda.Disponible 
                ? '<span class="badge bg-success">Disponible</span>'
                : '<span class="badge bg-secondary">No Disponible</span>';
            
            const puedeEliminar = agenda.SolicitudesAprobadas === 0;
            const btnEliminar = puedeEliminar
                ? `<button class="btn btn-sm btn-danger btn-eliminar-agenda" data-id="${agenda.ID}" title="Eliminar">
                     <i class="fas fa-trash"></i>
                   </button>`
                : '<span class="text-muted" title="No se puede eliminar: tiene solicitudes aprobadas"><i class="fas fa-lock"></i></span>';

            this.tabla.row.add([
                agenda.ID,
                fechaFormateada,
                horaInicio,
                horaFin,
                disponible,
                agenda.SolicitudesAprobadas,
                agenda.SolicitudesPendientes,
                agenda.Observaciones || '-',
                `<div class="btn-group">
                    <button class="btn btn-sm btn-primary btn-editar-agenda" data-id="${agenda.ID}" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${btnEliminar}
                 </div>`
            ]);
        });

        this.tabla.draw();
    }

    abrirModalNueva() {
        this.agendaActual = null;
        $('#modalAgendaTitulo').html('<i class="fas fa-calendar-plus me-2"></i>Nueva Agenda');
        $('#form-agenda')[0].reset();
        $('#agenda-id').val('');
        $('#agenda-disponible').prop('checked', true);
        const modal = new bootstrap.Modal(document.getElementById('modalAgenda'));
        modal.show();
    }

    editarAgenda(agendaId) {
        const formData = new FormData();
        formData.append('accion', 'obtener_todas_agendas');

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const agenda = data.data.find(a => a.ID == agendaId);
                if (agenda) {
                    this.agendaActual = agenda;
                    $('#modalAgendaTitulo').html('<i class="fas fa-edit me-2"></i>Editar Agenda');
                    $('#agenda-id').val(agenda.ID);
                    $('#agenda-fecha').val(agenda.Fecha);
                    $('#agenda-hora-inicio').val(agenda.HoraInicio.substring(0, 5));
                    $('#agenda-hora-fin').val(agenda.HoraFin.substring(0, 5));
                    $('#agenda-disponible').prop('checked', agenda.Disponible);
                    $('#agenda-observaciones').val(agenda.Observaciones || '');
                    const modal = new bootstrap.Modal(document.getElementById('modalAgenda'));
                    modal.show();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.mostrarAlerta('Error al cargar la agenda', 'error');
        });
    }

    guardarAgenda() {
        const form = document.getElementById('form-agenda');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const datos = {
            accion: 'gestionar_agenda',
            id: $('#agenda-id').val() || '',
            fecha: $('#agenda-fecha').val(),
            hora_inicio: $('#agenda-hora-inicio').val(),
            hora_fin: $('#agenda-hora-fin').val(),
            disponible: $('#agenda-disponible').is(':checked') ? 1 : 0,
            observaciones: $('#agenda-observaciones').val()
        };

        const formData = new FormData();
        Object.keys(datos).forEach(key => {
            formData.append(key, datos[key]);
        });

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarAlerta(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalAgenda')).hide();
                this.cargarAgendas();
            } else {
                this.mostrarAlerta('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.mostrarAlerta('Error al guardar la agenda', 'error');
        });
    }

    confirmarEliminar(agendaId) {
        this.agendaAEliminar = agendaId;
        const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminar'));
        modal.show();
    }

    eliminarAgenda() {
        if (!this.agendaAEliminar) return;

        const formData = new FormData();
        formData.append('accion', 'eliminar_agenda');
        formData.append('agenda_id', this.agendaAEliminar);

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.mostrarAlerta(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEliminar')).hide();
                this.agendaAEliminar = null;
                this.cargarAgendas();
            } else {
                this.mostrarAlerta('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.mostrarAlerta('Error al eliminar la agenda', 'error');
        });
    }

    aplicarFiltros() {
        const filtroFecha = $('#filtro-fecha').val() || null;
        const filtroDisponible = $('#filtro-disponible').val() !== '' ? $('#filtro-disponible').val() : null;
        this.cargarAgendas(filtroFecha, filtroDisponible);
    }

    limpiarFiltros() {
        $('#filtro-fecha').val('');
        $('#filtro-disponible').val('');
        this.cargarAgendas();
    }

    formatearFecha(fecha) {
        if (!fecha) return '-';
        const date = new Date(fecha + 'T00:00:00');
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('es-ES', options);
    }

    mostrarAlerta(mensaje, tipo) {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[tipo] || 'alert-info';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        $('.pc-content').prepend(alertHtml);

        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    window.administrarAgendas = new AdministrarAgendas();
});

