<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Verificar si hay una acción POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nombre = sanitize($_POST['nombre']);
            $porcentaje = floatval($_POST['porcentaje']);
            $tipo = sanitize($_POST['tipo']);
            $descripcion = sanitize($_POST['descripcion']);
            
            $query = "INSERT INTO factores_precio (nombre, porcentaje, tipo, descripcion) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sdss", $nombre, $porcentaje, $tipo, $descripcion);
            
            if ($stmt->execute()) {
                header("Location: factores_precio.php?success=1");
                exit;
            }
            break;
            
        case 'update':
            $id = intval($_POST['id']);
            $nombre = sanitize($_POST['nombre']);
            $porcentaje = floatval($_POST['porcentaje']);
            $tipo = sanitize($_POST['tipo']);
            $descripcion = sanitize($_POST['descripcion']);
            
            $query = "UPDATE factores_precio SET nombre = ?, porcentaje = ?, tipo = ?, descripcion = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sdssi", $nombre, $porcentaje, $tipo, $descripcion, $id);
            
            if ($stmt->execute()) {
                header("Location: factores_precio.php?success=2");
                exit;
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            // Verificar si está en uso
            $check_query = "SELECT COUNT(*) as count FROM prestamos WHERE factor_precio_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                header("Location: factores_precio.php?error=1");
                exit;
            }
            
            $query = "DELETE FROM factores_precio WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                header("Location: factores_precio.php?success=3");
                exit;
            }
            break;
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Gestión de Factores de Precio</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            switch ($_GET['success']) {
                case 1:
                    echo "Factor de precio creado exitosamente.";
                    break;
                case 2:
                    echo "Factor de precio actualizado exitosamente.";
                    break;
                case 3:
                    echo "Factor de precio eliminado exitosamente.";
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            No se puede eliminar el factor de precio porque está siendo utilizado.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createFactorModal">
        <i class="fas fa-plus"></i> Nuevo Factor de Precio
    </button>

    <div class="table-responsive">
        <table class="table table-striped" id="factoresTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Porcentaje</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>En Uso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT f.*, 
                         (SELECT COUNT(*) FROM prestamos p WHERE p.factor_precio_id = f.id) as total_usos 
                         FROM factores_precio f 
                         ORDER BY f.tipo, f.id DESC";
                $result = $conn->query($query);
                
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo $row['porcentaje']; ?>%</td>
                    <td><?php echo ucfirst($row['tipo']); ?></td>
                    <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                    <td><?php echo $row['total_usos']; ?></td>
                    <td>
                        <button class="btn btn-sm btn-info edit-btn" 
                                data-id="<?php echo $row['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                data-porcentaje="<?php echo $row['porcentaje']; ?>"
                                data-tipo="<?php echo $row['tipo']; ?>"
                                data-descripcion="<?php echo htmlspecialchars($row['descripcion']); ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($row['total_usos'] == 0): ?>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Crear Factor -->
<div class="modal fade" id="createFactorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Factor de Precio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="factores_precio.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Porcentaje</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control" name="porcentaje" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo" required>
                            <option value="venta">Venta</option>
                            <option value="compra">Compra</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion"></textarea>
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

<!-- Modal para Editar Factor -->
<div class="modal fade" id="editFactorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Factor de Precio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="factores_precio.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Porcentaje</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control" name="porcentaje" id="edit_porcentaje" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo" id="edit_tipo" required>
                            <option value="venta">Venta</option>
                            <option value="compra">Compra</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="edit_descripcion"></textarea>
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
    // Inicializar DataTable
    $('#factoresTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });
    
    // Manejar clic en botón editar
    $('.edit-btn').click(function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const porcentaje = $(this).data('porcentaje');
        const tipo = $(this).data('tipo');
        const descripcion = $(this).data('descripcion');
        
        $('#edit_id').val(id);
        $('#edit_nombre').val(nombre);
        $('#edit_porcentaje').val(porcentaje);
        $('#edit_tipo').val(tipo);
        $('#edit_descripcion').val(descripcion);
        
        $('#editFactorModal').modal('show');
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
                form.action = 'factores_precio.php';
                
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