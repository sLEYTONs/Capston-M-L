<!-- Sección de Consulta -->
<section id="search-section" class="section">
    <div class="search-container">
        <h2>Consulta de Vehículos</h2>
        <div class="search-form">
            <div class="search-fields">
                <div class="input-group">
                    <input type="text" id="search-plate" placeholder="Buscar por placa..." class="search-input">
                    <button class="btn-clear" data-target="search-plate" title="Limpiar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="input-group">
                    <input type="text" id="search-driver" placeholder="Buscar por conductor..." class="search-input">
                    <button class="btn-clear" data-target="search-driver" title="Limpiar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="input-group">
                    <input type="date" id="search-date" placeholder="Buscar por fecha..." class="search-input">
                    <button class="btn-clear" data-target="search-date" title="Limpiar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <button class="btn btn-primary" id="search-btn">
                    <i class="fas fa-search me-2"></i>Buscar
                </button>
            </div>
        </div>
        <div class="results-container">
            <table id="results-table" class="results-table display" style="width:100%">
                <thead>
                    <tr>
                        <th>Placa</th>
                        <th>Vehículo</th>
                        <th>Conductor</th>
                        <th>Empresa</th>
                        <th>Fecha Ingreso</th>
                        <th>Estado</th>
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