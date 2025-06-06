<?php
// Modal para Editar Cliente
?>
<div class="modal fade" id="editClientModal" tabindex="-1" role="dialog" aria-labelledby="editClientModalLabel" aria-modal="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editClientModalLabel">Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="clientes.php" method="POST" novalidate>
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
                            <select class="form-select country-select" name="codigo_pais" id="edit_codigo_pais" style="max-width: 200px;">
                                <option value="+591" selected>🇧🇴 Bolivia (+591)</option>
                                <option value="+54">🇦🇷 Argentina (+54)</option>
                                <option value="+55">🇧🇷 Brasil (+55)</option>
                                <option value="+1">🇨🇦 Canadá (+1)</option>
                                <option value="+56">🇨🇱 Chile (+56)</option>
                                <option value="+57">🇨🇴 Colombia (+57)</option>
                                <option value="+506">🇨🇷 Costa Rica (+506)</option>
                                <option value="+53">🇨🇺 Cuba (+53)</option>
                                <option value="+593">🇪🇨 Ecuador (+593)</option>
                                <option value="+503">🇸🇻 El Salvador (+503)</option>
                                <option value="+34">🇪🇸 España (+34)</option>
                                <option value="+1">🇺🇸 Estados Unidos (+1)</option>
                                <option value="+502">🇬🇹 Guatemala (+502)</option>
                                <option value="+504">🇭🇳 Honduras (+504)</option>
                                <option value="+52">🇲🇽 México (+52)</option>
                                <option value="+505">🇳🇮 Nicaragua (+505)</option>
                                <option value="+507">🇵🇦 Panamá (+507)</option>
                                <option value="+595">🇵🇾 Paraguay (+595)</option>
                                <option value="+51">🇵🇪 Perú (+51)</option>
                                <option value="+1">🇵🇷 Puerto Rico (+1)</option>
                                <option value="+1">🇩🇴 República Dominicana (+1)</option>
                                <option value="+598">🇺🇾 Uruguay (+598)</option>
                                <option value="+58">🇻🇪 Venezuela (+58)</option>
                            </select>
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