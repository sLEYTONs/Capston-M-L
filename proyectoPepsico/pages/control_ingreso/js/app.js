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
        
        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: {
                action: 'verificarEstado',
                placa: placa
            },
            dataType: 'json',
            success: (response) => {
                $('.guardia-card').removeClass('search-loading');
                
                if (response.success) {
                    this.vehiculoActual = response.data;
                    this.mostrarInformacionBasica(response.data);
                } else {
                    if (this.tipoOperacion === 'ingreso') {
                        // Para ingreso, no existe el vehículo - está bien
                        this.vehiculoActual = { Placa: placa };
                        this.mostrarPreparadoIngreso(placa);
                    } else {
                        // Para salida, el vehículo debe existir
                        this.mostrarAlerta(response.message, 'error');
                        this.ocultarInformacionVehiculo();
                    }
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
        $('#infoEmpresa').text('PENDIENTE');
        
        $('#sinResultados').hide();
        $('#conResultados').show().addClass('fade-in');
        
        this.actualizarEstadoBotones();
    }

    mostrarInformacionBasica(vehiculo) {
        this.vehiculoActual = vehiculo;
        
        $('#infoPlaca').text(vehiculo.Placa);
        $('#infoEstado').html('<span class="badge bg-success">En Patio</span>');
        $('#infoFechaIngreso').text(this.formatearFecha(vehiculo.FechaIngreso));
        $('#infoConductorNombre').text(vehiculo.ConductorNombre || 'Por completar');
        $('#infoEmpresa').text(vehiculo.EmpresaNombre || 'PENDIENTE');
        
        $('#sinResultados').hide();
        $('#conResultados').show().addClass('fade-in');
        
        this.actualizarEstadoBotones();
    }

    actualizarEstadoBotones() {
        const tieneVehiculo = this.vehiculoActual !== null;
        const tieneFotos = this.fotosCapturadas.length > 0;
        
        // Mínimo 2 fotos requeridas
        const fotosSuficientes = this.fotosCapturadas.length >= 2;
        
        $('#btnProcesarIngreso').prop('disabled', !tieneVehiculo || !fotosSuficientes);
        $('#btnProcesarSalida').prop('disabled', !tieneVehiculo || !fotosSuficientes);
        $('#btnReportarNovedad').prop('disabled', !tieneVehiculo);
    }

    procesarIngreso() {
        if (!this.vehiculoActual || this.fotosCapturadas.length < 2) {
            this.mostrarAlerta('Capture al menos 2 fotos del vehículo antes de registrar el ingreso', 'warning');
            return;
        }

        this.registrarIngresoBasico();
    }

    registrarIngresoBasico() {
        const placa = this.vehiculoActual.Placa;
        
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
                    this.guardarTodasLasFotos(placa);
                    this.mostrarAlerta('✅ Ingreso registrado correctamente. El conductor completará la información restante.', 'success');
                    this.limpiarInterfaz();
                    this.cargarEstadisticas();
                } else {
                    this.mostrarAlerta(response.message, 'error');
                }
            },
            error: () => {
                this.mostrarAlerta('Error al registrar ingreso', 'error');
            }
        });
    }

    procesarSalida() {
        if (!this.vehiculoActual || this.fotosCapturadas.length < 2) {
            this.mostrarAlerta('Capture al menos 2 fotos del vehículo antes de registrar la salida', 'warning');
            return;
        }

        this.registrarSalida();
    }

    registrarSalida() {
        const placa = this.vehiculoActual.Placa;
        
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
                    this.mostrarAlerta('✅ Salida registrada correctamente', 'success');
                    this.limpiarInterfaz();
                    this.cargarEstadisticas();
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
        
        this.mostrarAlerta(`✅ Foto ${tipo.replace('-', ' ')} capturada correctamente`, 'success');
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
                placa: this.vehiculoActual.Placa,
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
        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: { action: 'obtenerNovedades' },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.mostrarNovedades(response.data);
                }
            }
        });
    }

    mostrarNovedades(novedades) {
        const container = $('#listaNovedades');
        
        if (novedades.length === 0) {
            container.html('<div class="text-center text-muted py-4"><i class="fas fa-info-circle me-2"></i>No hay novedades recientes</div>');
            return;
        }

        let html = '';
        novedades.forEach(novedad => {
            html += `
                <div class="novedad-item ${novedad.gravedad.toLowerCase()}">
                    <div class="d-flex justify-content-between">
                        <span class="novedad-tipo">${novedad.tipo}</span>
                        <small class="novedad-fecha">${this.formatearFecha(novedad.fecha)}</small>
                    </div>
                    <div class="novedad-descripcion">${novedad.descripcion}</div>
                    <small><strong>Vehículo:</strong> ${novedad.placa}</small>
                </div>
            `;
        });

        container.html(html);
    }

    formatearFecha(fecha) {
        return new Date(fecha).toLocaleString('es-ES');
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
}

// Inicializar la aplicación cuando el documento esté listo
$(document).ready(function() {
    window.controlIngresoApp = new ControlIngresoApp();
});