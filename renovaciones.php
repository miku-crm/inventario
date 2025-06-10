<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'auth/auth_check.php';

// Verificar permisos para acciones específicas
$can_edit = checkPermission('operador');
$can_view = checkPermission('consulta');

if (!$can_view) {
    header("Location: index.php");
    exit;
}

// Verificar usuarios expirados
checkExpiredUsers();

// Verificar si hay un préstamo seleccionado
$prestamo_id = isset($_GET['prestamo_id']) ? intval($_GET['prestamo_id']) : 0;

// Agregar depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Préstamo ID = " . $prestamo_id . " -->";

if ($prestamo_id > 0) {
    // Consulta modificada para incluir préstamos con usuarios expirados
    $query = "SELECT p.*, 
             up.user_name, 
             up.estado as estado_usuario, 
             up.fecha_expiracion,
             pr.nombre as producto_nombre, 
             c.nombre as cliente_nombre,
             DATEDIFF(CURRENT_DATE, p.fecha_fin) as dias_vencido,
             DATEDIFF(up.fecha_expiracion, CURRENT_DATE) as dias_expiracion_usuario,
             up.id as usuario_producto_id
             FROM prestamos p 
             INNER JOIN usuarios_productos up ON p.usuario_producto_id = up.id 
             INNER JOIN productos pr ON up.producto_id = pr.id 
             INNER JOIN clientes c ON p.cliente_id = c.id 
             WHERE p.id = ?";
    
    echo "<!-- Debug: Query = " . $query . " -->";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $prestamo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $prestamo = $result->fetch_assoc();
    
    echo "<!-- Debug: Préstamo encontrado = " . ($prestamo ? 'SI' : 'NO') . " -->";
    if ($prestamo) {
        echo "<!-- Debug: Valores del préstamo:";
        echo "\n  estado_usuario = " . $prestamo['estado_usuario'];
        echo "\n  fecha_expiracion = " . $prestamo['fecha_expiracion'];
        echo "\n  dias_expiracion_usuario = " . $prestamo['dias_expiracion_usuario'];
        echo "\n-->";
    }
    
    if (!$prestamo) {
        echo "<!-- Debug: Préstamo no encontrado o fuera del período permitido para renovación -->";
        header("Location: prestamos.php?error=2");
        exit;
    }

    // Verificar si el préstamo está vencido y fuera del período de renovación
    if ($prestamo['estado'] === 'VENCIDO' && 
        $prestamo['dias_vencido'] > 30 && 
        $prestamo['estado_usuario'] !== 'EXPIRADO' && 
        $prestamo['dias_expiracion_usuario'] >= 0) {
        header("Location: prestamos.php?error=2");
        exit;
    }

    // Verificar si el usuario está expirado y necesita renovación
    $usuario_expirado = ($prestamo['estado_usuario'] === 'VENCIDO') || ($prestamo['dias_expiracion_usuario'] < 0);
    
    echo "<!-- Debug: Valores para determinar si el usuario está vencido:";
    echo "\n  estado_usuario = '" . $prestamo['estado_usuario'] . "'";
    echo "\n  dias_expiracion_usuario = " . $prestamo['dias_expiracion_usuario'];
    echo "\n  fecha_expiracion = " . $prestamo['fecha_expiracion'];
    echo "\n  usuario_expirado = " . ($usuario_expirado ? 'true' : 'false');
    echo "\n-->";
}

