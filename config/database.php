<?php
// Configuración de la base de datos
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'inventario';

// Crear conexión
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer charset
$conn->set_charset("utf8mb4");
?> 