$(document).ready(function() {
    // Ruta base para los scripts PHP
    var baseUrl = '../../../app/model/ingreso_vehiculos/scripts/s_ingreso_vehiculos.php';
    
    // Variables para almacenar archivos
    var documentosSubidos = [];
    var fotosSubidas = [];

    // Validación de patente chilena
    function validarPatenteChilena(patente) {
        // Remover espacios y convertir a mayúsculas
        patente = patente.trim().toUpperCase().replace(/\s/g, '');
        
        // Patrones de patentes chilenas
        const patrones = [
            /^[A-Z]{4}\d{2}$/, // Formato antiguo: ABCD12
            /^[A-Z]{2}\d{4}$/, // Formato para vehículos más nuevos: AB1234
            /^[A-Z]{2}\d{2}[A-Z]{2}$/, // Formato nuevo: AB12CD
            /^[A-Z]{2}\d{3}[A-Z]{1}$/, // Formato para vehículos comerciales: ABC123D
            /^\d{4}[A-Z]{2}$/ // Formato para motos: 1234AB
        ];
        
        // Verificar si coincide con algún patrón
        const valida = patrones.some(patron => patron.test(patente));
        
        if (!valida) {
            return {
                valida: false,
                mensaje: 'Formato de patente inválido. Formatos aceptados: ABCD12, AB1234, AB12CD, ABC123D, 1234AB',
                patenteNormalizada: patente
            };
        }
        
        return {
            valida: true,
            mensaje: 'Patente válida',
            patenteNormalizada: patente
        };
    }

    // Formatear patente mientras se escribe
    $('#placa').on('input', function() {
        let valor = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
        
        // Aplicar formato según la longitud
        if (valor.length <= 4) {
            $(this).val(valor);
        } else if (valor.length === 6) {
            // Formatear según el patrón detectado
            if (/^[A-Z]{4}\d{2}$/.test(valor)) {
                // ABCD12 -> ABCD-12
                $(this).val(valor.substring(0, 4) + '-' + valor.substring(4));
            } else if (/^[A-Z]{2}\d{4}$/.test(valor)) {
                // AB1234 -> AB-1234
                $(this).val(valor.substring(0, 2) + '-' + valor.substring(2));
            } else if (/^[A-Z]{2}\d{2}[A-Z]{2}$/.test(valor)) {
                // AB12CD -> AB-12-CD
                $(this).val(valor.substring(0, 2) + '-' + valor.substring(2, 4) + '-' + valor.substring(4));
            } else {
                $(this).val(valor);
            }
        } else {
            $(this).val(valor);
        }
        
        // Validar en tiempo real
        validarCampoPlaca();
    });

    // Validación específica para el campo placa
    function validarCampoPlaca() {
        const placa = $('#placa').val();
        const resultado = validarPatenteChilena(placa.replace(/-/g, ''));
        
        if (placa === '') {
            $('#placa').removeClass('is-valid is-invalid');
            $('#error-placa').remove();
            return false;
        }
        
        if (!resultado.valida) {
            $('#placa').removeClass('is-valid').addClass('is-invalid');
            $('#error-placa').remove();
            $('#placa').after('<div class="invalid-feedback" id="error-placa">' + resultado.mensaje + '</div>');
            return false;
        } else {
            $('#placa').removeClass('is-invalid').addClass('is-valid');
            $('#error-placa').remove();
            return true;
        }
    }

    // Validación de RUT chileno (opcional, si necesitas validar cédula)
    function validarRUT(rut) {
        // Implementación básica de validación de RUT
        rut = rut.replace(/[^0-9kK]/g, '');
        if (rut.length < 8) return false;
        
        return true; // Para simplificar, aceptamos cualquier número de 8+ dígitos
    }

    // Inicializar Dropzone para documentos
    if (typeof Dropzone !== 'undefined') {
        var dropzoneDocumentos = new Dropzone("#dropzone-documentos", {
            url: baseUrl,
            paramName: "documentos",
            maxFiles: 5,
            maxFilesize: 10, // MB
            acceptedFiles: ".pdf,.doc,.docx,.xls,.xlsx,.txt",
            addRemoveLinks: true,
            dictRemoveFile: "Eliminar",
            dictMaxFilesExceeded: "Solo puede subir máximo 5 documentos",
            dictFileTooBig: "El archivo es demasiado grande ({{filesize}}MB). Máximo: {{maxFilesize}}MB.",
            init: function() {
                this.on("success", function(file, response) {
                    if (response.success) {
                        documentosSubidos.push({
                            nombre: file.name,
                            ruta: response.ruta,
                            tipo: response.tipo
                        });
                    }
                });
                this.on("removedfile", function(file) {
                    documentosSubidos = documentosSubidos.filter(doc => doc.nombre !== file.name);
                });
            }
        });

        // Inicializar Dropzone para fotos
        var dropzoneFotos = new Dropzone("#dropzone-fotos", {
            url: baseUrl,
            paramName: "fotos",
            maxFiles: 10,
            maxFilesize: 5, // MB
            acceptedFiles: "image/*",
            addRemoveLinks: true,
            dictRemoveFile: "Eliminar",
            dictMaxFilesExceeded: "Solo puede subir máximo 10 fotos",
            dictFileTooBig: "La imagen es demasiado grande ({{filesize}}MB). Máximo: {{maxFilesize}}MB.",
            init: function() {
                this.on("success", function(file, response) {
                    if (response.success) {
                        fotosSubidas.push({
                            nombre: file.name,
                            ruta: response.ruta,
                            miniatura: response.miniatura
                        });
                    }
                });
                this.on("removedfile", function(file) {
                    fotosSubidas = fotosSubidas.filter(foto => foto.nombre !== file.name);
                });
            }
        });
    } else {
        console.error('Dropzone no está cargado');
    }

    // Validación del formulario
    $('#form-ingreso-vehiculo').on('submit', function(e) {
        e.preventDefault();
        
        // Validar patente primero
        if (!validarCampoPlaca()) {
            $('#placa').focus();
            Swal.fire({
                icon: 'error',
                title: 'Error en la patente',
                text: 'Por favor, ingrese una patente válida',
                timer: 3000
            });
            return;
        }

        // Validar formulario completo
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            
            // Encontrar el primer campo inválido y enfocarlo
            const firstInvalid = $(this).find('.is-invalid').first();
            if (firstInvalid.length) {
                firstInvalid.focus();
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Formulario incompleto',
                text: 'Por favor, complete todos los campos obligatorios correctamente',
                timer: 3000
            });
            return;
        }

        // Validar cédula (mínimo 8 caracteres)
        const cedula = $('#conductor_cedula').val().replace(/\D/g, '');
        if (cedula.length < 8) {
            $('#conductor_cedula').addClass('is-invalid');
            $('#conductor_cedula').focus();
            Swal.fire({
                icon: 'error',
                title: 'Cédula inválida',
                text: 'La cédula debe tener al menos 8 dígitos',
                timer: 3000
            });
            return;
        }

        // Confirmación con SweetAlert
        Swal.fire({
            title: '¿Registrar ingreso de vehículo?',
            text: "¿Está seguro de que desea registrar el ingreso de este vehículo?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                // Preparar datos del formulario
                var formData = new FormData();
                var formFields = $(this).serializeArray();
                
                // Normalizar patente (quitar guiones)
                const patenteNormalizada = $('#placa').val().replace(/-/g, '');
                formData.append('placa', patenteNormalizada);
                
                // Agregar otros campos del formulario (excepto placa que ya la agregamos normalizada)
                formFields.forEach(function(field) {
                    if (field.name !== 'placa') {
                        formData.append(field.name, field.value);
                    }
                });
                
                // Agregar archivos
                formData.append('documentos', JSON.stringify(documentosSubidos));
                formData.append('fotos', JSON.stringify(fotosSubidas));
                formData.append('action', 'registrar_ingreso');
                formData.append('usuario_id', '<?php echo $usuario_id; ?>');

                return $.ajax({
                    url: baseUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    timeout: 30000
                }).then(function(response) {
                    if (!response.success) {
                        throw new Error(response.message || 'Error al registrar el vehículo');
                    }
                    return response;
                }).catch(function(error) {
                    let errorMsg = 'Error de conexión';
                    if (error.responseJSON && error.responseJSON.message) {
                        errorMsg = error.responseJSON.message;
                    } else if (error.statusText) {
                        errorMsg = error.statusText;
                    }
                    Swal.showValidationMessage(`Error: ${errorMsg}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar modal de éxito
                $('#mensaje-exito').html(result.value.message);
                $('#modal-exito').modal('show');
                
                // Limpiar formulario
                $('#form-ingreso-vehiculo')[0].reset();
                $('#form-ingreso-vehiculo').removeClass('was-validated');
                
                // Limpiar dropzones
                if (typeof dropzoneDocumentos !== 'undefined') {
                    dropzoneDocumentos.removeAllFiles(true);
                }
                if (typeof dropzoneFotos !== 'undefined') {
                    dropzoneFotos.removeAllFiles(true);
                }
                documentosSubidos = [];
                fotosSubidas = [];
                
                // Redirigir después de 3 segundos
                setTimeout(function() {
                    window.location.href = '../index.php';
                }, 3000);
            }
        });
    });

    // Validación en tiempo real para todos los campos
    $('#form-ingreso-vehiculo input, #form-ingreso-vehiculo select, #form-ingreso-vehiculo textarea').on('blur', function() {
        const $field = $(this);
        const value = $field.val().trim();
        
        if ($field.is(':invalid') || ($field.prop('required') && value === '')) {
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
        $(this).val(valor);
        
        if (valor.length >= 8) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else if (valor.length > 0) {
            $(this).removeClass('is-valid').addClass('is-invalid');
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
    });

    // Auto-completar empresa basado en código
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
            $('#empresa_nombre').val(empresas[codigo]);
        }
    });

    // Mostrar/ocultar campos basados en el estado
    $('#estado_ingreso').on('change', function() {
        var estado = $(this).val();
        if (estado === 'Accidentado') {
            $('#observaciones').attr('placeholder', 'Describa los daños del accidente...');
        } else {
            $('#observaciones').attr('placeholder', 'Describa cualquier detalle importante sobre el estado del vehículo...');
        }
    });

    // Formato automático para teléfono
    $('#conductor_telefono').on('input', function() {
        let valor = $(this).val().replace(/\D/g, '');
        if (valor.length <= 8) {
            $(this).val(valor);
        } else if (valor.length === 9) {
            $(this).val(valor.substring(0, 1) + ' ' + valor.substring(1, 5) + ' ' + valor.substring(5));
        }
    });

    // Tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Cerrar modal de éxito
    $('#modal-exito').on('hidden.bs.modal', function() {
        window.location.href = '../index.php';
    });

    // Ajustar posición para evitar solapamiento con footer
    function adjustFormPosition() {
        const form = document.getElementById('form-ingreso-vehiculo');
        const footer = document.querySelector('.pc-footer');
        
        if (form && footer) {
            const formRect = form.getBoundingClientRect();
            const footerRect = footer.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            if (formRect.bottom > footerRect.top - 50) {
                const extraSpace = formRect.bottom - (footerRect.top - 100);
                document.querySelector('.pc-content').style.paddingBottom = (extraSpace + 100) + 'px';
            }
        }
    }

    // Ejecutar ajustes al cargar y redimensionar
    adjustFormPosition();
    window.addEventListener('resize', adjustFormPosition);
    window.addEventListener('scroll', adjustFormPosition);
    
    console.log('Aplicación de ingreso de vehículos cargada correctamente');
});