// Verificar si hay una acción POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Verificar permisos para acciones que modifican datos
    if (!$can_edit) {
        header("Location: renovaciones.php?error=unauthorized");
        exit;
    }
    
    if ($action === 'renovar') {
        $prestamo_id = intval($_POST['prestamo_id']);
        $fecha_anterior = sanitize($_POST['fecha_anterior']);
        $fecha_nueva = sanitize($_POST['fecha_nueva']);
        $precio_anterior = floatval($_POST['precio_anterior']);
        $precio_nuevo = floatval($_POST['precio_nuevo']);
        $factor_precio_id = !empty($_POST['factor_precio_id']) ? intval($_POST['factor_precio_id']) : null;
        $cantidad_dispositivos = intval($_POST['cantidad_dispositivos']);
        
        // Verificar que cantidad_dispositivos sea válida
        if ($cantidad_dispositivos < 1) {
            header("Location: renovaciones.php?prestamo_id=" . $prestamo_id . "&error=1");
            exit;
        }
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Obtener información actual del préstamo y usuario
            $query = "SELECT p.*, up.max_prestamos,
                     (SELECT COALESCE(SUM(cantidad_dispositivos), 0) 
                      FROM prestamos 
                      WHERE usuario_producto_id = up.id 
                      AND estado = 'ACTIVO'
                      AND id != p.id) as otros_dispositivos
                     FROM prestamos p
                     JOIN usuarios_productos up ON p.usuario_producto_id = up.id
                     WHERE p.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $prestamo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $prestamo_actual = $result->fetch_assoc();
            
            // Verificar disponibilidad de dispositivos
            if ($prestamo_actual['otros_dispositivos'] + $cantidad_dispositivos > $prestamo_actual['max_prestamos']) {
                $conn->rollback();
                header("Location: renovaciones.php?prestamo_id=" . $prestamo_id . "&error=2");
                exit;
            }
            
            // Registrar renovación
            $query = "INSERT INTO renovaciones (prestamo_id, fecha_renovacion, fecha_anterior, 
                     fecha_nueva, precio_anterior, precio_nuevo, factor_precio_id) 
                     VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            
            // Crear variable temporal para factor_precio_id
            $factor_precio_id_temp = $factor_precio_id ? $factor_precio_id : null;
            
            $stmt->bind_param("issddi", $prestamo_id, $fecha_anterior, $fecha_nueva, 
                            $precio_anterior, $precio_nuevo, 
                            $factor_precio_id_temp);
            $stmt->execute();
            
            // Actualizar préstamo
            $query = "UPDATE prestamos 
                     SET fecha_fin = ?, 
                         precio_final = ?, 
                         cantidad_dispositivos = ?,
                         estado = 'ACTIVO' 
                     WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sdii", $fecha_nueva, $precio_nuevo, $cantidad_dispositivos, $prestamo_id);
            $stmt->execute();
            
            // Actualizar prestamos_activos del usuario
            $query = "UPDATE usuarios_productos up
                     SET prestamos_activos = (
                         SELECT COALESCE(SUM(cantidad_dispositivos), 0)
                         FROM prestamos
                         WHERE usuario_producto_id = up.id
                         AND estado = 'ACTIVO'
                     )
                     WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $prestamo_actual['usuario_producto_id']);
            $stmt->execute();
            
            $conn->commit();
            header("Location: prestamos.php?success=3");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="fas fa-sync"></i> Renovación de Préstamo
                </h2>
                <div class="text-muted mt-1">
                    Actualizar fecha y condiciones del préstamo
                </div>
            </div>
            <div class="col-auto ms-auto">
                <a href="prestamos.php" class="btn btn-link">
                    <i class="fas fa-arrow-left"></i> Volver a Préstamos
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php
        switch ($_GET['error']) {
            case 1:
                echo "La cantidad de dispositivos debe ser mayor a cero.";
                break;
            case 2:
                echo "Se ha excedido el límite de dispositivos permitidos para este usuario.";
                break;
            case 'unauthorized':
                echo "No tienes permisos para realizar esta acción.";
                break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (!isset($prestamo)): ?>
        <div class="alert alert-danger">
            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Préstamo No Encontrado</h4>
            <p>El préstamo no existe o no está disponible para renovación.</p>
            <hr>
            <a href="prestamos.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Volver a Préstamos
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i> Detalles del Préstamo
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="info-item">
                                    <label class="text-muted mb-1">Producto</label>
                                    <div>
                                        <i class="fas fa-box"></i>
                                        <?php echo htmlspecialchars($prestamo['producto_nombre']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-item">
                                    <label class="text-muted mb-1">Usuario</label>
                                    <div>
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($prestamo['user_name']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-item">
                                    <label class="text-muted mb-1">Cliente</label>
                                    <div>
                                        <i class="fas fa-users"></i>
                                        <?php echo htmlspecialchars($prestamo['cliente_nombre']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-item">
                                    <label class="text-muted mb-1">Precio Actual</label>
                                    <div>
                                        <i class="fas fa-dollar-sign"></i>
                                        <?php echo formatMoney($prestamo['precio_final']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label class="text-muted mb-1">Fecha Inicio</label>
                                    <div>
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo formatDate($prestamo['fecha_inicio']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label class="text-muted mb-1">Fecha Fin</label>
                                    <div>
                                        <i class="fas fa-calendar-check"></i>
                                        <?php echo formatDate($prestamo['fecha_fin']); ?>
                                        <?php if ($prestamo['dias_vencido'] > 0): ?>
                                            <span class="badge bg-danger ms-2">
                                                Vencido hace <?php echo $prestamo['dias_vencido']; ?> días
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-sync"></i> Renovar Préstamo
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($usuario_expirado): ?>
                            <div class="alert alert-warning">
                                <h4 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle"></i> Usuario Expirado
                                </h4>
                                <p>
                                    El usuario del producto ha expirado. Es necesario renovar el usuario antes de 
                                    poder renovar el préstamo.
                                </p>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Usuario:</strong> <?php echo htmlspecialchars($prestamo['user_name']); ?><br>
                                        <strong>Fecha de Expiración:</strong> <?php echo formatDate($prestamo['fecha_expiracion']); ?><br>
                                        <?php if ($prestamo['dias_expiracion_usuario'] < 0): ?>
                                            <strong class="text-danger">Expirado hace <?php echo abs($prestamo['dias_expiracion_usuario']); ?> días</strong>
                                        <?php endif; ?>
                                    </div>
                                    <a href="renovar_usuario_producto.php?id=<?php echo $prestamo['usuario_producto_id']; ?>&return_to=renovaciones.php?prestamo_id=<?php echo $prestamo_id; ?>" 
                                       class="btn btn-warning">
                                        <i class="fas fa-sync"></i> Renovar Usuario
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form action="renovaciones.php" method="POST">
                                <input type="hidden" name="action" value="renovar">
                                <input type="hidden" name="prestamo_id" value="<?php echo $prestamo['id']; ?>">
                                <input type="hidden" name="fecha_anterior" value="<?php echo $prestamo['fecha_fin']; ?>">
                                <input type="hidden" name="precio_anterior" value="<?php echo $prestamo['precio_final']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-calendar-plus"></i> Nueva Fecha de Fin
                                            </label>
                                            <input type="date" class="form-control" name="fecha_nueva" 
                                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                                   value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" 
                                                   required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-mobile-alt"></i> Cantidad de Dispositivos
                                            </label>
                                            <input type="number" class="form-control" name="cantidad_dispositivos"
                                                   min="1" value="<?php echo $prestamo['cantidad_dispositivos']; ?>" 
                                                   required>
                                            <div class="form-text text-muted">
                                                Número de dispositivos que el cliente puede usar simultáneamente
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-percentage"></i> Factor de Precio
                                            </label>
                                            <select class="form-select" name="factor_precio_id" id="factor_precio_id">
                                                <option value="">Sin factor adicional</option>
                                                <?php
                                                $query = "SELECT * FROM factores_precio WHERE tipo = 'venta' ORDER BY porcentaje";
                                                $result = $conn->query($query);
                                                while ($factor = $result->fetch_assoc()):
                                                ?>
                                                <option value="<?php echo $factor['id']; ?>" 
                                                        data-porcentaje="<?php echo $factor['porcentaje']; ?>">
                                                    <?php echo htmlspecialchars($factor['nombre']); ?> 
                                                    (<?php echo $factor['porcentaje']; ?>%)
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">
                                                <i class="fas fa-dollar-sign"></i> Nuevo Precio
                                            </label>
                                            <input type="number" class="form-control" name="precio_nuevo" 
                                                   min="0.01" step="0.01"
                                                   value="<?php echo $prestamo['precio_final']; ?>" required>
                                            <div class="form-text text-muted">
                                                Precio base: $<?php echo formatMoney($prestamo['precio_final']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end mt-4">
                                    <a href="prestamos.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-sync"></i> Renovar Préstamo
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    let precioBase = <?php echo $prestamo['precio_final']; ?>;
    
    function actualizarPrecio() {
        const factorSeleccionado = $('#factor_precio_id option:selected');
        const porcentaje = factorSeleccionado.data('porcentaje') || 0;
        const cantidadDispositivos = parseInt($('input[name="cantidad_dispositivos"]').val()) || 1;
        
        const precioUnitario = precioBase * (1 + (porcentaje / 100));
        const precioTotal = precioUnitario * cantidadDispositivos;
        
        $('input[name="precio_nuevo"]').val(precioTotal.toFixed(2));
    }
    
    $('#factor_precio_id, input[name="cantidad_dispositivos"]').on('change', actualizarPrecio);
    actualizarPrecio();
});
</script>

<?php include 'includes/footer.php'; ?> 