$(document).ready(function() {
    // Inicializar DataTable
    $('#proveedoresTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });
    
    // Variables para almacenar el elemento que abrió el modal
    let lastFocusedElement = null;
    let mainContent = document.querySelector('.main-content');
    let sidebar = document.querySelector('.sidebar');
    
    // Función para manejar la accesibilidad del modal
    function setupModalAccessibility(modalId) {
        const modal = document.querySelector(modalId);
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        function handleTabKey(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        e.preventDefault();
                        lastFocusable.focus();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        e.preventDefault();
                        firstFocusable.focus();
                    }
                }
            }
        }

        return {
            open: function() {
                if (mainContent) mainContent.setAttribute('inert', '');
                if (sidebar) sidebar.setAttribute('inert', '');
                modal.removeAttribute('aria-hidden');
                setTimeout(() => {
                    firstFocusable.focus();
                }, 50);
                modal.addEventListener('keydown', handleTabKey);
            },
            close: function() {
                if (mainContent) mainContent.removeAttribute('inert');
                if (sidebar) sidebar.removeAttribute('inert');
                modal.removeEventListener('keydown', handleTabKey);
                if (lastFocusedElement) {
                    setTimeout(() => {
                        lastFocusedElement.focus();
                    }, 50);
                }
            }
        };
    }
    
    // Configurar modales
    const createModalAccessibility = setupModalAccessibility('#createProveedorModal');
    const editModalAccessibility = setupModalAccessibility('#editProveedorModal');
    const viewModalAccessibility = setupModalAccessibility('#viewProveedorModal');
    
    // Manejar eventos del modal de creación
    const createModal = document.querySelector('#createProveedorModal');
    if (createModal) {
        createModal.addEventListener('show.bs.modal', function(event) {
            lastFocusedElement = event.relatedTarget;
        });
        
        createModal.addEventListener('shown.bs.modal', function() {
            createModalAccessibility.open();
        });
        
        createModal.addEventListener('hide.bs.modal', function() {
            createModalAccessibility.close();
        });
    }
    
    // Manejar eventos del modal de edición
    const editModal = document.querySelector('#editProveedorModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            lastFocusedElement = event.relatedTarget;
        });
        
        editModal.addEventListener('shown.bs.modal', function() {
            editModalAccessibility.open();
        });
        
        editModal.addEventListener('hide.bs.modal', function() {
            editModalAccessibility.close();
        });
    }
    
    // Manejar eventos del modal de vista
    const viewModal = document.querySelector('#viewProveedorModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function(event) {
            lastFocusedElement = event.relatedTarget;
        });
        
        viewModal.addEventListener('shown.bs.modal', function() {
            viewModalAccessibility.open();
        });
        
        viewModal.addEventListener('hide.bs.modal', function() {
            viewModalAccessibility.close();
        });
    }
    
    // Manejar clic en botón editar
    $('.edit-btn').click(function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const telefono = $(this).data('telefono');
        const email = $(this).data('email');
        const direccion = $(this).data('direccion');
        
        $('#edit_id').val(id);
        $('#edit_nombre').val(nombre);
        
        // Separar el código de país del número de teléfono
        const telefonoMatch = telefono.match(/^(\+\d{2,3})(.*)/);
        if (telefonoMatch) {
            $('#edit_codigo_pais').val(telefonoMatch[1]);
            $('#edit_telefono').val(telefonoMatch[2].trim());
        } else {
            $('#edit_codigo_pais').val('+591');
            $('#edit_telefono').val(telefono);
        }
        
        $('#edit_email').val(email);
        $('#edit_direccion').val(direccion);
        
        $('#editProveedorModal').modal('show');
    });
    
    // Manejar clic en botón ver
    $('.view-btn').click(function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const telefono = $(this).data('telefono');
        const email = $(this).data('email');
        const direccion = $(this).data('direccion');
        const entradas = $(this).data('entradas');
        const ultima_compra = $(this).data('ultima-compra');
        
        $('#view_nombre').text(nombre);
        $('#view_telefono').text(telefono || 'No especificado');
        $('#view_email').text(email || 'No especificado');
        $('#view_direccion').text(direccion || 'No especificada');
        
        // Actualizar estadísticas
        $('#view_entradas').text(entradas || '0');
        $('#view_ultima_compra').text(ultima_compra || 'Sin compras');
        
        $('#viewProveedorModal').modal('show');
    });
    
    // Manejar clic en botón eliminar
    $('.delete-btn').click(function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas eliminar al proveedor "${nombre}"? Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'proveedores.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                
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
    
    // Manejar el botón de editar desde la vista de detalles
    $('#btn_edit_from_view').click(function() {
        $('#viewProveedorModal').modal('hide');
        const id = $(this).data('id');
        $(`.edit-btn[data-id="${id}"]`).click();
    });
}); 