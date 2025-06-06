<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Verificar que se recibió el ID
if (!isset($_GET['prestamo_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de préstamo no proporcionado']);
    exit;
}

$prestamo_id = intval($_GET['prestamo_id']);

// Obtener datos del préstamo original
$query_prestamo = "SELECT 
                    p.fecha_inicio as fecha_original,
                    p.fecha_fin as fecha_fin_original,
                    p.precio_unitario as precio_original,
                    p.precio_final as precio_final_original,
                    p.cantidad_dispositivos,
                    COALESCE(fp.nombre, 'Sin factor') as factor_precio
                  FROM prestamos p
                  LEFT JOIN factores_precio fp ON p.factor_precio_id = fp.id
                  WHERE p.id = ?";

$stmt = $conn->prepare($query_prestamo);
$stmt->bind_param("i", $prestamo_id);
$stmt->execute();
$prestamo_original = $stmt->get_result()->fetch_assoc();

// Obtener renovaciones
$query = "SELECT 
            fecha_renovacion,
            precio_anterior,
            precio_nuevo,
            fecha_anterior,
            fecha_nueva
          FROM renovaciones 
          WHERE prestamo_id = ?
          ORDER BY fecha_renovacion DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $prestamo_id);
$stmt->execute();
$result = $stmt->get_result();

$renovaciones = [];
while ($row = $result->fetch_assoc()) {
    $renovaciones[] = $row;
}

// Combinar datos del préstamo original con las renovaciones
$response = [
    'prestamo_original' => $prestamo_original,
    'renovaciones' => $renovaciones
];

// Devolver como JSON
header('Content-Type: application/json');
echo json_encode($response); 