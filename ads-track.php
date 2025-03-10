<?php

/**
 * Plugin Name: Velocity Ads Track
 * Description: Plugin untuk merekam parameter UTM ke dalam database dan menyimpannya di cookie.
 * Version: 1.0.0
 * Author: Your Name
 */

// Mencegah akses langsung ke file
if (!defined('ABSPATH')) {
    exit;
}

// Hook untuk menjalankan fungsi saat plugin diaktifkan
register_activation_hook(__FILE__, 'velocity_ads_track_activate');

function velocity_ads_track_activate()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'velocity_ads_track';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            session_id varchar(255) NOT NULL,
            utm_source varchar(255) DEFAULT NULL,
            utm_medium varchar(255) DEFAULT NULL,
            utm_campaign varchar(255) DEFAULT NULL,
            utm_content varchar(255) DEFAULT NULL,
            utm_term varchar(255) DEFAULT NULL,
            clicked_whatsapp tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    } else {
        // Tambahkan kolom clicked_whatsapp jika belum ada
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'clicked_whatsapp'");
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD clicked_whatsapp TINYINT(1) DEFAULT 0");
        }
    }
}

// Hook untuk menjalankan fungsi saat plugin dinonaktifkan
register_deactivation_hook(__FILE__, 'velocity_ads_track_deactivate');

function velocity_ads_track_deactivate()
{
    // Tidak ada tindakan khusus saat deaktivasi
}

// Fungsi untuk menangkap parameter UTM
add_action('init', 'velocity_ads_track_capture_utm');

function velocity_ads_track_capture_utm()
{
    // Periksa apakah ada parameter UTM di URL
    $utm_source = isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : null;
    $utm_medium = isset($_GET['utm_medium']) ? sanitize_text_field($_GET['utm_medium']) : null;
    $utm_campaign = isset($_GET['utm_campaign']) ? sanitize_text_field($_GET['utm_campaign']) : null;
    $utm_content = isset($_GET['utm_content']) ? sanitize_text_field($_GET['utm_content']) : null;
    $utm_term = isset($_GET['utm_term']) ? sanitize_text_field($_GET['utm_term']) : null;

    // Jika ada parameter UTM, simpan ke cookie
    if ($utm_source || $utm_medium || $utm_campaign || $utm_content || $utm_term) {
        setcookie('velocity_utm_source', $utm_source, time() + (86400 * 30), "/"); // 30 hari
        setcookie('velocity_utm_medium', $utm_medium, time() + (86400 * 30), "/");
        setcookie('velocity_utm_campaign', $utm_campaign, time() + (86400 * 30), "/");
        setcookie('velocity_utm_content', $utm_content, time() + (86400 * 30), "/");
        setcookie('velocity_utm_term', $utm_term, time() + (86400 * 30), "/");

        // Simpan ke database jika belum ada data untuk sesi ini
        velocity_ads_track_save_to_database($utm_source, $utm_medium, $utm_campaign, $utm_content, $utm_term);
    }
}

// Fungsi untuk menyimpan data UTM ke database
// Fungsi untuk mendapatkan session ID (atau cookie velocity_session_track)
function velocity_ads_track_get_session_id()
{
    // Periksa apakah cookie velocity_session_track sudah ada
    if (isset($_COOKIE['velocity_session_track'])) {
        return sanitize_text_field($_COOKIE['velocity_session_track']);
    }

    // Jika cookie tidak ada, generate nilai unik
    $session_id = uniqid('velocity_', true);

    // Set cookie velocity_session_track selama 30 hari
    setcookie('velocity_session_track', $session_id, time() + (86400 * 30), "/");

    // Kembalikan nilai session_id
    return $session_id;
}

