// GLOBAL FUNCTION FOR AUTOFILL BUTTON - NO DEPENDENCIES
function autoFillVehiculo() {
    console.log('autoFillVehiculo called!');

    const marcas = ['Toyota', 'Mercedes-Benz', 'Volvo', 'Scania', 'Isuzu', 'Hino', 'Freightliner'];
    const modelos = {
        'Toyota': ['Hilux', 'Dyna', 'Coaster'],
        'Mercedes-Benz': ['Actros', 'Atego', 'Sprinter'],
        'Volvo': ['FH', 'FM', 'FL'],
        'Scania': ['R450', 'P320', 'G410'],
        'Isuzu': ['NQR', 'NPR', 'FTR'],
        'Hino': ['300', '500', '700'],
        'Freightliner': ['Cascadia', 'M2', 'Coronado']
    };
    const tipos = ['Camión', 'Furgoneta', 'Camioneta', 'Tracto Camión'];

    const consonantes = 'BCDFGHJKLMNPRSTVWXYZ';
    let placa = '';
    for (let i = 0; i < 4; i++) {
        placa += consonantes.charAt(Math.floor(Math.random() * consonantes.length));
    }
    placa += Math.floor(Math.random() * 90 + 10);

    const marca = marcas[Math.floor(Math.random() * marcas.length)];
    const modelosDisponibles = modelos[marca];
    const modelo = modelosDisponibles[Math.floor(Math.random() * modelosDisponibles.length)];
    const anio = Math.floor(Math.random() * 10) + 2015;
    const kilometraje = Math.floor(Math.random() * 240000) + 10000;
    const tipo = tipos[Math.floor(Math.random() * tipos.length)];

    console.log('Filling fields with:', { placa, tipo, marca, modelo, anio, kilometraje });

    // Fill form fields using pure JavaScript
    var placaInput = document.getElementById('placa');
    var tipoInput = document.getElementById('tipo_vehiculo');
    var marcaInput = document.getElementById('marca');
    var modeloInput = document.getElementById('modelo');
    var anioInput = document.getElementById('anio');
    var kilometrajeInput = document.getElementById('kilometraje');
    var conductorInput = document.getElementById('conductor_nombre');

    if (placaInput) placaInput.value = placa;
    if (tipoInput) tipoInput.value = tipo;
    if (marcaInput) marcaInput.value = marca;
    if (modeloInput) modeloInput.value = modelo;
    if (anioInput) anioInput.value = anio;
    if (kilometrajeInput) kilometrajeInput.value = kilometraje;

    console.log('Fields filled successfully');

    // Try to fetch available drivers if jQuery is available
    if (typeof $ !== 'undefined' && $.ajax) {
        $.ajax({
            url: '../app/model/gestion_usuarios/scripts/s_gestionusuarios.php',
            type: 'POST',
            data: { action: 'obtener_choferes_disponibles' },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.choferes && response.choferes.length > 0) {
                    const chofer = response.choferes[Math.floor(Math.random() * response.choferes.length)];
                    if (conductorInput) conductorInput.value = chofer.NombreUsuario;

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Formulario Completado!',
                            text: 'Vehículo: ' + placa + ' - Conductor: ' + chofer.NombreUsuario,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert('Formulario completado: ' + placa + ' - ' + chofer.NombreUsuario);
                    }
                } else {
                    const nombres = ['Juan Pérez', 'Carlos González', 'Pedro Rodríguez', 'Luis Martínez'];
                    if (conductorInput) conductorInput.value = nombres[Math.floor(Math.random() * nombres.length)];

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Formulario Completado!',
                            text: 'Vehículo: ' + placa,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert('Formulario completado: ' + placa);
                    }
                }
            },
            error: function () {
                const nombres = ['Juan Pérez', 'Carlos González', 'Pedro Rodríguez', 'Luis Martínez'];
                if (conductorInput) conductorInput.value = nombres[Math.floor(Math.random() * nombres.length)];

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Formulario Completado!',
                        text: 'Vehículo: ' + placa,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert('Formulario completado: ' + placa);
                }
            }
        });
    } else {
        // Fallback if jQuery is not available
        const nombres = ['Juan Pérez', 'Carlos González', 'Pedro Rodríguez', 'Luis Martínez'];
        if (conductorInput) conductorInput.value = nombres[Math.floor(Math.random() * nombres.length)];
        alert('Formulario completado: ' + placa);
    }
}

