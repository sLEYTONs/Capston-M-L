// Aplicación para Recepción y Entrega de Repuestos
class RecepcionEntregaRepuestos {
    constructor() {
        this.dataTable = null;
        this.baseUrl = this.getBaseUrl();
        this.proveedores = [];
        this.vehiculos = [];
        this.mecanicos = [];
        this.repuestos = [];
        this.repuestosSeleccionadosRecepcion = [];
        this.repuestosSeleccionadosEntrega = [];
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
        this.inicializarDataTable();
        this.cargarDatos();
    }

    inicializarEventos() {
        $('#form-recepcion').on('submit', (e) => {
            e.preventDefault();
            this.registrarRecepcion();
        });

        $('#form-entrega').on('submit', (e) => {
            e.preventDefault();
            this.registrarEntrega();
        });
    }

    inicializarDataTable() {
        if (typeof $.fn.DataTable === 'undefined') {
            console.error('DataTables no está cargado');
            return;
        }

        this.dataTable = $('#tabla-historial').DataTable({
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
            responsive: true,
            columns: [
                { data: 'Fecha' },
                { 
                    data: 'Tipo',
                    render: (data) => {
                        const badgeClass = data === 'Recepción' ? 'success' : 'info';
                        return `<span class="badge bg-${badgeClass}">${data}</span>`;
                    }
                },
                { data: 'ProveedorVehiculo' },
                { data: 'Repuestos' },
                { data: 'CantidadTotal' },
                { 
                    data: 'UsuarioNombre',
                    render: (data) => data || '-'
                },
                {
                    data: null,
                    orderable: false,
                    render: () => '<button class="btn btn-sm btn-outline-primary btn-ver-detalle"><i class="fas fa-eye"></i></button>'
                }
            ]
        });
    }

