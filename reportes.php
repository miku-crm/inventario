<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Función para construir los filtros de fecha
function construirFiltrosFecha($params = [], $types = "") {
    $where = "";
    
    if (!empty($_GET['producto_id'])) {
        $where .= " AND producto_id = ?";
        $params[] = intval($_GET['producto_id']);
        $types .= "i";
    }
    
    if (!empty($_GET['fecha_desde'])) {
        $where .= " AND fecha_transaccion >= ?";
        $params[] = $_GET['fecha_desde'];
        $types .= "s";
    }
    
    if (!empty($_GET['fecha_hasta'])) {
        $where .= " AND fecha_transaccion <= ?";
        $params[] = $_GET['fecha_hasta'];
        $types .= "s";
    }
    
    return ['where' => $where, 'params' => $params, 'types' => $types];
}

// CTE Base optimizada para todas las transacciones
$base_query = "WITH primera_renovacion_prestamo AS (
    SELECT 
        prestamo_id,
        precio_anterior as precio_inicial
    FROM renovaciones r1
    WHERE fecha_renovacion = (
        SELECT MIN(fecha_renovacion)
        FROM renovaciones r2
        WHERE r2.prestamo_id = r1.prestamo_id
    )
),
primera_renovacion_usuario AS (
    -- Obtener la primera renovación de cada usuario_producto para tener el costo real de creación
    SELECT 
        usuario_producto_id,
        precio_anterior as costo_creacion_real,
        MIN(fecha_renovacion) as primera_fecha
    FROM renovaciones_usuarios_productos
    GROUP BY usuario_producto_id
),
todas_transacciones AS (
    -- Usuarios SIN renovaciones
    SELECT 
        up.created_at as fecha_transaccion,
        'CREACIÓN' as tipo,
        pr.id as producto_id,
        pr.nombre as producto_nombre,
        up.user_name as detalle_nombre,
        up.costo_creacion as monto,
        up.costo_creacion as monto_inversion,
        0 as monto_prestamo,
        0 as monto_renovacion,
        CONCAT('Usuario: ', up.user_name) as descripcion,
        up.id as transaccion_id
    FROM usuarios_productos up
    JOIN productos pr ON up.producto_id = pr.id
    WHERE NOT EXISTS (
        SELECT 1 
        FROM renovaciones_usuarios_productos rup 
        WHERE rup.usuario_producto_id = up.id
    )

    UNION ALL

    -- Usuarios CON renovaciones (CREACIÓN)
    SELECT 
        up.created_at as fecha_transaccion,
        'CREACIÓN' as tipo,
        pr.id as producto_id,
        pr.nombre as producto_nombre,
        up.user_name as detalle_nombre,
        pr_ren.costo_creacion_real as monto,
        pr_ren.costo_creacion_real as monto_inversion,
        0 as monto_prestamo,
        0 as monto_renovacion,
        CONCAT('Usuario: ', up.user_name) as descripcion,
        up.id as transaccion_id
    FROM usuarios_productos up
    JOIN productos pr ON up.producto_id = pr.id
    JOIN primera_renovacion_usuario pr_ren ON pr_ren.usuario_producto_id = up.id

    UNION ALL

    -- Renovaciones de Usuarios
    SELECT 
        rup.fecha_renovacion as fecha_transaccion,
        'RENOVACIÓN_USUARIO' as tipo,
        pr.id as producto_id,
        pr.nombre as producto_nombre,
        up.user_name as detalle_nombre,
        rup.costo_momento_renovacion as monto,
        rup.costo_momento_renovacion as monto_inversion,
        0 as monto_prestamo,
        0 as monto_renovacion,
        CONCAT('Renovación Usuario: ', up.user_name) as descripcion,
        rup.id as transaccion_id
    FROM renovaciones_usuarios_productos rup
    JOIN usuarios_productos up ON up.id = rup.usuario_producto_id
    JOIN productos pr ON pr.id = up.producto_id

    UNION ALL

    -- Préstamos iniciales
    SELECT 
        p.created_at as fecha_transaccion,
        'PRÉSTAMO' as tipo,
        pr.id as producto_id,
        pr.nombre as producto_nombre,
        c.nombre as detalle_nombre,
        COALESCE(
            (SELECT precio_inicial 
             FROM primera_renovacion_prestamo pr_ren 
             WHERE pr_ren.prestamo_id = p.id),
            p.precio_final
        ) as monto,
        0 as monto_inversion,
        COALESCE(
            (SELECT precio_inicial 
             FROM primera_renovacion_prestamo pr_ren 
             WHERE pr_ren.prestamo_id = p.id),
            p.precio_final
        ) as monto_prestamo,
        0 as monto_renovacion,
        CONCAT('Cliente: ', c.nombre, ' - Usuario: ', up.user_name) as descripcion,
        p.id as transaccion_id
    FROM prestamos p
    JOIN usuarios_productos up ON p.usuario_producto_id = up.id
    JOIN productos pr ON up.producto_id = pr.id
    JOIN clientes c ON p.cliente_id = c.id
    WHERE p.estado != 'CANCELADO'

    UNION ALL

    -- Renovaciones de préstamos
    SELECT 
        r.fecha_renovacion as fecha_transaccion,
        'RENOVACIÓN' as tipo,
        pr.id as producto_id,
        pr.nombre as producto_nombre,
        c.nombre as detalle_nombre,
        r.precio_nuevo as monto,
        0 as monto_inversion,
        0 as monto_prestamo,
        r.precio_nuevo as monto_renovacion,
        CONCAT('Cliente: ', c.nombre, ' - Usuario: ', up.user_name) as descripcion,
        r.id as transaccion_id
    FROM renovaciones r
    JOIN prestamos p ON r.prestamo_id = p.id
    JOIN usuarios_productos up ON p.usuario_producto_id = up.id
    JOIN productos pr ON up.producto_id = pr.id
    JOIN clientes c ON p.cliente_id = c.id
    WHERE p.estado != 'CANCELADO'
)";

