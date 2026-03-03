<?php
/**
 * Test Enhanced Registration Functionality
 *
 * This script tests the enhanced auto-registration features:
 * 1. Conditional registration for logged-in users
 * 2. Auto-population of form fields
 * 3. Proper booking association
 *
 * @package HydraBookingCustomization
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );
}

// Only allow admin users to run this test
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied. Only administrators can run this test.' );
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Enhanced Registration Test</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 20px; }
		.test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
		.success { background-color: #d4edda; border-color: #c3e6cb; }
		.error { background-color: #f8d7da; border-color: #f5c6cb; }
		.info { background-color: #d1ecf1; border-color: #bee5eb; }
		pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
	</style>
</head>
<body>
	<h1>Enhanced Registration Test Results</h1>
	
	<?php
	// Test 1: Check if AutoRegistration class exists and is properly loaded
	echo '<div class="test-section">';
	echo '<h2>Test 1: AutoRegistration Class</h2>';
	if ( class_exists( 'HydraBookingCustomization\\Features\\AutoRegistration' ) ) {
		echo '<p class="success">✓ AutoRegistration class is loaded</p>';
	} else {
		echo '<p class="error">✗ AutoRegistration class is not loaded</p>';
	}
	echo '</div>';
	
	// Test 2: Check if FormAutopopulate class exists and is properly loaded
	echo '<div class="test-section">';
	echo '<h2>Test 2: FormAutopopulate Class</h2>';
	if ( class_exists( 'HydraBookingCustomization\\Features\\FormAutopopulate' ) ) {
		echo '<p class="success">✓ FormAutopopulate class is loaded</p>';
	} else {
		echo '<p class="error">✗ FormAutopopulate class is not loaded</p>';
	}
	echo '</div>';
	
	// Test 3: Check if JavaScript file exists
	echo '<div class="test-section">';
	echo '<h2>Test 3: JavaScript Assets</h2>';
	$js_file = plugin_dir_path( __FILE__ ) . 'assets/js/form-autopopulate.js';
	if ( file_exists( $js_file ) ) {
		echo '<p class="success">✓ form-autopopulate.js file exists</p>';
		echo '<p>File size: ' . filesize( $js_file ) . ' bytes</p>';
	} else {
		echo '<p class="error">✗ form-autopopulate.js file is missing</p>';
	}
	echo '</div>';
	
	// Test 4: Check hooks registration
	echo '<div class="test-section">';
	echo '<h2>Test 4: WordPress Hooks</h2>';
	
	// Check if auto-registration hooks are registered
	global $wp_filter;
	
	if ( isset( $wp_filter['hydra_booking/after_booking_confirmed'] ) ) {
		echo '<p class="success">✓ hydra_booking/after_booking_confirmed hook is registered</p>';
	} else {
		echo '<p class="error">✗ hydra_booking/after_booking_confirmed hook is not registered</p>';
	}
	
	if ( isset( $wp_filter['wp_ajax_nopriv_tfhb_meeting_form_submit'] ) ) {
		echo '<p class="success">✓ AJAX form submission hook is registered</p>';
	} else {
		echo '<p class="error">✗ AJAX form submission hook is not registered</p>';
	}
	
	if ( isset( $wp_filter['wp_enqueue_scripts'] ) ) {
		echo '<p class="success">✓ wp_enqueue_scripts hook is registered</p>';
	} else {
		echo '<p class="error">✗ wp_enqueue_scripts hook is not registered</p>';
	}
	echo '</div>';
	
	// Test 5: Check current user status
	echo '<div class="test-section">';
	echo '<h2>Test 5: Current User Status</h2>';
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		echo '<p class="success">✓ User is logged in</p>';
		echo '<p><strong>User ID:</strong> ' . $current_user->ID . '</p>';
		echo '<p><strong>Username:</strong> ' . $current_user->user_login . '</p>';
		echo '<p><strong>Email:</strong> ' . $current_user->user_email . '</p>';
		echo '<p><strong>Display Name:</strong> ' . $current_user->display_name . '</p>';
		echo '<p class="info">This simulates the logged-in user scenario for auto-population testing.</p>';
	} else {
		echo '<p class="info">User is not logged in - this simulates the guest user scenario.</p>';
	}
	echo '</div>';
	
	// Test 6: Check plugin options
	echo '<div class="test-section">';
	echo '<h2>Test 6: Plugin Configuration</h2>';
	
	$auto_registration_enabled = get_option( 'hbc_auto_registration', true );
	if ( $auto_registration_enabled ) {
		echo '<p class="success">✓ Auto-registration is enabled</p>';
	} else {
		echo '<p class="error">✗ Auto-registration is disabled</p>';
	}
	
	$welcome_email_enabled = get_option( 'hbc_send_welcome_email', true );
	if ( $welcome_email_enabled ) {
		echo '<p class="success">✓ Welcome email is enabled</p>';
	} else {
		echo '<p class="info">Welcome email is disabled</p>';
	}
	echo '</div>';
	
	// Test 7: Database table check
	echo '<div class="test-section">';
	echo '<h2>Test 7: Database Structure</h2>';
	
	global $wpdb;
	
	// Check if attendees table exists
	$attendees_table = $wpdb->prefix . 'tfhb_attendees';
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$attendees_table'" ) === $attendees_table;
	
	if ( $table_exists ) {
		echo '<p class="success">✓ Attendees table exists</p>';
		
		// Check if user_id column exists
		$columns = $wpdb->get_results( "SHOW COLUMNS FROM $attendees_table" );
		$user_id_column_exists = false;
		foreach ( $columns as $column ) {
			if ( $column->Field === 'user_id' ) {
				$user_id_column_exists = true;
				break;
			}
		}
		
		if ( $user_id_column_exists ) {
			echo '<p class="success">✓ user_id column exists in attendees table</p>';
		} else {
			echo '<p class="error">✗ user_id column is missing from attendees table</p>';
		}
	} else {
		echo '<p class="error">✗ Attendees table does not exist</p>';
	}
	echo '</div>';
	
	// Test 8: Simulate form auto-population
	echo '<div class="test-section">';
	echo '<h2>Test 8: Form Auto-population Simulation</h2>';
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$user_data = array(
			'name'  => $current_user->display_name,
			'email' => $current_user->user_email,
			'first_name' => $current_user->first_name,
			'last_name'  => $current_user->last_name,
		);
		
		// If display name is empty, construct from first and last name
		if ( empty( $user_data['name'] ) ) {
			$user_data['name'] = trim( $user_data['first_name'] . ' ' . $user_data['last_name'] );
		}
		
		// If still empty, use username
		if ( empty( $user_data['name'] ) ) {
			$user_data['name'] = $current_user->user_login;
		}
		
		echo '<p class="success">✓ User data prepared for auto-population:</p>';
		echo '<pre>' . print_r( $user_data, true ) . '</pre>';
		echo '<p class="info">This data would be automatically populated in booking form fields.</p>';
	} else {
		echo '<p class="info">User not logged in - no auto-population data available.</p>';
	}
	echo '</div>';
	?>
	
	<div class="test-section info">
		<h2>Next Steps</h2>
		<p>To fully test the enhanced registration functionality:</p>
		<ol>
			<li><strong>For logged-in users:</strong> Create a page with the [hydra_booking] shortcode and test booking while logged in</li>
			<li><strong>For guest users:</strong> Test booking while logged out to verify auto-registration</li>
			<li><strong>Check form auto-population:</strong> Verify that name and email fields are automatically filled for logged-in users</li>
			<li><strong>Verify booking association:</strong> Check that bookings are properly linked to user accounts</li>
		</ol>
		<p><strong>Admin Tools:</strong></p>
		<ul>
			<li><a href="<?php echo admin_url( 'tools.php?page=debug-attendees' ); ?>">Debug Attendee Records</a></li>
			<li><a href="<?php echo admin_url( 'tools.php?page=fix-attendee-user-ids' ); ?>">Fix Attendee User IDs</a></li>
		</ul>
	</div>
	
	<p><a href="<?php echo admin_url(); ?>">← Back to Admin Dashboard</a></p>
</body>
</html>