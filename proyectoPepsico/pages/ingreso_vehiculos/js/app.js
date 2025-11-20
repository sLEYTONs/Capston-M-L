$(document).ready(function() {
    var baseUrl = '../app/model/ingreso_vehiculos/scripts/s_ingreso_vehiculos.php';
    
    // Variables para almacenar archivos
    var documentosSubidos = [];
    var vehiculoEncontrado = null;

    // Configuración de Bootstrap File Input
    $('.custom-file-input').on('change', function() {
        let fileName = 'Seleccionar archivos...';
        const files = this.files;
        
        if (files && files.length > 0) {
            if (files.length === 1) {
                fileName = files[0].name;
            } else {
                fileName = `${files.length} archivos seleccionados`;
            }
        }
        
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
        mostrarArchivosSeleccionados(this);
    });

    // Mostrar archivos seleccionados
    function mostrarArchivosSeleccionados(input) {
        if (!input) return;
        
        const files = input.files;
        const listaId = input.id === 'documentos' ? 'lista-documentos' : 'lista-fotos';
        const lista = document.getElementById(listaId);
        
        if (!lista) return;
        
        lista.innerHTML = '';
        
        if (files && files.length > 0) {
            const ul = document.createElement('ul');
            ul.className = 'list-group list-group-flush';
            
            Array.from(files).forEach((file, index) => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.innerHTML = `
                    <span>${file.name} (${formatFileSize(file.size)})</span>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerArchivo(${index}, '${input.id}')">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                ul.appendChild(li);
            });
            
            lista.appendChild(ul);
        }
    }

    // Función global para remover archivos
    window.removerArchivo = function(index, tipo) {
        const input = document.getElementById(tipo);
        
        if (!input) return;
        
        const files = Array.from(input.files);
        files.splice(index, 1);
        
        const dt = new DataTransfer();
        files.forEach(file => dt.items.add(file));
        input.files = dt.files;
        
        mostrarArchivosSeleccionados(input);
        
        const fileName = files.length > 0 ? `${files.length} archivo(s) seleccionado(s)` : 'Seleccionar archivos...';
        $(input).next('.custom-file-label').addClass("selected").html(fileName);
    };

    // Formatear tamaño de archivo
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Buscar vehículo por placa
    $('#btnBuscarPlaca').click(function() {
        buscarVehiculoPorPlaca();
    });

    $('#buscadorPlaca').keypress(function(e) {
        if (e.which === 13) {
            buscarVehiculoPorPlaca();
        }
    });

    function buscarVehiculoPorPlaca() {
        const placa = $('#buscadorPlaca').val().trim().toUpperCase();
        
        if (!placa) {
            mostrarError('Placa requerida', 'Ingrese una placa para buscar el vehículo');
            return;
        }

        // Validar formato básico de placa
        if (!validarPatenteChilena(placa).valida) {
            mostrarError('Placa inválida', 'Ingrese una placa válida');
            return;
        }

        $('#btnBuscarPlaca').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Buscando...');

        $.ajax({
            url: '../app/model/ingreso_vehiculos/scripts/s_ingreso_vehiculos.php',
            type: 'POST',
            data: {
                action: 'buscar_ingreso_pendiente',
                placa: placa
            },
            dataType: 'json',
            success: function(response) {
                $('#btnBuscarPlaca').prop('disabled', false).html('<i class="fas fa-search"></i> Buscar');
                
                if (response.success) {
                    vehiculoEncontrado = response.data;
                    cargarDatosVehiculo(vehiculoEncontrado);
                    mostrarFormulario();
                } else {
                    mostrarVehiculoNoEncontrado();
                }
            },
            error: function() {
                $('#btnBuscarPlaca').prop('disabled', false).html('<i class="fas fa-search"></i> Buscar');
                mostrarError('Error de conexión', 'No se pudo conectar con el servidor');
            }
        });
    }

    function cargarDatosVehiculo(vehiculo) {
        // Cargar datos básicos del vehículo
        $('#placa').val(vehiculo.Placa);
        $('#ingreso_id').val(vehiculo.ID);
        
        // Mostrar información de precarga
        $('#textoInfoPrecarga').html(`
            Vehículo <strong>${vehiculo.Placa}</strong> encontrado. 
            Registrado el ${formatFecha(vehiculo.FechaIngreso)}. 
            Complete la información faltante.
        `);
        $('#infoPrecarga').show();

        // Si hay campos con "Por definir" o "PENDIENTE", limpiarlos para que el usuario complete
        if (vehiculo.TipoVehiculo === 'Por definir') {
            $('#tipo_vehiculo').val('');
        } else {
            $('#tipo_vehiculo').val(vehiculo.TipoVehiculo);
        }

        if (vehiculo.Marca === 'Por definir') {
            $('#marca').val('');
        } else {
            $('#marca').val(vehiculo.Marca);
        }

        if (vehiculo.Modelo === 'Por definir') {
            $('#modelo').val('');
        } else {
            $('#modelo').val(vehiculo.Modelo);
        }

        if (vehiculo.ConductorNombre === 'Por completar') {
            $('#conductor_nombre').val('');
        } else {
            $('#conductor_nombre').val(vehiculo.ConductorNombre);
        }

        if (vehiculo.ConductorCedula === 'Por completar') {
            $('#conductor_cedula').val('');
        } else {
            $('#conductor_cedula').val(vehiculo.ConductorCedula);
        }

        if (vehiculo.EmpresaCodigo === 'PENDIENTE') {
            $('#empresa_codigo').val('');
        } else {
            $('#empresa_codigo').val(vehiculo.EmpresaCodigo);
        }

        if (vehiculo.EmpresaNombre === 'PENDIENTE') {
            $('#empresa_nombre').val('');
        } else {
            $('#empresa_nombre').val(vehiculo.EmpresaNombre);
        }

        if (vehiculo.Proposito === 'PENDIENTE') {
            $('#proposito').val('');
        } else {
            $('#proposito').val(vehiculo.Proposito);
        }

        // Cargar otros campos si existen
        if (vehiculo.Color && vehiculo.Color !== 'Sin especificar') {
            $('#color').val(vehiculo.Color);
        }

        if (vehiculo.Anio) {
            $('#anio').val(vehiculo.Anio);
        }

        if (vehiculo.Kilometraje) {
            $('#kilometraje').val(vehiculo.Kilometraje);
        }

        if (vehiculo.Chasis) {
            $('#chasis').val(vehiculo.Chasis);
        }

        if (vehiculo.ConductorTelefono && vehiculo.ConductorTelefono !== 'No registrado') {
            $('#conductor_telefono').val(vehiculo.ConductorTelefono);
        }

        if (vehiculo.Licencia && vehiculo.Licencia !== 'No registrada') {
            $('#licencia').val(vehiculo.Licencia);
        }

        if (vehiculo.EstadoIngreso) {
            $('#estado_ingreso').val(vehiculo.EstadoIngreso);
        }

        if (vehiculo.Combustible) {
            $('#combustible').val(vehiculo.Combustible);
        }

        if (vehiculo.Area && vehiculo.Area !== 'General') {
            $('#area').val(vehiculo.Area);
        }

        if (vehiculo.PersonaContacto && vehiculo.PersonaContacto !== 'No asignado') {
            $('#persona_contacto').val(vehiculo.PersonaContacto);
        }

        if (vehiculo.Observaciones) {
            $('#observaciones').val(vehiculo.Observaciones);
        }
    }

    function mostrarFormulario() {
        $('#form-ingreso-vehiculo').show();
        $('#mensaje-no-encontrado').hide();
        $('html, body').animate({
            scrollTop: $('#form-ingreso-vehiculo').offset().top - 100
        }, 500);
    }

    function mostrarVehiculoNoEncontrado() {
        $('#form-ingreso-vehiculo').hide();
        $('#mensaje-no-encontrado').show();
        $('#infoPrecarga').hide();
        vehiculoEncontrado = null;
    }

    // Validación de patente chilena
    function validarPatenteChilena(patente) {
        patente = patente.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
        
        const patrones = [
            /^[A-Z]{4}\d{2}$/,      // ABCD12
            /^[A-Z]{2}\d{4}$/,      // AB1234
            /^[A-Z]{2}\d{2}[A-Z]{2}$/, // AB12CD
            /^[A-Z]{3}\d{3}$/,      // ABC123
            /^\d{4}[A-Z]{2}$/,      // 1234AB
            /^[A-Z]{2}\d{3}[A-Z]{1}$/ // AB123C
        ];
        
        const valida = patrones.some(patron => patron.test(patente));
        
        if (!valida) {
            return {
                valida: false,
                mensaje: 'Formato de patente inválido',
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
        return placa.toUpperCase().replace(/\s/g, '');
    }

    function normalizarChasis(chasis) {
        return chasis.toUpperCase().replace(/\s/g, '');
    }

    function normalizarCedula(cedula) {
        return cedula.replace(/\D/g, '');
    }

    function normalizarTelefono(telefono) {
        return telefono.replace(/\D/g, '');
    }

    function normalizarLicencia(licencia) {
        return licencia.toUpperCase().replace(/\s/g, '');
    }

    // Validaciones
    function validarNombre(nombre) {
        return /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(nombre);
    }

    function validarCedula(cedula) {
        return /^\d{7,15}$/.test(cedula);
    }

    function validarTelefono(telefono) {
        return telefono === '' || /^\d{8,15}$/.test(telefono);
    }

    function validarLicencia(licencia) {
        return licencia === '' || /^[a-zA-Z0-9\s]+$/.test(licencia);
    }

    // Formatear fecha
    function formatFecha(fechaString) {
        if (!fechaString) return 'Fecha no disponible';
        
        try {
            const fecha = new Date(fechaString);
            return fecha.toLocaleDateString('es-CL', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return fechaString;
        }
    }

    // Validación en tiempo real
    $('#form-ingreso-vehiculo input, #form-ingreso-vehiculo select, #form-ingreso-vehiculo textarea').on('blur', function() {
        const $field = $(this);
        const value = $field.val().trim();
        
        if ($field.prop('required') && value === '') {
            $field.addClass('is-invalid');
            $field.removeClass('is-valid');
        } else if ($field.is(':invalid')) {
            $field.addClass('is-invalid');
            $field.removeClass('is-valid');
        } else {
            $field.removeClass('is-invalid');
            $field.addClass('is-valid');
        }
    });

    // Validación específica para cédula
    $('#conductor_cedula').on('input', function() {
        let valor = $(this).val().replace(/\D/g, '');
        if (valor.length > 15) {
            valor = valor.substring(0, 15);
        }
        $(this).val(valor);
        
        if (valor.length >= 7 && valor.length <= 15) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else if (valor.length > 0) {
            $(this).removeClass('is-valid').addClass('is-invalid');
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
    });

    // Auto-completar empresa
    $('#empresa_codigo').on('change', function() {
        var codigo = $(this).val();
        var empresas = {
            'PEPS001': 'PepsiCo Chile S.A.',
            'PEPS002': 'PepsiCo Distribución',
            'PEPS003': 'PepsiCo Logística',
            'PEPS004': 'PepsiCo Flota Norte',
            'PEPS005': 'PepsiCo Flota Sur'
        };
        
        if (empresas[codigo]) {
            $('#empresa_nombre').val(empresas[codigo]).addClass('is-valid');
        }
    });

    // EVENTO PRINCIPAL DEL FORMULARIO - ACTUALIZAR REGISTRO EXISTENTE
    $('#form-ingreso-vehiculo').on('submit', function(e) {
        e.preventDefault();
        
        if (!vehiculoEncontrado) {
            mostrarError('Error', 'Primero busque un vehículo por placa');
            return;
        }

        // Validar formulario
        if (!this.checkValidity()) {
            $(this).addClass('was-validated');
            const firstInvalid = $(this).find('.is-invalid').first();
            if (firstInvalid.length) {
                firstInvalid.focus();
            }
            mostrarError('Formulario incompleto', 'Complete todos los campos obligatorios');
            return;
        }

        // Validar cédula
        const cedula = $('#conductor_cedula').val().replace(/\D/g, '');
        if (cedula.length < 7) {
            $('#conductor_cedula').focus();
            mostrarError('Cédula inválida', 'La cédula debe tener entre 7 y 15 dígitos');
            return;
        }

        actualizarRegistroVehiculo();
    });

    function actualizarRegistroVehiculo() {
        $('#btn-registrar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');

        // Preparar FormData
        const formData = new FormData();
        
        // OBTENER Y NORMALIZAR TODOS LOS CAMPOS
        const camposFormulario = {
            action: 'actualizar_ingreso',
            ingreso_id: $('#ingreso_id').val(),
            placa: normalizarPlaca($('#placa').val()),
            tipo_vehiculo: $('#tipo_vehiculo').val(),
            marca: normalizarTexto($('#marca').val()),
            modelo: normalizarTexto($('#modelo').val()),
            chasis: normalizarChasis($('#chasis').val()),
            color: normalizarTexto($('#color').val()),
            anio: $('#anio').val(),
            conductor_nombre: capitalizarTexto(normalizarTexto($('#conductor_nombre').val())),
            conductor_cedula: normalizarCedula($('#conductor_cedula').val()),
            conductor_telefono: normalizarTelefono($('#conductor_telefono').val()),
            licencia: normalizarLicencia($('#licencia').val()),
            empresa_codigo: $('#empresa_codigo').val(),
            empresa_nombre: $('#empresa_nombre').val(),
            proposito: $('#proposito').val(),
            area: $('#area').val(),
            persona_contacto: normalizarTexto($('#persona_contacto').val()),
            observaciones: normalizarTexto($('#observaciones').val()),
            estado_ingreso: $('#estado_ingreso').val(),
            kilometraje: $('#kilometraje').val(),
            combustible: $('#combustible').val(),
            usuario_id: $('#usuario_id').val() || 1
        };

        // Agregar campos manualmente al FormData
        Object.keys(camposFormulario).forEach(key => {
            if (camposFormulario[key] !== undefined && camposFormulario[key] !== null) {
                formData.append(key, camposFormulario[key]);
            }
        });

        // Manejar archivos
        try {
            const inputDocumentos = document.getElementById('documentos');
            if (inputDocumentos && inputDocumentos.files) {
                const documentos = inputDocumentos.files;
                for (let i = 0; i < documentos.length; i++) {
                    formData.append('documentos[]', documentos[i]);
                }
            }
        } catch (error) {
            console.warn('Error al procesar archivos:', error);
        }

        // Enviar datos via AJAX
        $.ajax({
            url: baseUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    mostrarModalExito(response.ingreso_id, response.message);
                    limpiarFormulario();
                } else {
                    mostrarError('Error al actualizar', response.message || 'Error desconocido');
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
                $('#btn-registrar').prop('disabled', false).html('<i class="fas fa-save"></i> Completar Registro');
            }
        });
    }

    function limpiarFormulario() {
        $('#form-ingreso-vehiculo')[0].reset();
        $('#form-ingreso-vehiculo').removeClass('was-validated');
        $('.custom-file-label').html('Seleccionar archivos...');
        $('#lista-documentos').empty();
        $('.is-valid').removeClass('is-valid');
        $('#form-ingreso-vehiculo').hide();
        $('#buscadorPlaca').val('');
        $('#infoPrecarga').hide();
        vehiculoEncontrado = null;
    }

    $('#btn-limpiar').click(function() {
        limpiarFormulario();
        $('#mensaje-no-encontrado').hide();
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

    // Función para mostrar el modal de éxito
    function mostrarModalExito(ingresoId, mensaje) {
        if (ingresoId) {
            $('#mensaje-exito').html(`
                La información del vehículo ha sido completada correctamente.
                <br><strong>ID de ingreso: <span id="id-registro">${ingresoId}</span></strong>
            `);
        }
        
        const ahora = new Date();
        const fechaFormateada = ahora.toLocaleDateString('es-CL', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        $('#fecha-registro').text(fechaFormateada);
        
        $('#modal-exito').modal('show');
    }

    // Manejadores de eventos para el modal
    $(document).on('click', '#modal-exito .btn-success, #modal-exito .close, #modal-exito [data-dismiss="modal"]', function() {
        $('#modal-exito').modal('hide');
    });

    $(document).on('click', '#modal-exito .btn-outline-primary', function() {
        $('#modal-exito').modal('hide');
        setTimeout(() => {
            location.reload();
        }, 300);
    });

    console.log('🚀 Aplicación de conductor lista para usar');
});