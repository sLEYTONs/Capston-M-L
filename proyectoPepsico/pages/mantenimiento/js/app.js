class TareasMecanico {
    constructor() {
        this.dataTable = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        if (!$('#tareas-table').length) {
            console.error('No se encontró la tabla tareas-table');
            return;
        }
        
        this.bindEvents();
        this.inicializarDataTable();
        this.cargarTareas();
    }

    bindEvents() {
        $('#btn-refresh').on('click', () => {
            this.cargarTareas();
        });

        $(document).on('click', '.btn-registrar-avance', (e) => {
            const asignacionId = $(e.currentTarget).data('id');
            this.mostrarModalAvance(asignacionId);
        });

        $('#guardar-avance').on('click', () => {
            this.guardarAvance();
        });
    }

    inicializarDataTable() {
        try {
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables no está cargado');
                return;
            }

            this.dataTable = $('#tareas-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                dom: '<"table-header"lf>rt<"table-footer"ip>',
                columns: [
                    { 
                        data: null,
                        render: (data) => {
                            return `
                                <div class="vehicle-info">
                                    <strong>${data.Marca} ${data.Modelo}</strong>
                                    <div class="vehicle-details">
                                        <small class="text-muted">${data.TipoVehiculo} • ${data.Color}</small>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    { 
                        data: 'Placa',
                        render: (data) => {
                            return `<span class="placa-badge">${data}</span>`;
                        }
                    },
                    { 
                        data: 'FechaAsignacion',
                        className: 'fecha-column'
                    },
                    { 
                        data: 'Estado',
                        render: (data) => {
                            const estadoClass = 'status-' + data.replace(' ', '');
                            return `<span class="${estadoClass}">${data}</span>`;
                        }
                    },
                    { 
                        data: 'Observaciones',
                        render: (data) => {
                            return data || '<span class="text-muted">Sin observaciones</span>';
                        }
                    },
                    { 
                        data: null,
                        orderable: false,
                        render: (data) => {
                            let botones = '';
                            
                            if (data.Estado !== 'Completado') {
                                botones = `
                                    <button class="btn btn-primary btn-sm btn-registrar-avance" 
                                            data-id="${data.AsignacionID}" 
                                            title="Registrar Avance">
                                        <i class="fas fa-clipboard-check me-1"></i>Avance
                                    </button>
                                `;
                            } else {
                                botones = `
                                    <span class="badge bg-success">Completado</span>
                                `;
                            }
                            
                            return `<div class="btn-group">${botones}</div>`;
                        }
                    }
                ],
                order: [[2, 'desc']],
                responsive: true,
                initComplete: () => {
                    $('.dataTables_length select').addClass('form-select form-select-sm');
                    $('.dataTables_filter input').addClass('form-control form-control-sm');
                }
            });
        } catch (error) {
            console.error('Error al inicializar DataTables:', error);
        }
    }

    cargarTareas() {
        if (this.isLoading) return;
        
        this.mostrarLoading();

        $.ajax({
            url: '../app/model/tareas/scripts/s_tareas.php',
            type: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.cargarDataEnTabla(response.data);
                } else {
                    this.mostrarError(response.message);
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
            },
            complete: () => {
                this.ocultarLoading();
            }
        });
    }

    cargarDataEnTabla(data) {
        if (this.dataTable) {
            this.dataTable.clear();
            this.dataTable.rows.add(data).draw();
        }
    }

    mostrarModalAvance(asignacionId) {
        // Cargar datos de la asignación
        $.ajax({
            url: '../app/model/tareas/scripts/s_detalles_asignacion.php',
            type: 'POST',
            data: { asignacion_id: asignacionId },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    $('#avance-asignacion-id').val(asignacionId);
                    $('#modal-placa-avance').text(response.data.Placa);
                    $('#avance-descripcion').val('');
                    $('#avance-estado').val('En progreso');
                    
                    const avanceModal = new bootstrap.Modal(document.getElementById('avanceModal'));
                    avanceModal.show();
                } else {
                    this.mostrarError('Error al cargar datos: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
            }
        });
    }

    guardarAvance() {
        const asignacionId = $('#avance-asignacion-id').val();
        const descripcion = $('#avance-descripcion').val().trim();
        const estado = $('#avance-estado').val();

        if (!descripcion) {
            this.mostrarError('La descripción del avance es requerida');
            return;
        }

        $('#guardar-avance').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

        $.ajax({
            url: '../app/model/tareas/scripts/s_guardar_avance.php',
            type: 'POST',
            data: {
                asignacion_id: asignacionId,
                descripcion: descripcion,
                estado: estado
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarToast('Éxito', 'Avance registrado correctamente', 'success');
                    this.cerrarModalAvance();
                    this.cargarTareas(); // Recargar la tabla
                } else {
                    this.mostrarError('Error al guardar: ' + response.message);
                    $('#guardar-avance').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Avance');
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
                $('#guardar-avance').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Avance');
            }
        });
    }

    cerrarModalAvance() {
        const avanceModal = bootstrap.Modal.getInstance(document.getElementById('avanceModal'));
        avanceModal.hide();
        
        // Limpiar formulario
        $('#avance-form')[0].reset();
        $('#guardar-avance').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Guardar Avance');
    }

    mostrarLoading() {
        this.isLoading = true;
        $('#btn-refresh').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Actualizando...');
    }

    ocultarLoading() {
        this.isLoading = false;
        $('#btn-refresh').prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i>Actualizar');
    }

    mostrarError(mensaje) {
        this.mostrarToast('Error', mensaje, 'danger');
    }

    mostrarToast(titulo, mensaje, tipo = 'info') {
        const toastHtml = `
            <div class="toast align-items-center text-bg-${tipo} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${titulo}:</strong> ${mensaje}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        const $container = $('#toast-container');
        if (!$container.length) {
            $('body').append('<div id="toast-container"></div>');
        }
        
        $('#toast-container').html(toastHtml);
        $('.toast').toast('show');
    }
}

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    window.tareas = new TareasMecanico();
});