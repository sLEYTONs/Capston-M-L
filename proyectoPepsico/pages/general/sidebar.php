<div class="loader-bg">
  <div class="loader-track">
    <div class="loader-fill"></div>
  </div>
</div>
<!-- [ Pre-loader ] End -->

<!-- Botón hamburguesa para móviles -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
  <i class="fas fa-bars"></i>
</button>

<!-- Área de detección de hover en el borde derecho (solo desktop) -->
<div class="sidebar-hover-trigger" id="sidebarHoverTrigger"></div>

<!-- Overlay para móviles -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar" id="pcSidebar">
  <div class="navbar-wrapper">
    <!-- Header del sidebar con logo PepsiCo -->
    <div class="sidebar-header">
      <div class="d-flex align-items-center justify-content-center py-3">
        <div class="sidebar-logo">
          <img src="../assets/images/pepsicoLogo.png" alt="PepsiCo" class="logo-img">
          <span class="logo-text">Taller Mecánico</span>
        </div>
      </div>
      <!-- Botón de cerrar para móviles -->
      <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Cerrar menú">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="navbar-content">
      <!-- Tarjeta de usuario mejorada -->
      <div class="card pc-user-card">
        <div class="card-body py-3">
          <div class="d-flex align-items-center">
            <!-- Avatar con iniciales del usuario -->
            <div class="flex-shrink-0">
              <div class="avatar-initials wid-50 rounded-circle bg-gradient-primary text-white d-flex align-items-center justify-content-center">
                <?php
                // Generar iniciales del nombre de usuario
                $iniciales = '';
                if (isset($usuario_actual)) {
                  $nombres = explode(' ', $usuario_actual);
                  foreach ($nombres as $nombre) {
                    if (!empty($nombre)) {
                      $iniciales .= strtoupper(substr($nombre, 0, 1));
                      if (strlen($iniciales) >= 2) break;
                    }
                  }
                }
                echo $iniciales ?: 'U';
                ?>
              </div>
            </div>

            <!-- Info del usuario -->
            <div class="flex-grow-1 ms-3">
              <h6 class="mb-0 text-truncate fw-semibold" title="<?php echo htmlspecialchars($usuario_actual ?? 'Usuario'); ?>">
                  <?php echo htmlspecialchars($usuario_actual ?? 'Usuario'); ?>
              </h6>
              <small class="text-muted text-truncate d-block">
                  <?php 
                  $rol = $usuario_rol ?? 'Usuario';
                  $badge_class = [
                      'Administrador' => 'badge-admin',
                      'Jefe de Taller' => 'badge-jefe-taller',
                      'Mecánico' => 'badge-mecanico',
                      'Recepcionista' => 'badge-recepcionista',
                      'Guardia' => 'badge-guardia',
                      'Supervisor' => 'badge-supervisor',
                      'Chofer' => 'badge-chofer',
                      'Asistente de Repuestos' => 'badge-asistente-repuestos',
                      'Coordinador de Zona' => 'badge-coordinador-zona',
                      'Ejecutivo/a de Ventas' => 'badge-ejecutivo-ventas',
                      'Supervisor de Flotas' => 'badge-supervisor-flotas',
                      'Encargado de Llaves' => 'badge-encargado-llaves'
                  ][$rol] ?? 'badge-secondary';
                  ?>
                  <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($rol); ?></span>
              </small>
            </div>
          </div>
          
        </div>
      </div>

      <!-- Menú lateral mejorado con permisos por rol -->
      <ul class="pc-navbar">
        <li class="pc-item pc-caption">
          <label>Navegación Principal</label>
        </li>

        <!-- Control de Ingreso - Solo para Guardia y Administrador -->
        <?php if (tiene_acceso('control_ingreso.php')): ?>
        <li class="pc-item">
          <a href="control_ingreso.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-shield-alt"></i>
            </span>
            <span class="pc-mtext">Control de Ingreso</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Vehículos Agendados - Solo para Guardia -->
        <?php if (tiene_acceso('vehiculos_agendados.php')): ?>
        <li class="pc-item">
          <a href="vehiculos_agendados.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-calendar-check"></i>
            </span>
            <span class="pc-mtext">Vehículos Agendados</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Ingreso de Vehículos -->
        <?php if (tiene_acceso('ingreso_vehiculos.php')): ?>
        <li class="pc-item">
          <a href="ingreso_vehiculos.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-car"></i>
            </span>
            <span class="pc-mtext">Ingreso Vehículos</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Solicitudes de Agendamiento - Solo para Chofer -->
        <?php if (tiene_acceso('solicitudes_agendamiento.php')): ?>
        <li class="pc-item">
          <a href="solicitudes_agendamiento.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-calendar-check"></i>
            </span>
            <span class="pc-mtext">Solicitudes de Agendamiento</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Mis Solicitudes - Solo para Chofer -->
        <?php if (tiene_acceso('mis_solicitudes.php')): ?>
        <li class="pc-item">
          <a href="mis_solicitudes.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-list"></i>
            </span>
            <span class="pc-mtext">Mis Solicitudes</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Consulta -->
        <?php if (tiene_acceso('consulta.php')): ?>
        <li class="pc-item">
          <a href="consulta.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-search"></i>
            </span>
            <span class="pc-mtext">Consulta</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Gestión de Solicitudes - Solo para Supervisor -->
        <?php if (tiene_acceso('gestion_solicitudes.php')): ?>
        <li class="pc-item">
          <a href="gestion_solicitudes.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-calendar-check"></i>
            </span>
            <span class="pc-mtext">Gestión de Solicitudes</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Administrar Agendas - Solo para Supervisor -->
        <?php if (tiene_acceso('administrar_agendas.php')): ?>
        <li class="pc-item">
          <a href="administrar_agendas.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-calendar-alt"></i>
            </span>
            <span class="pc-mtext">Administrar Agendas</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Recepción Técnica -->
        <?php if (tiene_acceso('recepcion_tecnica.php')): ?>
        <li class="pc-item">
          <a href="recepcion_tecnica.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-clipboard-check"></i>
            </span>
            <span class="pc-mtext">Recepción Técnica</span>
          </a>
        </li>
        <?php endif; ?>


        <!-- Reportes -->
        <?php if (tiene_acceso('reportes.php')): ?>
        <li class="pc-item">
          <a href="reportes.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-chart-bar"></i>
            </span>
            <span class="pc-mtext">Reportes</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Menú desplegable para Gestión (solo para roles con acceso a base_datos o gestion_usuarios) -->
        <?php if (tiene_acceso('base_datos.php') || tiene_acceso('gestion_usuarios.php')): ?>
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-cogs"></i>
            </span>
            <span class="pc-mtext">Gestión</span>
            <span class="pc-arrow">
              <i class="fas fa-chevron-down"></i>
            </span>
          </a>
          <ul class="pc-submenu">

            <?php if (tiene_acceso('base_datos.php')): ?>
            <li class="pc-item">
              <a href="base_datos.php" class="pc-link">
                <span class="pc-mtext">Base de Datos</span>
              </a>
            </li>
            <?php endif; ?>

            <?php if (tiene_acceso('gestion_usuarios.php')): ?>
            <li class="pc-item">
              <a href="gestion_usuarios.php" class="pc-link">
                <span class="pc-mtext">Gestión de Usuarios</span>
              </a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <!-- Menú desplegable para Taller (solo para roles con acceso a tareas o mantenimiento) -->
        <?php if (tiene_acceso('tareas.php') || tiene_acceso('mantenimiento.php') || tiene_acceso('gestion_pausas_repuestos.php')): ?>
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-tools"></i>
            </span>
            <span class="pc-mtext">Taller</span>
            <span class="pc-arrow">
              <i class="fas fa-chevron-down"></i>
            </span>
          </a>
          <ul class="pc-submenu">
            <?php if (tiene_acceso('tareas.php')): ?>
            <li class="pc-item">
              <a href="tareas.php" class="pc-link">
                <span class="pc-mtext">Tareas</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('gestion_pausas_repuestos.php')): ?>
            <li class="pc-item">
              <a href="gestion_pausas_repuestos.php" class="pc-link">
                <span class="pc-mtext">Gestión de Pausas</span>
              </a>
            </li>
            <?php endif; ?>

            <?php if (tiene_acceso('mantenimiento.php')): ?>
            <li class="pc-item">
              <a href="mantenimiento.php" class="pc-link">
                <span class="pc-mtext">Mantenimiento</span>
              </a>
            </li>
            <?php endif; ?>

            <?php if (tiene_acceso('calidad.php')): ?>
            <li class="pc-item">
              <a href="calidad.php" class="pc-link">
                <span class="pc-mtext">Control de Calidad</span>
              </a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <!-- Menú desplegable para Repuestos (separado de Taller) -->
        <?php if (tiene_acceso('repuestos.php') || tiene_acceso('solicitar_repuestos.php') || tiene_acceso('estado_solicitudes_repuestos.php') || tiene_acceso('registro_insumos_vehiculo.php') || tiene_acceso('comunicacion_proveedores.php') || tiene_acceso('gestion_repuestos_jefe.php') || tiene_acceso('gestion_solicitudes_repuestos.php')): ?>
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-boxes"></i>
            </span>
            <span class="pc-mtext">Repuestos</span>
            <span class="pc-arrow">
              <i class="fas fa-chevron-down"></i>
            </span>
          </a>
          <ul class="pc-submenu">
            <?php if (tiene_acceso('repuestos.php')): ?>
            <li class="pc-item">
              <a href="repuestos.php" class="pc-link">
                <i class="fas fa-warehouse"></i>
                <span class="pc-mtext">Inventario</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('estado_solicitudes_repuestos.php')): ?>
            <li class="pc-item">
              <a href="estado_solicitudes_repuestos.php" class="pc-link">
                <i class="fas fa-clipboard-check"></i>
                <span class="pc-mtext">Estado de Solicitudes</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('registro_insumos_vehiculo.php')): ?>
            <li class="pc-item">
              <a href="registro_insumos_vehiculo.php" class="pc-link">
                <i class="fas fa-clipboard-list"></i>
                <span class="pc-mtext">Registro de Insumos</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('comunicacion_proveedores.php')): ?>
            <li class="pc-item">
              <a href="comunicacion_proveedores.php" class="pc-link">
                <i class="fas fa-comments"></i>
                <span class="pc-mtext">Comunicación Proveedores</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('gestion_repuestos_jefe.php')): ?>
            <li class="pc-item">
              <a href="gestion_repuestos_jefe.php" class="pc-link">
                <i class="fas fa-user-tie"></i>
                <span class="pc-mtext">Gestión con Jefe</span>
              </a>
            </li>
            <?php endif; ?>

            <?php if (tiene_acceso('gestion_solicitudes_repuestos.php')): ?>
            <li class="pc-item">
              <a href="gestion_solicitudes_repuestos.php" class="pc-link">
                <i class="fas fa-clipboard-list"></i>
                <span class="pc-mtext">Gestión de Solicitudes</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('solicitar_repuestos.php')): ?>
            <li class="pc-item">
              <a href="solicitar_repuestos.php" class="pc-link">
                <i class="fas fa-shopping-cart"></i>
                <span class="pc-mtext">Solicitar Repuestos</span>
              </a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <!-- Menú desplegable para Proveedores -->
        <?php if (tiene_acceso('recepcion_entrega_repuestos.php') || tiene_acceso('seguimiento_ingresos_repuestos.php')): ?>
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-truck"></i>
            </span>
            <span class="pc-mtext">Proveedores</span>
            <span class="pc-arrow">
              <i class="fas fa-chevron-down"></i>
            </span>
          </a>
          <ul class="pc-submenu">
            <?php if (tiene_acceso('recepcion_entrega_repuestos.php')): ?>
              <li class="pc-item">
                <a href="recepcion_entrega_repuestos.php" class="pc-link">
                  <i class="fas fa-box-open"></i>
                  <span class="pc-mtext">Recepción de Repuestos</span>
                </a>
              </li>
            <?php endif; ?>
            <?php if (tiene_acceso('seguimiento_ingresos_repuestos.php')): ?>
            <li class="pc-item">
              <a href="seguimiento_ingresos_repuestos.php" class="pc-link">
                <i class="fas fa-chart-line"></i>
                <span class="pc-mtext">Seguimiento de Ingresos</span>
              </a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>
        
        <!-- Control de Calidad - Enlace directo para Jefe de Taller -->
        <?php if (tiene_acceso('calidad.php')): ?>
        <li class="pc-item">
          <a href="calidad.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-clipboard-check"></i>
            </span>
            <span class="pc-mtext">Diagnóstico y Calidad</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Enlace directo para administradores (ya incluido en Gestión, pero por si se quiere destacar) -->
        <?php if ($usuario_rol === 'Administrador'): ?>
        <li class="pc-item">
          <a href="gestion_usuarios.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-users-cog"></i>
            </span>
            <span class="pc-mtext">Admin Usuarios</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Menú para Coordinador de Zona -->
        <?php if (tiene_acceso('inventario_coordinador.php') || tiene_acceso('coordinacion_jefe_taller.php') || tiene_acceso('control_gastos_vehiculos.php') || tiene_acceso('reportes_semanales.php')): ?>
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-map-marked-alt"></i>
            </span>
            <span class="pc-mtext">Coordinación de Zona</span>
            <span class="pc-arrow">
              <i class="fas fa-chevron-down"></i>
            </span>
          </a>
          <ul class="pc-submenu">
            <?php if (tiene_acceso('inventario_coordinador.php')): ?>
            <li class="pc-item">
              <a href="inventario_coordinador.php" class="pc-link">
                <span class="pc-mtext">Inventario</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('coordinacion_jefe_taller.php')): ?>
            <li class="pc-item">
              <a href="coordinacion_jefe_taller.php" class="pc-link">
                <span class="pc-mtext">Coordinación Jefe Taller</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('control_gastos_vehiculos.php')): ?>
            <li class="pc-item">
              <a href="control_gastos_vehiculos.php" class="pc-link">
                <span class="pc-mtext">Control Gastos</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('reportes_semanales.php')): ?>
            <li class="pc-item">
              <a href="reportes_semanales.php" class="pc-link">
                <span class="pc-mtext">Reportes Semanales</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('reportes.php')): ?>
            <li class="pc-item">
              <a href="reportes.php" class="pc-link">
                <span class="pc-mtext">Reportes</span>
              </a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <!-- Menú para Ejecutivo/a de Ventas -->
        <?php if (tiene_acceso('recepcion_devolucion_vehiculos.php') || tiene_acceso('coordinacion_taller_fallas.php') || tiene_acceso('vehiculos_asignados.php')): ?>
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-handshake"></i>
            </span>
            <span class="pc-mtext">Ventas Terreno</span>
            <span class="pc-arrow">
              <i class="fas fa-chevron-down"></i>
            </span>
          </a>
          <ul class="pc-submenu">
            <?php if (tiene_acceso('recepcion_devolucion_vehiculos.php')): ?>
            <li class="pc-item">
              <a href="recepcion_devolucion_vehiculos.php" class="pc-link">
                <span class="pc-mtext">Recepción/Devolución</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('coordinacion_taller_fallas.php')): ?>
            <li class="pc-item">
              <a href="coordinacion_taller_fallas.php" class="pc-link">
                <span class="pc-mtext">Coordinación Taller</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('vehiculos_asignados.php')): ?>
            <li class="pc-item">
              <a href="vehiculos_asignados.php" class="pc-link">
                <span class="pc-mtext">Vehículos Asignados</span>
              </a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <!-- Menú para Supervisor de Flotas -->
        <?php if (tiene_acceso('supervisar_politicas_uso.php') || tiene_acceso('gestion_incidentes_siniestros.php') || tiene_acceso('coordinacion_jefe_flota.php')): ?>
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-truck"></i>
            </span>
            <span class="pc-mtext">Supervisión Flotas</span>
            <span class="pc-arrow">
              <i class="fas fa-chevron-down"></i>
            </span>
          </a>
          <ul class="pc-submenu">
            <?php if (tiene_acceso('supervisar_politicas_uso.php')): ?>
            <li class="pc-item">
              <a href="supervisar_politicas_uso.php" class="pc-link">
                <span class="pc-mtext">Políticas de Uso</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('gestion_incidentes_siniestros.php')): ?>
            <li class="pc-item">
              <a href="gestion_incidentes_siniestros.php" class="pc-link">
                <span class="pc-mtext">Incidentes/Siniestros</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('coordinacion_jefe_flota.php')): ?>
            <li class="pc-item">
              <a href="coordinacion_jefe_flota.php" class="pc-link">
                <span class="pc-mtext">Coordinación Jefe Flota</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('reportes.php')): ?>
            <li class="pc-item">
              <a href="reportes.php" class="pc-link">
                <span class="pc-mtext">Reportes</span>
              </a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <!-- Menú para Encargado de Llaves -->
        <?php if (tiene_acceso('control_llaves.php') || tiene_acceso('registro_prestamos_temporales.php') || tiene_acceso('control_duplicados_chapas.php') || tiene_acceso('gestion_cambios_perdidas.php')): ?>
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-key"></i>
            </span>
            <span class="pc-mtext">Control Llaves</span>
            <span class="pc-arrow">
              <i class="fas fa-chevron-down"></i>
            </span>
          </a>
          <ul class="pc-submenu">
            <?php if (tiene_acceso('control_llaves.php')): ?>
            <li class="pc-item">
              <a href="control_llaves.php" class="pc-link">
                <span class="pc-mtext">Control de Llaves</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('registro_prestamos_temporales.php')): ?>
            <li class="pc-item">
              <a href="registro_prestamos_temporales.php" class="pc-link">
                <span class="pc-mtext">Préstamos Temporales</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('control_duplicados_chapas.php')): ?>
            <li class="pc-item">
              <a href="control_duplicados_chapas.php" class="pc-link">
                <span class="pc-mtext">Duplicados/Chapas</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('gestion_cambios_perdidas.php')): ?>
            <li class="pc-item">
              <a href="gestion_cambios_perdidas.php" class="pc-link">
                <span class="pc-mtext">Cambios/Pérdidas</span>
              </a>
            </li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
