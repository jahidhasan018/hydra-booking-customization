<?php
/**
 * Debug Attendees Admin Page
 * 
 * Add this to WordPress admin to debug attendee records
 */

// Add admin menu
add_action('admin_menu', 'debug_attendees_admin_menu');

function debug_attendees_admin_menu() {
    add_management_page(
        'Debug Attendees',
        'Debug Attendees', 
        'manage_options',
        'debug-attendees',
        'debug_attendees_admin_page'
    );
}

function debug_attendees_admin_page() {
    global $wpdb;
    
    echo '<div class="wrap">';
    echo '<h1>Debug Attendees</h1>';
    
    // Get recent attendee records
    $query = "
        SELECT 
            a.id, 
            a.attendee_name, 
            a.email, 
            a.user_id, 
            a.host_id, 
            a.booking_id, 
            a.status as attendee_status,
            b.meeting_dates, 
            b.start_time, 
            b.status as booking_status
        FROM {$wpdb->prefix}tfhb_attendees a 
        LEFT JOIN {$wpdb->prefix}tfhb_bookings b ON a.booking_id = b.id 
        ORDER BY a.id DESC 
        LIMIT 10
    ";
    
    $results = $wpdb->get_results($query);
    
    echo '<h2>Recent Attendee Records</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th>ID</th><th>Name</th><th>Email</th><th>User ID</th><th>Host ID</th><th>Booking ID</th><th>Attendee Status</th><th>Meeting Date</th><th>Start Time</th><th>Booking Status</th>';
    echo '</tr></thead><tbody>';
    
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->attendee_name) . '</td>';
        echo '<td>' . esc_html($row->email) . '</td>';
        echo '<td>' . esc_html($row->user_id ?: 'NULL') . '</td>';
        echo '<td>' . esc_html($row->host_id) . '</td>';
        echo '<td>' . esc_html($row->booking_id) . '</td>';
        echo '<td>' . esc_html($row->attendee_status) . '</td>';
        echo '<td>' . esc_html($row->meeting_dates) . '</td>';
        echo '<td>' . esc_html($row->start_time) . '</td>';
        echo '<td>' . esc_html($row->booking_status) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    
    // Check current user
    $current_user = wp_get_current_user();
    echo '<h2>Current User Info</h2>';
    echo '<p><strong>User ID:</strong> ' . $current_user->ID . '</p>';
    echo '<p><strong>Email:</strong> ' . $current_user->user_email . '</p>';
    echo '<p><strong>Display Name:</strong> ' . $current_user->display_name . '</p>';
    
    // Check if current user has any attendee records
    $user_attendees = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tfhb_attendees WHERE user_id = %d OR email = %s",
        $current_user->ID,
        $current_user->user_email
    ));
    
    echo '<h2>Current User\'s Attendee Records</h2>';
    if (empty($user_attendees)) {
        echo '<p>No attendee records found for current user.</p>';
    } else {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>Attendee ID</th><th>Name</th><th>Email</th><th>User ID</th><th>Host ID</th><th>Booking ID</th><th>Status</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($user_attendees as $attendee) {
            echo '<tr>';
            echo '<td>' . esc_html($attendee->id) . '</td>';
            echo '<td>' . esc_html($attendee->attendee_name) . '</td>';
            echo '<td>' . esc_html($attendee->email) . '</td>';
            echo '<td>' . esc_html($attendee->user_id ?: 'NULL') . '</td>';
            echo '<td>' . esc_html($attendee->host_id) . '</td>';
            echo '<td>' . esc_html($attendee->booking_id) . '</td>';
            echo '<td>' . esc_html($attendee->status) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    echo '</div>';
}
?>