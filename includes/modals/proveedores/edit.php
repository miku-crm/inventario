<?php
require_once __DIR__ . '/../../components/country_select.php';
?>
<div class="modal fade" id="editProveedorModal" tabindex="-1" role="dialog" aria-labelledby="editProveedorModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProveedorModalLabel">Editar Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="proveedores.php" method="POST" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_codigo_pais" class="form-label">Teléfono</label>
                        <div class="input-group">
                            <div class="country-select-container">
                                <?php echo renderCountrySelect('edit_codigo_pais'); ?>
                            </div>
                            <input type="tel" class="form-control" name="telefono" id="edit_telefono" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="edit_email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" name="direccion" id="edit_direccion"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="edit_cancel">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="edit_submit">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div> 