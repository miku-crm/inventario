<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Verificar si hay una acción POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nombre = sanitize($_POST['nombre']);
            $codigo = sanitize($_POST['codigo']);
            $descripcion = sanitize($_POST['descripcion']);
            $precio_compra = floatval($_POST['precio_compra']);
            $precio_venta = floatval($_POST['precio_venta']);
            $stock = intval($_POST['stock']);
            
            $query = "INSERT INTO productos (nombre, codigo, descripcion, precio_compra, precio_venta, stock) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssddi", $nombre, $codigo, $descripcion, $precio_compra, $precio_venta, $stock);
            
            if ($stmt->execute()) {
                header("Location: productos.php?success=1");
                exit;
            }
            break;
            
        case 'update':
            $id = intval($_POST['id']);
            $nombre = sanitize($_POST['nombre']);
            $codigo = sanitize($_POST['codigo']);
            $descripcion = sanitize($_POST['descripcion']);
            $precio_compra = floatval($_POST['precio_compra']);
            $precio_venta = floatval($_POST['precio_venta']);
            
            $query = "UPDATE productos SET nombre = ?, codigo = ?, descripcion = ?, 
                     precio_compra = ?, precio_venta = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssddi", $nombre, $codigo, $descripcion, $precio_compra, $precio_venta, $id);
            
            if ($stmt->execute()) {
                header("Location: productos.php?success=2");
                exit;
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            // Verificar si tiene entradas o usuarios
            $check_query = "SELECT 
                          (SELECT COUNT(*) FROM entradas WHERE producto_id = ?) as total_entradas,
                          (SELECT COUNT(*) FROM usuarios_productos WHERE producto_id = ?) as total_usuarios";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("ii", $id, $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['total_entradas'] > 0 || $row['total_usuarios'] > 0) {
                header("Location: productos.php?error=1");
                exit;
            }
            
            $query = "DELETE FROM productos WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                header("Location: productos.php?success=3");
                exit;
            }
            break;
            
        case 'create_users':
            $producto_id = intval($_POST['producto_id']);
            $cantidad = intval($_POST['cantidad']);
            $prefijo = sanitize($_POST['prefijo']);
            $inicio = intval($_POST['inicio']);
            
            // Verificar stock disponible
            $query = "SELECT stock FROM productos WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $producto_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['stock'] < $cantidad) {
                header("Location: productos.php?error=2");
                exit;
            }
            
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // Crear usuarios
                for ($i = 0; $i < $cantidad; $i++) {
                    $user_name = $prefijo . str_pad($inicio + $i, 4, '0', STR_PAD_LEFT);
                    
                    $query = "INSERT INTO usuarios_productos (producto_id, user_name, estado) VALUES (?, ?, 'LIBRE')";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("is", $producto_id, $user_name);
                    $stmt->execute();
                }
                
                // Actualizar stock del producto
                $query = "UPDATE productos SET stock = stock - ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $cantidad, $producto_id);
                $stmt->execute();
                
                $conn->commit();
                header("Location: productos.php?success=4");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Gestión de Productos</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            switch ($_GET['success']) {
                case 1:
                    echo "Producto creado exitosamente.";
                    break;
                case 2:
                    echo "Producto actualizado exitosamente.";
                    break;
                case 3:
                    echo "Producto eliminado exitosamente.";
                    break;
                case 4:
                    echo "Usuarios creados exitosamente.";
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
                    echo "No se puede eliminar el producto porque tiene entradas o usuarios asociados.";
                    break;
                case 2:
                    echo "No hay suficiente stock para crear los usuarios.";
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createProductModal">
        <i class="fas fa-plus"></i> Nuevo Producto
    </button>

    <div class="table-responsive">
        <table class="table table-striped" id="productosTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio Compra</th>
                    <th>Precio Venta</th>
                    <th>Stock</th>
                    <th>Usuarios</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT p.*, 
                         (SELECT COUNT(*) FROM usuarios_productos up WHERE up.producto_id = p.id) as total_usuarios,
                         (SELECT COUNT(*) FROM usuarios_productos up WHERE up.producto_id = p.id AND up.estado = 'LIBRE') as usuarios_libres
                         FROM productos p 
                         ORDER BY p.id DESC";
                $result = $conn->query($query);
                
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                    <td>$<?php echo formatMoney($row['precio_compra']); ?></td>
                    <td>$<?php echo formatMoney($row['precio_venta']); ?></td>
                    <td><?php echo $row['stock']; ?></td>
                    <td>
                        Total: <?php echo $row['total_usuarios']; ?><br>
                        Libres: <?php echo $row['usuarios_libres']; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-info edit-btn" 
                                data-id="<?php echo $row['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                data-codigo="<?php echo htmlspecialchars($row['codigo']); ?>"
                                data-descripcion="<?php echo htmlspecialchars($row['descripcion']); ?>"
                                data-precio-compra="<?php echo $row['precio_compra']; ?>"
                                data-precio-venta="<?php echo $row['precio_venta']; ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($row['total_usuarios'] == 0): ?>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($row['stock'] > 0): ?>
                        <button class="btn btn-sm btn-success create-users-btn" 
                                data-id="<?php echo $row['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                data-stock="<?php echo $row['stock']; ?>">
                            <i class="fas fa-user-plus"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Crear Producto -->
<div class="modal fade" id="createProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="productos.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Código</label>
                        <input type="text" class="form-control" name="codigo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Precio de Compra</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control" name="precio_compra" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Precio de Venta</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control" name="precio_venta" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Stock Inicial</label>
                        <input type="number" class="form-control" name="stock" value="0" required>
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

<!-- Modal para Editar Producto -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="productos.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Código</label>
                        <input type="text" class="form-control" name="codigo" id="edit_codigo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="edit_descripcion"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Precio de Compra</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control" name="precio_compra" 
                                   id="edit_precio_compra" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Precio de Venta</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control" name="precio_venta" 
                                   id="edit_precio_venta" required>
                        </div>
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

<!-- Modal para Crear Usuarios -->
<div class="modal fade" id="createUsersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Usuarios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="productos.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_users">
                    <input type="hidden" name="producto_id" id="create_users_producto_id">
                    
                    <p>Producto: <strong id="create_users_producto_nombre"></strong></p>
                    <p>Stock disponible: <strong id="create_users_stock"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Cantidad de Usuarios</label>
                        <input type="number" class="form-control" name="cantidad" id="create_users_cantidad" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Prefijo</label>
                        <input type="text" class="form-control" name="prefijo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Número Inicial</label>
                        <input type="number" class="form-control" name="inicio" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuarios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#productosTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });
    
    // Manejar clic en botón editar
    $('.edit-btn').click(function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const codigo = $(this).data('codigo');
        const descripcion = $(this).data('descripcion');
        const precioCompra = $(this).data('precio-compra');
        const precioVenta = $(this).data('precio-venta');
        
        $('#edit_id').val(id);
        $('#edit_nombre').val(nombre);
        $('#edit_codigo').val(codigo);
        $('#edit_descripcion').val(descripcion);
        $('#edit_precio_compra').val(precioCompra);
        $('#edit_precio_venta').val(precioVenta);
        
        $('#editProductModal').modal('show');
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
                form.action = 'productos.php';
                
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
    
    // Manejar clic en botón crear usuarios
    $('.create-users-btn').click(function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const stock = $(this).data('stock');
        
        $('#create_users_producto_id').val(id);
        $('#create_users_producto_nombre').text(nombre);
        $('#create_users_stock').text(stock);
        $('#create_users_cantidad').attr('max', stock);
        
        $('#createUsersModal').modal('show');
    });
    
    // Validar cantidad de usuarios
    $('#create_users_cantidad').on('input', function() {
        const stock = parseInt($('#create_users_stock').text());
        const cantidad = parseInt($(this).val());
        
        if (cantidad > stock) {
            $(this).val(stock);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 