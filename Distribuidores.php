<?php
/**
 * Plugin Name: Distribuidores
 * Description: Importa distribuidores desde un JSON, permite gestionar y mostrar los registros en el frontend.
 * Version:     1.1
 * Author:      menghy sanchez
 * Text Domain: mi-plugin-distribuidores
 */

if (!defined('ABSPATH')) {
    exit; // Evitar acceso directo
}

global $wpdb;
$mi_plugin_db_version = '1.1';

/**
 * Al activar el plugin, creamos o actualizamos la tabla.
 */
function mpd_activate_plugin() {
    global $wpdb, $mi_plugin_db_version;

    $tabla_distribuidores = $wpdb->prefix . 'distribuidores';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $tabla_distribuidores (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        address VARCHAR(255) NOT NULL,
        city VARCHAR(100) NOT NULL,
        province VARCHAR(100) NOT NULL,
        distributor VARCHAR(100) NOT NULL,
        sucursal VARCHAR(255) NOT NULL,
        phone VARCHAR(100) NOT NULL,
        logo BIGINT(20) DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('mi_plugin_db_version', $mi_plugin_db_version);
}
register_activation_hook(__FILE__, 'mpd_activate_plugin');
/**
 * Permitir subida de archivos JSON.
 */
function mpd_allow_json_upload($mime_types) {
    $mime_types['json'] = 'application/json'; // Permitir archivos JSON
    return $mime_types;
}
add_filter('upload_mimes', 'mpd_allow_json_upload');

/**
 * Menú en el panel de administración.
 */
function mpd_add_admin_menu() {
    add_menu_page(
        'Distribuidores',
        'Distribuidores',
        'manage_options',
        'mpd_distribuidores',
        'mpd_render_admin_page',
        'dashicons-store',
        25
    );

    add_submenu_page(
        'mpd_distribuidores',
        'Administrar Distribuidores',
        'Administrar Distribuidores',
        'manage_options',
        'mpd_administrar_distribuidores',
        'mpd_render_admin_distributors_page'
    );
}
add_action('admin_menu', 'mpd_add_admin_menu');

/**
 * Carga los scripts y estilos para el admin y el frontend.
 */
function mpd_enqueue_scripts() {
    wp_enqueue_style(
        'mpd-distribuidores-css',
        plugin_dir_url(__FILE__) . 'distribuidores.css',
        [],
        '1.0',
        'all'
    );
     wp_enqueue_media();
    wp_enqueue_script(
        'mpd-media-uploader',
        plugin_dir_url(__FILE__) . 'media-uploader.js',
        ['jquery'],
        '1.0',
        true
    );

    wp_enqueue_script(
        'mpd-filtros',
        plugin_dir_url(__FILE__) . 'filtros.js',
        ['jquery'],
        '1.0',
        true
    );

    wp_localize_script('mpd-filtros', 'mpd_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'mpd_enqueue_scripts');
add_action('admin_enqueue_scripts', 'mpd_enqueue_scripts');

/**
 * Página de administración: Crear, Editar, Importar/Exportar y Listar registros.
 */
function mpd_render_admin_page() {
    global $wpdb;
    $tabla_distribuidores = $wpdb->prefix . 'distribuidores';

    // Procesar creación, edición e importación/exportación
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Crear o editar registros
        if ((isset($_POST['mpd_create_distribuidor']) || isset($_POST['mpd_edit_distribuidor'])) && check_admin_referer('mpd_nonce', 'mpd_nonce_field')) {
            $id = isset($_POST['id']) ? absint($_POST['id']) : null;
            $address = sanitize_text_field($_POST['address']);
            $city = sanitize_text_field($_POST['city']);
            $province = sanitize_text_field($_POST['province']);
            $distributor = sanitize_text_field($_POST['distributor']);
            $sucursal = sanitize_text_field($_POST['sucursal']);
            $phone = sanitize_text_field($_POST['phone']);
            $logo = intval($_POST['logo_id'] ?? 0);

            $data = compact('address', 'city', 'province', 'distributor', 'sucursal', 'phone', 'logo');

            if ($id) {
                $wpdb->update($tabla_distribuidores, $data, ['id' => $id]);
                echo '<div class="notice notice-success"><p>Registro actualizado correctamente.</p></div>';
            } else {
                $wpdb->insert($tabla_distribuidores, $data);
                echo '<div class="notice notice-success"><p>Registro creado correctamente.</p></div>';
            }
        }

        // Importar JSON
        // Procesar importación desde JSON
if (isset($_POST['mpd_import_json']) && check_admin_referer('mpd_nonce', 'mpd_nonce_field')) {
    if (!empty($_FILES['json_file']['tmp_name'])) {
        $json_file = file_get_contents($_FILES['json_file']['tmp_name']);
        $data = json_decode($json_file, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            foreach ($data as $record) {
                $address = sanitize_text_field($record['address'] ?? '');
                $city = sanitize_text_field($record['city'] ?? '');
                $province = sanitize_text_field($record['province'] ?? '');
                $distributor = sanitize_text_field($record['distributor'] ?? '');
                $sucursal = sanitize_text_field($record['sucursal'] ?? '');
                $phone = sanitize_text_field($record['phone'] ?? '');
                $logo = 0;

                $wpdb->insert($tabla_distribuidores, compact('address', 'city', 'province', 'distributor', 'sucursal', 'phone', 'logo'));
            }
            echo '<div class="notice notice-success"><p>Importación completada correctamente.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error: El archivo JSON es inválido o está vacío.</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>Error: Por favor, selecciona un archivo JSON válido.</p></div>';
    }
}

        // Exportar a JSON
        if (isset($_POST['mpd_export_json'])) {
            $registros = $wpdb->get_results("SELECT * FROM $tabla_distribuidores", ARRAY_A);
            $json_data = json_encode($registros, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="distribuidores.json"');
            echo $json_data;
            exit;
        }
    }

    echo '<h1>Gestión de Distribuidores</h1>';

    // Formulario para importar JSON
echo '<h2>Importar Distribuidores desde JSON</h2>';
echo '<form method="post" enctype="multipart/form-data">';
wp_nonce_field('mpd_nonce', 'mpd_nonce_field');
echo '<input type="file" name="json_file" accept=".json" />';
echo '<p><input type="submit" name="mpd_import_json" class="button button-primary" value="Importar JSON"></p>';
echo '</form>';

    // Botón para exportar JSON
    echo '<h2>Exportar Distribuidores</h2>';
    echo '<form method="post">';
    echo '<p><input type="submit" name="mpd_export_json" class="button button-secondary" value="Exportar JSON"></p>';
    echo '</form>';
    
    
     // Formulario de creación/edición
    $registro_a_editar = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
    $id = absint($_GET['id']);
    $registro_a_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_distribuidores WHERE id = %d", $id));
}

echo '<h2>' . ($registro_a_editar ? 'Editar Distribuidor' : 'Crear Nuevo Distribuidor') . '</h2>';
echo '<form method="post">';
wp_nonce_field('mpd_nonce', 'mpd_nonce_field');
if ($registro_a_editar) {
    echo '<input type="hidden" name="id" value="' . esc_attr($registro_a_editar->id) . '">';
}
echo '<table class="form-table">
    <tr><th><label for="address">Dirección:</label></th>
        <td><input type="text" name="address" value="' . esc_attr($registro_a_editar->address ?? '') . '" required></td></tr>
    <tr><th><label for="city">Ciudad:</label></th>
        <td><input type="text" name="city" value="' . esc_attr($registro_a_editar->city ?? '') . '" required></td></tr>
    <tr><th><label for="province">Provincia:</label></th>
        <td><input type="text" name="province" value="' . esc_attr($registro_a_editar->province ?? '') . '" required></td></tr>
    <tr><th><label for="distributor">Distribuidor:</label></th>
        <td><input type="text" name="distributor" value="' . esc_attr($registro_a_editar->distributor ?? '') . '" required></td></tr>
    <tr><th><label for="sucursal">Sucursal:</label></th>
        <td><input type="text" name="sucursal" value="' . esc_attr($registro_a_editar->sucursal ?? '') . '"></td></tr>
    <tr><th><label for="phone">Teléfono:</label></th>
        <td><input type="text" name="phone" value="' . esc_attr($registro_a_editar->phone ?? '') . '"></td></tr>
    <tr><th><label for="logo">Logo:</label></th>
        <td>
            <button type="button" class="button mpd-select-logo">Seleccionar Logo</button>
            <input type="hidden" name="logo_id" id="mpd_logo_id" value="' . esc_attr($registro_a_editar->logo ?? '') . '">
            <img src="' . (!empty($registro_a_editar->logo) ? esc_url(wp_get_attachment_url($registro_a_editar->logo)) : '') . '" class="mpd-logo-preview" style="max-width: 80px; ' . (!empty($registro_a_editar->logo) ? '' : 'display:none;') . '">
            <button type="button" class="button mpd-remove-logo">Quitar Logo</button>
        </td>
    </tr>
</table>';
echo '<p><input type="submit" name="' . ($registro_a_editar ? 'mpd_edit_distribuidor' : 'mpd_create_distribuidor') . '" class="button button-primary" value="' . ($registro_a_editar ? 'Guardar Cambios' : 'Crear Registro') . '"></p>';
echo '</form>';


    
        // Listar registros en la vista de administración
    $registros = $wpdb->get_results("SELECT * FROM $tabla_distribuidores ORDER BY id DESC");

    if (!empty($registros)) {
        echo '<h2>Lista de Distribuidores</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>
            <tr>
                <th>ID</th>
                <th>Dirección</th>
                <th>Ciudad</th>
                <th>Provincia</th>
                <th>Distribuidor</th>
                <th>Sucursal</th>
                <th>Teléfono</th>
                <th>Logo</th>
                <th>Acciones</th>
            </tr>
        </thead>';
        echo '<tbody>';
        foreach ($registros as $row) {
            $logo_url = $row->logo ? wp_get_attachment_url($row->logo) : '';
            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->address) . '</td>';
            echo '<td>' . esc_html($row->city) . '</td>';
            echo '<td>' . esc_html($row->province) . '</td>';
            echo '<td>' . esc_html($row->distributor) . '</td>';
            echo '<td>' . esc_html($row->sucursal) . '</td>';
            echo '<td>' . (!empty($row->phone) && strtolower($row->phone) !== 'na' ? esc_html($row->phone) : '—') . '</td>';
            echo '<td>';
            if ($logo_url) {
                echo '<img src="' . esc_url($logo_url) . '" alt="Logo" style="max-width: 80px; height: auto;">';
            } else {
                echo '—';
            }
            echo '</td>';
            echo '<td>
                <a href="?page=mpd_distribuidores&action=edit&id=' . $row->id . '" class="button">Editar</a>
                <a href="?page=mpd_distribuidores&action=delete&id=' . $row->id . '" class="button button-danger" onclick="return confirm(\'¿Estás seguro de eliminar este registro?\')">Eliminar</a>
            </td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No hay distribuidores registrados.</p>';
    }
}

/**
 * Página de administración para "Administrar Distribuidores".
 */
function mpd_render_admin_distributors_page() {
    global $wpdb;
    $tabla_distribuidores = $wpdb->prefix . 'distribuidores';

    // Obtener distribuidores únicos y sus conteos
    $distribuidores = $wpdb->get_results("
        SELECT distributor, logo, COUNT(*) as registros
        FROM $tabla_distribuidores
        GROUP BY distributor
        ORDER BY distributor ASC
    ");

    // Procesar asignación de logo
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mpd_assign_logo'])) {
        $distributor = sanitize_text_field($_POST['distributor']);
        $logo_id = intval($_POST['logo_id']);

        $wpdb->update(
            $tabla_distribuidores,
            ['logo' => $logo_id],
            ['distributor' => $distributor]
        );

        echo '<div class="notice notice-success"><p>Logo asignado correctamente para "' . esc_html($distributor) . '".</p></div>';
    }

    echo '<h1>Administrar Distribuidores</h1>';
    echo '<p>Desde esta vista puedes asignar un logo único a cada distribuidor y gestionar sus detalles.</p>';

    if (!empty($distribuidores)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>
            <tr>
                <th>Distribuidor</th>
                <th>Logo</th>
                <th>Registros Asociados</th>
                <th>Acciones</th>
            </tr>
        </thead>';
        echo '<tbody>';
        foreach ($distribuidores as $distribuidor) {
            $logo_url = $distribuidor->logo ? wp_get_attachment_url($distribuidor->logo) : '';

            echo '<tr>';
            echo '<td>' . esc_html($distribuidor->distributor) . '</td>';
            echo '<td>';
            if ($logo_url) {
                echo '<img src="' . esc_url($logo_url) . '" alt="Logo" style="max-width: 50px; height: auto;">';
            } else {
                echo '—';
            }
            echo '</td>';
            echo '<td>' . esc_html($distribuidor->registros) . '</td>';
            echo '<td>
                <button class="button mpd-select-logo" data-distributor="' . esc_attr($distribuidor->distributor) . '">Asignar Logo</button>
            </td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No hay distribuidores registrados.</p>';
    }

    // Formulario oculto para procesar asignación de logo
    echo '<form method="post" id="mpd-assign-logo-form" style="display: none;">
        <input type="hidden" name="distributor" id="mpd-distributor">
        <input type="hidden" name="logo_id" id="mpd-logo-id">
        <input type="hidden" name="mpd_assign_logo" value="1">
        ' . wp_nonce_field('mpd_nonce', 'mpd_nonce_field', true, false) . '
    </form>';
}

/**
 * Script para manejar la selección de logos en "Administrar Distribuidores".
 */
function mpd_admin_distributors_script() {
    ?>
    <script>
        jQuery(document).ready(function ($) {
            let mediaUploader;

            $('.mpd-select-logo').click(function (e) {
                e.preventDefault();
                const distributor = $(this).data('distributor');
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                mediaUploader = wp.media({
                    title: 'Seleccionar Logo',
                    button: {
                        text: 'Asignar este logo',
                    },
                    multiple: false
                });

                mediaUploader.on('select', function () {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#mpd-logo-id').val(attachment.id);
                    $('#mpd-distributor').val(distributor);
                    $('#mpd-assign-logo-form').submit();
                });

                mediaUploader.open();
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'mpd_admin_distributors_script');

/**
 * Shortcode para mostrar distribuidores en el frontend.
 */
function mpd_distribuidores_shortcode() {
    global $wpdb;
    $tabla_distribuidores = $wpdb->prefix . 'distribuidores';

    wp_enqueue_style(
        'mpd-distribuidores-css',
        plugin_dir_url(__FILE__) . 'distribuidores.css',
        [],
        '1.0',
        'all'
    );

    wp_enqueue_script(
        'mpd-filtros',
        plugin_dir_url(__FILE__) . 'filtros.js',
        ['jquery'],
        '1.0',
        true
    );

    wp_localize_script('mpd-filtros', 'mpd_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);

    // Obtener opciones únicas para los filtros
    $provincias = $wpdb->get_col("SELECT DISTINCT province FROM $tabla_distribuidores ORDER BY province ASC");
    $ciudades = $wpdb->get_col("SELECT DISTINCT city FROM $tabla_distribuidores ORDER BY city ASC");
    $distribuidores = $wpdb->get_col("SELECT DISTINCT distributor FROM $tabla_distribuidores ORDER BY distributor ASC");

    ob_start();

    // Filtros
    echo '<div class="mpd-filtros" style="margin-bottom: 20px;">';
    echo '<label>Provincia: </label>';
    echo '<select id="mpd-filtro-provincia">';
    echo '<option value="">Todas</option>';
    foreach ($provincias as $provincia) {
        echo '<option value="' . esc_attr($provincia) . '">' . esc_html($provincia) . '</option>';
    }
    echo '</select>';

    echo '<label>Ciudad: </label>';
    echo '<select id="mpd-filtro-ciudad">';
    echo '<option value="">Todas</option>';
    foreach ($ciudades as $ciudad) {
        echo '<option value="' . esc_attr($ciudad) . '">' . esc_html($ciudad) . '</option>';
    }
    echo '</select>';

    echo '<label>Tienda: </label>';
    echo '<select id="mpd-filtro-distribuidor">';
    echo '<option value="">Todos</option>';
    foreach ($distribuidores as $distribuidor) {
        echo '<option value="' . esc_attr($distribuidor) . '">' . esc_html($distribuidor) . '</option>';
    }
    echo '</select>';
    echo '</div>';

    // Contenedor para las tarjetas
    echo '<div id="mpd-distribuidores-grid" class="mpd-distribuidores-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">';
    //echo '<p>Cargando distribuidores...</p>';
    echo '</div>';

    return ob_get_clean();
}
add_shortcode('mpd_distribuidores', 'mpd_distribuidores_shortcode');

/**
 * Endpoint AJAX para obtener los registros filtrados.
 */
function mpd_get_distribuidores_filtrados() {
    global $wpdb;
    $tabla_distribuidores = $wpdb->prefix . 'distribuidores';

    $provincia = sanitize_text_field($_POST['provincia']);
    $ciudad = sanitize_text_field($_POST['ciudad']);
    $distribuidor = sanitize_text_field($_POST['distribuidor']);

    $where = [];
    if (!empty($provincia)) {
        $where[] = $wpdb->prepare("province = %s", $provincia);
    }
    if (!empty($ciudad)) {
        $where[] = $wpdb->prepare("city = %s", $ciudad);
    }
    if (!empty($distribuidor)) {
        $where[] = $wpdb->prepare("distributor = %s", $distribuidor);
    }

    $query = "SELECT * FROM $tabla_distribuidores";
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    $query .= " ORDER BY id DESC";

    $registros = $wpdb->get_results($query);

    ob_start();
    foreach ($registros as $row) {
        $logo_url = $row->logo ? wp_get_attachment_url($row->logo) : '';
        echo '<div class="mpd-distribuidor-card" style="border: 1px solid #ccc; padding: 1rem; border-radius: 8px;">';

        // Mostrar logo si está disponible
        if ($logo_url) {
            echo '<img src="' . esc_url($logo_url) . '" alt="Logo de ' . esc_attr($row->distributor) . '" style="max-width: 100%; height: auto; margin: 0 0 10px;">';
        }

        echo '<h3>' . esc_html($row->distributor) . '</h3>';
        echo '<p><strong>Dirección:</strong> ' . esc_html($row->address) . '</p>';

        // Mostrar teléfono si existe
        if (!empty($row->phone) && strtolower($row->phone) !== 'na') {
            echo '<p><strong>Teléfono:</strong> ' . esc_html($row->phone) . '</p>';
        }

        echo '</div>';
    }
    wp_send_json_success(ob_get_clean());
}
add_action('wp_ajax_get_distribuidores_filtrados', 'mpd_get_distribuidores_filtrados');
add_action('wp_ajax_nopriv_get_distribuidores_filtrados', 'mpd_get_distribuidores_filtrados');