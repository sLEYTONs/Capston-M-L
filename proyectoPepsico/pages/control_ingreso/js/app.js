class ControlIngresoApp {
    constructor() {
        this.vehiculoActual = null;
        this.stream = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.cargarEstadisticas();
        this.cargarNovedades();
    }

    bindEvents() {
        // Búsquedas
        $('#btnBuscarPlaca').click(() => this.buscarPorPlaca());
        $('#placaBusqueda').keypress((e) => {
            if (e.which === 13) this.buscarPorPlaca();
        });

        $('#btnBuscarCedula').click(() => this.buscarPorCedula());
        $('#cedulaBusqueda').keypress((e) => {
            if (e.which === 13) this.buscarPorCedula();
        });

        // Acciones
        $('#btnRegistrarIngreso').click(() => this.registrarIngreso());
        $('#btnRegistrarSalida').click(() => this.registrarSalida());
        $('#btnTomarFoto').click(() => this.iniciarCamara());
        $('#btnReportarNovedad').click(() => this.mostrarModalNovedad());

        // Modal Foto
        $('#btnCapturar').click(() => this.capturarFoto());
        $('#btnGuardarFoto').click(() => this.guardarFoto());

        // Modal Novedad
        $('#btnGuardarNovedad').click(() => this.guardarNovedad());
    }

    buscarPorPlaca() {
        const placa = $('#placaBusqueda').val().trim().toUpperCase();
        if (!placa) {
            this.mostrarAlerta('Ingrese una placa para buscar', 'warning');
            return;
        }

        this.realizarBusqueda('placa', placa);
    }

    buscarPorCedula() {
        const cedula = $('#cedulaBusqueda').val().trim();
        if (!cedula) {
            this.mostrarAlerta('Ingrese una cédula para buscar', 'warning');
            return;
        }

        this.realizarBusqueda('cedula', cedula);
    }

    realizarBusqueda(tipo, valor) {
        $('.guardia-card').addClass('search-loading');
        
        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: {
                action: 'buscarVehiculo',
                tipo: tipo,
                valor: valor
            },
            dataType: 'json',
            success: (response) => {
                $('.guardia-card').removeClass('search-loading');
                
                if (response.success) {
                    this.mostrarInformacionVehiculo(response.data);
                } else {
                    this.mostrarAlerta(response.message, 'error');
                    this.ocultarInformacionVehiculo();
                }
            },
            error: () => {
                $('.guardia-card').removeClass('search-loading');
                this.mostrarAlerta('Error en la búsqueda', 'error');
            }
        });
    }

    mostrarInformacionVehiculo(vehiculo) {
        this.vehiculoActual = vehiculo;
        
        // Información básica
        $('#infoPlaca').text(vehiculo.Placa);
        $('#infoTipo').text(vehiculo.TipoVehiculo);
        $('#infoMarcaModelo').text(`${vehiculo.Marca} ${vehiculo.Modelo}`);
        $('#infoColor').text(vehiculo.Color);
        $('#infoAnio').text(vehiculo.Anio || 'N/A');

        // Estado
        const estadoClase = this.obtenerClaseEstado(vehiculo.EstadoIngreso);
        $('#badgeEstadoIngreso').html(`<span class="badge ${estadoClase}">${vehiculo.EstadoIngreso}</span>`);
        $('#infoCombustible').text(vehiculo.Combustible);
        $('#infoKilometraje').text(vehiculo.Kilometraje ? `${vehiculo.Kilometraje} km` : 'N/A');
        $('#infoFechaIngreso').text(this.formatearFecha(vehiculo.FechaIngreso));

        // Conductor
        $('#infoConductorNombre').text(vehiculo.ConductorNombre);
        $('#infoConductorCedula').text(vehiculo.ConductorCedula);
        $('#infoConductorTelefono').text(vehiculo.ConductorTelefono || 'N/A');
        $('#infoLicencia').text(vehiculo.Licencia || 'N/A');
        $('#infoEmpresa').text(`${vehiculo.EmpresaNombre} (${vehiculo.EmpresaCodigo})`);

        // Propósito
        $('#infoProposito').text(vehiculo.Proposito);
        $('#infoArea').text(vehiculo.Area);
        $('#infoObservaciones').text(vehiculo.Observaciones || 'Sin observaciones');

        // Mostrar panel de resultados
        $('#sinResultados').hide();
        $('#conResultados').show().addClass('fade-in');
    }

    ocultarInformacionVehiculo() {
        $('#sinResultados').show();
        $('#conResultados').hide();
        this.vehiculoActual = null;
    }

    obtenerClaseEstado(estado) {
        const clases = {
            'Bueno': 'badge-estado-bueno',
            'Regular': 'badge-estado-regular',
            'Malo': 'badge-estado-malo',
            'Accidentado': 'badge-estado-accidentado'
        };
        return clases[estado] || 'badge-secondary';
    }

    formatearFecha(fecha) {
        return new Date(fecha).toLocaleString('es-ES');
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
            container.html('<div class="text-center text-muted py-3"><i class="fas fa-info-circle me-2"></i>No hay novedades recientes</div>');
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

    registrarIngreso() {
        if (!this.vehiculoActual) {
            this.mostrarAlerta('No hay vehículo seleccionado', 'warning');
            return;
        }

        // Lógica para registrar ingreso
        this.mostrarAlerta('Ingreso registrado correctamente', 'success');
    }

    registrarSalida() {
        if (!this.vehiculoActual) {
            this.mostrarAlerta('No hay vehículo seleccionado', 'warning');
            return;
        }

        // Lógica para registrar salida
        this.mostrarAlerta('Salida registrada correctamente', 'success');
    }

    iniciarCamara() {
        if (!this.vehiculoActual) {
            this.mostrarAlerta('No hay vehículo seleccionado', 'warning');
            return;
        }

        $('#modalFoto').modal('show');
        this.iniciarVideo();
    }

    async iniciarVideo() {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ 
                video: { width: 1280, height: 720 } 
            });
            const video = document.getElementById('video');
            video.srcObject = this.stream;
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
        $('#fotoPreview').html(`<img src="${fotoData}" class="foto-preview" alt="Foto capturada">`);
        
        $('#btnCapturar').hide();
        $('#btnGuardarFoto').show().data('foto', fotoData);

        // Detener la cámara
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
        }
    }

    guardarFoto() {
        const fotoData = $('#btnGuardarFoto').data('foto');
        
        $.ajax({
            url: '../app/model/control_ingreso/scripts/s_control_ingreso.php',
            type: 'POST',
            data: {
                action: 'guardarFoto',
                placa: this.vehiculoActual.Placa,
                foto: fotoData
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.mostrarAlerta('Foto guardada correctamente', 'success');
                    $('#modalFoto').modal('hide');
                } else {
                    this.mostrarAlerta('Error al guardar la foto', 'error');
                }
            }
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