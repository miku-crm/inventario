<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Verificar si hay una acción POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $producto_id = intval($_POST['producto_id']);
            $proveedor_id = intval($_POST['proveedor_id']);
            $cantidad = intval($_POST['cantidad']);
            $precio_unitario = floatval($_POST['precio_unitario']);
            $factor_precio_id = !empty($_POST['factor_precio_id']) ? intval($_POST['factor_precio_id']) : null;
            $fecha = sanitize($_POST['fecha']);
            $notas = sanitize($_POST['notas']);
            
            // Calcular precio final
            $precio_final = $precio_unitario;
            if ($factor_precio_id) {
                $query = "SELECT porcentaje FROM factores_precio WHERE id = ? AND tipo = 'compra'";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $factor_precio_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $porcentaje = $row['porcentaje'];
                $precio_final = $precio_unitario * (1 + ($porcentaje / 100));
            }
            
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // Registrar entrada
                $query = "INSERT INTO entradas (producto_id, proveedor_id, cantidad, precio_unitario, 
                         factor_precio_id, precio_final, fecha, notas) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiidddss", $producto_id, $proveedor_id, $cantidad, $precio_unitario, 
                                $factor_precio_id, $precio_final, $fecha, $notas);
                $stmt->execute();
                
                // Actualizar stock del producto
                $query = "UPDATE productos SET stock = stock + ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $cantidad, $producto_id);
                $stmt->execute();
                
                $conn->commit();
                header("Location: entradas.php?success=1");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            // Obtener datos de la entrada
            $query = "SELECT producto_id, cantidad FROM entradas WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $entrada = $result->fetch_assoc();
            
            // Verificar stock disponible
            $query = "SELECT stock FROM productos WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $entrada['producto_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $producto = $result->fetch_assoc();
            
            if ($producto['stock'] < $entrada['cantidad']) {
                header("Location: entradas.php?error=1");
                exit;
            }
            
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // Eliminar entrada
                $query = "DELETE FROM entradas WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                // Actualizar stock del producto
                $query = "UPDATE productos SET stock = stock - ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $entrada['cantidad'], $entrada['producto_id']);
                $stmt->execute();
                
                $conn->commit();
                header("Location: entradas.php?success=2");
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
    <h2 class="mb-4">Gestión de Entradas</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            switch ($_GET['success']) {
                case 1:
                    echo "Entrada registrada exitosamente.";
                    break;
                case 2:
                    echo "Entrada eliminada exitosamente.";
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            No hay suficiente stock para eliminar esta entrada.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createEntradaModal">
        <i class="fas fa-plus"></i> Nueva Entrada
    </button>

    <div class="table-responsive">
        <table class="table table-striped" id="entradasTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th>Proveedor</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Precio Final</th>
                    <th>Notas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT e.*, p.nombre as producto_nombre, pr.nombre as proveedor_nombre 
                         FROM entradas e 
                         JOIN productos p ON e.producto_id = p.id 
                         JOIN proveedores pr ON e.proveedor_id = pr.id 
                         ORDER BY e.fecha DESC, e.id DESC";
                $result = $conn->query($query);
                
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo formatDate($row['fecha']); ?></td>
                    <td><?php echo htmlspecialchars($row['producto_nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['proveedor_nombre']); ?></td>
                    <td><?php echo $row['cantidad']; ?></td>
                    <td>$<?php echo formatMoney($row['precio_unitario']); ?></td>
                    <td>$<?php echo formatMoney($row['precio_final']); ?></td>
                    <td><?php echo htmlspecialchars($row['notas']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Crear Entrada -->
<div class="modal fade" id="createEntradaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Entrada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="entradas.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <select class="form-select" name="producto_id" required>
                            <option value="">Seleccione un producto</option>
                            <?php
                            $query = "SELECT id, nombre FROM productos ORDER BY nombre";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Proveedor</label>
                        <select class="form-select" name="proveedor_id" required>
                            <option value="">Seleccione un proveedor</option>
                            <?php
                            $query = "SELECT id, nombre FROM proveedores ORDER BY nombre";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number" class="form-control" name="cantidad" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Precio Unitario</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control" name="precio_unitario" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Factor de Precio</label>
                        <select class="form-select" name="factor_precio_id">
                            <option value="">Sin factor</option>
                            <?php
                            $query = "SELECT id, nombre, porcentaje FROM factores_precio WHERE tipo = 'compra' ORDER BY nombre";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['nombre']); ?> 
                                (<?php echo $row['porcentaje']; ?>%)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" class="form-control" name="fecha" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea class="form-control" name="notas"></textarea>
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

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#entradasTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        order: [[1, 'desc'], [0, 'desc']]
    });
    
    // Manejar clic en botón eliminar
    $('.delete-btn').click(function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer y reducirá el stock del producto",
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
                form.action = 'entradas.php';
                
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
});
</script>

<?php include 'includes/footer.php'; ?> 