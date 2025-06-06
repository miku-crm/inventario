# Sistema de Gestión de Inventario y Préstamos

## 1. Información General del Proyecto

### 1.1 Descripción General
Sistema web para la gestión de inventario y préstamos de productos, diseñado para administrar eficientemente el stock, usuarios, clientes y transacciones.

### 1.2 Objetivos del Sistema
- Gestionar el inventario de productos
- Administrar préstamos y devoluciones
- Control de usuarios y clientes
- Seguimiento de proveedores
- Generación de reportes

### 1.3 Alcance del Sistema
El sistema abarca:
- Gestión de productos y stock
- Control de préstamos
- Administración de usuarios
- Gestión de clientes
- Control de proveedores
- Reportes y estadísticas

## 2. Arquitectura del Sistema

### 2.1 Tecnologías Utilizadas
- Frontend: HTML5, CSS3, JavaScript, Bootstrap 5
- Backend: PHP
- Base de Datos: MySQL
- Librerías: jQuery, DataTables, SweetAlert2
- Iconos: Font Awesome

### 2.2 Estructura de Directorios
```
inventario/
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── auth_check.php
├── config/
│   └── database.php
├── includes/
│   ├── components/
│   ├── css/
│   ├── js/
│   ├── modals/
│   ├── functions.php
│   └── header.php
├── sql/
│   ├── auth_tables.sql
│   └── create_users_table.sql
└── [archivos principales .php]
```

## 3. Módulos del Sistema

### 3.1 Módulo de Autenticación
#### Características
- Login seguro
- Control de sesiones
- Gestión de roles (admin, operador, consulta)
- Protección de rutas

#### Funcionalidades
- Inicio de sesión
- Cierre de sesión
- Verificación de permisos
- Control de acceso basado en roles

### 3.2 Módulo de Inventario
#### Características
- Gestión de productos
- Control de stock
- Registro de entradas
- Seguimiento de movimientos

#### Funcionalidades
- Alta, baja y modificación de productos
- Control de stock mínimo
- Registro de entradas de mercadería
- Historial de movimientos

### 3.3 Módulo de Préstamos
#### Características
- Gestión de préstamos
- Control de devoluciones
- Seguimiento de estado
- Historial de transacciones

#### Funcionalidades
- Registro de préstamos
- Control de devoluciones
- Renovaciones
- Historial completo

### 3.4 Módulo de Usuarios y Clientes
#### Características
- Gestión de usuarios del sistema
- Administración de clientes
- Control de accesos
- Perfiles de usuario

#### Funcionalidades
- CRUD de usuarios
- Gestión de clientes
- Asignación de roles
- Historial de actividades

### 3.5 Módulo de Proveedores
#### Características
- Gestión de proveedores
- Registro de compras
- Historial de transacciones

#### Funcionalidades
- CRUD de proveedores
- Registro de compras
- Historial de entregas

### 3. Actores y Casos de Uso

#### Administrador
- **Descripción**: Usuario con acceso total al sistema
- **Responsabilidades**:
  * Gestión completa de usuarios del sistema
  * Configuración general del sistema
  * Acceso a todos los módulos
  * Gestión de roles y permisos
  * Visualización de logs y reportes completos

#### Operador
- **Descripción**: Usuario con acceso a operaciones diarias
- **Responsabilidades**:
  * Gestión de préstamos y devoluciones
  * Gestión de clientes
  * Gestión de productos
  * Acceso a reportes básicos
  * Registro de entradas de inventario

#### Usuario de Consulta
- **Descripción**: Usuario con acceso solo de lectura
- **Responsabilidades**:
  * Visualización de préstamos
  * Consulta de inventario
  * Visualización de clientes
  * Acceso a reportes básicos

#### Cliente
- **Descripción**: Usuario externo que recibe préstamos
- **Características**:
  * No tiene acceso al sistema
  * Recibe productos en préstamo
  * Puede tener múltiples préstamos activos

#### Proveedor
- **Descripción**: Entidad que suministra productos
- **Características**:
  * No tiene acceso al sistema
  * Proporciona productos al inventario
  * Mantiene historial de entregas

### 3.2 Casos de Uso Principales

#### Gestión de Préstamos
1. **Crear Nuevo Préstamo**
   - Actor: Operador
   - Flujo Principal:
     * Seleccionar cliente
     * Verificar disponibilidad de productos
     * Registrar préstamo
     * Generar comprobante
   - Flujos Alternativos:
     * Cliente no registrado → Registrar nuevo cliente
     * Producto no disponible → Notificar indisponibilidad

2. **Procesar Devolución**
   - Actor: Operador
   - Flujo Principal:
     * Buscar préstamo activo
     * Verificar estado del producto
     * Registrar devolución
     * Actualizar inventario
   - Flujos Alternativos:
     * Producto dañado → Registrar incidencia
     * Devolución tardía → Calcular penalización

