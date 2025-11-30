class ControlIngresoApp {
    constructor() {
        this.vehiculoActual = null;
        this.stream = null;
        this.tipoOperacion = 'ingreso';
        this.fotosCapturadas = [];
        this.tipoFotoActual = '';
        this.init();
    }

    init() {
        this.bindEvents();
        this.cargarEstadisticas();
        this.cargarNovedades();
        this.actualizarInterfazOperacion();
    }

    bindEvents() {
        // Selector de operación
        $('input[name="tipoOperacion"]').change(() => {
            this.tipoOperacion = $('input[name="tipoOperacion"]:checked').val();
            this.actualizarInterfazOperacion();
            this.limpiarBusqueda();
        });

        // Búsqueda
        $('#btnBuscarPlaca').click(() => this.buscarPorPlaca());
        $('#placaBusqueda').keypress((e) => {
            if (e.which === 13) this.buscarPorPlaca();
        });

        // Acciones principales
        $('#btnProcesarIngreso').click(() => this.procesarIngreso());
        $('#btnProcesarSalida').click(() => this.procesarSalida());
        $('#btnReportarNovedad').click(() => this.mostrarModalNovedad());

        // Modal Foto
        $('#btnCapturar').click(() => this.capturarFoto());
        $('#btnGuardarFoto').click(() => this.guardarFoto());

        // Modal Novedad
        $('#btnGuardarNovedad').click(() => this.guardarNovedad());
        
        // Modal Atrasado - Botón permitir ingreso con motivo
        $('#btnPermitirIngresoConMotivo').click(() => this.permitirIngresoConMotivo());
    }

    actualizarInterfazOperacion() {
        const esIngreso = this.tipoOperacion === 'ingreso';
        const texto = esIngreso ? 'INGRESO' : 'SALIDA';
        const clase = esIngreso ? 'operacion-ingreso' : 'operacion-salida';
        
        $('#textoOperacion').html(`Preparado para registrar <span class="${clase}">${texto}</span> del vehículo`);
        $('#textoDocumentacion').text(
            esIngreso 
                ? 'Documente el estado INICIAL del vehículo con fotografías' 
                : 'Documente el estado FINAL del vehículo con fotografías'
        );

        // Actualizar botones
        $('#btnProcesarIngreso').toggle(esIngreso);
        $('#btnProcesarSalida').toggle(!esIngreso);
        
        // Habilitar/deshabilitar botones
        this.actualizarEstadoBotones();
    }

    buscarPorPlaca() {
        const placa = $('#placaBusqueda').val().trim().toUpperCase();
        if (!placa) {
            this.mostrarAlerta('Ingrese una placa para buscar', 'warning');
            return;
        }

        this.verificarEstadoVehiculo(placa);
    }

    verificarEstadoVehiculo(placa) {
        $('.guardia-card').addClass('search-loading');
        
        // Obtener fecha actual en formato YYYY-MM-DD
        const fechaActual = new Date().toISOString().split('T')[0];
        
        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: {
                action: 'verificarEstado',
                placa: placa,
                fecha: fechaActual,
                tipo_operacion: this.tipoOperacion
            },
            dataType: 'json',
            success: (response) => {
                $('.guardia-card').removeClass('search-loading');
                
                if (response.success) {
                    const data = response.data;
                    this.vehiculoActual = data;
                    
                    // Si es atrasado o temprano y requiere motivo de retraso, mostrar modal
                    if ((data.es_atrasado || data.es_temprano) && data.requiere_motivo_retraso) {
                        this.mostrarModalAtrasado(data);
                        // También mostrar información básica
                        if (data.vehiculo) {
                            this.mostrarInformacionBasica(data);
                        }
                    } else if (data.es_atrasado && !data.puede_ingresar) {
                        // Si es atrasado y NO puede ingresar (caso antiguo, no debería llegar aquí)
                        this.mostrarModalAtrasado(data);
                        // También mostrar información básica
                        if (data.vehiculo) {
                            this.mostrarInformacionBasica(data);
                        }
                    } else if (data.tiene_agenda && data.agenda && data.puede_ingresar) {
                        // Si tiene agenda aprobada y puede ingresar (incluso si está atrasado entre 10-30 min)
                        this.mostrarInformacionConAgenda(data);
                        // Si está atrasado pero puede ingresar, mostrar advertencia
                        if (data.es_atrasado && data.tipo_atraso === 'hora' && data.mensaje) {
                            this.mostrarAlerta(data.mensaje, 'warning');
                        }
                    } else if (data.tiene_agenda && data.agenda && !data.puede_ingresar) {
                        // Tiene agenda pero no puede ingresar (fuera de horario, otro día, etc.)
                        this.mostrarInformacionConAgenda(data);
                        if (data.mensaje) {
                            this.mostrarAlerta(data.mensaje, 'warning');
                        }
                    } else if (data.puede_salir) {
                        // Vehículo completado - puede salir
                        this.mostrarInformacionBasica(data);
                    } else {
                        // No puede ingresar ni salir
                        if (data.mensaje) {
                            this.mostrarAlerta(data.mensaje, 'warning');
                        } else {
                            this.mostrarAlerta('No se puede procesar esta operación', 'warning');
                        }
                        // Mostrar información del vehículo aunque no pueda procesar
                        if (data.vehiculo) {
                            this.mostrarInformacionBasica(data);
                        } else {
                            this.ocultarInformacionVehiculo();
                        }
                    }
                } else {
                    // No tiene agenda aprobada o no está ingresado
                    this.mostrarAlerta(response.message, 'error');
                    this.ocultarInformacionVehiculo();
                }
            },
            error: () => {
                $('.guardia-card').removeClass('search-loading');
                this.mostrarAlerta('Error en la verificación', 'error');
            }
        });
    }

    mostrarPreparadoIngreso(placa) {
        this.vehiculoActual = { Placa: placa };
        
        $('#infoPlaca').text(placa);
        $('#infoEstado').html('<span class="badge bg-warning">Nuevo Ingreso</span>');
        $('#infoFechaIngreso').text('Al confirmar ingreso');
        $('#infoConductorNombre').text('Por completar (Conductor)');
        
        $('#sinResultados').hide();
        $('#conResultados').show().addClass('fade-in');
        
        this.actualizarEstadoBotones();
    }

    mostrarInformacionBasica(data) {
        const vehiculo = data.vehiculo || data;
        
        $('#infoPlaca').text(vehiculo.Placa || '-');
        $('#infoConductorNombre').text(vehiculo.ConductorNombre || 'N/A');
        
        // Limpiar información adicional del vehículo
        $('#infoVehiculoDetalles').empty();
        $('#infoVehiculo').hide();
        
        if (vehiculo.Estado === 'Completado') {
            $('#infoEstado').html('<span class="badge bg-success">Completado - Listo para Salir</span>');
            $('#infoFechaIngreso').text(this.formatearFecha(vehiculo.FechaIngreso));
            
            // Mostrar información adicional del vehículo si existe
            this.mostrarInfoAdicionalVehiculo(vehiculo);
            
            // Mostrar alerta informativa para salida
            const alertHtml = `
                <div class="alert alert-success mb-3" id="alertAgenda">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Vehículo completado:</strong> El mecánico ha terminado el proceso. El vehículo está listo para salir.
                    <br><small class="mt-2 d-block"><i class="fas fa-info-circle me-1"></i>Puede proceder con el registro de salida usando el botón "Confirmar Salida".</small>
                </div>
            `;
            
            if ($('#alertAgenda').length === 0) {
                $('#alertOperacion').after(alertHtml);
            } else {
                $('#alertAgenda').replaceWith(alertHtml);
            }
        } else if (vehiculo.Estado === 'Ingresado') {
            $('#infoEstado').html('<span class="badge bg-info">En Patio - En Proceso</span>');
            $('#infoFechaIngreso').text(this.formatearFecha(vehiculo.FechaIngreso));
            
            // Mostrar información adicional del vehículo si existe
            this.mostrarInfoAdicionalVehiculo(vehiculo);
            
            // Si no puede salir, mostrar mensaje
            if (data.mensaje) {
                const alertHtml = `
                    <div class="alert alert-warning mb-3" id="alertAgenda">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Información:</strong> ${data.mensaje}
                    </div>
                `;
                
                if ($('#alertAgenda').length === 0) {
                    $('#alertOperacion').after(alertHtml);
                } else {
                    $('#alertAgenda').replaceWith(alertHtml);
                }
            } else {
                $('#alertAgenda').remove();
            }
        } else {
            $('#infoEstado').html('<span class="badge bg-warning">Pendiente Ingreso</span>');
            $('#infoFechaIngreso').text('Al confirmar ingreso');
            $('#alertAgenda').remove();
        }
        
        $('#sinResultados').hide();
        $('#conResultados').show().addClass('fade-in');
        
        this.actualizarEstadoBotones();
    }
    
    mostrarInfoAdicionalVehiculo(vehiculo) {
        const detalles = [];
        
        if (vehiculo.TipoVehiculo) {
            detalles.push({
                label: 'Tipo:',
                valor: vehiculo.TipoVehiculo
            });
        }
        
        if (vehiculo.Marca && vehiculo.Modelo) {
            detalles.push({
                label: 'Vehículo:',
                valor: `${vehiculo.Marca} ${vehiculo.Modelo}`
            });
        }
        
        if (vehiculo.Proposito) {
            detalles.push({
                label: 'Propósito:',
                valor: vehiculo.Proposito
            });
        }
        
        if (detalles.length > 0) {
            let html = '';
            detalles.forEach(detalle => {
                html += `
                    <tr>
                        <td class="fw-bold text-muted" width="40%">${detalle.label}</td>
                        <td class="text-dark">${detalle.valor}</td>
                    </tr>
                `;
            });
            $('#infoVehiculoDetalles').html(html);
            $('#infoVehiculo').show();
        }
    }

    mostrarInformacionConAgenda(data) {
        const vehiculo = data.vehiculo;
        const agenda = data.agenda;
        const puedeIngresar = data.puede_ingresar;
        
        $('#infoPlaca').text(vehiculo.Placa || '-');
        $('#infoConductorNombre').text(vehiculo.ConductorNombre || 'N/A');
        
        // Cambiar el badge según si puede ingresar o no
        if (puedeIngresar) {
            $('#infoEstado').html('<span class="badge bg-success">Con Hora Asignada - Puede Ingresar</span>');
        } else {
            $('#infoEstado').html('<span class="badge bg-warning">Con Hora Asignada - No Puede Ingresar</span>');
        }
        
        $('#infoFechaIngreso').html(`
            <strong>Fecha:</strong> ${this.formatearFecha(agenda.FechaAgenda)}<br>
            <strong>Hora:</strong> ${this.formatearHora(agenda.HoraInicio)} - ${this.formatearHora(agenda.HoraFin)}
        `);
        
        // Mostrar información adicional del vehículo si existe
        this.mostrarInfoAdicionalVehiculo(vehiculo);
        
        // Mostrar alerta informativa sobre la agenda según si puede ingresar o no
        let alertHtml = '';
        if (puedeIngresar) {
            // Si puede ingresar, mostrar el mensaje de "Preparado para registrar" y la alerta de éxito
            $('#alertOperacion').show();
            alertHtml = `
                <div class="alert alert-success mb-3" id="alertAgenda">
                    <i class="fas fa-calendar-check me-2"></i>
                    <strong>Vehículo con hora asignada:</strong> Este vehículo tiene una cita agendada y aprobada para hoy.
                    <br><small>Puede proceder con el registro de ingreso.</small>
                </div>
            `;
        } else {
            // Si no puede ingresar, ocultar el mensaje de "Preparado para registrar" y mostrar solo la restricción
            $('#alertOperacion').hide();
            const mensaje = data.mensaje || 'No se puede procesar el ingreso en este momento.';
            alertHtml = `
                <div class="alert alert-warning mb-3" id="alertAgenda">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Restricción de ingreso:</strong> ${mensaje}
                </div>
            `;
        }
        
        // Insertar alerta después de la alerta de operación si no existe
        if ($('#alertAgenda').length === 0) {
            $('#alertOperacion').after(alertHtml);
        } else {
            $('#alertAgenda').replaceWith(alertHtml);
        }
        
        $('#sinResultados').hide();
        $('#conResultados').show().addClass('fade-in');
        
        this.actualizarEstadoBotones();
    }

    formatearHora(hora) {
        if (!hora) return '-';
        // Formatear hora de formato HH:MM:SS a HH:MM
        const partes = hora.split(':');
        return `${partes[0]}:${partes[1]}`;
    }

    actualizarEstadoBotones() {
        const tieneVehiculo = this.vehiculoActual !== null;
        const tieneFotos = this.fotosCapturadas.length > 0;
        
        // Fotos son opcionales - no se requieren para confirmar ingreso
        // const fotosSuficientes = this.fotosCapturadas.length >= 2;
        
        // Para ingreso: solo si tiene agenda aprobada o atrasada (pero puede ingresar)
        const puedeIngresar = tieneVehiculo && this.vehiculoActual.puede_ingresar;
        // Para salida: solo si el vehículo está ingresado
        const puedeSalir = tieneVehiculo && this.vehiculoActual.puede_salir;
        
        // Fotos son opcionales para ingreso y salida
        $('#btnProcesarIngreso').prop('disabled', !puedeIngresar);
        $('#btnProcesarSalida').prop('disabled', !puedeSalir); // Fotos opcionales para salida también
        $('#btnReportarNovedad').prop('disabled', !tieneVehiculo);
    }

    procesarIngreso() {
        if (!this.vehiculoActual) {
            this.mostrarAlerta('Primero busque un vehículo por placa', 'warning');
            return;
        }

        // Verificar que tenga agenda y pueda ingresar
        if (!this.vehiculoActual.puede_ingresar) {
            this.mostrarAlerta('Este vehículo no puede ingresar en este momento. Verifique la hora asignada.', 'warning');
            return;
        }

        // Si requiere motivo de retraso, mostrar el modal en lugar de procesar directamente
        if (this.vehiculoActual.requiere_motivo_retraso) {
            this.mostrarModalAtrasado(this.vehiculoActual);
            return;
        }

        // Las fotos son opcionales - si hay fotos, se guardarán, pero no son requeridas
        this.registrarIngresoBasico();
    }

    registrarIngresoBasico() {
        // Obtener la placa del vehículo, manejando tanto vehículos ingresados como con agenda
        const placa = this.vehiculoActual.vehiculo ? this.vehiculoActual.vehiculo.Placa : this.vehiculoActual.Placa;
        
        if (!placa) {
            this.mostrarAlerta('No se pudo obtener la placa del vehículo', 'error');
            return;
        }
        
        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: {
                action: 'registrarIngresoBasico',
                placa: placa
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    // Guardar fotos si hay (opcionales)
                    if (this.fotosCapturadas.length > 0) {
                        this.guardarTodasLasFotos(placa);
                    }
                    this.mostrarModalExito('Ingreso registrado correctamente', 'El vehículo ha sido ingresado al taller.');
                    this.limpiarInterfaz();
                    this.cargarEstadisticas();
                    this.cargarNovedades(); // Actualizar movimientos
                } else {
                    this.mostrarAlerta(response.message || 'Error al registrar ingreso', 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error en registrarIngresoBasico:', error, xhr.responseText);
                this.mostrarAlerta('Error al registrar ingreso: ' + error, 'error');
            }
        });
    }

    procesarSalida() {
        if (!this.vehiculoActual) {
            this.mostrarAlerta('Primero busque un vehículo por placa', 'warning');
            return;
        }

        // Verificar que el vehículo pueda salir
        if (!this.vehiculoActual.puede_salir) {
            this.mostrarAlerta('Este vehículo no puede salir. Debe estar completado por el mecánico.', 'warning');
            return;
        }

        // Las fotos son opcionales - si hay fotos, se guardarán, pero no son requeridas
        this.registrarSalida();
    }

    registrarSalida() {
        // Obtener la placa del vehículo, manejando tanto vehículos ingresados como con agenda
        const placa = this.vehiculoActual.vehiculo ? this.vehiculoActual.vehiculo.Placa : this.vehiculoActual.Placa;
        
        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: {
                action: 'registrarSalida',
                placa: placa
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.guardarTodasLasFotos(placa);
                    this.mostrarModalExito('Salida registrada correctamente', 'El vehículo ha sido registrado como salido del taller.');
                    this.limpiarInterfaz();
                    this.cargarEstadisticas();
                    this.cargarNovedades(); // Actualizar movimientos
                } else {
                    this.mostrarAlerta(response.message, 'error');
                }
            },
            error: () => {
                this.mostrarAlerta('Error al registrar salida', 'error');
            }
        });
    }

    guardarTodasLasFotos(placa) {
        if (this.fotosCapturadas.length === 0) return;
        
        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: {
                action: 'guardarFotos',
                placa: placa,
                fotos: JSON.stringify(this.fotosCapturadas)
            },
            dataType: 'json',
            success: (response) => {
                if (!response.success) {
                    console.error('Error al guardar fotos:', response.message);
                }
            }
        });
    }

    iniciarCapturaFoto(tipo) {
        if (!this.vehiculoActual) {
            this.mostrarAlerta('Primero busque un vehículo', 'warning');
            return;
        }

        this.tipoFotoActual = tipo;
        const textos = {
            'frontal': 'Vista Frontal - Capture toda la parte delantera del vehículo',
            'lateral-izq': 'Lateral Izquierdo - Incluya espejos y ruedas',
            'lateral-der': 'Lateral Derecho - Incluya espejos y ruedas',
            'trasera': 'Vista Trasera - Capture placa y luces traseras',
            'interior': 'Interior - Tablero y asientos',
            'daños': 'Daños/Detalles - Áreas específicas con daños'
        };

        $('#modalFotoTitulo').text(`Capturar ${tipo.replace('-', ' ')}`);
        $('#tipoFotoTexto').text(tipo.replace('-', ' '));
        $('#instruccionesFoto').text(textos[tipo] || 'Asegure buena iluminación y enfoque');
        
        $('#modalFoto').modal('show');
        this.iniciarVideo();
    }

    async iniciarVideo() {
        try {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }
            
            this.stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'environment'
                } 
            });
            const video = document.getElementById('video');
            video.srcObject = this.stream;
            
            $('#btnCapturar').show();
            $('#btnGuardarFoto').hide();
            $('#fotoPreview').html('<p class="text-muted">La foto aparecerá aquí después de capturar</p>');
        } catch (err) {
            this.mostrarAlerta('Error al acceder a la cámara: ' + err.message, 'error');
        }
    }

    capturarFoto() {
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        const fotoData = canvas.toDataURL('image/jpeg');
        $('#fotoPreview').html(`<img src="${fotoData}" class="img-fluid rounded" alt="Foto capturada">`);
        
        $('#btnCapturar').hide();
        $('#btnGuardarFoto').show().data('foto', fotoData);

        // Detener la cámara
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
        }
    }

    guardarFoto() {
        const fotoData = $('#btnGuardarFoto').data('foto');
        const tipo = this.tipoFotoActual;
        
        // Agregar foto a la lista
        const nuevaFoto = {
            data: fotoData,
            tipo: 'foto_vehiculo',
            angulo: tipo,
            fecha: new Date().toISOString()
        };
        
        this.fotosCapturadas.push(nuevaFoto);
        this.actualizarGaleriaFotos();
        
        this.mostrarAlerta(`Foto ${tipo.replace('-', ' ')} capturada correctamente`, 'success');
        $('#modalFoto').modal('hide');
        this.actualizarEstadoBotones();
    }

    actualizarGaleriaFotos() {
        const listaFotos = $('#listaFotos');
        const sinFotos = $('#sinFotos');
        
        if (this.fotosCapturadas.length === 0) {
            listaFotos.hide();
            sinFotos.show();
            return;
        }
        
        sinFotos.hide();
        listaFotos.show().empty();
        
        this.fotosCapturadas.forEach((foto, index) => {
            const badgeClass = {
                'frontal': 'bg-primary',
                'lateral-izq': 'bg-info',
                'lateral-der': 'bg-info',
                'trasera': 'bg-warning',
                'interior': 'bg-success',
                'daños': 'bg-danger'
            }[foto.angulo] || 'bg-secondary';
            
            const html = `
                <div class="col-4 col-md-3">
                    <div class="position-relative">
                        <img src="${foto.data}" class="foto-miniatura" alt="${foto.angulo}">
                        <span class="badge badge-foto-tipo ${badgeClass}">${foto.angulo.substring(0, 3)}</span>
                    </div>
                </div>
            `;
            listaFotos.append(html);
        });
    }

    mostrarModalNovedad() {
        if (!this.vehiculoActual) {
            this.mostrarAlerta('No hay vehículo seleccionado', 'warning');
            return;
        }

        $('#modalNovedad').modal('show');
    }

    guardarNovedad() {
        const tipo = $('#tipoNovedad').val();
        const descripcion = $('#descripcionNovedad').val();
        const gravedad = $('#gravedadNovedad').val();

        if (!descripcion.trim()) {
            this.mostrarAlerta('Ingrese una descripción de la novedad', 'warning');
            return;
        }

        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: {
                action: 'reportarNovedad',
                placa: this.vehiculoActual.vehiculo ? this.vehiculoActual.vehiculo.Placa : this.vehiculoActual.Placa,
                tipo: tipo,
                descripcion: descripcion,
                gravedad: gravedad
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.mostrarAlerta('Novedad reportada correctamente', 'success');
                    $('#modalNovedad').modal('hide');
                    this.cargarNovedades();
                    this.limpiarModalNovedad();
                } else {
                    this.mostrarAlerta('Error al reportar la novedad', 'error');
                }
            }
        });
    }

    limpiarModalNovedad() {
        $('#tipoNovedad').val('Daño vehiculo');
        $('#descripcionNovedad').val('');
        $('#gravedadNovedad').val('Media');
    }

    cargarEstadisticas() {
        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: { action: 'obtenerEstadisticas' },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    $('#vehiculosActivos').text(response.data.vehiculosActivos);
                    $('#ingresosHoy').text(response.data.ingresosHoy);
                }
            }
        });
    }

    cargarNovedades() {
        // Mostrar estado de carga
        $('#listaNovedades').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-2"></i>Cargando movimientos...</div>');
        
        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: { action: 'obtenerMovimientosDelDia' },
            dataType: 'json',
            timeout: 10000, // 10 segundos de timeout
            success: (response) => {
                console.log('Respuesta de movimientos:', response);
                if (response && response.success !== undefined) {
                    const movimientos = response.data || [];
                    this.mostrarMovimientos(movimientos);
                } else {
                    console.warn('Respuesta sin success:', response);
                    $('#listaNovedades').html('<div class="text-center text-muted py-4"><i class="fas fa-info-circle me-2"></i>No hay movimientos registrados hoy</div>');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar movimientos:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                
                let mensaje = 'Error al cargar movimientos';
                if (status === 'timeout') {
                    mensaje = 'Tiempo de espera agotado. Intente nuevamente.';
                } else if (xhr.status === 0) {
                    mensaje = 'No se pudo conectar con el servidor.';
                }
                
                $('#listaNovedades').html(`<div class="text-center text-muted py-4"><i class="fas fa-exclamation-triangle me-2"></i>${mensaje}</div>`);
            }
        });
    }

    mostrarMovimientos(movimientos) {
        const container = $('#listaNovedades');
        
        if (movimientos.length === 0) {
            container.html('<div class="text-center text-muted py-4"><i class="fas fa-info-circle me-2"></i>No hay movimientos registrados hoy</div>');
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Hora</th>
                            <th style="width: 100px;">Tipo</th>
                            <th style="width: 100px;">Placa</th>
                            <th>Vehículo</th>
                            <th>Conductor</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        movimientos.forEach(movimiento => {
            const esIngreso = movimiento.tipo_movimiento === 'Ingreso';
            const tipoBadge = esIngreso ? 'success' : 'info';
            const tipoIcono = esIngreso ? 'fa-arrow-down' : 'fa-arrow-up';
            const tipoTexto = esIngreso ? 'Ingreso' : 'Salida';
            
            // Formatear fecha y hora
            const fechaHora = new Date(movimiento.fecha);
            const hora = fechaHora.toLocaleTimeString('es-ES', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            // Obtener información del vehículo
            const vehiculo = `${movimiento.marca || ''} ${movimiento.modelo || ''}`.trim() || '<span class="text-muted">-</span>';
            const conductor = movimiento.conductor && movimiento.conductor !== 'N/A' ? movimiento.conductor : '<span class="text-muted">-</span>';
            
            // Formatear estado de manera más atractiva
            let estadoHtml = '';
            const estado = movimiento.estado || '';
            
            if (!estado || estado === 'N/A' || estado.trim() === '') {
                // Si no hay estado, mostrar un badge más bonito
                if (esIngreso) {
                    estadoHtml = '<span class="badge bg-success bg-opacity-75">En Taller</span>';
                } else {
                    estadoHtml = '<span class="badge bg-info bg-opacity-75">Retirado</span>';
                }
            } else {
                // Mapear estados a colores más bonitos
                const estadoLower = estado.toLowerCase();
                let estadoBadgeClass = 'bg-secondary';
                
                if (estadoLower.includes('completado') || estadoLower.includes('finalizado')) {
                    estadoBadgeClass = 'bg-success';
                } else if (estadoLower.includes('ingresado') || estadoLower.includes('asignado')) {
                    estadoBadgeClass = 'bg-primary';
                } else if (estadoLower.includes('en proceso') || estadoLower.includes('proceso')) {
                    estadoBadgeClass = 'bg-warning';
                } else if (estadoLower.includes('cancelado') || estadoLower.includes('rechazado')) {
                    estadoBadgeClass = 'bg-danger';
                } else if (estadoLower.includes('retirado') || estadoLower.includes('salida')) {
                    estadoBadgeClass = 'bg-info';
                }
                
                estadoHtml = `<span class="badge ${estadoBadgeClass}">${estado}</span>`;
            }
            
            html += `
                <tr>
                    <td class="text-muted"><small>${hora}</small></td>
                    <td>
                        <span class="badge bg-${tipoBadge}">
                            <i class="fas ${tipoIcono} me-1"></i>${tipoTexto}
                        </span>
                    </td>
                    <td><strong>${movimiento.placa}</strong></td>
                    <td>${vehiculo}</td>
                    <td>${conductor}</td>
                    <td>${estadoHtml}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        container.html(html);
    }

    formatearFecha(fecha) {
        if (!fecha) return '-';
        // Si viene en formato YYYY-MM-DD, formatear a DD/MM/YYYY
        if (fecha.match(/^\d{4}-\d{2}-\d{2}$/)) {
            const partes = fecha.split('-');
            return `${partes[2]}/${partes[1]}/${partes[0]}`;
        }
        // Si ya tiene formato de fecha/hora, formatear
        return new Date(fecha).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    limpiarInterfaz() {
        $('#placaBusqueda').val('');
        this.vehiculoActual = null;
        this.fotosCapturadas = [];
        $('#listaFotos').empty();
        $('#sinFotos').show();
        this.ocultarInformacionVehiculo();
        this.actualizarEstadoBotones();
    }

    limpiarBusqueda() {
        $('#placaBusqueda').val('');
        this.vehiculoActual = null;
        this.fotosCapturadas = [];
        $('#listaFotos').empty();
        $('#sinFotos').show();
        this.ocultarInformacionVehiculo();
        this.actualizarEstadoBotones();
    }

    ocultarInformacionVehiculo() {
        $('#sinResultados').show();
        $('#conResultados').hide();
    }

    mostrarModalAtrasado(data) {
        const vehiculo = data.vehiculo;
        const agenda = data.agenda;
        const tipoAtraso = data.tipo_atraso; // 'fecha', 'hora', 'no_llego', o 'temprano'
        const esNoLlego = tipoAtraso === 'no_llego';
        const esTemprano = tipoAtraso === 'temprano';
        
        // Formatear hora actual
        let horaActualFormateada = '';
        if (data.hora_actual) {
            const partes = data.hora_actual.split(':');
            if (partes.length >= 2) {
                horaActualFormateada = `${partes[0]}:${partes[1]}`;
            } else {
                horaActualFormateada = data.hora_actual;
            }
        } else {
            const ahora = new Date();
            horaActualFormateada = ahora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        }
        
        // Calcular diferencia de tiempo
        let diferenciaTexto = '';
        if (tipoAtraso === 'hora' || tipoAtraso === 'no_llego' || tipoAtraso === 'temprano') {
            const horaInicio = new Date(`2000-01-01 ${agenda.HoraInicio}`);
            const horaActual = new Date(`2000-01-01 ${data.hora_actual || horaActualFormateada}`);
            const diferenciaMs = horaActual - horaInicio;
            const diferenciaMin = Math.abs(Math.floor(diferenciaMs / 60000));
            
            if (diferenciaMin > 0) {
                if (diferenciaMin >= 60) {
                    const horas = Math.floor(diferenciaMin / 60);
                    const minutos = diferenciaMin % 60;
                    diferenciaTexto = minutos > 0 ? `${horas}h ${minutos}min` : `${horas}h`;
                } else {
                    diferenciaTexto = `${diferenciaMin} minutos`;
                }
            }
        }
        
        // Llenar información del modal
        $('#modalAtrasadoPlaca').text(vehiculo.Placa || '-');
        $('#modalAtrasadoFecha').text(this.formatearFecha(agenda.FechaAgenda));
        $('#modalAtrasadoHora').text(`${this.formatearHora(agenda.HoraInicio)} - ${this.formatearHora(agenda.HoraFin)}`);
        $('#modalAtrasadoHoraLlegada').text(horaActualFormateada);
        
        // Verificar si requiere motivo de retraso
        const requiereMotivo = data.requiere_motivo_retraso === true;
        
        // Personalizar según el tipo de atraso
        if (esTemprano && requiereMotivo) {
            // Llegó temprano - más de 10 minutos antes
            $('#modalAtrasadoHeader').removeClass('bg-danger text-white').addClass('bg-info text-white');
            $('#modalAtrasadoTitulo').html('<i class="fas fa-clock me-2"></i>Vehículo Llegó Temprano');
            $('#modalAtrasadoTituloPrincipal').text('Vehículo Llegó Antes de la Hora Asignada').removeClass('text-danger text-warning').addClass('text-info');
            $('#modalAtrasadoSubtitulo').text('El vehículo llegó antes del margen de tiempo permitido');
            $('#modalAtrasadoIcono').html('<i class="fas fa-clock fa-4x text-info"></i>');
            $('#modalAtrasadoAlerta').removeClass('alert-danger alert-warning alert-info').addClass('alert-info');
            $('#modalAtrasadoIconoAlerta').removeClass('text-danger text-warning').addClass('text-info');
            $('#modalAtrasadoMotivoTitulo').text('Motivo del Ingreso Anticipado');
            
            const mensajeTemprano = `El vehículo <strong>llegó antes de la hora asignada</strong>. Llegó ${diferenciaTexto ? `<strong>${diferenciaTexto}</strong> antes` : 'antes'} de la hora asignada (${this.formatearHora(agenda.HoraInicio)}). Para permitir el ingreso, por favor, ingrese un motivo.`;
            $('#mensajeAtrasado').html(mensajeTemprano);
            
            $('#modalAtrasadoInfoTexto').html(`
                <div class="alert alert-info mb-0">
                    <strong><i class="fas fa-info-circle me-2"></i>Ingreso Permitido con Motivo:</strong><br>
                    El vehículo llegó antes del margen de tiempo permitido (10 minutos antes), pero puede ingresar al taller.<br>
                    Por favor, ingrese el motivo del ingreso anticipado en el campo de abajo para completar el registro.
                </div>
            `);
            
            // Mostrar campo de motivo y botón de permitir ingreso
            $('#campoMotivoRetraso').show();
            $('#tituloMotivoRetraso').html('<i class="fas fa-edit me-2 text-info"></i>Motivo del Ingreso Anticipado (Obligatorio)');
            $('#labelMotivoRetraso').text('Describa el motivo por el cual el vehículo llegó antes de la hora asignada:');
            $('#motivoRetraso').attr('placeholder', 'Ejemplo: Llegada anticipada por disponibilidad, terminó ruta anterior antes de lo esperado, etc.');
            $('#btnPermitirIngresoConMotivo').show();
            $('#btnPermitirIngresoConMotivo').prop('disabled', true);
            $('#motivoRetraso').val('').removeClass('is-invalid');
            $('#btnCerrarModalAtrasado').text('Cancelar');
            
            // La validación se configurará cuando el modal esté completamente visible
            // (ver evento 'shown.bs.modal' más abajo)
        } else if (esNoLlego) {
            // No llegó - más de 30 minutos
            if (requiereMotivo) {
                // Permite ingreso con motivo
                $('#modalAtrasadoHeader').removeClass('bg-danger text-white').addClass('bg-warning text-dark');
                $('#modalAtrasadoTitulo').html('<i class="fas fa-clock me-2"></i>Vehículo Llegó Tarde');
                $('#modalAtrasadoTituloPrincipal').text('Vehículo Llegó con Retraso').removeClass('text-danger').addClass('text-warning');
                $('#modalAtrasadoSubtitulo').text('El vehículo llegó después del margen de tiempo permitido');
                $('#modalAtrasadoIcono').html('<i class="fas fa-exclamation-triangle fa-4x text-warning"></i>');
                $('#modalAtrasadoAlerta').removeClass('alert-danger alert-info').addClass('alert-warning');
                $('#modalAtrasadoIconoAlerta').removeClass('text-danger').addClass('text-warning');
                $('#modalAtrasadoMotivoTitulo').text('Información del Retraso');
                
                const mensajeNoLlego = `El vehículo <strong>llegó con retraso</strong>. Pasó más de 30 minutos de la hora asignada (${this.formatearHora(agenda.HoraInicio)}).${diferenciaTexto ? ` Llegó con un retraso de <strong>${diferenciaTexto}</strong> después de la hora asignada.` : ''} Para permitir el ingreso, debe ingresar un motivo del retraso.`;
                $('#mensajeAtrasado').html(mensajeNoLlego);
                
                $('#modalAtrasadoInfoTexto').html(`
                    <div class="alert alert-warning mb-0">
                        <strong><i class="fas fa-info-circle me-2"></i>Ingreso Permitido con Motivo:</strong><br>
                        El vehículo llegó fuera del margen de tiempo permitido, pero puede ingresar al taller.<br>
                        Por favor, ingrese el motivo del retraso en el campo de abajo para completar el registro.
                    </div>
                `);
                
                // Mostrar campo de motivo y botón de permitir ingreso
                $('#campoMotivoRetraso').show();
                $('#tituloMotivoRetraso').html('<i class="fas fa-edit me-2 text-warning"></i>Motivo del Retraso (Obligatorio)');
                $('#labelMotivoRetraso').text('Describa el motivo por el cual el vehículo llegó tarde:');
                $('#motivoRetraso').attr('placeholder', 'Ejemplo: Tráfico pesado en la autopista, problema mecánico en ruta, etc.');
                $('#btnPermitirIngresoConMotivo').show();
                $('#btnPermitirIngresoConMotivo').prop('disabled', true);
                $('#motivoRetraso').val('').removeClass('is-invalid');
                $('#btnCerrarModalAtrasado').text('Cancelar');
                
                // Remover eventos anteriores y agregar nuevo listener
                $('#motivoRetraso').off('input keyup paste change');
                
                // Habilitar/deshabilitar botón según el input del motivo
                const validarMotivoRetraso = () => {
                    const motivo = $('#motivoRetraso').val().trim();
                    console.log('Validando motivo retraso:', motivo, 'Longitud:', motivo.length);
                    if (motivo.length >= 10) {
                        $('#motivoRetraso').removeClass('is-invalid');
                        $('#btnPermitirIngresoConMotivo').prop('disabled', false);
                        console.log('Botón habilitado');
                    } else {
                        $('#motivoRetraso').addClass('is-invalid');
                        $('#btnPermitirIngresoConMotivo').prop('disabled', true);
                        console.log('Botón deshabilitado');
                    }
                };
                
                $('#motivoRetraso').on('input keyup paste change', validarMotivoRetraso);
                
                // Validar inmediatamente si hay texto
                setTimeout(() => {
                    validarMotivoRetraso();
                }, 100);
            } else {
                // Comportamiento antiguo (no debería llegar aquí, pero por seguridad)
                $('#modalAtrasadoHeader').removeClass('bg-warning text-dark').addClass('bg-danger text-white');
                $('#modalAtrasadoTitulo').html('<i class="fas fa-times-circle me-2"></i>Vehículo No Llegó');
                $('#modalAtrasadoTituloPrincipal').text('Vehículo No Llegó a Tiempo').removeClass('text-warning').addClass('text-danger');
                $('#modalAtrasadoSubtitulo').text('El vehículo no llegó dentro del margen de tiempo permitido');
                $('#modalAtrasadoIcono').html('<i class="fas fa-times-circle fa-4x text-danger"></i>');
                $('#modalAtrasadoAlerta').removeClass('alert-warning alert-info').addClass('alert-danger');
                $('#modalAtrasadoIconoAlerta').removeClass('text-info').addClass('text-danger');
                $('#modalAtrasadoMotivoTitulo').text('Motivo de Rechazo: No Llegó');
                
                const mensajeNoLlego = `El vehículo <strong>NO LLEGÓ</strong> a tiempo. Pasó más de 30 minutos de la hora asignada (${this.formatearHora(agenda.HoraInicio)}).${diferenciaTexto ? ` Llegó con un retraso de <strong>${diferenciaTexto}</strong> después de la hora asignada.` : ''} El margen máximo de atraso permitido es de 30 minutos.`;
                $('#mensajeAtrasado').html(mensajeNoLlego);
                
                $('#modalAtrasadoInfoTexto').html(`
                    <div class="alert alert-danger mb-0">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i>No se puede confirmar el ingreso:</strong><br>
                        El vehículo no llegó a la hora asignada y ha pasado más de 30 minutos del tiempo permitido.<br>
                        La solicitud ha sido marcada automáticamente como <strong>"No llegó"</strong> y el proceso ha sido cerrado.<br><br>
                        <strong>El conductor deberá crear una nueva solicitud de agendamiento.</strong>
                    </div>
                `);
                
                // Ocultar campo de motivo y botón
                $('#campoMotivoRetraso').hide();
                $('#btnPermitirIngresoConMotivo').hide();
            }
        } else if (tipoAtraso === 'fecha') {
            // Fecha pasada
            $('#modalAtrasadoHeader').removeClass('bg-danger text-white').addClass('bg-warning text-dark');
            $('#modalAtrasadoTitulo').html('<i class="fas fa-calendar-times me-2"></i>Vehículo Atrasado');
            $('#modalAtrasadoTituloPrincipal').text('Vehículo Llegó en Fecha Incorrecta').addClass('text-warning');
            $('#modalAtrasadoSubtitulo').text('El vehículo llegó en una fecha diferente a la asignada');
            $('#modalAtrasadoIcono').html('<i class="fas fa-calendar-times fa-4x text-warning"></i>');
            $('#modalAtrasadoAlerta').removeClass('alert-danger alert-info').addClass('alert-warning');
            $('#modalAtrasadoIconoAlerta').removeClass('text-danger').addClass('text-warning');
            $('#modalAtrasadoMotivoTitulo').text('Motivo de Rechazo: Fecha Incorrecta');
            
            const mensajeFecha = `Este vehículo tenía una cita agendada para el día <strong>${this.formatearFecha(agenda.FechaAgenda)}</strong>, pero llegó en una fecha posterior. El vehículo <strong>llegó fuera del margen de tiempo permitido</strong> para esa cita.`;
            $('#mensajeAtrasado').html(mensajeFecha);
            
            $('#modalAtrasadoInfoTexto').html(`
                La solicitud ha sido marcada automáticamente como <strong class="text-warning">"Atrasada"</strong> y el proceso ha sido cancelado.
                El conductor debe crear una nueva solicitud de agendamiento.
            `);
        } else {
            // Atrasado dentro de 30 minutos
            $('#modalAtrasadoHeader').removeClass('bg-danger text-white').addClass('bg-warning text-dark');
            $('#modalAtrasadoTitulo').html('<i class="fas fa-clock me-2"></i>Vehículo Atrasado');
            $('#modalAtrasadoTituloPrincipal').text('Vehículo Llegó Fuera del Margen').addClass('text-warning');
            $('#modalAtrasadoSubtitulo').text('El vehículo llegó después de la hora asignada');
            $('#modalAtrasadoIcono').html('<i class="fas fa-exclamation-triangle fa-4x text-warning"></i>');
            $('#modalAtrasadoAlerta').removeClass('alert-danger alert-info').addClass('alert-warning');
            $('#modalAtrasadoIconoAlerta').removeClass('text-danger').addClass('text-warning');
            $('#modalAtrasadoMotivoTitulo').text('Motivo de Rechazo: Llegó Fuera del Margen');
            
            const mensajeAtraso = `Este vehículo <strong>llegó fuera del margen de tiempo permitido</strong>. Llegó ${diferenciaTexto ? `con un retraso de <strong>${diferenciaTexto}</strong>` : 'después'} de la hora asignada (${this.formatearHora(agenda.HoraInicio)}). El margen de atraso permitido es de 30 minutos.`;
            $('#mensajeAtrasado').html(mensajeAtraso);
            
            $('#modalAtrasadoInfoTexto').html(`
                La solicitud ha sido marcada automáticamente como <strong class="text-warning">"Atrasada"</strong> y el proceso ha sido cancelado.
                El conductor debe crear una nueva solicitud de agendamiento.
            `);
        }
        
        // Si no requiere motivo, ocultar campo y botón
        if (!requiereMotivo) {
            $('#campoMotivoRetraso').hide();
            $('#btnPermitirIngresoConMotivo').hide();
        }
        
        // Guardar referencia a los datos para usar en el botón
        this.datosAtrasado = data;
        
        // Limpiar campos de motivo al mostrar el modal
        $('#motivoRetraso').val('').removeClass('is-invalid');
        $('#inputMotivoRetraso').val('').removeClass('is-invalid');
        
        // Agregar evento para limpiar cuando se cierra el modal
        const modalElement = document.getElementById('modalAtrasado');
        if (modalElement) {
            // Remover listeners anteriores para evitar duplicados
            $(modalElement).off('hidden.bs.modal shown.bs.modal');
            
            // Agregar listener para cuando el modal se muestra completamente
            $(modalElement).on('shown.bs.modal', () => {
                // Re-vincular eventos de validación después de que el modal esté visible
                const campoMotivo = $('#motivoRetraso');
                const btnPermitir = $('#btnPermitirIngresoConMotivo');
                
                if (campoMotivo.length === 0) {
                    console.error('Campo motivoRetraso no encontrado después de mostrar modal');
                    return;
                }
                
                if (btnPermitir.length === 0) {
                    console.error('Botón btnPermitirIngresoConMotivo no encontrado');
                    return;
                }
                
                // Remover eventos anteriores
                campoMotivo.off('input keyup paste change blur');
                
                // Función de validación
                const validarMotivoModal = function() {
                    const motivo = $(this).val().trim();
                    const longitud = motivo.length;
                    console.log('Validación después de mostrar modal - Motivo:', motivo, 'Longitud:', longitud);
                    
                    if (longitud >= 10) {
                        $(this).removeClass('is-invalid');
                        btnPermitir.prop('disabled', false);
                        console.log('Botón habilitado después de validación - Longitud:', longitud);
                    } else {
                        $(this).addClass('is-invalid');
                        btnPermitir.prop('disabled', true);
                        console.log('Botón deshabilitado - motivo muy corto. Longitud:', longitud);
                    }
                };
                
                // Vincular eventos usando jQuery
                campoMotivo.on('input keyup paste change blur', validarMotivoModal);
                
                // También usar evento nativo como respaldo (más confiable)
                const elementoNativo = campoMotivo[0];
                if (elementoNativo) {
                    // Remover listeners nativos anteriores si existen
                    const nuevoListener = function() {
                        const motivo = this.value.trim();
                        console.log('Evento nativo - Validando motivo:', motivo, 'Longitud:', motivo.length);
                        
                        if (motivo.length >= 10) {
                            $(this).removeClass('is-invalid');
                            btnPermitir.prop('disabled', false);
                            console.log('Botón habilitado (evento nativo)');
                        } else {
                            $(this).addClass('is-invalid');
                            btnPermitir.prop('disabled', true);
                            console.log('Botón deshabilitado (evento nativo)');
                        }
                    };
                    
                    elementoNativo.addEventListener('input', nuevoListener, { passive: true });
                    elementoNativo.addEventListener('keyup', nuevoListener, { passive: true });
                    elementoNativo.addEventListener('paste', function() {
                        setTimeout(nuevoListener, 10);
                    }, { passive: true });
                }
                
                // Validar inmediatamente después de que el modal esté visible
                setTimeout(() => {
                    if (campoMotivo.length > 0) {
                        validarMotivoModal.call(campoMotivo[0]);
                    }
                }, 250);
            });
            
            // Agregar listener para limpiar cuando se cierra
            $(modalElement).on('hidden.bs.modal', () => {
                $('#motivoRetraso').val('').removeClass('is-invalid');
                $('#inputMotivoRetraso').val('').removeClass('is-invalid');
                $('#campoMotivoRetraso').hide();
                $('#btnPermitirIngresoConMotivo').hide();
            });
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }
    
    permitirIngresoConMotivo() {
        const motivo = $('#motivoRetraso').val().trim();
        const esTemprano = this.vehiculoActual && this.vehiculoActual.es_temprano;
        const tipoMensaje = esTemprano ? 'del ingreso anticipado' : 'del retraso';
        
        if (!motivo) {
            this.mostrarAlerta(`Debe ingresar un motivo ${tipoMensaje} para continuar`, 'warning');
            $('#motivoRetraso').focus();
            return;
        }
        
        if (motivo.length < 10) {
            this.mostrarAlerta('El motivo debe tener al menos 10 caracteres', 'warning');
            $('#motivoRetraso').focus();
            return;
        }
        
        // Cerrar el modal
        const modalElement = document.getElementById('modalAtrasado');
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
        
        // Guardar el motivo en el vehículo actual
        if (this.vehiculoActual) {
            this.vehiculoActual.motivo_retraso = motivo;
            this.vehiculoActual.puede_ingresar = true; // Asegurar que puede ingresar
        }
        
        // Proceder con el ingreso
        this.registrarIngresoBasicoConMotivo(motivo);
    }
    
    registrarIngresoBasicoConMotivo(motivo) {
        const placa = this.vehiculoActual.vehiculo ? this.vehiculoActual.vehiculo.Placa : this.vehiculoActual.Placa;
        
        if (!placa) {
            this.mostrarAlerta('No se pudo obtener la placa del vehículo', 'error');
            return;
        }
        
        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: {
                action: 'registrarIngresoBasico',
                placa: placa,
                motivo_retraso: motivo
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    // Guardar fotos si hay (opcionales)
                    if (this.fotosCapturadas.length > 0) {
                        this.guardarTodasLasFotos(placa);
                    }
                    this.mostrarModalExito('Ingreso registrado correctamente', 'El vehículo ha sido ingresado al taller con motivo de retraso registrado.');
                    this.limpiarInterfaz();
                    this.cargarEstadisticas();
                    this.cargarNovedades(); // Actualizar movimientos
                } else {
                    this.mostrarAlerta(response.message || 'Error al registrar ingreso', 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error en registrarIngresoBasicoConMotivo:', error, xhr.responseText);
                this.mostrarAlerta('Error al registrar ingreso: ' + error, 'error');
            }
        });
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

        // Insertar al inicio del contenido
        $('.pc-content').prepend(alertHtml);

        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }

    mostrarModalExito(titulo, mensaje) {
        $('#modalExitoTitulo').text(titulo);
        $('#modalExitoMensaje').text(mensaje);
        const modal = new bootstrap.Modal(document.getElementById('modalExito'));
        modal.show();
    }
}

// Inicializar la aplicación cuando el documento esté listo
$(document).ready(function() {
    window.controlIngresoApp = new ControlIngresoApp();
});