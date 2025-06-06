<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="page-header mb-4">
        <h2>Historial de Préstamos</h2>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Operación realizada con éxito.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Filtros básicos -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado">
                        <option value="">Todos</option>
                        <option value="CANCELADO" <?php echo isset($_GET['estado']) && $_GET['estado'] === 'CANCELADO' ? 'selected' : ''; ?>>Cancelado</option>
                        <option value="LIBERADO" <?php echo isset($_GET['estado']) && $_GET['estado'] === 'LIBERADO' ? 'selected' : ''; ?>>Liberado</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Cliente</label>
                    <select class="form-select" name="cliente_id">
                        <option value="">Todos</option>
                        <?php
                        $query = "SELECT id, nombre, telefono FROM clientes ORDER BY nombre";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <option value="<?php echo $row['id']; ?>" <?php echo isset($_GET['cliente_id']) && $_GET['cliente_id'] == $row['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['nombre']); ?> (<?php echo htmlspecialchars(formatPhoneNumber($row['telefono'])); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Producto</label>
                    <select class="form-select" name="producto_id">
                        <option value="">Todos</option>
                        <?php
                        $query = "SELECT id, nombre FROM productos ORDER BY nombre";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <option value="<?php echo $row['id']; ?>" <?php echo isset($_GET['producto_id']) && $_GET['producto_id'] == $row['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['nombre']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" name="fecha_desde" 
                           value="<?php echo $_GET['fecha_desde'] ?? ''; ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" name="fecha_hasta" 
                           value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                    <a href="historial_prestamos.php" class="btn btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Préstamos Finalizados</h5>
                    <?php
                    $query = "SELECT COUNT(*) as total FROM prestamos WHERE estado IN ('CANCELADO', 'LIBERADO')";
                    $result = $conn->query($query);
                    $row = $result->fetch_assoc();
                    ?>
                    <h3 class="mb-0"><?php echo $row['total']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h5 class="card-title">Préstamos Cancelados</h5>
                    <?php
                    $query = "SELECT COUNT(*) as total FROM prestamos WHERE estado = 'CANCELADO'";
                    $result = $conn->query($query);
                    $row = $result->fetch_assoc();
                    ?>
                    <h3 class="mb-0"><?php echo $row['total']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Préstamos Liberados</h5>
                    <?php
                    $query = "SELECT COUNT(*) as total FROM prestamos WHERE estado = 'LIBERADO'";
                    $result = $conn->query($query);
                    $row = $result->fetch_assoc();
                    ?>
                    <h3 class="mb-0"><?php echo $row['total']; ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla de Historial -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-striped" id="historialTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Usuario</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Precio Final</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Estado</th>
                        <th>Duración</th>
                        <th>Renovaciones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT p.*, up.user_name, pr.nombre as producto_nombre, 
                             c.nombre as cliente_nombre, c.telefono,
                             (SELECT COUNT(*) FROM renovaciones r WHERE r.prestamo_id = p.id) as total_renovaciones
                             FROM prestamos p 
                             JOIN usuarios_productos up ON p.usuario_producto_id = up.id 
                             JOIN productos pr ON up.producto_id = pr.id 
                             JOIN clientes c ON p.cliente_id = c.id 
                             WHERE p.estado IN ('CANCELADO', 'LIBERADO')";

                    // Agregar información de depuración
                    echo "<!-- Query de depuración: " . $query . " -->";
                    
                    if (!empty($_GET['estado'])) {
                        $estado = sanitize($_GET['estado']);
                        $query .= " AND p.estado = '$estado'";
                        echo "<!-- Filtro de estado aplicado: $estado -->";
                    }
                    
                    if (!empty($_GET['cliente_id'])) {
                        $query .= " AND p.cliente_id = " . intval($_GET['cliente_id']);
                    }

                    if (!empty($_GET['producto_id'])) {
                        $query .= " AND pr.id = " . intval($_GET['producto_id']);
                    }

                    if (!empty($_GET['fecha_desde'])) {
                        $query .= " AND p.fecha_inicio >= '" . sanitize($_GET['fecha_desde']) . "'";
                    }

                    if (!empty($_GET['fecha_hasta'])) {
                        $query .= " AND p.fecha_inicio <= '" . sanitize($_GET['fecha_hasta']) . "'";
                    }
                    
                    $query .= " ORDER BY p.id DESC";
                    $result = $conn->query($query);
                    
                    while ($row = $result->fetch_assoc()):
                        $estado_class = '';
                        switch ($row['estado']) {
                            case 'ACTIVO':
                                $estado_class = 'success';
                                break;
                            case 'VENCIDO':
                                $estado_class = 'danger';
                                break;
                            case 'CANCELADO':
                                $estado_class = 'secondary';
                                break;
                            case 'LIBERADO':
                                $estado_class = 'info';
                                break;
                        }
                        
                        // Debug información
                        echo "<!-- Préstamo ID: " . $row['id'] . ", Estado: " . $row['estado'] . " -->";

                        // Calcular duración
                        $fecha_inicio = new DateTime($row['fecha_inicio']);
                        $fecha_fin = new DateTime($row['fecha_fin']);
                        $duracion = $fecha_inicio->diff($fecha_fin);
                    ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['producto_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                        <td><?php echo htmlspecialchars(formatPhoneNumber($row['telefono'])); ?></td>
                        <td>$<?php echo formatMoney($row['precio_final']); ?></td>
                        <td><?php echo formatDate($row['fecha_inicio']); ?></td>
                        <td><?php echo formatDate($row['fecha_fin']); ?></td>
                        <td><span class="badge bg-<?php echo $estado_class; ?>"><?php echo $row['estado']; ?></span></td>
                        <td><?php echo $duracion->days . ' días'; ?></td>
                        <td>
                            <?php if ($row['total_renovaciones'] > 0): ?>
                            <a href="#" class="btn btn-sm btn-info ver-renovaciones" data-id="<?php echo $row['id']; ?>" 
                               data-cliente="<?php echo htmlspecialchars($row['cliente_nombre']); ?>"
                               data-producto="<?php echo htmlspecialchars($row['producto_nombre']); ?>"
                               data-usuario="<?php echo htmlspecialchars($row['user_name']); ?>">
                                Ver (<?php echo $row['total_renovaciones']; ?>)
                            </a>
                            <?php else: ?>
                            <span class="text-muted">Sin renovaciones</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['estado'] === 'VENCIDO'): ?>
                            <button class="btn btn-sm btn-danger liberar-btn" data-id="<?php echo $row['id']; ?>">
                                <i class="fas fa-unlock"></i> Liberar Cuenta
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

<!-- Modal para ver renovaciones -->
<div class="modal fade" id="renovacionesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Historial de Renovaciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="info-prestamo mb-3">
                    <p><strong>Cliente:</strong> <span id="modal-cliente"></span></p>
                    <p><strong>Producto:</strong> <span id="modal-producto"></span></p>
                    <p><strong>Usuario:</strong> <span id="modal-usuario"></span></p>
                </div>

                <!-- Datos del préstamo original -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Préstamo Original</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Fecha Inicio:</strong> <span id="modal-fecha-original"></span></p>
                                <p><strong>Fecha Fin:</strong> <span id="modal-fecha-fin-original"></span></p>
                                <p><strong>Dispositivos:</strong> <span id="modal-dispositivos"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Precio Base:</strong> $<span id="modal-precio-original"></span></p>
                                <p><strong>Factor de Precio:</strong> <span id="modal-factor"></span></p>
                                <p><strong>Precio Final:</strong> $<span id="modal-precio-final"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Historial de Renovaciones</h5>
                <div class="table-responsive">
                    <table class="table table-striped" id="tablaRenovaciones">
                        <thead>
                            <tr>
                                <th>Fecha Renovación</th>
                                <th>Precio Anterior</th>
                                <th>Precio Nuevo</th>
                                <th>Fecha Anterior</th>
                                <th>Nueva Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#historialTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        order: [[0, 'desc']]
    });

    // Función para formatear fecha
    function formatDate(dateString) {
        const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    }

    // Función para formatear dinero
    function formatMoney(amount) {
        return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Manejar clic en ver renovaciones
    $('.ver-renovaciones').click(function(e) {
        e.preventDefault();
        const prestamoId = $(this).data('id');
        
        // Actualizar información en el modal
        $('#modal-cliente').text($(this).data('cliente'));
        $('#modal-producto').text($(this).data('producto'));
        $('#modal-usuario').text($(this).data('usuario'));
        
        // Limpiar tabla anterior
        $('#tablaRenovaciones tbody').empty();
        
        // Mostrar loading
        $('#tablaRenovaciones tbody').html('<tr><td colspan="5" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>');
        
        // Abrir modal
        $('#renovacionesModal').modal('show');
        
        // Cargar datos
        $.ajax({
            url: 'get_renovaciones.php',
            method: 'GET',
            data: { prestamo_id: prestamoId },
            success: function(response) {
                // Mostrar datos del préstamo original
                if (response.prestamo_original) {
                    $('#modal-fecha-original').text(formatDate(response.prestamo_original.fecha_original));
                    $('#modal-fecha-fin-original').text(formatDate(response.prestamo_original.fecha_fin_original));
                    $('#modal-dispositivos').text(response.prestamo_original.cantidad_dispositivos);
                    $('#modal-precio-original').text(formatMoney(response.prestamo_original.precio_original));
                    $('#modal-factor').text(response.prestamo_original.factor_precio);
                    $('#modal-precio-final').text(formatMoney(response.prestamo_original.precio_final_original));
                }

                // Mostrar renovaciones
                let html = '';
                if (response.renovaciones.length === 0) {
                    html = '<tr><td colspan="5" class="text-center">No se encontraron renovaciones</td></tr>';
                } else {
                    response.renovaciones.forEach(function(renovacion) {
                        html += `
                            <tr>
                                <td>${formatDate(renovacion.fecha_renovacion)}</td>
                                <td>$${formatMoney(renovacion.precio_anterior)}</td>
                                <td>$${formatMoney(renovacion.precio_nuevo)}</td>
                                <td>${formatDate(renovacion.fecha_anterior)}</td>
                                <td>${formatDate(renovacion.fecha_nueva)}</td>
                            </tr>
                        `;
                    });
                }
                $('#tablaRenovaciones tbody').html(html);
            },
            error: function() {
                $('#tablaRenovaciones tbody').html('<tr><td colspan="5" class="text-center text-danger">Error al cargar los datos</td></tr>');
            }
        });
    });

    // Manejar clic en botón liberar
    $('.liberar-btn').click(function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¿Deseas liberar esta cuenta?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, liberar',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'prestamos.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'liberar';
                
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