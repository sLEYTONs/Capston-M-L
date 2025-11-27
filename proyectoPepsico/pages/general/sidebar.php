<div class="loader-bg">
  <div class="loader-track">
    <div class="loader-fill"></div>
  </div>
</div>
<!-- [ Pre-loader ] End -->

<!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <!-- Header del sidebar con logo PepsiCo -->
    <div class="sidebar-header">
      <div class="d-flex align-items-center justify-content-center py-3">
        <div class="sidebar-logo">
          <img src="../assets/images/pepsicoLogo.png" alt="PepsiCo" class="logo-img">
          <span class="logo-text">Taller Mecánico</span>
        </div>
      </div>
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

        <!-- Comunicación Flota y Proveedores -->
        <?php if (tiene_acceso('comunicacion_flota_proveedores.php')): ?>
        <li class="pc-item">
          <a href="comunicacion_flota_proveedores.php" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-comments"></i>
            </span>
            <span class="pc-mtext">Comunicación Flota/Proveedores</span>
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
        <?php if (tiene_acceso('repuestos.php') || tiene_acceso('solicitar_repuestos.php') || tiene_acceso('estado_solicitudes_repuestos.php')): ?>
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon">
              <i class="fas fa-cogs"></i>
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
                <span class="pc-mtext">Inventario</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('solicitar_repuestos.php')): ?>
            <li class="pc-item">
              <a href="solicitar_repuestos.php" class="pc-link">
                <span class="pc-mtext">Solicitar Repuestos</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('estado_solicitudes_repuestos.php')): ?>
            <li class="pc-item">
              <a href="estado_solicitudes_repuestos.php" class="pc-link">
                <span class="pc-mtext">Estado de Solicitudes</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('recepcion_entrega_repuestos.php')): ?>
            <li class="pc-item">
              <a href="recepcion_entrega_repuestos.php" class="pc-link">
                <span class="pc-mtext">Recepción y Entrega</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('registro_insumos_vehiculo.php')): ?>
            <li class="pc-item">
              <a href="registro_insumos_vehiculo.php" class="pc-link">
                <span class="pc-mtext">Registro de Insumos</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('comunicacion_proveedores.php')): ?>
            <li class="pc-item">
              <a href="comunicacion_proveedores.php" class="pc-link">
                <span class="pc-mtext">Comunicación Proveedores</span>
              </a>
            </li>
            <?php endif; ?>
            <?php if (tiene_acceso('gestion_repuestos_jefe.php')): ?>
            <li class="pc-item">
              <a href="gestion_repuestos_jefe.php" class="pc-link">
                <span class="pc-mtext">Gestión con Jefe</span>
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
  background-color: rgba(0, 0, 0, 0.2);
  border-left: 3px solid #E21C21;
  margin-left: 15px;
  border-radius: 0 0 8px 8px;
  overflow: hidden;
}

.pc-submenu .pc-item .pc-link {
  padding-left: 45px;
  font-size: 0.9rem;
  margin-bottom: 0;
  border-radius: 0;
}

.pc-submenu .pc-item .pc-link:hover {
  background-color: rgba(255, 255, 255, 0.05);
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
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }
  
  .pc-sidebar.mobile-open {
    transform: translateX(0);
  }
  
  .sidebar-logo {
    flex-direction: row;
    gap: 10px;
  }
  
  .logo-text {
    font-size: 1rem;
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
});
</script>