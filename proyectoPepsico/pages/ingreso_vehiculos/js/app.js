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

    // Funciones de normalización y validación mejoradas
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

    // Validar nombre (solo letras y espacios)
    function validarNombre(nombre) {
        return /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(nombre);
    }

    // Validar cédula (solo números, entre 7 y 15)
    function validarCedula(cedula) {
        return /^\d{7,15}$/.test(cedula);
    }

    // Validar teléfono (solo números, entre 8 y 15)
    function validarTelefono(telefono) {
        return telefono === '' || /^\d{8,15}$/.test(telefono);
    }

    // Validar licencia (solo letras, números y espacios)
    function validarLicencia(licencia) {
        return licencia === '' || /^[a-zA-Z0-9\s]+$/.test(licencia);
    }

    // Verificar duplicados en tiempo real
    function verificarDuplicados() {
        const placa = $('#placa').val();
        const chasis = $('#chasis').val();
        const cedula = $('#conductor_cedula').val();
        const licencia = $('#licencia').val();
        
        return {
            placaValida: validarPatenteChilena(placa).valida,
            chasisValido: chasis === '' || chasis.length >= 5,
            cedulaValida: validarCedula(cedula),
            licenciaValida: licencia === '' || validarLicencia(licencia)
        };
    }

    // Normalizar y validar campos en tiempo real
    $('#placa').on('blur', function() {
        let valor = normalizarPlaca($(this).val());
        $(this).val(valor);
        validarCampoPlaca();
    });

    $('#chasis').on('blur', function() {
        let valor = normalizarChasis($(this).val());
        $(this).val(valor);
        
        if (valor !== '' && !verificarDuplicados().chasisValido) {
            $(this).addClass('is-invalid');
            $(this).removeClass('is-valid');
        } else if (valor !== '') {
            $(this).removeClass('is-invalid');
            $(this).addClass('is-valid');
        } else {
            $(this).removeClass('is-valid is-invalid');
        }
    });

    $('#conductor_nombre').on('blur', function() {
        let valor = normalizarTexto($(this).val());
        valor = capitalizarTexto(valor);
        $(this).val(valor);
        
        if (!validarNombre(valor)) {
            $(this).addClass('is-invalid');
            $(this).removeClass('is-valid');
        } else {
            $(this).removeClass('is-invalid');
            $(this).addClass('is-valid');
        }
    });

    $('#conductor_cedula').on('blur', function() {
        let valor = normalizarCedula($(this).val());
        $(this).val(valor);
        
        if (!validarCedula(valor)) {
            $(this).addClass('is-invalid');
            $(this).removeClass('is-valid');
        } else {
            $(this).removeClass('is-invalid');
            $(this).addClass('is-valid');
        }
    });

    $('#conductor_telefono').on('blur', function() {
        let valor = normalizarTelefono($(this).val());
        $(this).val(valor);
        
        if (!validarTelefono(valor)) {
            $(this).addClass('is-invalid');
            $(this).removeClass('is-valid');
        } else {
            $(this).removeClass('is-invalid');
            $(this).addClass('is-valid');
        }
    });

    $('#licencia').on('blur', function() {
        let valor = normalizarLicencia($(this).val());
        $(this).val(valor);
        
        if (!validarLicencia(valor)) {
            $(this).addClass('is-invalid');
            $(this).removeClass('is-valid');
        } else {
            $(this).removeClass('is-invalid');
            $(this).addClass('is-valid');
        }
    });

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

    // Validación antes del envío del formulario
    function validarFormularioCompleto() {
        const duplicados = verificarDuplicados();
        
        if (!duplicados.placaValida) {
            mostrarError('Placa inválida', 'Por favor ingrese una placa válida');
            return false;
        }
        
        if (!duplicados.cedulaValida) {
            mostrarError('Cédula inválida', 'La cédula debe contener entre 7 y 15 dígitos');
            return false;
        }
        
        // Validar que no haya caracteres especiales en nombres
        const conductorNombre = $('#conductor_nombre').val();
        if (!validarNombre(conductorNombre)) {
            mostrarError('Nombre inválido', 'El nombre solo puede contener letras y espacios');
            return false;
        }
        
        return true;
    }

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

        // Validación adicional antes del envío
        if (!validarFormularioCompleto()) {
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

    // Función mejorada para mostrar errores con información detallada de duplicados
    function mostrarError(titulo, mensaje, camposDuplicados = [], datosDuplicados = {}) {
        let htmlContent = `
            <div class="text-left">
                <h6 class="mb-3">${titulo}</h6>
                <p class="mb-3">${mensaje}</p>
        `;
        
        if (camposDuplicados.length > 0) {
            htmlContent += `
                <div class="alert alert-warning mt-3">
                    <h6 class="alert-heading mb-2">📋 Entradas duplicadas encontradas:</h6>
                    <div class="duplicated-entries">
            `;
            
            const camposInfo = {
                'placa': 'Placa del vehículo',
                'chasis': 'Número de chasis', 
                'cedula': 'Cédula del conductor',
                'licencia': 'Número de licencia'
            };
            
            camposDuplicados.forEach(campo => {
                const info = datosDuplicados[campo];
                htmlContent += `<div class="duplicate-item mb-3 p-2 border rounded">`;
                htmlContent += `<strong>${camposInfo[campo]}</strong>`;
                
                if (info) {
                    htmlContent += `<div class="duplicate-details mt-1 small text-muted">`;
                    
                    switch(campo) {
                        case 'placa':
                            htmlContent += `
                                <div>🚗 <strong>Vehículo existente:</strong> ${info.Placa} - ${info.Marca} ${info.Modelo}</div>
                                <div>👤 Conductor: ${info.ConductorNombre}</div>
                                <div>📅 Fecha de ingreso: ${formatFecha(info.FechaIngreso)}</div>
                            `;
                            break;
                        case 'chasis':
                            htmlContent += `
                                <div>🔧 <strong>Vehículo existente:</strong> ${info.Placa} - ${info.Marca} ${info.Modelo}</div>
                                <div>👤 Conductor: ${info.ConductorNombre}</div>
                                <div>📅 Fecha de ingreso: ${formatFecha(info.FechaIngreso)}</div>
                            `;
                            break;
                        case 'cedula':
                            htmlContent += `
                                <div>👤 <strong>Conductor existente:</strong> ${info.ConductorNombre}</div>
                                <div>🆔 Cédula: ${info.ConductorCedula}</div>
                                <div>🚗 Vehículo: ${info.Placa}</div>
                                <div>📅 Fecha de ingreso: ${formatFecha(info.FechaIngreso)}</div>
                            `;
                            break;
                        case 'licencia':
                            htmlContent += `
                                <div>📜 <strong>Conductor existente:</strong> ${info.ConductorNombre}</div>
                                <div>🆔 Licencia: ${info.Licencia}</div>
                                <div>🚗 Vehículo: ${info.Placa}</div>
                                <div>📅 Fecha de ingreso: ${formatFecha(info.FechaIngreso)}</div>
                            `;
                            break;
                    }
                    
                    htmlContent += `</div>`;
                }
                
                htmlContent += `</div>`;
            });
            
            htmlContent += `
                    </div>
                    <hr>
                    <p class="mb-0 small">⚠️ Los campos marcados han sido limpiados automáticamente. Por favor, ingrese valores únicos.</p>
                </div>
            `;
        }
        
        htmlContent += `</div>`;
        
        Swal.fire({
            icon: 'error',
            title: titulo,
            html: htmlContent,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc3545',
            width: '600px',
            focusConfirm: false,
            timer: camposDuplicados.length > 0 ? 15000 : 5000
        }).then((result) => {
            // Si hay campos duplicados, limpiarlos y enfocar el primero
            if (camposDuplicados.length > 0) {
                limpiarCamposDuplicados(camposDuplicados);
            }
        });
    }

    // Función para formatear fecha
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

    // Función para limpiar campos duplicados
    function limpiarCamposDuplicados(camposDuplicados) {
        console.log('🧹 Limpiando campos duplicados:', camposDuplicados);
        
        const mapeoCampos = {
            'placa': '#placa',
            'chasis': '#chasis',
            'cedula': '#conductor_cedula', 
            'licencia': '#licencia'
        };
        
        // Limpiar todos los campos duplicados
        camposDuplicados.forEach(campo => {
            const selector = mapeoCampos[campo];
            if (selector) {
                $(selector).val('')
                    .removeClass('is-valid is-invalid')
                    .addClass('is-invalid')
                    .addClass('highlight-duplicate');
                
                // Agregar mensaje de error específico
                const mensajeError = `Este valor ya existe en el sistema. Por favor ingrese uno diferente.`;
                $(selector).next('.invalid-feedback').text(mensajeError);
                
                // Remover la clase de highlight después de la animación
                setTimeout(() => {
                    $(selector).removeClass('highlight-duplicate');
                }, 2000);
            }
        });
        
        // Enfocar el primer campo duplicado
        if (camposDuplicados.length > 0) {
            const primerCampo = mapeoCampos[camposDuplicados[0]];
            if (primerCampo) {
                setTimeout(() => {
                    $(primerCampo).focus();
                }, 500);
            }
        }
    }

    // Función principal para registrar el vehículo
    function registrarVehiculo() {
        console.log('🔔 Iniciando registro de vehículo...');
        
        // Validación adicional antes del envío
        if (!validarFormularioCompleto()) {
            return;
        }

        // Deshabilitar botón
        $('#btn-registrar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        // Preparar FormData con datos normalizados
        const formData = new FormData();
        
        // OBTENER Y NORMALIZAR TODOS LOS CAMPOS
        const camposFormulario = {
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

        console.log('📝 Datos normalizados:', camposFormulario);

        // Agregar campos manualmente al FormData
        Object.keys(camposFormulario).forEach(key => {
            if (camposFormulario[key] !== undefined && camposFormulario[key] !== null && camposFormulario[key] !== '') {
                formData.append(key, camposFormulario[key]);
            }
        });

        // DEBUG: Verificar que el color se agregó correctamente
        console.log('🔍 Verificando FormData - color:');
        for (let pair of formData.entries()) {
            if (pair[0] === 'color') {
                console.log('✅ Color encontrado en FormData:', pair[1]);
            }
        }

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
                    // Verificar si hay campos duplicados
                    if (response.duplicated_fields && response.duplicated_fields.length > 0) {
                        mostrarError(
                            'Datos duplicados encontrados', 
                            response.message, 
                            response.duplicated_fields, 
                            response.duplicated_data
                        );
                    } else {
                        mostrarError('Error al registrar', response.message || 'Error desconocido');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error en AJAX:', error);
                console.error('📊 Estado:', status);
                
                // Mostrar la respuesta del servidor para debugging
                if (xhr.responseText) {
                    console.error('📄 Respuesta del servidor:', xhr.responseText);
                }
                
                let errorMsg = 'Error de conexión con el servidor';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.statusText) {
                    errorMsg = xhr.statusText;
                }
                
                mostrarError('Error de conexión', errorMsg);
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