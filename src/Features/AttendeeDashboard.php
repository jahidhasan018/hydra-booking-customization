<?php
/**
 * Attendee Dashboard Feature
 *
 * @package HydraBookingCustomization\Features
 */

namespace HydraBookingCustomization\Features;

use HydraBookingCustomization\Core\CacheManager;

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
		add_action( 'wp_ajax_hbc_cancel_booking', array( $this, 'ajax_cancel_booking' ) );
		add_action( 'wp_ajax_hbc_reschedule_booking', array( $this, 'ajax_reschedule_booking' ) );
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
				<h2><?php printf( __( 'Welcome, %s!', 'hydra-booking-customization' ), esc_html( $user->display_name ) ); ?></h2>
				<p><?php _e( 'Manage your bookings and profile from this dashboard.', 'hydra-booking-customization' ); ?></p>
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
								
								<?php if ( $this->can_cancel_booking( $booking ) ) : ?>
									<button class="button hbc-cancel-booking" data-booking-id="<?php echo esc_attr( $booking->booking_id ); ?>">
										<?php _e( 'Cancel', 'hydra-booking-customization' ); ?>
									</button>
								<?php endif; ?>
								
								<?php if ( $this->can_reschedule_booking( $booking ) ) : ?>
									<button class="button hbc-reschedule-booking" data-booking-id="<?php echo esc_attr( $booking->booking_id ); ?>">
										<?php _e( 'Reschedule', 'hydra-booking-customization' ); ?>
									</button>
								<?php endif; ?>
								
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
	 * Get user bookings (cached).
	 *
	 * @since 1.0.0
	 * @param int    $user_id User ID.
	 * @param string $type    Booking type (upcoming, past, all).
	 * @return array
	 */
	private function get_user_bookings( $user_id, $type = 'all' ) {
		$user_id   = absint( $user_id );
		$cache_key = 'attendee_bookings_' . $user_id . '_' . sanitize_key( $type );

		return CacheManager::remember( $cache_key, CacheManager::TTLS['bookings'], function () use ( $user_id, $type ) {
			return $this->fetch_user_bookings( $user_id, $type );
		} );
	}

	/**
	 * Fetch user bookings from the database (uncached).
	 *
	 * @since 1.1.0
	 * @param int    $user_id User ID.
	 * @param string $type    Booking type (upcoming, past, all).
	 * @return array
	 */
	private function fetch_user_bookings( $user_id, $type = 'all' ) {
		global $wpdb;
		
		$attendees_table = $wpdb->prefix . 'tfhb_attendees';
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		$meetings_table = $wpdb->prefix . 'tfhb_meetings';
		$hosts_table = $wpdb->prefix . 'tfhb_hosts';
		$meta_table = $wpdb->prefix . 'tfhb_booking_meta';
		
		$current_time = current_time( 'mysql' );
		
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
				h.email as host_email,
				mstart.value as meeting_started_at
			FROM {$attendees_table} a
			INNER JOIN {$bookings_table} b ON a.booking_id = b.id
			LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id
			LEFT JOIN {$hosts_table} h ON a.host_id = h.id
			LEFT JOIN {$meta_table} mstart ON b.id = mstart.booking_id AND mstart.meta_key = 'hbc_meeting_started_at'
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
		
		return $wpdb->get_results( $wpdb->prepare( $query, $query_params ) );
	}

	/**
	 * Check if booking can be cancelled.
	 *
	 * @param object $booking Booking object.
	 * @return bool
	 */
	private function can_cancel_booking( $booking ) {
		if ( get_option( 'hbc_enable_test_mode', false ) ) {
			return true;
		}
		
		if ( ! get_option( 'hbc_allow_booking_cancellation', true ) ) {
			return false;
		}

		// Allow cancellation if booking is in the future.
		$booking_datetime = $booking->meeting_dates . ' ' . $booking->start_time;
		$booking_time = strtotime( $booking_datetime );
		$current_time = current_time( 'timestamp' );
		
		$status = $booking->attendee_status ?? $booking->booking_status ?? '';
		return $booking_time > $current_time && in_array( $status, array( 'confirmed', 'pending' ), true );
	}

	/**
	 * Check if booking can be rescheduled.
	 *
	 * @param object $booking Booking object.
	 * @return bool
	 */
	private function can_reschedule_booking( $booking ) {
		if ( get_option( 'hbc_enable_test_mode', false ) ) {
			return true;
		}

		if ( ! get_option( 'hbc_allow_booking_rescheduling', true ) ) {
			return false;
		}

		// Allow rescheduling if booking is in the future.
		$booking_datetime = $booking->meeting_dates . ' ' . $booking->start_time;
		$booking_time = strtotime( $booking_datetime );
		$current_time = current_time( 'timestamp' );
		
		$status = $booking->attendee_status ?? $booking->booking_status ?? '';
		return $booking_time > $current_time && in_array( $status, array( 'confirmed', 'pending' ), true );
	}

	/**
	 * Validate an attendee AJAX request.
	 *
	 * Consolidates repeated nonce and login checks common to all
	 * attendee dashboard AJAX handlers.
	 *
	 * @since 1.1.0
	 * @param string $nonce_action Nonce action name. Default 'hbc_ajax_nonce'.
	 * @param string $nonce_field  POST field name for nonce. Default 'nonce'.
	 * @return int|false User ID on success, false on failure (response already sent).
	 */
	private function validate_attendee_ajax_request( $nonce_action = 'hbc_ajax_nonce', $nonce_field = 'nonce' ) {
		check_ajax_referer( $nonce_action, $nonce_field );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'User not logged in.', 'hydra-booking-customization' ) );
			return false;
		}

		return get_current_user_id();
	}

	/**
	 * AJAX handler to get attendee bookings.
	 */
	public function ajax_get_attendee_bookings() {
		$user_id = $this->validate_attendee_ajax_request();
		if ( ! $user_id ) {
			return;
		}
		$type = sanitize_text_field( $_POST['type'] ?? 'all' );
		
		$bookings = $this->get_user_bookings( $user_id, $type );
		
		wp_send_json_success( $bookings );
	}

	/**
	 * AJAX handler to cancel booking.
	 */
	public function ajax_cancel_booking() {
		$user_id = $this->validate_attendee_ajax_request();
		if ( ! $user_id ) {
			return;
		}
		
		$booking_id = intval( $_POST['booking_id'] ?? 0 );

		if ( ! $booking_id ) {
			wp_send_json_error( __( 'Invalid booking ID', 'hydra-booking-customization' ) );
		}
		
		// Verify booking belongs to user.
		global $wpdb;
		$attendees_table = $wpdb->prefix . 'tfhb_attendees';
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		
		$booking = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT b.*, a.id as attendee_id 
				FROM {$bookings_table} b 
				INNER JOIN {$attendees_table} a ON b.id = a.booking_id 
				WHERE b.id = %d AND a.user_id = %d",
				$booking_id,
				$user_id
			)
		);
		
		if ( ! $booking ) {
			wp_send_json_error( __( 'Booking not found', 'hydra-booking-customization' ) );
		}
		
		if ( ! $this->can_cancel_booking( $booking ) ) {
			wp_send_json_error( __( 'This booking cannot be cancelled', 'hydra-booking-customization' ) );
		}
		
		// Update booking status.
		$updated = $wpdb->update(
			$bookings_table,
			array( 'status' => 'cancelled' ),
			array( 'id' => $booking_id ),
			array( '%s' ),
			array( '%d' )
		);
		
		if ( $updated !== false ) {
			// Invalidate caches.
			CacheManager::invalidate_attendee( $user_id );

			// Send cancellation notification.
			do_action( 'hbc_booking_cancelled', $booking_id, $user_id );
			
			wp_send_json_success( __( 'Booking cancelled successfully', 'hydra-booking-customization' ) );
		} else {
			wp_send_json_error( __( 'Failed to cancel booking', 'hydra-booking-customization' ) );
		}
	}

	/**
	 * AJAX handler to reschedule booking.
	 */
	public function ajax_reschedule_booking() {
		$user_id = $this->validate_attendee_ajax_request();
		if ( ! $user_id ) {
			return;
		}
		
		$booking_id = intval( $_POST['booking_id'] ?? 0 );
		$new_date = sanitize_text_field( $_POST['new_date'] ?? '' );
		$new_time = sanitize_text_field( $_POST['new_time'] ?? '' );
		$duration = intval( $_POST['duration'] ?? 30 );
		$reason = sanitize_textarea_field( $_POST['reason'] ?? '' );
		$notify_host = filter_var( $_POST['notify_host'] ?? true, FILTER_VALIDATE_BOOLEAN );
		$send_confirmation = filter_var( $_POST['send_confirmation'] ?? true, FILTER_VALIDATE_BOOLEAN );
		
		if ( ! $booking_id || ! $new_date || ! $new_time ) {
			wp_send_json_error( __( 'Missing required fields', 'hydra-booking-customization' ) );
		}
		
		// Verify booking belongs to user.
		global $wpdb;
		$attendees_table = $wpdb->prefix . 'tfhb_attendees';
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		$hosts_table = $wpdb->prefix . 'tfhb_hosts';
		
		$booking = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT b.*, a.id as attendee_id, a.email as email, h.email as host_email 
				FROM {$bookings_table} b 
				INNER JOIN {$attendees_table} a ON b.id = a.booking_id 
				LEFT JOIN {$hosts_table} h ON a.host_id = h.id
				WHERE b.id = %d AND a.user_id = %d",
				$booking_id,
				$user_id
			)
		);
		
		if ( ! $booking ) {
			wp_send_json_error( __( 'Booking not found', 'hydra-booking-customization' ) );
		}
		
		if ( ! $this->can_reschedule_booking( $booking ) ) {
			wp_send_json_error( __( 'This booking cannot be rescheduled', 'hydra-booking-customization' ) );
		}
		
		// Update booking date and time.
		$updated = $wpdb->update(
			$bookings_table,
			array( 
				'meeting_dates' => $new_date,
				'start_time' => $new_time,
				'status' => 'pending' // Reset to pending for host approval
			),
			array( 'id' => $booking_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
		
		if ( $updated !== false ) {
			// Invalidate caches.
			CacheManager::invalidate_attendee( $user_id );

			// Send notifications.
			if ( $notify_host && ! empty( $booking->host_email ) ) {
				$subject = 'Booking Rescheduled';
				$message = sprintf( "A booking has been rescheduled to %s at %s.\n\nReason: %s", $new_date, $new_time, $reason ?: 'Not provided' );
				wp_mail( $booking->host_email, $subject, $message );
			}

			if ( $send_confirmation && ! empty( $booking->email ) ) {
				$subject = 'Your Booking is Rescheduled';
				$message = sprintf( "Your booking has been rescheduled to %s at %s. Awaiting host approval.", $new_date, $new_time );
				wp_mail( $booking->email, $subject, $message );
			}

			// Keep existing hook in case other systems depend on it.
			do_action( 'hbc_booking_rescheduled', $booking_id, $user_id, $new_date, $new_time );
			
			wp_send_json_success( __( 'Booking rescheduled successfully. Awaiting host approval.', 'hydra-booking-customization' ) );
		} else {
			wp_send_json_error( __( 'Failed to reschedule booking', 'hydra-booking-customization' ) );
		}
	}

	/**
	 * AJAX handler to update profile.
	 */
	public function ajax_update_profile() {
		$user_id = $this->validate_attendee_ajax_request( 'hbc_update_profile', 'hbc_profile_nonce' );
		if ( ! $user_id ) {
			return;
		}
		
		$user_data = array(
            'ID'          => $user_id,
            'first_name'  => sanitize_text_field( $_POST['first_name'] ?? '' ),
            'last_name'   => sanitize_text_field( $_POST['last_name'] ?? '' ),
            'description' => sanitize_textarea_field( $_POST['bio'] ?? '' ),
        );
		
		$updated = wp_update_user( $user_data );
		
		update_user_meta( $user_id, 'phone', sanitize_text_field( $_POST['phone'] ?? '' ) );

		if ( is_wp_error( $updated ) ) {
			wp_send_json_error( $updated->get_error_message() );
		} else {
			// Invalidate caches.
			CacheManager::invalidate_attendee( $user_id );

			wp_send_json_success( __( 'Profile updated successfully', 'hydra-booking-customization' ) );
		}
	}

	/**
	 * AJAX handler to change password.
	 */
	public function ajax_change_password() {
		$user_id = $this->validate_attendee_ajax_request( 'hbc_change_password', 'hbc_password_nonce' );
		if ( ! $user_id ) {
			return;
		}
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
	 * Get attendee statistics (cached).
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_attendee_stats( $user_id ) {
		$user_id = absint( $user_id );

		return CacheManager::remember( 'attendee_stats_' . $user_id, CacheManager::TTLS['stats'], function () use ( $user_id ) {
			return $this->compute_attendee_stats( $user_id );
		} );
	}

	/**
	 * Compute attendee statistics from the database (uncached).
	 *
	 * @since 1.1.0
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function compute_attendee_stats( $user_id ) {
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
		$user_id = $this->validate_attendee_ajax_request();
		if ( ! $user_id ) {
			return;
		}
		$stats = $this->get_attendee_stats( $user_id );
		
		wp_send_json_success( $stats );
	}
}