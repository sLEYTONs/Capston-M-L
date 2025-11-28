<?php
require_once '../functions/f_gestionusuarios.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'listar_usuarios':
            $usuarios = obtenerTodosUsuarios();
            echo json_encode(['data' => $usuarios]);
            break;

        case 'crear_usuario':
            $response = ['success' => false, 'message' => ''];

            // Validaciones
            if (empty($_POST['nombre_usuario']) || empty($_POST['correo']) || empty($_POST['clave'])) {
                $response['message'] = 'Todos los campos son obligatorios';
                echo json_encode($response);
                exit;
            }

            if (existeNombreUsuario($_POST['nombre_usuario'])) {
                $response['message'] = 'El nombre de usuario ya existe';
                echo json_encode($response);
                exit;
            }

            if (existeCorreo($_POST['correo'])) {
                $response['message'] = 'El correo electrónico ya está registrado';
                echo json_encode($response);
                exit;
            }

            $datos = [
                'nombre_usuario' => trim($_POST['nombre_usuario']),
                'correo' => trim($_POST['correo']),
                'clave' => $_POST['clave'],
                'rol' => $_POST['rol'],
                'estado' => intval($_POST['estado'])
            ];

            $resultado = crearNuevoUsuario($datos);

            if ($resultado) {
                $response['success'] = true;
                $response['message'] = 'Usuario creado exitosamente';
            } else {
                $response['message'] = 'Error al crear el usuario';
            }

            echo json_encode($response);
            break;

        case 'obtener_usuario':
            $usuario_id = intval($_POST['usuario_id']);
            $usuario = obtenerUsuarioPorId($usuario_id);
            echo json_encode($usuario);
            break;

        case 'editar_usuario':
            $response = ['success' => false, 'message' => ''];

            if (empty($_POST['nombre_usuario']) || empty($_POST['correo'])) {
                $response['message'] = 'Nombre de usuario y correo son obligatorios';
                echo json_encode($response);
                exit;
            }

            $usuario_id = intval($_POST['usuario_id']);

            if (existeNombreUsuario($_POST['nombre_usuario'], $usuario_id)) {
                $response['message'] = 'El nombre de usuario ya existe';
                echo json_encode($response);
                exit;
            }

            if (existeCorreo($_POST['correo'], $usuario_id)) {
                $response['message'] = 'El correo electrónico ya está registrado';
                echo json_encode($response);
                exit;
            }

            $datos = [
                'usuario_id' => $usuario_id,
                'nombre_usuario' => trim($_POST['nombre_usuario']),
                'correo' => trim($_POST['correo']),
                'rol' => $_POST['rol'],
                'estado' => intval($_POST['estado'])
            ];

            // Solo incluir clave si se proporcionó una nueva
            if (!empty($_POST['clave'])) {
                $datos['clave'] = $_POST['clave'];
            }

            $resultado = actualizarUsuario($datos);

            if ($resultado) {
                $response['success'] = true;
                $response['message'] = 'Usuario actualizado exitosamente';
            } else {
                $response['message'] = 'Error al actualizar el usuario';
            }

            echo json_encode($response);
            break;

        case 'generar_choferes':
            $nombres = ['Juan', 'Carlos', 'Pedro', 'Luis', 'Miguel', 'Jose', 'Antonio', 'Manuel', 'Francisco', 'David'];
            $apellidos = ['Garcia', 'Rodriguez', 'Gonzalez', 'Fernandez', 'Lopez', 'Martinez', 'Sanchez', 'Perez', 'Gomez', 'Martin'];

            $count = 0;
            $errores = 0;

            for ($i = 0; $i < 5; $i++) {
                $nombre = $nombres[array_rand($nombres)];
                $apellido = $apellidos[array_rand($apellidos)];
                $nombreUsuario = strtolower(substr($nombre, 0, 1) . $apellido . rand(100, 999));

                // Asegurar nombre de usuario único
                while (existeNombreUsuario($nombreUsuario)) {
                    $nombreUsuario = strtolower(substr($nombre, 0, 1) . $apellido . rand(100, 999));
                }

                $correo = $nombreUsuario . '@pepsico.com';

                // Asegurar correo único
                while (existeCorreo($correo)) {
                    $nombreUsuario = strtolower(substr($nombre, 0, 1) . $apellido . rand(100, 999));
                    $correo = $nombreUsuario . '@pepsico.com';
                }

                $datos = [
                    'nombre_usuario' => $nombreUsuario,
                    'correo' => $correo,
                    'clave' => 'Pepsico123!',
                    'rol' => 'Chofer',
                    'estado' => 1
                ];

                if (crearNuevoUsuario($datos)) {
                    $count++;
                } else {
                    $errores++;
                }
            }

            if ($count > 0) {
                echo json_encode(['success' => true, 'message' => "Se generaron $count choferes exitosamente."]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudieron generar los choferes.']);
            }
            break;

        case 'obtener_choferes_disponibles':
            // Get choferes who don't have vehicles assigned
            $conn = conectar_Pepsico();

            $sql = "SELECT u.UsuarioID, u.NombreUsuario, u.Correo 
                    FROM USUARIOS u 
                    WHERE u.Rol = 'Chofer' 
                    AND u.Estado = 1
                    AND NOT EXISTS (
                        SELECT 1 FROM VEHICULOS v 
                        WHERE v.ConductorNombre = u.NombreUsuario
                    )
                    ORDER BY u.NombreUsuario";

            $result = $conn->query($sql);
            $choferes = [];

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $choferes[] = $row;
                }
            }

            $conn->close();

            echo json_encode([
                'success' => true,
                'choferes' => $choferes,
                'count' => count($choferes)
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
}
?>