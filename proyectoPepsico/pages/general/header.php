<?php
// Al inicio del header.php, después de tus includes existentes
include 'funciones_notificaciones.php';

// Obtener notificaciones del usuario actual
$notificaciones = [];
$contador_notificaciones = 0;

if (isset($usuario_id) && !empty($usuario_id)) {
    $notificaciones = obtenerNotificacionesUsuario($usuario_id, 5);
    $contador_notificaciones = obtenerContadorNotificaciones($usuario_id);
}

// Procesar marcar como leída si se envió la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_leida'])) {
    $notificacion_id = intval($_POST['notificacion_id']);
    if ($notificacion_id > 0) {
        marcarNotificacionLeida($notificacion_id, $usuario_id);
        // Recargar la página o usar AJAX para actualizar
        echo "<script>window.location.reload();</script>";
    }
}
?>

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
                
                <!-- Logo -->
                <li class="pc-h-item d-none d-md-inline-flex align-items-center">
                    <a href="<?php echo obtener_pagina_principal($usuario_rol); ?>" class="header-logo">
                        <img src="../assets/images/pepsicoLogo.png" alt="PepsiCo" height="32" class="me-2">
                        <span class="logo-text">Sistema Taller</span>
                    </a>
                </li>
            </ul>
        </div>
        <!-- [Mobile Menu Block end] -->

        <!-- Controles del usuario -->
        <div class="ms-auto">
            <ul class="list-unstyled">
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
                        <?php if ($contador_notificaciones > 0): ?>
                            <span class="badge bg-danger notification-badge"><?php echo $contador_notificaciones; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown notification-dropdown">
                        <div class="dropdown-header">
                            <h6 class="mb-0">Notificaciones</h6>
                            <?php if ($contador_notificaciones > 0): ?>
                                <span class="text-muted"><?php echo $contador_notificaciones; ?> sin leer</span>
                            <?php else: ?>
                                <span class="text-muted">0 sin leer</span>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-body">
                            <?php if (empty($notificaciones)): ?>
                                <div class="text-center py-3">
                                    <i class="ti ti-bell-off text-muted mb-2" style="font-size: 2rem;"></i>
                                    <p class="text-muted mb-0">No hay notificaciones</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notificaciones as $notificacion): ?>
                                    <div class="notification-item">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-grow-1 me-3">
                                                <h6 class="notification-title mb-1"><?php echo htmlspecialchars($notificacion['titulo']); ?></h6>
                                                <p class="notification-message mb-1"><?php echo htmlspecialchars($notificacion['mensaje']); ?></p>
                                                <small class="text-muted">
                                                    <i class="ti ti-clock"></i>
                                                    <?php 
                                                    $fecha = new DateTime($notificacion['fecha_creacion']);
                                                    echo $fecha->format('d/m/Y H:i');
                                                    ?>
                                                </small>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="notificacion_id" value="<?php echo $notificacion['id']; ?>">
                                                    <button type="submit" name="marcar_leida" class="btn btn-sm btn-outline-success" title="Marcar como leída">
                                                        <i class="ti ti-check"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-footer">
                            <a href="notificaciones.php" class="btn btn-sm btn-primary w-100">
                                Ver todas las notificaciones
                            </a>
                        </div>
                    </div>
                </li>

                <!-- User Profile - Solo avatar como en sidebar -->
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <div class="header-avatar">
                            <?php
                            // Generar iniciales del nombre de usuario (igual que en sidebar)
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
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <!-- Header del dropdown igual al sidebar -->
                        <div class="dropdown-header user-dropdown-header">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="dropdown-avatar">
                                        <?php echo $iniciales ?: 'U'; ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($usuario_actual ?? 'Usuario'); ?></h6>
                                    <small class="text-muted">
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
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer con cerrar sesión -->
                        <div class="dropdown-footer">
                            <a href="../auth/logout.php" class="dropdown-item text-danger">
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

/* Avatar del header (igual que sidebar) */
.header-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #004B93 0%, #E21C21 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.header-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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
    overflow: hidden;
}

