<?php
/**
 * Attendee Dashboard Feature
 *
 * @package HydraBookingCustomization\Features
 */

namespace HydraBookingCustomization\Features;

use HydraBookingCustomization\Core\AccessControl;

/**
 * Attendee Dashboard Feature Class
 */
class AttendeeDashboard {

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
		// Note: Shortcode registration is now handled by Vue.js template in main plugin file
		// add_shortcode( 'hbc_attendee_dashboard', array( $this, 'render_dashboard_shortcode' ) );
		add_action( 'wp_ajax_hbc_get_attendee_bookings', array( $this, 'ajax_get_attendee_bookings' ) );
		add_action( 'wp_ajax_hbc_get_attendee_stats', array( $this, 'ajax_get_attendee_stats' ) );

		add_action( 'wp_ajax_hbc_update_profile', array( $this, 'ajax_update_profile' ) );
		add_action( 'wp_ajax_hbc_change_password', array( $this, 'ajax_change_password' ) );

	}

	/**
	 * Render dashboard shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_dashboard_shortcode( $atts ) {
		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			return $this->render_login_form();
		}

		$current_user = wp_get_current_user();
		
		// Check if user has attendee role or is admin.
		if ( ! in_array( 'hbc_attendee', $current_user->roles, true ) && ! current_user_can( 'manage_options' ) ) {
			return '<div class="hbc-error">' . __( 'Access denied. This dashboard is for attendees only.', 'hydra-booking-customization' ) . '</div>';
		}

		ob_start();
		$this->render_dashboard_content( $current_user );
		return ob_get_clean();
	}

	/**
	 * Render login form for non-logged-in users.
	 *
	 * @return string
	 */
	private function render_login_form() {
		// Don't redirect back to current page to avoid loops
		$login_url = wp_login_url();
		
		return sprintf(
			'<div class="hbc-login-required">
				<h3>%s</h3>
				<p>%s</p>
				<a href="%s" class="button button-primary">%s</a>
			</div>',
			__( 'Login Required', 'hydra-booking-customization' ),
			__( 'Please log in to access your attendee dashboard.', 'hydra-booking-customization' ),
			esc_url( $login_url ),
			__( 'Login', 'hydra-booking-customization' )
		);
	}

	/**
	 * Render dashboard content.
	 *
	 * @param WP_User $user Current user.
	 */
	private function render_dashboard_content( $user ) {
		?>
		<div id="hbc-attendee-dashboard" class="hbc-dashboard">
			<div class="hbc-dashboard-header">
				<div class="hbc-header-content">
					<div class="hbc-header-text">
						<h2><?php printf( __( 'Welcome, %s!', 'hydra-booking-customization' ), esc_html( $user->display_name ) ); ?></h2>
						<p><?php _e( 'Manage your bookings and profile from this dashboard.', 'hydra-booking-customization' ); ?></p>
					</div>
					<div class="hbc-header-actions">
						<button type="button" class="hbc-logout-btn button" onclick="hbcLogout()"><?php _e( 'Logout', 'hydra-booking-customization' ); ?></button>
					</div>
				</div>
			</div>

			<div class="hbc-dashboard-nav">
				<ul class="hbc-nav-tabs">
					<li><a href="#bookings" class="hbc-nav-tab active" data-tab="bookings"><?php _e( 'My Bookings', 'hydra-booking-customization' ); ?></a></li>
					<li><a href="#profile" class="hbc-nav-tab" data-tab="profile"><?php _e( 'Profile', 'hydra-booking-customization' ); ?></a></li>
					<li><a href="#history" class="hbc-nav-tab" data-tab="history"><?php _e( 'Booking History', 'hydra-booking-customization' ); ?></a></li>
				</ul>
			</div>

			<div class="hbc-dashboard-content">
				<div id="hbc-tab-bookings" class="hbc-tab-content active">
					<?php $this->render_bookings_tab( $user ); ?>
				</div>

				<div id="hbc-tab-profile" class="hbc-tab-content">
					<?php $this->render_profile_tab( $user ); ?>
				</div>

				<div id="hbc-tab-history" class="hbc-tab-content">
					<?php $this->render_history_tab( $user ); ?>
				</div>
			</div>
		</div>
		
		<script>
		function hbcLogout() {
			if (confirm('<?php _e( 'Are you sure you want to logout?', 'hydra-booking-customization' ); ?>')) {
				fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: 'action=hbc_logout&nonce=<?php echo wp_create_nonce( 'hbc_logout_nonce' ); ?>'
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						window.location.href = data.data.login_url;
					} else {
						alert('<?php _e( 'Logout failed. Please try again.', 'hydra-booking-customization' ); ?>');
					}
				})
				.catch(error => {
					console.error('Error:', error);
					alert('<?php _e( 'Logout failed. Please try again.', 'hydra-booking-customization' ); ?>');
				});
			}
		}
		</script>
		<?php
	}

	/**
	 * Render bookings tab.
	 *
	 * @param WP_User $user Current user.
	 */
	private function render_bookings_tab( $user ) {
		$upcoming_bookings = $this->get_user_bookings( $user->ID, 'upcoming' );
		?>
		<div class="hbc-bookings-section">
			<h3><?php _e( 'Upcoming Bookings', 'hydra-booking-customization' ); ?></h3>
			
			<?php if ( empty( $upcoming_bookings ) ) : ?>
				<div class="hbc-no-bookings">
					<p><?php _e( 'You have no upcoming bookings.', 'hydra-booking-customization' ); ?></p>
				</div>
			<?php else : ?>
				<div class="hbc-bookings-list">
					<?php foreach ( $upcoming_bookings as $booking ) : ?>
						<div class="hbc-booking-card" data-booking-id="<?php echo esc_attr( $booking->booking_id ); ?>">
							<div class="hbc-booking-header">
								<h4><?php echo esc_html( $booking->meeting_title ); ?></h4>
								<span class="hbc-booking-status status-<?php echo esc_attr( $booking->attendee_status ?? $booking->booking_status ?? 'pending' ); ?>">
									<?php echo esc_html( ucfirst( $booking->attendee_status ?? $booking->booking_status ?? 'pending' ) ); ?>
								</span>
							</div>
							
							<div class="hbc-booking-details">
								<div class="hbc-booking-time">
									<strong><?php _e( 'Date & Time:', 'hydra-booking-customization' ); ?></strong>
									<?php 
									$booking_datetime = $booking->meeting_dates . ' ' . $booking->start_time;
									echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $booking_datetime ) ) ); 
									?>
								</div>
								
								<?php if ( ! empty( $booking->meeting_description ) ) : ?>
									<div class="hbc-booking-description">
										<strong><?php _e( 'Description:', 'hydra-booking-customization' ); ?></strong>
										<?php echo wp_kses_post( $booking->meeting_description ); ?>
									</div>
								<?php endif; ?>
								
								<div class="hbc-booking-host">
									<strong><?php _e( 'Host:', 'hydra-booking-customization' ); ?></strong>
									<?php echo esc_html( trim( $booking->host_first_name . ' ' . $booking->host_last_name ) ); ?>
								</div>
							</div>
							
							<div class="hbc-booking-actions">
								<?php 
								// Allow other plugins to add custom actions before default actions
								do_action( 'hbc_booking_actions_before', $booking );
								?>
								

								
								<?php 
								// Allow other plugins to add custom actions after default actions
								do_action( 'hbc_booking_actions_after', $booking );
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render profile tab.
	 *
	 * @param WP_User $user Current user.
	 */
	private function render_profile_tab( $user ) {
		?>
		<div class="hbc-profile-section">
			<h3><?php _e( 'Profile Information', 'hydra-booking-customization' ); ?></h3>
			
			<form id="hbc-profile-form" class="hbc-form">
				<?php wp_nonce_field( 'hbc_update_profile', 'hbc_profile_nonce' ); ?>
				
				<div class="hbc-form-row">
					<div class="hbc-form-group">
						<label for="first_name"><?php _e( 'First Name', 'hydra-booking-customization' ); ?></label>
						<input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" required>
					</div>
					
					<div class="hbc-form-group">
						<label for="last_name"><?php _e( 'Last Name', 'hydra-booking-customization' ); ?></label>
						<input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $user->last_name ); ?>">
					</div>
				</div>
				
				<div class="hbc-form-group">
					<label for="user_email"><?php _e( 'Email Address', 'hydra-booking-customization' ); ?></label>
					<input type="email" id="user_email" name="user_email" value="<?php echo esc_attr( $user->user_email ); ?>" required>
				</div>
				
				<div class="hbc-form-group">
					<label for="description"><?php _e( 'Bio', 'hydra-booking-customization' ); ?></label>
					<textarea id="description" name="description" rows="4"><?php echo esc_textarea( $user->description ); ?></textarea>
				</div>
				
				<div class="hbc-form-actions">
					<button type="submit" class="button button-primary"><?php _e( 'Update Profile', 'hydra-booking-customization' ); ?></button>
				</div>
			</form>
			
			<div class="hbc-password-section">
				<h4><?php _e( 'Change Password', 'hydra-booking-customization' ); ?></h4>
				<form id="hbc-password-form" class="hbc-form">
					<?php wp_nonce_field( 'hbc_change_password', 'hbc_password_nonce' ); ?>
					
					<div class="hbc-form-group">
						<label for="current_password"><?php _e( 'Current Password', 'hydra-booking-customization' ); ?></label>
						<input type="password" id="current_password" name="current_password" required>
					</div>
					
					<div class="hbc-form-group">
						<label for="new_password"><?php _e( 'New Password', 'hydra-booking-customization' ); ?></label>
						<input type="password" id="new_password" name="new_password" required>
					</div>
					
					<div class="hbc-form-group">
						<label for="confirm_password"><?php _e( 'Confirm New Password', 'hydra-booking-customization' ); ?></label>
						<input type="password" id="confirm_password" name="confirm_password" required>
					</div>
					
					<div class="hbc-form-actions">
						<button type="submit" class="button button-primary"><?php _e( 'Change Password', 'hydra-booking-customization' ); ?></button>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Render history tab.
	 *
	 * @param WP_User $user Current user.
	 */
	private function render_history_tab( $user ) {
		$past_bookings = $this->get_user_bookings( $user->ID, 'past' );
		?>
		<div class="hbc-history-section">
			<h3><?php _e( 'Booking History', 'hydra-booking-customization' ); ?></h3>
			
			<?php if ( empty( $past_bookings ) ) : ?>
				<div class="hbc-no-bookings">
					<p><?php _e( 'You have no past bookings.', 'hydra-booking-customization' ); ?></p>
				</div>
			<?php else : ?>
				<div class="hbc-bookings-list">
					<?php foreach ( $past_bookings as $booking ) : ?>
						<div class="hbc-booking-card past-booking">
							<div class="hbc-booking-header">
								<h4><?php echo esc_html( $booking->meeting_title ); ?></h4>
								<span class="hbc-booking-status status-<?php echo esc_attr( $booking->attendee_status ?? $booking->booking_status ?? 'completed' ); ?>">
									<?php echo esc_html( ucfirst( $booking->attendee_status ?? $booking->booking_status ?? 'completed' ) ); ?>
								</span>
							</div>
							
							<div class="hbc-booking-details">
								<div class="hbc-booking-time">
									<strong><?php _e( 'Date & Time:', 'hydra-booking-customization' ); ?></strong>
									<?php 
									$booking_datetime = $booking->meeting_dates . ' ' . $booking->start_time;
									echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $booking_datetime ) ) ); 
									?>
								</div>
								
								<div class="hbc-booking-host">
									<strong><?php _e( 'Host:', 'hydra-booking-customization' ); ?></strong>
									<?php echo esc_html( trim( $booking->host_first_name . ' ' . $booking->host_last_name ) ); ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get user bookings.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    Booking type (upcoming, past, all).
	 * @return array
	 */
	private function get_user_bookings( $user_id, $type = 'all' ) {
		global $wpdb;
		
		$attendees_table = $wpdb->prefix . 'tfhb_attendees';
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		$meetings_table = $wpdb->prefix . 'tfhb_meetings';
		$hosts_table = $wpdb->prefix . 'tfhb_hosts';
		
		$current_time = current_time( 'mysql' );
		
		// Get testing mode setting and ensure it's a boolean
		$testing_mode = ( HBC_TEST_MODE_STATUS === 'active' );

		// Build the base query with proper joins and validation
		$query = "
			SELECT 
				a.id as attendee_id,
				a.attendee_name,
				a.email,
				a.status as attendee_status,
				a.payment_status,
				a.created_at as booking_created,
				b.id as booking_id,
				b.meeting_id,
				b.meeting_dates,
				b.start_time,
				b.end_time,
				b.status as booking_status,
				b.booking_type,
				b.meeting_locations,
				m.title as meeting_title,
				m.description as meeting_description,
				m.duration,
				m.attendee_can_cancel,
				m.attendee_can_reschedule,
				h.first_name as host_first_name,
				h.last_name as host_last_name,
				h.email as host_email
			FROM {$attendees_table} a
			INNER JOIN {$bookings_table} b ON a.booking_id = b.id
			LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id
			LEFT JOIN {$hosts_table} h ON a.host_id = h.id
			WHERE a.user_id = %d
				AND b.id IS NOT NULL
				AND b.meeting_dates IS NOT NULL
				AND b.meeting_dates != ''
				AND b.start_time IS NOT NULL
				AND b.start_time != ''
		";
		
		$query_params = array( $user_id );
		
		// Add time-based filtering
		switch ( $type ) {
			case 'upcoming':
				$query .= " AND CONCAT(b.meeting_dates, ' ', b.start_time) > %s";
				$query .= " AND (a.status = 'confirmed' OR b.status = 'confirmed')";
				$query .= " AND a.status NOT IN ('completed', 'cancelled', 'canceled')";
				$query .= " AND b.status NOT IN ('completed', 'cancelled', 'canceled')";
				$query_params[] = $current_time;
				$order_by = 'ORDER BY b.meeting_dates ASC, b.start_time ASC';
				break;
			case 'past':
				$query .= " AND CONCAT(b.meeting_dates, ' ', b.start_time) < %s";
				$query_params[] = $current_time;
				$order_by = 'ORDER BY b.meeting_dates DESC, b.start_time DESC';
				break;
			default:
				$order_by = 'ORDER BY b.meeting_dates DESC, b.start_time DESC';
				break;
		}
		
		$query .= " {$order_by}";
		
		$results = $wpdb->get_results( $wpdb->prepare( $query, $query_params ) );
		
		// Add testing mode information to each booking
		foreach ( $results as $booking ) {
			$booking->testing_mode = $testing_mode;
		}
		
		return $results;
	}





	/**
	 * AJAX handler to get attendee bookings.
	 */
	public function ajax_get_attendee_bookings() {
		check_ajax_referer( 'hbc_ajax_nonce', 'nonce' );
		
		// Verify attendee access
		AccessControl::verify_attendee_ajax_access();
		
		$user_id = get_current_user_id();
		$type = sanitize_text_field( $_POST['type'] ?? 'all' );
		
		$bookings = $this->get_user_bookings( $user_id, $type );
		
		wp_send_json_success( $bookings );
	}





	/**
	 * AJAX handler to update profile.
	 */
	public function ajax_update_profile() {
		check_ajax_referer( 'hbc_update_profile', 'hbc_profile_nonce' );
		
		// Verify attendee access
		AccessControl::verify_attendee_ajax_access();
		
		$user_id = get_current_user_id();
		
		$user_data = array(
            'ID'          => $user_id,
            'first_name'  => sanitize_text_field( $_POST['first_name'] ?? '' ),
            'last_name'   => sanitize_text_field( $_POST['last_name'] ?? '' ),
            'user_email'  => sanitize_email( $_POST['email'] ?? $_POST['user_email'] ?? '' ),
            'description' => sanitize_textarea_field( $_POST['bio'] ?? $_POST['description'] ?? '' ),
        );
        
        // Handle phone number as user meta
        $phone = sanitize_text_field( $_POST['phone'] ?? '' );
        update_user_meta( $user_id, 'billing_phone', $phone );
        
        // Handle password change if provided
        if ( !empty( $_POST['new_password'] ) && !empty( $_POST['current_password'] ) ) {
            $current_password = sanitize_text_field( $_POST['current_password'] );
            $new_password = sanitize_text_field( $_POST['new_password'] );
            
            // Verify current password
            $user = get_user_by( 'id', $user_id );
            if ( wp_check_password( $current_password, $user->user_pass, $user_id ) ) {
                wp_set_password( $new_password, $user_id );
            } else {
                wp_send_json_error( __( 'Current password is incorrect', 'hydra-booking-customization' ) );
                return;
            }
        }
		
		$updated = wp_update_user( $user_data );
		
		if ( is_wp_error( $updated ) ) {
			wp_send_json_error( $updated->get_error_message() );
		} else {
			wp_send_json_success( __( 'Profile updated successfully', 'hydra-booking-customization' ) );
		}
	}

	/**
	 * AJAX handler to change password.
	 */
	public function ajax_change_password() {
		check_ajax_referer( 'hbc_change_password', 'hbc_password_nonce' );
		
		// Verify attendee access
		AccessControl::verify_attendee_ajax_access();
		
		$user_id = get_current_user_id();
		$current_password = sanitize_text_field( $_POST['current_password'] ?? '' );
		$new_password = sanitize_text_field( $_POST['new_password'] ?? '' );
		$confirm_password = sanitize_text_field( $_POST['confirm_password'] ?? '' );
		
		// Validate current password
		$user = get_user_by( 'id', $user_id );
		if ( ! $user || ! wp_check_password( $current_password, $user->user_pass, $user_id ) ) {
			wp_send_json_error( __( 'Current password is incorrect', 'hydra-booking-customization' ) );
		}
		
		// Validate new passwords match
		if ( $new_password !== $confirm_password ) {
			wp_send_json_error( __( 'New passwords do not match', 'hydra-booking-customization' ) );
		}
		
		// Validate password strength
		if ( strlen( $new_password ) < 8 ) {
			wp_send_json_error( __( 'Password must be at least 8 characters long', 'hydra-booking-customization' ) );
		}
		
		// Update password
		wp_set_password( $new_password, $user_id );
		
		wp_send_json_success( __( 'Password changed successfully', 'hydra-booking-customization' ) );
	}



	/**
	 * Redirect non-attendees from dashboard page.
	 */
	public function redirect_non_attendees() {
		$dashboard_page_id = get_option( 'hbc_attendee_dashboard_page_id' );
		
		if ( ! is_page( $dashboard_page_id ) || ! is_user_logged_in() ) {
			return;
		}
		
		$current_user = wp_get_current_user();
		
		// Allow admins and attendees.
		if ( current_user_can( 'manage_options' ) || in_array( 'hbc_attendee', $current_user->roles, true ) ) {
			return;
		}
		
		// Show access denied message instead of redirecting
		return;
	}

	/**
	 * Get attendee statistics.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_attendee_stats( $user_id ) {
		global $wpdb;
		
		$attendees_table = $wpdb->prefix . 'tfhb_attendees';
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		
		$current_date = current_time( 'Y-m-d' );
		$current_datetime = current_time( 'Y-m-d H:i:s' );
		
		// Total bookings (only count valid bookings with complete data)
		$total_bookings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM {$attendees_table} a 
				INNER JOIN {$bookings_table} b ON a.booking_id = b.id 
				WHERE a.user_id = %d
					AND b.id IS NOT NULL
					AND b.meeting_dates IS NOT NULL
					AND b.meeting_dates != ''
					AND b.start_time IS NOT NULL
					AND b.start_time != ''",
				$user_id
			)
		);
		
		// Upcoming bookings (confirmed only, with valid data)
		$upcoming_bookings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM {$attendees_table} a 
				INNER JOIN {$bookings_table} b ON a.booking_id = b.id 
				WHERE a.user_id = %d 
					AND b.id IS NOT NULL
					AND b.meeting_dates IS NOT NULL
					AND b.meeting_dates != ''
					AND b.start_time IS NOT NULL
					AND b.start_time != ''
					AND CONCAT(b.meeting_dates, ' ', b.start_time) > %s 
					AND (a.status = 'confirmed' OR b.status = 'confirmed')
					AND a.status NOT IN ('completed', 'cancelled', 'canceled')
					AND b.status NOT IN ('completed', 'cancelled', 'canceled')",
				$user_id,
				$current_datetime
			)
		);
		
		// Completed bookings (with valid data)
		$completed_bookings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM {$attendees_table} a 
				INNER JOIN {$bookings_table} b ON a.booking_id = b.id 
				WHERE a.user_id = %d 
					AND b.id IS NOT NULL
					AND b.meeting_dates IS NOT NULL
					AND b.meeting_dates != ''
					AND b.start_time IS NOT NULL
					AND b.start_time != ''
					AND (b.status = 'completed' OR CONCAT(b.meeting_dates, ' ', b.end_time) < %s)",
				$user_id,
				$current_datetime
			)
		);
		
		// Cancelled bookings (with valid data)
		$cancelled_bookings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM {$attendees_table} a 
				INNER JOIN {$bookings_table} b ON a.booking_id = b.id 
				WHERE a.user_id = %d 
					AND b.id IS NOT NULL
					AND b.meeting_dates IS NOT NULL
					AND b.meeting_dates != ''
					AND b.start_time IS NOT NULL
					AND b.start_time != ''
					AND b.status = 'cancelled'",
				$user_id
			)
		);
		
		return array(
			'total_bookings'     => intval( $total_bookings ),
			'upcoming_bookings'  => intval( $upcoming_bookings ),
			'completed_bookings' => intval( $completed_bookings ),
			'cancelled_bookings' => intval( $cancelled_bookings ),
		);
	}

	/**
	 * AJAX: Get attendee stats.
	 */
	public function ajax_get_attendee_stats() {
		check_ajax_referer( 'hbc_ajax_nonce', 'nonce' );
		
		// Verify attendee access
		AccessControl::verify_attendee_ajax_access();
		
		$user_id = get_current_user_id();
		$stats = $this->get_attendee_stats( $user_id );
		
		wp_send_json_success( $stats );
	}
}