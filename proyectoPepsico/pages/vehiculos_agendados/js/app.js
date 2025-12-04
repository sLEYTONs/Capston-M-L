class VehiculosAgendados {
    constructor() {
        this.table = null;
        this.historialTable = null;
        this.fechaActual = new Date().toISOString().split('T')[0];
        this.baseUrl = this.getBaseUrl();
        this.vehiculosData = []; // Almacenar datos de vehículos para el modal
        this.historialData = []; // Almacenar datos del historial para el modal
        this.inicializar();
    }

    // Función helper para parsear fechas sin problemas de zona horaria
    parsearFecha(fechaString) {
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

    formatearFechaAgenda(fechaString, opciones = {}) {
        if (!fechaString) return 'N/A';
        const fecha = this.parsearFecha(fechaString);
        if (!fecha || isNaN(fecha.getTime())) return 'N/A';
        const opcionesDefault = {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        return fecha.toLocaleDateString('es-ES', { ...opcionesDefault, ...opciones });
    }

    getBaseUrl() {
        // Obtener la ruta actual y construir la ruta al script
        const currentPath = window.location.pathname;
        
        // Si estamos en pages/vehiculos_agendados.php, construir la ruta relativa
        if (currentPath.includes('/pages/')) {
            // Obtener la parte antes de /pages/
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/control_ingreso/scripts/s_control_ingreso.php';
        }
        
        // Fallback: ruta relativa desde pages/vehiculos_agendados/js/
        // Subimos 2 niveles: ../ -> vehiculos_agendados/, ../../ -> pages/, luego app/
        return '../../app/model/control_ingreso/scripts/s_control_ingreso.php';
    }

    inicializar() {
        // Solo inicializar eventos del historial
        this.inicializarEventos();
        
        // Cargar el historial
        this.cargarHistorial();
    }

    inicializarEventos() {
        const esGuardia = window.esGuardia === true;
        
        // Eventos para el historial
        const fechaFiltroHistorial = document.getElementById('fecha-filtro-historial');
        const btnRefrescarHistorial = document.getElementById('btn-refrescar-historial');
        
        if (fechaFiltroHistorial && !esGuardia) {
            fechaFiltroHistorial.addEventListener('change', () => {
                this.cargarHistorial();
            });
        }

        if (btnRefrescarHistorial) {
            btnRefrescarHistorial.addEventListener('click', () => {
                this.cargarHistorial();
            });
        }
    }

    inicializarDataTable() {
        // Ya no se inicializa la primera tabla, solo se mantiene el método por compatibilidad
        // La tabla de historial se inicializa en mostrarHistorial()
    }

    cargarVehiculos() {
        // Este método ya no se usa, se mantiene por compatibilidad
        // La funcionalidad se movió completamente al historial
    }

    cargarHistorial() {
        // Cargar historial completo
        const esGuardia = window.esGuardia === true;
        const fechaFiltroHistorial = document.getElementById('fecha-filtro-historial');
        const fecha = (esGuardia || !fechaFiltroHistorial) ? null : fechaFiltroHistorial.value;

        const formData = new FormData();
        formData.append('action', 'obtenerHistorialVehiculosAgendados');
        // Solo enviar fecha si no es Guardia
        if (!esGuardia && fecha) {
            formData.append('fecha', fecha);
        }

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta historial:', data);
            if (data.success && data.data) {
                console.log('Datos historial recibidos:', data.data.length, 'registros');
                this.historialData = data.data; // Guardar datos para el modal
                this.mostrarHistorial(data.data);
            } else {
                console.error('Error en respuesta historial:', data);
                this.mostrarErrorHistorial(data.message || 'Error al cargar historial');
            }
        })
        .catch(error => {
            console.error('Error al cargar historial:', error);
            this.mostrarErrorHistorial('Error de conexión al cargar historial');
        });
    }

    mostrarVehiculos(vehiculos) {
        const tbody = document.querySelector('#vehiculos-agendados-table tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (vehiculos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay vehículos pendientes de ingreso</p>
                    </td>
                </tr>
            `;
            return;
        }

        vehiculos.forEach(vehiculo => {
            const row = document.createElement('tr');
            
            // Formatear fecha usando el método helper
            const fechaFormateada = this.formatearFechaAgenda(vehiculo.FechaAgenda);

            // Formatear hora
            const horaInicio = vehiculo.HoraInicio ? vehiculo.HoraInicio.substring(0, 5) : 'N/A';
            const horaFin = vehiculo.HoraFin ? vehiculo.HoraFin.substring(0, 5) : '';
            const horaFormateada = horaFin ? `${horaInicio} - ${horaFin}` : horaInicio;

            // Estado badge - verificar tanto EstadoIngreso como EstadoVehiculo
            // Si no hay estado en la base de datos, significa que ya no tiene hora asignada (completado y salió)
            const estado = vehiculo.EstadoIngreso || vehiculo.EstadoVehiculo;
            let estadoClass = 'warning';
            let estadoIcon = 'fa-clock';
            let estadoTexto = 'Pendiente';
            
            // Si no hay estado, significa que el vehículo ya salió (no tiene registro en ingreso_vehiculos)
            if (!estado || estado === null || estado === '') {
                estadoClass = 'secondary';
                estadoIcon = 'fa-check-circle';
                estadoTexto = 'Sin Hora Asignada';
            } else if (estado === 'Completado' || estado === 'Finalizado') {
                estadoClass = 'success';
                estadoIcon = 'fa-check-double';
                estadoTexto = 'Completado - Listo para Salir';
            } else if (estado === 'Ingresado' || estado === 'Asignado') {
                estadoClass = 'info';
                estadoIcon = 'fa-check-circle';
                estadoTexto = 'En Taller';
            } else if (estado === 'Pendiente Ingreso') {
                estadoClass = 'warning';
                estadoIcon = 'fa-clock';
                estadoTexto = 'Pendiente';
            }

            // Vehículo completo
            const vehiculoCompleto = `${vehiculo.Marca || ''} ${vehiculo.Modelo || ''} ${vehiculo.TipoVehiculo || ''}`.trim() || 'N/A';

            row.innerHTML = `
                <td><strong>${vehiculo.Placa}</strong></td>
                <td>${vehiculoCompleto}</td>
                <td>${vehiculo.ConductorNombre || 'N/A'}</td>
                <td>${fechaFormateada}</td>
                <td><i class="fas fa-clock me-1"></i>${horaFormateada}</td>
                <td>${vehiculo.Proposito || 'N/A'}</td>
                <td>
                    <span class="badge bg-${estadoClass}">
                        <i class="fas ${estadoIcon} me-1"></i>${estadoTexto}
                    </span>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-info" onclick="vehiculosAgendados.verDetalles(${vehiculo.SolicitudID})" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${(estado === 'Completado' || estado === 'Finalizado' || estadoTexto === 'Completado - Listo para Salir') && window.esGuardia ? `
                        <button class="btn btn-sm btn-success" onclick="vehiculosAgendados.marcarCompletado(${vehiculo.SolicitudID}, '${vehiculo.Placa}')" title="Marcar como completado y ocultar de la lista">
                            <i class="fas fa-check-double"></i>
                        </button>
                        ` : ''}
                    </div>
                </td>
            `;

            tbody.appendChild(row);
        });

        // Redibujar DataTable si existe
        if (this.table) {
            this.table.clear();
            this.table.rows.add(Array.from(tbody.querySelectorAll('tr')));
            this.table.draw();
        }
    }


    mostrarHistorial(vehiculos) {
        const tbody = document.querySelector('#historial-vehiculos-table tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (vehiculos.length === 0) {
            const esGuardia = window.esGuardia === true;
            const mensaje = esGuardia 
                ? 'No hay vehículos en el historial' 
                : 'No hay vehículos en el historial para la fecha seleccionada';
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted">${mensaje}</p>
                    </td>
                </tr>
            `;
            // Si la tabla ya existe, destruirla antes de mostrar el mensaje vacío
            if (this.historialTable) {
                this.historialTable.destroy();
                this.historialTable = null;
            }
            
            // Inicializar DataTable incluso cuando está vacía para que los controles funcionen
            setTimeout(() => {
                this.historialTable = $('#historial-vehiculos-table').DataTable({
                    language: {
                        "sProcessing": "Procesando...",
                        "sLengthMenu": "Mostrar _MENU_ registros",
                        "sZeroRecords": "No se encontraron resultados",
                        "sEmptyTable": "Ningún dato disponible en esta tabla",
                        "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                        "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                        "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
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
                    responsive: true,
                    pageLength: 25,
                    searching: true,
                    ordering: true,
                    info: true,
                    paging: true,
                    lengthChange: true,
                    columnDefs: [
                        { 
                            type: 'date', 
                            targets: 3,
                            orderable: true,
                            searchable: true
                        },
                        { 
                            targets: [0, 1, 2, 4, 5, 6],
                            orderable: true,
                            searchable: true
                        },
                        { 
                            orderable: false, 
                            searchable: false,
                            targets: 7
                        }
                    ]
                });
            }, 100);
            return;
        }

        vehiculos.forEach(vehiculo => {
            const row = document.createElement('tr');
            
            // Formatear fecha - usar FechaAgenda si existe, sino FechaIngreso
            const fechaParaMostrar = vehiculo.FechaAgenda || vehiculo.FechaIngreso;
            const fechaFormateada = fechaParaMostrar ? this.formatearFechaAgenda(fechaParaMostrar) : 'N/A';
            
            // Crear fecha ISO para ordenamiento (YYYY-MM-DD)
            let fechaParaOrdenar = '';
            if (fechaParaMostrar) {
                const fechaObj = this.parsearFecha(fechaParaMostrar);
                if (fechaObj && !isNaN(fechaObj.getTime())) {
                    fechaParaOrdenar = fechaObj.toISOString().split('T')[0]; // YYYY-MM-DD
                }
            }

            // Formatear hora - usar HoraInicio/HoraFin de agenda si existe
            let horaFormateada = 'N/A';
            let horaParaOrdenar = '';
            if (vehiculo.HoraInicio) {
                const horaInicio = vehiculo.HoraInicio.substring(0, 5);
                const horaFin = vehiculo.HoraFin ? vehiculo.HoraFin.substring(0, 5) : '';
                horaFormateada = horaFin ? `${horaInicio} - ${horaFin}` : horaInicio;
                horaParaOrdenar = vehiculo.HoraInicio; // Para ordenamiento
            } else if (vehiculo.FechaIngreso) {
                // Si no hay hora de agenda, usar hora de ingreso
                const fechaIngreso = new Date(vehiculo.FechaIngreso);
                horaFormateada = fechaIngreso.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                horaParaOrdenar = fechaIngreso.toTimeString().substring(0, 8); // HH:MM:SS
            }

            // Estado badge - usar EstadoIngreso o EstadoVehiculo
            const estado = vehiculo.EstadoIngreso || vehiculo.EstadoVehiculo || 'N/A';
            let estadoClass = 'secondary';
            let estadoIcon = 'fa-question-circle';
            let estadoTexto = estado;
            
            // Verificar si el vehículo está realmente en el taller
            // Si el estado es "En Circulación" pero el vehículo está en el taller, no mostrar "En Circulación"
            const estadosEnTaller = ['Ingresado', 'Asignado', 'Completado', 'En Proceso', 'En Revisión', 'En Pausa', 'En Taller'];
            const estaEnTaller = estadosEnTaller.includes(estado) || 
                                 estadosEnTaller.includes(vehiculo.EstadoVehiculo || '');
            
            if (estaEnTaller && estado === 'En Circulación') {
                // Si está en el taller pero dice "En Circulación", usar el estado del vehículo
                const estadoReal = vehiculo.EstadoVehiculo || 'En Taller';
                if (estadoReal === 'Completado') {
                    estadoClass = 'info';
                    estadoIcon = 'fa-check-double';
                    estadoTexto = 'Completado';
                } else if (estadoReal === 'Ingresado') {
                    estadoClass = 'success';
                    estadoIcon = 'fa-check-circle';
                    estadoTexto = 'En Taller';
                } else if (estadoReal === 'Asignado') {
                    estadoClass = 'warning';
                    estadoIcon = 'fa-clock';
                    estadoTexto = 'En Taller - Asignado';
                } else {
                    estadoClass = 'success';
                    estadoIcon = 'fa-wrench';
                    estadoTexto = 'En Taller';
                }
            } else if (estado === 'En Circulación') {
                estadoClass = 'info';
                estadoIcon = 'fa-road';
                estadoTexto = 'En Circulación';
            } else if (estado === 'Ingresado' || estado === 'En Taller') {
                estadoClass = 'success';
                estadoIcon = 'fa-check-circle';
                estadoTexto = 'En Taller';
            } else if (estado === 'Completado') {
                estadoClass = 'info';
                estadoIcon = 'fa-check-double';
                estadoTexto = 'Completado';
            } else if (estado === 'Asignado' || estado === 'Solicitó Hora') {
                estadoClass = 'warning';
                estadoIcon = 'fa-clock';
                estadoTexto = estado === 'Asignado' ? 'En Taller - Asignado' : 'Solicitó Hora';
            } else if (estado === 'No Llegó' || estado === 'No llegó') {
                estadoClass = 'danger';
                estadoIcon = 'fa-times-circle';
                estadoTexto = 'No Llegó';
            } else if (estado === 'Cancelada') {
                estadoClass = 'secondary';
                estadoIcon = 'fa-ban';
                estadoTexto = 'Cancelada';
            } else if (estado === 'Pendiente Ingreso') {
                estadoClass = 'warning';
                estadoIcon = 'fa-hourglass-half';
                estadoTexto = 'Pendiente Ingreso';
            } else if (estado === 'En Proceso' || estado === 'En Revisión' || estado === 'En Pausa') {
                estadoClass = 'warning';
                estadoIcon = 'fa-wrench';
                estadoTexto = 'En Taller - ' + estado;
            }

            // Vehículo completo
            const vehiculoCompleto = `${vehiculo.Marca || ''} ${vehiculo.Modelo || ''} ${vehiculo.TipoVehiculo || ''}`.trim() || 'N/A';

            // ID para el botón - usar SolicitudID si existe, sino VehiculoID
            const idParaDetalle = vehiculo.SolicitudID || vehiculo.VehiculoID;

            row.innerHTML = `
                <td><strong>${vehiculo.Placa}</strong></td>
                <td>${vehiculoCompleto}</td>
                <td>${vehiculo.ConductorNombre || 'N/A'}</td>
                <td data-order="${fechaParaOrdenar || '9999-12-31'}">${fechaFormateada}</td>
                <td data-order="${horaParaOrdenar || '23:59:59'}"><i class="fas fa-clock me-1"></i>${horaFormateada}</td>
                <td>${vehiculo.Proposito || 'N/A'}</td>
                <td>
                    <span class="badge bg-${estadoClass}">
                        <i class="fas ${estadoIcon} me-1"></i>${estadoTexto}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="vehiculosAgendados.verDetallesHistorial(${idParaDetalle}, ${vehiculo.SolicitudID ? 'true' : 'false'})" title="Ver detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(row);
        });

        // Inicializar o redibujar DataTable
        if (this.historialTable) {
            // Destruir tabla existente
            this.historialTable.destroy();
            this.historialTable = null;
        }
        
        // Esperar un momento para que el DOM se actualice
        setTimeout(() => {
            // Inicializar DataTable con configuración completa
            this.historialTable = $('#historial-vehiculos-table').DataTable({
                language: {
                    "sProcessing": "Procesando...",
                    "sLengthMenu": "Mostrar _MENU_ registros",
                    "sZeroRecords": "No se encontraron resultados",
                    "sEmptyTable": "Ningún dato disponible en esta tabla",
                    "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
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
                responsive: true,
                pageLength: 25,
                order: [[3, 'desc'], [4, 'desc']], // Orden inicial por fecha y hora descendente
                columnDefs: [
                    { 
                        type: 'date', 
                        targets: 3, // Columna de fecha (índice 3) - usar tipo date para ordenamiento correcto
                        orderable: true,
                        searchable: true
                    },
                    { 
                        targets: 4, // Columna de hora (índice 4)
                        orderable: true,
                        searchable: true
                    },
                    { 
                        targets: 5, // Columna de propósito (índice 5)
                        orderable: true,
                        searchable: true
                    },
                    { 
                        targets: [0, 1, 2, 6], // Placa, Vehículo, Conductor, Estado - todas ordenables y filtrables
                        orderable: true,
                        searchable: true
                    },
                    { 
                        orderable: false, 
                        searchable: false,
                        targets: 7 // Columna de acciones (índice 7) - no ordenable ni filtrable
                    }
                ],
                // Habilitar todas las funciones de DataTable
                searching: true,
                ordering: true,
                info: true,
                paging: true,
                lengthChange: true,
                // Asegurar que el ordenamiento funcione correctamente
                orderMulti: true
            });
        }, 100);
    }

    verDetallesHistorial(id, esSolicitud = false) {
        const modalElement = document.getElementById('detalles-modal');
        const content = document.getElementById('detalles-content');

        if (!modalElement || !content) return;

        content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-2">Cargando detalles...</p></div>';
        
        // Mostrar modal usando Bootstrap
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        // Buscar el vehículo en los datos del historial
        let vehiculo = null;
        if (this.historialData) {
            if (esSolicitud) {
                vehiculo = this.historialData.find(v => v.SolicitudID == id);
            } else {
                vehiculo = this.historialData.find(v => v.VehiculoID == id);
            }
        }

        if (!vehiculo) {
            content.innerHTML = '<p class="text-danger">No se pudieron cargar los detalles del vehículo.</p>';
            return;
        }

        // Reutilizar la misma función de mostrar detalles
        this.mostrarDetallesEnModal(vehiculo);
    }

    mostrarDetallesEnModal(vehiculo) {
        const content = document.getElementById('detalles-content');

        // Formatear fecha - usar FechaAgenda si existe, sino FechaIngreso
        const fechaParaMostrar = vehiculo.FechaAgenda || vehiculo.FechaIngreso;
        let fechaFormateada = 'N/A';
        if (fechaParaMostrar) {
            fechaFormateada = this.formatearFechaAgenda(fechaParaMostrar, {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        }

        // Formatear hora - usar HoraInicio/HoraFin de agenda si existe
        let horaFormateada = 'N/A';
        if (vehiculo.HoraInicio) {
            const horaInicio = vehiculo.HoraInicio.substring(0, 5);
        const horaFin = vehiculo.HoraFin ? vehiculo.HoraFin.substring(0, 5) : '';
            horaFormateada = horaFin ? `${horaInicio} - ${horaFin}` : horaInicio;
        } else if (vehiculo.FechaIngreso) {
            // Si no hay hora de agenda, usar hora de ingreso
            const fechaIngreso = new Date(vehiculo.FechaIngreso);
            horaFormateada = fechaIngreso.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        }

        // Vehículo completo
        const vehiculoCompleto = `${vehiculo.Marca || ''} ${vehiculo.Modelo || ''} ${vehiculo.TipoVehiculo || ''}`.trim() || 'N/A';

        // Estado badge - usar EstadoIngreso o EstadoVehiculo
        const estado = vehiculo.EstadoIngreso || vehiculo.EstadoVehiculo || 'N/A';
        let estadoClass = 'secondary';
        let estadoIcon = 'fa-question-circle';
        let estadoTexto = estado;
        
        // Verificar si el vehículo está realmente en el taller
        const estadosEnTaller = ['Ingresado', 'Asignado', 'Completado', 'En Proceso', 'En Revisión', 'En Pausa', 'En Taller'];
        const estaEnTaller = estadosEnTaller.includes(estado) || 
                             estadosEnTaller.includes(vehiculo.EstadoVehiculo || '');
        
        if (estaEnTaller && estado === 'En Circulación') {
            // Si está en el taller pero dice "En Circulación", usar el estado del vehículo
            const estadoReal = vehiculo.EstadoVehiculo || 'En Taller';
            if (estadoReal === 'Completado') {
                estadoClass = 'info';
                estadoIcon = 'fa-check-double';
                estadoTexto = 'Completado';
            } else if (estadoReal === 'Ingresado') {
                estadoClass = 'success';
                estadoIcon = 'fa-check-circle';
                estadoTexto = 'En Taller';
            } else if (estadoReal === 'Asignado') {
                estadoClass = 'warning';
                estadoIcon = 'fa-clock';
                estadoTexto = 'En Taller - Asignado';
            } else {
                estadoClass = 'success';
                estadoIcon = 'fa-wrench';
                estadoTexto = 'En Taller';
            }
        } else if (estado === 'En Circulación') {
            estadoClass = 'info';
            estadoIcon = 'fa-road';
            estadoTexto = 'En Circulación';
        } else if (estado === 'Ingresado' || estado === 'En Taller') {
            estadoClass = 'success';
            estadoIcon = 'fa-check-circle';
            estadoTexto = 'En Taller';
        } else if (estado === 'Completado') {
            estadoClass = 'info';
            estadoIcon = 'fa-check-double';
            estadoTexto = 'Completado';
        } else if (estado === 'Asignado' || estado === 'Solicitó Hora') {
            estadoClass = 'warning';
            estadoIcon = 'fa-clock';
            estadoTexto = estado === 'Asignado' ? 'En Taller - Asignado' : 'Solicitó Hora';
        } else if (estado === 'No Llegó' || estado === 'No llegó') {
            estadoClass = 'danger';
            estadoIcon = 'fa-times-circle';
            estadoTexto = 'No Llegó';
        } else if (estado === 'Cancelada') {
            estadoClass = 'secondary';
            estadoIcon = 'fa-ban';
            estadoTexto = 'Cancelada';
        } else if (estado === 'Pendiente Ingreso') {
            estadoClass = 'warning';
            estadoIcon = 'fa-hourglass-half';
            estadoTexto = 'Pendiente Ingreso';
        } else if (estado === 'En Proceso' || estado === 'En Revisión' || estado === 'En Pausa') {
            estadoClass = 'warning';
            estadoIcon = 'fa-wrench';
            estadoTexto = 'En Taller - ' + estado;
        } else {
            estadoClass = 'secondary';
            estadoIcon = 'fa-question-circle';
        }

        content.innerHTML = `
            <div class="detalles-vehiculo">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-car me-2"></i>Información del Vehículo
                                </h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th width="40%" class="text-muted">Placa:</th>
                                        <td><strong class="text-dark">${vehiculo.Placa}</strong></td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">Vehículo:</th>
                                        <td>${vehiculoCompleto}</td>
                                    </tr>
                                    ${vehiculo.Anio ? `
                                    <tr>
                                        <th class="text-muted">Año:</th>
                                        <td>${vehiculo.Anio}</td>
                                    </tr>
                                    ` : ''}
                                    <tr>
                                        <th class="text-muted">Conductor:</th>
                                        <td>${vehiculo.ConductorNombre || 'N/A'}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-calendar me-2"></i>Información de Agenda
                                </h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless table-sm mb-0">
                                    ${vehiculo.FechaAgenda ? `
                                    <tr>
                                        <th width="40%" class="text-muted">Fecha Agenda:</th>
                                        <td><i class="fas fa-calendar-alt me-1 text-primary"></i>${fechaFormateada}</td>
                                    </tr>
                                    ` : vehiculo.FechaIngreso ? `
                                    <tr>
                                        <th width="40%" class="text-muted">Fecha Ingreso:</th>
                                        <td><i class="fas fa-calendar-alt me-1 text-primary"></i>${fechaFormateada}</td>
                                    </tr>
                                    ` : ''}
                                    ${vehiculo.HoraInicio ? `
                                    <tr>
                                        <th class="text-muted">Hora Agenda:</th>
                                        <td><i class="fas fa-clock me-1 text-primary"></i>${horaFormateada}</td>
                                    </tr>
                                    ` : vehiculo.FechaIngreso ? `
                                    <tr>
                                        <th class="text-muted">Hora Ingreso:</th>
                                        <td><i class="fas fa-clock me-1 text-primary"></i>${horaFormateada}</td>
                                    </tr>
                                    ` : ''}
                                    <tr>
                                        <th class="text-muted">Estado:</th>
                                        <td><span class="badge bg-${estadoClass}"><i class="fas ${estadoIcon} me-1"></i>${estadoTexto}</span></td>
                                    </tr>
                                    ${vehiculo.FechaSalida ? `
                                    <tr>
                                        <th class="text-muted">Fecha Salida:</th>
                                        <td><i class="fas fa-sign-out-alt me-1 text-primary"></i>${this.formatearFechaAgenda(vehiculo.FechaSalida, {
                                            weekday: 'short',
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric'
                                        })}</td>
                                    </tr>
                                    ` : ''}
                                    ${vehiculo.FechaRegistro ? `
                                    <tr>
                                        <th class="text-muted">Fecha Registro:</th>
                                        <td><i class="fas fa-calendar me-1 text-primary"></i>${this.formatearFechaAgenda(vehiculo.FechaRegistro, {
                                            weekday: 'short',
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric'
                                        })}</td>
                                    </tr>
                                    ` : ''}
                                    ${vehiculo.SupervisorNombre ? `
                                    <tr>
                                        <th class="text-muted">Supervisor:</th>
                                        <td><i class="fas fa-user-tie me-1 text-primary"></i>${vehiculo.SupervisorNombre}</td>
                                    </tr>
                                    ` : ''}
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-info-circle me-2"></i>Propósito
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">${vehiculo.Proposito || 'No especificado'}</p>
                            </div>
                        </div>
                    </div>
                    ${vehiculo.Observaciones ? `
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-sticky-note me-2"></i>Observaciones
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">${vehiculo.Observaciones}</p>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    verDetalles(solicitudId) {
        const modalElement = document.getElementById('detalles-modal');
        const content = document.getElementById('detalles-content');

        if (!modalElement || !content) return;

        content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-2">Cargando detalles...</p></div>';
        
        // Mostrar modal usando Bootstrap
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        // Buscar el vehículo en los datos cargados
        let vehiculo = null;
        if (this.vehiculosData) {
            vehiculo = this.vehiculosData.find(v => v.SolicitudID == solicitudId);
        }

        if (!vehiculo) {
            content.innerHTML = '<p class="text-danger">No se pudieron cargar los detalles del vehículo.</p>';
            return;
        }

        // Reutilizar la función de mostrar detalles
        this.mostrarDetallesEnModal(vehiculo);
    }

    mostrarError(mensaje) {
        const tbody = document.querySelector('#vehiculos-agendados-table tbody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                        <p>${mensaje}</p>
                    </td>
                </tr>
            `;
        }
    }

    mostrarErrorHistorial(mensaje) {
        const tbody = document.querySelector('#historial-vehiculos-table tbody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                        <p>${mensaje}</p>
                    </td>
                </tr>
            `;
        }
    }

    marcarCompletado(solicitudId, placa) {
        if (!confirm(`¿Está seguro de marcar la solicitud del vehículo ${placa} como completada?\n\nEsto ocultará el vehículo de la lista principal.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'marcarSolicitudCompletada');
        formData.append('solicitud_id', solicitudId);

        fetch(this.baseUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.mostrarMensaje('Vehículo marcado como completado y ocultado de la lista', 'success');
                this.cargarVehiculos(); // Recargar la lista
            } else {
                this.mostrarMensaje(data.message || 'Error al marcar como completado', 'error');
            }
        })
        .catch(error => {
            console.error('Error al marcar como completado:', error);
            this.mostrarMensaje('Error de conexión al marcar como completado', 'error');
        });
    }

    mostrarMensaje(mensaje, tipo) {
        // Crear un toast o alert simple
        const alertClass = tipo === 'success' ? 'alert-success' : 'alert-danger';
        const alertHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 80px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', alertHTML);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            const alert = document.querySelector('.alert:last-of-type');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
}

// Inicializar cuando el DOM esté listo
let vehiculosAgendados;
document.addEventListener('DOMContentLoaded', () => {
    vehiculosAgendados = new VehiculosAgendados();
});