// Contoh penggunaan saat menyimpan data UTM ke database
function velocity_ads_track_save_to_database($utm_source, $utm_medium, $utm_campaign, $utm_content, $utm_term)
{
    global $wpdb;

    // Sanitasi input untuk keamanan
    $utm_source = sanitize_text_field($utm_source);
    $utm_medium = sanitize_text_field($utm_medium);
    $utm_campaign = sanitize_text_field($utm_campaign);
    $utm_content = sanitize_text_field($utm_content);
    $utm_term = sanitize_text_field($utm_term);

    // Skip jika $utm_term adalah placeholder dan jika kosong atau tidak ada term
    if ($utm_term === '{keyword}') {
        return;
    }

    // Ambil session ID dari cookie
    $session_id = velocity_ads_track_get_session_id();

    // Ambil user ID (NULL jika tidak ada pengguna yang login)
    $user_id = get_current_user_id() ?: NULL;

    // Nama tabel
    $table_name = $wpdb->prefix . 'velocity_ads_track';

    // Periksa apakah data untuk sesi ini sudah ada
    $existing_data = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_name WHERE session_id = %s",
        $session_id
    ));

    if (!$existing_data) {
        // Simpan data baru jika belum ada
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'session_id' => $session_id,
                'utm_source' => $utm_source,
                'utm_medium' => $utm_medium,
                'utm_campaign' => $utm_campaign,
                'utm_content' => $utm_content,
                'utm_term' => $utm_term,
            )
        );

        // Logging untuk debugging
        error_log("Data UTM berhasil disimpan: session_id=$session_id, user_id=$user_id, utm_source=$utm_source, utm_medium=$utm_medium, utm_campaign=$utm_campaign, utm_content=$utm_content, utm_term=$utm_term");
    } else {
        // Logging jika data sudah ada
        error_log("Data UTM untuk session_id=$session_id sudah ada, tidak disimpan ulang.");
    }
}

// Hook untuk menambahkan menu admin
add_action('admin_menu', 'velocity_ads_track_add_admin_menu');

function velocity_ads_track_add_admin_menu()
{
    add_menu_page(
        'Velocity Ads Track', // Judul halaman
        'Velocity Ads Track', // Nama menu
        'manage_options',     // Kemampuan yang diperlukan
        'velocity-ads-track', // Slug menu
        'velocity_ads_track_admin_page', // Fungsi callback
        'dashicons-chart-bar', // Ikon
        6                     // Posisi menu
    );
}

// Muat file halaman admin
function velocity_ads_track_admin_page()
{
    include_once(plugin_dir_path(__FILE__) . 'admin-page.php');
}

// Mulai sesi PHP jika belum dimulai
add_action('init', 'velocity_ads_track_start_session');

function velocity_ads_track_start_session()
{
    if (!session_id()) {
        session_start();
    }
}

// Enqueue script untuk deteksi klik WhatsApp
add_action('wp_enqueue_scripts', 'velocity_ads_track_enqueue_scripts');

function velocity_ads_track_enqueue_scripts()
{
    wp_enqueue_script(
        'velocity-ads-track-whatsapp',
        plugins_url('js/whatsapp-click.js', __FILE__),
        array('jquery'),
        '1.0',
        true
    );

    // Kirim data AJAX URL ke JavaScript
    wp_localize_script('velocity-ads-track-whatsapp', 'velocityAdsTrackAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    ));
}

// Hook untuk menangani klik WhatsApp (untuk pengguna yang login dan tidak login)
add_action('wp_ajax_track_whatsapp_click', 'velocity_ads_track_save_whatsapp_click');
add_action('wp_ajax_nopriv_track_whatsapp_click', 'velocity_ads_track_save_whatsapp_click');

function velocity_ads_track_save_whatsapp_click()
{
    global $wpdb;

    // Nama tabel
    $table_name = $wpdb->prefix . 'velocity_ads_track';

    // Ambil session ID
    $session_id = velocity_ads_track_get_session_id();

    // Periksa apakah sudah ada data untuk sesi ini
    $existing_data = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_name WHERE session_id = %s",
        $session_id
    ));

    if ($existing_data) {
        // Update kolom clicked_whatsapp menjadi 1
        $wpdb->update(
            $table_name,
            array('clicked_whatsapp' => 1),
            array('id' => $existing_data->id)
        );
    }

    wp_die(); // Wajib untuk mengakhiri permintaan AJAX
}

// Hook untuk memulai sesi atau membuat cookie velocity_session_track
add_action('init', 'velocity_ads_track_start_session_or_cookie');

function velocity_ads_track_start_session_or_cookie()
{
    // Periksa apakah cookie velocity_session_track sudah ada
    if (!isset($_COOKIE['velocity_session_track'])) {
        // Generate identifier unik
        $unique_id = uniqid('velocity_', true);

        // Set cookie velocity_session_track selama 30 hari
        setcookie('velocity_session_track', $unique_id, time() + (86400 * 30), "/");
    }
}
