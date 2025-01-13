jQuery(document).ready(function ($) {
    function actualizarCiudades(provincia) {
        $.post(mpd_ajax.ajax_url, { action: 'get_ciudades_por_provincia', provincia }, function (response) {
            if (response.success) {
                const ciudadSelect = $('#mpd-filtro-ciudad');
                ciudadSelect.empty().append('<option value="">Selecciona una Ciudad</option>');
                response.data.forEach(ciudad => {
                    ciudadSelect.append(`<option value="${ciudad}">${ciudad}</option>`);
                });
                ciudadSelect.prop('disabled', false);
            } else {
                console.error(response.data);
            }
        });
    }

    function actualizarDistribuidores(ciudad) {
        $.post(mpd_ajax.ajax_url, { action: 'get_distribuidores_por_ciudad', ciudad }, function (response) {
            if (response.success) {
                const distribuidorSelect = $('#mpd-filtro-distribuidor');
                distribuidorSelect.empty().append('<option value="">Selecciona un Distribuidor</option>');
                response.data.forEach(distribuidor => {
                    distribuidorSelect.append(`<option value="${distribuidor}">${distribuidor}</option>`);
                });
                distribuidorSelect.prop('disabled', false);
            } else {
                console.error(response.data);
            }
        });
    }
    function cargarDistribuidores(provincia, ciudad, distribuidor) {
        $.post(mpd_ajax.ajax_url, {
            action: 'get_distribuidores_filtrados',
            provincia,
            ciudad,
            distribuidor
        }, function (response) {
            const grid = $('#mpd-distribuidores-grid');
            if (response.success) {
                console.log(response.data); // Depuración: Verifica los datos devueltos en la consola
                grid.html(response.data).fadeIn();
            } else {
                console.error(response.data); // Depuración: Muestra el error si no tiene éxito
                grid.html('<p>No se encontraron distribuidores.</p>').fadeIn();
            }
        });
    }

    $('#mpd-filtro-provincia').change(function () {
        const provincia = $(this).val();
        $('#mpd-filtro-ciudad').prop('disabled', true).html('<option value="">Cargando...</option>');
        $('#mpd-filtro-distribuidor').prop('disabled', true).html('<option value="">Selecciona un Distribuidor</option>');
        if (provincia) {
            actualizarCiudades(provincia);
        } else {
            $('#mpd-filtro-ciudad').html('<option value="">Selecciona una Ciudad</option>');
            $('#mpd-filtro-distribuidor').html('<option value="">Selecciona un Distribuidor</option>');
        }
    });

    $('#mpd-filtro-ciudad').change(function () {
        const ciudad = $(this).val();
        $('#mpd-filtro-distribuidor').prop('disabled', true).html('<option value="">Cargando...</option>');
        if (ciudad) {
            actualizarDistribuidores(ciudad);
        } else {
            $('#mpd-filtro-distribuidor').html('<option value="">Selecciona un Distribuidor</option>');
        }
    });

    $('#mpd-filtro-distribuidor').change(function () {
        const provincia = $('#mpd-filtro-provincia').val();
        const ciudad = $('#mpd-filtro-ciudad').val();
        const distribuidor = $(this).val();
        if (provincia && ciudad && distribuidor) {
            cargarDistribuidores(provincia, ciudad, distribuidor);
        }
    });
});