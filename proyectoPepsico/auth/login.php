<?php
include '../app/config/conexion.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$mensaje = "";

// Si ya está logueado, redirigir a su página principal ANTES de procesar el POST
if (isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id'])) {
    $pagina_principal = obtener_pagina_principal_directa($_SESSION['usuario']['rol']);
    header('Location: ' . $pagina_principal);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../app/model/login/functions/f_login.php';
    
    // Sanitizar entradas
    $usuario  = isset($_POST['usuario'])  ? trim($_POST['usuario'])  : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Verificar usuario
    $user = verificarUsuario($usuario, $password);

    if ($user) {
        // Crear sesión
        $_SESSION['usuario'] = [
            'id' => $user['UsuarioID'],
            'nombre' => $user['NombreUsuario'],
            'rol' => $user['Rol']
        ];

        // Actualizar último acceso
        $conn = conectar_Pepsico();
        $stmt = $conn->prepare("UPDATE USUARIOS SET UltimoAcceso = NOW() WHERE UsuarioID = ?");
        $stmt->bind_param("i", $user['UsuarioID']);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        // Redirigir a la página principal correspondiente
        $pagina_principal = obtener_pagina_principal_directa($user['Rol']);
        header('Location: ' . $pagina_principal);
        exit();
    } else {
        $mensaje = 'Usuario o contraseña incorrecto.';
    }
}

/**
 * Función directa para obtener página principal sin incluir middle.php
 * AHORA RETORNA LA RUTA COMPLETA
 */
function obtener_pagina_principal_directa($rol) {
    $paginas_principales = [
        'Administrador' => '../pages/gestion_usuarios.php',
        'Jefe de Taller' => '../pages/consulta.php',
        'Mecánico' => '../pages/consulta.php',
        'Recepcionista' => '../pages/ingreso_vehiculos.php',
        'Guardia' => '../pages/control_ingreso.php',
        'Supervisor' => '../pages/reportes.php',
        'Chofer' => '../pages/ingreso_vehiculos.php'
    ];
    
    return $paginas_principales[$rol] ?? '../pages/ingreso_vehiculos.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inicio de Sesión | Portal Pepsico</title>
    <link rel="icon" href="../assets/images/pepsicoLogo.png" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" crossorigin="anonymous" />
</head>
<body class="h-screen w-screen flex font-sans bg-gray-100">
    <div class="hidden md:flex md:w-1/2 relative bg-cover bg-center"
        style="background-image: url('../assets/images/PepsicoBackground.png');">
        <div class="absolute inset-0 bg-gradient-to-b from-blue-600/70 to-gray-900/80"></div>
        <div class="relative z-10 flex flex-col justify-center px-12 text-white">
            <h1 class="text-4xl font-bold leading-tight">
                Bienvenido al Gestor de <br />
                <span class="text-orange-300">Pepsico</span>
            </h1>
            <p class="mt-4 text-lg text-gray-200">
                Controla, gestiona y visualiza el ingreso de Vehículos.
            </p>
        </div>
    </div>

    <div class="flex w-full md:w-1/2 items-center justify-center px-4 py-8">
        <div class="bg-white shadow-2xl rounded-2xl w-full max-w-md p-6 md:p-10 mx-auto animate-fadeIn">
            <div class="flex items-center space-x-3 mb-8">
                <img src="../assets/images/pepsicoLogo.png" alt="pepsico" class="w-12 h-12" />
                <h3 class="text-2xl font-semibold text-gray-800">Ingreso al Portal</h3>
            </div>

            <form id="loginForm" method="post" class="space-y-6">
                <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2 focus-within:ring-2 focus-within:ring-blue-400">
                    <i class="fas fa-user text-blue-500 text-lg mr-3"></i>
                    <input type="text" name="usuario" id="usuario" placeholder="Usuario" required
                        class="w-full bg-transparent outline-none text-gray-700 placeholder-gray-400" />
                </div>

                <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2 focus-within:ring-2 focus-within:ring-blue-400">
                    <i class="fas fa-lock text-blue-500 text-lg mr-3"></i>
                    <input type="password" name="password" id="password" placeholder="Contraseña" required
                        class="w-full bg-transparent outline-none text-gray-700 placeholder-gray-400" />
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white py-3 rounded-lg shadow-lg transition transform hover:scale-[1.02] font-medium">
                    Iniciar sesión
                </button>

                <?php if (!empty($mensaje)): ?>
                <p class="text-red-600 text-center mt-3 text-sm">
                    <?= htmlspecialchars($mensaje) ?>
                </p>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn { animation: fadeIn 0.6s ease-out; }
    
    /* Responsive para login */
    @media (max-width: 768px) {
        body {
            flex-direction: column;
        }
        
        .hidden.md\:flex {
            display: none !important;
        }
        
        .flex.w-full.md\:w-1\/2 {
            width: 100% !important;
            min-height: 100vh;
            padding: 1rem;
        }
        
        .bg-white.shadow-2xl {
            padding: 1.5rem !important;
            margin: 0 !important;
        }
        
        h3 {
            font-size: 1.25rem;
        }
        
        input[type="text"],
        input[type="password"] {
            font-size: 16px; /* Evitar zoom en iOS */
        }
    }
    
    @media (max-width: 480px) {
        .bg-white.shadow-2xl {
            padding: 1rem !important;
            border-radius: 1rem;
        }
        
        .flex.items-center.space-x-3 {
            flex-direction: column;
            text-align: center;
        }
        
        .flex.items-center.space-x-3 img {
            margin-bottom: 0.5rem;
        }
        
        h3 {
            font-size: 1.1rem;
        }
    }
    </style>
</body>
</html>