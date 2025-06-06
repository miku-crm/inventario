<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Verificar si hay una acción POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nombre = sanitize($_POST['nombre']);
            $codigo_pais = sanitize($_POST['codigo_pais']);
            $telefono = $codigo_pais . sanitize($_POST['telefono']);
            $email = sanitize($_POST['email']);
            $direccion = sanitize($_POST['direccion']);
            
            $query = "INSERT INTO proveedores (nombre, telefono, email, direccion) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssss", $nombre, $telefono, $email, $direccion);
            
            if ($stmt->execute()) {
                header("Location: proveedores.php?success=1");
                exit;
            }
            break;
            
        case 'update':
            $id = intval($_POST['id']);
            $nombre = sanitize($_POST['nombre']);
            $codigo_pais = sanitize($_POST['codigo_pais']);
            $telefono = $codigo_pais . sanitize($_POST['telefono']);
            $email = sanitize($_POST['email']);
            $direccion = sanitize($_POST['direccion']);
            
            $query = "UPDATE proveedores SET nombre = ?, telefono = ?, email = ?, direccion = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssi", $nombre, $telefono, $email, $direccion, $id);
            
            if ($stmt->execute()) {
                header("Location: proveedores.php?success=2");
                exit;
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            // Verificar si tiene entradas asociadas
            $check_query = "SELECT COUNT(*) as count FROM entradas WHERE proveedor_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                header("Location: proveedores.php?error=1");
                exit;
            }
            
            $query = "DELETE FROM proveedores WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                header("Location: proveedores.php?success=3");
                exit;
            }
            break;
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <h2 class="mb-4">Gestión de Proveedores</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            switch ($_GET['success']) {
                case 1:
                    echo "Proveedor creado exitosamente.";
                    break;
                case 2:
                    echo "Proveedor actualizado exitosamente.";
                    break;
                case 3:
                    echo "Proveedor eliminado exitosamente.";
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            No se puede eliminar el proveedor porque tiene entradas asociadas.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createProveedorModal">
        <i class="fas fa-plus"></i> Nuevo Proveedor
    </button>

    <div class="table-responsive">
        <table class="table table-striped" id="proveedoresTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Entradas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT p.*, 
                         (SELECT COUNT(*) FROM entradas WHERE proveedor_id = p.id) as entradas_count,
                         (SELECT fecha FROM entradas WHERE proveedor_id = p.id ORDER BY fecha DESC LIMIT 1) as ultima_compra 
                         FROM proveedores p 
                         ORDER BY p.id DESC";
                $result = $conn->query($query);
                
                while ($row = $result->fetch_assoc()):
                    $ultima_compra = $row['ultima_compra'] ? date('d/m/Y', strtotime($row['ultima_compra'])) : null;
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo htmlspecialchars(formatPhoneNumber($row['telefono'])); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo $row['entradas_count']; ?></td>
                    <td>
                        <button class="btn btn-sm btn-info view-btn" 
                                data-id="<?php echo $row['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                data-telefono="<?php echo htmlspecialchars($row['telefono']); ?>"
                                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                data-direccion="<?php echo htmlspecialchars($row['direccion']); ?>"
                                data-entradas="<?php echo $row['entradas_count']; ?>"
                                data-ultima-compra="<?php echo $ultima_compra; ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning edit-btn"
                                data-id="<?php echo $row['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                data-telefono="<?php echo htmlspecialchars($row['telefono']); ?>"
                                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                data-direccion="<?php echo htmlspecialchars($row['direccion']); ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($row['entradas_count'] == 0): ?>
                        <button class="btn btn-sm btn-danger delete-btn" 
                                data-id="<?php echo $row['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>">
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

<?php 
// Incluir los modales
include 'includes/modals/proveedores/create.php';
include 'includes/modals/proveedores/edit.php';
include 'includes/modals/proveedores/view.php';
?>

<!-- Incluir el JavaScript específico de proveedores -->
<script src="includes/js/proveedores.js"></script>

<?php include 'includes/footer.php'; ?> 