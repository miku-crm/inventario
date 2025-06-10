<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'auth/auth_check.php';

// Verificar permisos para acciones específicas
$can_edit = checkPermission('operador');
$can_view = checkPermission('consulta');

if (!$can_view) {
    header("Location: index.php");
    exit;
}

// Verificar préstamos vencidos
checkExpiredLoans();

// Verificar usuarios expirados
checkExpiredUsers();

// Verificar si hay una acción POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Verificar permisos para acciones que modifican datos
    if (!$can_edit) {
        header("Location: prestamos.php?error=unauthorized");
        exit;
    }
    
    switch ($action) {
        case 'create':
            $usuario_producto_id = intval($_POST['usuario_producto_id']);
            $cliente_id = intval($_POST['cliente_id']);
            $precio_unitario = floatval($_POST['precio_unitario']);
            $factor_precio_id = !empty($_POST['factor_precio_id']) ? intval($_POST['factor_precio_id']) : null;
            $fecha_inicio = sanitize($_POST['fecha_inicio']);
            $fecha_fin = sanitize($_POST['fecha_fin']);
            $cantidad_dispositivos = intval($_POST['cantidad_dispositivos']);
            
            // Verificar que cantidad_dispositivos sea válida
            if ($cantidad_dispositivos < 1) {
                header("Location: prestamos.php?error=4");
                exit;
            }
            
            // Verificar si el usuario está disponible para más préstamos
            $query = "SELECT up.*, 
                     (SELECT COALESCE(SUM(cantidad_dispositivos), 0) FROM prestamos 
                      WHERE usuario_producto_id = up.id 
                      AND estado = 'ACTIVO') as dispositivos_activos
                     FROM usuarios_productos up 
                     WHERE up.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $usuario_producto_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuario = $result->fetch_assoc();
            
            if ($usuario['dispositivos_activos'] + $cantidad_dispositivos > $usuario['max_prestamos']) {
                header("Location: prestamos.php?error=1");
                exit;
            }
            
            // Verificar si el cliente ya tiene un préstamo activo de este usuario
            $query = "SELECT COUNT(*) as tiene_prestamo 
                     FROM prestamos 
                     WHERE usuario_producto_id = ? 
                     AND cliente_id = ? 
                     AND estado = 'ACTIVO'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $usuario_producto_id, $cliente_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['tiene_prestamo'] > 0) {
                header("Location: prestamos.php?error=3");
                exit;
            }
            
            // Calcular precio final
            $precio_final = applyPriceFactor($precio_unitario, $factor_precio_id);
            
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // Crear préstamo
                $query = "INSERT INTO prestamos (usuario_producto_id, cliente_id, precio_unitario, 
                         factor_precio_id, precio_final, cantidad_dispositivos, fecha_inicio, fecha_fin) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iidddiss", $usuario_producto_id, $cliente_id, $precio_unitario, 
                                $factor_precio_id, $precio_final, $cantidad_dispositivos, $fecha_inicio, $fecha_fin);
                $stmt->execute();
                
                // Actualizar contador de préstamos activos
                $query = "UPDATE usuarios_productos 
                         SET prestamos_activos = prestamos_activos + ?,
                             estado = CASE 
                                WHEN prestamos_activos + ? >= max_prestamos THEN 'OCUPADO'
                                ELSE 'LIBRE'
                             END
                         WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iii", $cantidad_dispositivos, $cantidad_dispositivos, $usuario_producto_id);
                $stmt->execute();
                
                $conn->commit();
                header("Location: prestamos.php?success=1");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        case 'cancel':
            $id = intval($_POST['id']);
            $precio_negociado = floatval($_POST['precio_negociado']);
            
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // Obtener información del préstamo y usuario
                $query = "SELECT p.*, up.id as usuario_producto_id, up.max_prestamos,
                         (SELECT COUNT(*) FROM renovaciones WHERE prestamo_id = p.id) as tiene_renovaciones,
                         (SELECT COALESCE(SUM(cantidad_dispositivos), 0) 
                          FROM prestamos 
                          WHERE usuario_producto_id = up.id 
                          AND estado = 'ACTIVO'
                          AND id != p.id) as otros_dispositivos_activos
                         FROM prestamos p
                         JOIN usuarios_productos up ON p.usuario_producto_id = up.id
                         WHERE p.id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $prestamo = $result->fetch_assoc();
                
                if (!$prestamo) {
                    throw new Exception("Préstamo no encontrado");
                }

                // Actualizar precio_final y estado del préstamo
                $query = "UPDATE prestamos 
                         SET precio_final = ?,
                             estado = 'CANCELADO' 
                         WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("di", $precio_negociado, $id);
                $stmt->execute();
                
                // Si tiene renovaciones, establecer sus precios a 0
                if ($prestamo['tiene_renovaciones'] > 0) {
                    $query = "UPDATE renovaciones 
                             SET precio_nuevo = 0 
                             WHERE prestamo_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                }
                
                // Actualizar prestamos_activos y estado del usuario
                $query = "UPDATE usuarios_productos 
                         SET prestamos_activos = prestamos_activos - ?,
                             estado = CASE 
                                WHEN ? = 0 THEN 'LIBRE'
                                ELSE 'OCUPADO'
                             END
                         WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iii", 
                    $prestamo['cantidad_dispositivos'],
                    $prestamo['otros_dispositivos_activos'],
                    $prestamo['usuario_producto_id']
                );
                $stmt->execute();
                
                // Registrar en historial
                $query = "INSERT INTO historial_usuarios 
                         (usuario_producto_id, estado_anterior, estado_nuevo, motivo) 
                         VALUES (?, 
                                (SELECT estado FROM usuarios_productos WHERE id = ?),
                                CASE WHEN ? = 0 THEN 'LIBRE' ELSE 'OCUPADO' END,
                                'Préstamo cancelado con precio negociado')";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iii", 
                    $prestamo['usuario_producto_id'],
                    $prestamo['usuario_producto_id'],
                    $prestamo['otros_dispositivos_activos']
                );
                $stmt->execute();
                
                $conn->commit();
                header("Location: prestamos.php?success=2");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Error al cancelar préstamo: " . $e->getMessage());
                header("Location: prestamos.php?error=cancel");
                exit;
            }
            break;
            
        case 'liberar':
            $id = intval($_POST['id']);
            
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // Obtener usuario_producto_id y cantidad_dispositivos del préstamo
                $query = "SELECT usuario_producto_id, cantidad_dispositivos FROM prestamos WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $prestamo = $result->fetch_assoc();
                $usuario_producto_id = $prestamo['usuario_producto_id'];
                $cantidad_dispositivos = $prestamo['cantidad_dispositivos'];
                
                // Actualizar estado del préstamo a LIBERADO
                $query = "UPDATE prestamos SET estado = 'LIBERADO' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                // Actualizar estado del usuario y contador de préstamos considerando la fecha de expiración
                $query = "UPDATE usuarios_productos 
                        SET prestamos_activos = prestamos_activos - ?,
                            estado = CASE 
                                WHEN fecha_expiracion < CURRENT_DATE THEN 'EXPIRADO'
                                WHEN prestamos_activos - ? = 0 THEN 'LIBRE'
                                WHEN prestamos_activos - ? < max_prestamos THEN 'LIBRE'
                                ELSE estado
                            END
                        WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiii", $cantidad_dispositivos, $cantidad_dispositivos, $cantidad_dispositivos, $usuario_producto_id);
                $stmt->execute();
                
                $conn->commit();
                header("Location: prestamos.php?success=4");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        case 'update':
            $id = intval($_POST['id']);
            $precio_unitario = floatval($_POST['precio_unitario']);
            $factor_precio_id = !empty($_POST['factor_precio_id']) ? intval($_POST['factor_precio_id']) : null;
            $fecha_inicio = sanitize($_POST['fecha_inicio']);
            $fecha_fin = sanitize($_POST['fecha_fin']);
            
            // Calcular precio final
            $precio_final = applyPriceFactor($precio_unitario, $factor_precio_id);
            
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // Actualizar préstamo
                $query = "UPDATE prestamos 
                         SET precio_unitario = ?, 
                             factor_precio_id = ?,
                             precio_final = ?,
                             fecha_inicio = ?,
                             fecha_fin = ?
                         WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("diddsi", $precio_unitario, $factor_precio_id, $precio_final, 
                                $fecha_inicio, $fecha_fin, $id);
                $stmt->execute();
                
                $conn->commit();
                header("Location: prestamos.php?success=5");
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
    <h2 class="mb-4">Gestión de Préstamos</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            switch ($_GET['success']) {
                case 1:
                    echo "Préstamo creado exitosamente.";
                    break;
                case 2:
                    echo "Préstamo cancelado exitosamente.";
                    break;
                case 4:
                    echo "Cuenta liberada exitosamente.";
                    break;
                case 5:
                    echo "Préstamo actualizado exitosamente.";
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
                    echo "El usuario seleccionado no está disponible para más préstamos.";
                    break;
                case 2:
                    echo "El préstamo no existe o ha excedido el período permitido para renovación (30 días).";
                    break;
                case 3:
                    echo "El cliente ya tiene un préstamo activo de este usuario.";
                    break;
                case 4:
                    echo "La cantidad de dispositivos debe ser mayor a 0.";
                    break;
                case 'unauthorized':
                    echo "No tienes permiso para realizar esta acción.";
                    break;
                case 'cancel':
                    echo "Error al cancelar el préstamo. Por favor, inténtelo de nuevo.";
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($can_edit): ?>
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createLoanModal">
        <i class="fas fa-plus"></i> Nuevo Préstamo
    </button>
    <?php endif; ?>

    <!-- Filtro de productos -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <label class="input-group-text" for="filtroProducto">
                    <i class="fas fa-filter"></i> Filtrar por Producto
                </label>
                <select class="form-select" id="filtroProducto">
                    <option value="">Todos los productos</option>
                    <?php
                    $query = "SELECT DISTINCT pr.id, pr.nombre 
                             FROM prestamos p 
                             JOIN usuarios_productos up ON p.usuario_producto_id = up.id 
                             JOIN productos pr ON up.producto_id = pr.id 
                             WHERE p.estado IN ('ACTIVO', 'VENCIDO')
                             ORDER BY pr.nombre";
                    $result = $conn->query($query);
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <option value="<?php echo htmlspecialchars($row['nombre']); ?>">
                        <?php echo htmlspecialchars($row['nombre']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped" id="prestamosTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Usuario</th>
                    <th>Teléfono</th>
                    <th>Precio Final</th>
                    <th>Fecha Fin</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT p.*, 
                         c.nombre as cliente_nombre,
                         c.telefono,
                         up.user_name,
                         up.password,
                         up.estado as estado_usuario,
                         pr.nombre as producto_nombre,
                         DATEDIFF(p.fecha_fin, CURRENT_DATE) as dias_vencido,
                         DATEDIFF(up.fecha_expiracion, CURRENT_DATE) as dias_expiracion
                         FROM prestamos p 
                         INNER JOIN usuarios_productos up ON p.usuario_producto_id = up.id 
                         INNER JOIN productos pr ON up.producto_id = pr.id 
                         INNER JOIN clientes c ON p.cliente_id = c.id 
                         WHERE p.estado IN ('ACTIVO', 'VENCIDO')
                         ORDER BY p.id DESC";
                $result = $conn->query($query);
                
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['producto_nombre']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($row['user_name']); ?>
                        <br>
                        <small>
                            <?php 
                            $dias_exp = $row['dias_expiracion'];
                            if ($dias_exp < 0) {
                                echo '<span class="text-danger">Expirado hace ' . abs($dias_exp) . ' días</span>';
                            } else {
                                echo '<span class="text-' . ($dias_exp <= 7 ? 'warning' : 'success') . '">Expira en ' . $dias_exp . ' días</span>';
                            }
                            ?>
                        </small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars(formatPhoneNumber($row['telefono'])); ?>
                        <br>
                        <small>
                            <?php 
                            $dias_restantes = $row['dias_vencido'];
                            $es_vencido = $row['estado'] === 'VENCIDO';
                            
                            if ($es_vencido) {
                                echo '<span class="text-danger">Vencido hace ' . abs($dias_restantes) . ' días</span>';
                            } else {
                                echo '<span class="text-' . ($dias_restantes <= 7 ? 'warning' : 'success') . '">Vence en ' . $dias_restantes . ' días</span>';
                            }
                            ?>
                        </small>
                    </td>
                    <td>$<?php echo formatMoney($row['precio_final']); ?></td>
                    <td><?php echo formatDate($row['fecha_fin']); ?></td>
                    <td class="text-center">
                        <div class="btn-group">
                            <?php if ($can_edit): ?>
                            <button type="button" 
                                    class="btn btn-info btn-sm view-btn" 
                                    data-id="<?php echo $row['id']; ?>"
                                    data-cliente="<?php echo htmlspecialchars($row['cliente_nombre']); ?>"
                                    data-telefono="<?php echo htmlspecialchars($row['telefono']); ?>"
                                    data-producto="<?php echo htmlspecialchars($row['producto_nombre']); ?>"
                                    data-usuario="<?php echo htmlspecialchars($row['user_name']); ?>"
                                    data-password="<?php echo htmlspecialchars($row['password']); ?>"
                                    data-precio="<?php echo $row['precio_final']; ?>"
                                    data-dispositivos="<?php echo $row['cantidad_dispositivos']; ?>"
                                    data-fecha-inicio="<?php echo $row['fecha_inicio']; ?>"
                                    data-fecha-fin="<?php echo $row['fecha_fin']; ?>"
                                    data-estado="<?php echo $row['estado']; ?>">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <?php endif; ?>

                            <?php if ($can_edit && $row['estado'] == 'ACTIVO'): ?>
                            <button type="button" 
                                    class="btn btn-warning btn-sm edit-btn" 
                                    data-id="<?php echo $row['id']; ?>"
                                    data-precio="<?php echo $row['precio_unitario']; ?>"
                                    data-factor="<?php echo $row['factor_precio_id']; ?>"
                                    data-inicio="<?php echo $row['fecha_inicio']; ?>"
                                    data-fin="<?php echo $row['fecha_fin']; ?>">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($can_edit && $row['estado'] == 'ACTIVO'): ?>
                            <a href="renovaciones.php?prestamo_id=<?php echo $row['id']; ?>" 
                               class="btn btn-success btn-sm">
                                <i class="fas fa-sync"></i> Renovar
                            </a>
                            <?php endif; ?>
                            
                            <!-- boton de prueba -->
                            <?php if ($can_edit && $row['estado'] == 'VENCIDO'): ?>
                                <form method="POST" action="prestamos.php" style="display: inline;">
                                    <input type="hidden" name="action" value="liberar">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-unlock"></i> Liberar
                                    </button>
                                </form>
                            <?php endif; ?>
                            <!-- fin boton de prueba -->

                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
// Incluir los modales
include 'includes/modals/prestamos/view.php';
include 'includes/modals/prestamos/create.php';
include 'includes/modals/prestamos/edit.php';
?>

<!-- Scripts específicos de préstamos -->
<script src="includes/js/prestamos/view.js"></script>
<script src="includes/js/prestamos/create.js"></script>
<script src="includes/js/prestamos/edit.js"></script>
<script>
$(document).ready(function() {
    // Inicializar tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Inicializar datatable
    $('#prestamosTable').DataTable({
        language: {
            url: 'assets/js/dataTables.spanish.json'
        },
        order: [[0, 'desc']]
    });
    
    // Función para actualizar precio final
    function actualizarPrecioFinal() {
        const precioBase = parseFloat($('#precio_unitario').val()) || 0;
        const factorPorcentaje = parseFloat($('#factor_precio_id option:selected').data('porcentaje')) || 0;
        const precioFinal = precioBase * (1 + (factorPorcentaje / 100));
        $('#precio_final').val(precioFinal.toFixed(2));
    }
    
    // Event listeners para actualizar precio
    $('#precio_unitario, #factor_precio_id').on('change input', actualizarPrecioFinal);
    
    // Validación de cantidad de dispositivos
    $('#cantidad_dispositivos').on('input', function() {
        const cantidad = parseInt($(this).val());
        if (isNaN(cantidad) || cantidad < 1) {
            $(this).addClass('is-invalid');
            $('#dispositivos-feedback').text('La cantidad debe ser mayor a 0');
            $('#submit-btn').prop('disabled', true);
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $('#dispositivos-feedback').text('');
            $('#submit-btn').prop('disabled', false);
        }
    });
    
    // Validación de fechas
    $('#fecha_fin').on('change', function() {
        const fechaInicio = new Date($('#fecha_inicio').val());
        const fechaFin = new Date($(this).val());
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        
        if (fechaFin <= fechaInicio) {
            $(this).addClass('is-invalid');
            $('#fecha-fin-feedback').text('La fecha fin debe ser posterior a la fecha de inicio');
            $('#submit-btn').prop('disabled', true);
        } else if (fechaFin < hoy) {
            $(this).addClass('is-invalid');
            $('#fecha-fin-feedback').text('La fecha fin no puede ser en el pasado');
            $('#submit-btn').prop('disabled', true);
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $('#fecha-fin-feedback').text('');
            $('#submit-btn').prop('disabled', false);
        }
    });
    
    // Validación de fecha inicio
    $('#fecha_inicio').on('change', function() {
        const fechaInicio = new Date($(this).val());
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        
        if (fechaInicio < hoy) {
            $(this).addClass('is-invalid');
            $('#fecha-inicio-feedback').text('La fecha inicio no puede ser en el pasado');
            $('#submit-btn').prop('disabled', true);
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $('#fecha-inicio-feedback').text('');
            $('#submit-btn').prop('disabled', false);
            $('#fecha_fin').attr('min', $(this).val());
        }
    });
});
</script>

<!-- Estilos específicos -->
<style>
.btn-group .btn {
    margin-right: 2px;
}
.copy-data-btn, .copy-renewal-btn, .copy-update-btn {
    padding: 0.25rem 0.5rem;
}

/* Estilos para validación en tiempo real */
.form-control.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-valid {
    border-color: #198754;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.invalid-feedback {
    display: none;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}

.form-control.is-invalid ~ .invalid-feedback {
    display: block;
}

/* Estilos para campos deshabilitados */
.form-control:disabled,
.form-control[readonly] {
    background-color: #e9ecef;
    opacity: 1;
}

/* Estilos para el precio final */
#precio_final {
    background-color: #f8f9fa;
    font-weight: bold;
    color: #0d6efd;
}
</style>
</body>
</html> 