<main class="main">
    <div class="container">
        <!-- Sección de Solicitud de Agendamiento -->
        <section id="solicitud-section" class="section active">
            <div class="form-container">
                <h2>
                    <i class="fas fa-calendar-check me-2"></i>
                    Solicitud de Agendamiento para Taller
                </h2>
                <p class="info-text">
                    Complete el formulario para solicitar una hora de agendamiento en el taller. 
                    El supervisor revisará su solicitud, verificará la disponibilidad de horarios y mecánicos, 
                    y le notificará la respuesta.
                </p>
                
                <form id="solicitud-agendamiento-form" class="vehicle-form">
                    <div class="form-grid">
                        <!-- Información del Vehículo -->
                        <div class="form-group">
                            <h3>Información del Vehículo</h3>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="plate">Placa del Vehículo *</label>
                                    <input type="text" id="plate" name="plate" required 
                                           placeholder="Ej: BCDF12 o BC1234" maxlength="6"
                                           style="text-transform: uppercase;">
                                    <small class="form-text text-muted">Formato: 4 letras + 2 números (BCDF12) o 2 letras + 4 números (BC1234). Solo consonantes permitidas.</small>
                                    <small id="plate-error" class="form-text text-danger" style="display: none;"></small>
                                </div>
                            </div>
                            <div id="vehicle-info" style="display: none;">
                                <div class="form-row">
                                    <div class="form-field">
                                        <label for="vehicle-type">Tipo de Vehículo *</label>
                                        <input type="text" id="vehicle-type" name="vehicleType" readonly required>
                                    </div>
                                    <div class="form-field">
                                        <label for="brand">Marca *</label>
                                        <input type="text" id="brand" name="brand" readonly required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-field">
                                        <label for="model">Modelo *</label>
                                        <input type="text" id="model" name="model" readonly required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-field">
                                        <label for="year">Año</label>
                                        <input type="number" id="year" name="year" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Conductor -->
                        <div class="form-group">
                            <h3>Información del Conductor</h3>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="driver-name">Nombre del Conductor *</label>
                                    <input type="text" id="driver-name" name="driverName" readonly required 
                                           value="<?php echo htmlspecialchars($usuario_actual ?? ''); ?>">
                                    <small class="form-text text-muted">Se obtiene automáticamente del usuario logueado</small>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Servicio -->
                        <div class="form-group">
                            <h3>Información del Servicio</h3>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="purpose">Propósito del Servicio *</label>
                                    <select id="purpose" name="purpose" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="mantenimiento">Mantenimiento</option>
                                        <option value="reparacion">Reparación</option>
                                        <option value="revision">Revisión Técnica</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-field full-width">
                                <label for="observations">Observaciones</label>
                                <textarea id="observations" name="observations" rows="3" 
                                          placeholder="Describa el problema o servicio requerido..."></textarea>
                            </div>
                        </div>

                        <!-- Imágenes del Vehículo -->
                        <div class="form-group">
                            <h3>Imágenes del Vehículo</h3>
                            <div class="form-field full-width">
                                <label for="fotos">Fotos del Vehículo</label>
                                <input type="file" id="fotos" name="fotos[]" multiple accept="image/*" 
                                       class="form-control">
                                <small class="form-text text-muted">
                                    Puede seleccionar múltiples imágenes. Formatos permitidos: JPG, PNG, GIF, WEBP. Tamaño máximo: 5MB por imagen.
                                </small>
                                <div id="fotos-preview" class="mt-3" style="display: none;">
                                    <div class="row" id="fotos-preview-container"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Agendamiento -->
                        <div class="form-group">
                            <h3>Información de Agendamiento</h3>
                            <p class="help-text" style="background: #e3f2fd; padding: 1rem; border-radius: 5px; border-left: 4px solid #2196f3;">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Nota:</strong> El supervisor revisará su solicitud y le asignará una fecha y hora disponible según la agenda del taller. 
                                Se le notificará cuando su solicitud sea aprobada con los detalles del agendamiento.
                            </p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="clear-form">Limpiar Formulario</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud de Agendamiento
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>

<!-- Modal de Confirmación -->
<div id="confirmation-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Confirmar Solicitud de Agendamiento</h3>
        <p>¿Está seguro de que desea enviar esta solicitud de agendamiento? El supervisor revisará su solicitud y le notificará la respuesta.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" id="cancel-registration">Cancelar</button>
            <button class="btn btn-primary" id="confirm-registration">Enviar Solicitud</button>
        </div>
    </div>
</div>

<!-- Modal de Solicitud Pendiente -->
<div id="solicitud-pendiente-modal" class="modal">
    <div class="modal-content">
        <span class="close" id="close-pendiente-modal">&times;</span>
        <div style="text-align: center; padding: 1rem;">
            <i class="fas fa-clock" style="font-size: 3rem; color: #ff9800; margin-bottom: 1rem;"></i>
            <h3 style="color: #333; margin-bottom: 1rem;">Solicitud Pendiente</h3>
            <p style="color: #666; font-size: 1.1rem; line-height: 1.6;">
                Ya ha realizado una solicitud de agendamiento para este vehículo.<br>
                Por favor, espere la respuesta del supervisor.
            </p>
            <p style="color: #999; font-size: 0.9rem; margin-top: 1rem;">
                Puede revisar el estado de su solicitud en la sección "Mis Solicitudes".
            </p>
        </div>
        <div class="modal-actions" style="justify-content: center; margin-top: 1.5rem;">
            <button class="btn btn-primary" id="aceptar-pendiente-modal">Entendido</button>
        </div>
    </div>
</div>

<!-- Notificaciones -->
<div id="notification" class="notification"></div>