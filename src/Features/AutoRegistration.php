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
		
		// Handle redirect after auto-registration.
		add_action( 'wp_loaded', array( $this, 'handle_post_registration_redirect' ) );
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
			
			// Get existing user and attach booking to their account
			$existing_user = get_user_by( 'email', $attendee_email );
			if ( $existing_user && isset( $attendee_booking->id ) ) {
				$this->update_attendee_user_id( $attendee_booking->id, $existing_user->ID );
				
				// Auto-login existing user and redirect to cart
				$this->auto_login_user( $existing_user->ID );
				$this->redirect_to_cart();
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

			// Auto-login the newly created user
			$this->auto_login_user( $user_id );

			// Send welcome email with enhanced content.
			if ( get_option( 'hbc_send_welcome_email', true ) ) {
				$this->send_enhanced_welcome_email( $user_id, $attendee_booking );
			}

			// Redirect to cart page after successful registration
			$this->redirect_to_cart();

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
		
		// Validate email format
		if ( ! is_email( $attendee_email ) ) {
			error_log( 'HBC AutoRegistration: Invalid email format: ' . $attendee_email );
			return;
		}

		// Check if user already exists.
		$existing_user = get_user_by( 'email', $attendee_email );
		if ( $existing_user ) {
			error_log( 'HBC AutoRegistration: User already exists, auto-logging in and redirecting to cart' );
			
			// Auto-login existing user and redirect to cart
			$this->auto_login_user( $existing_user->ID );
			$this->redirect_to_cart();
			return;
		}

		// Get attendee name from booking data.
		$attendee_name = isset( $booking_data['attendee_name'] ) ? sanitize_text_field( $booking_data['attendee_name'] ) : '';
		
		// Create new attendee account only for non-logged-in users.
		$user_id = $this->create_attendee_user( $attendee_email, $attendee_name );
		
		if ( $user_id && ! is_wp_error( $user_id ) ) {
			// Auto-login the newly created user
			$this->auto_login_user( $user_id );
			
			// Create a mock attendee booking object for the welcome email.
			$mock_attendee_booking = (object) array(
				'email' => $attendee_email,
				'attendee_name'  => $attendee_name,
			);
			
			// Send enhanced welcome email.
			if ( get_option( 'hbc_send_welcome_email', true ) ) {
				$this->send_enhanced_welcome_email( $user_id, $mock_attendee_booking );
			}
			
			// Redirect to cart page after successful registration
			$this->redirect_to_cart();
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
			// Store the generated password for welcome email (using temporary meta key).
			update_user_meta( $user_id, '_hbc_temp_password', $password );
			update_user_meta( $user_id, 'hbc_auto_registered', true );
			update_user_meta( $user_id, 'hbc_registration_date', current_time( 'mysql' ) );
			
			// Log successful user creation with security details
			error_log( 'HBC AutoRegistration: User created with ID ' . $user_id . ', password properly hashed by WordPress' );
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
	 * Send enhanced welcome email with booking details.
	 *
	 * @param int    $user_id User ID.
	 * @param object $attendee_booking Attendee booking object.
	 */
	public function send_enhanced_welcome_email( $user_id, $attendee_booking ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return;
		}

		// Get the stored password.
		$password = get_user_meta( $user_id, '_hbc_temp_password', true );
		if ( ! $password ) {
			return;
		}

		$site_name = get_bloginfo( 'name' );
		$dashboard_url = home_url( '/attendee-dashboard/' );
		$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url();
		
		$subject = sprintf( __( 'Welcome to %s - Your Account Details', 'hydra-booking-customization' ), $site_name );
		
		$message = sprintf(
			__( "Hello %s,\n\nWelcome to %s! Your account has been created successfully and you have been automatically logged in.\n\n=== Your Login Details ===\nUsername: %s\nPassword: %s\n\n=== Quick Links ===\n• Login Page: %s\n• Your Dashboard: %s\n• Shopping Cart: %s\n\n=== What's Next? ===\nYou can now:\n• View and manage your bookings\n• Update your profile information\n• Complete your current booking in the cart\n\nFor security reasons, we recommend changing your password after your first login.\n\nIf you have any questions, please don't hesitate to contact us.\n\nThank you for choosing %s!\n\nBest regards,\nThe %s Team", 'hydra-booking-customization' ),
			$user->display_name,
			$site_name,
			$user->user_login,
			$password,
			wp_login_url(),
			$dashboard_url,
			$cart_url,
			$site_name,
			$site_name
		);

		// Set headers for better email formatting
		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>'
		);

		$email_sent = wp_mail( $user->user_email, $subject, $message, $headers );
		
		if ( $email_sent ) {
			error_log( 'HBC AutoRegistration: Enhanced welcome email sent successfully to ' . $user->user_email );
		} else {
			error_log( 'HBC AutoRegistration: Failed to send enhanced welcome email to ' . $user->user_email );
		}

		// Clear the temporary password for security.
		delete_user_meta( $user_id, '_hbc_temp_password' );
	}

	/**
	 * Automatically log in a user by ID.
	 *
	 * @param int $user_id User ID to log in.
	 */
	public function auto_login_user( $user_id ) {
		// Validate user ID
		if ( ! $user_id || is_wp_error( $user_id ) ) {
			error_log( 'HBC AutoRegistration: Invalid user ID for auto-login: ' . print_r( $user_id, true ) );
			return false;
		}

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			error_log( 'HBC AutoRegistration: User not found for auto-login with ID: ' . $user_id );
			return false;
		}

		// Check if user is already logged in
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			if ( $current_user->ID === $user_id ) {
				error_log( 'HBC AutoRegistration: User is already logged in with ID: ' . $user_id );
				return true;
			}
		}

		// Set authentication cookies for secure session management
		wp_clear_auth_cookie();
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );
		
		// Update user's last login time
		update_user_meta( $user_id, 'last_login', current_time( 'mysql' ) );
		
		// Fire action for other plugins to hook into
		do_action( 'hbc_user_auto_logged_in', $user_id );
		do_action( 'wp_login', $user->user_login, $user );
		
		error_log( 'HBC AutoRegistration: Successfully auto-logged in user with ID: ' . $user_id );
		return true;
	}

	/**
	 * Redirect user to cart page after successful registration/login.
	 */
	public function redirect_to_cart() {
		// Check if WooCommerce is active
		if ( ! function_exists( 'wc_get_cart_url' ) ) {
			error_log( 'HBC AutoRegistration: WooCommerce not active, redirecting to home page' );
			$redirect_url = home_url();
		} else {
			$redirect_url = wc_get_cart_url();
		}
		
		// Allow other plugins to modify the redirect URL
		$redirect_url = apply_filters( 'hbc_auto_registration_redirect_url', $redirect_url );
		
		// Only redirect if we're in an AJAX context or appropriate hook
		if ( wp_doing_ajax() || did_action( 'wp_ajax_nopriv_tfhb_meeting_form_submit' ) || did_action( 'wp_ajax_tfhb_meeting_form_submit' ) ) {
			// For AJAX requests, we'll set a transient to handle redirect on next page load
			set_transient( 'hbc_redirect_after_registration_' . get_current_user_id(), $redirect_url, 300 ); // 5 minutes
			error_log( 'HBC AutoRegistration: Set redirect transient for cart: ' . $redirect_url );
		} else {
			// Direct redirect for non-AJAX contexts
			error_log( 'HBC AutoRegistration: Redirecting to cart: ' . $redirect_url );
			wp_safe_redirect( $redirect_url );
			exit;
		}
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

	/**
	 * Handle post-registration redirect using transient.
	 */
	public function handle_post_registration_redirect() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		$redirect_url = get_transient( 'hbc_redirect_after_registration_' . $user_id );

		if ( $redirect_url ) {
			// Delete the transient to prevent multiple redirects
			delete_transient( 'hbc_redirect_after_registration_' . $user_id );
			
			// Only redirect if we're not already on the target page
			if ( ! is_admin() && ! wp_doing_ajax() && $redirect_url !== $_SERVER['REQUEST_URI'] ) {
				error_log( 'HBC AutoRegistration: Executing post-registration redirect to: ' . $redirect_url );
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}
}