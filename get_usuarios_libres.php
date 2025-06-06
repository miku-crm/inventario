<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['producto_id'])) {
    $producto_id = intval($_POST['producto_id']);
    
    $query = "SELECT id, user_name, prestamos_activos, max_prestamos 
             FROM usuarios_productos 
             WHERE producto_id = ? 
             AND prestamos_activos < max_prestamos
             AND estado != 'VENCIDO'
             ORDER BY id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo '<option value="">Seleccione un usuario</option>';
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . $row['id'] . '">' . 
             htmlspecialchars($row['user_name']) . 
             ' (Préstamos: ' . $row['prestamos_activos'] . '/' . $row['max_prestamos'] . ')</option>';
    }
} else {
    echo '<option value="">Error al cargar usuarios</option>';
}
?> 