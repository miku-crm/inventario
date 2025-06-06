<?php
// Modal para Ver Detalles del Préstamo
?>
<div class="modal fade" id="viewLoanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Cliente</h6>
                        <p id="view_cliente" class="mb-3"></p>
                        
                        <h6 class="fw-bold">Teléfono</h6>
                        <p id="view_telefono" class="mb-3"></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Producto</h6>
                        <p id="view_producto" class="mb-3"></p>
                        
                        <h6 class="fw-bold">Usuario</h6>
                        <p id="view_usuario" class="mb-3"></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Precio Final</h6>
                        <p id="view_precio" class="mb-3"></p>
                        
                        <h6 class="fw-bold">Dispositivos</h6>
                        <p id="view_dispositivos" class="mb-3"></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Fecha Inicio</h6>
                        <p id="view_fecha_inicio" class="mb-3"></p>
                        
                        <h6 class="fw-bold">Fecha Fin</h6>
                        <p id="view_fecha_fin" class="mb-3"></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="fw-bold">Estado</h6>
                        <p id="view_estado" class="mb-3"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-group w-100">
                    <?php if ($can_edit): ?>
                    <button type="button" class="btn btn-warning cancel-loan-btn" id="modal_cancel_btn">
                        <i class="fas fa-times"></i> Cancelar Préstamo
                    </button>
                    <button type="button" class="btn btn-secondary copy-loan-data-btn" id="modal_copy_data_btn">
                        <i class="fas fa-copy"></i> Copiar Datos
                    </button>
                    <button type="button" class="btn btn-info copy-renewal-btn" id="modal_copy_renewal_btn">
                        <i class="fas fa-sync"></i> Copiar Renovación
                    </button>
                    <button type="button" class="btn btn-success copy-update-btn" id="modal_copy_update_btn">
                        <i class="fas fa-edit"></i> Copiar Actualización
                    </button>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-secondary mt-2 w-100" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div> 