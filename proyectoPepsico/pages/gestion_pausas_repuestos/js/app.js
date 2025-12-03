class GestionPausasRepuestos {
    constructor() {
        this.tareasPausadas = [];
        this.baseUrl = this.getBaseUrl();
        this.dataTable = null;
        this.asignacionIdPendiente = null;
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
        this.inicializarEventos();
        this.cargarDatos();
        this.inicializarActualizacionAutomatica();
    }

    inicializarActualizacionAutomatica() {
        // Actualizar cada 30 segundos
        setInterval(() => {
            this.cargarDatos();
        }, 30000);

        // Escuchar eventos de cambio desde otras pestañas
        window.addEventListener('storage', (e) => {
            if (e.key === 'tareaPausada' || e.key === 'tareaReanudada') {
                this.cargarDatos();
            }
        });

        // Escuchar eventos personalizados
        window.addEventListener('tareaPausada', () => {
            this.cargarDatos();
        });

        window.addEventListener('tareaReanudada', () => {
            this.cargarDatos();
        });
    }

    inicializarDataTables() {
        if (!$.fn.DataTable) {
            return;
        }

        try {
            // Verificar si ya existe una instancia y destruirla de forma segura
            if ($.fn.DataTable.isDataTable('#tareas-pausadas-table')) {
                const table = $('#tareas-pausadas-table').DataTable();
                if (table) {
                    table.destroy();
                }
            }
        } catch (e) {
            console.warn('Error al destruir DataTable:', e);
        }

        // Inicializar DataTable solo si hay filas en el tbody (excluyendo el mensaje vacío)
        const tbody = document.querySelector('#tareas-pausadas-table tbody');
        if (!tbody) return;

        const filas = tbody.querySelectorAll('tr');
        const tieneDatos = filas.length > 0 && !filas[0].querySelector('td[colspan]');

        if (tieneDatos) {
            try {
                this.dataTable = $('#tareas-pausadas-table').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                    },
                    responsive: true,
                    order: [[5, 'desc']],
                    pageLength: 10,
                    columnDefs: [
                        { orderable: false, targets: 7 }
                    ]
                });
            } catch (e) {
                console.error('Error al inicializar DataTable:', e);
            }
        }
    }

    inicializarEventos() {
        const btnRefrescar = document.getElementById('btn-refrescar-tareas');
        if (btnRefrescar) {
            btnRefrescar.addEventListener('click', () => {
                this.cargarDatos();
            });
        }

        // Evento para confirmar reanudación
        const btnConfirmarReanudar = document.getElementById('btn-confirmar-reanudar');
        if (btnConfirmarReanudar) {
            btnConfirmarReanudar.addEventListener('click', () => {
                this.reanudarTarea();
            });
        }

        // Limpiar asignación pendiente cuando se cierra el modal
        const modalReanudar = document.getElementById('modalReanudarTarea');
        if (modalReanudar) {
            modalReanudar.addEventListener('hidden.bs.modal', () => {
                this.asignacionIdPendiente = null;
                const btnConfirmar = document.getElementById('btn-confirmar-reanudar');
                if (btnConfirmar) {
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = '<i class="fas fa-check me-2"></i>Confirmar Reanudación';
                }
            });
        }
    }

    cargarDatos() {
        this.cargarTareasEnPausa();
    }

    cargarTareasEnPausa() {
        fetch(this.baseUrl + '?action=obtenerTareasEnPausa', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                this.tareasPausadas = data.data;
                this.mostrarTareasEnPausa(data.data);
                this.actualizarResumen(data.data);
            } else {
                this.mostrarError('Error al cargar tareas en pausa: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error al cargar tareas:', error);
            this.mostrarError('Error de conexión al cargar tareas en pausa');
        });
    }

    mostrarTareasEnPausa(tareas) {
        const tbody = document.querySelector('#tareas-pausadas-table tbody');
        if (!tbody) {
            console.error('No se encontró el tbody de la tabla');
            return;
        }

        // Destruir DataTable de forma segura si existe
        try {
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#tareas-pausadas-table')) {
                const table = $('#tareas-pausadas-table').DataTable();
                if (table) {
                    table.destroy();
                }
            }
            this.dataTable = null;
        } catch (e) {
            console.warn('Error al destruir DataTable:', e);
            this.dataTable = null;
        }

        // Limpiar el tbody
        tbody.innerHTML = '';

        if (tareas.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">No hay tareas en pausa</p>
                    </td>
                </tr>
            `;
        } else {
            tareas.forEach(tarea => {
                const row = document.createElement('tr');
                const vehiculoCompleto = `${tarea.Marca || ''} ${tarea.Modelo || ''} ${tarea.TipoVehiculo || ''}`.trim() || 'N/A';
                const solicitudesBadge = tarea.SolicitudesPendientes > 0 
                    ? `<span class="badge bg-warning">${tarea.SolicitudesPendientes} pendiente(s)</span>`
                    : '<span class="badge bg-secondary">Sin solicitudes</span>';
                
                // Obtener nombre del conductor (puede venir de ingreso_vehiculos o solicitudes_agendamiento)
                const conductorNombre = (tarea.ConductorNombre && tarea.ConductorNombre.trim() !== '' && tarea.ConductorNombre !== 'null') 
                    ? tarea.ConductorNombre 
                    : 'N/A';

                row.innerHTML = `
                    <td><strong>#${tarea.AsignacionID}</strong></td>
                    <td>${vehiculoCompleto}</td>
                    <td><strong>${tarea.Placa}</strong></td>
                    <td>${conductorNombre}</td>
                    <td>${tarea.MotivoPausa || 'No especificado'}</td>
                    <td>${tarea.FechaAsignacion}</td>
                    <td>${solicitudesBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="gestionPausas.mostrarModalReanudar(${tarea.AsignacionID}, '${tarea.Placa}', '${vehiculoCompleto}')" title="Reanudar tarea">
                            <i class="fas fa-play"></i> Reanudar
                        </button>
                        <a href="solicitar_repuestos.php?asignacion_id=${tarea.AsignacionID}" class="btn btn-sm btn-primary" title="Solicitar repuestos">
                            <i class="fas fa-tools"></i> Solicitar Repuestos
                        </a>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Reinicializar DataTable después de agregar las filas (solo si hay datos)
        if (tareas.length > 0) {
            // Usar setTimeout para asegurar que el DOM se haya actualizado
            setTimeout(() => {
                this.inicializarDataTables();
            }, 100);
        }
    }


    mostrarModalReanudar(asignacionId, placa, vehiculoInfo) {
        // Guardar el ID de asignación para usar en la confirmación
        this.asignacionIdPendiente = asignacionId;
        
        // Escapar HTML para prevenir XSS
        const escapeHtml = (text) => {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        };
        
        // Llenar información en el modal
        const asignacionIdEl = document.getElementById('reanudar-asignacion-id');
        const placaEl = document.getElementById('reanudar-placa');
        const vehiculoEl = document.getElementById('reanudar-vehiculo-info');
        
        if (asignacionIdEl) asignacionIdEl.textContent = '#' + asignacionId;
        if (placaEl) placaEl.textContent = escapeHtml(placa || 'N/A');
        if (vehiculoEl) vehiculoEl.textContent = escapeHtml(vehiculoInfo || 'N/A');
        
        // Mostrar modal usando Bootstrap
        const modalElement = document.getElementById('modalReanudarTarea');
        if (modalElement && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            // Fallback si Bootstrap no está disponible
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
        }
    }

    reanudarTarea(asignacionId) {
        if (!asignacionId) {
            asignacionId = this.asignacionIdPendiente;
        }
        
        if (!asignacionId) {
            this.mostrarError('Error: No se pudo identificar la tarea a reanudar');
            return;
        }

        // Deshabilitar botón mientras se procesa
        const btnConfirmar = document.getElementById('btn-confirmar-reanudar');
        if (btnConfirmar) {
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Reanudando...';
        }

        const formData = new FormData();
        formData.append('action', 'reanudarTarea');
        formData.append('asignacion_id', asignacionId);

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Cerrar modal
                const modalElement = document.getElementById('modalReanudarTarea');
                if (modalElement && typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                }
                
                this.mostrarExito('Tarea reanudada correctamente');
                this.cargarDatos();
                
                // Notificar a otras pestañas/páginas que hay un cambio
                if (typeof Storage !== 'undefined') {
                    localStorage.setItem('tareaReanudada', Date.now().toString());
                    localStorage.removeItem('tareaReanudada'); // Limpiar inmediatamente
                }
                
                // Disparar evento personalizado
                window.dispatchEvent(new CustomEvent('tareaReanudada', { 
                    detail: { asignacionId: asignacionId } 
                }));
            } else {
                this.mostrarError('Error: ' + (data.message || 'Error desconocido'));
                if (btnConfirmar) {
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = '<i class="fas fa-check me-2"></i>Confirmar Reanudación';
                }
            }
        })
        .catch(error => {
            console.error('Error al reanudar tarea:', error);
            this.mostrarError('Error de conexión al reanudar tarea');
            if (btnConfirmar) {
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = '<i class="fas fa-check me-2"></i>Confirmar Reanudación';
            }
        });
    }

    verSolicitudes(asignacionId) {
        // Redirigir a la página de estado de solicitudes
        window.location.href = 'estado_solicitudes_repuestos.php';
    }

    actualizarResumen(tareas) {
        const totalPausadas = tareas.length;
        const solicitudesPendientes = tareas.reduce((sum, t) => sum + (t.SolicitudesPendientes || 0), 0);

        document.getElementById('total-pausadas').textContent = totalPausadas;
        if (document.getElementById('solicitudes-pendientes')) {
            document.getElementById('solicitudes-pendientes').textContent = solicitudesPendientes;
        }
    }

    getEstadoClass(estado) {
        const clases = {
            'Pendiente': 'bg-warning',
            'Aprobada': 'bg-info',
            'Rechazada': 'bg-danger',
            'Entregada': 'bg-success',
            'Cancelada': 'bg-secondary'
        };
        return clases[estado] || 'bg-secondary';
    }

    getUrgenciaClass(urgencia) {
        const clases = {
            'Baja': 'bg-success',
            'Media': 'bg-warning',
            'Alta': 'bg-danger'
        };
        return clases[urgencia] || 'bg-secondary';
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
let gestionPausas;
document.addEventListener('DOMContentLoaded', () => {
    gestionPausas = new GestionPausasRepuestos();
});

