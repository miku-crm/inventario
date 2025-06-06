// Manejar clic en botón editar
$('.edit-btn').click(function() {
    const id = $(this).data('id');
    const precio = $(this).data('precio');
    const factor = $(this).data('factor');
    const inicio = $(this).data('inicio');
    const fin = $(this).data('fin');
    
    $('#edit_prestamo_id').val(id);
    $('#edit_precio_unitario').val(precio);
    $('#edit_factor_precio_id').val(factor);
    $('#edit_fecha_inicio').val(inicio);
    $('#edit_fecha_fin').val(fin);
    
    $('#editLoanModal').modal('show');
});

// Validación de fechas
$('#edit_fecha_fin').on('change', function() {
    const fechaInicio = new Date($('#edit_fecha_inicio').val());
    const fechaFin = new Date($(this).val());
    
    if (fechaFin <= fechaInicio) {
        $(this).addClass('is-invalid');
        $(this).next('.invalid-feedback').text('La fecha de fin debe ser posterior a la fecha de inicio');
        $('button[type="submit"]').prop('disabled', true);
    } else {
        $(this).removeClass('is-invalid').addClass('is-valid');
        $(this).next('.invalid-feedback').text('');
        $('button[type="submit"]').prop('disabled', false);
    }
}); 