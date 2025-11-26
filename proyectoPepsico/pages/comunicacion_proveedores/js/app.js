// Aplicación para Comunicación con Proveedores
class ComunicacionProveedores {
    constructor() {
        this.dataTable = null;
        this.proveedorSeleccionado = null;
        this.baseUrl = this.getBaseUrl();
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/comunicacion_proveedores/scripts/s_comunicacion_proveedores.php';
        }
        return '../../app/model/comunicacion_proveedores/scripts/s_comunicacion_proveedores.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.inicializarValidaciones();
        this.inicializarDataTable();
        this.cargarProveedores();
    }

    inicializarEventos() {
        $('#btn-nuevo-proveedor').on('click', () => {
            this.abrirModalProveedor();
        });

        $('#btn-guardar-proveedor').on('click', () => {
            this.guardarProveedor();
        });

        $('#form-comunicacion').on('submit', (e) => {
            e.preventDefault();
            this.enviarComunicacion();
        });

        // Limpiar formulario al cerrar modal
        $('#modal-proveedor').on('hidden.bs.modal', () => {
            this.limpiarFormulario();
        });

        // Eventos para botones de acciones en la tabla
        $(document).on('click', '.btn-editar-proveedor', (e) => {
            const id = $(e.currentTarget).data('id');
            this.editarProveedor(id);
        });

        $(document).on('click', '.btn-eliminar-proveedor', (e) => {
            const id = $(e.currentTarget).data('id');
            this.eliminarProveedor(id);
        });
    }

    inicializarValidaciones() {
        // Validación de email en tiempo real
        $('#email-proveedor').on('blur', () => {
            this.validarEmail();
        });

        // Validación de teléfono en tiempo real
        $('#telefono-proveedor').on('blur', () => {
            this.validarTelefono();
        });

        // Validación de RUT en tiempo real
        $('#rut-proveedor').on('blur', () => {
            this.validarRUT();
        });

        // Formatear teléfono mientras se escribe
        $('#telefono-proveedor').on('input', (e) => {
            this.formatearTelefono(e.target);
        });

        // Formatear RUT mientras se escribe
        $('#rut-proveedor').on('input', (e) => {
            this.formatearRUT(e.target);
        });
    }

    // Validar formato de email
    validarEmail() {
        const email = $('#email-proveedor').val().trim();
        const emailInput = $('#email-proveedor');
        const errorDiv = $('#error-email');

        if (email === '') {
            emailInput.removeClass('is-valid is-invalid');
            errorDiv.text('');
            return false;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailRegex.test(email)) {
            emailInput.removeClass('is-invalid').addClass('is-valid');
            errorDiv.text('');
            return true;
        } else {
            emailInput.removeClass('is-valid').addClass('is-invalid');
            errorDiv.text('El formato del email no es válido. Ejemplo: proveedor@ejemplo.cl');
            return false;
        }
    }

    // Validar formato de teléfono chileno
    validarTelefono() {
        const telefono = $('#telefono-proveedor').val().trim();
        const telefonoInput = $('#telefono-proveedor');
        const errorDiv = $('#error-telefono');

        if (telefono === '') {
            telefonoInput.removeClass('is-valid is-invalid');
            errorDiv.text('');
            return false;
        }

        // Limpiar el teléfono
        const telefonoLimpio = telefono.replace(/[\s\-\(\)]/g, '');

        // Remover código país si existe
        let telefonoSinCodigo = telefonoLimpio;
        if (telefonoLimpio.startsWith('+56') || telefonoLimpio.startsWith('56')) {
            telefonoSinCodigo = telefonoLimpio.replace(/^(\+?56)/, '');
        }

        // Validar móvil (9 dígitos: 9 + 8 dígitos)
        const esMovil = /^9\d{8}$/.test(telefonoSinCodigo);
        // Validar fijo (8 dígitos: 2 o 3 + 7 dígitos)
        const esFijo = /^[23]\d{7}$/.test(telefonoSinCodigo);

        if (esMovil || esFijo) {
            telefonoInput.removeClass('is-invalid').addClass('is-valid');
            errorDiv.text('');
            return true;
        } else {
            telefonoInput.removeClass('is-valid').addClass('is-invalid');
            errorDiv.text('Formato inválido. Use: +56 9 1234 5678 (móvil) o +56 2 2123 4567 (fijo)');
            return false;
        }
    }

    // Validar formato de RUT chileno (personas y empresas)
    validarRUT() {
        const rut = $('#rut-proveedor').val().trim();
        const rutInput = $('#rut-proveedor');
        const errorDiv = $('#error-rut');

        if (rut === '') {
            rutInput.removeClass('is-valid is-invalid');
            errorDiv.text('');
            return true; // RUT es opcional
        }

        // Limpiar el RUT: eliminar puntos, guiones y espacios
        let rutLimpio = rut.replace(/[\.\-\s]/g, '');
        rutLimpio = rutLimpio.toUpperCase();

        // Validar formato básico: 7 u 8 dígitos + 1 dígito verificador (0-9 o K)
        // El RUT debe tener entre 8 y 9 caracteres en total (7-8 dígitos + 1 DV)
        if (!/^\d{7,8}[0-9K]$/.test(rutLimpio)) {
            rutInput.removeClass('is-valid').addClass('is-invalid');
            errorDiv.text('Formato inválido. Use: 12.345.678-9 (8 dígitos) o 1.234.567-8 (7 dígitos)');
            return false;
        }

        // Separar número y dígito verificador
        const matches = rutLimpio.match(/^(\d{7,8})([0-9K])$/);
        if (!matches) {
            rutInput.removeClass('is-valid').addClass('is-invalid');
            errorDiv.text('Formato inválido. Use: 12.345.678-9 (8 dígitos) o 1.234.567-8 (7 dígitos)');
            return false;
        }

        const numero = matches[1];
        const dv = matches[2];

        // Validar que el número tenga exactamente 7 u 8 dígitos
        const longitudNumero = numero.length;
        if (longitudNumero < 7 || longitudNumero > 8) {
            rutInput.removeClass('is-valid').addClass('is-invalid');
            errorDiv.text('El RUT debe tener 7 u 8 dígitos antes del dígito verificador');
            return false;
        }

        // Calcular dígito verificador usando algoritmo módulo 11
        let suma = 0;
        let multiplier = 2;

        // Recorrer de derecha a izquierda
        for (let i = longitudNumero - 1; i >= 0; i--) {
            suma += parseInt(numero[i]) * multiplier;
            multiplier++;
            // Reiniciar multiplicador cuando llega a 7
            if (multiplier > 7) {
                multiplier = 2;
            }
        }

        let resto = suma % 11;
        let dvCalculado = 11 - resto;

        // Ajustar casos especiales
        if (dvCalculado === 11) {
            dvCalculado = '0';
        } else if (dvCalculado === 10) {
            dvCalculado = 'K';
        } else {
            dvCalculado = dvCalculado.toString();
        }

        // Comparar dígito verificador
        if (dv === dvCalculado) {
            rutInput.removeClass('is-invalid').addClass('is-valid');
            errorDiv.text('');
            return true;
        } else {
            rutInput.removeClass('is-valid').addClass('is-invalid');
            errorDiv.text('El RUT no es válido. El dígito verificador no coincide. Esperado: ' + dvCalculado);
            return false;
        }
    }

    // Formatear teléfono mientras se escribe
    formatearTelefono(input) {
        let valor = input.value.replace(/\D/g, ''); // Solo números

        // Si empieza con 56, mantenerlo
        if (valor.startsWith('56')) {
            if (valor.length > 2) {
                const sinCodigo = valor.substring(2);
                if (sinCodigo.startsWith('9') && sinCodigo.length === 9) {
                    // Móvil: +56 9 XXXX XXXX
                    input.value = '+56 ' + sinCodigo.substring(0, 1) + ' ' + 
                                 sinCodigo.substring(1, 5) + ' ' + 
                                 sinCodigo.substring(5);
                } else if ((sinCodigo.startsWith('2') || sinCodigo.startsWith('3')) && sinCodigo.length === 8) {
                    // Fijo: +56 2 XXXX XXXX
                    input.value = '+56 ' + sinCodigo.substring(0, 1) + ' ' + 
                                 sinCodigo.substring(1, 5) + ' ' + 
                                 sinCodigo.substring(5);
                }
            }
        } else if (valor.startsWith('9') && valor.length === 9) {
            // Móvil sin código: 9 XXXX XXXX
            input.value = valor.substring(0, 1) + ' ' + 
                         valor.substring(1, 5) + ' ' + 
                         valor.substring(5);
        } else if ((valor.startsWith('2') || valor.startsWith('3')) && valor.length === 8) {
            // Fijo sin código: 2 XXXX XXXX
            input.value = valor.substring(0, 1) + ' ' + 
                         valor.substring(1, 5) + ' ' + 
                         valor.substring(5);
        }
    }

    // Formatear RUT mientras se escribe
    formatearRUT(input) {
        // Limpiar: solo números y K
        let valor = input.value.replace(/[^0-9kK]/gi, '');
        valor = valor.toUpperCase();

        if (valor.length > 1) {
            const numero = valor.slice(0, -1);
            const dv = valor.slice(-1);

            // Validar que tenga 7 u 8 dígitos
            if (numero.length < 7 || numero.length > 8) {
                // Si tiene menos de 7 dígitos, solo mostrar sin formatear
                if (numero.length < 7) {
                    input.value = valor;
                    return;
                }
                // Si tiene más de 8 dígitos, truncar
                if (numero.length > 8) {
                    valor = numero.slice(0, 8) + dv;
                    return;
                }
            }

            // Formatear número con puntos según la longitud
            let numeroFormateado = '';
            if (numero.length === 8) {
                // RUT de 8 dígitos: XX.XXX.XXX
                numeroFormateado = numero.substring(0, 2) + '.' + 
                                  numero.substring(2, 5) + '.' + 
                                  numero.substring(5, 8);
            } else if (numero.length === 7) {
                // RUT de 7 dígitos: X.XXX.XXX
                numeroFormateado = numero.substring(0, 1) + '.' + 
                                  numero.substring(1, 4) + '.' + 
                                  numero.substring(4, 7);
            } else {
                numeroFormateado = numero;
            }

            input.value = numeroFormateado + '-' + dv;
        } else if (valor.length === 1 && /[0-9K]/.test(valor)) {
            // Si solo hay un carácter y es válido, dejarlo tal cual
            input.value = valor;
        }
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        // Tabla de Proveedores
        this.tablaProveedores = $('#tabla-proveedores').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles en la tabla",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros coincidentes",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "aria": {
                    "sortAscending": ": activar para ordenar la columna de manera ascendente",
                    "sortDescending": ": activar para ordenar la columna de manera descendente"
                }
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[1, 'asc']], // Ordenar por nombre
            responsive: true,
            columns: [
                { data: 'ID' },
                { data: 'Nombre' },
                { data: 'Contacto' },
                { 
                    data: 'Email',
                    render: (data) => data ? `<a href="mailto:${data}">${data}</a>` : '-'
                },
                { data: 'Telefono' },
                { data: 'RUT' },
                { 
                    data: 'Direccion',
                    render: (data) => data || '-'
                },
                { 
                    data: 'Estado',
                    render: (data) => {
                        const badgeClass = data === 'Activo' ? 'success' : 'secondary';
                        return `<span class="badge bg-${badgeClass}">${data}</span>`;
                    }
                },
                { 
                    data: 'FechaCreacion',
                    render: (data) => {
                        if (!data) return '-';
                        const fecha = new Date(data);
                        return fecha.toLocaleDateString('es-ES', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit'
                        });
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: (data, type, row) => {
                        return `
                            <button class="btn btn-sm btn-info btn-editar-proveedor" data-id="${row.ID}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-eliminar-proveedor" data-id="${row.ID}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                    }
                }
            ],
            data: []
        });

        // Tabla de Comunicaciones
        this.dataTable = $('#tabla-comunicaciones').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles en la tabla",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros coincidentes",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "aria": {
                    "sortAscending": ": activar para ordenar la columna de manera ascendente",
                    "sortDescending": ": activar para ordenar la columna de manera descendente"
                }
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            responsive: true
        });
    }

    cargarProveedores() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: {
                accion: 'obtener_proveedores'
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarProveedores(response.data);
                } else {
                    console.error('Error al cargar proveedores:', response.message);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error de conexión:', error);
            }
        });
    }

    mostrarProveedores(proveedores) {
        if (this.tablaProveedores) {
            this.tablaProveedores.clear();
            this.tablaProveedores.rows.add(proveedores);
            this.tablaProveedores.draw();
        } else {
            console.error('Tabla de proveedores no inicializada');
        }
    }

    seleccionarProveedor(proveedor) {
        this.proveedorSeleccionado = proveedor;
        $('#proveedor-id-comunicacion').val(proveedor.ID);
        $('#form-nueva-comunicacion').show();
        // Aquí se puede cargar el historial de comunicaciones
    }

    abrirModalProveedor() {
        $('#modal-proveedor-title').html('<i class="fas fa-building me-2"></i>Nuevo Proveedor');
        $('#proveedor-id').val('');
        $('#modal-proveedor').modal('show');
    }

    editarProveedor(id) {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: {
                accion: 'obtener_proveedor',
                id: id
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success' && response.data) {
                    const proveedor = response.data;
                    $('#modal-proveedor-title').html('<i class="fas fa-edit me-2"></i>Editar Proveedor');
                    $('#proveedor-id').val(proveedor.ID);
                    $('#nombre-proveedor').val(proveedor.Nombre);
                    $('#contacto-proveedor').val(proveedor.Contacto);
                    $('#email-proveedor').val(proveedor.Email);
                    $('#telefono-proveedor').val(proveedor.Telefono);
                    $('#rut-proveedor').val(proveedor.RUT || '');
                    $('#direccion-proveedor').val(proveedor.Direccion || '');
                    $('#modal-proveedor').modal('show');
                } else {
                    this.mostrarError('Error al cargar los datos del proveedor');
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
            }
        });
    }

    eliminarProveedor(id) {
        if (!confirm('¿Está seguro de que desea eliminar este proveedor?')) {
            return;
        }

        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: {
                accion: 'eliminar_proveedor',
                id: id
            },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarExito('Proveedor eliminado correctamente');
                    this.cargarProveedores();
                } else {
                    this.mostrarError(response.message || 'Error al eliminar el proveedor');
                }
            },
            error: (xhr, status, error) => {
                this.mostrarError('Error de conexión: ' + error);
            }
        });
    }

    limpiarFormulario() {
        $('#form-proveedor')[0].reset();
        $('#proveedor-id').val('');
        $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
        $('.invalid-feedback').text('');
    }

    guardarProveedor() {
        // Validar todos los campos
        const nombre = $('#nombre-proveedor').val().trim();
        const contacto = $('#contacto-proveedor').val().trim();
        const email = $('#email-proveedor').val().trim();
        const telefono = $('#telefono-proveedor').val().trim();
        const rut = $('#rut-proveedor').val().trim();
        const direccion = $('#direccion-proveedor').val().trim();
        const proveedorId = $('#proveedor-id').val();

        // Validar campos obligatorios
        let errores = [];

        if (nombre === '') {
            errores.push('El nombre es obligatorio');
            $('#nombre-proveedor').addClass('is-invalid');
        } else {
            $('#nombre-proveedor').removeClass('is-invalid').addClass('is-valid');
        }

        if (contacto === '') {
            errores.push('El contacto es obligatorio');
            $('#contacto-proveedor').addClass('is-invalid');
        } else {
            $('#contacto-proveedor').removeClass('is-invalid').addClass('is-valid');
        }

        if (!this.validarEmail()) {
            errores.push('El email no es válido');
        }

        if (!this.validarTelefono()) {
            errores.push('El teléfono no es válido');
        }

        if (rut !== '' && !this.validarRUT()) {
            errores.push('El RUT no es válido');
        }

        if (errores.length > 0) {
            const mensajeErrores = 'Por favor, corrija los siguientes errores:\n\n• ' + errores.join('\n• ');
            this.mostrarModalMensaje(mensajeErrores, 'error', 'Error de Validación', 'exclamation-triangle');
            return;
        }

        // Preparar datos
        const datos = {
            accion: proveedorId ? 'actualizar_proveedor' : 'crear_proveedor',
            nombre: nombre,
            contacto: contacto,
            email: email,
            telefono: telefono,
            rut: rut,
            direccion: direccion
        };

        if (proveedorId) {
            datos.id = proveedorId;
        }

        // Deshabilitar botón mientras se procesa
        const btnGuardar = $('#btn-guardar-proveedor');
        const textoOriginal = btnGuardar.html();
        btnGuardar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...');

        // Enviar datos
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                btnGuardar.prop('disabled', false).html(textoOriginal);

                if (response.status === 'success') {
                    // Cerrar modal de proveedor primero
                    const modalProveedor = bootstrap.Modal.getInstance(document.getElementById('modal-proveedor'));
                    if (modalProveedor) {
                        modalProveedor.hide();
                    }
                    // Mostrar mensaje de éxito
                    this.mostrarExito(response.message);
                    // Recargar lista de proveedores
                    this.cargarProveedores();
                } else {
                    // Mostrar error sin cerrar el modal de proveedor
                    this.mostrarError(response.message);
                }
            },
            error: (xhr, status, error) => {
                btnGuardar.prop('disabled', false).html(textoOriginal);
                this.mostrarError('Error de conexión: ' + error);
            }
        });
    }

    enviarComunicacion() {
        console.log('Enviando comunicación...');
        // Implementar envío de comunicación
    }

    mostrarExito(mensaje) {
        this.mostrarModalMensaje(mensaje, 'success', 'Éxito', 'check-circle');
    }

    mostrarError(mensaje) {
        // Detectar si es error de duplicado
        let tipo = 'error';
        let titulo = 'Error';
        let icono = 'exclamation-triangle';
        
        if (mensaje.toLowerCase().includes('duplicado') || 
            mensaje.toLowerCase().includes('ya está registrado') ||
            mensaje.toLowerCase().includes('ya existe')) {
            tipo = 'warning';
            titulo = 'Dato Duplicado';
            icono = 'exclamation-circle';
        }
        
        this.mostrarModalMensaje(mensaje, tipo, titulo, icono);
    }

    mostrarModalMensaje(mensaje, tipo = 'info', titulo = 'Información', icono = 'info-circle') {
        const modal = $('#modal-mensaje');
        const modalHeader = $('#modal-mensaje-header');
        const modalTitle = $('#modal-mensaje-title');
        const modalTituloTexto = $('#modal-mensaje-titulo-texto');
        const modalIcono = $('#modal-mensaje-icono');
        const modalTexto = $('#modal-mensaje-texto');
        const modalBootstrap = new bootstrap.Modal(modal[0]);

        // Remover clases anteriores
        modalHeader.removeClass('bg-success bg-danger bg-warning bg-info text-white');
        modalIcono.removeClass('fa-check-circle fa-exclamation-triangle fa-exclamation-circle fa-info-circle');

        // Aplicar estilos según el tipo
        switch (tipo) {
            case 'success':
                modalHeader.addClass('bg-success text-white');
                modalIcono.addClass('fa-check-circle');
                break;
            case 'error':
                modalHeader.addClass('bg-danger text-white');
                modalIcono.addClass('fa-exclamation-triangle');
                break;
            case 'warning':
                modalHeader.addClass('bg-warning text-dark');
                modalIcono.addClass('fa-exclamation-circle');
                break;
            default:
                modalHeader.addClass('bg-info text-white');
                modalIcono.addClass('fa-info-circle');
        }

        // Configurar contenido
        modalTituloTexto.text(titulo);
        modalTexto.text(mensaje);

        // Mostrar modal
        modalBootstrap.show();

        // Cerrar automáticamente después de 5 segundos si es éxito
        if (tipo === 'success') {
            setTimeout(() => {
                modalBootstrap.hide();
            }, 5000);
        }
    }
}

// Inicializar cuando el DOM esté listo
let comunicacionProveedores;
document.addEventListener('DOMContentLoaded', () => {
    comunicacionProveedores = new ComunicacionProveedores();
});
