<?php
/**
 * Plugin Name: System Information Widget
 * Description: Adds a styled dashboard widget displaying detailed system information.
 * Version: 1.0
 * Author: Eric Dennis
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */


add_action('wp_dashboard_setup', 'add_styled_system_info_dashboard_widget');

function add_styled_system_info_dashboard_widget() {
    wp_add_dashboard_widget(
        'styled_system_info_widget',
        '📊 System Information',
        'display_styled_system_info_widget_content'
    );
}

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'index.php') {
        wp_enqueue_script('sysinfo-dashboard-js', plugin_dir_url(__FILE__) . 'assets/widget.js', ['jquery'], null, true);
        wp_localize_script('sysinfo-dashboard-js', 'sysInfoDashboard', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sysinfo_nonce')
        ]);
    }
});

add_action('wp_ajax_save_sysinfo_prefs', function() {
    check_ajax_referer('sysinfo_nonce');
    $user_id = get_current_user_id();

    delete_user_meta($user_id, 'sysinfo_widget_prefs');

    $prefs = isset($_POST['prefs']) ? $_POST['prefs'] : [];
    $clean_prefs = [];

    foreach ($prefs as $section => $visible) {
        $clean_prefs[sanitize_text_field($section)] = filter_var($visible, FILTER_VALIDATE_BOOLEAN);
    }

    update_user_meta($user_id, 'sysinfo_widget_prefs', $clean_prefs);
    wp_send_json_success();
});

