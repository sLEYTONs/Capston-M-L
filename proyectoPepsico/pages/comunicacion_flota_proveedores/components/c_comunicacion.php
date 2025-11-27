<?php
// Obtener rol del usuario para controlar visibilidad
$usuario_rol = $_SESSION['usuario']['rol'] ?? '';
?>
<div class="row">
    <!-- Tabs para Flota y Proveedores -->
    <div class="col-md-12 mb-4">
        <ul class="nav nav-tabs" id="tabsComunicacion" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-flota" data-bs-toggle="tab" data-bs-target="#panel-flota" 
                        type="button" role="tab" aria-controls="panel-flota" aria-selected="true">
                    <i class="fas fa-truck me-2"></i>Comunicación con Flota
                </button>
            </li>
            <?php if ($usuario_rol !== 'Chofer'): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-proveedores" data-bs-toggle="tab" data-bs-target="#panel-proveedores" 
                        type="button" role="tab" aria-controls="panel-proveedores" aria-selected="false">
                    <i class="fas fa-boxes me-2"></i>Comunicación con Proveedores
                </button>
            </li>
            <?php endif; ?>
        </ul>

        <div class="tab-content" id="tabContentComunicacion">
            <!-- Panel Flota -->
            <div class="tab-pane fade show active" id="panel-flota" role="tabpanel" aria-labelledby="tab-flota">
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Gestión de Comunicación con Flota</h5>
                            <?php if ($usuario_rol !== 'Chofer'): ?>
                            <button class="btn btn-primary btn-sm" id="btn-nueva-comunicacion-flota" data-bs-toggle="modal" data-bs-target="#modalNuevaComunicacionFlota">
                                <i class="fas fa-plus me-2"></i>Nueva Comunicación
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filtros -->
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <label for="filtro-placa-flota" class="form-label">Buscar por Placa</label>
                                <input type="text" class="form-control" id="filtro-placa-flota" placeholder="Ej: ABCD60">
                            </div>
                            <?php if ($usuario_rol !== 'Chofer'): ?>
                            <div class="col-md-2">
                                <label for="filtro-conductor-flota" class="form-label">Buscar por Conductor</label>
                                <input type="text" class="form-control" id="filtro-conductor-flota" placeholder="Nombre del conductor">
                            </div>
                            <?php else: ?>
                            <input type="hidden" id="filtro-conductor-flota" value="<?php echo htmlspecialchars($usuario_actual ?? ''); ?>">
                            <?php endif; ?>
                            <div class="col-md-<?php echo $usuario_rol === 'Chofer' ? '3' : '2'; ?>">
                                <label for="filtro-tipo-flota" class="form-label">Tipo</label>
                                <select class="form-select" id="filtro-tipo-flota">
                                    <option value="">Todos</option>
                                    <option value="Solicitud">Solicitud</option>
                                    <option value="Notificación">Notificación</option>
                                    <option value="Consulta">Consulta</option>
                                    <option value="Urgente">Urgente</option>
                                </select>
                            </div>
                            <div class="col-md-<?php echo $usuario_rol === 'Chofer' ? '3' : '2'; ?>">
                                <label for="filtro-estado-flota" class="form-label">Estado</label>
                                <select class="form-select" id="filtro-estado-flota">
                                    <option value="">Todos</option>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="En Proceso">En Proceso</option>
                                    <option value="Respondida">Respondida</option>
                                    <option value="Cerrada">Cerrada</option>
                                </select>
                            </div>
                            <div class="col-md-<?php echo $usuario_rol === 'Chofer' ? '3' : '2'; ?>">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button class="btn btn-secondary" id="btn-aplicar-filtros-flota">
                                        <i class="fas fa-search me-2"></i>Buscar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Comunicaciones Flota -->
                        <div class="table-responsive">
                            <table id="tabla-comunicaciones-flota" class="table table-striped table-hover" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Placa</th>
                                        <th>Conductor</th>
                                        <th>Tipo</th>
                                        <th>Asunto</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Proveedores -->
            <div class="tab-pane fade" id="panel-proveedores" role="tabpanel" aria-labelledby="tab-proveedores">
                <!-- Lista de Proveedores -->
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-building me-2"></i>
                            Lista de Proveedores
                        </h5>
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

                <!-- Área de Comunicación -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-comments me-2"></i>
                            Comunicación
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="area-comunicacion-proveedores" class="comunicacion-container">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Seleccione un proveedor para ver el historial de comunicación
                            </div>
                        </div>

                        <!-- Formulario de Nueva Comunicación -->
                        <div class="mt-4" id="form-nueva-comunicacion-proveedores" style="display: none;">
                            <hr>
                            <h5>Nueva Comunicación</h5>
                            <form id="form-comunicacion-proveedores">
                                <input type="hidden" id="proveedor-id-comunicacion">
                                <div class="mb-3">
                                    <label for="tipo-comunicacion-proveedores" class="form-label">Tipo de Comunicación *</label>
                                    <select class="form-select" id="tipo-comunicacion-proveedores" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="solicitud">Solicitud de Cotización</option>
                                        <option value="pedido">Realizar Pedido</option>
                                        <option value="consulta">Consulta General</option>
                                        <option value="reclamo">Reclamo</option>
                                        <option value="seguimiento">Seguimiento de Pedido</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="asunto-proveedores" class="form-label">Asunto *</label>
                                    <input type="text" class="form-control" id="asunto-proveedores" required>
                                </div>
                                <div class="mb-3">
                                    <label for="mensaje-proveedores" class="form-label">Mensaje *</label>
                                    <textarea class="form-control" id="mensaje-proveedores" rows="5" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="archivos-proveedores" class="form-label">Archivos Adjuntos</label>
                                    <input type="file" class="form-control" id="archivos-proveedores" multiple>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar Comunicación
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Historial de Comunicaciones -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Historial de Comunicaciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabla-comunicaciones-proveedor" class="table table-striped" style="width:100%">
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
</div>

