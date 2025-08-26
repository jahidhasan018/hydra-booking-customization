<?php
/**
 * Uninstall Script for Hydra Booking Customization
 * 
 * This script cleans up plugin data when the plugin is deleted.
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data
 */
function hbc_uninstall() {
	// Remove plugin options
	$options_to_remove = array(
		'hbc_enable_auto_registration',
		'hbc_send_welcome_email',
		'hbc_attendee_dashboard_page_id',
		'hbc_allow_booking_cancellation',
		'hbc_cancellation_hours_limit',
		'hbc_allow_booking_rescheduling',
		'hbc_rescheduling_hours_limit',
	);

	foreach ( $options_to_remove as $option ) {
		delete_option( $option );
	}

	// Remove attendee role (optional - you might want to keep users)
	// Uncomment the following lines if you want to remove the role and users
	/*
	remove_role( 'hbc_attendee' );
	
	// Get all users with attendee role and delete them
	$attendee_users = get_users( array( 'role' => 'hbc_attendee' ) );
	foreach ( $attendee_users as $user ) {
		wp_delete_user( $user->ID );
	}
	*/

	// Remove attendee dashboard page (optional)
	$dashboard_page_id = get_option( 'hbc_attendee_dashboard_page_id' );
	if ( $dashboard_page_id ) {
		wp_delete_post( $dashboard_page_id, true );
	}

	// Clear any cached data
	wp_cache_flush();
}

// Run uninstall
hbc_uninstall();