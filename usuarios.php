<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'auth/auth_check.php';

// Verificar que solo el admin pueda acceder
if (!checkPermission('admin')) {
    header("Location: index.php");
    exit;
}

// Verificar si hay una acción POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nombre_completo = sanitize($_POST['nombre_completo']);
            $email = sanitize($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $rol = sanitize($_POST['rol']);
            
            $query = "INSERT INTO usuarios_sistema (nombre_completo, email, password, rol) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssss", $nombre_completo, $email, $password, $rol);
            
            if ($stmt->execute()) {
                header("Location: usuarios.php?success=1");
                exit;
            }
            break;
            
        case 'update':
            $id = intval($_POST['id']);
            $nombre_completo = sanitize($_POST['nombre_completo']);
            $email = sanitize($_POST['email']);
            $rol = sanitize($_POST['rol']);
            
            $query = "UPDATE usuarios_sistema SET nombre_completo = ?, email = ?, rol = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $nombre_completo, $email, $rol, $id);
            
            if ($stmt->execute()) {
                header("Location: usuarios.php?success=2");
                exit;
            }
            break;
            
        case 'delete':
            $id = intval($_POST['id']);
            
            // No permitir eliminar el propio usuario
            if ($id === $_SESSION['user_id']) {
                header("Location: usuarios.php?error=2");
                exit;
            }
            
            $query = "DELETE FROM usuarios_sistema WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                header("Location: usuarios.php?success=3");
                exit;
            }
            break;
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="page-header mb-4">
        <h2 class="mb-3">Gestión de Usuarios</h2>
        <div class="mb-3">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fas fa-plus"></i> Nuevo Usuario
            </button>
        </div>
    </div>

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
                echo "Error al procesar la operación.";
                break;
            case 2:
                echo "No puedes eliminar tu propio usuario.";
                break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-striped" id="usuariosTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM usuarios_sistema ORDER BY id DESC";
                    $result = $conn->query($query);
                    
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><span class="badge bg-<?php echo $row['rol'] === 'admin' ? 'danger' : ($row['rol'] === 'operador' ? 'warning' : 'info'); ?>">
                            <?php echo ucfirst($row['rol']); ?>
                        </span></td>
                        <td><?php echo formatDate($row['fecha_creacion']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning edit-btn"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($row['nombre_completo']); ?>"
                                    data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                    data-rol="<?php echo $row['rol']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($row['id'] !== $_SESSION['user_id']): ?>
                            <button class="btn btn-sm btn-danger delete-btn" 
                                    data-id="<?php echo $row['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($row['nombre_completo']); ?>">
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
</div>

<!-- Modal para Crear Usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="usuarios.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" name="nombre_completo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select class="form-select" name="rol" required>
                            <option value="">Seleccione un rol</option>
                            <option value="admin">Administrador</option>
                            <option value="operador">Operador</option>
                            <option value="consulta">Consulta</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
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
            <form action="usuarios.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" name="nombre_completo" id="edit_nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select class="form-select" name="rol" id="edit_rol" required>
                            <option value="">Seleccione un rol</option>
                            <option value="admin">Administrador</option>
                            <option value="operador">Operador</option>
                            <option value="consulta">Consulta</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#usuariosTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        order: [[0, 'desc']]
    });

    // Manejar clic en botón editar
    $('.edit-btn').click(function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const email = $(this).data('email');
        const rol = $(this).data('rol');
        
        $('#edit_id').val(id);
        $('#edit_nombre').val(nombre);
        $('#edit_email').val(email);
        $('#edit_rol').val(rol);
        
        $('#editUserModal').modal('show');
    });

    // Manejar clic en botón eliminar
    $('.delete-btn').click(function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas eliminar al usuario "${nombre}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'usuarios.php';
                
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