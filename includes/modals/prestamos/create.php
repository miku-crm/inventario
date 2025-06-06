<?php
// Modal para Crear Préstamo
?>
<div class="modal fade" id="createLoanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="prestamos.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <select class="form-select" id="producto_select" required>
                            <option value="">Seleccione un producto</option>
                            <?php
                            $query = "SELECT DISTINCT p.id, p.nombre, p.precio_venta 
                                     FROM productos p 
                                     JOIN usuarios_productos up ON p.id = up.producto_id 
                                     WHERE up.prestamos_activos < up.max_prestamos
                                     AND up.estado != 'VENCIDO'";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id']; ?>" 
                                    data-precio="<?php echo $row['precio_venta']; ?>">
                                <?php echo htmlspecialchars($row['nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <select class="form-select" name="usuario_producto_id" id="usuario_select" required>
                            <option value="">Seleccione un producto primero</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Buscar Cliente por Teléfono</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="telefono_busqueda" placeholder="Ingrese el teléfono">
                            <button class="btn btn-outline-secondary" type="button" id="buscar_cliente">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <select class="form-select" name="cliente_id" id="cliente_select" required>
                            <option value="">Seleccione un cliente</option>
                            <?php
                            $query = "SELECT id, nombre, telefono FROM clientes ORDER BY nombre";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id']; ?>" data-telefono="<?php echo htmlspecialchars(formatPhoneNumber($row['telefono'])); ?>">
                                <?php echo htmlspecialchars($row['nombre']); ?> - Tel: <?php echo htmlspecialchars(formatPhoneNumber($row['telefono'])); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Precio Base</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0.01" 
                                   class="form-control" 
                                   name="precio_unitario" 
                                   id="precio_unitario" 
                                   required>
                        </div>
                        <div class="invalid-feedback" id="precio-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Factor de Precio</label>
                        <select class="form-select" name="factor_precio_id" id="factor_precio_id">
                            <option value="">Sin factor</option>
                            <?php
                            $query = "SELECT id, nombre, porcentaje FROM factores_precio WHERE tipo = 'venta' ORDER BY nombre";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id']; ?>" data-porcentaje="<?php echo $row['porcentaje']; ?>">
                                <?php echo htmlspecialchars($row['nombre']); ?> 
                                (<?php echo $row['porcentaje']; ?>%)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Precio Final</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control" id="precio_final" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cantidad de Dispositivos</label>
                        <input type="number" 
                               min="1" 
                               class="form-control" 
                               name="cantidad_dispositivos" 
                               id="cantidad_dispositivos"
                               value="1" required>
                        <div class="invalid-feedback" id="dispositivos-feedback"></div>
                        <div class="form-text">
                            Número de dispositivos que el cliente podrá usar simultáneamente.
                            Este valor se descontará del máximo de préstamos permitidos para el usuario.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" 
                               class="form-control" 
                               name="fecha_inicio" 
                               id="fecha_inicio"
                               required>
                        <div class="invalid-feedback" id="fecha-inicio-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" 
                               class="form-control" 
                               name="fecha_fin" 
                               id="fecha_fin"
                               required>
                        <div class="invalid-feedback" id="fecha-fin-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="submit-btn">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div> 