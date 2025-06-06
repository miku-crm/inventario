<?php
require_once __DIR__ . '/../../components/country_select.php';
?>
<div class="modal fade" id="createProveedorModal" tabindex="-1" role="dialog" aria-labelledby="createProveedorModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createProveedorModalLabel">Nuevo Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="proveedores.php" method="POST" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="create_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" id="create_nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_codigo_pais" class="form-label">Teléfono</label>
                        <div class="input-group">
                            <div class="country-select-container">
                                <?php echo renderCountrySelect('create_codigo_pais'); ?>
                            </div>
                            <input type="tel" class="form-control" name="telefono" id="create_telefono" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="create_email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" name="direccion" id="create_direccion"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="create_cancel">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="create_submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div> 