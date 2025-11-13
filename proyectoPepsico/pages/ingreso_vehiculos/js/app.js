$(document).ready(function() {
    var baseUrl = '../app/model/ingreso_vehiculos/scripts/s_ingreso_vehiculos.php';
    
    // Variables para almacenar archivos
    var documentosSubidos = [];
    var fotosSubidas = [];

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
        if (!input) {
            console.warn('⚠️ Input no encontrado');
            return;
        }
        
        const files = input.files;
        const listaId = input.id === 'documentos' ? 'lista-documentos' : 'lista-fotos';
        const lista = document.getElementById(listaId);
        
        if (!lista) {
            console.warn(`⚠️ Elemento con id ${listaId} no encontrado`);
            return;
        }
        
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
        
        if (!input) {
            console.warn(`⚠️ Input ${tipo} no encontrado`);
            return;
        }
        
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
                mensaje: 'Formato de patente inválido. Formatos: ABCD12, AB1234, AB12CD, ABC123, 1234AB, AB123C',
                patenteNormalizada: patente
            };
        }
        
        return {
            valida: true,
            mensaje: 'Patente válida',
            patenteNormalizada: patente
        };
    }

    // Formatear patente en tiempo real
    $('#placa').on('input', function() {
        let valor = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
        
        if (valor.length > 10) {
            valor = valor.substring(0, 10);
        }
        
        $(this).val(valor);
        validarCampoPlaca();
    });

    // Validación específica para placa
    function validarCampoPlaca() {
        const placa = $('#placa').val();
        const resultado = validarPatenteChilena(placa);
        
        if (placa === '') {
            $('#placa').removeClass('is-valid is-invalid');
            return false;
        }
        
        if (!resultado.valida) {
            $('#placa').removeClass('is-valid').addClass('is-invalid');
            return false;
        } else {
            $('#placa').removeClass('is-invalid').addClass('is-valid');
            return true;
        }
    }

    // Validación en tiempo real para todos los campos
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

    // EVENTO PRINCIPAL DEL FORMULARIO
    $('#form-ingreso-vehiculo').on('submit', function(e) {
        console.log('🔔 Submit del formulario capturado');
        e.preventDefault();
        
        // Validar patente
        if (!validarCampoPlaca()) {
            $('#placa').focus();
            mostrarError('Error en la patente', 'Por favor, ingrese una patente válida');
            return;
        }

        // Validar formulario completo
        if (!this.checkValidity()) {
            console.log('❌ Formulario inválido');
            $(this).addClass('was-validated');
            
            const firstInvalid = $(this).find('.is-invalid').first();
            if (firstInvalid.length) {
                firstInvalid.focus();
            }
            
            mostrarError('Formulario incompleto', 'Por favor, complete todos los campos obligatorios correctamente');
            return;
        }

        // Validar cédula
        const cedula = $('#conductor_cedula').val().replace(/\D/g, '');
        if (cedula.length < 7) {
            $('#conductor_cedula').focus();
            mostrarError('Cédula inválida', 'La cédula debe tener entre 7 y 15 dígitos');
            return;
        }

        console.log('✅ Formulario válido, procediendo con registro...');
        registrarVehiculo();
    });

    // Función para mostrar errores
    function mostrarError(titulo, mensaje) {
        Swal.fire({
            icon: 'error',
            title: titulo,
            text: mensaje,
            timer: 5000
        });
    }

    // Función principal para registrar el vehículo
    function registrarVehiculo() {
        // Deshabilitar botón
        $('#btn-registrar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        // Preparar FormData
        const formData = new FormData();
        const formFields = $('#form-ingreso-vehiculo').serializeArray();
        
        // Agregar campos del formulario
        formFields.forEach(field => {
            formData.append(field.name, field.value);
        });

        // Manejar archivos de forma segura
        try {
            const inputDocumentos = document.getElementById('documentos');
            const inputFotos = document.getElementById('fotos');
            
            if (inputDocumentos && inputDocumentos.files) {
                const documentos = inputDocumentos.files;
                for (let i = 0; i < documentos.length; i++) {
                    formData.append('documentos[]', documentos[i]);
                }
                console.log(`📄 Documentos a subir: ${documentos.length}`);
            }
            
            if (inputFotos && inputFotos.files) {
                const fotos = inputFotos.files;
                for (let i = 0; i < fotos.length; i++) {
                    formData.append('fotos[]', fotos[i]);
                }
                console.log(`📷 Fotos a subir: ${fotos.length}`);
            }
        } catch (error) {
            console.warn('⚠️ Error al procesar archivos:', error);
        }

        // Agregar acción
        formData.append('action', 'registrar_ingreso');

        console.log('📤 Enviando datos al servidor:', baseUrl);

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
                console.log('📨 Respuesta del servidor:', response);
                
                if (response.success) {
                    // Éxito - usar la nueva función mejorada
                    mostrarModalExito(response.ingreso_id, response.message);
                    
                    // Limpiar formulario
                    $('#form-ingreso-vehiculo')[0].reset();
                    $('#form-ingreso-vehiculo').removeClass('was-validated');
                    $('.custom-file-label').html('Seleccionar archivos...');
                    $('#lista-documentos, #lista-fotos').empty();
                    $('.is-valid').removeClass('is-valid');
                    
                    console.log('✅ Vehículo registrado exitosamente');
                } else {
                    mostrarError('Error al registrar', response.message || 'Error desconocido');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error en AJAX:', error);
                console.error('📊 Estado:', status);
                console.error('🔧 XHR:', xhr);
                
                let errorMsg = 'Error de conexión con el servidor';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.statusText) {
                    errorMsg = xhr.statusText;
                }
                
                // Mostrar más detalles del error
                let detalles = '';
                if (xhr.status === 404) {
                    detalles = 'El archivo PHP no fue encontrado. Verifica la ruta.';
                } else if (xhr.status === 500) {
                    detalles = 'Error interno del servidor. Revisa el log de errores.';
                }
                
                mostrarError('Error de conexión', errorMsg + (detalles ? '<br><small>' + detalles + '</small>' : ''));
            },
            complete: function() {
                $('#btn-registrar').prop('disabled', false).html('<i class="fas fa-car"></i> Registrar Ingreso de Vehículo');
            }
        });
    }

    // Función mejorada para mostrar el modal de éxito
    function mostrarModalExito(ingresoId, mensaje) {
        // Actualizar mensaje con ID si está disponible
        if (ingresoId) {
            $('#mensaje-exito').html(`
                El vehículo ha sido registrado correctamente en el sistema. 
                <br><strong>ID de ingreso: <span id="id-registro">${ingresoId}</span></strong>
                <br>Se han notificado a los responsables correspondientes.
            `);
        } else {
            $('#mensaje-exito').html(mensaje || 'El vehículo ha sido registrado correctamente en el sistema.');
        }
        
        // Actualizar fecha actual
        const ahora = new Date();
        const fechaFormateada = ahora.toLocaleDateString('es-CL', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        $('#fecha-registro').text(fechaFormateada);
        
        // Mostrar modal con efectos
        $('#modal-exito').modal('show');
        
        // Efecto de confeti opcional (requiere librería confetti.js)
        if (typeof confetti === 'function') {
            setTimeout(() => {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 }
                });
            }, 300);
        }
    }

    // MANEJADORES DE EVENTOS PARA EL MODAL - CORREGIDOS
    $(document).on('click', '#modal-exito .btn-success, #modal-exito .close, #modal-exito [data-dismiss="modal"]', function() {
        $('#modal-exito').modal('hide');
    });

    $(document).on('click', '#modal-exito .btn-outline-primary', function() {
        $('#modal-exito').modal('hide');
        setTimeout(() => {
            window.location.reload();
        }, 300);
    });

    // También manejar el evento hidden.bs.modal para asegurar el cierre
    $('#modal-exito').on('hidden.bs.modal', function() {
        // Código adicional que quieras ejecutar después de cerrar el modal
        console.log('Modal cerrado correctamente');
    });

    // Limpiar validación al resetear formulario
    $('button[type="reset"]').on('click', function() {
        $('#form-ingreso-vehiculo').removeClass('was-validated');
        $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $('.custom-file-label').html('Seleccionar archivos...');
        $('#lista-documentos, #lista-fotos').empty();
        documentosSubidos = [];
        fotosSubidas = [];
    });

    console.log('🚀 Aplicación lista para usar');
});