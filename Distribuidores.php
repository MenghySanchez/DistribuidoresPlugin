<?php
/**
 * Plugin Name: Distribuidores
 * Description: Importa distribuidores desde un JSON, permite gestionar y mostrar los registros en el frontend.
 * Version:     0.1.7.3
 * Author:      menghy sanchez
 * Text Domain: mi-plugin-distribuidores
 */

 if (!defined('ABSPATH')) {
    exit; // Evitar acceso directo
}

global $wpdb;
$mi_plugin_db_version = '1.7.3';

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
 * Página de administración: Crear, Editar, Importar y Listar registros.
 */
function mpd_render_admin_page() {
    global $wpdb;
    $tabla_distribuidores = $wpdb->prefix . 'distribuidores';

    // Procesar creación de nuevo registro
    if (isset($_POST['mpd_create_distribuidor']) && check_admin_referer('mpd_create_nonce', 'mpd_nonce_field')) {
        $address = sanitize_text_field($_POST['address']);
        $city = sanitize_text_field($_POST['city']);
        $province = sanitize_text_field($_POST['province']);
        $distributor = sanitize_text_field($_POST['distributor']);
        $sucursal = sanitize_text_field($_POST['sucursal']);
        $phone = sanitize_text_field($_POST['phone']);
        $logo = intval($_POST['logo'] ?? 0);

        $wpdb->insert(
            $tabla_distribuidores,
            compact('address', 'city', 'province', 'distributor', 'sucursal', 'phone', 'logo')
        );

        echo '<div class="notice notice-success"><p>Registro creado correctamente.</p></div>';
    }

    // Procesar actualización de registro
    if (isset($_POST['mpd_edit_distribuidor']) && check_admin_referer('mpd_edit_nonce', 'mpd_edit_nonce_field')) {
        $id = absint($_POST['id']);
        $address = sanitize_text_field($_POST['address']);
        $city = sanitize_text_field($_POST['city']);
        $province = sanitize_text_field($_POST['province']);
        $distributor = sanitize_text_field($_POST['distributor']);
        $sucursal = sanitize_text_field($_POST['sucursal']);
        $phone = sanitize_text_field($_POST['phone']);
        $logo = intval($_POST['logo'] ?? 0);

        $wpdb->update(
            $tabla_distribuidores,
            compact('address', 'city', 'province', 'distributor', 'sucursal', 'phone', 'logo'),
            ['id' => $id]
        );

        echo '<div class="notice notice-success"><p>Registro actualizado correctamente.</p></div>';
    }

    // Procesar importación desde JSON
    if (isset($_POST['mpd_import_json']) && check_admin_referer('mpd_import_nonce', 'mpd_import_nonce_field')) {
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
                    $logo = 0; // Si el JSON no incluye un logo, usar 0 por defecto

                    // Insertar en la base de datos
                    $wpdb->insert(
                        $tabla_distribuidores,
                        compact('address', 'city', 'province', 'distributor', 'sucursal', 'phone', 'logo')
                    );
                }
                echo '<div class="notice notice-success"><p>Importación completada correctamente.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error al procesar el archivo JSON.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>No se seleccionó ningún archivo.</p></div>';
        }
    }

    echo '<h1>Gestión de Distribuidores</h1>';

    // Formulario para importar JSON
    echo '<h2>Importar Distribuidores desde JSON</h2>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('mpd_import_nonce', 'mpd_import_nonce_field');
    echo '<p><input type="file" name="json_file" accept=".json" required></p>';
    echo '<p><input type="submit" name="mpd_import_json" class="button button-primary" value="Importar JSON"></p>';
    echo '</form>';
    echo '<hr>';

    // Formulario para crear o editar registros
    echo '<h2>Crear o Editar Distribuidor</h2>';
    $registro_a_editar = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
        $id = absint($_GET['id']);
        $registro_a_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_distribuidores WHERE id = %d", $id));
    }

    echo '<form method="post">';
    wp_nonce_field('mpd_' . ($registro_a_editar ? 'edit' : 'create') . '_nonce', 'mpd_nonce_field');
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
        <tr><th><label for="logo">Logo (ID de Medios):</label></th>
            <td><input type="number" name="logo" value="' . esc_attr($registro_a_editar->logo ?? '') . '"></td></tr>
    </table>';
    echo '<p><input type="submit" name="' . ($registro_a_editar ? 'mpd_edit_distribuidor' : 'mpd_create_distribuidor') . '" class="button button-primary" value="' . ($registro_a_editar ? 'Guardar Cambios' : 'Crear Registro') . '"></p>';
    echo '</form>';
    echo '<hr>';

    // Listar registros...
// Listado de Registros en la Vista de Administración

//Agrega el listado de registros y los botones de **Editar** y **Eliminar**.

    // Listar registros
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
            echo '<td>' . (!empty($row->phone) && preg_match('/^[0-9\s\-$begin:math:text$$end:math:text$\+]+$/', $row->phone) ? esc_html($row->phone) : '—') . '</td>';
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