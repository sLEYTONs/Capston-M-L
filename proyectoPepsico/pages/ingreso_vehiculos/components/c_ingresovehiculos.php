<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Completar Registro de Ingreso de Vehículo</h5>
                <small>Ingrese la placa para cargar el registro del guardia y complete la información faltante</small>
            </div>
            <div class="card-body">
                <!-- Buscador de Placa -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-search me-2"></i>Buscar Vehículo por Placa</h6>
                                <div class="input-group">
                                    <input type="text" class="form-control text-uppercase" id="buscadorPlaca" 
                                           placeholder="Ingrese la placa (ej: ABCD60)" maxlength="10">
                                    <button class="btn btn-primary" type="button" id="btnBuscarPlaca">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                                <small class="text-muted">Ingrese la placa que registró el guardia en la entrada</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="infoPrecarga" class="alert alert-info" style="display: none;">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="textoInfoPrecarga">Se cargarán los datos registrados por el guardia</span>
                        </div>
                    </div>
                </div>

                <form id="form-ingreso-vehiculo" enctype="multipart/form-data" style="display: none;">
                    <!-- Campo oculto para usuario_id -->
                    <input type="hidden" id="usuario_id" name="usuario_id" value="<?php echo $usuario_id ?? 1; ?>">
                    <input type="hidden" id="ingreso_id" name="ingreso_id" value="">
                    
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
                                       readonly style="background-color: #e9ecef;">
                                <div class="invalid-feedback">Placa requerida</div>
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

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="color">Color</label>
                                <input type="text" class="form-control" id="color" name="color" 
                                    placeholder="Especifique" maxlength="30" value="">
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
                                <input type="text" class="form-control" id="conductor_nombre" name="conductor_nombre" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="conductor_telefono">Teléfono</label>
                                <input type="tel" class="form-control" id="conductor_telefono" name="conductor_telefono">
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
                                    <option value="1/4">1/4</option>
                                    <option value="Reserva">Reserva</option>
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
                    </div>

                    <div class="row mb-4">
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
                        
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="persona_contacto">Persona de Contacto en Taller</label>
                                <input type="text" class="form-control" id="persona_contacto" name="persona_contacto" 
                                       placeholder="Nombre del mecánico o responsable">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Documentación y Fotos -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="section-title">Documentación Adicional</h6>
                            <hr>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="documentos">Subir Documentos (PDF, Word, Excel)</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="documentos" name="documentos[]" multiple 
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.txt">
                                    <label class="custom-file-label" for="documentos">Seleccionar archivos...</label>
                                </div>
                                <div id="lista-documentos" class="mt-2"></div>
                                <small class="form-text text-muted">Máximo 5 documentos, 10MB cada uno</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="alert alert-warning">
                                <small>
                                    <i class="fas fa-camera me-1"></i>
                                    <strong>Fotos del vehículo:</strong> Ya fueron tomadas por el guardia al ingreso.
                                    Puede verlas en el sistema de control de acceso.
                                </small>
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
                    
                    <!-- Botones -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg" id="btn-registrar">
                                <i class="fas fa-save"></i> Completar Registro
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg" id="btn-limpiar">
                                <i class="fas fa-undo"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Mensaje cuando no se encuentra el vehículo -->
                <div id="mensaje-no-encontrado" class="alert alert-warning text-center" style="display: none;">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h5>Vehículo no encontrado</h5>
                    <p>No se encontró un registro de ingreso con la placa ingresada.</p>
                    <p class="mb-0">
                        <small>Verifique que la placa sea correcta o consulte con el guardia de acceso.</small>
                    </p>
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
                <h4 class="modal-title-confirm">¡Registro Completado!</h4>
                <p class="modal-message" id="mensaje-exito">
                    La información del vehículo ha sido completada correctamente.
                </p>
                
                <div class="success-details">
                    <div class="detail-item">
                        <i class="fas fa-car"></i>
                        <span>Información del vehículo actualizada</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-bell"></i>
                        <span>Notificaciones enviadas al taller</span>
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