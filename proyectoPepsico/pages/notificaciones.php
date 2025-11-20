<?php
include 'general/middle.php';
include 'general/funciones_notificaciones.php';

$pagina_titulo = "Mis Notificaciones";
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
    .notification-page {
        max-width: 800px;
        margin: 0 auto;
    }
    .notification-card {
        border-left: 4px solid #007bff;
        margin-bottom: 15px;
    }
    .notification-card.unread {
        background-color: #f8f9fa;
        border-left-color: #28a745;
    }
    .notification-card.read {
        opacity: 0.8;
    }
    .notification-actions {
        display: flex;
        gap: 10px;
    }
    .mark-all-btn {
        margin-bottom: 20px;
    }
    </style>
</head>
<body>
    <?php include 'general/sidebar.php'; ?>
    <?php include 'general/header.php'; ?>

    <div class="pc-container">
        <div class="pc-content">
            <div class="notification-page">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Mis Notificaciones</h5>
                        <div>
                            <span class="badge bg-primary"><?php echo $contador_notificaciones; ?> sin leer</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($contador_notificaciones > 0): ?>
                            <form method="POST" class="mark-all-btn">
                                <button type="submit" name="marcar_todas_leidas" class="btn btn-success btn-sm">
                                    <i class="fas fa-check-double"></i> Marcar todas como leídas
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php
                        // Obtener todas las notificaciones
                        $todas_notificaciones = obtenerNotificacionesUsuario($usuario_id, 50);
                        
                        if (empty($todas_notificaciones)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No tienes notificaciones</h5>
                            </div>
                        <?php else: ?>
                            <div class="notifications-list">
                                <?php foreach ($todas_notificaciones as $notificacion): ?>
                                    <div class="card notification-card <?php echo $notificacion['leida'] ? 'read' : 'unread'; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($notificacion['titulo']); ?></h6>
                                                    <p class="card-text"><?php echo htmlspecialchars($notificacion['mensaje']); ?></p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i>
                                                        <?php 
                                                        $fecha = new DateTime($notificacion['fecha_creacion']);
                                                        echo $fecha->format('d/m/Y H:i');
                                                        ?>
                                                        • Módulo: <?php echo htmlspecialchars($notificacion['modulo']); ?>
                                                    </small>
                                                </div>
                                                <div class="notification-actions">
                                                    <?php if (!$notificacion['leida']): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="notificacion_id" value="<?php echo $notificacion['id']; ?>">
                                                            <button type="submit" name="marcar_leida" class="btn btn-sm btn-outline-success" title="Marcar como leída">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if (!empty($notificacion['enlace'])): ?>
                                                        <a href="<?php echo htmlspecialchars($notificacion['enlace']); ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'general/footer.php'; ?>
    <?php include 'general/script.php'; ?>
</body>
</html>

<?php
// Procesar marcar todas como leídas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_todas_leidas'])) {
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
    echo "<script>window.location.reload();</script>";
    exit;
}
?>