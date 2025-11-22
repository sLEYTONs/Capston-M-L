$(document).ready(function() {
    var baseUrl = '../app/model/ingreso_vehiculos/scripts/s_ingreso_vehiculos.php';
    
    // Validación de patente chilena
    function validarPatenteChilena(patente) {
        patente = patente.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
        
        // Solo consonantes permitidas: B, C, D, F, G, H, J, K, L, M, N, P, R, S, T, V, W, X, Y, Z
        const consonantesPermitidas = /^[BCDFGHJKLMNPRSTVWXYZ]+$/;
        
        // Formato: 4 letras + 2 números o 2 letras + 4 números
        const patrones = [
            /^[BCDFGHJKLMNPRSTVWXYZ]{4}\d{2}$/,  // ABCD12
            /^[BCDFGHJKLMNPRSTVWXYZ]{2}\d{4}$/   // AB1234
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

    // Funciones de normalización
    function normalizarTexto(texto) {
        return texto.trim().replace(/\s+/g, ' ');
    }

    function capitalizarTexto(texto) {
        return texto.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }

    function normalizarPlaca(placa) {
        return placa.toUpperCase().replace(/\s/g, '').replace(/[^BCDFGHJKLMNPRSTVWXYZ0-9]/g, '');
    }

    // Validación en tiempo real de la placa
    $('#placa').on('input', function() {
        let valor = $(this).val().toUpperCase();
        // Filtrar solo consonantes permitidas y números
        valor = valor.replace(/[^BCDFGHJKLMNPRSTVWXYZ0-9]/g, '');
        $(this).val(valor);
    });

    // Validación en tiempo real
    $('#form-ingreso-vehiculo input[required], #form-ingreso-vehiculo select[required]').on('blur', function() {
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

    // EVENTO PRINCIPAL DEL FORMULARIO - REGISTRAR NUEVO VEHÍCULO
    $('#form-ingreso-vehiculo').on('submit', function(e) {
        e.preventDefault();
        
        // Validar formulario
        if (!this.checkValidity()) {
            $(this).addClass('was-validated');
            const firstInvalid = $(this).find(':invalid').first();
            if (firstInvalid.length) {
                firstInvalid.focus();
            }
            mostrarError('Formulario incompleto', 'Complete todos los campos obligatorios');
            return;
        }

        // Validar patente
        const placa = normalizarPlaca($('#placa').val());
        const validacionPatente = validarPatenteChilena(placa);
        
        if (!validacionPatente.valida) {
            $('#placa').addClass('is-invalid');
            mostrarError('Patente inválida', validacionPatente.mensaje);
            return;
        }

        // Validar nombre del conductor
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

        // Preparar datos (solo campos mínimos)
        const datos = {
            action: 'registrar_vehiculo',
            placa: normalizarPlaca($('#placa').val()),
            tipo_vehiculo: $('#tipo_vehiculo').val(),
            marca: normalizarTexto($('#marca').val()),
            modelo: normalizarTexto($('#modelo').val()),
            color: normalizarTexto($('#color').val()),
            anio: $('#anio').val() || null,
            conductor_nombre: capitalizarTexto(normalizarTexto($('#conductor_nombre').val()))
        };

        // Enviar datos via AJAX
        $.ajax({
            url: baseUrl,
            type: 'POST',
            data: datos,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    mostrarModalExito(response.vehiculo_id, response.message);
                    limpiarFormulario();
                } else {
                    mostrarError('Error al registrar', response.message || 'Error desconocido');
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Error de conexión con el servidor';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                mostrarError('Error de conexión', errorMsg);
            },
            complete: function() {
                $('#btn-registrar').prop('disabled', false).html('<i class="fas fa-save"></i> Registrar Vehículo');
            }
        });
    }

    function limpiarFormulario() {
        $('#form-ingreso-vehiculo')[0].reset();
        $('#form-ingreso-vehiculo').removeClass('was-validated');
        $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
    }

    $('#btn-limpiar').click(function() {
        limpiarFormulario();
    });

    // Función para mostrar errores
    function mostrarError(titulo, mensaje) {
        Swal.fire({
            icon: 'error',
            title: titulo,
            text: mensaje,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc3545'
        });
    }

    // Función para mostrar modal de éxito
    function mostrarModalExito(vehiculoId, mensaje) {
        $('#mensaje-exito').text(mensaje || 'El vehículo ha sido registrado correctamente en el sistema.');
        $('#fecha-registro').text(new Date().toLocaleString('es-CL'));
        $('#modal-exito').modal('show');
        
        // Botón para registrar otro vehículo
        $('.btn-another').off('click').on('click', function() {
            $('#modal-exito').modal('hide');
            limpiarFormulario();
        });
    }
});