    cargarDatos() {
        this.cargarProveedores();
        this.cargarVehiculos();
        this.cargarMecanicos();
        this.cargarRepuestos();
        this.cargarHistorial();
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

    cargarVehiculos() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_vehiculos' },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.vehiculos = response.data;
                    this.mostrarVehiculos(response.data);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar vehículos:', error);
            }
        });
    }

    mostrarVehiculos(vehiculos) {
        const select = $('#vehiculo');
        select.empty().append('<option value="">Seleccionar vehículo...</option>');
        
        vehiculos.forEach((vehiculo) => {
            select.append(`<option value="${vehiculo.ID}">${vehiculo.Placa} - ${vehiculo.Marca} ${vehiculo.Modelo}</option>`);
        });
    }

    cargarMecanicos() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_mecanicos' },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mecanicos = response.data;
                    this.mostrarMecanicos(response.data);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar mecánicos:', error);
            }
        });
    }

    mostrarMecanicos(mecanicos) {
        const select = $('#mecanico');
        select.empty().append('<option value="">Seleccionar mecánico...</option>');
        
        mecanicos.forEach((mecanico) => {
            select.append(`<option value="${mecanico.UsuarioID}">${mecanico.NombreUsuario}</option>`);
        });
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
                    this.mostrarRepuestosRecepcion(response.data);
                    this.mostrarRepuestosEntrega(response.data);
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar repuestos:', error);
            }
        });
    }

    mostrarRepuestosRecepcion(repuestos) {
        const container = $('#lista-repuestos-recepcion');
        container.empty();
        
        if (repuestos.length === 0) {
            container.html('<p class="text-muted">No hay repuestos disponibles</p>');
            return;
        }
        
        let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
        html += '<thead><tr><th>Repuesto</th><th>Cantidad</th><th>Precio Unit.</th><th>Acción</th></tr></thead><tbody>';
        
        repuestos.forEach((repuesto) => {
            html += `
                <tr>
                    <td>${repuesto.Nombre} (Stock: ${repuesto.Stock})</td>
                    <td>
                        <input type="number" class="form-control form-control-sm cantidad-recepcion" 
                               data-id="${repuesto.ID}" min="1" value="1" style="width: 80px;">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm precio-recepcion" 
                               data-id="${repuesto.ID}" min="0" step="0.01" placeholder="0.00" style="width: 100px;">
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary btn-agregar-recepcion" 
                                data-id="${repuesto.ID}" data-nombre="${repuesto.Nombre}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        html += '<div id="repuestos-seleccionados-recepcion" class="mt-3"></div>';
        
        container.html(html);
        
        // Event listeners para agregar repuestos
        $('.btn-agregar-recepcion').on('click', (e) => {
            const repuestoId = $(e.currentTarget).data('id');
            const repuestoNombre = $(e.currentTarget).data('nombre');
            const cantidad = parseInt($(`.cantidad-recepcion[data-id="${repuestoId}"]`).val()) || 1;
            const precio = parseFloat($(`.precio-recepcion[data-id="${repuestoId}"]`).val()) || null;
            
            this.agregarRepuestoRecepcion(repuestoId, repuestoNombre, cantidad, precio);
        });
    }

    mostrarRepuestosEntrega(repuestos) {
        const container = $('#lista-repuestos-entrega');
        container.empty();
        
        if (repuestos.length === 0) {
            container.html('<p class="text-muted">No hay repuestos disponibles</p>');
            return;
        }
        
        let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
        html += '<thead><tr><th>Repuesto</th><th>Stock</th><th>Cantidad</th><th>Acción</th></tr></thead><tbody>';
        
        repuestos.forEach((repuesto) => {
            const stockClass = repuesto.Stock <= repuesto.StockMinimo ? 'text-danger' : 'text-success';
            html += `
                <tr>
                    <td>${repuesto.Nombre}</td>
                    <td><span class="${stockClass}">${repuesto.Stock}</span></td>
                    <td>
                        <input type="number" class="form-control form-control-sm cantidad-entrega" 
                               data-id="${repuesto.ID}" min="1" max="${repuesto.Stock}" value="1" style="width: 80px;">
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-success btn-agregar-entrega" 
                                data-id="${repuesto.ID}" data-nombre="${repuesto.Nombre}" data-stock="${repuesto.Stock}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        html += '<div id="repuestos-seleccionados-entrega" class="mt-3"></div>';
        
        container.html(html);
        
        // Event listeners para agregar repuestos
        $('.btn-agregar-entrega').on('click', (e) => {
            const repuestoId = $(e.currentTarget).data('id');
            const repuestoNombre = $(e.currentTarget).data('nombre');
            const stock = parseInt($(e.currentTarget).data('stock'));
            const cantidad = parseInt($(`.cantidad-entrega[data-id="${repuestoId}"]`).val()) || 1;
            
            if (cantidad > stock) {
                alert(`La cantidad solicitada (${cantidad}) excede el stock disponible (${stock})`);
                return;
            }
            
            this.agregarRepuestoEntrega(repuestoId, repuestoNombre, cantidad);
        });
    }

    agregarRepuestoRecepcion(id, nombre, cantidad, precio) {
        const index = this.repuestosSeleccionadosRecepcion.findIndex(r => r.id === id);
        
        if (index >= 0) {
            this.repuestosSeleccionadosRecepcion[index].cantidad += cantidad;
            if (precio) {
                this.repuestosSeleccionadosRecepcion[index].precio = precio;
            }
        } else {
            this.repuestosSeleccionadosRecepcion.push({
                id: id,
                nombre: nombre,
                cantidad: cantidad,
                precio: precio
            });
        }
        
        this.actualizarListaRepuestosRecepcion();
    }

    agregarRepuestoEntrega(id, nombre, cantidad) {
        const index = this.repuestosSeleccionadosEntrega.findIndex(r => r.id === id);
        
        if (index >= 0) {
            this.repuestosSeleccionadosEntrega[index].cantidad += cantidad;
        } else {
            this.repuestosSeleccionadosEntrega.push({
                id: id,
                nombre: nombre,
                cantidad: cantidad
            });
        }
        
        this.actualizarListaRepuestosEntrega();
    }

    actualizarListaRepuestosRecepcion() {
        const container = $('#repuestos-seleccionados-recepcion');
        
        if (this.repuestosSeleccionadosRecepcion.length === 0) {
            container.empty();
            return;
        }
        
        let html = '<div class="alert alert-info"><strong>Repuestos seleccionados:</strong><ul class="mb-0 mt-2">';
        
        this.repuestosSeleccionadosRecepcion.forEach((repuesto, index) => {
            html += `
                <li>
                    ${repuesto.nombre} - Cantidad: ${repuesto.cantidad}
                    ${repuesto.precio ? ` - Precio: $${repuesto.precio.toFixed(2)}` : ''}
                    <button type="button" class="btn btn-sm btn-danger ms-2 btn-eliminar-recepcion" data-index="${index}">
                        <i class="fas fa-times"></i>
                    </button>
                </li>
            `;
        });
        
        html += '</ul></div>';
        container.html(html);
        
        $('.btn-eliminar-recepcion').on('click', (e) => {
            const index = parseInt($(e.currentTarget).data('index'));
            this.repuestosSeleccionadosRecepcion.splice(index, 1);
            this.actualizarListaRepuestosRecepcion();
        });
    }

    actualizarListaRepuestosEntrega() {
        const container = $('#repuestos-seleccionados-entrega');
        
        if (this.repuestosSeleccionadosEntrega.length === 0) {
            container.empty();
            return;
        }
        
        let html = '<div class="alert alert-success"><strong>Repuestos seleccionados:</strong><ul class="mb-0 mt-2">';
        
        this.repuestosSeleccionadosEntrega.forEach((repuesto, index) => {
            html += `
                <li>
                    ${repuesto.nombre} - Cantidad: ${repuesto.cantidad}
                    <button type="button" class="btn btn-sm btn-danger ms-2 btn-eliminar-entrega" data-index="${index}">
                        <i class="fas fa-times"></i>
                    </button>
                </li>
            `;
        });
        
        html += '</ul></div>';
        container.html(html);
        
        $('.btn-eliminar-entrega').on('click', (e) => {
            const index = parseInt($(e.currentTarget).data('index'));
            this.repuestosSeleccionadosEntrega.splice(index, 1);
            this.actualizarListaRepuestosEntrega();
        });
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
                    alert('Recepción registrada correctamente');
                    $('#form-recepcion')[0].reset();
                    this.repuestosSeleccionadosRecepcion = [];
                    this.actualizarListaRepuestosRecepcion();
                    this.cargarHistorial();
                    this.cargarRepuestos(); // Recargar para actualizar stock
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                alert('Error de conexión: ' + error);
            }
        });
    }

    registrarEntrega() {
        const vehiculoId = $('#vehiculo').val();
        const mecanicoId = $('#mecanico').val();
        const fechaEntrega = new Date().toISOString().slice(0, 16);
        const observaciones = $('#observaciones-entrega').val().trim();
        
        if (!vehiculoId || !mecanicoId) {
            alert('Por favor, complete todos los campos obligatorios');
            return;
        }
        
        if (this.repuestosSeleccionadosEntrega.length === 0) {
            alert('Debe seleccionar al menos un repuesto');
            return;
        }
        
        const datos = {
            accion: 'registrar_entrega',
            vehiculo_id: vehiculoId,
            mecanico_id: mecanicoId,
            fecha_entrega: fechaEntrega,
            observaciones: observaciones,
            repuestos: JSON.stringify(this.repuestosSeleccionadosEntrega)
        };
        
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    alert('Entrega registrada correctamente');
                    $('#form-entrega')[0].reset();
                    this.repuestosSeleccionadosEntrega = [];
                    this.actualizarListaRepuestosEntrega();
                    this.cargarHistorial();
                    this.cargarRepuestos(); // Recargar para actualizar stock
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: (xhr, status, error) => {
                alert('Error de conexión: ' + error);
            }
        });
    }

    cargarHistorial() {
        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: { accion: 'obtener_historial' },
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    if (this.dataTable) {
                        this.dataTable.clear().rows.add(response.data).draw();
                    }
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar historial:', error);
            }
        });
    }
}

// Inicializar cuando el DOM esté listo
let recepcionEntrega;
document.addEventListener('DOMContentLoaded', () => {
    recepcionEntrega = new RecepcionEntregaRepuestos();
});
