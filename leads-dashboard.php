<?php
/**
 * Plugin Name: Digital Realm Lead Tracker
 * Plugin URI: https://digitalrealm.ca
 * Description: Manage leads and website performance with a comprehensive dashboard.
 * Version: 2.5
 * Author: Digital Realm
 */

defined('ABSPATH') || exit;

// === Constants ===
define('LEADS_DASHBOARD_PATH', plugin_dir_path(__FILE__));
define('LEADS_DASHBOARD_URL', plugin_dir_url(__FILE__));

// === Includes ===
$api_path = LEADS_DASHBOARD_PATH . 'includes/clickup-api.php';
$ajax_path = LEADS_DASHBOARD_PATH . 'includes/ajax-handlers.php';

if (file_exists($api_path)) {
    require_once $api_path;
} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>❌ Missing file: <code>includes/clickup-api.php</code></p></div>';
    });
}

if (file_exists($ajax_path)) {
    require_once $ajax_path;
} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>❌ Missing file: <code>includes/ajax-handlers.php</code></p></div>';
    });
}

// === Conditionally Enqueue Scripts & Styles ===
add_action('wp_enqueue_scripts', 'conditionally_enqueue_leads_dashboard_assets');
add_action('admin_enqueue_scripts', 'conditionally_enqueue_leads_dashboard_assets');

function conditionally_enqueue_leads_dashboard_assets() {
    if (is_admin()) {
        // Always load in admin area if needed
        enqueue_leads_dashboard_assets();
        return;
    }

    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'leads_dashboard')) {
        enqueue_leads_dashboard_assets();
    }
}

function enqueue_leads_dashboard_assets() {
    // ✅ Load Bootstrap only when needed
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.3', true);

    // Load custom dashboard JS
    $dashboard_js  = LEADS_DASHBOARD_URL . 'assets/js/dashboard.js';
    $dashboard_path = LEADS_DASHBOARD_PATH . 'assets/js/dashboard.js';

    if (file_exists($dashboard_path)) {
        $version = filemtime($dashboard_path); // ✅ Cache-busting
        wp_enqueue_script('leads-dashboard-js', $dashboard_js, ['jquery'], $version, true);

        wp_localize_script('leads-dashboard-js', 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('leads_dashboard_nonce'),
        ]);
    } else {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>❌ Missing file: <code>assets/js/dashboard.js</code></p></div>';
        });
    }
}

// === Shortcode ===
add_shortcode('leads_dashboard', 'render_leads_dashboard');

function render_leads_dashboard() {
    ob_start();
    $dashboard_path = LEADS_DASHBOARD_PATH . 'templates/dashboard.php';

    if (file_exists($dashboard_path)) {
        include $dashboard_path;
    } else {
        echo '<p style="color:red;">❌ <code>templates/dashboard.php</code> not found.</p>';
    }

    return ob_get_clean();
}
