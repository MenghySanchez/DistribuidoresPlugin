<?php
/**
 * Plugin Name: Distribuidores
 * Description: Importa distribuidores desde un JSON, permite gestionar y mostrar los registros en el frontend.
 * Version:     0.1.5
 * Author:      menghy sanchez
 * Text Domain: mi-plugin-distribuidores
 */

 if (!defined('ABSPATH')) {
    exit; // Evitar acceso directo
}

global $wpdb;
$mi_plugin_db_version = '1.5'; // Versión de la base de datos

/**
 * Al activar el plugin, creamos (o actualizamos) la tabla.
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
        logo BIGINT(20) DEFAULT 0, /* ID del logo almacenado */
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('mi_plugin_db_version', $mi_plugin_db_version);
}
register_activation_hook(__FILE__, 'mpd_activate_plugin');

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
}
add_action('admin_menu', 'mpd_add_admin_menu');

/**
 * Encolar los scripts necesarios.
 */
function mpd_enqueue_media_scripts($hook) {
    if ($hook !== 'toplevel_page_mpd_distribuidores') {
        return;
    }

    wp_enqueue_media(); // Biblioteca de medios de WordPress
    wp_enqueue_script(
        'mpd-media-uploader',
        plugin_dir_url(__FILE__) . 'media-uploader.js',
        ['jquery'],
        '1.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'mpd_enqueue_media_scripts');

/**
 * Página de administración: Crear, Editar y Listar registros.
 */
function mpd_render_admin_page() {
    global $wpdb;
    $tabla_distribuidores = $wpdb->prefix . 'distribuidores';

    // Procesar creación de nuevo registro
    if (isset($_POST['mpd_create_distribuidor']) && check_admin_referer('mpd_create_nonce', 'mpd_create_nonce_field')) {
        $address = sanitize_text_field($_POST['address']);
        $city = sanitize_text_field($_POST['city']);
        $province = sanitize_text_field($_POST['province']);
        $distributor = sanitize_text_field($_POST['distributor']);
        $sucursal = sanitize_text_field($_POST['sucursal']);
        $phone = sanitize_text_field($_POST['phone']);
        $logo = intval($_POST['logo'] ?? 0);

        $result = $wpdb->insert(
            $tabla_distribuidores,
            compact('address', 'city', 'province', 'distributor', 'sucursal', 'phone', 'logo')
        );

        if ($result === false) {
            echo '<div class="notice notice-error"><p>Error al crear el registro: ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Registro creado correctamente.</p></div>';
        }
    }

    // Procesar edición de un registro
    if (isset($_POST['mpd_edit_distribuidor']) && check_admin_referer('mpd_edit_nonce', 'mpd_edit_nonce_field')) {
        $id = absint($_POST['id']);
        $address = sanitize_text_field($_POST['address']);
        $city = sanitize_text_field($_POST['city']);
        $province = sanitize_text_field($_POST['province']);
        $distributor = sanitize_text_field($_POST['distributor']);
        $sucursal = sanitize_text_field($_POST['sucursal']);
        $phone = sanitize_text_field($_POST['phone']);
        $logo = intval($_POST['logo'] ?? 0);

        $data = compact('address', 'city', 'province', 'distributor', 'sucursal', 'phone');
        if ($logo) {
            $data['logo'] = $logo;
        }

        $result = $wpdb->update(
            $tabla_distribuidores,
            $data,
            ['id' => $id]
        );

        if ($result === false) {
            echo '<div class="notice notice-error"><p>Error al actualizar el registro: ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Registro actualizado correctamente.</p></div>';
        }
    }

    // Listar registros
    $registros = $wpdb->get_results("SELECT * FROM $tabla_distribuidores ORDER BY id DESC");

    echo '<h1>Gestión de Distribuidores</h1>';

    // Formulario para crear un nuevo registro
    echo '<h2>Crear Nuevo Distribuidor</h2>';
    echo '<form method="post">';
    wp_nonce_field('mpd_create_nonce', 'mpd_create_nonce_field');
    echo '<table class="form-table">
        <tr><th><label for="address">Dirección:</label></th><td><input type="text" name="address" required></td></tr>
        <tr><th><label for="city">Ciudad:</label></th><td><input type="text" name="city" required></td></tr>
        <tr><th><label for="province">Provincia:</label></th><td><input type="text" name="province" required></td></tr>
        <tr><th><label for="distributor">Distribuidor:</label></th><td><input type="text" name="distributor" required></td></tr>
        <tr><th><label for="sucursal">Sucursal:</label></th><td><input type="text" name="sucursal" required></td></tr>
        <tr><th><label for="phone">Teléfono:</label></th><td><input type="text" name="phone" required></td></tr>
        <tr><th><label for="logo">Logo:</label></th>
            <td>
                <input type="hidden" id="mpd_logo_id" name="logo">
                <img class="mpd-logo-preview" src="" alt="Logo" style="max-width: 80px; height: auto; display: none; margin-bottom: 8px;">
                <button type="button" class="button mpd-select-logo">Seleccionar Logo</button>
                <button type="button" class="button mpd-remove-logo">Eliminar Logo</button>
            </td>
        </tr>
    </table>';
    echo '<p><input type="submit" name="mpd_create_distribuidor" class="button button-primary" value="Crear Registro"></p>';
    echo '</form>';
    echo '<hr>';

    // Tabla de registros
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
            echo '<td>' . esc_html($row->phone) . '</td>';
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

/*
 * Shortcode para mostrar distribuidores en el frontend.
*/
function mpd_distribuidores_shortcode() {
    global $wpdb;
    $tabla_distribuidores = $wpdb->prefix . 'distribuidores';

    $registros = $wpdb->get_results("SELECT * FROM $tabla_distribuidores ORDER BY id DESC");

    ob_start();

    echo '<div class="mpd-distribuidores-container">';
    echo '<div class="mpd-distribuidores-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">';

    if (!empty($registros)) {
        foreach ($registros as $row) {
            $logo_url = $row->logo ? wp_get_attachment_url($row->logo) : '';
            echo '<div class="mpd-distribuidor-card" style="border: 1px solid #ccc; padding: 1rem; border-radius: 8px;">';
            if ($logo_url) {
                echo '<img src="' . esc_url($logo_url) . '" alt="Logo" style="max-width: 100%; height: auto; margin: 0 0 10px;">';
            }
            echo '<h3>' . esc_html($row->distributor) . '</h3>';
            echo '<p><strong>Sucursal:</strong> ' . esc_html($row->sucursal) . '</p>';
            echo '<p><strong>Provincia:</strong> ' . esc_html($row->province) . '</p>';
            echo '<p><strong>Ciudad:</strong> ' . esc_html($row->city) . '</p>';
            echo '<p><strong>Dirección:</strong> ' . esc_html($row->address) . '</p>';
            echo '<p><strong>Teléfono:</strong> ' . esc_html($row->phone) . '</p>';
            echo '</div>';
        }
    } else {
        echo '<p>No hay distribuidores registrados.</p>';
    }

    echo '</div>';
    echo '</div>';

    return ob_get_clean();
}
add_shortcode('mpd_distribuidores', 'mpd_distribuidores_shortcode');