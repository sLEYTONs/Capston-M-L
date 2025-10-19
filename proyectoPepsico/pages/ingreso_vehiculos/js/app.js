$(document).ready(function() {
    // Ruta base para los scripts PHP
    var baseUrl = '../../../app/model/ingreso_vehiculos/scripts/s_ingreso_vehiculos.php';
    
    // Variables para almacenar archivos
    var documentosSubidos = [];
    var fotosSubidas = [];

    // Inicializar Dropzone para documentos
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

    // Validación del formulario
    $('#form-ingreso-vehiculo').on('submit', function(e) {
        e.preventDefault();
        
        // Validar formulario
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
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
                
                // Agregar campos del formulario
                formFields.forEach(function(field) {
                    formData.append(field.name, field.value);
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
                    dataType: 'json'
                }).then(function(response) {
                    if (!response.success) {
                        throw new Error(response.message || 'Error al registrar el vehículo');
                    }
                    return response;
                }).catch(function(error) {
                    Swal.showValidationMessage(
                        `Error: ${error.responseJSON?.message || error.statusText || 'Error de conexión'}`
                    );
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
                dropzoneDocumentos.removeAllFiles(true);
                dropzoneFotos.removeAllFiles(true);
                documentosSubidos = [];
                fotosSubidas = [];
                
                // Redirigir después de 3 segundos
                setTimeout(function() {
                    window.location.href = '../index.php';
                }, 3000);
            }
        });
    });

    // Validación en tiempo real
    $('#form-ingreso-vehiculo input, #form-ingreso-vehiculo select').on('blur', function() {
        if ($(this).is(':invalid')) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
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

    // Formato automático para placa
    $('#placa').on('input', function() {
        var placa = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
        $(this).val(placa);
    });

    // Formato automático para cédula
    $('#conductor_cedula').on('input', function() {
        var cedula = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(cedula);
    });

    // Tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Cerrar modal de éxito
    $('#modal-exito').on('hidden.bs.modal', function() {
        window.location.href = '../index.php';
    });
});