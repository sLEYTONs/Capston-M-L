<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Registro de Ingreso de Vehículo</h5>
                <small>Complete todos los campos obligatorios (*) para registrar el ingreso del vehículo</small>
            </div>
            <div class="card-body">
                <form id="form-ingreso-vehiculo" enctype="multipart/form-data">
                    <!-- Información del Vehículo -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="section-title">Información del Vehículo</h6>
                            <hr>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="placa">Placa *</label>
                                <input type="text" class="form-control" id="placa" name="placa" required 
                                       pattern="[A-Z0-9]{6,8}" title="Formato de placa válido (ej: ABC123 o AB123CD)">
                                <div class="invalid-feedback">Ingrese una placa válida</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="tipo_vehiculo">Tipo de Vehículo *</label>
                                <select class="form-control" id="tipo_vehiculo" name="tipo_vehiculo" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Camión">Camión</option>
                                    <option value="Furgoneta">Furgoneta</option>
                                    <option value="Automóvil">Automóvil</option>
                                    <option value="Motocicleta">Motocicleta</option>
                                    <option value="Bus">Bus</option>
                                    <option value="Maquinaria">Maquinaria</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="marca">Marca *</label>
                                <input type="text" class="form-control" id="marca" name="marca" required>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="modelo">Modelo *</label>
                                <input type="text" class="form-control" id="modelo" name="modelo" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="color">Color</label>
                                <input type="text" class="form-control" id="color" name="color" value="Sin especificar">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="anio">Año</label>
                                <input type="number" class="form-control" id="anio" name="anio" 
                                       min="1990" max="<?php echo date('Y') + 1; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="kilometraje">Kilometraje</label>
                                <input type="number" class="form-control" id="kilometraje" name="kilometraje" 
                                       min="0" placeholder="Km actual">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="combustible">Nivel de Combustible</label>
                                <select class="form-control" id="combustible" name="combustible">
                                    <option value="Lleno">Lleno</option>
                                    <option value="3/4" selected>3/4</option>
                                    <option value="1/2">1/2</option>
                                    <option value="1/4">1/4</option>
                                    <option value="Reserva">Reserva</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información del Conductor -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="section-title">Información del Conductor</h6>
                            <hr>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="conductor_nombre">Nombre del Conductor *</label>
                                <input type="text" class="form-control" id="conductor_nombre" name="conductor_nombre" 
                                       value="<?php echo htmlspecialchars($usuario_actual ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="conductor_cedula">Cédula *</label>
                                <input type="text" class="form-control" id="conductor_cedula" name="conductor_cedula" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="conductor_telefono">Teléfono</label>
                                <input type="tel" class="form-control" id="conductor_telefono" name="conductor_telefono">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="licencia">Licencia</label>
                                <input type="text" class="form-control" id="licencia" name="licencia">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="empresa_codigo">Código de Empresa *</label>
                                <input type="text" class="form-control" id="empresa_codigo" name="empresa_codigo" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="empresa_nombre">Nombre de Empresa *</label>
                                <input type="text" class="form-control" id="empresa_nombre" name="empresa_nombre" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estado del Vehículo -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="section-title">Estado del Vehículo</h6>
                            <hr>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="estado_ingreso">Estado General *</label>
                                <select class="form-control" id="estado_ingreso" name="estado_ingreso" required>
                                    <option value="Bueno">Bueno</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Malo">Malo</option>
                                    <option value="Accidentado">Accidentado</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="proposito">Propósito de la Visita *</label>
                                <select class="form-control" id="proposito" name="proposito" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Mantenimiento">Mantenimiento</option>
                                    <option value="Reparación">Reparación</option>
                                    <option value="Inspección">Inspección</option>
                                    <option value="Lavado">Lavado</option>
                                    <option value="Revisión Técnica">Revisión Técnica</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="area">Área de Trabajo</label>
                                <select class="form-control" id="area" name="area">
                                    <option value="General">General</option>
                                    <option value="Mecánica">Mecánica</option>
                                    <option value="Electricidad">Electricidad</option>
                                    <option value="Carrocería">Carrocería</option>
                                    <option value="Pintura">Pintura</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Documentación y Fotos -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="section-title">Documentación y Fotos</h6>
                            <hr>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Subir Documentos (PDF, Word, Excel)</label>
                                <div id="dropzone-documentos" class="dropzone">
                                    <div class="dz-message">
                                        <i class="fas fa-file-upload fa-2x"></i>
                                        <br>
                                        Arrastre documentos aquí o haga clic para seleccionar
                                    </div>
                                </div>
                                <small class="form-text text-muted">Máximo 5 documentos, 10MB cada uno</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Subir Fotos del Vehículo (JPG, PNG)</label>
                                <div id="dropzone-fotos" class="dropzone">
                                    <div class="dz-message">
                                        <i class="fas fa-camera fa-2x"></i>
                                        <br>
                                        Arrastre fotos aquí o haga clic para seleccionar
                                    </div>
                                </div>
                                <small class="form-text text-muted">Máximo 10 fotos, 5MB cada una</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observaciones -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" 
                                          rows="4" placeholder="Describa cualquier detalle importante sobre el estado del vehículo..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Persona de Contacto -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="persona_contacto">Persona de Contacto en Taller</label>
                                <input type="text" class="form-control" id="persona_contacto" name="persona_contacto" 
                                       placeholder="Nombre del mecánico o responsable">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg" id="btn-registrar">
                                <i class="fas fa-car"></i> Registrar Ingreso de Vehículo
                            </button>
                            <button type="reset" class="btn btn-secondary btn-lg">
                                <i class="fas fa-undo"></i> Limpiar Formulario
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Éxito -->
<div class="modal fade" id="modal-exito" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">¡Registro Exitoso!</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4>Vehículo registrado correctamente</h4>
                <p id="mensaje-exito" class="mb-0">Se ha registrado el ingreso del vehículo y se han notificado a los responsables.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal">Aceptar</button>
            </div>
        </div>
    </div>
</div>