<!-- Sección de Comunicación con Proveedores -->
<section id="comunicacion-proveedores-section" class="section">
    <div class="container-fluid">
        <div class="row">
            <!-- Lista de Proveedores -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-building me-2"></i>
                            Lista de Proveedores
                        </h4>
                        <button class="btn btn-primary btn-sm" id="btn-nuevo-proveedor">
                            <i class="fas fa-plus me-1"></i> Nuevo Proveedor
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabla-proveedores" class="table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Contacto</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>RUT</th>
                                        <th>Dirección</th>
                                        <th>Estado</th>
                                        <th>Fecha Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Se cargarán dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área de Comunicación -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-comments me-2"></i>
                            Comunicación
                        </h4>
                    </div>
                    <div class="card-body">
                        <div id="area-comunicacion" class="comunicacion-container">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Seleccione un proveedor para ver el historial de comunicación
                            </div>
                        </div>

                        <!-- Formulario de Nueva Comunicación -->
                        <div class="mt-4" id="form-nueva-comunicacion" style="display: none;">
                            <hr>
                            <h5>Nueva Comunicación</h5>
                            <form id="form-comunicacion">
                                <input type="hidden" id="proveedor-id-comunicacion">
                                <div class="mb-3">
                                    <label for="tipo-comunicacion" class="form-label">Tipo de Comunicación *</label>
                                    <select class="form-select" id="tipo-comunicacion" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="solicitud">Solicitud de Cotización</option>
                                        <option value="pedido">Realizar Pedido</option>
                                        <option value="consulta">Consulta General</option>
                                        <option value="reclamo">Reclamo</option>
                                        <option value="seguimiento">Seguimiento de Pedido</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="asunto" class="form-label">Asunto *</label>
                                    <input type="text" class="form-control" id="asunto" required>
                                </div>
                                <div class="mb-3">
                                    <label for="mensaje" class="form-label">Mensaje *</label>
                                    <textarea class="form-control" id="mensaje" rows="5" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="archivos" class="form-label">Archivos Adjuntos</label>
                                    <input type="file" class="form-control" id="archivos" multiple>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar Comunicación
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historial de Comunicaciones -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-history me-2"></i>
                            Historial de Comunicaciones
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabla-comunicaciones" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Proveedor</th>
                                        <th>Tipo</th>
                                        <th>Asunto</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Se cargarán dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal para Nuevo Proveedor -->
<div class="modal fade" id="modal-proveedor" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-proveedor-title">
                    <i class="fas fa-building me-2"></i>
                    Nuevo Proveedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-proveedor">
                    <input type="hidden" id="proveedor-id">
                    <div class="mb-3">
                        <label for="nombre-proveedor" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="nombre-proveedor" required>
                    </div>
                    <div class="mb-3">
                        <label for="contacto-proveedor" class="form-label">Contacto *</label>
                        <input type="text" class="form-control" id="contacto-proveedor" required>
                    </div>
                    <div class="mb-3">
                        <label for="email-proveedor" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email-proveedor" required>
                        <div class="invalid-feedback" id="error-email"></div>
                    </div>
                    <div class="mb-3">
                        <label for="telefono-proveedor" class="form-label">Teléfono *</label>
                        <input type="tel" class="form-control" id="telefono-proveedor" 
                               placeholder="+56 9 1234 5678 o 912345678" 
                               pattern="(\+?56\s?)?[2-9]\d{7,8}" required>
                        <small class="form-text text-muted">Formato chileno: +56 9 1234 5678 (móvil) o +56 2 2123 4567 (fijo)</small>
                        <div class="invalid-feedback" id="error-telefono"></div>
                    </div>
                    <div class="mb-3">
                        <label for="rut-proveedor" class="form-label">RUT</label>
                        <input type="text" class="form-control" id="rut-proveedor" 
                               placeholder="12.345.678-9 o 1.234.567-8">
                        <small class="form-text text-muted">Formato: 12.345.678-9 (8 dígitos) o 1.234.567-8 (7 dígitos) - Opcional</small>
                        <div class="invalid-feedback" id="error-rut"></div>
                    </div>
                    <div class="mb-3">
                        <label for="direccion-proveedor" class="form-label">Dirección</label>
                        <textarea class="form-control" id="direccion-proveedor" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-proveedor">
                    <i class="fas fa-save me-2"></i>Guardar Proveedor
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Mensajes de Resultado -->
<div class="modal fade" id="modal-mensaje" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="modal-mensaje-header">
                <h5 class="modal-title" id="modal-mensaje-title">
                    <i class="fas fa-info-circle me-2" id="modal-mensaje-icono"></i>
                    <span id="modal-mensaje-titulo-texto">Información</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modal-mensaje-texto" class="mb-0" style="white-space: pre-line;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="fas fa-check me-2"></i>Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

