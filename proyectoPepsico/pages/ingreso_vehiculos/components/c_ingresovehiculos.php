<div class="page-container">
    <div class="content-wrapper">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-car me-2"></i>
                            Registro de Vehículos de PepsiCo
                        </h5>
                        <small>Complete el formulario para registrar un nuevo vehículo en el sistema</small>
                    </div>
                    <div class="card-body">
                        <form id="form-ingreso-vehiculo" enctype="multipart/form-data">
                            <!-- Campo oculto para usuario_id -->
                            <input type="hidden" id="usuario_id" name="usuario_id" value="<?php echo $usuario_id ?? 1; ?>">
                            
                            <!-- Información del Vehículo -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="section-title">
                                        <i class="fas fa-car me-2"></i>Información del Vehículo
                                    </h6>
                                    <hr>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="placa">Placa *</label>
                                        <input type="text" class="form-control text-uppercase" id="placa" name="placa" required 
                                               placeholder="Ej: ABCD60" maxlength="10">
                                        <div class="invalid-feedback">Placa requerida</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="tipo_vehiculo">Tipo de Vehículo *</label>
                                        <input type="text" class="form-control" id="tipo_vehiculo" name="tipo_vehiculo" required 
                                               placeholder="Ej: Camión, Furgoneta, etc.">
                                        <div class="invalid-feedback">Tipo de vehículo requerido</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="marca">Marca *</label>
                                        <input type="text" class="form-control" id="marca" name="marca" required 
                                               placeholder="Ej: Toyota">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="modelo">Modelo *</label>
                                        <input type="text" class="form-control" id="modelo" name="modelo" required 
                                               placeholder="Ej: Hilux">
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="anio">Año *</label>
                                        <input type="number" class="form-control" id="anio" name="anio" required
                                               min="1990" max="<?php echo date('Y') + 1; ?>" 
                                               placeholder="Ej: 2023">
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="kilometraje">Kilometraje</label>
                                        <input type="number" class="form-control" id="kilometraje" name="kilometraje"
                                               min="0" 
                                               placeholder="Ej: 50000">
                                        <small class="form-text text-muted">Opcional</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Información del Conductor -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="section-title">
                                        <i class="fas fa-user me-2"></i>Información del Conductor
                                    </h6>
                                    <hr>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="conductor_nombre">Nombre del Conductor *</label>
                                        <input type="text" class="form-control" id="conductor_nombre" name="conductor_nombre" required
                                               placeholder="Nombre completo del conductor">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botones -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-buttons-container">
                                        <button type="submit" class="btn btn-primary btn-lg" id="btn-registrar">
                                            <i class="fas fa-save"></i> Registrar Vehículo
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-lg" id="btn-limpiar">
                                            <i class="fas fa-undo"></i> Limpiar Formulario
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Éxito -->
<div class="modal fade" id="modal-exito" tabindex="-1" role="dialog" aria-labelledby="modalExitoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-confirm" role="document">
        <div class="modal-content">
            <div class="modal-header-confirm">
                <div class="icon-box">
                    <i class="fas fa-check"></i>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body-confirm text-center">
                <h4 class="modal-title-confirm">¡Vehículo Registrado!</h4>
                <p class="modal-message" id="mensaje-exito">
                    El vehículo ha sido registrado correctamente en el sistema.
                </p>
                
                <div class="success-details">
                    <div class="detail-item">
                        <i class="fas fa-car"></i>
                        <span>Vehículo registrado exitosamente</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <span>Fecha: <span id="fecha-registro"><?php echo date('d/m/Y H:i'); ?></span></span>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer-confirm">
                <button type="button" class="btn btn-success btn-continue" data-dismiss="modal">
                    <i class="fas fa-check-circle"></i> Continuar
                </button>
                <button type="button" class="btn btn-outline-primary btn-another">
                    <i class="fas fa-plus-circle"></i> Registrar otro vehículo
                </button>
            </div>
        </div>
    </div>
</div>
