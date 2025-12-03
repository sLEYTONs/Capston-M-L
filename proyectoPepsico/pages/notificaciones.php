<?php
include 'general/middle.php';
include 'general/funciones_notificaciones.php';

// Procesar marcar como leída (una notificación individual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_leida']) && !headers_sent()) {
    $notificacion_id = intval($_POST['notificacion_id'] ?? 0);
    if ($notificacion_id > 0) {
        marcarNotificacionLeida($notificacion_id, $usuario_id);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Procesar marcar todas como leídas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_todas_leidas']) && !headers_sent()) {
    $conn = conectar_Pepsico();
    if ($conn) {
        $sql = "UPDATE notificaciones SET leida = 1, fecha_leida = NOW() 
                WHERE usuario_id = ? AND leida = 0";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

$pagina_titulo = "Mis Notificaciones";

// Obtener contador de notificaciones no leídas
$contador_notificaciones = obtenerContadorNotificaciones($usuario_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pagina_titulo; ?> - PepsiCo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include 'general/head.php'; ?>
    <style>
    .notifications-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 0 15px;
    }
    
    .notifications-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px 15px 0 0;
        margin-bottom: 0;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .notifications-header h4 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .notifications-stats {
        display: flex;
        gap: 20px;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.2);
        padding: 8px 15px;
        border-radius: 20px;
        backdrop-filter: blur(10px);
    }
    
    .notifications-card {
        background: #fff;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    
    .notifications-actions {
        padding: 1.5rem;
        border-bottom: 1px solid #e9ecef;
        background: #f8f9fa;
    }
    
    .filter-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }
    
    .filter-btn {
        padding: 8px 20px;
        border: 2px solid #667eea;
        background: white;
        color: #667eea;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .filter-btn:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
    }
    
    .filter-btn.active {
        background: #667eea;
        color: white;
    }
    
    .notification-item {
        padding: 1.5rem;
        border-bottom: 1px solid #e9ecef;
        transition: all 0.3s ease;
        position: relative;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .notification-item:last-child {
        border-bottom: none;
    }
    
    .notification-item:hover {
        background: #f8f9fa;
    }
    
    .notification-item.unread {
        background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, rgba(255, 255, 255, 1) 5%);
        border-left: 4px solid #667eea;
        padding-left: calc(1.5rem - 4px);
    }
    
    .notification-item.read {
        opacity: 0.85;
    }
    
    .notification-item.unread::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        background: #667eea;
        border-radius: 50%;
    }
    
    .notification-content {
        display: flex;
        gap: 15px;
        align-items: flex-start;
    }
    
    .notification-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    
    .notification-item.unread .notification-icon {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
    }
    
    .notification-item.read .notification-icon {
        background: #e9ecef;
        color: #6c757d;
    }
    
    .notification-body {
        flex: 1;
        min-width: 0;
    }
    
    .notification-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 8px;
        color: #2c3e50;
    }
    
    .notification-item.unread .notification-title {
        color: #667eea;
    }
    
    .notification-message {
        color: #6c757d;
        margin-bottom: 10px;
        line-height: 1.6;
    }
    
    .notification-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        font-size: 0.875rem;
        color: #868e96;
    }
    
    .notification-meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .notification-actions-item {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }
    
    .btn-notification {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-notification:hover {
        transform: scale(1.1);
    }
    
    .btn-mark-read {
        background: #28a745;
        color: white;
    }
    
    .btn-mark-read:hover {
        background: #218838;
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
    }
    
    .btn-link-notification {
        background: #007bff;
        color: white;
    }
    
    .btn-link-notification:hover {
        background: #0056b3;
        box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }
    
    .empty-state i {
        font-size: 5rem;
        color: #dee2e6;
        margin-bottom: 1.5rem;
    }
    
    .empty-state h5 {
        color: #6c757d;
        margin-bottom: 0.5rem;
    }
    
    .empty-state p {
        color: #adb5bd;
    }
    
    .badge-module {
        background: #e9ecef;
        color: #495057;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    @media (max-width: 768px) {
        .notifications-header {
            padding: 1.5rem;
        }
        
        .notification-content {
            flex-direction: column;
        }
        
        .notification-actions-item {
            width: 100%;
            justify-content: flex-end;
        }
        
        .filter-buttons {
            flex-direction: column;
        }
        
        .filter-btn {
            width: 100%;
        }
    }
    </style>
</head>
<body data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" 
      data-pc-theme_contrast="" data-pc-theme="light">
    <?php include 'general/sidebar.php'; ?>
    <?php include 'general/header.php'; ?>

    <div class="pc-container">
        <div class="custom-page-header" style="top: 75px;">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <h5 class="mb-1">Mis Notificaciones</h5>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Notificaciones</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="pc-content">
            <div class="notifications-container">
                <div class="notifications-header">
                    <h4>
                        <i class="fas fa-bell"></i>
                        Mis Notificaciones
                    </h4>
                    <div class="notifications-stats">
                        <div class="stat-item">
                            <i class="fas fa-envelope-open-text"></i>
                            <span><?php echo $contador_notificaciones; ?> sin leer</span>
                        </div>
                    </div>
                </div>
                
                <div class="notifications-card">
                    <div class="notifications-actions">
                        <?php if ($contador_notificaciones > 0): ?>
                            <form method="POST" class="d-inline">
                                <button type="submit" name="marcar_todas_leidas" class="btn btn-success">
                                    <i class="fas fa-check-double"></i> Marcar todas como leídas
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="filter-buttons mt-3">
                            <button class="filter-btn active" data-filter="all" onclick="filtrarNotificaciones('all')">
                                <i class="fas fa-list"></i> Todas
                            </button>
                            <button class="filter-btn" data-filter="unread" onclick="filtrarNotificaciones('unread')">
                                <i class="fas fa-envelope"></i> No leídas
                            </button>
                            <button class="filter-btn" data-filter="read" onclick="filtrarNotificaciones('read')">
                                <i class="fas fa-envelope-open"></i> Leídas
                            </button>
                        </div>
                    </div>

                    <div id="notifications-list">
                        <?php
                        // Obtener todas las notificaciones
                        $todas_notificaciones = obtenerTodasNotificacionesUsuario($usuario_id, 50);
                        
                        if (empty($todas_notificaciones)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bell-slash"></i>
                                <h5>No tienes notificaciones</h5>
                                <p>Cuando recibas notificaciones, aparecerán aquí</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($todas_notificaciones as $notificacion): 
                                $is_unread = !$notificacion['leida'];
                                $icono = 'fa-bell';
                                
                                // Determinar icono según el módulo
                                $modulo_lower = strtolower($notificacion['modulo'] ?? '');
                                if (strpos($modulo_lower, 'agendamiento') !== false || strpos($modulo_lower, 'solicitud') !== false) {
                                    $icono = 'fa-calendar-check';
                                } elseif (strpos($modulo_lower, 'vehiculo') !== false || strpos($modulo_lower, 'ingreso') !== false) {
                                    $icono = 'fa-car';
                                } elseif (strpos($modulo_lower, 'tarea') !== false || strpos($modulo_lower, 'asignacion') !== false) {
                                    $icono = 'fa-tasks';
                                } elseif (strpos($modulo_lower, 'consulta') !== false) {
                                    $icono = 'fa-search';
                                }
                                
                                // Formatear fecha
                                $fecha = new DateTime($notificacion['fecha_creacion']);
                                $ahora = new DateTime();
                                $diferencia = $ahora->diff($fecha);
                                
                                $tiempo_relativo = '';
                                if ($diferencia->days > 7) {
                                    $tiempo_relativo = $fecha->format('d/m/Y H:i');
                                } elseif ($diferencia->days > 0) {
                                    $tiempo_relativo = 'Hace ' . $diferencia->days . ' día' . ($diferencia->days > 1 ? 's' : '');
                                } elseif ($diferencia->h > 0) {
                                    $tiempo_relativo = 'Hace ' . $diferencia->h . ' hora' . ($diferencia->h > 1 ? 's' : '');
                                } elseif ($diferencia->i > 0) {
                                    $tiempo_relativo = 'Hace ' . $diferencia->i . ' minuto' . ($diferencia->i > 1 ? 's' : '');
                                } else {
                                    $tiempo_relativo = 'Hace unos momentos';
                                }
                            ?>
                                <div class="notification-item <?php echo $is_unread ? 'unread' : 'read'; ?>" 
                                     data-status="<?php echo $is_unread ? 'unread' : 'read'; ?>">
                                    <div class="notification-content">
                                        <div class="notification-icon">
                                            <i class="fas <?php echo $icono; ?>"></i>
                                        </div>
                                        <div class="notification-body">
                                            <div class="notification-title">
                                                <?php echo htmlspecialchars($notificacion['titulo']); ?>
                                            </div>
                                            <div class="notification-message">
                                                <?php echo htmlspecialchars($notificacion['mensaje']); ?>
                                            </div>
                                            <div class="notification-meta">
                                                <div class="notification-meta-item">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?php echo $tiempo_relativo; ?></span>
                                                </div>
                                                <?php if (!empty($notificacion['modulo'])): ?>
                                                    <div class="notification-meta-item">
                                                        <span class="badge-module">
                                                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($notificacion['modulo']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="notification-actions-item">
                                            <?php if ($is_unread): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Marcar esta notificación como leída?');">
                                                    <input type="hidden" name="notificacion_id" value="<?php echo $notificacion['id']; ?>">
                                                    <button type="submit" name="marcar_leida" class="btn-notification btn-mark-read" title="Marcar como leída">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (!empty($notificacion['enlace'])): ?>
                                                <a href="<?php echo htmlspecialchars($notificacion['enlace']); ?>" 
                                                   class="btn-notification btn-link-notification" 
                                                   title="Ver detalles">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="pc-footer-fix" style="height: 100px;"></div>
        </div>
    </div>

    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>
    
    <script>
    function filtrarNotificaciones(filtro) {
        // Actualizar botones activos
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.filter === filtro) {
                btn.classList.add('active');
            }
        });
        
        // Filtrar notificaciones
        const notificaciones = document.querySelectorAll('.notification-item');
        notificaciones.forEach(notif => {
            if (filtro === 'all') {
                notif.style.display = '';
            } else {
                const status = notif.dataset.status;
                notif.style.display = (status === filtro) ? '' : 'none';
            }
        });
        
        // Verificar si hay resultados
        const visibles = Array.from(notificaciones).filter(n => n.style.display !== 'none');
        const lista = document.getElementById('notifications-list');
        
        // Remover mensaje de "sin resultados" si existe
        const sinResultados = lista.querySelector('.no-results');
        if (sinResultados) {
            sinResultados.remove();
        }
        
        if (visibles.length === 0 && notificaciones.length > 0) {
            const mensaje = document.createElement('div');
            mensaje.className = 'empty-state no-results';
            mensaje.innerHTML = `
                <i class="fas fa-filter"></i>
                <h5>No hay notificaciones ${filtro === 'unread' ? 'no leídas' : 'leídas'}</h5>
                <p>Intenta con otro filtro</p>
            `;
            lista.appendChild(mensaje);
        }
    }
    </script>
</body>
</html>
