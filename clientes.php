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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
                $nombre = sanitize($_POST['nombre']);
                $codigo_pais = sanitize($_POST['codigo_pais']);
                $telefono = $codigo_pais . sanitize($_POST['telefono']);
                $email = sanitize($_POST['email']);
                $direccion = sanitize($_POST['direccion']);
                
                $query = "INSERT INTO clientes (nombre, telefono, email, direccion) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssss", $nombre, $telefono, $email, $direccion);
                
                if ($stmt->execute()) {
                    header("Location: clientes.php?success=1");
                    exit;
                }
                throw new Exception("Error al crear el cliente");
                
            case 'update':
                $id = intval($_POST['id']);
                $nombre = sanitize($_POST['nombre']);
                $codigo_pais = sanitize($_POST['codigo_pais']);
                $telefono = $codigo_pais . sanitize($_POST['telefono']);
                $email = sanitize($_POST['email']);
                $direccion = sanitize($_POST['direccion']);
                
                $query = "UPDATE clientes SET nombre = ?, telefono = ?, email = ?, direccion = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssi", $nombre, $telefono, $email, $direccion, $id);
                
                if ($stmt->execute()) {
                    header("Location: clientes.php?success=2");
                    exit;
                }
                throw new Exception("Error al actualizar el cliente");
                
            case 'delete':
                $id = intval($_POST['id']);
                
                // Verificar si tiene préstamos
                $check_query = "SELECT COUNT(*) as count FROM prestamos WHERE cliente_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['count'] > 0) {
                    header("Location: clientes.php?error=1");
                    exit;
                }
                
                $query = "DELETE FROM clientes WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    header("Location: clientes.php?success=3");
                    exit;
                }
                throw new Exception("Error al eliminar el cliente");
        }
    } catch (Exception $e) {
        error_log("Error en clientes.php: " . $e->getMessage());
        header("Location: clientes.php?error=2");
        exit;
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="fas fa-users"></i> Gestión de Clientes
                </h2>
                <div class="text-muted mt-1">
                    Administración de clientes del sistema
                </div>
            </div>
            <?php if ($can_edit): ?>
            <div class="col-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClientModal">
                    <i class="fas fa-user-plus"></i> Nuevo Cliente
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php
        switch ($_GET['success']) {
            case 1:
                echo "Cliente creado exitosamente.";
                break;
            case 2:
                echo "Cliente actualizado exitosamente.";
                break;
            case 3:
                echo "Cliente eliminado exitosamente.";
                break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php
        switch ($_GET['error']) {
            case 1:
                echo "No se puede eliminar el cliente porque tiene préstamos asociados.";
                break;
            case 2:
                echo "Ha ocurrido un error al procesar la solicitud.";
                break;
            default:
                echo "Error desconocido.";
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="clientesTable">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user"></i> Nombre</th>
                            <th><i class="fas fa-phone"></i> Teléfono</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-handshake"></i> Préstamos</th>
                            <th><i class="fas fa-cogs"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $query = "SELECT c.id, c.nombre, c.telefono, c.email, c.direccion,
                                     (SELECT COUNT(*) FROM prestamos WHERE cliente_id = c.id) as total_prestamos
                                     FROM clientes c 
                                     ORDER BY c.id DESC";
                            $result = $conn->query($query);
                            
                            while ($row = $result->fetch_assoc()):
                                $telefono_original = $row['telefono'];
                                $telefono_formateado = formatPhoneNumber($telefono_original);
                                // Debug
                                error_log("Teléfono original: " . $telefono_original);
                                error_log("Teléfono formateado: " . $telefono_formateado);
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                    <?php echo htmlspecialchars($row['nombre']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-phone text-success me-2"></i>
                                    <?php 
                                    echo htmlspecialchars($telefono_formateado);
                                    // Debug en HTML como comentario
                                    echo "<!-- Debug: Original=" . htmlspecialchars($telefono_original) . " Formateado=" . htmlspecialchars($telefono_formateado) . " -->";
                                    ?>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-decoration-none">
                                    <i class="fas fa-envelope text-info me-1"></i>
                                    <?php echo htmlspecialchars($row['email']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $row['total_prestamos'] > 0 ? 'primary' : 'secondary'; ?>">
                                    <i class="fas fa-handshake me-1"></i>
                                    <?php echo number_format($row['total_prestamos']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-info view-btn" 
                                            title="Ver detalles"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                            data-telefono="<?php echo htmlspecialchars($row['telefono']); ?>"
                                            data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                            data-direccion="<?php echo htmlspecialchars($row['direccion']); ?>"
                                            data-total-prestamos="<?php echo $row['total_prestamos']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($can_edit): ?>
                                    <button class="btn btn-sm btn-warning edit-btn"
                                            title="Editar cliente"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>"
                                            data-telefono="<?php echo htmlspecialchars($row['telefono']); ?>"
                                            data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                            data-direccion="<?php echo htmlspecialchars($row['direccion']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($row['total_prestamos'] == 0): ?>
                                    <button class="btn btn-sm btn-danger delete-btn" 
                                            title="Eliminar cliente"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="6" class="text-center text-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    Error al cargar los clientes
                                  </td></tr>';
                            error_log($e->getMessage());
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
// Incluir los modales
include 'includes/modals/clientes/create.php';
include 'includes/modals/clientes/edit.php';
include 'includes/modals/clientes/view.php';
?>

<!-- Incluir el JavaScript específico de clientes -->
<script src="includes/js/clientes.js"></script>

<?php include 'includes/footer.php'; ?> 