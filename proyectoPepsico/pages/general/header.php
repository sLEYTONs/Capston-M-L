<!-- [ Header ] start -->
<header class="pc-header">
    <div class="header-wrapper">
        <!-- [Mobile Menu Block] start -->
        <div class="me-auto pc-mob-drp">
            <ul class="list-unstyled">
                <!-- Menu collapse Icon -->
                <li class="pc-h-item pc-sidebar-collapse">
                    <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>
                <li class="pc-h-item pc-sidebar-popup">
                    <a href="#" class="pc-head-link ms-0" id="mobile-collapse">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>
                
                <!-- Logo solo en desktop -->
                <li class="pc-h-item d-none d-md-inline-flex align-items-center">
                    <a href="<?php echo obtener_pagina_principal($usuario_rol); ?>" class="header-logo">
                        <img src="../assets/images/pepsicoLogo.png" alt="PepsiCo" height="32" class="me-2">
                        <span class="logo-text">Sistema Taller</span>
                    </a>
                </li>
            </ul>
        </div>
        <!-- [Mobile Menu Block end] -->

        <!-- Información del usuario y controles -->
        <div class="ms-auto">
            <ul class="list-unstyled">
                <!-- Información del usuario visible -->
                <li class="pc-h-item d-none d-md-inline-flex align-items-center me-3">
                    <div class="user-info">
                        <div class="user-name fw-semibold"><?php echo htmlspecialchars($usuario_actual ?? 'Usuario'); ?></div>
                        <div class="user-role">
                            <?php 
                            $rol = $usuario_rol ?? 'Usuario';
                            $badge_class = [
                                'Administrador' => 'badge-admin',
                                'Jefe de Taller' => 'badge-jefe-taller',
                                'Mecánico' => 'badge-mecanico',
                                'Recepcionista' => 'badge-recepcionista',
                                'Guardia' => 'badge-guardia',
                                'Supervisor' => 'badge-supervisor',
                                'Chofer' => 'badge-chofer'
                            ][$rol] ?? 'badge-secondary';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($rol); ?></span>
                        </div>
                    </div>
                </li>

                <!-- Selector de tema -->
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <i class="ti ti-sun" id="theme-icon"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <a href="#!" class="dropdown-item" onclick="layout_change('dark')">
                            <i class="ti ti-moon"></i>
                            <span>Oscuro</span>
                        </a>
                        <a href="#!" class="dropdown-item" onclick="layout_change('light')">
                            <i class="ti ti-sun"></i>
                            <span>Claro</span>
                        </a>
                        <a href="#!" class="dropdown-item" onclick="layout_change('auto')">
                            <i class="ti ti-device-desktop"></i>
                            <span>Automático</span>
                        </a>
                    </div>
                </li>

                <!-- Notificaciones -->
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <i class="ti ti-bell"></i>
                        <span class="badge bg-danger notification-badge">3</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown notification-dropdown">
                        <div class="dropdown-header">
                            <h6 class="mb-0">Notificaciones</h6>
                            <span class="text-muted">3 sin leer</span>
                        </div>
                        <div class="dropdown-body">
                            <a href="#!" class="dropdown-item">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="notification-icon bg-primary">
                                            <i class="ti ti-car"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0">Nuevo vehículo registrado</p>
                                        <small class="text-muted">Hace 5 minutos</small>
                                    </div>
                                </div>
                            </a>
                            <a href="#!" class="dropdown-item">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="notification-icon bg-warning">
                                            <i class="ti ti-alert-triangle"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0">Tarea pendiente de revisión</p>
                                        <small class="text-muted">Hace 1 hora</small>
                                    </div>
                                </div>
                            </a>
                            <a href="#!" class="dropdown-item">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="notification-icon bg-success">
                                            <i class="ti ti-check"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0">Reparación completada</p>
                                        <small class="text-muted">Hace 2 horas</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="dropdown-footer">
                            <a href="#!" class="btn btn-sm btn-primary w-100">Ver todas</a>
                        </div>
                    </div>
                </li>

                <!-- User Profile -->
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <span class="user-avatar">
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
                        </span>
                        <span class="user-name d-none d-md-inline-block">
                            <?php echo htmlspecialchars($usuario_actual ?? 'Usuario'); ?>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <div class="dropdown-header">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="user-avatar-lg">
                                        <?php echo $iniciales ?: 'U'; ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($usuario_actual ?? 'Usuario'); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($usuario_rol ?? 'Usuario'); ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-body">
                            <a href="#!" class="dropdown-item">
                                <i class="ti ti-user me-2"></i>
                                <span>Mi Perfil</span>
                            </a>
                            <a href="#!" class="dropdown-item">
                                <i class="ti ti-settings me-2"></i>
                                <span>Configuración</span>
                            </a>
                            <a href="#!" class="dropdown-item">
                                <i class="ti ti-bell me-2"></i>
                                <span>Notificaciones</span>
                            </a>
                        </div>
                        <div class="dropdown-footer">
                            <a href="../auth/logout.php" class="dropdown-item">
                                <i class="ti ti-power me-2"></i>
                                <span>Cerrar Sesión</span>
                            </a>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>
<!-- [ Header ] end -->

<style>
/* Header Styles */
.pc-header {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-bottom: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    backdrop-filter: blur(10px);
}