#### Gestión de Inventario
1. **Registrar Entrada de Productos**
   - Actor: Operador
   - Flujo Principal:
     * Seleccionar proveedor
     * Registrar productos recibidos
     * Actualizar stock
     * Generar comprobante de recepción
   - Flujos Alternativos:
     * Productos defectuosos → Registrar devolución a proveedor
     * Cantidad incorrecta → Ajustar pedido

2. **Control de Stock**
   - Actor: Operador/Administrador
   - Flujo Principal:
     * Verificar niveles de stock
     * Identificar productos bajo mínimo
     * Generar alertas
     * Planificar reposición
   - Flujos Alternativos:
     * Stock crítico → Notificar administración
     * Discrepancias → Realizar inventario físico

#### Gestión de Usuarios
1. **Administración de Usuarios del Sistema**
   - Actor: Administrador
   - Flujo Principal:
     * Crear nuevo usuario
     * Asignar rol y permisos
     * Configurar accesos
     * Activar cuenta
   - Flujos Alternativos:
     * Usuario existente → Notificar duplicado
     * Datos incompletos → Solicitar información faltante

2. **Gestión de Clientes**
   - Actor: Operador
   - Flujo Principal:
     * Registrar nuevo cliente
     * Verificar datos
     * Asignar categoría
     * Activar cuenta
   - Flujos Alternativos:
     * Cliente existente → Actualizar datos
     * Documentación incompleta → Solicitar documentos

### 3.3 Diagramas de Flujo de Trabajo

#### Proceso de Préstamo
```
[Cliente solicita préstamo] → [Verificar cliente en sistema] → [Verificar disponibilidad] → [Registrar préstamo] → [Generar comprobante]
                                     ↓                               ↓
                            [Registrar nuevo cliente]    [Notificar no disponibilidad]
```

#### Proceso de Devolución
```
[Cliente devuelve producto] → [Verificar préstamo] → [Inspeccionar producto] → [Registrar devolución] → [Actualizar inventario]
                                                            ↓
                                                  [Registrar incidencia]
```

#### Proceso de Entrada de Inventario
```
[Recepción de productos] → [Verificar pedido] → [Registrar entrada] → [Actualizar stock] → [Generar comprobante]
                                  ↓
                        [Registrar incidencia]
```

## 4. Base de Datos

### 4.1 Estructura de Tablas
#### usuarios_sistema
- id (INT, PK)
- username (VARCHAR)
- password (VARCHAR)
- nombre_completo (VARCHAR)
- email (VARCHAR)
- rol (ENUM)
- estado (ENUM)
- ultimo_acceso (DATETIME)
- fecha_creacion (DATETIME)
- fecha_modificacion (DATETIME)

[Otras tablas principales...]

### 4.2 Relaciones
[Diagrama de relaciones entre tablas]

## 5. Seguridad

### 5.1 Medidas Implementadas
- Autenticación segura
- Passwords hasheados
- Protección contra SQL Injection
- Sanitización de inputs
- Control de sesiones
- Protección XSS

### 5.2 Futuras Mejoras
- Tokens CSRF
- Rate limiting
- Headers de seguridad
- Sistema de recuperación de contraseñas
- Autenticación de dos factores
- Registro de actividad mejorado

## 6. Interfaces de Usuario

### 6.1 Pantallas Principales
- Dashboard
- Gestión de Préstamos
- Inventario
- Usuarios
- Clientes
- Proveedores
- Reportes

### 6.2 Flujos de Usuario
[Reemplazar esta sección con los diagramas detallados arriba]

## 7. Mantenimiento y Soporte

### 7.1 Respaldos
- Política de respaldos
- Procedimientos de recuperación
- Mantenimiento de datos

### 7.2 Monitoreo
- Logs del sistema
- Alertas
- Métricas de rendimiento

## 8. Guía de Instalación

### 8.1 Requisitos del Sistema
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web Apache/Nginx
- Extensiones PHP requeridas

### 8.2 Proceso de Instalación
1. Clonar repositorio
2. Configurar base de datos
3. Configurar variables de entorno
4. Ejecutar migraciones
5. Configurar servidor web

### 8.3 Configuración Inicial
- Crear usuario administrador
- Configurar parámetros del sistema
- Verificar permisos de archivos

## 9. Guía de Usuario

### 9.1 Acceso al Sistema
- Proceso de login
- Recuperación de contraseña
- Cierre de sesión

### 9.2 Operaciones Básicas
- Gestión de préstamos
- Control de inventario
- Administración de usuarios
- Generación de reportes

## 10. Anexos

### 10.1 Glosario de Términos
[Términos técnicos y definiciones]

### 10.2 Referencias
[Enlaces y documentación relacionada]

### 10.3 Historial de Versiones
- Versión 1.0: Implementación inicial
- [Futuras versiones y mejoras] 