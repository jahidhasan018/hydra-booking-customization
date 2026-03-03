<?php
/**
 * Test Login Redirect Functionality
 * 
 * This file can be accessed directly to test the login redirect logic.
 * URL: /wp-content/plugins/hydra-booking-customization/test-login-redirect.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_die('Please log in first to test the redirect functionality.');
}

// Load our plugin's autoloader
require_once(__DIR__ . '/vendor/autoload.php');

use HydraBookingCustomization\Core\AccessControl;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user role information
$primary_role = AccessControl::get_user_primary_role($user_id);
$can_access_attendee = AccessControl::can_access_attendee_dashboard($user_id);
$can_access_host = AccessControl::can_access_host_dashboard($user_id);

// Get dashboard page URLs
$attendee_dashboard_id = get_option('hbc_attendee_dashboard_page_id');
$host_dashboard_id = get_option('hbc_host_dashboard_page_id');
$hydra_dashboard_id = get_option('tfhb_dashboard_page_id');

$attendee_dashboard_url = $attendee_dashboard_id ? get_permalink($attendee_dashboard_id) : 'Not configured';
$host_dashboard_url = $host_dashboard_id ? get_permalink($host_dashboard_id) : 'Not configured';
$hydra_dashboard_url = $hydra_dashboard_id ? get_permalink($hydra_dashboard_id) : 'Not configured';

// Simulate the redirect logic
$expected_redirect = 'Unknown';
switch ($primary_role) {
    case 'attendee':
        if ($can_access_attendee) {
            $expected_redirect = home_url('/user-dashboard');
        } else {
            $expected_redirect = home_url() . ' (access denied)';
        }
        break;
    case 'host':
        if ($can_access_host) {
            $expected_redirect = home_url('/host-dashboard');
        } else {
            $expected_redirect = home_url() . ' (access denied)';
        }
        break;
    case 'admin':
        $expected_redirect = admin_url();
        break;
    default:
        $expected_redirect = current_user_can('read') ? admin_url('profile.php') : home_url();
        break;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Redirect Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .info-box { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Login Redirect Test Results</h1>
    
    <div class="info-box">
        <h2>Current User Information</h2>
        <table>
            <tr><th>User ID</th><td><?php echo esc_html($user_id); ?></td></tr>
            <tr><th>Username</th><td><?php echo esc_html($current_user->user_login); ?></td></tr>
            <tr><th>Email</th><td><?php echo esc_html($current_user->user_email); ?></td></tr>
            <tr><th>All Roles</th><td><?php echo esc_html(implode(', ', $current_user->roles)); ?></td></tr>
            <tr><th>Primary Role</th><td><?php echo esc_html($primary_role ?: 'None detected'); ?></td></tr>
        </table>
    </div>
    
    <div class="info-box">
        <h2>Access Permissions</h2>
        <table>
            <tr><th>Can Access Attendee Dashboard</th><td><?php echo $can_access_attendee ? '✅ Yes' : '❌ No'; ?></td></tr>
            <tr><th>Can Access Host Dashboard</th><td><?php echo $can_access_host ? '✅ Yes' : '❌ No'; ?></td></tr>
            <tr><th>Is Administrator</th><td><?php echo current_user_can('manage_options') ? '✅ Yes' : '❌ No'; ?></td></tr>
        </table>
    </div>
    
    <div class="info-box">
        <h2>Dashboard Configuration</h2>
        <table>
            <tr><th>Attendee Dashboard Page ID</th><td><?php echo esc_html($attendee_dashboard_id ?: 'Not set'); ?></td></tr>
            <tr><th>Attendee Dashboard URL</th><td><?php echo esc_html($attendee_dashboard_url); ?></td></tr>
            <tr><th>Host Dashboard Page ID</th><td><?php echo esc_html($host_dashboard_id ?: 'Not set'); ?></td></tr>
            <tr><th>Host Dashboard URL</th><td><?php echo esc_html($host_dashboard_url); ?></td></tr>
            <tr><th>Hydra Dashboard Page ID</th><td><?php echo esc_html($hydra_dashboard_id ?: 'Not set'); ?></td></tr>
            <tr><th>Hydra Dashboard URL</th><td><?php echo esc_html($hydra_dashboard_url); ?></td></tr>
        </table>
    </div>
    
    <div class="info-box <?php echo $expected_redirect !== 'Unknown' ? 'success' : 'error'; ?>">
        <h2>Expected Login Redirect</h2>
        <p><strong>Upon login, this user would be redirected to:</strong></p>
        <p><code><?php echo esc_html($expected_redirect); ?></code></p>
    </div>
    
    <div class="info-box">
        <h2>Test Actions</h2>
        <p>To test the actual redirect functionality:</p>
        <ol>
            <li>Log out of WordPress</li>
            <li>Log back in</li>
            <li>Verify you are redirected to the expected URL shown above</li>
        </ol>
        <p><a href="<?php echo wp_logout_url(home_url()); ?>">Logout to test redirect</a></p>
    </div>
    
    <div class="info-box">
        <h2>Quick Dashboard Links</h2>
        <ul>
            <?php if ($attendee_dashboard_url !== 'Not configured'): ?>
                <li><a href="<?php echo esc_url($attendee_dashboard_url); ?>">Attendee Dashboard</a></li>
            <?php endif; ?>
            <?php if ($host_dashboard_url !== 'Not configured'): ?>
                <li><a href="<?php echo esc_url($host_dashboard_url); ?>">Host Dashboard</a></li>
            <?php endif; ?>
            <?php if ($hydra_dashboard_url !== 'Not configured'): ?>
                <li><a href="<?php echo esc_url($hydra_dashboard_url); ?>">Hydra Dashboard</a></li>
            <?php endif; ?>
            <li><a href="<?php echo admin_url(); ?>">WordPress Admin</a></li>
        </ul>
    </div>
</body>
</html>