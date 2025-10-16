<main class="main">
    <div class="container">
        <div class="database-container">
            <div class="database-header">
                <h1><i class="fas fa-database"></i> Base de Datos Completa</h1>
                <p>Visualización completa de todos los datos disponibles en el sistema</p>
            </div>

            <!-- Estadísticas Generales -->
            <div class="stats-grid" id="general-stats">
                <div class="stat-card">
                    <h3>Total Registros</h3>
                    <div class="number" id="total-records">-</div>
                    <p>Registros en la base de datos</p>
                </div>
                <div class="stat-card">
                    <h3>Vehículos Activos</h3>
                    <div class="number" id="active-vehicles">-</div>
                    <p>Vehículos en uso</p>
                </div>
                <div class="stat-card">
                    <h3>Marcas Únicas</h3>
                    <div class="number" id="unique-brands">-</div>
                    <p>Marcas diferentes</p>
                </div>
                <div class="stat-card">
                    <h3>Empresas</h3>
                    <div class="number" id="unique-companies">-</div>
                    <p>Empresas registradas</p>
                </div>
            </div>

            <!-- Búsqueda y Filtros -->
            <div class="search-container">
                <h3><i class="fas fa-search"></i> Búsqueda y Filtros</h3>
                <div class="search-fields">
                    <div class="search-field">
                        <label for="global-search">Búsqueda Global:</label>
                        <input type="text" id="global-search" placeholder="Buscar en todos los campos...">
                    </div>
                    <div class="search-field">
                        <label for="filter-status">Estado:</label>
                        <select id="filter-status">
                            <option value="">Todos los estados</option>
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                            <option value="pending">Pendiente</option>
                        </select>
                    </div>
                    <div class="search-field">
                        <label for="filter-company">Empresa:</label>
                        <select id="filter-company">
                            <option value="">Todas las empresas</option>
                        </select>
                    </div>
                    <div class="search-field">
                        <label for="filter-brand">Marca:</label>
                        <select id="filter-brand">
                            <option value="">Todas las marcas</option>
                        </select>
                    </div>
                    <div class="search-field">
                        <button class="btn btn-primary" id="apply-filters">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pestañas de Datos -->
            <div class="tabs-container">
                <button class="tab-button active" data-tab="vehiculos">
                    <i class="fas fa-truck"></i> Vehículos
                </button>
                <button class="tab-button" data-tab="marcas">
                    <i class="fas fa-tags"></i> Marcas
                </button>
                <button class="tab-button" data-tab="empresas">
                    <i class="fas fa-building"></i> Empresas
                </button>
                <button class="tab-button" data-tab="conductores">
                    <i class="fas fa-user"></i> Conductores
                </button>
                <button class="tab-button" data-tab="estadisticas">
                    <i class="fas fa-chart-bar"></i> Estadísticas
                </button>
                <button class="tab-button" data-tab="temporal">
                    <i class="fas fa-calendar"></i> Análisis Temporal
                </button>
                <button class="tab-button" data-tab="graficos">
                    <i class="fas fa-chart-pie"></i> Gráficos
                </button>
                <button class="tab-button" data-tab="exportar">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </div>

            <!-- Contenido de Pestañas -->
            <div id="vehiculos" class="tab-content active">
                <h3><i class="fas fa-truck"></i> Registros de Vehículos</h3>
                <div class="data-summary" id="vehiculos-summary">
                    Cargando datos de vehículos...
                </div>
                <div id="vehiculos-table-container">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Cargando datos...</p>
                    </div>
                </div>
            </div>

            <div id="marcas" class="tab-content">
                <h3><i class="fas fa-tags"></i> Análisis por Marcas</h3>
                <div class="data-summary" id="marcas-summary">
                    Cargando análisis de marcas...
                </div>
                <div id="marcas-table-container">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Cargando análisis...</p>
                    </div>
                </div>
            </div>

            <div id="empresas" class="tab-content">
                <h3><i class="fas fa-building"></i> Análisis por Empresas</h3>
                <div class="data-summary" id="empresas-summary">
                    Cargando análisis de empresas...
                </div>
                <div id="empresas-table-container">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Cargando análisis...</p>
                    </div>
                </div>
            </div>

            <div id="conductores" class="tab-content">
                <h3><i class="fas fa-user"></i> Registros de Conductores</h3>
                <div class="data-summary" id="conductores-summary">
                    Cargando datos de conductores...
                </div>
                <div id="conductores-table-container">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Cargando datos...</p>
                    </div>
                </div>
            </div>

            <div id="estadisticas" class="tab-content">
                <h3><i class="fas fa-chart-bar"></i> Estadísticas del Sistema</h3>
                <div class="data-summary" id="estadisticas-summary">
                    Cargando estadísticas...
                </div>
                <div id="estadisticas-table-container">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Cargando estadísticas...</p>
                    </div>
                </div>
            </div>

            <div id="temporal" class="tab-content">
                <h3><i class="fas fa-calendar"></i> Análisis Temporal</h3>
                <div class="data-summary" id="temporal-summary">
                    Cargando análisis temporal...
                </div>
                
                <!-- Gráficos Temporales -->
                <div class="charts-grid">
                    <div class="chart-container">
                        <h4>Evolución Mensual</h4>
                        <div class="chart-placeholder" id="monthly-chart">
                            <i class="fas fa-chart-line" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Gráfico de evolución mensual</span>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Distribución por Horas</h4>
                        <div class="chart-placeholder" id="hourly-chart">
                            <i class="fas fa-clock" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Gráfico de distribución horaria</span>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Actividad por Día de la Semana</h4>
                        <div class="chart-placeholder" id="weekday-chart">
                            <i class="fas fa-calendar-week" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Gráfico de días de la semana</span>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Tendencias de Crecimiento</h4>
                        <div class="chart-placeholder" id="trends-chart">
                            <i class="fas fa-trending-up" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Gráfico de tendencias</span>
                        </div>
                    </div>
                </div>
                
                <div id="temporal-table-container">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Cargando análisis...</p>
                    </div>
                </div>
            </div>

            <div id="graficos" class="tab-content">
                <h3><i class="fas fa-chart-pie"></i> Gráficos y Analíticas Avanzadas</h3>
                <div class="data-summary" id="graficos-summary">
                    Visualización completa de todos los datos con gráficos interactivos
                </div>
                
                <!-- Gráficos Principales -->
                <div class="charts-grid">
                    <div class="chart-container">
                        <h4>Distribución por Marcas</h4>
                        <div class="chart-placeholder" id="brands-pie-chart">
                            <i class="fas fa-chart-pie" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Gráfico de distribución por marcas</span>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Distribución por Empresas</h4>
                        <div class="chart-placeholder" id="companies-bar-chart">
                            <i class="fas fa-chart-bar" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Gráfico de barras por empresas</span>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Tipos de Vehículo</h4>
                        <div class="chart-placeholder" id="vehicle-types-chart">
                            <i class="fas fa-truck" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Distribución por tipos de vehículo</span>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Estados de Vehículos</h4>
                        <div class="chart-placeholder" id="status-chart">
                            <i class="fas fa-check-circle" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Distribución por estados</span>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Propósitos de Visita</h4>
                        <div class="chart-placeholder" id="purpose-chart">
                            <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Distribución por propósitos</span>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Áreas de Destino</h4>
                        <div class="chart-placeholder" id="areas-chart">
                            <i class="fas fa-map-marker-alt" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Distribución por áreas</span>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficos de Análisis Avanzado -->
                <div class="charts-grid">
                    <div class="chart-container">
                        <h4>Top 10 Conductores Más Activos</h4>
                        <div class="chart-placeholder" id="top-drivers-chart">
                            <i class="fas fa-user-tie" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Ranking de conductores</span>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Análisis de Edad de Vehículos</h4>
                        <div class="chart-placeholder" id="vehicle-age-chart">
                            <i class="fas fa-calendar-alt" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Distribución por años</span>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Patrones de Uso por Hora</h4>
                        <div class="chart-placeholder" id="hourly-patterns-chart">
                            <i class="fas fa-clock" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Patrones horarios</span>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Evolución Temporal Completa</h4>
                        <div class="chart-placeholder" id="temporal-evolution-chart">
                            <i class="fas fa-chart-line" style="font-size: 3rem; margin-right: 1rem;"></i>
                            <span>Evolución temporal</span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="exportar" class="tab-content">
                <h3><i class="fas fa-download"></i> Exportar Datos</h3>
                <div class="export-section">
                    <h4>Descargar Base de Datos Completa</h4>
                    <p>Exporta todos los datos del sistema en diferentes formatos</p>
                    
                    <div class="export-buttons">
                        <button class="btn-export" id="export-all-csv">
                            <i class="fas fa-file-csv"></i> Exportar CSV Completo
                        </button>
                        <button class="btn-export secondary" id="export-all-json">
                            <i class="fas fa-file-code"></i> Exportar JSON Completo
                        </button>
                        <button class="btn-export secondary" id="export-excel-sheets">
                            <i class="fas fa-file-excel"></i> Exportar Hojas Excel
                        </button>
                        <button class="btn-export secondary" id="export-statistics">
                            <i class="fas fa-chart-line"></i> Exportar Estadísticas
                        </button>
                    </div>
                </div>

                <div class="export-section">
                    <h4>Exportaciones Especializadas</h4>
                    <p>Descarga datos específicos por categoría</p>
                    
                    <div class="export-buttons">
                        <button class="btn-export" id="export-vehicles-only">
                            <i class="fas fa-truck"></i> Solo Vehículos
                        </button>
                        <button class="btn-export" id="export-brands-only">
                            <i class="fas fa-tags"></i> Solo Marcas
                        </button>
                        <button class="btn-export" id="export-companies-only">
                            <i class="fas fa-building"></i> Solo Empresas
                        </button>
                        <button class="btn-export" id="export-drivers-only">
                            <i class="fas fa-user"></i> Solo Conductores
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Notificaciones -->
<div id="notification" class="notification"></div>