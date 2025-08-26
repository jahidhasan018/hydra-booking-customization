<?php
/**
 * Vue.js Host Dashboard Template
 * 
 * This template provides the container for the Vue.js host dashboard
 * and handles the necessary WordPress integration.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue WordPress i18n script
wp_enqueue_script('wp-i18n');

// Enqueue Vue.js dashboard assets
wp_enqueue_script(
    'hbc-host-dashboard',
    plugin_dir_url(__FILE__) . '../dist/host-dashboard.js',
    ['wp-i18n'],
    '1.0.0',
    true
);

// Set up script translations
wp_set_script_translations('hbc-host-dashboard', 'hydra-booking-customization', plugin_dir_path(__FILE__) . '../languages');

// Add module type attribute to the script
add_filter('script_loader_tag', function($tag, $handle, $src) {
    if ('hbc-host-dashboard' === $handle) {
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
$host_data = [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('hbc_ajax_nonce'),
    'profileNonce' => wp_create_nonce('hbc_update_host_profile'),
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
    'capabilities' => [
        'manage_bookings' => current_user_can('manage_options'),
        'edit_bookings' => current_user_can('edit_posts'),
        'delete_bookings' => current_user_can('delete_posts'),
    ],
    'translations' => [
        // Dashboard common
        'loading' => __('Loading...', 'hydra-booking-customization'),
        'logout' => __('Logout', 'hydra-booking-customization'),
        'refresh' => __('Refresh', 'hydra-booking-customization'),
        'edit_profile' => __('Edit Profile', 'hydra-booking-customization'),
        'profile_settings' => __('Profile Settings', 'hydra-booking-customization'),
        'save' => __('Save', 'hydra-booking-customization'),
        'cancel' => __('Cancel', 'hydra-booking-customization'),
        'close' => __('Close', 'hydra-booking-customization'),
        'confirm' => __('Confirm', 'hydra-booking-customization'),
        'delete' => __('Delete', 'hydra-booking-customization'),
        'view_details' => __('View Details', 'hydra-booking-customization'),
        'not_set' => __('Not set', 'hydra-booking-customization'),
        
        // Host Dashboard specific
        'host_dashboard' => __('Host Dashboard', 'hydra-booking-customization'),
        'manage_meetings_bookings' => __('Manage your meetings, bookings, and join links', 'hydra-booking-customization'),
        'todays_meetings' => __("Today's Meetings", 'hydra-booking-customization'),
        'upcoming' => __('Upcoming', 'hydra-booking-customization'),
        'completed' => __('Completed', 'hydra-booking-customization'),
        'active_links' => __('Active Links', 'hydra-booking-customization'),
        'bookings' => __('Bookings', 'hydra-booking-customization'),
        'join_links' => __('Join Links', 'hydra-booking-customization'),
        'profile' => __('Profile', 'hydra-booking-customization'),
        'join_links_management' => __('Join Links Management', 'hydra-booking-customization'),
        'generate_new_link' => __('Generate New Link', 'hydra-booking-customization'),
        'meeting_history' => __('Meeting History', 'hydra-booking-customization'),
        'no_bookings_found' => __('No bookings found', 'hydra-booking-customization'),
        'no_bookings_match' => __('No bookings match the current criteria.', 'hydra-booking-customization'),
        'meeting' => __('Meeting', 'hydra-booking-customization'),
        'min' => __('min', 'hydra-booking-customization'),
        'host' => __('Host:', 'hydra-booking-customization'),
        'booked' => __('Booked', 'hydra-booking-customization'),
        'id' => __('ID:', 'hydra-booking-customization'),
        'booking_reference' => __('Booking reference', 'hydra-booking-customization'),
        'notes' => __('Notes:', 'hydra-booking-customization'),
        'internal_note' => __('Internal Note:', 'hydra-booking-customization'),
        'attendee_comment' => __('Attendee Comment:', 'hydra-booking-customization'),
        'mark_complete' => __('Mark Complete', 'hydra-booking-customization'),
        'confirm_booking' => __('Confirm Booking', 'hydra-booking-customization'),
        'generate_link' => __('Generate Link', 'hydra-booking-customization'),
        'send_link' => __('Send Link', 'hydra-booking-customization'),
        'logout_confirm' => __('Are you sure you want to logout?', 'hydra-booking-customization'),
        'logout_failed' => __('Logout failed. Please try again.', 'hydra-booking-customization'),
        'profile_updated' => __('Profile updated successfully', 'hydra-booking-customization'),
        
        // Form fields
        'first_name' => __('First Name', 'hydra-booking-customization'),
        'last_name' => __('Last Name', 'hydra-booking-customization'),
        'email' => __('Email', 'hydra-booking-customization'),
        'phone' => __('Phone', 'hydra-booking-customization'),
        'bio' => __('Bio', 'hydra-booking-customization'),
        'timezone' => __('Timezone', 'hydra-booking-customization'),
        
        // Status texts
        'pending' => __('Pending', 'hydra-booking-customization'),
        'confirmed' => __('Confirmed', 'hydra-booking-customization'),
        'completed_status' => __('Completed', 'hydra-booking-customization'),
        'cancelled_status' => __('Cancelled', 'hydra-booking-customization'),
        'canceled_status' => __('Canceled', 'hydra-booking-customization'),
        
        // Error messages
        'error_loading_data' => __('Failed to load dashboard data', 'hydra-booking-customization'),
        'error_loading_stats' => __('Failed to load statistics', 'hydra-booking-customization'),
        'error_updating_profile' => __('Failed to update profile', 'hydra-booking-customization'),
        'error_loading_bookings' => __('Failed to load bookings', 'hydra-booking-customization'),
        
        // Success messages
        'booking_updated' => __('Booking status updated successfully', 'hydra-booking-customization'),
        'link_copied' => __('Link copied to clipboard', 'hydra-booking-customization'),
        'email_sent' => __('Email sent successfully', 'hydra-booking-customization'),
        'link_generated' => __('Join link generated successfully', 'hydra-booking-customization')
    ]
];
?>

<!-- WordPress Data for ES Modules -->
<script>
window.hbcHostData = <?php echo wp_json_encode($host_data); ?>;
</script>

<div class="hbc-vue-dashboard-wrapper">
    <!-- Loading State -->
    <div id="hbc-loading-state" class="hbc-loading-container">
        <div class="hbc-loading-spinner"></div>
        <p><?php _e('Loading dashboard...', 'hydra-booking-customization'); ?></p>
    </div>

    <!-- Vue.js App Container -->
    <div id="host-dashboard-app" class="hbc-vue-app">
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
        const app = document.getElementById('host-dashboard-app');
        const loading = document.getElementById('hbc-loading-state');
        
        if (app && loading) {
            app.style.display = 'block';
            loading.style.display = 'none';
        }
    }, 100);
});
</script>