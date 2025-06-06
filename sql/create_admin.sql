-- Insertar usuario administrador por defecto
-- Usuario: admin
-- Contraseña: admin123
INSERT INTO usuarios_sistema (
    username, 
    password, 
    nombre_completo, 
    email, 
    rol, 
    estado
) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Administrador del Sistema',
    'admin@sistema.local',
    'admin',
    'activo'
); 