include 'includes/header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Reportes de Costos y Ganancias</h2>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Producto</label>
                    <select name="producto_id" class="form-select">
                        <option value="">Todos los productos</option>
                        <?php
                        $query = "SELECT id, nombre FROM productos ORDER BY nombre";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()):
                            $selected = (isset($_GET['producto_id']) && $_GET['producto_id'] == $row['id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($row['nombre']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" 
                           value="<?php echo $_GET['fecha_desde'] ?? ''; ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" 
                           value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen General -->
    <div class="row mb-4">
        <?php
        $filtros = construirFiltrosFecha();
        
        // Una única consulta para obtener todos los totales
        $query = $base_query . "
        SELECT 
            COALESCE(SUM(monto_inversion), 0) as total_inversion,
            COALESCE(SUM(monto_prestamo), 0) as total_prestamos,
            COALESCE(SUM(monto_renovacion), 0) as total_renovaciones
        FROM todas_transacciones
        WHERE 1=1" . $filtros['where'];
        
        $stmt = $conn->prepare($query);
        if (!empty($filtros['params'])) {
            $stmt->bind_param($filtros['types'], ...$filtros['params']);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $totales = $result->fetch_assoc();
        
        $total_ingresos = ($totales['total_prestamos'] ?? 0) + ($totales['total_renovaciones'] ?? 0);
        $total_inversion = $totales['total_inversion'] ?? 0;
        $ganancia_neta = $total_ingresos - $total_inversion;
        ?>
        
        <!-- Total Inversión -->
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Inversión</h5>
                    <h3 class="mb-0">$<?php echo formatMoney($total_inversion); ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Total Ingresos -->
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Ingresos</h5>
                    <h3 class="mb-0">$<?php echo formatMoney($total_ingresos); ?></h3>
                    <small>
                        Préstamos: $<?php echo formatMoney($totales['total_prestamos'] ?? 0); ?><br>
                        Renovaciones: $<?php echo formatMoney($totales['total_renovaciones'] ?? 0); ?>
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Ganancia Neta -->
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Ganancia Neta</h5>
                    <?php
                    $porcentaje_ganancia = $total_inversion > 0 ? 
                        ($ganancia_neta / $total_inversion * 100) : 0;
                    ?>
                    <h3 class="mb-0">$<?php echo formatMoney($ganancia_neta); ?></h3>
                    <small>ROI: <?php echo number_format($porcentaje_ganancia, 2); ?>%</small>
                </div>
            </div>
        </div>
        
        <!-- Promedio Mensual -->
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Promedio Mensual</h5>
                    <?php
                    $query = $base_query . "
                    SELECT MIN(fecha_transaccion) as fecha_primera_operacion
                    FROM todas_transacciones
                    WHERE 1=1" . $filtros['where'];
                    
                    $stmt = $conn->prepare($query);
                    if (!empty($filtros['params'])) {
                        $stmt->bind_param($filtros['types'], ...$filtros['params']);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    $fecha_inicio = new DateTime($row['fecha_primera_operacion']);
                    $fecha_actual = new DateTime();
                    $diff = $fecha_inicio->diff($fecha_actual);
                    $meses = ($diff->y * 12) + $diff->m + ($diff->d / 30);
                    $promedio_mensual = $meses > 0 ? $ganancia_neta / $meses : $ganancia_neta;
                    ?>
                    <h3 class="mb-0">$<?php echo formatMoney($promedio_mensual); ?></h3>
                </div>
            </div>
        </div>
    </div>

  <!-- Detalles por Producto -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Detalles por Producto</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="productosTable">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Inversión</th>
                            <th>Ingresos Préstamos</th>
                            <th>Ingresos Renovaciones</th>
                            <th>Total Ingresos</th>
                            <th>Ganancia Neta</th>
                            <th>ROI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = $base_query . "
                        SELECT 
                            producto_nombre,
                            SUM(monto_inversion) as inversion,
                            SUM(monto_prestamo) as ingresos_prestamos,
                            SUM(monto_renovacion) as ingresos_renovaciones
                        FROM todas_transacciones
                        WHERE 1=1 " . $filtros['where'] . "
                        GROUP BY producto_id, producto_nombre
                        ORDER BY producto_nombre";

                        $stmt = $conn->prepare($query);
                        if (!empty($filtros['params'])) {
                            $stmt->bind_param($filtros['types'], ...$filtros['params']);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        while ($row = $result->fetch_assoc()):
                            $total_ingresos = ($row['ingresos_prestamos'] ?? 0) + ($row['ingresos_renovaciones'] ?? 0);
                            $ganancia_neta = $total_ingresos - ($row['inversion'] ?? 0);
                            $roi = $row['inversion'] > 0 ? ($ganancia_neta / $row['inversion'] * 100) : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['producto_nombre']); ?></td>
                            <td>$<?php echo formatMoney($row['inversion'] ?? 0); ?></td>
                            <td>$<?php echo formatMoney($row['ingresos_prestamos'] ?? 0); ?></td>
                            <td>$<?php echo formatMoney($row['ingresos_renovaciones'] ?? 0); ?></td>
                            <td>$<?php echo formatMoney($total_ingresos); ?></td>
                            <td>$<?php echo formatMoney($ganancia_neta); ?></td>
                            <td><?php echo number_format($roi, 2); ?>%</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Historial de Transacciones -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Historial de Transacciones</h5>
        </div>
        <div class="card-body">
            <!-- Filtro de producto -->
            <div class="mb-3">
                <select id="filtroProducto" class="form-select">
                    <option value="">Todos los productos</option>
                    <?php
                    $query = "SELECT id, nombre FROM productos ORDER BY nombre";
                    $result = $conn->query($query);
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <option value="<?php echo htmlspecialchars($row['nombre']); ?>">
                        <?php echo htmlspecialchars($row['nombre']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="table-responsive">
                <table class="table table-striped" id="transaccionesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Usuario/Cliente</th>
                            <th>Monto</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = $base_query . "
                        SELECT 
                            transaccion_id as id,
                            fecha_transaccion,
                            tipo,
                            producto_nombre,
                            detalle_nombre,
                            monto,
                            descripcion
                        FROM todas_transacciones
                        WHERE 1=1 " . $filtros['where'] . "
                        ORDER BY fecha_transaccion DESC";

                        $stmt = $conn->prepare($query);
                        if (!empty($filtros['params'])) {
                            $stmt->bind_param($filtros['types'], ...$filtros['params']);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        while ($row = $result->fetch_assoc()):
                            $tipo_class = '';
                            switch ($row['tipo']) {
                                case 'CREACIÓN':
                                    $tipo_class = 'danger';
                                    break;
                                case 'PRÉSTAMO':
                                    $tipo_class = 'success';
                                    break;
                                case 'RENOVACIÓN':
                                    $tipo_class = 'info';
                                    break;
                                case 'RENOVACIÓN_USUARIO':
                                    $tipo_class = 'warning';
                                    break;
                            }
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo formatDate($row['fecha_transaccion']); ?></td>
                            <td><span class="badge bg-<?php echo $tipo_class; ?>"><?php echo $row['tipo']; ?></span></td>
                            <td><?php echo htmlspecialchars($row['producto_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['detalle_nombre']); ?></td>
                            <td>$<?php echo formatMoney($row['monto']); ?></td>
                            <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTables
    $('#productosTable').DataTable({
        language: {
            decimal: "",
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(Filtrado de _MAX_ total registros)",
            infoPostFix: "",
            thousands: ",",
            lengthMenu: "Mostrar _MENU_ registros",
            loadingRecords: "Cargando...",
            processing: "Procesando...",
            search: "Buscar:",
            zeroRecords: "Sin resultados encontrados",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        order: [[0, 'asc']]
    });

    var transaccionesTable = $('#transaccionesTable').DataTable({
        language: {
            decimal: "",
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(Filtrado de _MAX_ total registros)",
            infoPostFix: "",
            thousands: ",",
            lengthMenu: "Mostrar _MENU_ registros",
            loadingRecords: "Cargando...",
            processing: "Procesando...",
            search: "Buscar:",
            zeroRecords: "Sin resultados encontrados",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        order: [[0, 'desc']]
    });

    // Filtro de producto para la tabla de transacciones
    $('#filtroProducto').on('change', function() {
        var producto = $(this).val();
        transaccionesTable.column(2).search(producto).draw();
    });
});
</script>

<?php include 'includes/footer.php'; ?>
