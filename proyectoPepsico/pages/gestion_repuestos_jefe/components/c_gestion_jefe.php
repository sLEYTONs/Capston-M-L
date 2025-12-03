<!-- Sección de Gestión de Repuestos con Jefe de Taller -->
<section id="gestion-jefe-section" class="section">
    <div class="repuestos-container">
        <div class="repuestos-layout">
            <!-- Contenido principal -->
            <div class="repuestos-main-content">
                <div class="card h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-user-tie me-2"></i>
                            Gestión con Jefe de Taller
                        </h4>
                        <div class="card-actions">
                            <button class="btn btn-outline-light btn-sm" id="btn-refresh" title="Actualizar">
                                <i class="fas fa-sync-alt me-1"></i> Actualizar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Solicitudes Pendientes -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Solicitudes Pendientes de Aprobación
                            </h5>
                            <div class="tabla-container">
                                <table id="tabla-solicitudes-pendientes" class="table table-striped table-hover table-bordered" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>Prioridad</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Se cargarán dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Formulario de Nueva Solicitud al Jefe -->
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-paper-plane me-2"></i>
                                Nueva Solicitud al Jefe de Taller
                            </h5>
                            <form id="form-solicitud-jefe">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tipo-solicitud" class="form-label">Tipo de Solicitud *</label>
                                            <select class="form-select" id="tipo-solicitud" required>
                                                <option value="">Seleccionar...</option>
                                                <option value="aprobacion-compra">Aprobación de Compra</option>
                                                <option value="autorizacion-entrega">Autorización de Entrega Especial</option>
                                                <option value="cambio-proveedor">Cambio de Proveedor</option>
                                                <option value="reporte">Solicitud de Reporte</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="prioridad-solicitud" class="form-label">Prioridad *</label>
                                            <select class="form-select" id="prioridad-solicitud" required>
                                                <option value="baja">Baja</option>
                                                <option value="media" selected>Media</option>
                                                <option value="alta">Alta</option>
                                                <option value="urgente">Urgente</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="asunto-solicitud" class="form-label">Asunto *</label>
                                    <input type="text" class="form-control" id="asunto-solicitud" required>
                                </div>
                                <div class="mb-3">
                                    <label for="descripcion-solicitud" class="form-label">Descripción *</label>
                                    <textarea class="form-control" id="descripcion-solicitud" rows="5" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="archivos-solicitud" class="form-label">Archivos Adjuntos</label>
                                    <input type="file" class="form-control" id="archivos-solicitud" multiple>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
                                </button>
                            </form>
                        </div>

                        <!-- Historial de Comunicaciones con Jefe -->
                        <div>
                            <h5 class="mb-3">
                                <i class="fas fa-history me-2"></i>
                                Historial de Comunicaciones
                            </h5>
                            <div class="tabla-container">
                                <table id="tabla-comunicaciones-jefe" class="table table-striped table-hover table-bordered" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Asunto</th>
                                            <th>Prioridad</th>
                                            <th>Estado</th>
                                            <th>Respuesta</th>
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
            
            <!-- Aside con reportes y estadísticas -->
            <aside class="repuestos-aside">
                <div class="card h-100 mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Reportes y Estadísticas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h3 id="total-repuestos">0</h3>
                                        <p class="mb-0">Total Repuestos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h3 id="stock-bajo">0</h3>
                                        <p class="mb-0">Stock Bajo</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h3 id="solicitudes-pendientes">0</h3>
                                        <p class="mb-0">Solicitudes Pendientes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3 id="entregas-mes">0</h3>
                                        <p class="mb-0">Entregas del Mes</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100" id="btn-generar-reporte">
                            <i class="fas fa-file-pdf me-2"></i>Generar Reporte Completo
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

