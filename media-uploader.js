jQuery(document).ready(function ($) {
    let mediaUploader;

    // Función genérica para abrir el Media Uploader
    function openMediaUploader(options) {
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: options.title,
            button: {
                text: options.buttonText,
            },
            multiple: false,
            library: options.library || {}, // Filtro opcional
        });

        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            options.onSelect(attachment);
        });

        mediaUploader.open();
    }

    // Seleccionar logo para un registro
    $('.mpd-select-logo').click(function (e) {
        e.preventDefault();

        openMediaUploader({
            title: 'Seleccionar Logo',
            buttonText: 'Usar este logo',
            library: { type: 'image' }, // Solo imágenes
            onSelect: function (attachment) {
                $('#mpd_logo_id').val(attachment.id); // Asigna el ID del logo al campo oculto
                $('.mpd-logo-preview').attr('src', attachment.url).show(); // Muestra la vista previa del logo
            },
        });
    });

    // Eliminar logo seleccionado
    $('.mpd-remove-logo').click(function (e) {
        e.preventDefault();
        $('#mpd_logo_id').val(''); // Elimina el ID del logo del campo oculto
        $('.mpd-logo-preview').attr('src', '').hide(); // Oculta la vista previa del logo
    });

    // Seleccionar archivo JSON para importación
    $('.mpd-select-json').click(function (e) {
        e.preventDefault();

        openMediaUploader({
            title: 'Seleccionar Archivo JSON',
            buttonText: 'Usar este archivo',
            library: { type: 'application' }, // Permite seleccionar archivos
            onSelect: function (attachment) {
                $('#json_url').val(attachment.url); // Asigna la URL del archivo JSON al campo oculto
                $('#json_file_name').text(attachment.filename); // Muestra el nombre del archivo
                $('#json_file_url').text(attachment.url); // Muestra la URL del archivo
            },
        });
    });
});