<!-- [ Sidebar Menu ] end -->

<style>
/* Estilos generales del sidebar */
.pc-sidebar {
  background: linear-gradient(180deg, #004B93 0%, #002D5A 100%);
  color: white;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

/* Header del sidebar con logo */
.sidebar-header {
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  background-color: rgba(0, 0, 0, 0.1);
}

.sidebar-logo {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.logo-img {
  height: 40px;
  width: auto;
  filter: brightness(0) invert(1);
}

.logo-text {
  color: white;
  font-size: 0.85rem;
  font-weight: 500;
  letter-spacing: 0.5px;
}

/* Tarjeta de usuario mejorada */
.pc-user-card {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 10px;
  backdrop-filter: blur(10px);
  margin: 15px;
  overflow: hidden;
}

.pc-user-card .card-body {
  padding: 1.25rem;
}

.avatar-initials {
  width: 50px;
  height: 50px;
  font-weight: bold;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #004B93 0%, #E21C21 100%);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.text-truncate {
  max-width: 140px;
}

/* Badges personalizados para roles */
.badge {
  font-size: 0.7em;
  padding: 0.3em 0.6em;
  border-radius: 4px;
}

.badge-admin {
  background-color: #E21C21;
  color: white;
}

.badge-jefe-taller {
  background-color: #FFC107;
  color: #212529;
}

.badge-mecanico {
  background-color: #17A2B8;
  color: white;
}

.badge-recepcionista {
  background-color: #28A745;
  color: white;
}

.badge-guardia {
  background-color: #6C757D;
  color: white;
}

.badge-supervisor {
  background-color: #6F42C1;
  color: white;
}

.badge-chofer {
  background-color: #FD7E14;
  color: white;
}

.badge-asistente-repuestos {
  background-color: #20C997;
  color: white;
}

.badge-coordinador-zona {
  background-color: #9C27B0;
  color: white;
}

.badge-ejecutivo-ventas {
  background-color: #2196F3;
  color: white;
}

.badge-supervisor-flotas {
  background-color: #FF9800;
  color: white;
}

.badge-encargado-llaves {
  background-color: #795548;
  color: white;
}

/* Botón de cerrar sesión */
.logout-btn {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  padding: 8px 12px;
  border-radius: 6px;
  transition: all 0.3s ease;
  width: 100%;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.logout-btn:hover {
  background-color: rgba(226, 28, 33, 0.2);
  color: white;
  border-color: #E21C21;
}

/* Navegación */
.pc-navbar {
  padding: 0 15px;
}

.pc-navbar .pc-item.pc-caption {
  padding: 15px 0 10px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.6);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 10px;
}

.pc-navbar .pc-item .pc-link {
  color: rgba(255, 255, 255, 0.8);
  padding: 12px 15px;
  border-radius: 8px;
  margin-bottom: 5px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
}

.pc-navbar .pc-item .pc-link:hover,
.pc-navbar .pc-item .pc-link.active {
  background-color: rgba(255, 255, 255, 0.1);
  color: white;
}

.pc-navbar .pc-item .pc-link .pc-micon {
  margin-right: 10px;
  width: 20px;
  text-align: center;
}

.pc-navbar .pc-item .pc-link .pc-mtext {
  flex-grow: 1;
}

.pc-navbar .pc-item .pc-link .pc-arrow {
  transition: transform 0.3s ease;
}

.pc-navbar .pc-item.pc-hasmenu.active .pc-arrow {
  transform: rotate(180deg);
}

/* Submenús */
.pc-submenu {
  background-color: rgba(0, 0, 0, 0.15);
  border-left: 3px solid #E21C21;
  margin-left: 20px;
  margin-top: 8px;
  margin-bottom: 12px;
  border-radius: 6px;
  overflow: hidden;
  padding: 6px 0;
  box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pc-submenu .pc-item {
  margin: 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.pc-submenu .pc-item:last-child {
  border-bottom: none;
}

.pc-submenu .pc-item .pc-link {
  padding-left: 20px;
  padding-right: 15px;
  padding-top: 12px;
  padding-bottom: 12px;
  font-size: 0.875rem;
  margin-bottom: 0;
  border-radius: 0;
  position: relative;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  color: rgba(255, 255, 255, 0.85);
}

.pc-submenu .pc-item .pc-link i {
  margin-right: 10px;
  font-size: 0.9rem;
  width: 18px;
  text-align: center;
  opacity: 0.9;
  transition: all 0.2s ease;
}

.pc-submenu .pc-item .pc-link:hover {
  background-color: rgba(255, 255, 255, 0.12);
  color: white;
  padding-left: 25px;
}

.pc-submenu .pc-item .pc-link:hover i {
  opacity: 1;
  transform: scale(1.1);
  color: #E21C21;
}

.pc-submenu .pc-item .pc-link.active {
  background-color: rgba(226, 28, 33, 0.2);
  color: white;
  border-left: 3px solid #E21C21;
  padding-left: 17px;
}

.pc-submenu .pc-item .pc-link.active i {
  color: #E21C21;
  opacity: 1;
}

/* Loader personalizado */
.loader-bg {
  background: #004B93;
}

.loader-track {
  background: rgba(255, 255, 255, 0.2);
}

.loader-fill {
  background: linear-gradient(90deg, #004B93 0%, #E21C21 100%);
}

/* Responsive */
@media (max-width: 1024px) {
  .pc-sidebar {
    position: fixed;
    left: -280px;
    top: 0;
    height: 100vh;
    z-index: 1030;
    transform: translateX(0);
    transition: left 0.3s ease;
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
  }
  
  .pc-sidebar.mobile-open {
    left: 0;
  }
  
  .sidebar-logo {
    flex-direction: row;
    gap: 10px;
  }
  
  .logo-text {
    font-size: 1rem;
  }
  
  /* Botón de cerrar en el sidebar para móviles */
  .sidebar-close-btn {
    display: block;
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 1.5rem;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
  }
  
  .sidebar-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
  }
}

@media (min-width: 1025px) {
  .sidebar-close-btn {
    display: none;
  }
  
  .mobile-menu-toggle {
    display: none !important;
  }
  
  .sidebar-overlay {
    display: none !important;
  }
  
  /* Área de detección de hover en el borde izquierdo */
  .sidebar-hover-trigger {
    display: block !important;
    position: fixed !important;
    top: 0;
    left: 0;
    width: 30px;
    height: 100vh;
    z-index: 1029 !important;
    background: transparent;
    cursor: pointer;
    pointer-events: auto;
    opacity: 1;
  }
  
  /* Cuando el sidebar está colapsado, mostrar el área de hover */
  .pc-sidebar.pc-sidebar-hide ~ .sidebar-hover-trigger,
  body:not(:has(.pc-sidebar:not(.pc-sidebar-hide))) .sidebar-hover-trigger {
    display: block !important;
    opacity: 1 !important;
    pointer-events: auto !important;
  }
  
  /* Cuando el sidebar está abierto, mantener visible pero con menor prioridad */
  .pc-sidebar:not(.pc-sidebar-hide) ~ .sidebar-hover-trigger {
    display: block !important;
    opacity: 0.3;
    pointer-events: auto;
  }
  
  /* Efecto visual sutil cuando el mouse está sobre el área de detección */
  .sidebar-hover-trigger:hover {
    background: rgba(0, 75, 147, 0.1);
    opacity: 1 !important;
  }
}

@media (max-width: 1024px) {
  .sidebar-hover-trigger {
    display: none !important;
  }
}
</style>

<script>
// Script mejorado para la interactividad del sidebar
document.addEventListener('DOMContentLoaded', function() {
  // Confirmación al cerrar sesión
  const logoutBtn = document.querySelector('.logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function(e) {
      if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        e.preventDefault();
      }
    });
  }

  // Manejo de menús desplegables
  const hasMenuItems = document.querySelectorAll('.pc-hasmenu > .pc-link');
  hasMenuItems.forEach(item => {
    item.addEventListener('click', function(e) {
      e.preventDefault();
      const parent = this.parentElement;
      
      // Cerrar otros menús abiertos
      document.querySelectorAll('.pc-hasmenu.active').forEach(openMenu => {
        if (openMenu !== parent) {
          openMenu.classList.remove('active');
        }
      });
      
      // Alternar el menú actual
      parent.classList.toggle('active');
    });
  });

  // Cerrar menús al hacer clic fuera (en móviles)
  if (window.innerWidth < 1024) {
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.pc-sidebar')) {
        const openMenus = document.querySelectorAll('.pc-hasmenu.active');
        openMenus.forEach(menu => menu.classList.remove('active'));
      }
    });
  }
  
  // Efecto de hover mejorado para elementos del menú
  const menuItems = document.querySelectorAll('.pc-navbar .pc-item:not(.pc-caption)');
  menuItems.forEach(item => {
    item.addEventListener('mouseenter', function() {
      this.style.transform = 'translateX(5px)';
    });
    
    item.addEventListener('mouseleave', function() {
      this.style.transform = 'translateX(0)';
    });
  });
  
  // Funcionalidad del menú móvil
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const sidebar = document.getElementById('pcSidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
  
  function openSidebar() {
    sidebar.classList.add('mobile-open');
    sidebarOverlay.classList.add('active');
    document.body.classList.add('sidebar-open');
    document.body.style.overflow = 'hidden';
    // Asegurar que el contenido no se mueva
    const container = document.querySelector('.pc-container');
    if (container) {
      container.style.marginLeft = '0';
      container.style.width = '100%';
    }
  }
  
  function closeSidebar() {
    sidebar.classList.remove('mobile-open');
    sidebarOverlay.classList.remove('active');
    document.body.classList.remove('sidebar-open');
    document.body.style.overflow = '';
    // Asegurar que el contenido permanezca en su lugar
    const container = document.querySelector('.pc-container');
    if (container) {
      container.style.marginLeft = '0';
      container.style.width = '100%';
    }
  }
  
  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', openSidebar);
  }
  
  if (sidebarCloseBtn) {
    sidebarCloseBtn.addEventListener('click', closeSidebar);
  }
  
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebar);
  }
  
  // Cerrar sidebar al hacer clic en un enlace en móviles
  if (window.innerWidth <= 1024) {
    const sidebarLinks = document.querySelectorAll('.pc-sidebar .pc-link');
    sidebarLinks.forEach(link => {
      link.addEventListener('click', function() {
        // Solo cerrar si no es un submenú
        if (!this.parentElement.classList.contains('pc-hasmenu')) {
          setTimeout(closeSidebar, 300);
        }
      });
    });
  }
  
  // Cerrar sidebar al redimensionar la ventana si se vuelve desktop
  window.addEventListener('resize', function() {
    if (window.innerWidth > 1024) {
      closeSidebar();
    }
  });
  
  // Funcionalidad de hover para abrir sidebar en desktop
  const sidebarHoverTrigger = document.getElementById('sidebarHoverTrigger');
  let hoverTimeout;
  let isHovering = false;
  
  // Función para actualizar el contenedor cuando el sidebar se oculta/muestra
  function updateContainerWidth() {
    const container = document.querySelector('.pc-container');
    if (container) {
      if (sidebar.classList.contains('pc-sidebar-hide')) {
        container.style.marginLeft = '0';
        container.style.width = '100%';
      } else {
        container.style.marginLeft = '280px';
        container.style.width = 'calc(100% - 280px)';
      }
    }
  }
  
  // Ocultar sidebar automáticamente al cargar la página (especialmente para Guardia)
  if (sidebar && window.innerWidth > 1024) {
    // Inicializar contenedor sin margen (sidebar oculto)
    updateContainerWidth();
    
    // Ocultar sidebar al cargar (más rápido)
    setTimeout(function() {
      sidebar.classList.add('pc-sidebar-hide');
      sidebar.style.transform = 'translateX(-100%)';
      sidebar.style.transition = 'transform 0.3s ease';
      updateContainerWidth();
    }, 100); // Delay muy corto para ocultar rápidamente
  } else if (sidebar && window.innerWidth <= 1024) {
    // En móviles, asegurar que el contenedor use todo el ancho
    updateContainerWidth();
  }
  
  // Usar la variable sidebar ya declarada arriba
  if (sidebarHoverTrigger && sidebar && window.innerWidth > 1024) {
    // Asegurar que el área de detección esté siempre visible cuando el sidebar está oculto
    function updateHoverTriggerVisibility() {
      if (sidebar.classList.contains('pc-sidebar-hide')) {
        sidebarHoverTrigger.style.display = 'block';
        sidebarHoverTrigger.style.opacity = '1';
        sidebarHoverTrigger.style.pointerEvents = 'auto';
      } else {
        sidebarHoverTrigger.style.opacity = '0.3';
      }
      updateContainerWidth();
    }
    
    // Verificar estado inicial
    updateHoverTriggerVisibility();
    
    // Observar cambios en la clase del sidebar
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
          updateHoverTriggerVisibility();
        }
      });
    });
    
    observer.observe(sidebar, {
      attributes: true,
      attributeFilter: ['class']
    });
    
    // Abrir sidebar cuando el mouse entra en el área de detección
    sidebarHoverTrigger.addEventListener('mouseenter', function() {
      clearTimeout(hoverTimeout);
      isHovering = true;
      if (sidebar) {
        sidebar.classList.remove('pc-sidebar-hide');
        sidebar.style.transform = 'translateX(0)';
        sidebar.style.left = '0';
        sidebar.style.transition = 'transform 0.3s ease';
        updateContainerWidth();
      }
    });
    
    // Mantener el sidebar abierto cuando el mouse está sobre él
    sidebar.addEventListener('mouseenter', function() {
      clearTimeout(hoverTimeout);
      isHovering = true;
      sidebar.classList.remove('pc-sidebar-hide');
      sidebar.style.transform = 'translateX(0)';
      sidebar.style.left = '0';
      sidebar.style.transition = 'transform 0.3s ease';
      updateContainerWidth();
    });
    
    // Cerrar el sidebar cuando el mouse sale (con delay reducido)
    sidebar.addEventListener('mouseleave', function(e) {
      isHovering = false;
      // Verificar si el mouse se movió al área de detección
      const relatedTarget = e.relatedTarget;
      if (relatedTarget && (relatedTarget === sidebarHoverTrigger || sidebarHoverTrigger.contains(relatedTarget))) {
        return; // No cerrar si el mouse se movió al área de detección
      }
      
      hoverTimeout = setTimeout(function() {
        if (!isHovering && sidebar && !sidebar.matches(':hover') && !sidebarHoverTrigger.matches(':hover')) {
          sidebar.classList.add('pc-sidebar-hide');
          sidebar.style.transform = 'translateX(-100%)';
          sidebar.style.transition = 'transform 0.3s ease';
          updateContainerWidth();
        }
      }, 200); // Delay reducido de 500ms a 200ms
    });
    
    // Cerrar cuando el mouse sale del área de detección
    sidebarHoverTrigger.addEventListener('mouseleave', function(e) {
      const relatedTarget = e.relatedTarget;
      // Verificar si el mouse se movió al sidebar
      if (relatedTarget && (relatedTarget === sidebar || sidebar.contains(relatedTarget))) {
        return; // No cerrar si el mouse se movió al sidebar
      }
      
      isHovering = false;
      hoverTimeout = setTimeout(function() {
        if (!isHovering && sidebar && !sidebar.matches(':hover')) {
          sidebar.classList.add('pc-sidebar-hide');
          sidebar.style.transform = 'translateX(-100%)';
          sidebar.style.transition = 'transform 0.3s ease';
          updateContainerWidth();
        }
      }, 200); // Delay reducido
    });
    
    // Cerrar sidebar al hacer clic fuera de él
    document.addEventListener('click', function(e) {
      if (window.innerWidth > 1024) {
        // Si el clic no está en el sidebar ni en el trigger, cerrar el sidebar
        if (!sidebar.contains(e.target) && !sidebarHoverTrigger.contains(e.target)) {
          if (!sidebar.classList.contains('pc-sidebar-hide')) {
            sidebar.classList.add('pc-sidebar-hide');
            sidebar.style.transform = 'translateX(-100%)';
            sidebar.style.transition = 'transform 0.3s ease';
            updateContainerWidth();
          }
        }
      }
    });
  }
});
</script>