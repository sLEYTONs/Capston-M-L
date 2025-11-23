<main class="main">
    <div class="container">
        <!-- Sección de Solicitud de Agendamiento -->
        <section id="entry-section" class="section active">
            <div class="form-container">
                <h2>Solicitud de Agendamiento para Taller</h2>
                <p class="info-text">Complete el formulario para solicitar una hora de agendamiento en el taller. El supervisor revisará su solicitud y le notificará la respuesta.</p>
                <form id="vehicle-entry-form" class="vehicle-form">
                    <div class="form-grid">
                        <!-- Información del Vehículo -->
                        <div class="form-group">
                            <h3>Información del Vehículo</h3>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="plate">Placa del Vehículo *</label>
                                    <input type="text" id="plate" name="plate" required>
                                </div>
                                <div class="form-field">
                                    <label for="vehicle-type">Tipo de Vehículo *</label>
                                    <select id="vehicle-type" name="vehicleType" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="camion">Camión</option>
                                        <option value="furgon">Furgón</option>
                                        <option value="van">Van</option>
                                        <option value="pickup">Pickup</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="brand">Marca *</label>
                                    <input type="text" id="brand" name="brand" required>
                                </div>
                                <div class="form-field">
                                    <label for="model">Modelo *</label>
                                    <input type="text" id="model" name="model" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="year">Año</label>
                                    <input type="number" id="year" name="year" min="1990" max="2024">
                                </div>
                            </div>
                        </div>

                        <!-- Información del Conductor -->
                        <div class="form-group">
                            <h3>Información del Conductor</h3>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="driver-name">Nombre Completo *</label>
                                    <input type="text" id="driver-name" name="driverName" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="driver-phone">Teléfono</label>
                                    <input type="tel" id="driver-phone" name="driverPhone">
                                </div>
                            </div>
                        </div>

                        <!-- Información del Ingreso -->
                        <div class="form-group">
                            <h3>Información del Ingreso</h3>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="purpose">Propósito de la Visita *</label>
                                    <select id="purpose" name="purpose" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="entrega">Entrega de Productos</option>
                                        <option value="recogida">Recogida de Productos</option>
                                        <option value="mantenimiento">Mantenimiento</option>
                                        <option value="visita">Visita Comercial</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="area">Área de Destino</label>
                                    <select id="area" name="area">
                                        <option value="">Seleccionar...</option>
                                        <option value="almacen">Almacén</option>
                                        <option value="produccion">Producción</option>
                                        <option value="oficinas">Oficinas</option>
                                        <option value="mantenimiento">Mantenimiento</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="contact-person">Persona de Contacto</label>
                                    <input type="text" id="contact-person" name="contactPerson">
                                </div>
                            </div>
                            <div class="form-field full-width">
                                <label for="observations">Observaciones</label>
                                <textarea id="observations" name="observations" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Información de Agendamiento -->
                        <div class="form-group">
                            <p class="help-text">El supervisor revisará su solicitud y le asignará una fecha y hora disponible en la agenda del taller.</p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="clear-form">Limpiar Formulario</button>
                        <button type="submit" class="btn btn-primary">Enviar Solicitud de Agendamiento</button>
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

<!-- Notificaciones -->
<div id="notification" class="notification"></div>

