<!-- Sección de Gestión de Repuestos con Jefe de Taller -->
<section id="gestion-jefe-section" class="section">
    <div class="container-fluid">
        <div class="row">
            <!-- Solicitudes Pendientes -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Solicitudes Pendientes de Aprobación
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabla-solicitudes-pendientes" class="table table-striped" style="width:100%">
                                <thead>
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
                </div>
            </div>

            <!-- Reportes y Estadísticas -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-chart-bar me-2"></i>
                            Reportes para Jefe de Taller
                        </h4>
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
            </div>
        </div>

        <!-- Formulario de Nueva Solicitud al Jefe -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-paper-plane me-2"></i>
                            Nueva Solicitud al Jefe de Taller
                        </h4>
                    </div>
                    <div class="card-body">
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
                </div>
            </div>
        </div>

        <!-- Historial de Comunicaciones con Jefe -->
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
                            <table id="tabla-comunicaciones-jefe" class="table table-striped" style="width:100%">
                                <thead>
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
    </div>
</section>

