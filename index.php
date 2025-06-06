<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'auth/auth_check.php';

// Verificar permisos
$can_view = checkPermission('consulta');

if (!$can_view) {
    header("Location: auth/login.php");
    exit;
}

// Verificar préstamos vencidos
try {
    checkExpiredLoans();
} catch (Exception $e) {
    error_log("Error al verificar préstamos vencidos: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </h2>
                <div class="text-muted mt-1">
                    Resumen general del sistema
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resumen General -->
    <div class="row mb-4">
        <!-- Resumen de Productos -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-box"></i> Productos
                    </h5>
                    <?php
                    try {
                        $query = "SELECT 
                                 COUNT(*) as total_productos,
                                 SUM(stock) as total_stock
                                 FROM productos";
                        $result = $conn->query($query);
                        $row = $result->fetch_assoc();
                    ?>
                    <div class="mt-3">
                        <p class="card-text">
                            <i class="fas fa-cubes"></i> Total: 
                            <span class="badge bg-primary"><?php echo number_format($row['total_productos']); ?></span>
                        </p>
                        <p class="card-text">
                            <i class="fas fa-warehouse"></i> Stock Total: 
                            <span class="badge bg-info"><?php echo number_format($row['total_stock']); ?></span>
                        </p>
                    </div>
                    <?php
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error al cargar datos de productos</div>';
                        error_log($e->getMessage());
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Resumen de Préstamos -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-handshake"></i> Préstamos
                    </h5>
                    <?php
                    try {
                        $query = "SELECT 
                                 COUNT(*) as total_prestamos,
                                 SUM(CASE WHEN estado = 'ACTIVO' THEN 1 ELSE 0 END) as prestamos_activos,
                                 SUM(CASE WHEN estado = 'VENCIDO' THEN 1 ELSE 0 END) as prestamos_vencidos
                                 FROM prestamos";
                        $result = $conn->query($query);
                        $row = $result->fetch_assoc();
                    ?>
                    <div class="mt-3">
                        <p class="card-text">
                            <i class="fas fa-list"></i> Total: 
                            <span class="badge bg-primary"><?php echo number_format($row['total_prestamos']); ?></span>
                        </p>
                        <p class="card-text">
                            <i class="fas fa-check-circle"></i> Activos: 
                            <span class="badge bg-success"><?php echo number_format($row['prestamos_activos']); ?></span>
                        </p>
                        <p class="card-text">
                            <i class="fas fa-exclamation-circle"></i> Vencidos: 
                            <span class="badge bg-danger"><?php echo number_format($row['prestamos_vencidos']); ?></span>
                        </p>
                    </div>
                    <?php
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error al cargar datos de préstamos</div>';
                        error_log($e->getMessage());
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Resumen de Renovaciones -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-sync"></i> Renovaciones
                    </h5>
                    <?php
                    try {
                        $query = "SELECT 
                                 COUNT(*) as total_renovaciones,
                                 COUNT(DISTINCT prestamo_id) as prestamos_renovados
                                 FROM renovaciones";
                        $result = $conn->query($query);
                        $row = $result->fetch_assoc();
                    ?>
                    <div class="mt-3">
                        <p class="card-text">
                            <i class="fas fa-history"></i> Total: 
                            <span class="badge bg-primary"><?php echo number_format($row['total_renovaciones']); ?></span>
                        </p>
                        <p class="card-text">
                            <i class="fas fa-redo"></i> Préstamos Renovados: 
                            <span class="badge bg-info"><?php echo number_format($row['prestamos_renovados']); ?></span>
                        </p>
                    </div>
                    <?php
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error al cargar datos de renovaciones</div>';
                        error_log($e->getMessage());
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Resumen Total de Usuarios -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-users"></i> Total Usuarios
                    </h5>
                    <?php
                    try {
                        $query = "SELECT 
                                 COUNT(*) as total_usuarios,
                                 SUM(CASE WHEN estado = 'LIBRE' THEN 1 ELSE 0 END) as usuarios_libres,
                                 SUM(CASE WHEN estado = 'OCUPADO' THEN 1 ELSE 0 END) as usuarios_ocupados,
                                 SUM(CASE WHEN estado = 'VENCIDO' THEN 1 ELSE 0 END) as usuarios_vencidos
                                 FROM usuarios_productos";
                        $result = $conn->query($query);
                        $row = $result->fetch_assoc();
                    ?>
                    <div class="mt-3">
                        <p class="card-text">
                            <i class="fas fa-user-check"></i> Libres: 
                            <span class="badge bg-success"><?php echo number_format($row['usuarios_libres']); ?></span>
                        </p>
                        <p class="card-text">
                            <i class="fas fa-user-clock"></i> Ocupados: 
                            <span class="badge bg-warning"><?php echo number_format($row['usuarios_ocupados']); ?></span>
                        </p>
                        <p class="card-text">
                            <i class="fas fa-user-times"></i> Vencidos: 
                            <span class="badge bg-danger"><?php echo number_format($row['usuarios_vencidos']); ?></span>
                        </p>
                        <p class="card-text">
                            <i class="fas fa-users"></i> Total: 
                            <span class="badge bg-primary"><?php echo number_format($row['total_usuarios']); ?></span>
                        </p>
                    </div>
                    <?php
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error al cargar datos de usuarios</div>';
                        error_log($e->getMessage());
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen por Producto -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-box-open"></i> Resumen por Producto
                    </h3>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $query = "SELECT id, nombre FROM productos ORDER BY nombre";
                        $productos = $conn->query($query);
                        
                        while ($producto = $productos->fetch_assoc()):
                            $producto_id = $producto['id'];
                            
                            $query = "SELECT 
                                     COUNT(*) as total_usuarios,
                                     SUM(CASE WHEN estado = 'LIBRE' THEN 1 ELSE 0 END) as usuarios_libres,
                                     SUM(CASE WHEN estado = 'OCUPADO' THEN 1 ELSE 0 END) as usuarios_ocupados,
                                     SUM(CASE WHEN estado = 'VENCIDO' THEN 1 ELSE 0 END) as usuarios_vencidos
                                     FROM usuarios_productos 
                                     WHERE producto_id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $producto_id);
                            $stmt->execute();
                            $stats = $stmt->get_result()->fetch_assoc();
                    ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-box"></i> <?php echo htmlspecialchars($producto['nombre']); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="small-box bg-success bg-opacity-10 p-3 rounded">
                                        <h6><i class="fas fa-user-check"></i> Usuarios Libres</h6>
                                        <h4><?php echo number_format($stats['usuarios_libres']); ?></h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="small-box bg-warning bg-opacity-10 p-3 rounded">
                                        <h6><i class="fas fa-user-clock"></i> Usuarios Ocupados</h6>
                                        <h4><?php echo number_format($stats['usuarios_ocupados']); ?></h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="small-box bg-danger bg-opacity-10 p-3 rounded">
                                        <h6><i class="fas fa-user-times"></i> Usuarios Vencidos</h6>
                                        <h4><?php echo number_format($stats['usuarios_vencidos']); ?></h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="small-box bg-primary bg-opacity-10 p-3 rounded">
                                        <h6><i class="fas fa-users"></i> Total Usuarios</h6>
                                        <h4><?php echo number_format($stats['total_usuarios']); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error al cargar resumen por producto</div>';
                        error_log($e->getMessage());
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Préstamos Próximos a Vencer -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock"></i> Préstamos Próximos a Vencer
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-box"></i> Producto</th>
                                    <th><i class="fas fa-user"></i> Usuario</th>
                                    <th><i class="fas fa-phone"></i> Teléfono</th>
                                    <th><i class="fas fa-calendar"></i> Fecha Fin</th>
                                    <th><i class="fas fa-hourglass-half"></i> Días Restantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $query = "SELECT 
                                             p.id,
                                             pr.nombre as producto_nombre,
                                             up.user_name,
                                             c.telefono,
                                             p.fecha_fin,
                                             DATEDIFF(p.fecha_fin, CURRENT_DATE) as dias_restantes
                                             FROM prestamos p
                                             JOIN usuarios_productos up ON p.usuario_producto_id = up.id
                                             JOIN clientes c ON p.cliente_id = c.id
                                             JOIN productos pr ON up.producto_id = pr.id
                                             WHERE p.estado = 'ACTIVO'
                                             AND p.fecha_fin >= CURRENT_DATE
                                             ORDER BY dias_restantes ASC
                                             LIMIT 5";
                                    $result = $conn->query($query);
                                    
                                    while ($row = $result->fetch_assoc()):
                                        $clase = '';
                                        if ($row['dias_restantes'] <= 3) {
                                            $clase = 'table-danger';
                                        } elseif ($row['dias_restantes'] <= 7) {
                                            $clase = 'table-warning';
                                        }
                                ?>
                                <tr class="<?php echo $clase; ?>">
                                    <td><?php echo htmlspecialchars($row['producto_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                    <td>
                                        <a href="tel:<?php echo htmlspecialchars(formatPhoneNumber($row['telefono'])); ?>" class="text-decoration-none">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars(formatPhoneNumber($row['telefono'])); ?>
                                        </a>
                                    </td>
                                    <td><?php echo formatDate($row['fecha_fin']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['dias_restantes'] <= 3 ? 'bg-danger' : ($row['dias_restantes'] <= 7 ? 'bg-warning' : 'bg-success'); ?>">
                                            <?php echo $row['dias_restantes']; ?> días
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="5" class="text-center text-danger">Error al cargar préstamos próximos a vencer</td></tr>';
                                    error_log($e->getMessage());
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Últimas Renovaciones -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sync"></i> Últimas Renovaciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-box"></i> Producto</th>
                                    <th><i class="fas fa-user"></i> Usuario</th>
                                    <th><i class="fas fa-phone"></i> Teléfono</th>
                                    <th><i class="fas fa-calendar-plus"></i> Fecha Renovación</th>
                                    <th><i class="fas fa-calendar-check"></i> Nueva Fecha Fin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $query = "SELECT 
                                             r.fecha_renovacion,
                                             r.fecha_nueva,
                                             pr.nombre as producto_nombre,
                                             up.user_name,
                                             c.telefono
                                             FROM renovaciones r
                                             JOIN prestamos p ON r.prestamo_id = p.id
                                             JOIN usuarios_productos up ON p.usuario_producto_id = up.id
                                             JOIN productos pr ON up.producto_id = pr.id
                                             JOIN clientes c ON p.cliente_id = c.id
                                             ORDER BY r.fecha_renovacion DESC
                                             LIMIT 5";
                                    $result = $conn->query($query);
                                    
                                    while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['producto_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                    <td>
                                        <a href="tel:<?php echo htmlspecialchars(formatPhoneNumber($row['telefono'])); ?>" class="text-decoration-none">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars(formatPhoneNumber($row['telefono'])); ?>
                                        </a>
                                    </td>
                                    <td><?php echo formatDate($row['fecha_renovacion']); ?></td>
                                    <td><?php echo formatDate($row['fecha_nueva']); ?></td>
                                </tr>
                                <?php 
                                    endwhile;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="5" class="text-center text-danger">Error al cargar últimas renovaciones</td></tr>';
                                    error_log($e->getMessage());
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 