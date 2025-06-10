-- Modificar la columna estado para incluir el valor 'EXPIRADO'
ALTER TABLE usuarios_productos
MODIFY COLUMN estado ENUM('LIBRE', 'OCUPADO', 'VENCIDO', 'EXPIRADO') NOT NULL DEFAULT 'LIBRE';

-- Actualizar usuarios expirados
UPDATE usuarios_productos
SET estado = 'EXPIRADO'
WHERE fecha_expiracion < CURRENT_DATE; 