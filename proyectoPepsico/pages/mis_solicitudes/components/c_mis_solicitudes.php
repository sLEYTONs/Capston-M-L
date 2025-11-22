<main class="main">
    <div class="container">
        <!-- Sección de Mis Solicitudes -->
        <section id="mis-solicitudes-section" class="section active">
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
                                <th>Fecha/Hora Asignada</th>
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

<!-- Modal para ver motivo de rechazo -->
<div id="motivo-rechazo-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('motivo-rechazo-modal').style.display='none'">&times;</span>
        <h3>Motivo de Rechazo</h3>
        <p id="motivo-rechazo-texto"></p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="document.getElementById('motivo-rechazo-modal').style.display='none'">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal para ver seguimiento del vehículo -->
<div id="seguimiento-modal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close" id="close-seguimiento-modal">&times;</span>
        <h3>
            <i class="fas fa-tools me-2"></i>
            Seguimiento del Vehículo
        </h3>
        <div id="seguimiento-content" style="max-height: 600px; overflow-y: auto;">
            <div class="text-center" style="padding: 2rem;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando información del seguimiento...</p>
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" id="cerrar-seguimiento-modal">Cerrar</button>
        </div>
    </div>
</div>

