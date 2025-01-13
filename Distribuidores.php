<?php
/**
 * Plugin Name: Distribuidores
 * Description: Importa distribuidores desde un JSON, permite gestionar y mostrar los registros en el frontend.
 * Version:     0.1.2
 * Author:      menghy sanchez
 * Text Domain: mi-plugin-distribuidores
 */

if (!defined('ABSPATH')) {
    exit; // Evitar acceso directo
}

global $mi_plugin_db_version;
$mi_plugin_db_version = '1.1'; // Versión de la base de datos

/**
 * Al activar el plugin, creamos (o actualizamos) la tabla con la columna 'logo'.
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
        logo BIGINT(20) DEFAULT 0,  /* Columna para almacenar el ID del logo (opcional) */
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('mi_plugin_db_version', $mi_plugin_db_version);
}
register_activation_hook(__FILE__, 'mpd_activate_plugin');

/**
 * Menú en el panel de administración
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
 * Página de administración: Importar JSON y listar registros
 */
function mpd_render_admin_page() {
    global $wpdb;
    $tabla_distribuidores = $wpdb->prefix . 'distribuidores';

    // Importar JSON
    if (isset($_POST['mpd_json_import']) && check_admin_referer('mpd_import_nonce', 'mpd_import_nonce_field')) {
        if (!empty($_FILES['mpd_json_file']['tmp_name'])) {
            $json_file = file_get_contents($_FILES['mpd_json_file']['tmp_name']);
            $data_array = json_decode($json_file, true); // Decodificar como array asociativo

            if (is_array($data_array)) {
                $count_inserted = 0; // Contador de registros insertados

                foreach ($data_array as $item) {
                    // Evitar errores si faltan claves
                    $address = sanitize_text_field($item['address'] ?? '');
                    $city = sanitize_text_field($item['city'] ?? '');
                    $province = sanitize_text_field($item['province'] ?? '');
                    $distributor = sanitize_text_field($item['distributor'] ?? '');
                    $sucursal = sanitize_text_field($item['sucursal'] ?? '');
                    $phone = sanitize_text_field($item['phone'] ?? '');

                    // Insertar registro en la base de datos
                    $result = $wpdb->insert(
                        $tabla_distribuidores,
                        compact('address', 'city', 'province', 'distributor', 'sucursal', 'phone')
                    );

                    if ($result !== false) {
                        $count_inserted++;
                    }
                }

                if ($count_inserted > 0) {
                    echo '<div class="notice notice-success"><p>Se importaron ' . $count_inserted . ' registros correctamente.</p></div>';
                } else {
                    echo '<div class="notice notice-warning"><p>No se importaron registros. Revisa el formato del JSON.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Formato de JSON no válido.</p></div>';
            }
        }
    }

    // Listar registros
    $registros = $wpdb->get_results("SELECT * FROM $tabla_distribuidores ORDER BY id DESC");

    echo '<h1>Gestión de Distribuidores</h1>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('mpd_import_nonce', 'mpd_import_nonce_field');
    echo '<p>
        <label for="mpd_json_file">Importar JSON:</label>
        <input type="file" name="mpd_json_file" id="mpd_json_file" accept=".json" />
    </p>';
    echo '<p>
        <input type="submit" name="mpd_json_import" class="button button-primary" value="Importar JSON" />
    </p>';
    echo '</form>';
    echo '<hr>';

    if (!empty($registros)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>
            <tr>
                <th>ID</th>
                <th>Address</th>
                <th>City</th>
                <th>Province</th>
                <th>Distributor</th>
                <th>Sucursal</th>
                <th>Phone</th>
            </tr>
        </thead>';
        echo '<tbody>';
        foreach ($registros as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->address) . '</td>';
            echo '<td>' . esc_html($row->city) . '</td>';
            echo '<td>' . esc_html($row->province) . '</td>';
            echo '<td>' . esc_html($row->distributor) . '</td>';
            echo '<td>' . esc_html($row->sucursal) . '</td>';
            echo '<td>' . esc_html($row->phone) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No hay distribuidores registrados aún.</p>';
    }
}

/**
 * Shortcode para mostrar distribuidores en el frontend
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
            echo '<div class="mpd-distribuidor-card" style="border: 1px solid #ccc; padding: 1rem;">';
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