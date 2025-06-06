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

// Verificar si hay una acción POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Verificar permisos para acciones que modifican datos
    if (!$can_edit) {
        header("Location: renovar_usuario_producto.php?error=unauthorized");
        exit;
    }
    
    if ($action === 'renovar') {
        $usuario_producto_id = intval($_POST['usuario_producto_id']);
        $nueva_fecha_expiracion = sanitize($_POST['nueva_fecha_expiracion']);
        $cantidad_nueva = floatval($_POST['cantidad_nueva']);
        $notas = sanitize($_POST['notas']);
        
        // Obtener datos actuales del usuario_producto
        $query = "SELECT up.*, p.precio_compra 
                 FROM usuarios_productos up 
                 JOIN productos p ON up.producto_id = p.id 
                 WHERE up.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $usuario_producto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario_producto = $result->fetch_assoc();
        
        // Verificar que la cantidad sea mayor a cero
        if ($cantidad_nueva <= 0) {
            header("Location: renovar_usuario_producto.php?error=1&id=" . $usuario_producto_id);
            exit;
        }
        
        // Verificar si hay suficiente stock para la cantidad nueva
        $query = "SELECT stock FROM productos WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $usuario_producto['producto_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();
        
        if ($producto['stock'] < $cantidad_nueva) {
            header("Location: renovar_usuario_producto.php?error=2&id=" . $usuario_producto_id);
            exit;
        }
        
        // Calcular precios
        $precio_anterior = $usuario_producto['costo_creacion'];
        $costo_momento_renovacion = $usuario_producto['precio_compra'] * $cantidad_nueva;
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Insertar renovación
            $query = "INSERT INTO renovaciones_usuarios_productos 
                     (usuario_producto_id, fecha_renovacion, fecha_anterior_expiracion, 
                      nueva_fecha_expiracion, cantidad_anterior, cantidad_nueva,
                      precio_anterior, precio_nuevo,
                      costo_momento_renovacion, notas) 
                     VALUES (?, CURRENT_DATE, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issiiddds", 
                $usuario_producto_id, 
                $usuario_producto['fecha_expiracion'],
                $nueva_fecha_expiracion,
                $usuario_producto['cantidad_inicial'],
                $cantidad_nueva,
                $precio_anterior,
                $costo_momento_renovacion,
                $costo_momento_renovacion,
                $notas
            );
            $stmt->execute();
            
            // Actualizar usuario_producto
            $query = "UPDATE usuarios_productos 
                     SET fecha_expiracion = ?,
                         cantidad_inicial = ?,
                         costo_creacion = ?
                     WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sidi", 
                $nueva_fecha_expiracion,
                $cantidad_nueva,
                $costo_momento_renovacion,
                $usuario_producto_id
            );
            $stmt->execute();
            
            // Actualizar stock del producto
            $query = "UPDATE productos 
                     SET stock = stock - ? 
                     WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $cantidad_nueva, $usuario_producto['producto_id']);
            $stmt->execute();
            
            $conn->commit();
            header("Location: usuarios_productos.php?success=4");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
}

include 'includes/header.php';

// Obtener usuario_producto si se proporciona ID
$usuario_producto = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT up.*, p.nombre as producto_nombre, p.precio_compra
              FROM usuarios_productos up 
              JOIN productos p ON up.producto_id = p.id 
              WHERE up.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario_producto = $result->fetch_assoc();
}

if (!$usuario_producto) {
    header("Location: usuarios_productos.php");
    exit;
}
?>

<div class="container-fluid">
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="fas fa-sync"></i> Renovar Usuario de Producto
                </h2>
                <div class="text-muted mt-1">
                    Actualizar fecha de expiración y cantidad
                </div>
            </div>
            <div class="col-auto ms-auto">
                <a href="usuarios_productos.php" class="btn btn-link">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php
        switch ($_GET['error']) {
            case 1:
                echo "La cantidad debe ser mayor a cero.";
                break;
            case 2:
                echo "No hay suficiente stock disponible.";
                break;
            case 'unauthorized':
                echo "No tienes permisos para realizar esta acción.";
                break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información Actual</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Producto</dt>
                                <dd class="col-sm-8">
                                    <i class="fas fa-box"></i> 
                                    <?php echo htmlspecialchars($usuario_producto['producto_nombre']); ?>
                                </dd>
                                
                                <dt class="col-sm-4">Usuario</dt>
                                <dd class="col-sm-8">
                                    <i class="fas fa-user"></i> 
                                    <?php echo htmlspecialchars($usuario_producto['user_name']); ?>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Cantidad Actual</dt>
                                <dd class="col-sm-8">
                                    <i class="fas fa-layer-group"></i> 
                                    <?php echo $usuario_producto['cantidad_inicial']; ?>
                                </dd>
                                
                                <dt class="col-sm-4">Fecha de Expiración</dt>
                                <dd class="col-sm-8">
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo formatDate($usuario_producto['fecha_expiracion']); ?>
                                </dd>
                                
                                <dt class="col-sm-4">Costo Actual</dt>
                                <dd class="col-sm-8">
                                    <i class="fas fa-dollar-sign"></i> 
                                    <?php echo formatMoney($usuario_producto['costo_creacion']); ?>
                                </dd>
                            </dl>
                        </div>
                    </div>

                    <form action="renovar_usuario_producto.php" method="POST">
                        <input type="hidden" name="action" value="renovar">
                        <input type="hidden" name="usuario_producto_id" value="<?php echo $usuario_producto['id']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-calendar-plus"></i> Nueva Fecha de Expiración
                                    </label>
                                    <input type="date" class="form-control" name="nueva_fecha_expiracion" 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-layer-group"></i> Nueva Cantidad
                                    </label>
                                    <input type="number" class="form-control" name="cantidad_nueva" 
                                           min="0.01" step="0.01"
                                           value="<?php echo $usuario_producto['cantidad_inicial']; ?>" required>
                                    <div class="form-text text-muted">
                                        La nueva cantidad debe ser mayor a cero.
                                        Se descontará del stock del producto.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-sticky-note"></i> Notas
                                    </label>
                                    <textarea class="form-control" name="notas" rows="3" 
                                              placeholder="Ingrese notas o comentarios sobre la renovación"></textarea>
                                </div>
                                
                                <div class="alert alert-info">
                                    <h5 class="alert-heading">
                                        <i class="fas fa-calculator"></i> Cálculos de Renovación
                                    </h5>
                                    <p class="mb-1">
                                        Costo base por unidad: 
                                        <strong>$<?php echo formatMoney($usuario_producto['precio_compra']); ?></strong>
                                    </p>
                                    <p class="mb-0">
                                        Costo total nuevo: 
                                        <strong>$<span id="costoTotal">0.00</span></strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end mt-4">
                            <a href="usuarios_productos.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync"></i> Renovar Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function actualizarCalculos() {
        const cantidadNueva = parseFloat($('input[name="cantidad_nueva"]').val()) || 0;
        const costoBase = <?php echo $usuario_producto['precio_compra']; ?>;
        const costoTotal = costoBase * cantidadNueva;
        
        $('#costoTotal').text(costoTotal.toFixed(2));
    }
    
    $('input[name="cantidad_nueva"]').on('input', actualizarCalculos);
    actualizarCalculos();
});
</script>

<?php include 'includes/footer.php'; ?> 