<!-- Modal Nueva Comunicación Flota -->
<div class="modal fade" id="modalNuevaComunicacionFlota" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Comunicación con Flota</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-nueva-comunicacion-flota">
                    <div class="mb-3">
                        <label for="placa-comunicacion-flota" class="form-label">Placa del Vehículo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="placa-comunicacion-flota" 
                               placeholder="Ingrese la placa" maxlength="10" required>
                    </div>

                    <div class="mb-3">
                        <label for="tipo-comunicacion-flota" class="form-label">Tipo de Comunicación <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipo-comunicacion-flota" required>
                            <option value="">Seleccione un tipo</option>
                            <option value="Solicitud">Solicitud</option>
                            <option value="Notificación">Notificación</option>
                            <option value="Consulta">Consulta</option>
                            <option value="Urgente">Urgente</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="asunto-comunicacion-flota" class="form-label">Asunto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="asunto-comunicacion-flota" 
                               placeholder="Ingrese el asunto" required>
                    </div>

                    <div class="mb-3">
                        <label for="mensaje-comunicacion-flota" class="form-label">Mensaje <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="mensaje-comunicacion-flota" rows="5" 
                                  placeholder="Escriba el mensaje..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Comunicación Proveedor -->
<div class="modal fade" id="modalNuevaComunicacionProveedor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Comunicación con Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-nueva-comunicacion-proveedor">
                    <div class="mb-3">
                        <label for="proveedor-comunicacion" class="form-label">Proveedor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="proveedor-comunicacion" 
                               placeholder="Nombre del proveedor" required>
                    </div>

                    <div class="mb-3">
                        <label for="contacto-proveedor" class="form-label">Contacto</label>
                        <input type="text" class="form-control" id="contacto-proveedor" 
                               placeholder="Nombre del contacto">
                    </div>

                    <div class="mb-3">
                        <label for="email-proveedor" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email-proveedor" 
                               placeholder="email@proveedor.com">
                    </div>

                    <div class="mb-3">
                        <label for="telefono-proveedor" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono-proveedor" 
                               placeholder="+56 9 1234 5678">
                    </div>

                    <div class="mb-3">
                        <label for="tipo-comunicacion-proveedor" class="form-label">Tipo de Comunicación <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipo-comunicacion-proveedor" required>
                            <option value="">Seleccione un tipo</option>
                            <option value="Pedido">Pedido</option>
                            <option value="Consulta">Consulta</option>
                            <option value="Reclamo">Reclamo</option>
                            <option value="Cotización">Cotización</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="asunto-comunicacion-proveedor" class="form-label">Asunto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="asunto-comunicacion-proveedor" 
                               placeholder="Ingrese el asunto" required>
                    </div>

                    <div class="mb-3">
                        <label for="mensaje-comunicacion-proveedor" class="form-label">Mensaje <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="mensaje-comunicacion-proveedor" rows="5" 
                                  placeholder="Escriba el mensaje..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Detalles -->
<div class="modal fade" id="modalVerComunicacion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de Comunicación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenido-comunicacion">
                <!-- Se cargará dinámicamente -->
            </div>
            <div class="modal-footer" id="footer-comunicacion">
                <!-- Se cargará dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Responder Comunicación -->
<div class="modal fade" id="modalResponderComunicacion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-reply me-2"></i>Responder Comunicación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-responder-comunicacion">
                    <input type="hidden" id="comunicacion-padre-id">
                    <input type="hidden" id="placa-respuesta">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Respondiendo a:</strong> <span id="asunto-original-respuesta"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mensaje-respuesta" class="form-label">Mensaje de Respuesta <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="mensaje-respuesta" rows="6" 
                                  placeholder="Escriba su respuesta..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Respuesta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
                        <label for="contacto-proveedor-modal" class="form-label">Contacto *</label>
                        <input type="text" class="form-control" id="contacto-proveedor-modal" required>
                    </div>
                    <div class="mb-3">
                        <label for="email-proveedor-modal" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email-proveedor-modal" required>
                        <div class="invalid-feedback" id="error-email-modal"></div>
                    </div>
                    <div class="mb-3">
                        <label for="telefono-proveedor-modal" class="form-label">Teléfono *</label>
                        <input type="tel" class="form-control" id="telefono-proveedor-modal" 
                               placeholder="+56 9 1234 5678 o 912345678" 
                               pattern="(\+?56\s?)?[2-9]\d{7,8}" required>
                        <small class="form-text text-muted">Formato chileno: +56 9 1234 5678 (móvil) o +56 2 2123 4567 (fijo)</small>
                        <div class="invalid-feedback" id="error-telefono-modal"></div>
                    </div>
                    <div class="mb-3">
                        <label for="rut-proveedor-modal" class="form-label">RUT</label>
                        <input type="text" class="form-control" id="rut-proveedor-modal" 
                               placeholder="12.345.678-9 o 1.234.567-8">
                        <small class="form-text text-muted">Formato: 12.345.678-9 (8 dígitos) o 1.234.567-8 (7 dígitos) - Opcional</small>
                        <div class="invalid-feedback" id="error-rut-modal"></div>
                    </div>
                    <div class="mb-3">
                        <label for="direccion-proveedor-modal" class="form-label">Dirección</label>
                        <textarea class="form-control" id="direccion-proveedor-modal" rows="2"></textarea>
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

