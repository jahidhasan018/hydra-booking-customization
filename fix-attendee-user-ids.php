<?php
/**
 * Fix Attendee User IDs
 * 
 * This script fixes attendee records that don't have user_id populated
 * by matching them with existing users by email address.
 */

// Add admin menu
add_action('admin_menu', 'fix_attendee_user_ids_admin_menu');

function fix_attendee_user_ids_admin_menu() {
    add_management_page(
        'Fix Attendee User IDs',
        'Fix Attendee User IDs', 
        'manage_options',
        'fix-attendee-user-ids',
        'fix_attendee_user_ids_admin_page'
    );
}

function fix_attendee_user_ids_admin_page() {
    global $wpdb;
    
    echo '<div class="wrap">';
    echo '<h1>Fix Attendee User IDs</h1>';
    
    // Handle form submission
    if (isset($_POST['fix_user_ids']) && wp_verify_nonce($_POST['_wpnonce'], 'fix_attendee_user_ids')) {
        $results = fix_attendee_user_ids_process();
        
        echo '<div class="notice notice-success"><p>';
        echo 'Fixed ' . $results['fixed'] . ' attendee records. ';
        echo 'Skipped ' . $results['skipped'] . ' records (already had user_id or no matching user found).';
        echo '</p></div>';
    }
    
    // Get attendees without user_id
    $attendees_without_user_id = $wpdb->get_results(
        "SELECT a.id, a.attendee_name, a.email, a.user_id, a.booking_id, b.meeting_dates 
         FROM {$wpdb->prefix}tfhb_attendees a 
         LEFT JOIN {$wpdb->prefix}tfhb_bookings b ON a.booking_id = b.id 
         WHERE a.user_id IS NULL OR a.user_id = 0 
         ORDER BY a.id DESC"
    );
    
    echo '<h2>Attendees Without User ID (' . count($attendees_without_user_id) . ')</h2>';
    
    if (empty($attendees_without_user_id)) {
        echo '<p>All attendee records have user_id populated!</p>';
    } else {
        echo '<p>The following attendee records don\'t have user_id populated:</p>';
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>Attendee ID</th><th>Name</th><th>Email</th><th>User ID</th><th>Booking ID</th><th>Meeting Date</th><th>Matching User</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($attendees_without_user_id as $attendee) {
            $matching_user = get_user_by('email', $attendee->email);
            
            echo '<tr>';
            echo '<td>' . esc_html($attendee->id) . '</td>';
            echo '<td>' . esc_html($attendee->attendee_name) . '</td>';
            echo '<td>' . esc_html($attendee->email) . '</td>';
            echo '<td>' . esc_html($attendee->user_id ?: 'NULL') . '</td>';
            echo '<td>' . esc_html($attendee->booking_id) . '</td>';
            echo '<td>' . esc_html($attendee->meeting_dates) . '</td>';
            echo '<td>';
            if ($matching_user) {
                echo 'User ID: ' . $matching_user->ID . ' (' . esc_html($matching_user->display_name) . ')';
            } else {
                echo 'No matching user found';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        echo '<form method="post">';
        wp_nonce_field('fix_attendee_user_ids');
        echo '<p><input type="submit" name="fix_user_ids" class="button button-primary" value="Fix User IDs" onclick="return confirm(\'Are you sure you want to fix the user IDs? This will update the database.\');"></p>';
        echo '</form>';
    }
    
    // Show current user info for debugging
    $current_user = wp_get_current_user();
    echo '<h2>Current User Debug Info</h2>';
    echo '<p><strong>User ID:</strong> ' . $current_user->ID . '</p>';
    echo '<p><strong>Email:</strong> ' . $current_user->user_email . '</p>';
    echo '<p><strong>Display Name:</strong> ' . $current_user->display_name . '</p>';
    
    // Check current user's attendee records
    $current_user_attendees = $wpdb->get_results($wpdb->prepare(
        "SELECT a.*, b.meeting_dates, b.start_time, b.status as booking_status 
         FROM {$wpdb->prefix}tfhb_attendees a 
         LEFT JOIN {$wpdb->prefix}tfhb_bookings b ON a.booking_id = b.id 
         WHERE a.user_id = %d OR a.email = %s",
        $current_user->ID,
        $current_user->user_email
    ));
    
    echo '<h3>Current User\'s Attendee Records (' . count($current_user_attendees) . ')</h3>';
    if (empty($current_user_attendees)) {
        echo '<p>No attendee records found for current user.</p>';
    } else {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>Attendee ID</th><th>Name</th><th>Email</th><th>User ID</th><th>Booking ID</th><th>Meeting Date</th><th>Start Time</th><th>Status</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($current_user_attendees as $attendee) {
            echo '<tr>';
            echo '<td>' . esc_html($attendee->id) . '</td>';
            echo '<td>' . esc_html($attendee->attendee_name) . '</td>';
            echo '<td>' . esc_html($attendee->email) . '</td>';
            echo '<td>' . esc_html($attendee->user_id ?: 'NULL') . '</td>';
            echo '<td>' . esc_html($attendee->booking_id) . '</td>';
            echo '<td>' . esc_html($attendee->meeting_dates) . '</td>';
            echo '<td>' . esc_html($attendee->start_time) . '</td>';
            echo '<td>' . esc_html($attendee->booking_status) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    echo '</div>';
}

function fix_attendee_user_ids_process() {
    global $wpdb;
    
    $fixed = 0;
    $skipped = 0;
    
    // Get all attendees without user_id
    $attendees = $wpdb->get_results(
        "SELECT id, email FROM {$wpdb->prefix}tfhb_attendees WHERE user_id IS NULL OR user_id = 0"
    );
    
    foreach ($attendees as $attendee) {
        // Try to find matching user by email
        $user = get_user_by('email', $attendee->email);
        
        if ($user) {
            // Update attendee record with user_id
            $updated = $wpdb->update(
                $wpdb->prefix . 'tfhb_attendees',
                array('user_id' => $user->ID),
                array('id' => $attendee->id),
                array('%d'),
                array('%d')
            );
            
            if ($updated !== false) {
                $fixed++;
            } else {
                $skipped++;
            }
        } else {
            $skipped++;
        }
    }
    
    return array(
        'fixed' => $fixed,
        'skipped' => $skipped
    );
}
?>