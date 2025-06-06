<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Verificar si hay una acción POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $producto_id = intval($_POST['producto_id']);
            $user_name = sanitize($_POST['user_name']);
            $password = sanitize($_POST['password']);
            $cantidad_stock = floatval($_POST['cantidad_stock']);
            $fecha_expiracion = sanitize($_POST['fecha_expiracion']);
            $max_prestamos = intval($_POST['max_prestamos']);
            
            // Validar max_prestamos
            if ($max_prestamos < 1) {
                $max_prestamos = 1;
            }
            
            // Validar cantidad
            if ($cantidad_stock <= 0) {
                header("Location: usuarios_productos.php?error=4");
                exit;
            }
            
            // Verificar stock disponible
            $stock_disponible = getAvailableStock($producto_id);
            if ($stock_disponible < $cantidad_stock) {
                header("Location: usuarios_productos.php?error=1");
                exit;
            }
            
            // Obtener precio de compra del producto
            $query = "SELECT precio_compra FROM productos WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $costo_creacion = $row['precio_compra'] * $cantidad_stock;
            
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // Crear usuario
                $query = "INSERT INTO usuarios_productos (producto_id, user_name, password, costo_creacion, cantidad_inicial, fecha_expiracion, max_prestamos) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("issdisi", $producto_id, $user_name, $password, $costo_creacion, $cantidad_stock, $fecha_expiracion, $max_prestamos);
                $stmt->execute();
                
                // Actualizar stock del producto
                $query = "UPDATE productos SET stock = stock - ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $cantidad_stock, $producto_id);
                $stmt->execute();
                
                $conn->commit();
                header("Location: usuarios_productos.php?success=1");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        case 'update':
            $id = intval($_POST['id']);
            $user_name = sanitize($_POST['user_name']);
            $password = sanitize($_POST['password']);
            
            $query = "UPDATE usuarios_productos SET user_name = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $user_name, $password, $id);
            
            if ($stmt->execute()) {
                header("Location: usuarios_productos.php?success=2");
                exit;
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            // Verificar si el usuario está ocupado
            if (isUserOccupied($id)) {
                header("Location: usuarios_productos.php?error=2");
                exit;
            }
            
            $query = "DELETE FROM usuarios_productos WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                header("Location: usuarios_productos.php?success=3");
                exit;
            }
            break;
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Gestión de Usuarios de Productos</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            switch ($_GET['success']) {
                case 1:
                    echo "Usuario creado exitosamente.";
                    break;
                case 2:
                    echo "Usuario actualizado exitosamente.";
                    break;
                case 3:
                    echo "Usuario eliminado exitosamente.";
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php
            switch ($_GET['error']) {
                case 1:
                    echo "No hay stock disponible para crear más usuarios.";
                    break;
                case 2:
                    echo "No se puede eliminar un usuario que está ocupado.";
                    break;
                case 4:
                    echo "La cantidad de stock debe ser mayor que cero.";
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="fas fa-plus"></i> Nuevo Usuario
    </button>

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
        <table class="table table-striped" id="usuariosTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Usuario</th>
                    <th>Cantidad</th>
                    <th>Costo Creación</th>
                    <th>Estado</th>
                    <th>Préstamos</th>
                    <th>Fecha Expiración</th>
                    <th>Fecha Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT up.*, p.nombre as producto_nombre, p.precio_compra,
                         DATEDIFF(up.fecha_expiracion, CURRENT_DATE) as dias_restantes 
                         FROM usuarios_productos up 
                         JOIN productos p ON up.producto_id = p.id 
                         ORDER BY up.id DESC";
                $result = $conn->query($query);
                
                while ($row = $result->fetch_assoc()):
                    $estado_class = '';
                    switch ($row['estado']) {
                        case 'LIBRE':
                            $estado_class = 'success';
                            break;
                        case 'OCUPADO':
                            $estado_class = 'warning';
                            break;
                        case 'VENCIDO':
                            $estado_class = 'danger';
                            break;
                    }
                    
                    $dias_restantes = $row['dias_restantes'];
                    $fecha_expiracion_class = '';
                    if ($dias_restantes !== null) {
                        if ($dias_restantes < 0) {
                            $fecha_expiracion_class = 'text-danger';
                        } elseif ($dias_restantes <= 30) {
                            $fecha_expiracion_class = 'text-warning';
                        }
                    }
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['producto_nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                    <td><?php echo $row['cantidad_inicial']; ?></td>
                    <td>$<?php echo formatMoney($row['costo_creacion']); ?></td>
                    <td><span class="badge bg-<?php echo $estado_class; ?>"><?php echo $row['estado']; ?></span></td>
                    <td><?php echo $row['prestamos_activos']; ?>/<?php echo $row['max_prestamos']; ?></td>
                    <td class="<?php echo $fecha_expiracion_class; ?>">
                        <?php echo formatDate($row['fecha_expiracion']); ?>
                        <?php if ($dias_restantes !== null): ?>
                            <br><small>(<?php echo $dias_restantes; ?> días)</small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo formatDate($row['created_at']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-info edit-btn" 
                                data-id="<?php echo $row['id']; ?>"
                                data-user-name="<?php echo htmlspecialchars($row['user_name']); ?>"
                                data-password="<?php echo htmlspecialchars($row['password']); ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($row['estado'] === 'LIBRE'): ?>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                        <a href="renovar_usuario_producto.php?id=<?php echo $row['id']; ?>" 
                           class="btn btn-sm btn-success">
                            <i class="fas fa-sync"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Crear Usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="usuarios_productos.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <select class="form-select" name="producto_id" id="producto_select" required>
                            <option value="">Seleccione un producto</option>
                            <?php
                            $query = "SELECT id, nombre, 
                                     (stock - (SELECT COUNT(*) FROM usuarios_productos WHERE producto_id = productos.id)) as stock_disponible,
                                     precio_compra 
                                     FROM productos 
                                     HAVING stock_disponible > 0";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id']; ?>" 
                                    data-stock="<?php echo $row['stock_disponible']; ?>"
                                    data-precio-compra="<?php echo $row['precio_compra']; ?>">
                                <?php echo htmlspecialchars($row['nombre']); ?> 
                                (Stock: <?php echo $row['stock_disponible']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" name="user_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="text" class="form-control" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cantidad de Stock a Descontar</label>
                        <input type="number" min="0.01" step="0.01" class="form-control" name="cantidad_stock" id="cantidad_stock" required>
                        <div class="form-text" id="stock_disponible"></div>
                        <div class="form-text" id="costo_total"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha de Expiración</label>
                        <input type="date" class="form-control" name="fecha_expiracion" 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Máximo de Préstamos Simultáneos</label>
                        <input type="number" min="1" max="10" class="form-control" 
                               name="max_prestamos" value="1" required>
                        <div class="form-text">
                            Número máximo de clientes que pueden usar esta cuenta simultáneamente.
                            Use 1 para cuentas de uso exclusivo.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="usuarios_productos.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" name="user_name" id="edit_user_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="text" class="form-control" name="password" id="edit_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTables
    var usuariosTable = $('#usuariosTable').DataTable({
        pageLength: 25,
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

    // Filtro de producto
    $('#filtroProducto').on('change', function() {
        var producto = $(this).val();
        usuariosTable.column(1).search(producto).draw();
    });
    
    // Manejar clic en botón editar
    $('.edit-btn').click(function() {
        const id = $(this).data('id');
        const userName = $(this).data('user-name');
        const password = $(this).data('password');
        
        $('#edit_id').val(id);
        $('#edit_user_name').val(userName);
        $('#edit_password').val(password);
        
        $('#editUserModal').modal('show');
    });
    
    // Manejar clic en botón eliminar
    $('.delete-btn').click(function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'usuarios_productos.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
    
    // Actualizar información de stock disponible al seleccionar producto
    $('#producto_select').change(function() {
        const stockDisponible = $('option:selected', this).data('stock');
        const precioCompra = $('option:selected', this).data('precio-compra');
        if (stockDisponible) {
            $('#stock_disponible').html(`Stock disponible: ${stockDisponible}`);
            $('#cantidad_stock').attr('max', stockDisponible);
            actualizarCostoTotal(precioCompra);
        } else {
            $('#stock_disponible').html('');
            $('#cantidad_stock').removeAttr('max');
            $('#costo_total').html('');
        }
    });
    
    // Actualizar costo total al cambiar cantidad
    $('#cantidad_stock').on('input', function() {
        const precioCompra = $('#producto_select option:selected').data('precio-compra');
        actualizarCostoTotal(precioCompra);
    });
    
    function actualizarCostoTotal(precioCompra) {
        const cantidad = parseFloat($('#cantidad_stock').val()) || 0;
        if (precioCompra && cantidad > 0) {
            const costoTotal = precioCompra * cantidad;
            $('#costo_total').html(`Costo total de creación: $${costoTotal.toFixed(2)}`);
        } else {
            $('#costo_total').html('');
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?> 