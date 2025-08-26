<?php
/**
 * Auto Registration Feature
 *
 * @package HydraBookingCustomization\Features
 */

namespace HydraBookingCustomization\Features;

/**
 * Auto Registration Feature Class
 */
class AutoRegistration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Debug: Log that auto-registration is initialized.
		error_log( 'HBC AutoRegistration: Initialized with hooks' );
		
		// Ensure user_id column exists in attendees table
		$this->ensure_user_id_column();
		
		// Hook into Hydra Booking after booking confirmation (correct hook name).
		add_action( 'hydra_booking/after_booking_confirmed', array( $this, 'auto_create_attendee_account' ), 10, 1 );
		
		// Hook into AJAX form submission for testing.
		add_action( 'wp_ajax_nopriv_tfhb_meeting_form_submit', array( $this, 'intercept_booking_confirmation' ), 15 );
		add_action( 'wp_ajax_tfhb_meeting_form_submit', array( $this, 'intercept_booking_confirmation' ), 15 );
	}

	/**
	 * Automatically create attendee account after booking confirmation.
	 *
	 * @param object $attendee_booking Attendee booking object.
	 */
	public function auto_create_attendee_account( $attendee_booking ) {
		// Debug: Log that the method is being called.
		error_log( 'HBC AutoRegistration: auto_create_attendee_account called with: ' . print_r( $attendee_booking, true ) );
		
		// Check if auto-registration is enabled.
		if ( ! get_option( 'hbc_auto_registration', true ) ) {
			error_log( 'HBC AutoRegistration: Auto-registration is disabled in auto_create_attendee_account' );
			return;
		}

		// Validate attendee booking object.
		if ( ! $attendee_booking || ! isset( $attendee_booking->email ) ) {
			error_log( 'HBC AutoRegistration: Invalid attendee booking object' );
			return;
		}

		$attendee_email = sanitize_email( $attendee_booking->email );
		$attendee_name = isset( $attendee_booking->attendee_name ) ? sanitize_text_field( $attendee_booking->attendee_name ) : '';

		// Check if user is currently logged in
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			error_log( 'HBC AutoRegistration: User is logged in with ID: ' . $current_user->ID );
			
			// Associate booking with logged-in user
			if ( isset( $attendee_booking->id ) ) {
				$this->update_attendee_user_id( $attendee_booking->id, $current_user->ID );
			}
			
			// Fire action for logged-in user booking
			do_action( 'hbc_after_logged_in_user_booking', $current_user->ID, $attendee_booking );
			return;
		}

		// Check if user already exists (for non-logged-in users).
		if ( email_exists( $attendee_email ) ) {
			error_log( 'HBC AutoRegistration: User already exists with email: ' . $attendee_email );
			
			// Update existing user with attendee ID if not already set.
			$existing_user = get_user_by( 'email', $attendee_email );
			if ( $existing_user && isset( $attendee_booking->id ) ) {
				$this->update_attendee_user_id( $attendee_booking->id, $existing_user->ID );
			}
			return;
		}

		// Create new attendee account only for non-logged-in users.
		$user_id = $this->create_attendee_user( $attendee_email, $attendee_name );

		if ( $user_id && ! is_wp_error( $user_id ) ) {
			// Update attendee with user ID.
			if ( isset( $attendee_booking->id ) ) {
				$this->update_attendee_user_id( $attendee_booking->id, $user_id );
			}

			// Send welcome email.
			if ( get_option( 'hbc_send_welcome_email', true ) ) {
				$this->send_welcome_email( $user_id, $attendee_booking );
			}

			// Fire action for other plugins to hook into.
			do_action( 'hbc_after_auto_registration', $user_id, $attendee_booking );

			error_log( 'HBC AutoRegistration: Successfully created user with ID: ' . $user_id );
		} else {
			error_log( 'HBC AutoRegistration: Failed to create user for email: ' . $attendee_email );
		}
	}

	/**
	 * Intercept booking confirmation to handle auto registration.
	 */
	public function intercept_booking_confirmation() {
		// Debug: Log that the method is being called.
		error_log( 'HBC AutoRegistration: intercept_booking_confirmation called' );
		
		// Check if user is logged in - skip auto-registration for logged-in users
		if ( is_user_logged_in() ) {
			error_log( 'HBC AutoRegistration: User is logged in, skipping auto-registration in intercept' );
			return;
		}
		
		// Get booking data from POST.
		$booking_data = $this->sanitize_booking_data( $_POST );
		
		if ( empty( $booking_data['attendee_email'] ) ) {
			return;
		}

		// Check if auto-registration is enabled.
		if ( ! get_option( 'hbc_auto_registration', true ) ) {
			error_log( 'HBC AutoRegistration: Auto-registration is disabled' );
			return;
		}

		$attendee_email = sanitize_email( $booking_data['attendee_email'] );
		
		if ( ! is_email( $attendee_email ) ) {
			return;
		}

		// Check if user already exists.
		$existing_user = get_user_by( 'email', $attendee_email );
		if ( $existing_user ) {
			error_log( 'HBC AutoRegistration: User already exists, skipping registration' );
			return;
		}

		// Get attendee name from booking data.
		$attendee_name = isset( $booking_data['attendee_name'] ) ? sanitize_text_field( $booking_data['attendee_name'] ) : '';
		
		// Create new attendee account only for non-logged-in users.
		$user_id = $this->create_attendee_user( $attendee_email, $attendee_name );
		
		if ( $user_id && ! is_wp_error( $user_id ) ) {
			// Create a mock attendee booking object for the welcome email.
			$mock_attendee_booking = (object) array(
				'email' => $attendee_email,
				'attendee_name'  => $attendee_name,
			);
			
			// Send welcome email.
			if ( get_option( 'hbc_send_welcome_email', true ) ) {
				$this->send_welcome_email( $user_id, $mock_attendee_booking );
			}
		}
	}

	/**
	 * Get attendee email from booking data.
	 *
	 * @param array $booking_data Booking data.
	 * @param int   $booking_id   Booking ID.
	 * @return string
	 */
	private function get_attendee_email_from_booking( $booking_data, $booking_id ) {
		// Try to get email from booking data array.
		if ( isset( $booking_data['attendee_email'] ) ) {
			return sanitize_email( $booking_data['attendee_email'] );
		}

		// Try to get from database if booking ID is provided.
		if ( $booking_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'tfhb_bookings';
			
			$booking = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT attendee_email FROM {$table_name} WHERE id = %d",
					$booking_id
				)
			);
			
			if ( $booking && ! empty( $booking->attendee_email ) ) {
				return sanitize_email( $booking->attendee_email );
			}
		}

		return '';
	}

	/**
	 * Create attendee user account.
	 *
	 * @param string $attendee_email Attendee email.
	 * @param string $attendee_name  Attendee name.
	 * @return int|WP_Error User ID on success, WP_Error on failure.
	 */
	private function create_attendee_user( $attendee_email, $attendee_name = '' ) {
		// Generate username from email.
		$username = $this->generate_username_from_email( $attendee_email );
		
		// Generate random password.
		$password = wp_generate_password( 12, false );
		
		// Parse attendee name.
		$name_parts = $this->parse_attendee_name( $attendee_name );
		
		// Create user data.
		$user_data = array(
			'user_login'   => $username,
			'user_email'   => $attendee_email,
			'user_pass'    => $password,
			'first_name'   => $name_parts['first_name'],
			'last_name'    => $name_parts['last_name'],
			'display_name' => trim( $name_parts['first_name'] . ' ' . $name_parts['last_name'] ),
			'role'         => 'hbc_attendee',
		);

		// Create the user.
		$user_id = wp_insert_user( $user_data );
		
		if ( ! is_wp_error( $user_id ) ) {
			// Store the generated password for welcome email.
			update_user_meta( $user_id, 'hbc_generated_password', $password );
			update_user_meta( $user_id, 'hbc_auto_registered', true );
			update_user_meta( $user_id, 'hbc_registration_date', current_time( 'mysql' ) );
		}

		return $user_id;
	}

	/**
	 * Generate username from email.
	 *
	 * @param string $email Email address.
	 * @return string
	 */
	private function generate_username_from_email( $email ) {
		$username = sanitize_user( substr( $email, 0, strpos( $email, '@' ) ) );
		
		// Ensure username is unique.
		$original_username = $username;
		$counter = 1;
		
		while ( username_exists( $username ) ) {
			$username = $original_username . $counter;
			$counter++;
		}

		return $username;
	}

	/**
	 * Parse attendee name into first and last name.
	 *
	 * @param string $attendee_name Full attendee name.
	 * @return array
	 */
	private function parse_attendee_name( $attendee_name ) {
		$first_name = '';
		$last_name = '';

		if ( ! empty( $attendee_name ) ) {
			$name_parts = explode( ' ', trim( $attendee_name ), 2 );
			$first_name = sanitize_text_field( $name_parts[0] );
			$last_name = isset( $name_parts[1] ) ? sanitize_text_field( $name_parts[1] ) : '';
		}

		return array(
			'first_name' => $first_name,
			'last_name'  => $last_name,
		);
	}

	/**
	 * Ensure user_id column exists in attendees table.
	 */
	private function ensure_user_id_column() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'tfhb_attendees';
		
		error_log( 'HBC AutoRegistration: Checking for user_id column in ' . $table_name );
		
		// Check if user_id column exists
		$column_exists = $wpdb->get_results( 
			$wpdb->prepare( 
				"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'user_id'",
				DB_NAME,
				$table_name
			)
		);
		
		// Add user_id column if it doesn't exist
		if ( empty( $column_exists ) ) {
			error_log( 'HBC AutoRegistration: user_id column not found, adding it...' );
			$result = $wpdb->query( 
				"ALTER TABLE {$table_name} ADD COLUMN user_id INT(11) NULL AFTER email"
			);
			if ( $result !== false ) {
				error_log( 'HBC AutoRegistration: Successfully added user_id column to attendees table' );
			} else {
				error_log( 'HBC AutoRegistration: Failed to add user_id column: ' . $wpdb->last_error );
			}
		} else {
			error_log( 'HBC AutoRegistration: user_id column already exists' );
		}
	}

	/**
	 * Update attendee with user ID.
	 *
	 * @param int $attendee_id Attendee ID.
	 * @param int $user_id     User ID.
	 */
	private function update_attendee_user_id( $attendee_id, $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'tfhb_attendees';
		
		$wpdb->update(
			$table_name,
			array( 'user_id' => $user_id ),
			array( 'id' => $attendee_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Update booking with user ID.
	 *
	 * @param int $booking_id Booking ID.
	 * @param int $user_id    User ID.
	 */
	private function update_booking_user_id( $booking_id, $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'tfhb_bookings';
		
		$wpdb->update(
			$table_name,
			array( 'user_id' => $user_id ),
			array( 'id' => $booking_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Send welcome email to new attendee.
	 *
	 * @param int    $user_id         User ID.
	 * @param object $attendee_booking Attendee booking object.
	 */
	private function send_welcome_email( $user_id, $attendee_booking ) {
		$user = get_user_by( 'id', $user_id );
		$password = get_user_meta( $user_id, 'hbc_generated_password', true );
		
		if ( ! $user || ! $password ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: Site name */
			__( 'Welcome to %s - Your Account Details', 'hydra-booking-customization' ),
			get_bloginfo( 'name' )
		);

		$dashboard_url = get_permalink( get_option( 'hbc_attendee_dashboard_page_id' ) );
		
		$message = sprintf(
			/* translators: 1: User display name, 2: Site name, 3: Username, 4: Password, 5: Dashboard URL */
			__( 'Hello %1$s,

Welcome to %2$s! An account has been automatically created for you.

Your login details:
Username: %3$s
Password: %4$s

You can access your attendee dashboard here: %5$s

We recommend changing your password after your first login.

Best regards,
The %2$s Team', 'hydra-booking-customization' ),
			$user->display_name,
			get_bloginfo( 'name' ),
			$user->user_login,
			$password,
			$dashboard_url
		);

		wp_mail( $attendee_booking->email, $subject, $message );
		
		// Clear the stored password for security.
		delete_user_meta( $user_id, 'hbc_generated_password' );
	}

	/**
	 * Sanitize booking data.
	 *
	 * @param array $data Raw booking data.
	 * @return array
	 */
	private function sanitize_booking_data( $data ) {
		$sanitized = array();
		
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_booking_data( $value );
			} else {
				$sanitized[ $key ] = sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}
}