<?php
// Modal para Ver Detalles del Proveedor
?>
<div class="modal fade" id="viewProveedorModal" tabindex="-1" role="dialog" aria-labelledby="viewProveedorModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewProveedorModalLabel">Detalles del Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <h6 class="fw-bold">Nombre</h6>
                        <p id="view_nombre" class="mb-3"></p>
                        
                        <h6 class="fw-bold">Teléfono</h6>
                        <p id="view_telefono" class="mb-3"></p>
                        
                        <h6 class="fw-bold">Email</h6>
                        <p id="view_email" class="mb-3"></p>
                        
                        <h6 class="fw-bold">Dirección</h6>
                        <p id="view_direccion" class="mb-3"></p>
                    </div>
                </div>
                
                <!-- Sección para estadísticas -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="fw-bold border-bottom pb-2">Estadísticas</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Total de Entradas</small>
                                <p id="view_entradas" class="mb-2"></p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Última Compra</small>
                                <p id="view_ultima_compra" class="mb-2"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn_edit_from_view">Editar</button>
            </div>
        </div>
    </div>
</div> 