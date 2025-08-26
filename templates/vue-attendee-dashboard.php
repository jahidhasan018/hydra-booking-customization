<?php
/**
 * Vue.js Attendee Dashboard Template
 * 
 * This template provides the container for the Vue.js attendee dashboard
 * and handles the necessary WordPress integration.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue Vue.js dashboard assets
wp_enqueue_script(
    'hbc-attendee-dashboard',
    plugin_dir_url(__FILE__) . '../dist/attendee-dashboard.js',
    [],
    '1.0.0',
    true
);

// Add module type attribute to the script
add_filter('script_loader_tag', function($tag, $handle, $src) {
    if ('hbc-attendee-dashboard' === $handle) {
        return '<script type="module" src="' . esc_url($src) . '" id="' . $handle . '-js"></script>' . "\n";
    }
    return $tag;
}, 10, 3);

wp_enqueue_style(
    'hbc-vue-dashboard',
    plugin_dir_url(__FILE__) . '../dist/style.css',
    [],
    '1.0.0'
);

// Enqueue toast notification styles
wp_enqueue_style(
    'hbc-toast-notifications',
    plugin_dir_url(__FILE__) . '../assets/css/toast-notifications.css',
    [],
    '1.0.0'
);

// Prepare WordPress data for ES modules
$attendee_data = [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('hbc_ajax_nonce'),
    'profileNonce' => wp_create_nonce('hbc_update_profile'),
    'logoutNonce' => wp_create_nonce('hbc_logout_nonce'),
    'userId' => get_current_user_id(),
    'userEmail' => wp_get_current_user()->user_email,
    'siteUrl' => site_url(),
    'pluginUrl' => plugin_dir_url(__FILE__),
    'restUrl' => rest_url('hydra-booking/v1/'),
    'restNonce' => wp_create_nonce('wp_rest'),
    'logoutUrl' => wp_logout_url(home_url()),
    'currentUser' => [
        'id' => get_current_user_id(),
        'email' => wp_get_current_user()->user_email,
        'display_name' => wp_get_current_user()->display_name,
        'first_name' => get_user_meta(get_current_user_id(), 'first_name', true),
        'last_name' => get_user_meta(get_current_user_id(), 'last_name', true),
    ],
    'dateFormat' => get_option('date_format'),
    'timeFormat' => get_option('time_format'),
    'timezone' => wp_timezone_string(),
    'testMode' => ( HBC_TEST_MODE_STATUS === 'active' ),
    'translations' => [
        'loading' => __('Loading...', 'hydra-booking-customization'),
        'error' => __('An error occurred', 'hydra-booking-customization'),
        'success' => __('Success', 'hydra-booking-customization'),
        'confirm' => __('Are you sure?', 'hydra-booking-customization'),
        'cancel' => __('Cancel', 'hydra-booking-customization'),
        'save' => __('Save', 'hydra-booking-customization'),
        'edit' => __('Edit', 'hydra-booking-customization'),
        'delete' => __('Delete', 'hydra-booking-customization'),
        'view' => __('View', 'hydra-booking-customization'),
        'close' => __('Close', 'hydra-booking-customization'),
    ]
];
?>

<!-- WordPress Data for ES Modules -->
<script>
window.hbcAttendeeData = <?php echo wp_json_encode($attendee_data); ?>;
</script>

<div class="hbc-vue-dashboard-wrapper">
    <!-- Loading State -->
    <div id="hbc-loading-state" class="hbc-loading-container">
        <div class="hbc-loading-spinner"></div>
        <p><?php _e('Loading dashboard...', 'hydra-booking-customization'); ?></p>
    </div>

    <!-- Vue.js App Container -->
    <div id="attendee-dashboard-app" class="hbc-vue-app">
        <!-- Vue.js component will be mounted here -->
    </div>

    <!-- Fallback for JavaScript disabled -->
    <noscript>
        <div class="hbc-no-js-fallback">
            <h3><?php _e('JavaScript Required', 'hydra-booking-customization'); ?></h3>
            <p><?php _e('This dashboard requires JavaScript to function properly. Please enable JavaScript in your browser and refresh the page.', 'hydra-booking-customization'); ?></p>
        </div>
    </noscript>
</div>

<style>
.hbc-vue-dashboard-wrapper {
    min-height: 400px;
    position: relative;
}

.hbc-loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    text-align: center;
}

.hbc-loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    animation: hbc-spin 1s linear infinite;
    margin-bottom: 16px;
}

@keyframes hbc-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.hbc-no-js-fallback {
    padding: 20px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    text-align: center;
}

.hbc-no-js-fallback h3 {
    color: #dc3545;
    margin-bottom: 10px;
}

.hbc-vue-app {
    width: 100%;
}

/* Hide loading state when Vue app is mounted */
.hbc-vue-app:not([style*="display: none"]) ~ #hbc-loading-state {
    display: none;
}
</style>

<script>
// Show Vue app and hide loading state when Vue is ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Vue to mount
    setTimeout(function() {
        const app = document.getElementById('attendee-dashboard-app');
        const loading = document.getElementById('hbc-loading-state');
        
        if (app && loading) {
            app.style.display = 'block';
            loading.style.display = 'none';
        }
    }, 100);
});
</script>