$(document).ready(function () {
    // Inicializar DataTable
    var tablaUsuarios = $('#tabla-usuarios').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "../app/model/gestion_usuarios/scripts/s_gestionusuarios.php",
            "type": "POST",
            "data": {
                "action": "listar_usuarios"
            },
            "dataSrc": "data"
        },
        "columns": [
            { "data": "UsuarioID" },
            { "data": "NombreUsuario" },
            { "data": "Correo" },
            { "data": "Rol" },
            {
                "data": "Estado",
                "render": function (data, type, row) {
                    if (data == 1) {
                        return '<span class="estado-activo"><i class="fas fa-check-circle"></i> Activo</span>';
                    } else {
                        return '<span class="estado-inactivo"><i class="fas fa-times-circle"></i> Inactivo</span>';
                    }
                }
            },
            {
                "data": "FechaCreacion",
                "render": function (data) {
                    return data ? new Date(data).toLocaleDateString('es-ES') : 'N/A';
                }
            },
            {
                "data": "UltimoAcceso",
                "render": function (data) {
                    if (data) {
                        return new Date(data).toLocaleString('es-ES');
                    } else {
                        return 'Nunca';
                    }
                }
            },
            {
                "data": null,
                "render": function (data, type, row) {
                    return '<button class="btn btn-warning btn-sm btn-editar" data-id="' + row.UsuarioID + '">' +
                        '<i class="fas fa-edit"></i> Editar</button>';
                },
                "orderable": false
            }
        ],
        "pageLength": 10,
        "lengthMenu": [[5, 10, 20, 50, 100], [5, 10, 20, 50, 100]],
        "order": [[0, 'desc']],
        "responsive": true,
        "autoWidth": false
    });

    // Enviar formulario de nuevo usuario
    $('#form-nuevo-usuario').on('submit', function (e) {
        e.preventDefault();

        Swal.fire({
            title: '¿Crear nuevo usuario?',
            text: "¿Estás seguro de que deseas registrar este nuevo usuario?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, crear usuario',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = $(this).serializeArray();
                formData.push({ name: 'action', value: 'crear_usuario' });

                $.ajax({
                    url: '../app/model/gestion_usuarios/scripts/s_gestionusuarios.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Limpiar formulario
                            $('#form-nuevo-usuario')[0].reset();
                            $('#form-nuevo-usuario').removeClass('was-validated');

                            // Recargar tabla
                            tablaUsuarios.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error del sistema',
                            text: 'Ocurrió un error al procesar la solicitud'
                        });
                        console.error('Error:', error);
                    }
                });
            }
        });
    });

    // Abrir modal para editar usuario
    $('#tabla-usuarios tbody').on('click', '.btn-editar', function () {
        var usuarioId = $(this).data('id');

        $.ajax({
            url: '../app/model/gestion_usuarios/scripts/s_gestionusuarios.php',
            type: 'POST',
            data: {
                action: 'obtener_usuario',
                usuario_id: usuarioId
            },
            dataType: 'json',
            success: function (response) {
                if (response) {
                    $('#edit-usuario-id').val(response.UsuarioID);
                    $('#edit-nombre-usuario').val(response.NombreUsuario);
                    $('#edit-correo').val(response.Correo);
                    $('#edit-rol').val(response.Rol);
                    $('#edit-estado').val(response.Estado.toString());
                    $('#edit-clave').val('');

                    $('#modal-editar-usuario').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cargar la información del usuario'
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al cargar los datos del usuario'
                });
            }
        });
    });

    // Actualizar usuario
    $('#btn-actualizar-usuario').on('click', function () {
        Swal.fire({
            title: '¿Actualizar usuario?',
            text: "¿Estás seguro de que deseas guardar los cambios?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, actualizar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = $('#form-editar-usuario').serializeArray();
                formData.push({ name: 'action', value: 'editar_usuario' });

                $.ajax({
                    url: '../app/model/gestion_usuarios/scripts/s_gestionusuarios.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            $('#modal-editar-usuario').modal('hide');
                            tablaUsuarios.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error del sistema',
                            text: 'Ocurrió un error al procesar la solicitud'
                        });
                    }
                });
            }
        });
    });
});