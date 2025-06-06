// Manejar cambio en selección de producto
$('#producto_select').change(function() {
    const productoId = $(this).val();
    const precioVenta = $('option:selected', this).data('precio');
    
    $('#precio_unitario').val(precioVenta);
    
    if (productoId) {
        $.ajax({
            url: 'get_usuarios_libres.php',
            method: 'POST',
            data: { producto_id: productoId },
            success: function(response) {
                $('#usuario_select').html(response);
            }
        });
    } else {
        $('#usuario_select').html('<option value="">Seleccione un producto primero</option>');
    }
});

// Establecer fecha mínima como hoy para las fechas
const today = new Date().toISOString().split('T')[0];
$('#fecha_inicio').attr('min', today);
$('#fecha_fin').attr('min', today);

// Establecer fecha de inicio por defecto
$('#fecha_inicio').val(today);

// Validación de precio en tiempo real
$('#precio_unitario').on('input', function() {
    const precio = parseFloat($(this).val());
    
    if (isNaN(precio) || precio <= 0) {
        $(this).addClass('is-invalid');
        $('#precio-feedback').text('El precio debe ser mayor a 0');
        $('#submit-btn').prop('disabled', true);
    } else {
        $(this).removeClass('is-invalid').addClass('is-valid');
        $('#precio-feedback').text('');
        $('#submit-btn').prop('disabled', false);
    }
    
    actualizarPrecioFinal();
});

// Actualizar precio final cuando cambia el factor
$('#factor_precio_id').on('change', function() {
    actualizarPrecioFinal();
});

// Función para actualizar precio final
function actualizarPrecioFinal() {
    const precioBase = parseFloat($('#precio_unitario').val()) || 0;
    const factorPorcentaje = parseFloat($('#factor_precio_id option:selected').data('porcentaje')) || 0;
    
    const precioFinal = precioBase * (1 + (factorPorcentaje / 100));
    $('#precio_final').val(precioFinal.toFixed(2));
}

// Buscar cliente por teléfono
$('#buscar_cliente').click(function() {
    const telefono = $('#telefono_busqueda').val();
    if (telefono) {
        $('#cliente_select option').each(function() {
            if ($(this).data('telefono') === telefono) {
                $(this).prop('selected', true);
                return false;
            }
        });
    }
});

// Validación de cantidad de dispositivos
$('#cantidad_dispositivos').on('input', function() {
    const cantidad = parseInt($(this).val());
    if (isNaN(cantidad) || cantidad < 1) {
        $(this).addClass('is-invalid');
        $('#dispositivos-feedback').text('La cantidad debe ser mayor a 0');
        $('#submit-btn').prop('disabled', true);
    } else {
        $(this).removeClass('is-invalid').addClass('is-valid');
        $('#dispositivos-feedback').text('');
        $('#submit-btn').prop('disabled', false);
    }
});

// Validación de fechas
$('#fecha_fin').on('change', function() {
    const fechaInicio = new Date($('#fecha_inicio').val());
    const fechaFin = new Date($(this).val());
    
    if (fechaFin <= fechaInicio) {
        $(this).addClass('is-invalid');
        $('#fecha-fin-feedback').text('La fecha de fin debe ser posterior a la fecha de inicio');
        $('#submit-btn').prop('disabled', true);
    } else {
        $(this).removeClass('is-invalid').addClass('is-valid');
        $('#fecha-fin-feedback').text('');
        $('#submit-btn').prop('disabled', false);
    }
}); 