-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS inventario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventario;

-- Tabla de productos
CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    precio_compra DECIMAL(10,2) NOT NULL COMMENT 'Precio base para crear usuarios',
    precio_venta DECIMAL(10,2) NOT NULL COMMENT 'Precio base para prestar usuarios',
    stock DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Cuántos usuarios podemos crear',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de factores de precio
CREATE TABLE factores_precio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    porcentaje DECIMAL(5,2) NOT NULL,
    tipo ENUM('venta', 'compra') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de usuarios de productos
CREATE TABLE usuarios_productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    cantidad_inicial DECIMAL(10,2) NOT NULL DEFAULT 1,
    costo_creacion DECIMAL(10,2) NOT NULL COMMENT 'Costo que se aplicó al crear el usuario',
    max_prestamos INT NOT NULL DEFAULT 1,
    prestamos_activos INT NOT NULL DEFAULT 0,
    fecha_expiracion DATE NOT NULL,
    estado ENUM('LIBRE', 'OCUPADO', 'VENCIDO') NOT NULL DEFAULT 'LIBRE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Tabla de clientes
CREATE TABLE clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(255),
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de préstamos
CREATE TABLE prestamos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_producto_id INT NOT NULL,
    cliente_id INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL COMMENT 'Precio base del préstamo',
    factor_precio_id INT,
    precio_final DECIMAL(10,2) NOT NULL COMMENT 'Precio después de aplicar factor',
    cantidad_dispositivos INT NOT NULL DEFAULT 1 COMMENT 'Cantidad de dispositivos permitidos',
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('ACTIVO', 'VENCIDO', 'CANCELADO', 'LIBERADO') NOT NULL DEFAULT 'ACTIVO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_producto_id) REFERENCES usuarios_productos(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (factor_precio_id) REFERENCES factores_precio(id)
);

-- Tabla de renovaciones de préstamos
CREATE TABLE renovaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prestamo_id INT NOT NULL,
    fecha_renovacion DATE NOT NULL,
    fecha_anterior DATE NOT NULL,
    fecha_nueva DATE NOT NULL,
    precio_anterior DECIMAL(10,2) NOT NULL,
    precio_nuevo DECIMAL(10,2) NOT NULL,
    factor_precio_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id),
    FOREIGN KEY (factor_precio_id) REFERENCES factores_precio(id)
);

-- Tabla de renovaciones de usuarios_productos
CREATE TABLE renovaciones_usuarios_productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_producto_id INT NOT NULL,
    fecha_renovacion DATE NOT NULL,
    fecha_anterior_expiracion DATE NOT NULL,
    nueva_fecha_expiracion DATE NOT NULL,
    cantidad_anterior DECIMAL(10,2) NOT NULL,
    cantidad_nueva DECIMAL(10,2) NOT NULL,
    precio_anterior DECIMAL(10,2) NOT NULL,
    precio_nuevo DECIMAL(10,2) NOT NULL,
    costo_momento_renovacion DECIMAL(10,2) NOT NULL,
    ganancia_bruta DECIMAL(10,2) GENERATED ALWAYS AS (precio_nuevo - costo_momento_renovacion) STORED,
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_producto_id) REFERENCES usuarios_productos(id)
);

-- Tabla de proveedores
CREATE TABLE proveedores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(255),
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de entradas (compras de stock)
CREATE TABLE entradas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT NOT NULL,
    proveedor_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    factor_precio_id INT,
    precio_final DECIMAL(10,2) NOT NULL,
    fecha DATE NOT NULL,
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
    FOREIGN KEY (factor_precio_id) REFERENCES factores_precio(id)
);

-- Tabla de historial_usuarios
CREATE TABLE IF NOT EXISTS historial_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_producto_id INT NOT NULL,
    estado_anterior VARCHAR(20) NOT NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    motivo TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_producto_id) REFERENCES usuarios_productos(id)
);

-- Modificar columnas para soportar decimales
ALTER TABLE usuarios_productos
MODIFY COLUMN cantidad_inicial DECIMAL(10,2) NOT NULL DEFAULT 1;

ALTER TABLE renovaciones_usuarios_productos
MODIFY COLUMN cantidad_anterior DECIMAL(10,2) NOT NULL,
MODIFY COLUMN cantidad_nueva DECIMAL(10,2) NOT NULL;

ALTER TABLE productos
MODIFY COLUMN stock DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Cuántos usuarios podemos crear'; 