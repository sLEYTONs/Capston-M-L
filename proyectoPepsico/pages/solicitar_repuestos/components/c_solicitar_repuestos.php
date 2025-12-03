<div class="solicitar-repuestos-container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Solicitar Repuestos
                    </h4>
                </div>
                <div class="card-body">
                    <form id="form-solicitud-repuestos">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="vehiculo-select" class="form-label">Vehículo Asignado</label>
                                <select class="form-select" id="vehiculo-select" name="asignacion_id">
                                    <option value="">Seleccionar vehículo (opcional)...</option>
                                    <!-- Se cargarán dinámicamente -->
                                </select>
                                <small class="form-text text-muted">Seleccione el vehículo al que corresponde este repuesto. Si tiene varios vehículos asignados, elija el correspondiente.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="repuesto-select" class="form-label">Repuesto <span class="text-danger">*</span></label>
                                <select class="form-select" id="repuesto-select" name="repuesto_id" required>
                                    <option value="">Cargando repuestos...</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="cantidad" class="form-label">Cantidad <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" value="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="urgencia" class="form-label">Urgencia <span class="text-danger">*</span></label>
                                <select class="form-select" id="urgencia" name="urgencia" required>
                                    <option value="Baja">Baja</option>
                                    <option value="Media" selected>Media</option>
                                    <option value="Alta">Alta</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="motivo" class="form-label">Motivo</label>
                                <input type="text" class="form-control" id="motivo" name="motivo" placeholder="Motivo de la solicitud">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-redo me-2"></i>Limpiar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

