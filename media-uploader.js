jQuery(document).ready(function ($) {
    let mediaUploader;

    // Evento para abrir la biblioteca de medios
    $('.mpd-select-logo').click(function (e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Seleccionar Logo',
            button: {
                text: 'Usar este logo',
            },
            multiple: false,
        });

        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#mpd_logo_id').val(attachment.id);
            $('.mpd-logo-preview').attr('src', attachment.url).show();
        });

        mediaUploader.open();
    });

    // Evento para eliminar el logo seleccionado
    $('.mpd-remove-logo').click(function (e) {
        e.preventDefault();
        $('#mpd_logo_id').val('');
        $('.mpd-logo-preview').attr('src', '').hide();
    });
});
