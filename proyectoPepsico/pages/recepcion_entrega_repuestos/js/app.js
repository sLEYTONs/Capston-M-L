// Aplicación para Recepción de Repuestos
class RecepcionRepuestos {
    constructor() {
        this.baseUrl = this.getBaseUrl();
        this.proveedores = [];
        this.repuestos = [];
        this.repuestosSeleccionadosRecepcion = [];
        this.inicializar();
    }

    getBaseUrl() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('/pages/')) {
            const basePath = currentPath.substring(0, currentPath.indexOf('/pages/'));
            return basePath + '/app/model/recepcion_entrega_repuestos/scripts/s_recepcion_entrega.php';
        }
        return '../../app/model/recepcion_entrega_repuestos/scripts/s_recepcion_entrega.php';
    }

    inicializar() {
        this.inicializarEventos();
        this.cargarDatos();
        // Ya no cargamos el historial aquí, se movió a seguimiento_ingresos_repuestos.php
    }

    inicializarEventos() {
        $('#form-recepcion').on('submit', (e) => {
            e.preventDefault();
            this.registrarRecepcion();
        });

        // Evento para abrir modal de repuestos
        $('#btn-abrir-modal-repuestos').on('click', () => {
            this.cargarRepuestosModal();
        });

        // Evento para agregar repuesto desde el modal
        $('#btn-agregar-repuesto-modal').on('click', () => {
            this.agregarRepuestoDesdeModal();
        });

        // Evento cuando se cierra el modal
        $('#modal-seleccionar-repuestos').on('hidden.bs.modal', () => {
            // Limpiar campos pero mantener los repuestos seleccionados
            $('#select-repuesto-modal').val('');
            $('#cantidad-repuesto-modal').val(1);
        });
    }


    cargarDatos() {
        this.cargarProveedores();
        this.cargarRepuestos();
    }

    cargarProveedores() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_proveedores' },
            dataType: 'json',
            success: (response) => {
                console.log('Respuesta de proveedores:', response);
                if (response.status === 'success') {
                    this.proveedores = response.data || [];
                    this.mostrarProveedores(this.proveedores);
                } else {
                    console.error('Error al cargar proveedores:', response.message);
                    $('#proveedor').html('<option value="">Error al cargar proveedores</option>');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error de conexión al cargar proveedores:', error);
                console.error('Respuesta del servidor:', xhr.responseText);
                $('#proveedor').html('<option value="">Error de conexión</option>');
            }
        });
    }

    mostrarProveedores(proveedores) {
        const select = $('#proveedor');
        select.empty().append('<option value="">Seleccionar proveedor...</option>');
        
        if (!proveedores || proveedores.length === 0) {
            console.warn('No se encontraron proveedores');
            return;
        }
        
        proveedores.forEach((proveedor) => {
            const estado = proveedor.Estado === 'Activo' ? '' : ' (Inactivo)';
            select.append(`<option value="${proveedor.ID}">${proveedor.Nombre}${estado}</option>`);
        });
        
        console.log(`Se cargaron ${proveedores.length} proveedores en el select`);
    }

    cargarRepuestos() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_repuestos' },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.repuestos = response.data;
                    this.actualizarListaRepuestosRecepcion();
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar repuestos:', error);
            }
        });
    }

    cargarRepuestosModal() {
        const select = $('#select-repuesto-modal');
        select.html('<option value="">Cargando repuestos...</option>').prop('disabled', true);

        // Si ya tenemos los repuestos cargados, usarlos directamente
        if (this.repuestos && this.repuestos.length > 0) {
            this.mostrarRepuestosEnSelect(this.repuestos);
            return;
        }

        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_repuestos' },
            dataType: 'json',
            success: (response) => {
                console.log('Respuesta de repuestos:', response);
                if (response.status === 'success' && response.data) {
                    this.repuestos = response.data;
                    console.log('Repuestos cargados:', this.repuestos.length);
                    this.mostrarRepuestosEnSelect(response.data);
                } else {
                    console.error('Error en respuesta:', response);
                    select.html('<option value="">No hay repuestos disponibles</option>').prop('disabled', false);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar repuestos:', error);
                console.error('Respuesta del servidor:', xhr.responseText);
                select.html('<option value="">No se pudieron cargar los repuestos</option>').prop('disabled', false);
            }
        });
    }

    mostrarRepuestosEnSelect(repuestos) {
        const select = $('#select-repuesto-modal');
        select.empty().append('<option value="">Seleccionar repuesto...</option>').prop('disabled', false);

        if (!repuestos || repuestos.length === 0) {
            select.append('<option value="">No hay repuestos disponibles</option>');
            return;
        }

        // Filtrar repuestos que ya están seleccionados
        const repuestosDisponibles = repuestos.filter(repuesto => {
            return !this.repuestosSeleccionadosRecepcion.some(sel => sel.id == repuesto.ID);
        });

        if (repuestosDisponibles.length === 0) {
            select.append('<option value="">Todos los repuestos ya han sido agregados</option>');
            return;
        }

        repuestosDisponibles.forEach((repuesto) => {
            const nombre = repuesto.Nombre ? repuesto.Nombre.trim() : 'Sin nombre';
            select.append(`<option value="${repuesto.ID}" data-nombre="${nombre}">${nombre}</option>`);
        });

        this.actualizarListaRepuestosModal();
    }

    agregarRepuestoDesdeModal() {
        const repuestoId = $('#select-repuesto-modal').val();
        const repuestoOption = $('#select-repuesto-modal option:selected');
        const cantidad = parseInt($('#cantidad-repuesto-modal').val()) || 1;

        if (!repuestoId) {
            alert('Por favor, seleccione un repuesto');
            return;
        }

        if (cantidad <= 0) {
            alert('La cantidad debe ser mayor a 0');
            return;
        }

        const repuestoNombre = repuestoOption.data('nombre') || repuestoOption.text();
        
        // Agregar a la lista de recepción (sin precio)
        this.agregarRepuestoRecepcion(repuestoId, repuestoNombre, cantidad, 0);
        
        // Remover del select
        repuestoOption.remove();
        $('#select-repuesto-modal').val('');
        $('#cantidad-repuesto-modal').val(1);
        
        // Actualizar lista en el modal
        this.actualizarListaRepuestosModal();
    }

    actualizarListaRepuestosModal() {
        const container = $('#lista-repuestos-agregados-modal');
        
        if (this.repuestosSeleccionadosRecepcion.length === 0) {
            container.html('<p class="text-muted text-center py-3">No hay repuestos agregados</p>');
            return;
        }

        let html = '';
        let total = 0;

        this.repuestosSeleccionadosRecepcion.forEach((repuesto, index) => {
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <strong>${repuesto.nombre}</strong><br>
                        <small class="text-muted">
                            Cantidad: ${repuesto.cantidad}
                        </small>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger btn-eliminar-modal" data-index="${index}" title="Eliminar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        });

        container.html(html);

        // Event listeners para eliminar repuestos desde el modal
        $('.btn-eliminar-modal').on('click', (e) => {
            const index = parseInt($(e.currentTarget).data('index'));
            
            // Remover de la lista
            this.repuestosSeleccionadosRecepcion.splice(index, 1);
            
            // Agregar de vuelta al select
            this.mostrarRepuestosEnSelect(this.repuestos);
            
            // Actualizar lista en el modal y contador del formulario
            this.actualizarListaRepuestosModal();
            this.actualizarListaRepuestosRecepcion();
        });
    }


    agregarRepuestoRecepcion(id, nombre, cantidad, precio) {
        const index = this.repuestosSeleccionadosRecepcion.findIndex(r => r.id == id);
        
        if (index >= 0) {
            // Si ya existe, sumar la cantidad
            this.repuestosSeleccionadosRecepcion[index].cantidad += cantidad;
            // Actualizar precio si se proporciona
            if (precio && precio > 0) {
                this.repuestosSeleccionadosRecepcion[index].precio = precio;
            }
        } else {
            // Si no existe, agregarlo
            this.repuestosSeleccionadosRecepcion.push({
                id: parseInt(id),
                nombre: nombre,
                cantidad: cantidad,
                precio: precio || 0
            });
        }
        
        // Actualizar ambas listas
        this.actualizarListaRepuestosRecepcion();
        this.actualizarListaRepuestosModal();
    }


    actualizarListaRepuestosRecepcion() {
        // Solo mostrar un contador pequeño en el formulario principal
        const contador = $('#contador-repuestos');
        const totalRepuestos = this.repuestosSeleccionadosRecepcion.length;
        
        if (totalRepuestos === 0) {
            contador.text('No hay repuestos agregados').removeClass('text-success').addClass('text-muted');
        } else {
            const totalCantidad = this.repuestosSeleccionadosRecepcion.reduce((sum, r) => sum + r.cantidad, 0);
            const texto = `${totalRepuestos} repuesto(s) agregado(s) - Total cantidad: ${totalCantidad}`;
            contador.text(texto).removeClass('text-muted').addClass('text-success');
        }
        
        // Actualizar también la lista del modal
        this.actualizarListaRepuestosModal();
    }


    registrarRecepcion() {
        const proveedorId = $('#proveedor').val();
        const numeroFactura = $('#factura').val().trim();
        const fechaRecepcion = $('#fecha-recepcion').val();
        const observaciones = $('#observaciones').val().trim();
        
        if (!proveedorId || !numeroFactura || !fechaRecepcion) {
            alert('Por favor, complete todos los campos obligatorios');
            return;
        }
        
        if (this.repuestosSeleccionadosRecepcion.length === 0) {
            alert('Debe seleccionar al menos un repuesto');
            return;
        }
        
        const datos = {
            accion: 'registrar_recepcion',
            proveedor_id: proveedorId,
            numero_factura: numeroFactura,
            fecha_recepcion: fechaRecepcion,
            observaciones: observaciones,
            repuestos: JSON.stringify(this.repuestosSeleccionadosRecepcion)
        };
        
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    // Mostrar modal de éxito
                    $('#mensaje-exito-recepcion').text(response.message || 'Los repuestos han sido registrados y el stock ha sido actualizado.');
                    const modalExito = new bootstrap.Modal(document.getElementById('modal-exito-recepcion'));
                    modalExito.show();
                    
                    // Limpiar formulario
                    $('#form-recepcion')[0].reset();
                    this.repuestosSeleccionadosRecepcion = [];
                    this.actualizarListaRepuestosRecepcion();
                    // Ya no cargamos el historial aquí, se movió a seguimiento_ingresos_repuestos.php
                    this.cargarRepuestos(); // Recargar para actualizar stock
                    
                    // Cerrar modal de selección de repuestos si está abierto
                    const modalRepuestos = bootstrap.Modal.getInstance(document.getElementById('modal-seleccionar-repuestos'));
                    if (modalRepuestos) {
                        modalRepuestos.hide();
                    }
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                alert('Error de conexión: ' + error);
            }
        });
    }


}

// Inicializar cuando el DOM esté listo
let recepcionRepuestos;
document.addEventListener('DOMContentLoaded', () => {
    recepcionRepuestos = new RecepcionRepuestos();
});