function display_styled_system_info_widget_content() {
    $wp_version = get_bloginfo('version');
    $site_url = get_site_url();
    $home_url = get_home_url();

    $php_version = phpversion();
    $server_software = $_SERVER['SERVER_SOFTWARE'];
    $upload_max = ini_get('upload_max_filesize');
    $memory_limit = ini_get('memory_limit');

    $ABSPATH = ABSPATH;
    $content_dir = WP_CONTENT_DIR;
    $plugin_dir = WP_PLUGIN_DIR;
    $theme_dir = get_template_directory();

    $is_debug = defined('WP_DEBUG') && WP_DEBUG;
    $is_ssl = is_ssl() ? 'Yes' : 'No';

    $current_user = wp_get_current_user();
    $user_name = $current_user->user_login;
    $user_roles = implode(', ', $current_user->roles);

    global $wpdb;
    $post_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts");

    $plugins = get_plugins();
    $active_plugins = get_option('active_plugins');
    $theme = wp_get_theme();
    $theme_name = $theme->get('Name') . ' ' . $theme->get('Version');

    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $public_ip = 'N/A';
    $ip_info = array();
    $response = wp_remote_get('https://api.ipify.org');
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
        $public_ip = wp_remote_retrieve_body($response);
    }
    if ($public_ip !== 'N/A') {
        $ipinfo_response = wp_remote_get('https://ipinfo.io/' . $public_ip . '/json');
        if (!is_wp_error($ipinfo_response) && wp_remote_retrieve_response_code($ipinfo_response) == 200) {
            $ip_info = json_decode(wp_remote_retrieve_body($ipinfo_response), true);
        }
    }

    $user_id = get_current_user_id();
    $default_sections = [
        'current-user-sid' => true,
        'wordpress-env-sid' => true,
        'server-config-sid' => true,
        'server-ip-sid' => true,
        'debug-security-sid' => true,
        'file-systems-sid' => true,
    ];
    $user_prefs = get_user_meta($user_id, 'sysinfo_widget_prefs', true);

    $section_labels = [
        'current-user-sid' => '👤 Current User',
        'wordpress-env-sid' => '🌐 WordPress Environment',
        'server-config-sid' => '⚙️ Server Configuration',
        'server-ip-sid' => '🏡 Server IP Address',
        'debug-security-sid' => '🔐 Debug & Security',
        'file-systems-sid' => '📂 File System Paths',
    ];

    if (!is_array($user_prefs)) $user_prefs = $default_sections;

    echo '<style>
        .sysinfo-widget { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; }
        .sysinfo-widget table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        .sysinfo-widget th, .sysinfo-widget td { border: 1px solid #ddd; padding: 8px; }
        .sysinfo-widget th { background: #f7f7f7; text-align: left; width: 200px; }
        .sysinfo-widget h4 { margin-top: 20px; margin-bottom: 10px; font-size: 15px; font-weight: bold !important; }
    </style>';

    echo '<div class="sysinfo-widget">';

    if (!empty($user_prefs['current-user-sid'])) {
        echo '<div id="current-user-sid"><h4>👤 Current User</h4>
        <table><tr><th>Username</th><td>' . esc_html($user_name) . '</td></tr>
        <tr><th>User Roles</th><td>' . esc_html($user_roles) . '</td></tr>
        <tr><th>User Agent</th><td>' . esc_html($user_agent) . '</td></tr></table></div>';
    }

    if (!empty($user_prefs['wordpress-env-sid'])) {
        echo '<div id="wordpress-env-sid"><h4>🌐 WordPress Environment</h4>
        <table><tr><th>WordPress Version</th><td>' . esc_html($wp_version) . '</td></tr>
        <tr><th>Current Theme</th><td>' . esc_html($theme_name) . '</td></tr>
        <tr><th>Active Plugins</th><td>' . count($active_plugins) . ' / ' . count($plugins) . '</td></tr>
        <tr><th>Total Posts</th><td>' . esc_html($post_count) . '</td></tr>
        <tr><th>Site URL</th><td><a href="' . esc_url($site_url) . '" target="_blank">' . esc_html($site_url) . '</a></td></tr>
        <tr><th>Home URL</th><td><a href="' . esc_url($home_url) . '" target="_blank">' . esc_html($home_url) . '</a></td></tr></table></div>';
    }

    if (!empty($user_prefs['server-config-sid'])) {
        echo '<div id="server-config-sid"><h4>⚙️ Server Configuration</h4>
        <table><tr><th>PHP Version</th><td>' . esc_html($php_version) . '</td></tr>
        <tr><th>Server Software</th><td>' . esc_html($server_software) . '</td></tr>
        <tr><th>Max Upload Size</th><td>' . esc_html($upload_max) . '</td></tr>
        <tr><th>Memory Limit</th><td>' . esc_html($memory_limit) . '</td></tr></table></div>';
    }

    if (!empty($user_prefs['server-ip-sid'])) {
        echo '<div id="server-ip-sid"><h4>🏡 Server IP Address</h4><table>';
        if (!empty($ip_info)) {
            $location_parts = array_filter([$ip_info['city'] ?? '', $ip_info['region'] ?? '', $ip_info['country'] ?? '']);
            $location_string = implode(', ', $location_parts);
            if (!empty($ip_info['country'])) {
                $flag_html = '<img src="https://flagsapi.com/' . esc_attr(strtoupper($ip_info['country'])) . '/flat/16.png" alt="" style="margin-right: 5px; vertical-align: middle;">';
                $location_string = $flag_html . $location_string;
            }
            echo '<tr><th>Server Location</th><td>' . $location_string . '</td></tr>';
            if (!empty($ip_info['loc'])) echo '<tr><th>GPS Coordinates</th><td>' . esc_html($ip_info['loc']) . '</td></tr>';
            if (!empty($ip_info['org'])) echo '<tr><th>ISP/Organization</th><td>' . esc_html($ip_info['org']) . '</td></tr>';
            if (!empty($ip_info['timezone'])) echo '<tr><th>Server Timezone</th><td>' . esc_html(str_replace('_', ' ', $ip_info['timezone'])) . '</td></tr>';
            if (!empty($ip_info['postal'])) echo '<tr><th>Postal Code</th><td>' . esc_html($ip_info['postal']) . '</td></tr>';
        }
        echo '</table></div>';
    }

    if (!empty($user_prefs['debug-security-sid'])) {
        echo '<div id="debug-security-sid"><h4>🔐 Debug & Security</h4><table>
        <tr><th>Debug Mode</th><td>' . ($is_debug ? 'On' : 'Off') . '</td></tr>
        <tr><th>HTTPS</th><td>' . $is_ssl . '</td></tr></table></div>';
    }

    if (!empty($user_prefs['file-systems-sid'])) {
        echo '<div id="file-systems-sid"><h4>📂 File System Paths</h4><table>
        <tr><th>ABSPATH</th><td>' . esc_html($ABSPATH) . '</td></tr>
        <tr><th>Content Directory</th><td>' . esc_html($content_dir) . '</td></tr>
        <tr><th>Plugin Directory</th><td>' . esc_html($plugin_dir) . '</td></tr>
        <tr><th>Theme Directory</th><td>' . esc_html($theme_dir) . '</td></tr></table></div>';
    }

    echo '<details style="margin-top: 20px;">
        <summary style="cursor: pointer; font-weight: bold; font-size: 14px; margin-bottom: 10px;">🛠️ Customize Sections</summary>
        <form id="sysinfo-section-toggle" style="margin-top: 10px; padding-left: 10px;">';

    foreach ($default_sections as $section_id => $enabled) {
        $checked = !empty($user_prefs[$section_id]) ? 'checked' : '';
        $label = $section_labels[$section_id] ?? $section_id;
        echo '<label style="display: block; margin-bottom: 5px;">
            <input type="checkbox" class="sysinfo-toggle" data-section="' . esc_attr($section_id) . '" ' . $checked . '> ' . esc_html($label) . '
        </label>';
    }

    echo '</form></details>';
    echo '</div>';
}
?>
