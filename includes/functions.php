<?php
// Ajustar la ruta para que sea relativa a la raíz del proyecto
require_once dirname(__FILE__) . '/../config/database.php';

// Función para sanitizar input
function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($input));
}

// Función para formatear fecha
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Función para formatear moneda
function formatMoney($amount) {
    return number_format($amount, 2, '.', ',');
}

// Función para verificar si un usuario está ocupado
function isUserOccupied($userId) {
    global $conn;
    $query = "SELECT estado FROM usuarios_productos WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['estado'] === 'OCUPADO';
}

// Función para actualizar el estado de un usuario
function updateUserStatus($usuario_producto_id, $nuevo_estado, $razon = '') {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE usuarios_productos SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $nuevo_estado, $usuario_producto_id);
    $stmt->execute();
    
    // Registrar el cambio de estado si se proporciona una razón
    if (!empty($razon)) {
        $stmt = $conn->prepare("INSERT INTO cambios_estado (usuario_producto_id, estado_nuevo, razon) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $usuario_producto_id, $nuevo_estado, $razon);
        $stmt->execute();
    }
}

// Función para verificar préstamos vencidos
function checkExpiredLoans() {
    global $conn;
    
    // Actualizar préstamos vencidos
    $query = "UPDATE prestamos 
             SET estado = 'VENCIDO' 
             WHERE estado = 'ACTIVO' 
             AND fecha_fin < CURRENT_DATE";
    $conn->query($query);
}

// Función para verificar usuarios vencidos
function checkExpiredUsers() {
    global $conn;
    $today = date('Y-m-d');
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Marcar usuarios como expirados si su fecha de expiración ha pasado
        $query = "UPDATE usuarios_productos 
                 SET estado = 'EXPIRADO' 
                 WHERE fecha_expiracion < ? 
                 AND estado != 'EXPIRADO'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $today);
        $stmt->execute();
        
        // Registrar en historial los cambios
        if ($stmt->affected_rows > 0) {
            $query = "INSERT INTO historial_usuarios 
                     (usuario_producto_id, estado_anterior, estado_nuevo, motivo) 
                     SELECT id, estado, 'EXPIRADO', 'Usuario expirado automáticamente' 
                     FROM usuarios_productos 
                     WHERE fecha_expiracion < ? 
                     AND estado != 'EXPIRADO'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $today);
            $stmt->execute();
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Función para verificar si un usuario está disponible para préstamos
function isUserAvailableForLoan($usuario_producto_id) {
    global $conn;
    
    $query = "SELECT up.*, 
             (SELECT COUNT(*) FROM prestamos 
              WHERE usuario_producto_id = up.id 
              AND estado = 'VENCIDO') as prestamos_vencidos
             FROM usuarios_productos up 
             WHERE up.id = ? 
             AND up.estado != 'EXPIRADO'
             AND up.prestamos_activos < up.max_prestamos";
             
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $usuario_producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row && $row['prestamos_vencidos'] == 0;
}

// Función para obtener el stock disponible de un producto
function getAvailableStock($productoId) {
    global $conn;
    $query = "SELECT 
                p.stock - COUNT(up.id) as stock_disponible 
              FROM productos p 
              LEFT JOIN usuarios_productos up ON p.id = up.producto_id 
              WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['stock_disponible'];
}

// Función para obtener usuarios libres de un producto
function getFreeUsers($productoId) {
    global $conn;
    $query = "SELECT id, user_name 
              FROM usuarios_productos 
              WHERE producto_id = ? AND estado = 'LIBRE'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productoId);
    $stmt->execute();
    return $stmt->get_result();
}

// Función para aplicar factor de precio
function applyPriceFactor($precio_base, $factor_id = null) {
    global $conn;
    
    if (!$factor_id) {
        return $precio_base;
    }
    
    $stmt = $conn->prepare("SELECT porcentaje FROM factores_precio WHERE id = ?");
    $stmt->bind_param("i", $factor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $porcentaje = $row['porcentaje'];
        return $precio_base * (1 + ($porcentaje / 100));
    }
    
    return $precio_base;
}

// Función para formatear números de teléfono
function formatPhoneNumber($phone) {
    // Para debugging
    error_log("Número original: " . $phone);
    
    // Eliminar cualquier carácter que no sea número
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Para debugging
    error_log("Número limpio: " . $phone);
    
    // Si está vacío, retornar vacío
    if (empty($phone)) {
        return '';
    }
    
    // Definir códigos de país y sus longitudes
    $countryCodes = [
        '591' => 3,  // Bolivia
        '593' => 3,  // Ecuador
        '595' => 3,  // Paraguay
        '598' => 3,  // Uruguay
        '51' => 2,   // Perú
        '52' => 2,   // México
        '54' => 2,   // Argentina
        '55' => 2,   // Brasil
        '56' => 2,   // Chile
        '57' => 2,   // Colombia
        '58' => 2,   // Venezuela
        '34' => 2,   // España
        '1' => 1     // USA/Canada
    ];
    
    // Detectar código de país
    $countryCode = '';
    foreach ($countryCodes as $code => $length) {
        if (substr($phone, 0, $length) === $code) {
            $countryCode = $code;
            $phone = substr($phone, $length);
            // Para debugging
            error_log("Código de país encontrado: " . $countryCode);
            error_log("Resto del número: " . $phone);
            break;
        }
    }
    
    // Si se encontró un código de país, formatear con el estándar internacional
    if (!empty($countryCode)) {
        $formatted = '+' . $countryCode . ' ' . $phone;
        // Para debugging
        error_log("Número formateado: " . $formatted);
        return $formatted;
    }
    
    // Si no se encontró código de país, devolver el número con formato básico
    error_log("No se encontró código de país, número original: " . $phone);
    return '+' . substr($phone, 0, 3) . ' ' . substr($phone, 3);
}
?> 