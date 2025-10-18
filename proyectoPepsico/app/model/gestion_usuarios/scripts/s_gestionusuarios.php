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
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
}
?>