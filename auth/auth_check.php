<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

// Función para verificar permisos según rol
function checkPermission($required_role) {
    $user_role = $_SESSION['rol'] ?? '';
    
    switch ($required_role) {
        case 'admin':
            return $user_role === 'admin';
        
        case 'operador':
            return in_array($user_role, ['admin', 'operador']);
        
        case 'consulta':
            return in_array($user_role, ['admin', 'operador', 'consulta']);
        
        default:
            return false;
    }
}

// Función para registrar actividad
function logActivity($action, $description = '') {
    global $conn;
    
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO logs_actividad (usuario_id, accion, descripcion, ip_address) 
                           VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $description, $ip);
    $stmt->execute();
} 