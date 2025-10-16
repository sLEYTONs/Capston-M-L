<!-- Sección de Reportes -->
<section id="reports-section" class="section">
    <div class="reports-container">
        <h2>Reportes</h2>
        <div class="report-filters">
            <div class="filter-group">
                <label for="report-date-from">Desde:</label>
                <input type="date" id="report-date-from">
            </div>
            <div class="filter-group">
                <label for="report-date-to">Hasta:</label>
                <input type="date" id="report-date-to">
            </div>
            <div class="filter-group">
                <label for="report-type">Tipo de Reporte:</label>
                <select id="report-type">
                    <option value="daily">Diario</option>
                    <option value="weekly">Semanal</option>
                    <option value="monthly">Mensual</option>
                    <option value="custom">Personalizado</option>
                </select>
            </div>
            <button class="btn btn-primary" id="generate-report">Generar Reporte</button>
        </div>
        <div id="report-results" class="report-results"></div>
    </div>
</section>