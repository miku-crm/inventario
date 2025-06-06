<?php
function renderCountrySelect($id) {
    $countries = [
        [
            'code' => '+591',
            'name' => 'Bolivia',
            'flag' => '🇧🇴'
        ],
        [
            'code' => '+54',
            'name' => 'Argentina',
            'flag' => '🇦🇷'
        ],
        [
            'code' => '+56',
            'name' => 'Chile',
            'flag' => '🇨🇱'
        ],
        [
            'code' => '+51',
            'name' => 'Perú',
            'flag' => '🇵🇪'
        ],
        [
            'code' => '+55',
            'name' => 'Brasil',
            'flag' => '🇧🇷'
        ],
        [
            'code' => '+593',
            'name' => 'Ecuador',
            'flag' => '🇪🇨'
        ],
        [
            'code' => '+595',
            'name' => 'Paraguay',
            'flag' => '🇵🇾'
        ],
        [
            'code' => '+598',
            'name' => 'Uruguay',
            'flag' => '🇺🇾'
        ],
        [
            'code' => '+57',
            'name' => 'Colombia',
            'flag' => '🇨🇴'
        ],
        [
            'code' => '+58',
            'name' => 'Venezuela',
            'flag' => '🇻🇪'
        ]
    ];

    $html = '<select class="form-select" name="codigo_pais" id="' . htmlspecialchars($id) . '" style="width: auto;">';
    
    foreach ($countries as $country) {
        $selected = $country['code'] === '+591' ? ' selected' : '';
        $html .= sprintf(
            '<option value="%s" data-flag="%s"%s>%s %s</option>',
            htmlspecialchars($country['code']),
            htmlspecialchars($country['flag']),
            $selected,
            $country['flag'],
            htmlspecialchars($country['name'])
        );
    }
    
    $html .= '</select>';
    
    return $html;
}

// Agregar estilos CSS para el selector de país
?>
<style>
.country-select-container {
    display: inline-block;
    margin-right: -1px;
}

.country-select-container .form-select {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    min-width: 140px;
}

/* Asegurar que el input de teléfono se conecte bien con el selector de país */
.country-select-container + input.form-control {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}
</style> 