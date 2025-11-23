-- Script para crear usuarios de prueba de los nuevos roles
-- Utiliza la misma contraseña de la plantilla: password

-- 1. Coordinador de Zona
INSERT INTO usuarios (NombreUsuario, Correo, ClaveHash, Rol, Estado, FechaCreacion)
VALUES ('Coordinador Zona', 'coordinador.zona@pepsico.cl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Coordinador de Zona', 1, NOW());

-- 2. Ejecutivo/a de Ventas / Personal de Ventas Terreno
INSERT INTO usuarios (NombreUsuario, Correo, ClaveHash, Rol, Estado, FechaCreacion)
VALUES ('Ejecutivo Ventas', 'ejecutivo.ventas@pepsico.cl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ejecutivo/a de Ventas', 1, NOW());

-- 3. Supervisor de Flotas / Zonal
INSERT INTO usuarios (NombreUsuario, Correo, ClaveHash, Rol, Estado, FechaCreacion)
VALUES ('Supervisor Flotas', 'supervisor.flotas@pepsico.cl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supervisor de Flotas', 1, NOW());

-- 4. Encargado de Llaves y Control Documental
INSERT INTO usuarios (NombreUsuario, Correo, ClaveHash, Rol, Estado, FechaCreacion)
VALUES ('Encargado Llaves', 'encargado.llaves@pepsico.cl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Encargado de Llaves', 1, NOW());