$(document).ready(function () {
    var baseUrl = '../app/model/ingreso_vehiculos/scripts/s_ingreso_vehiculos.php';

    function validarPatenteChilena(patente) {
        patente = patente.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');

        const patrones = [
            /^[BCDFGHJKLMNPRSTVWXYZ]{4}\d{2}$/,
            /^[BCDFGHJKLMNPRSTVWXYZ]{2}\d{4}$/
        ];

        const valida = patrones.some(patron => patron.test(patente));

        if (!valida) {
            return {
                valida: false,
                mensaje: 'Formato de patente inválido. Debe ser 4 letras + 2 números o 2 letras + 4 números, usando solo consonantes permitidas.',
                patenteNormalizada: patente
            };
        }

        return {
            valida: true,
            mensaje: 'Patente válida',
            patenteNormalizada: patente
        };
    }

    function normalizarTexto(texto) {
        return texto.trim().replace(/\s+/g, ' ');
    }

    function capitalizarTexto(texto) {
        return texto.replace(/\b\w/g, function (l) { return l.toUpperCase(); });
    }

    function normalizarPlaca(placa) {
        return placa.toUpperCase().replace(/\s/g, '').replace(/[^BCDFGHJKLMNPRSTVWXYZ0-9]/g, '');
    }

    $('#placa').on('input', function () {
        let valor = $(this).val().toUpperCase();
        valor = valor.replace(/[^BCDFGHJKLMNPRSTVWXYZ0-9]/g, '');
        $(this).val(valor);
    });

    $('#form-ingreso-vehiculo input[required], #form-ingreso-vehiculo select[required]').on('blur', function () {
        const $field = $(this);
        const value = $field.val().trim();

        if ($field.prop('required') && value === '') {
            $field.addClass('is-invalid');
            $field.removeClass('is-valid');
        } else {
            $field.removeClass('is-invalid');
            $field.addClass('is-valid');
        }
    });

    $('#form-ingreso-vehiculo').on('submit', function (e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            $(this).addClass('was-validated');
            const firstInvalid = $(this).find(':invalid').first();
            if (firstInvalid.length) {
                firstInvalid.focus();
            }
            mostrarError('Formulario incompleto', 'Complete todos los campos obligatorios');
            return;
        }

        const placa = normalizarPlaca($('#placa').val());
        const validacionPatente = validarPatenteChilena(placa);

        if (!validacionPatente.valida) {
            $('#placa').addClass('is-invalid');
            mostrarError('Patente inválida', validacionPatente.mensaje);
            return;
        }

        const nombreConductor = $('#conductor_nombre').val().trim();
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(nombreConductor)) {
            $('#conductor_nombre').addClass('is-invalid');
            mostrarError('Nombre inválido', 'El nombre del conductor solo puede contener letras y espacios');
            return;
        }

        registrarVehiculo();
    });

    function registrarVehiculo() {
        $('#btn-registrar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Registrando...');

        const datos = {
            action: 'registrar_vehiculo',
            placa: normalizarPlaca($('#placa').val()),
            tipo_vehiculo: $('#tipo_vehiculo').val(),
            marca: normalizarTexto($('#marca').val()),
            modelo: normalizarTexto($('#modelo').val()),
            anio: $('#anio').val() || null,
            conductor_nombre: capitalizarTexto(normalizarTexto($('#conductor_nombre').val())),
            kilometraje: $('#kilometraje').val() || null
        };

        $.ajax({
            url: baseUrl,
            type: 'POST',
            data: datos,
            dataType: 'json',
            timeout: 30000,
            success: function (response) {
                if (response.success) {
                    mostrarModalExito(response.vehiculo_id, response.message);
                    limpiarFormulario();
                } else {
                    mostrarError('Error al registrar', response.message || 'Error desconocido');
                }
            },
            error: function (xhr, status, error) {
                let errorMsg = 'Error de conexión con el servidor';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                mostrarError('Error de conexión', errorMsg);
            },
            complete: function () {
                $('#btn-registrar').prop('disabled', false).html('<i class="fas fa-save"></i> Registrar Vehículo');
            }
        });
    }

    function limpiarFormulario() {
        $('#form-ingreso-vehiculo')[0].reset();
        $('#form-ingreso-vehiculo').removeClass('was-validated');
        $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
    }

    $('#btn-limpiar').click(function () {
        limpiarFormulario();
    });

    function mostrarError(titulo, mensaje) {
        Swal.fire({
            icon: 'error',
            title: titulo,
            text: mensaje,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc3545'
        });
    }

    function mostrarModalExito(vehiculoId, mensaje) {
        $('#mensaje-exito').text(mensaje || 'El vehículo ha sido registrado correctamente en el sistema.');
        $('#fecha-registro').text(new Date().toLocaleString('es-CL'));
        $('#modal-exito').modal('show');

        $('.btn-another').off('click').on('click', function () {
            $('#modal-exito').modal('hide');
            limpiarFormulario();
        });
    }
});