/* Header del dropdown de usuario (estilo sidebar) */
.user-dropdown-header {
    background: linear-gradient(135deg, #004B93 0%, #002D5A 100%);
    color: white;
    padding: 1.25rem;
    border-radius: 8px 8px 0 0;
}

.user-dropdown-header h6 {
    color: white;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.user-dropdown-header .text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
}

.dropdown-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #004B93 0%, #E21C21 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Badges de roles (igual que sidebar) */
.badge {
    font-size: 0.7rem;
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    font-weight: 500;
}

.badge-admin { background-color: #E21C21; color: white; }
.badge-jefe-taller { background-color: #FFC107; color: #212529; }
.badge-mecanico { background-color: #17A2B8; color: white; }
.badge-recepcionista { background-color: #28A745; color: white; }
.badge-guardia { background-color: #6C757D; color: white; }
.badge-supervisor { background-color: #6F42C1; color: white; }
.badge-chofer { background-color: #FD7E14; color: white; }

/* Cuerpo del dropdown */
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
    border: none;
    background: none;
    width: 100%;
    text-align: left;
}

.dropdown-item:hover {
    background-color: rgba(0, 75, 147, 0.1);
    color: #004B93;
}

.dropdown-item.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.dropdown-item i {
    width: 20px;
    text-align: center;
    margin-right: 0.5rem;
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
    min-width: 350px;
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-title {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 4px;
    color: #2c3e50;
    line-height: 1.2;
}

.notification-message {
    font-size: 0.8rem;
    margin-bottom: 6px;
    color: #6c757d;
    line-height: 1.3;
}

.notification-dropdown .dropdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    position: sticky;
    top: 0;
    z-index: 1;
}

.notification-dropdown .dropdown-body {
    padding: 0;
}

.notification-dropdown .dropdown-footer {
    padding: 12px 15px;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
    position: sticky;
    bottom: 0;
}

/* Botón de marcar como leída */
.btn-outline-success {
    border-color: #28a745;
    color: #28a745;
    padding: 0.25rem 0.5rem;
}

.btn-outline-success:hover {
    background-color: #28a745;
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .header-wrapper {
        padding: 0.5rem;
    }
    
    .pc-head-link {
        padding: 0.4rem 0.6rem;
    }
    
    .notification-dropdown {
        min-width: 300px;
        right: 0 !important;
        left: auto !important;
    }
    
    .pc-h-dropdown {
        min-width: 250px;
    }
    
    .header-avatar {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
    
    .notification-dropdown {
        min-width: 280px;
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

/* Estados de tema oscuro */
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

[data-pc-theme="dark"] .dropdown-body .dropdown-item {
    color: #cbd5e0;
}

[data-pc-theme="dark"] .dropdown-body .dropdown-item:hover {
    color: white;
    background-color: rgba(255, 255, 255, 0.1);
}

[data-pc-theme="dark"] .dropdown-footer {
    background: #2d3748;
    border-top-color: #4a5568;
}

[data-pc-theme="dark"] .notification-item {
    border-bottom-color: #4a5568;
}

[data-pc-theme="dark"] .notification-item:hover {
    background-color: #4a5568;
}

[data-pc-theme="dark"] .notification-title {
    color: #e2e8f0;
}

[data-pc-theme="dark"] .notification-message {
    color: #a0aec0;
}

[data-pc-theme="dark"] .notification-dropdown .dropdown-header,
[data-pc-theme="dark"] .notification-dropdown .dropdown-footer {
    background-color: #2d3748;
    border-color: #4a5568;
}

[data-pc-theme="dark"] .user-dropdown-header {
    background: linear-gradient(135deg, #004B93 0%, #002D5A 100%);
}

/* Scrollbar personalizado para notificaciones */
.notification-dropdown::-webkit-scrollbar {
    width: 6px;
}

.notification-dropdown::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.notification-dropdown::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.notification-dropdown::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

[data-pc-theme="dark"] .notification-dropdown::-webkit-scrollbar-track {
    background: #4a5568;
}

[data-pc-theme="dark"] .notification-dropdown::-webkit-scrollbar-thumb {
    background: #718096;
}

[data-pc-theme="dark"] .notification-dropdown::-webkit-scrollbar-thumb:hover {
    background: #90a3bf;
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

// Actualizar notificaciones cada 30 segundos
function actualizarNotificaciones() {
    fetch('?actualizar_notificaciones=1')
        .then(response => response.text())
        .then(data => {
            // Aquí puedes actualizar el contador y la lista de notificaciones
            console.log('Notificaciones actualizadas');
        })
        .catch(error => console.error('Error al actualizar notificaciones:', error));
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
    
    // Confirmación al cerrar sesión
    const logoutLink = document.querySelector('.dropdown-item.text-danger');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                e.preventDefault();
            }
        });
    }
    
    // Iniciar actualización automática de notificaciones
    setInterval(actualizarNotificaciones, 30000);
    
    // Manejar el cierre del dropdown de notificaciones al hacer clic fuera
    document.addEventListener('click', function(e) {
        const notificationDropdown = document.querySelector('.notification-dropdown');
        const notificationToggle = document.querySelector('.pc-head-link[data-bs-toggle="dropdown"]');
        
        if (notificationDropdown && notificationToggle && 
            !notificationDropdown.contains(e.target) && 
            !notificationToggle.contains(e.target)) {
            // El dropdown se cierra automáticamente con Bootstrap
        }
    });
});

// Función para marcar notificación como leída con AJAX (opcional)
function marcarNotificacionLeida(notificacionId) {
    fetch('marcar_notificacion_leida.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notificacion_id=' + notificacionId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar la interfaz
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 1) {
                    badge.textContent = currentCount - 1;
                } else {
                    badge.remove();
                }
            }
            // Remover la notificación de la lista
            const notificationItem = document.querySelector('[data-notification-id="' + notificacionId + '"]');
            if (notificationItem) {
                notificationItem.remove();
            }
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>