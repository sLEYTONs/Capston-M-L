<div class="loader-bg">
  <div class="loader-track">
    <div class="loader-fill"></div>
  </div>
</div>
<!-- [ Pre-loader ] End -->

<!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="navbar-content">
      <!-- Tarjeta de usuario -->
      <div class="card pc-user-card">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <!-- Avatar -->
            <div class="flex-shrink-0">
              <img src="../assets/images/user/avatar-1.jpg" alt="user-image" class="user-avtar wid-45 rounded-circle" />
            </div>

            <!-- Info del usuario -->
            <div class="flex-grow-1 ms-3">
              <h6 class="mb-0"><?php echo htmlspecialchars($usuario_actual); ?> A</h6>
              <small><?php echo htmlspecialchars($usuario_rol); ?></small>
            </div>
          </div>

          <!-- Link cerrar sesión -->
          <div class="pc-user-links mt-3 text-center">
            <a href="../auth/logout.php" class="d-inline-flex align-items-center">
              <i class="ti ti-power me-2"></i>
              <span>Cerrar Sesión</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Menú lateral -->
      <ul class="pc-navbar">
        <li class="pc-item pc-hasmenu">
          <a href="index.php" class="pc-link">
            <span class="pc-micon">
              <svg class="pc-icon">
                <use xlink:href="#custom-document-filter"></use>
              </svg>
            </span>
            <span class="pc-mtext">Ingreso Vehículos</span>
          </a>
        </li>
        <li class="pc-item pc-hasmenu">
          <a href="consulta.php" class="pc-link">
            <span class="pc-micon">
              <svg class="pc-icon">
                <use xlink:href="#custom-document-filter"></use>
              </svg>
            </span>
            <span class="pc-mtext">Consulta</span>
          </a>
        </li>
        <li class="pc-item pc-hasmenu">
          <a href="reportes.php" class="pc-link">
            <span class="pc-micon">
              <svg class="pc-icon">
                <use xlink:href="#custom-document-filter"></use>
              </svg>
            </span>
            <span class="pc-mtext">Reportes</span>
          </a>
        </li>
        <li class="pc-item pc-hasmenu">
          <a href="marcas.php" class="pc-link">
            <span class="pc-micon">
              <svg class="pc-icon">
                <use xlink:href="#custom-document-filter"></use>
              </svg>
            </span>
            <span class="pc-mtext">Marcas</span>
          </a>
        </li>
        <li class="pc-item pc-hasmenu">
          <a href="base_datos.php" class="pc-link">
            <span class="pc-micon">
              <svg class="pc-icon">
                <use xlink:href="#custom-document-filter"></use>
              </svg>
            </span>
            <span class="pc-mtext">Base de Datos</span>
          </a>
        </li>
        <li class="pc-item pc-hasmenu">
          <a href="gestion_usuarios.php" class="pc-link">
            <span class="pc-micon">
              <svg class="pc-icon">
                <use xlink:href="#custom-document-filter"></use>
              </svg>
            </span>
            <span class="pc-mtext">Gestión de Usuarios</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<!-- [ Sidebar Menu ] end -->