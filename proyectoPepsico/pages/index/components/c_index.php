<main class="main">
    <div class="container">
        <!-- Sección de Ingreso de Vehículos -->
        <section id="entry-section" class="section active">
            <div class="form-container">
                <h2>Registro de Ingreso de Vehículo</h2>
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
                                    <label for="color">Color</label>
                                    <input type="text" id="color" name="color">
                                </div>
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
                                    <label for="license">Número de Licencia</label>
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
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="clear-form">Limpiar Formulario</button>
                        <button type="submit" class="btn btn-primary">Registrar Ingreso</button>
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
        <h3>Confirmar Registro</h3>
        <p>¿Está seguro de que desea registrar el ingreso de este vehículo?</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" id="cancel-registration">Cancelar</button>
            <button class="btn btn-primary" id="confirm-registration">Confirmar</button>
        </div>
    </div>
</div>

<!-- Notificaciones -->
<div id="notification" class="notification"></div>