.header-wrapper {
    padding: 0.75rem 1rem;
}

/* Logo en header */
.header-logo {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #004B93;
    font-weight: 600;
    font-size: 1.1rem;
}

.header-logo:hover {
    color: #E21C21;
}

.logo-text {
    margin-left: 0.5rem;
}

/* Información del usuario */
.user-info {
    text-align: right;
}

.user-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #004B93;
    margin-bottom: 0.1rem;
}

.user-role .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* Badges de roles */
.badge-admin { background-color: #E21C21; color: white; }
.badge-jefe-taller { background-color: #FFC107; color: #212529; }
.badge-mecanico { background-color: #17A2B8; color: white; }
.badge-recepcionista { background-color: #28A745; color: white; }
.badge-guardia { background-color: #6C757D; color: white; }
.badge-supervisor { background-color: #6F42C1; color: white; }
.badge-chofer { background-color: #FD7E14; color: white; }

/* Avatar de usuario */
.user-avatar {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #004B93 0%, #E21C21 100%);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.9rem;
    margin-right: 0.5rem;
}

.user-avatar-lg {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #004B93 0%, #E21C21 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
}

/* Iconos del header */
.pc-head-link {
    color: #6c757d;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    position: relative;
}

.pc-head-link:hover {
    color: #004B93;
    background-color: rgba(0, 75, 147, 0.1);
}

/* Dropdowns */
.pc-h-dropdown {
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-radius: 8px;
    min-width: 280px;
}

.dropdown-header {
    background: linear-gradient(135deg, #004B93 0%, #002D5A 100%);
    color: white;
    border-radius: 8px 8px 0 0;
    padding: 1rem;
}

.dropdown-header h6 {
    color: white;
    margin-bottom: 0.25rem;
}

.dropdown-body {
    padding: 0.5rem 0;
}

.dropdown-footer {
    padding: 0.75rem;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
}

.dropdown-item {
    padding: 0.75rem 1rem;
    color: #495057;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background-color: rgba(0, 75, 147, 0.1);
    color: #004B93;
}

.dropdown-item i {
    width: 20px;
    text-align: center;
}

/* Notificaciones */
.notification-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    font-size: 0.6rem;
    padding: 0.2rem 0.4rem;
}

.notification-dropdown {
    min-width: 320px;
}

.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.notification-dropdown .dropdown-item {
    border-bottom: 1px solid #f8f9fa;
    padding: 0.75rem 1rem;
}

.notification-dropdown .dropdown-item:last-child {
    border-bottom: none;
}

.notification-dropdown .dropdown-item p {
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.notification-dropdown .dropdown-item small {
    font-size: 0.75rem;
}

/* Responsive */
@media (max-width: 768px) {
    .header-wrapper {
        padding: 0.5rem;
    }
    
    .pc-head-link {
        padding: 0.4rem 0.6rem;
    }
    
    .user-info {
        display: none;
    }
    
    .notification-dropdown {
        min-width: 280px;
        right: 0 !important;
        left: auto !important;
    }
    
    .pc-h-dropdown {
        min-width: 250px;
    }
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.pc-h-dropdown {
    animation: fadeIn 0.3s ease-out;
}

/* Estados de tema */
[data-pc-theme="dark"] .pc-header {
    background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
    border-bottom-color: #4a5568;
}

[data-pc-theme="dark"] .pc-head-link {
    color: #cbd5e0;
}

[data-pc-theme="dark"] .pc-head-link:hover {
    color: white;
    background-color: rgba(255, 255, 255, 0.1);
}

[data-pc-theme="dark"] .header-logo {
    color: white;
}
</style>

<script>
// Funcionalidad para el cambio de tema
function layout_change(theme) {
    const body = document.body;
    const themeIcon = document.getElementById('theme-icon');
    
    // Remover clases existentes
    body.removeAttribute('data-pc-theme');
    body.removeAttribute('data-pc-theme_contrast');
    
    // Aplicar nuevo tema
    if (theme === 'dark') {
        body.setAttribute('data-pc-theme', 'dark');
        body.setAttribute('data-pc-theme_contrast', 'default');
        themeIcon.className = 'ti ti-moon';
        localStorage.setItem('theme', 'dark');
    } else if (theme === 'light') {
        body.setAttribute('data-pc-theme', 'light');
        themeIcon.className = 'ti ti-sun';
        localStorage.setItem('theme', 'light');
    } else {
        // Tema automático
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            body.setAttribute('data-pc-theme', 'dark');
            body.setAttribute('data-pc-theme_contrast', 'default');
            themeIcon.className = 'ti ti-device-desktop';
        } else {
            body.setAttribute('data-pc-theme', 'light');
            themeIcon.className = 'ti ti-device-desktop';
        }
        localStorage.setItem('theme', 'auto');
    }
}

// Cargar tema guardado al iniciar
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    layout_change(savedTheme);
    
    // Detectar cambios en la preferencia del sistema
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (localStorage.getItem('theme') === 'auto') {
            layout_change('auto');
        }
    });
    
    // Manejo de notificaciones
    const notificationBell = document.querySelector('.pc-head-link .ti-bell');
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            // Aquí puedes agregar lógica para marcar notificaciones como leídas
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.style.display = 'none';
            }
        });
    }
});
</script>