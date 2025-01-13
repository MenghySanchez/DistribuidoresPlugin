jQuery(document).ready(function ($) {
    function cargarDistribuidores() {
        const provincia = $('#mpd-filtro-provincia').val();
        const ciudad = $('#mpd-filtro-ciudad').val();
        const distribuidor = $('#mpd-filtro-distribuidor').val();

        $.post(mpd_ajax.ajax_url, {
            action: 'get_distribuidores_filtrados',
            provincia: provincia,
            ciudad: ciudad,
            distribuidor: distribuidor,
        }, function (response) {
            if (response.success) {
                $('#mpd-distribuidores-grid').html(response.data);
            }
        });
    }

    // Eventos para actualizar las tarjetas al cambiar un filtro
    $('#mpd-filtro-provincia, #mpd-filtro-ciudad, #mpd-filtro-distribuidor').change(cargarDistribuidores);

    // Cargar todos los registros al inicio
    //cargarDistribuidores();
});