<?php
// Modal para Editar Préstamo
?>
<div class="modal fade" id="editLoanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="prestamos.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_prestamo_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Precio Base</label>
                        <input type="number" step="0.01" class="form-control" name="precio_unitario" id="edit_precio_unitario" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Factor de Precio</label>
                        <select class="form-select" name="factor_precio_id" id="edit_factor_precio_id">
                            <option value="">Sin factor</option>
                            <?php
                            $query = "SELECT id, nombre, porcentaje FROM factores_precio WHERE tipo = 'venta' ORDER BY nombre";
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
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" name="fecha_inicio" id="edit_fecha_inicio" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" name="fecha_fin" id="edit_fecha_fin" required>
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