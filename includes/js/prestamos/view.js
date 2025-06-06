// Función auxiliar para formatear moneda
function formatMoney(amount) {
    return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Función auxiliar para formatear fecha
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Función para copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        Swal.fire({
            icon: 'success',
            title: '¡Copiado!',
            text: 'Texto copiado al portapapeles',
            showConfirmButton: false,
            timer: 1500
        });
    }).catch(function(err) {
        console.error('Error al copiar: ', err);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo copiar al portapapeles'
        });
    });
}

// Manejar clic en botón ver
$('.view-btn').click(function() {
    const id = $(this).data('id');
    const cliente = $(this).data('cliente');
    const telefono = $(this).data('telefono');
    const producto = $(this).data('producto');
    const usuario = $(this).data('usuario');
    const password = $(this).data('password');
    const precio = $(this).data('precio');
    const dispositivos = $(this).data('dispositivos');
    const fechaInicio = $(this).data('fecha-inicio');
    const fechaFin = $(this).data('fecha-fin');
    const estado = $(this).data('estado');
    
    // Guardar datos en el modal para uso posterior
    const $modal = $('#viewLoanModal');
    $modal.data('prestamo-data', {
        id,
        cliente,
        telefono,
        producto,
        usuario,
        password,
        precio,
        dispositivos,
        fechaInicio,
        fechaFin,
        estado
    });
    
    $('#view_cliente').text(cliente);
    $('#view_telefono').text(telefono || 'No especificado');
    $('#view_producto').text(producto);
    $('#view_usuario').text(usuario);
    $('#view_precio').text('$' + formatMoney(precio));
    $('#view_dispositivos').text(dispositivos);
    $('#view_fecha_inicio').text(formatDate(fechaInicio));
    $('#view_fecha_fin').text(formatDate(fechaFin));
    $('#view_estado').text(estado);
    
    $('#viewLoanModal').modal('show');
});

// Manejar clic en botón cancelar préstamo desde el modal
$('#modal_cancel_btn').click(function() {
    const prestamoData = $('#viewLoanModal').data('prestamo-data');
    if (confirm('¿Está seguro de que desea cancelar este préstamo?')) {
        window.location.href = `prestamos.php?action=cancel&id=${prestamoData.id}`;
    }
});

// Manejar clic en botón copiar datos desde el modal
$('#modal_copy_data_btn').click(function() {
    const prestamoData = $('#viewLoanModal').data('prestamo-data');
    const texto = `Producto: ${prestamoData.producto}\n` +
                 `Usuario: ${prestamoData.usuario}\n` +
                 `Contraseña: ${prestamoData.password}\n` +
                 `Dispositivos: ${prestamoData.dispositivos}\n` +
                 `Fecha de expiración: ${formatDate(prestamoData.fechaFin)}`;
    
    copyToClipboard(texto);
});

// Manejar clic en botón copiar renovación desde el modal
$('#modal_copy_renewal_btn').click(function() {
    const prestamoData = $('#viewLoanModal').data('prestamo-data');
    const texto = `Renovación de préstamo:\n` +
                 `Cliente: ${prestamoData.cliente}\n` +
                 `Producto: ${prestamoData.producto}\n` +
                 `Usuario: ${prestamoData.usuario}\n` +
                 `Fecha Fin: ${formatDate(prestamoData.fechaFin)}`;
    
    copyToClipboard(texto);
});

// Manejar clic en botón copiar actualización desde el modal
$('#modal_copy_update_btn').click(function() {
    const prestamoData = $('#viewLoanModal').data('prestamo-data');
    const texto = `Actualización de préstamo:\n` +
                 `Cliente: ${prestamoData.cliente}\n` +
                 `Producto: ${prestamoData.producto}\n` +
                 `Usuario: ${prestamoData.usuario}\n` +
                 `Precio: $${formatMoney(prestamoData.precio)}\n` +
                 `Fecha Fin: ${formatDate(prestamoData.fechaFin)}`;
    
    copyToClipboard(texto);
}); 