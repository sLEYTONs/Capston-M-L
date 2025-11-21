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
                                    <label for="color">Color</label>
                                    <input type="text" id="color" name="color">
                                </div>
                                <div class="form-field">
                                    <label for="year">Año</label>
                                    <input type="number" id="year" name="year" min="1990" max="2025">
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
                                <div class="form-field">
                                    <label for="driver-id">Cédula de Identidad *</label>
                                    <input type="text" id="driver-id" name="driverId" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="driver-phone">Teléfono</label>
                                    <input type="tel" id="driver-phone" name="driverPhone">
                                </div>
                                <div class="form-field">
                                    <input type="text" id="license" name="license">
                                </div>
                            </div>
                        </div>

                        <!-- Información de la Empresa -->
                        <div class="form-group">
                            <h3>Información de la Empresa</h3>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="company">Empresa *</label>
                                    <select id="company" name="company" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="pepsico">PepsiCo</option>
                                        <option value="proveedor">Proveedor</option>
                                        <option value="cliente">Cliente</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="company-name">Nombre de la Empresa</label>
                                    <input type="text" id="company-name" name="companyName">
                                </div>
                            </div>
                        </div>

                        <!-- Información del Ingreso -->
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
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="area">Área</label>
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
                                <textarea id="observations" name="observations" rows="3" 
                                          placeholder="Describa el problema o servicio requerido..."></textarea>
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

        <!-- Sección de Mis Solicitudes -->
        <section id="mis-solicitudes-section" class="section">
            <div class="form-container">
                <h2>
                    <i class="fas fa-list me-2"></i>
                    Mis Solicitudes de Agendamiento
                </h2>
                <div class="results-container">
                    <table id="mis-solicitudes-table" class="results-table display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Placa</th>
                                <th>Vehículo</th>
                                <th>Fecha/Hora Solicitada</th>
                                <th>Estado</th>
                                <th>Fecha Respuesta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán via Ajax -->
                        </tbody>
                    </table>
                </div>
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