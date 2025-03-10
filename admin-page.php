<?php
/**
 * Halaman admin untuk menampilkan daftar data UTM.
 */

// Mencegah akses langsung ke file
if (!defined('ABSPATH')) {
    exit;
}

function velocity_ads_track_admin_list() {
    global $wpdb;

    // Nama tabel
    $table_name = $wpdb->prefix . 'velocity_ads_track';

    // Proses tindakan hapus
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']); // Pastikan ID adalah angka

        // Hapus data dari database
        $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d') // Format placeholder untuk ID
        );
    }

    // Pagination
    $per_page = 20; // Jumlah baris per halaman
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // Query untuk mengambil total data
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Query untuk mengambil data dengan limit dan offset
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ),
        ARRAY_A
    );

    // Hitung jumlah halaman
    $total_pages = ceil($total_items / $per_page);

    // Tampilkan tabel data
    ?>
    <div class="wrap">
        <h1>Velocity Ads Track</h1>
        <p>Daftar parameter UTM yang telah direkam.</p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Session ID</th>
                <th>UTM Source</th>
                <th>UTM Medium</th>
                <th>UTM Campaign</th>
                <th>UTM Content</th>
                <th>UTM Term</th>
                <th>Clicked WhatsApp</th>
                <th>Tanggal</th>
                <th>Aksi</th> <!-- Kolom baru untuk tombol hapus -->
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($results)) : ?>
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row['id']); ?></td>
                        <td><?php echo esc_html($row['user_id'] ?: '-'); ?></td>
                        <td><?php echo esc_html($row['session_id']); ?></td>
                        <td><?php echo esc_html($row['utm_source'] ?: '-'); ?></td>
                        <td><?php echo esc_html($row['utm_medium'] ?: '-'); ?></td>
                        <td><?php echo esc_html($row['utm_campaign'] ?: '-'); ?></td>
                        <td><?php echo esc_html($row['utm_content'] ?: '-'); ?></td>
                        <td><?php echo esc_html($row['utm_term'] ?: '-'); ?></td>
                        <td><?php echo $row['clicked_whatsapp'] ? 'Ya' : 'Tidak'; ?></td>
                        <td><?php echo esc_html($row['created_at']); ?></td>
                        <td>
                            <!-- Tombol Hapus -->
                            <a href="?page=velocity-ads-track&action=delete&id=<?php echo esc_attr($row['id']); ?>" class="button button-small button-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="11">Tidak ada data.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        </table>

        <!-- Pagination -->
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo; Previous'),
                    'next_text' => __('Next &raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page,
                ));
                ?>
            </div>
        </div>
    </div>
    <?php
}

// Panggil fungsi untuk menampilkan halaman admin
velocity_ads_track_admin_list();