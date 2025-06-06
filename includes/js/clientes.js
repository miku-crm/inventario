$(document).ready(function() {
    // Verificar si Bootstrap está disponible
    console.log('Bootstrap disponible:', typeof bootstrap !== 'undefined');
    
    // Verificar si el modal existe
    console.log('Modal en el DOM:', $('#viewClienteModal').length > 0);
    console.log('ID del modal encontrado:', document.getElementById('viewClienteModal') !== null);
    
    // Inicializar DataTable
    $('#clientesTable').DataTable({
        language: {
            url: 'includes/js/datatables/i18n/es-ES.json'
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]]
    });
    
    // Variables para almacenar el elemento que abrió el modal
    let lastFocusedElement = null;
    let mainContent = document.querySelector('.main-content');
    let sidebar = document.querySelector('.sidebar');
    
    // Función para manejar la accesibilidad del modal
    function setupModalAccessibility(modalId) {
        const modal = document.querySelector(modalId);
        if (!modal) return null;
        
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        // Verificar si hay elementos focusables
        if (focusableElements.length === 0) {
            console.warn(`No se encontraron elementos focusables en el modal ${modalId}`);
            return null;
        }
        
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        // Función para manejar el ciclo de foco
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
                // Hacer el contenido principal y sidebar inert
                if (mainContent) mainContent.setAttribute('inert', '');
                if (sidebar) sidebar.setAttribute('inert', '');
                
                // Asegurarnos de que el modal sea visible para lectores de pantalla
                modal.removeAttribute('aria-hidden');
                
                // Enfocar el primer elemento después de un pequeño retraso
                if (firstFocusable) {
                    requestAnimationFrame(() => {
                        try {
                            firstFocusable.focus();
                        } catch (error) {
                            console.warn('No se pudo enfocar el primer elemento:', error);
                        }
                    });
                }
                
                // Agregar manejador de teclado
                modal.addEventListener('keydown', handleTabKey);
            },
            close: function() {
                // Restaurar el contenido principal y sidebar
                if (mainContent) mainContent.removeAttribute('inert');
                if (sidebar) sidebar.removeAttribute('inert');
                
                // Remover manejador de teclado
                modal.removeEventListener('keydown', handleTabKey);
                
                // Asegurarnos de devolver el foco antes de que Bootstrap oculte el modal
                if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                    requestAnimationFrame(() => {
                        try {
                            lastFocusedElement.focus();
                        } catch (error) {
                            console.warn('No se pudo devolver el foco al elemento anterior:', error);
                        }
                        lastFocusedElement = null;
                    });
                }
            }
        };
    }
    
    // Configurar modales
    const createModalAccessibility = setupModalAccessibility('#createClientModal');
    const editModalAccessibility = setupModalAccessibility('#editClientModal');
    const viewModalAccessibility = setupModalAccessibility('#viewClienteModal');
    
    // Manejar eventos del modal de creación
    const createModal = document.querySelector('#createClientModal');
    if (createModal) {
        createModal.addEventListener('show.bs.modal', function(event) {
            lastFocusedElement = event.relatedTarget;
        });
        
        createModal.addEventListener('shown.bs.modal', function() {
            if (createModalAccessibility) createModalAccessibility.open();
        });
        
        createModal.addEventListener('hide.bs.modal', function() {
            if (createModalAccessibility) createModalAccessibility.close();
        });
    }
    
    // Manejar eventos del modal de edición
    const editModal = document.querySelector('#editClientModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            lastFocusedElement = event.relatedTarget;
        });
        
        editModal.addEventListener('shown.bs.modal', function() {
            if (editModalAccessibility) editModalAccessibility.open();
        });
        
        editModal.addEventListener('hide.bs.modal', function() {
            if (editModalAccessibility) editModalAccessibility.close();
        });
    }
    
    // Manejar eventos del modal de vista
    const viewModal = document.querySelector('#viewClienteModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function(event) {
            lastFocusedElement = event.relatedTarget;
        });
        
        viewModal.addEventListener('shown.bs.modal', function() {
            if (viewModalAccessibility) viewModalAccessibility.open();
        });
        
        viewModal.addEventListener('hide.bs.modal', function() {
            if (viewModalAccessibility) viewModalAccessibility.close();
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
        
        $('#editClientModal').modal('show');
    });
    
    // Manejar clic en botón ver
    $('.view-btn').off('click').on('click', function() {
        console.log('Botón ver clickeado');
        
        // Verificar si el modal existe antes de intentar usarlo
        const $modal = $('#viewClienteModal');
        if ($modal.length === 0) {
            console.error('Error: Modal no encontrado en el DOM');
            return;
        }
        
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const telefono = $(this).data('telefono');
        const email = $(this).data('email');
        const direccion = $(this).data('direccion');
        const total_prestamos = $(this).data('total-prestamos');
        
        $('#view_nombre').text(nombre);
        
        // Actualizar el enlace y número de teléfono
        const $phoneLink = $('#view_telefono .phone-link');
        const $phoneNumber = $('#view_telefono .phone-number');
        $phoneLink.attr('href', `tel:${telefono}`);
        $phoneNumber.text(telefono);
        
        $('#view_email').text(email || 'No especificado');
        $('#view_direccion').text(direccion || 'No especificada');
        $('#view_total_prestamos').text(total_prestamos);
        
        $modal.modal('show');
    });
    
    // Manejar clic en botón eliminar
    $('.delete-btn').click(function() {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas eliminar al cliente "${nombre}"? Esta acción no se puede deshacer.`,
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
                form.action = 'clientes.php';
                
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
        $('#viewClienteModal').modal('hide');
        const id = $(this).data('id');
        $(`.edit-btn[data-id="${id}"]`).click();
    });
}); 