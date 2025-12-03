// Aplicación para Seguimiento de Ingresos de Repuestos
class SeguimientoIngresosRepuestos {
    constructor() {
        this.baseUrl = this.getBaseUrl();
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
        this.cargarHistorial();
    }

    inicializarEventos() {
        $('#btn-refresh').on('click', () => {
            this.cargarHistorial();
        });

        $('#btn-filtrar').on('click', () => {
            this.cargarHistorial();
        });
    }

    cargarHistorial() {
        const tipo = $('#filtro-tipo').val();
        const fechaDesde = $('#filtro-fecha-desde').val();
        const fechaHasta = $('#filtro-fecha-hasta').val();

        const datos = {
            accion: 'obtener_historial',
            tipo: tipo || '',
            fecha_desde: fechaDesde || '',
            fecha_hasta: fechaHasta || ''
        };

        $.ajax({
            url: this.baseUrl,
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: (response) => {
                if (response.status === 'success') {
                    this.mostrarHistorial(response.data);
                } else {
                    console.error('Error al cargar historial:', response.message);
                    $('#tabla-historial tbody').html('<tr><td colspan="6" class="text-center text-danger">Error al cargar el historial</td></tr>');
                }
            },
            error: (xhr, status, error) => {
                console.error('Error al cargar historial:', error);
                $('#tabla-historial tbody').html('<tr><td colspan="6" class="text-center text-danger">Error de conexión</td></tr>');
            }
        });
    }

    mostrarHistorial(datos) {
        const tbody = $('#tabla-historial tbody');
        tbody.empty();

        if (!datos || datos.length === 0) {
            tbody.html('<tr><td colspan="6" class="text-center text-muted">No hay registros disponibles</td></tr>');
            return;
        }

        // Aplicar filtros adicionales si es necesario
        let datosFiltrados = datos;
        
        const tipoFiltro = $('#filtro-tipo').val();
        if (tipoFiltro) {
            datosFiltrados = datosFiltrados.filter(item => item.Tipo === tipoFiltro);
        }

        const fechaDesde = $('#filtro-fecha-desde').val();
        const fechaHasta = $('#filtro-fecha-hasta').val();
        
        if (fechaDesde) {
            datosFiltrados = datosFiltrados.filter(item => {
                const fechaItem = new Date(item.Fecha);
                return fechaItem >= new Date(fechaDesde);
            });
        }

        if (fechaHasta) {
            datosFiltrados = datosFiltrados.filter(item => {
                const fechaItem = new Date(item.Fecha);
                const hasta = new Date(fechaHasta);
                hasta.setHours(23, 59, 59, 999); // Incluir todo el día
                return fechaItem <= hasta;
            });
        }

        // Ordenar por fecha descendente
        datosFiltrados.sort((a, b) => {
            const fechaA = new Date(a.Fecha);
            const fechaB = new Date(b.Fecha);
            return fechaB - fechaA;
        });

        datosFiltrados.forEach((registro) => {
            const badgeClass = registro.Tipo === 'Recepción' ? 'success' : 'info';
            const badge = `<span class="badge bg-${badgeClass}">${registro.Tipo}</span>`;
            
            const row = `
                <tr>
                    <td>${registro.Fecha || '-'}</td>
                    <td>${badge}</td>
                    <td>${registro.ProveedorVehiculo || '-'}</td>
                    <td>${registro.CantidadTotal || '-'}</td>
                    <td>${registro.UsuarioNombre || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary btn-ver-detalle" 
                                data-id="${registro.ID}" 
                                data-tipo="${registro.Tipo}"
                                data-proveedor="${registro.ProveedorVehiculo || ''}"
                                data-repuestos="${registro.Repuestos || ''}"
                                data-cantidad="${registro.CantidadTotal || ''}"
                                data-fecha="${registro.Fecha || ''}"
                                data-usuario="${registro.UsuarioNombre || ''}">
                            <i class="fas fa-eye"></i> Ver Detalles
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        // Event listener para ver detalles (usar delegación de eventos)
        $(document).off('click', '.btn-ver-detalle').on('click', '.btn-ver-detalle', (e) => {
            const btn = $(e.currentTarget);
            this.mostrarDetallesModal({
                id: btn.data('id'),
                tipo: btn.data('tipo'),
                proveedor: btn.data('proveedor'),
                repuestos: btn.data('repuestos'),
                cantidad: btn.data('cantidad'),
                fecha: btn.data('fecha'),
                usuario: btn.data('usuario')
            });
        });
    }

    mostrarDetallesModal(registro) {
        const modalBody = $('#modal-detalles-historial-body');
        let html = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Fecha:</strong><br>
                    <span>${registro.fecha || '-'}</span>
                </div>
                <div class="col-md-6">
                    <strong>Tipo:</strong><br>
                    <span class="badge bg-${registro.tipo === 'Recepción' ? 'success' : 'info'}">${registro.tipo}</span>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>${registro.tipo === 'Recepción' ? 'Proveedor' : 'Vehículo'}:</strong><br>
                    <span>${registro.proveedor || '-'}</span>
                </div>
                <div class="col-md-6">
                    <strong>Cantidad Total:</strong><br>
                    <span>${registro.cantidad || '-'}</span>
                </div>
            </div>
            <div class="mb-3">
                <strong>Usuario:</strong><br>
                <span>${registro.usuario || '-'}</span>
            </div>
            <div class="mb-3">
                <strong>Repuestos:</strong><br>
                <div class="list-group mt-2" style="max-height: 300px; overflow-y: auto;">
        `;

        if (registro.repuestos) {
            const repuestosList = registro.repuestos.split(', ');
            repuestosList.forEach((repuesto) => {
                html += `<div class="list-group-item">${repuesto}</div>`;
            });
        } else {
            html += '<div class="list-group-item text-muted">No hay repuestos registrados</div>';
        }

        html += `
                </div>
            </div>
        `;

        modalBody.html(html);
        const modal = new bootstrap.Modal(document.getElementById('modal-detalles-historial'));
        modal.show();
    }
}

// Inicializar cuando el DOM esté listo
let seguimientoIngresos;
document.addEventListener('DOMContentLoaded', () => {
    seguimientoIngresos = new SeguimientoIngresosRepuestos();
});

