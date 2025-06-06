<?php
$password = "admin123";

// Generar hash de la contraseña
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Contraseña original: " . $password . "\n";
echo "Hash generado: " . $hash . "\n\n";

// Verificar si la contraseña coincide con el hash
$verificacion = password_verify($password, $hash);
echo "¿La contraseña coincide? " . ($verificacion ? "SÍ" : "NO") . "\n";

// Verificar si la contraseña coincide con el hash almacenado en la base de datos
$hash_almacenado = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$verificacion_almacenado = password_verify($password, $hash_almacenado);
echo "¿La contraseña coincide con el hash almacenado? " . ($verificacion_almacenado ? "SÍ" : "NO") . "\n";

// Demostrar que cada vez genera un hash diferente pero válido
$otro_hash = password_hash($password, PASSWORD_DEFAULT);
echo "\nOtro hash generado para la misma contraseña: " . $otro_hash . "\n";
echo "¿Este nuevo hash también coincide? " . (password_verify($password, $otro_hash) ? "SÍ" : "NO") . "\n"; 