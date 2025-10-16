<main class="main">
    <div class="container">
        <!-- Sección de Marcas -->
        <section id="brands-section" class="section active">
            <div class="brands-container">
                <h2>Marcas Registradas en el Sistema</h2>
                
                <!-- Filtros -->
                <div class="filters-section">
                    <h3>Filtros de Búsqueda</h3>
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="brand-search">Buscar Marca:</label>
                            <input type="text" id="brand-search" placeholder="Nombre de la marca...">
                        </div>
                        <div class="filter-group">
                            <label for="vehicle-type-filter">Tipo de Vehículo:</label>
                            <select id="vehicle-type-filter">
                                <option value="">Todos los tipos</option>
                                <option value="camion">Camión</option>
                                <option value="furgon">Furgón</option>
                                <option value="van">Van</option>
                                <option value="pickup">Pickup</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="company-filter">Empresa:</label>
                            <select id="company-filter">
                                <option value="">Todas las empresas</option>
                                <option value="pepsico">PepsiCo</option>
                                <option value="proveedor">Proveedor</option>
                                <option value="cliente">Cliente</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button class="btn btn-primary" id="apply-filters">Aplicar Filtros</button>
                        </div>
                    </div>
                </div>

                <!-- Resumen Estadístico -->
                <div class="summary-cards" id="summary-cards">
                    <!-- Se llenará dinámicamente -->
                </div>

                <!-- Grid de Marcas -->
                <div class="brands-grid" id="brands-grid">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>
        </section>

        <!-- Sección de Analíticas -->
        <section id="analytics-section" class="section">
            <div class="brands-container">
                <h2>Analíticas de Marcas</h2>
                
                <!-- Filtros de Analíticas -->
                <div class="analytics-filters">
                    <h3>Filtros de Análisis</h3>
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="analytics-date-from">Desde:</label>
                            <input type="date" id="analytics-date-from">
                        </div>
                        <div class="filter-group">
                            <label for="analytics-date-to">Hasta:</label>
                            <input type="date" id="analytics-date-to">
                        </div>
                        <div class="filter-group">
                            <label for="analytics-brand">Marca:</label>
                            <select id="analytics-brand">
                                <option value="">Todas las marcas</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="analytics-company">Empresa:</label>
                            <select id="analytics-company">
                                <option value="">Todas las empresas</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button class="btn btn-primary" id="apply-analytics-filters">Aplicar Filtros</button>
                        </div>
                    </div>
                </div>

                <!-- Distribución por Marca -->
                <div class="chart-container">
                    <h3>Distribución por Marca</h3>
                    <div class="chart-content" id="brand-distribution-chart">
                        <div class="chart-placeholder">
                            <i class="fas fa-chart-pie" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Gráfico de distribución por marca</span>
                        </div>
                    </div>
                </div>

                <!-- Evolución Temporal -->
                <div class="chart-container">
                    <h3>Evolución Temporal</h3>
                    <div class="chart-content" id="temporal-evolution-chart">
                        <div class="chart-placeholder">
                            <i class="fas fa-chart-line" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Gráfico de evolución temporal</span>
                        </div>
                    </div>
                </div>

                <!-- Análisis por Empresa -->
                <div class="chart-container">
                    <h3>Análisis por Empresa</h3>
                    <div class="chart-content" id="company-analysis-chart">
                        <div class="chart-placeholder">
                            <i class="fas fa-chart-bar" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Gráfico de análisis por empresa</span>
                        </div>
                    </div>
                </div>

                <!-- Análisis Avanzado con Datos del Excel -->
                <div class="advanced-analytics">
                    <h3>Análisis Avanzado - Datos del Excel</h3>
                    
                    <!-- Resumen de Datos -->
                    <div class="data-summary-grid">
                        <div class="summary-card">
                            <h4>Resumen General</h4>
                            <div class="summary-stats" id="general-summary">
                                <div class="stat-item">
                                    <span class="stat-label">Total Registros:</span>
                                    <span class="stat-value" id="total-records">-</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Marcas Únicas:</span>
                                    <span class="stat-value" id="unique-brands">-</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Empresas:</span>
                                    <span class="stat-value" id="unique-companies">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="summary-card">
                            <h4>Datos por Pestaña del Excel</h4>
                            <div class="excel-tabs-summary" id="excel-tabs-summary">
                                <div class="tab-summary">
                                    <span class="tab-name">RESUMEN:</span>
                                    <span class="tab-count" id="resumen-count">-</span>
                                </div>
                                <div class="tab-summary">
                                    <span class="tab-name">DATA:</span>
                                    <span class="tab-count" id="data-count">-</span>
                                </div>
                                <div class="tab-summary">
                                    <span class="tab-name">MERCH:</span>
                                    <span class="tab-count" id="merch-count">-</span>
                                </div>
                                <div class="tab-summary">
                                    <span class="tab-name">BASE:</span>
                                    <span class="tab-count" id="base-count">-</span>
                                </div>
                                <div class="tab-summary">
                                    <span class="tab-name">Movimiento diario:</span>
                                    <span class="tab-count" id="movimiento-count">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos Específicos por Pestaña -->
                    <div class="excel-tabs-charts">
                        <div class="chart-container">
                            <h4>Análisis MERCH (Marcas)</h4>
                            <div class="chart-content" id="merch-analysis-chart">
                                <div class="chart-placeholder">
                                    <i class="fas fa-tags" style="font-size: 2rem; margin-right: 1rem;"></i>
                                    <span>Análisis de datos MERCH</span>
                                </div>
                            </div>
                        </div>

                        <div class="chart-container">
                            <h4>Análisis BASE (Base de Datos)</h4>
                            <div class="chart-content" id="base-analysis-chart">
                                <div class="chart-placeholder">
                                    <i class="fas fa-database" style="font-size: 2rem; margin-right: 1rem;"></i>
                                    <span>Análisis de datos BASE</span>
                                </div>
                            </div>
                        </div>

                        <div class="chart-container">
                            <h4>Movimiento Diario</h4>
                            <div class="chart-content" id="daily-movement-chart">
                                <div class="chart-placeholder">
                                    <i class="fas fa-calendar-day" style="font-size: 2rem; margin-right: 1rem;"></i>
                                    <span>Análisis de movimiento diario</span>
                                </div>
                            </div>
                        </div>

                        <div class="chart-container">
                            <h4>Análisis de Beneficios</h4>
                            <div class="chart-content" id="benefits-analysis-chart">
                                <div class="chart-placeholder">
                                    <i class="fas fa-chart-line" style="font-size: 2rem; margin-right: 1rem;"></i>
                                    <span>Análisis de beneficios</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección de Exportación -->
        <section id="export-section" class="section">
            <div class="brands-container">
                <h2>Exportar Datos</h2>
                
                <div class="export-section">
                    <h3>Descargar Información de Marcas</h3>
                    <p>Exporta los datos de marcas registradas en diferentes formatos</p>
                    
                    <div class="export-buttons">
                        <button class="btn-export" id="export-csv">
                            <i class="fas fa-file-csv"></i> Exportar CSV
                        </button>
                        <button class="btn-export secondary" id="export-json">
                            <i class="fas fa-file-code"></i> Exportar JSON
                        </button>
                        <button class="btn-export secondary" id="export-excel">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </button>
                        <button class="btn-export secondary" id="export-pdf">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </button>
                    </div>
                </div>

                <div class="export-section">
                    <h3>Reportes Especializados</h3>
                    <p>Genera reportes específicos por marca o empresa</p>
                    
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="report-brand">Marca:</label>
                            <select id="report-brand">
                                <option value="">Todas las marcas</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="report-company">Empresa:</label>
                            <select id="report-company">
                                <option value="">Todas las empresas</option>
                                <option value="pepsico">PepsiCo</option>
                                <option value="proveedor">Proveedor</option>
                                <option value="cliente">Cliente</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="report-date-from">Desde:</label>
                            <input type="date" id="report-date-from">
                        </div>
                        <div class="filter-group">
                            <label for="report-date-to">Hasta:</label>
                            <input type="date" id="report-date-to">
                        </div>
                        <div class="filter-group">
                            <button class="btn btn-primary" id="generate-brand-report">Generar Reporte</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<!-- Modal de Detalles de Marca -->
<div id="brand-details-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="modal-brand-name">Detalles de la Marca</h3>
        <div id="modal-brand-content">
            <!-- Se llenará dinámicamente -->
        </div>
    </div>
</div>

<!-- Notificaciones -->
<div id="notification" class="notification"></div>