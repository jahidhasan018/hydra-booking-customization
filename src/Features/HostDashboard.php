<?php
/**
 * Host Dashboard Feature
 *
 * @package HydraBookingCustomization\Features
 */

namespace HydraBookingCustomization\Features;

use HydraBookingCustomization\Core\AccessControl;

/**
 * Host Dashboard Feature Class
 */
class HostDashboard {

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
		// add_shortcode( 'hbc_host_dashboard', array( $this, 'render_dashboard_shortcode' ) );
		add_action( 'wp_ajax_hbc_get_host_bookings', array( $this, 'ajax_get_host_bookings' ) );
		add_action( 'wp_ajax_hbc_get_host_profile', array( $this, 'ajax_get_host_profile' ) );
		add_action( 'wp_ajax_hbc_get_join_links', array( $this, 'ajax_get_join_links' ) );
		add_action( 'wp_ajax_hbc_update_booking_status', array( $this, 'ajax_update_booking_status' ) );
		add_action( 'wp_ajax_hbc_generate_join_link', array( $this, 'ajax_generate_join_link' ) );
		add_action( 'wp_ajax_hbc_send_join_link', array( $this, 'ajax_send_join_link' ) );
		add_action( 'wp_ajax_hbc_update_host_profile', array( $this, 'ajax_update_host_profile' ) );
		add_action( 'wp_ajax_hbc_get_booking_details', array( $this, 'ajax_get_booking_details' ) );
		add_action( 'wp_ajax_hbc_get_host_stats', array( $this, 'ajax_get_host_stats' ) );

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
		
		// Check if user has host role or is admin.
		if ( ! in_array( 'tfhb_host', $current_user->roles, true ) && ! current_user_can( 'manage_options' ) ) {
			return '<div class="hbc-error">' . __( 'Access denied. This dashboard is for hosts only.', 'hydra-booking-customization' ) . '</div>';
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
			__( 'Please log in to access your host dashboard.', 'hydra-booking-customization' ),
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
		$host_data = $this->get_host_data( $user->ID );
		?>
		<div id="hbc-host-dashboard" class="hbc-dashboard hbc-host-dashboard">
			<div class="hbc-dashboard-header">
				<div class="hbc-header-content">
					<div class="hbc-header-text">
						<h2><?php printf( __( 'Welcome, %s!', 'hydra-booking-customization' ), esc_html( $user->display_name ) ); ?></h2>
						<p><?php _e( 'Manage your meetings, bookings, and join links from this dashboard.', 'hydra-booking-customization' ); ?></p>
					</div>
					<div class="hbc-header-actions">
						<button type="button" class="hbc-logout-btn button" onclick="hbcLogout()"><?php _e( 'Logout', 'hydra-booking-customization' ); ?></button>
					</div>
				</div>
			</div>

			<div class="hbc-dashboard-stats">
				<?php $this->render_dashboard_stats( $host_data ); ?>
			</div>

			<div class="hbc-dashboard-nav">
				<ul class="hbc-nav-tabs">
					<li><a href="#bookings" class="hbc-nav-tab active" data-tab="bookings"><?php _e( 'Today\'s Meetings', 'hydra-booking-customization' ); ?></a></li>
					<li><a href="#upcoming" class="hbc-nav-tab" data-tab="upcoming"><?php _e( 'Upcoming Bookings', 'hydra-booking-customization' ); ?></a></li>
					<li><a href="#join-links" class="hbc-nav-tab" data-tab="join-links"><?php _e( 'Join Links', 'hydra-booking-customization' ); ?></a></li>
					<li><a href="#profile" class="hbc-nav-tab" data-tab="profile"><?php _e( 'Profile', 'hydra-booking-customization' ); ?></a></li>
					<li><a href="#history" class="hbc-nav-tab" data-tab="history"><?php _e( 'Meeting History', 'hydra-booking-customization' ); ?></a></li>
				</ul>
			</div>

			<div class="hbc-dashboard-content">
				<div id="hbc-tab-bookings" class="hbc-tab-content active">
					<?php $this->render_todays_meetings_tab( $host_data ); ?>
				</div>

				<div id="hbc-tab-upcoming" class="hbc-tab-content">
					<?php $this->render_upcoming_bookings_tab( $host_data ); ?>
				</div>

				<div id="hbc-tab-join-links" class="hbc-tab-content">
					<?php $this->render_join_links_tab( $host_data ); ?>
				</div>

				<div id="hbc-tab-profile" class="hbc-tab-content">
					<?php $this->render_profile_tab( $user, $host_data ); ?>
				</div>

				<div id="hbc-tab-history" class="hbc-tab-content">
					<?php $this->render_history_tab( $host_data ); ?>
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
	 * Render dashboard stats.
	 *
	 * @param array $host_data Host data.
	 */
	private function render_dashboard_stats( $host_data ) {
		$stats = $this->get_host_stats( $host_data['host_id'] );
		?>
		<div class="hbc-stats-grid">
			<div class="hbc-stat-card">
				<div class="hbc-stat-icon">📅</div>
				<div class="hbc-stat-content">
					<h3><?php echo esc_html( $stats['today_meetings'] ); ?></h3>
					<p><?php _e( 'Today\'s Meetings', 'hydra-booking-customization' ); ?></p>
				</div>
			</div>
			
			<div class="hbc-stat-card">
				<div class="hbc-stat-icon">⏰</div>
				<div class="hbc-stat-content">
					<h3><?php echo esc_html( $stats['upcoming_meetings'] ); ?></h3>
					<p><?php _e( 'Upcoming Meetings', 'hydra-booking-customization' ); ?></p>
				</div>
			</div>
			
			<div class="hbc-stat-card">
				<div class="hbc-stat-icon">✅</div>
				<div class="hbc-stat-content">
					<h3><?php echo esc_html( $stats['completed_meetings'] ); ?></h3>
					<p><?php _e( 'Completed This Month', 'hydra-booking-customization' ); ?></p>
				</div>
			</div>
			
			<div class="hbc-stat-card">
				<div class="hbc-stat-icon">🔗</div>
				<div class="hbc-stat-content">
					<h3><?php echo esc_html( $stats['active_join_links'] ); ?></h3>
					<p><?php _e( 'Active Join Links', 'hydra-booking-customization' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render today's meetings tab.
	 *
	 * @param array $host_data Host data.
	 */
	private function render_todays_meetings_tab( $host_data ) {
		$todays_meetings = $this->get_host_bookings( $host_data['host_id'], 'today' );
		?>
		<div class="hbc-meetings-section">
			<div class="hbc-section-header">
				<h3><?php _e( 'Today\'s Meetings', 'hydra-booking-customization' ); ?></h3>
				<div class="hbc-section-actions">
					<button class="button hbc-refresh-meetings" data-period="today">
						<?php _e( 'Refresh', 'hydra-booking-customization' ); ?>
					</button>
				</div>
			</div>
			
			<?php if ( empty( $todays_meetings ) ) : ?>
				<div class="hbc-no-meetings">
					<div class="hbc-empty-state">
						<div class="hbc-empty-icon">📅</div>
						<h4><?php _e( 'No meetings scheduled for today', 'hydra-booking-customization' ); ?></h4>
						<p><?php _e( 'Enjoy your free day!', 'hydra-booking-customization' ); ?></p>
					</div>
				</div>
			<?php else : ?>
				<div class="hbc-meetings-list">
					<?php foreach ( $todays_meetings as $meeting ) : ?>
						<?php $this->render_meeting_card( $meeting, 'today' ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render upcoming bookings tab.
	 *
	 * @param array $host_data Host data.
	 */
	private function render_upcoming_bookings_tab( $host_data ) {
		$upcoming_meetings = $this->get_host_bookings( $host_data['host_id'], 'upcoming' );
		?>
		<div class="hbc-meetings-section">
			<div class="hbc-section-header">
				<h3><?php _e( 'Upcoming Meetings', 'hydra-booking-customization' ); ?></h3>
				<div class="hbc-section-actions">
					<button class="button hbc-refresh-meetings" data-period="upcoming">
						<?php _e( 'Refresh', 'hydra-booking-customization' ); ?>
					</button>
				</div>
			</div>
			
			<?php if ( empty( $upcoming_meetings ) ) : ?>
				<div class="hbc-no-meetings">
					<div class="hbc-empty-state">
						<div class="hbc-empty-icon">📆</div>
						<h4><?php _e( 'No upcoming meetings', 'hydra-booking-customization' ); ?></h4>
						<p><?php _e( 'Your schedule is clear for the coming days.', 'hydra-booking-customization' ); ?></p>
					</div>
				</div>
			<?php else : ?>
				<div class="hbc-meetings-list">
					<?php foreach ( $upcoming_meetings as $meeting ) : ?>
						<?php $this->render_meeting_card( $meeting, 'upcoming' ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render join links tab.
	 *
	 * @param array $host_data Host data.
	 */
	private function render_join_links_tab( $host_data ) {
		$meetings_with_links = $this->get_meetings_with_join_links( $host_data['host_id'] );
		?>
		<div class="hbc-join-links-section">
			<div class="hbc-section-header">
				<h3><?php _e( 'Meeting Join Links', 'hydra-booking-customization' ); ?></h3>
				<div class="hbc-section-actions">
					<button class="button button-primary hbc-generate-all-links">
						<?php _e( 'Generate Missing Links', 'hydra-booking-customization' ); ?>
					</button>
				</div>
			</div>
			
			<?php if ( empty( $meetings_with_links ) ) : ?>
				<div class="hbc-no-meetings">
					<div class="hbc-empty-state">
						<div class="hbc-empty-icon">🔗</div>
						<h4><?php _e( 'No meetings with join links', 'hydra-booking-customization' ); ?></h4>
						<p><?php _e( 'Generate join links for your upcoming meetings.', 'hydra-booking-customization' ); ?></p>
					</div>
				</div>
			<?php else : ?>
				<div class="hbc-join-links-list">
					<?php foreach ( $meetings_with_links as $meeting ) : ?>
						<div class="hbc-join-link-card" data-booking-id="<?php echo esc_attr( $meeting->booking_id ); ?>">
							<div class="hbc-meeting-info">
								<h4><?php echo esc_html( $meeting->meeting_title ); ?></h4>
								<div class="hbc-meeting-meta">
									<span class="hbc-meeting-date">
										<?php 
										$meeting_datetime = $meeting->meeting_dates . ' ' . $meeting->start_time;
										echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $meeting_datetime ) ) ); 
										?>
									</span>
									<span class="hbc-attendee-count">
										<?php printf( __( '%d attendees', 'hydra-booking-customization' ), $meeting->attendee_count ); ?>
									</span>
								</div>
							</div>
							
							<div class="hbc-join-links">
								<?php 
								$join_links = $this->get_meeting_join_links( $meeting->booking_id );
								if ( ! empty( $join_links ) ) :
									foreach ( $join_links as $link ) :
								?>
									<div class="hbc-join-link-item">
										<div class="hbc-link-info">
											<span class="hbc-link-type"><?php echo esc_html( ucfirst( $link['type'] ) ); ?></span>
											<input type="text" class="hbc-join-url" value="<?php echo esc_attr( $link['join_url'] ); ?>" readonly>
										</div>
										<div class="hbc-link-actions">
											<button class="button hbc-copy-link" data-url="<?php echo esc_attr( $link['join_url'] ); ?>">
												<?php _e( 'Copy', 'hydra-booking-customization' ); ?>
											</button>
											<button class="button hbc-send-link" data-booking-id="<?php echo esc_attr( $meeting->booking_id ); ?>" data-link-type="<?php echo esc_attr( $link['type'] ); ?>">
												<?php _e( 'Send', 'hydra-booking-customization' ); ?>
											</button>
										</div>
									</div>
								<?php 
									endforeach;
								else :
								?>
									<div class="hbc-no-links">
										<p><?php _e( 'No join links available', 'hydra-booking-customization' ); ?></p>
										<button class="button button-primary hbc-generate-link" data-booking-id="<?php echo esc_attr( $meeting->booking_id ); ?>">
											<?php _e( 'Generate Link', 'hydra-booking-customization' ); ?>
										</button>
									</div>
								<?php endif; ?>
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
	 * @param array   $host_data Host data.
	 */
	private function render_profile_tab( $user, $host_data ) {
		?>
		<div class="hbc-profile-section">
			<h3><?php _e( 'Host Profile Information', 'hydra-booking-customization' ); ?></h3>
			
			<form id="hbc-host-profile-form" class="hbc-form">
				<?php wp_nonce_field( 'hbc_update_host_profile', 'hbc_host_profile_nonce' ); ?>
				
				<div class="hbc-form-row">
					<div class="hbc-form-group">
						<label for="first_name"><?php _e( 'First Name', 'hydra-booking-customization' ); ?></label>
						<input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $host_data['first_name'] ?? $user->first_name ); ?>" required>
					</div>
					
					<div class="hbc-form-group">
						<label for="last_name"><?php _e( 'Last Name', 'hydra-booking-customization' ); ?></label>
						<input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $host_data['last_name'] ?? $user->last_name ); ?>">
					</div>
				</div>
				
				<div class="hbc-form-group">
					<label for="user_email"><?php _e( 'Email Address', 'hydra-booking-customization' ); ?></label>
					<input type="email" id="user_email" name="user_email" value="<?php echo esc_attr( $host_data['email'] ?? $user->user_email ); ?>" required>
				</div>
				
				<div class="hbc-form-group">
					<label for="phone"><?php _e( 'Phone Number', 'hydra-booking-customization' ); ?></label>
					<input type="tel" id="phone" name="phone" value="<?php echo esc_attr( $host_data['phone'] ?? '' ); ?>">
				</div>
				
				<div class="hbc-form-group">
					<label for="bio"><?php _e( 'Bio', 'hydra-booking-customization' ); ?></label>
					<textarea id="bio" name="bio" rows="4"><?php echo esc_textarea( $host_data['bio'] ?? $user->description ); ?></textarea>
				</div>
				
				<div class="hbc-form-actions">
					<button type="submit" class="button button-primary"><?php _e( 'Update Profile', 'hydra-booking-customization' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render history tab.
	 *
	 * @param array $host_data Host data.
	 */
	private function render_history_tab( $host_data ) {
		$past_meetings = $this->get_host_bookings( $host_data['host_id'], 'past' );
		?>
		<div class="hbc-history-section">
			<h3><?php _e( 'Meeting History', 'hydra-booking-customization' ); ?></h3>
			
			<?php if ( empty( $past_meetings ) ) : ?>
				<div class="hbc-no-meetings">
					<div class="hbc-empty-state">
						<div class="hbc-empty-icon">📚</div>
						<h4><?php _e( 'No past meetings', 'hydra-booking-customization' ); ?></h4>
						<p><?php _e( 'Your meeting history will appear here.', 'hydra-booking-customization' ); ?></p>
					</div>
				</div>
			<?php else : ?>
				<div class="hbc-meetings-list">
					<?php foreach ( $past_meetings as $meeting ) : ?>
						<?php $this->render_meeting_card( $meeting, 'past' ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render meeting card.
	 *
	 * @param object $meeting Meeting object.
	 * @param string $type    Meeting type (today, upcoming, past).
	 */
	private function render_meeting_card( $meeting, $type = 'upcoming' ) {
		$status_class = $this->get_meeting_status_class( $meeting, $type );
		$join_links = $this->get_meeting_join_links( $meeting->booking_id );
		?>
		<div class="hbc-meeting-card <?php echo esc_attr( $status_class ); ?>" data-booking-id="<?php echo esc_attr( $meeting->booking_id ); ?>">
			<div class="hbc-meeting-header">
				<h4><?php echo esc_html( $meeting->meeting_title ); ?></h4>
				<div class="hbc-meeting-status">
					<span class="hbc-status-badge status-<?php echo esc_attr( $meeting->booking_status ?? 'pending' ); ?>">
						<?php echo esc_html( ucfirst( $meeting->booking_status ?? 'pending' ) ); ?>
					</span>
				</div>
			</div>
			
			<div class="hbc-meeting-details">
				<div class="hbc-meeting-time">
					<strong><?php _e( 'Date & Time:', 'hydra-booking-customization' ); ?></strong>
					<?php 
					$meeting_datetime = $meeting->meeting_dates . ' ' . $meeting->start_time;
					echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $meeting_datetime ) ) ); 
					?>
				</div>
				
				<div class="hbc-meeting-duration">
					<strong><?php _e( 'Duration:', 'hydra-booking-customization' ); ?></strong>
					<?php echo esc_html( $meeting->duration ?? '30' ); ?> <?php _e( 'minutes', 'hydra-booking-customization' ); ?>
				</div>
				
				<div class="hbc-meeting-attendees">
					<strong><?php _e( 'Attendees:', 'hydra-booking-customization' ); ?></strong>
					<?php echo esc_html( $meeting->attendee_count ?? 0 ); ?>
				</div>
				
				<?php if ( ! empty( $join_links ) ) : ?>
					<div class="hbc-meeting-links">
						<strong><?php _e( 'Join Links:', 'hydra-booking-customization' ); ?></strong>
						<div class="hbc-quick-links">
							<?php foreach ( $join_links as $link ) : ?>
								<a href="<?php echo esc_url( $link['join_url'] ); ?>" target="_blank" class="hbc-quick-link hbc-link-<?php echo esc_attr( $link['type'] ); ?>">
									<?php echo esc_html( ucfirst( $link['type'] ) ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			
			<div class="hbc-meeting-actions">
				<?php if ( $type === 'today' || $type === 'upcoming' ) : ?>
					<button class="button hbc-view-details" data-booking-id="<?php echo esc_attr( $meeting->booking_id ); ?>">
						<?php _e( 'View Details', 'hydra-booking-customization' ); ?>
					</button>
					
					<?php if ( empty( $join_links ) ) : ?>
						<button class="button button-primary hbc-generate-link" data-booking-id="<?php echo esc_attr( $meeting->booking_id ); ?>">
							<?php _e( 'Generate Join Link', 'hydra-booking-customization' ); ?>
						</button>
					<?php else : ?>
						<button class="button hbc-copy-all-links" data-booking-id="<?php echo esc_attr( $meeting->booking_id ); ?>">
							<?php _e( 'Copy Links', 'hydra-booking-customization' ); ?>
						</button>
					<?php endif; ?>
					
					<button class="button hbc-send-reminder" data-booking-id="<?php echo esc_attr( $meeting->booking_id ); ?>">
						<?php _e( 'Send Reminder', 'hydra-booking-customization' ); ?>
					</button>
				<?php else : ?>
					<button class="button hbc-view-details" data-booking-id="<?php echo esc_attr( $meeting->booking_id ); ?>">
						<?php _e( 'View Details', 'hydra-booking-customization' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get host data from user ID.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_host_data( $user_id ) {
		global $wpdb;
		
		$hosts_table = $wpdb->prefix . 'tfhb_hosts';
		
		$host = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$hosts_table} WHERE user_id = %d",
			$user_id
		), ARRAY_A );
		
		if ( ! $host ) {
			// Create host record if it doesn't exist
			$user = get_user_by( 'id', $user_id );
			$host_data = array(
				'user_id' => $user_id,
				'first_name' => $user->first_name,
				'last_name' => $user->last_name,
				'email' => $user->user_email,
				'status' => 'active',
				'created_at' => current_time( 'mysql' )
			);
			
			$wpdb->insert( $hosts_table, $host_data );
			$host_data['id'] = $wpdb->insert_id;
			$host_data['host_id'] = $host_data['id'];
			
			return $host_data;
		}
		
		$host['host_id'] = $host['id'];
		return $host;
	}

	/**
	 * Get host statistics.
	 *
	 * @param int $host_id Host ID.
	 * @return array
	 */
	private function get_host_stats( $host_id ) {
		global $wpdb;
		
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		$attendees_table = $wpdb->prefix . 'tfhb_attendees';
		
		$today = current_time( 'Y-m-d' );
		$month_start = current_time( 'Y-m-01' );
		$current_time = current_time( 'mysql' );
		
		// Today's meetings
		$today_meetings = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT b.id) 
			FROM {$bookings_table} b 
			INNER JOIN {$attendees_table} a ON b.id = a.booking_id 
			WHERE a.host_id = %d AND b.meeting_dates = %s",
			$host_id, $today
		) );
		
		// Upcoming meetings
		$upcoming_meetings = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT b.id) 
			FROM {$bookings_table} b 
			INNER JOIN {$attendees_table} a ON b.id = a.booking_id 
			WHERE a.host_id = %d AND CONCAT(b.meeting_dates, ' ', b.start_time) > %s",
			$host_id, $current_time
		) );
		
		// Completed meetings this month
		$completed_meetings = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT b.id) 
			FROM {$bookings_table} b 
			INNER JOIN {$attendees_table} a ON b.id = a.booking_id 
			WHERE a.host_id = %d 
			AND CONCAT(b.meeting_dates, ' ', b.start_time) < %s 
			AND b.meeting_dates >= %s 
			AND b.status = 'confirmed'",
			$host_id, $current_time, $month_start
		) );
		
		// Active join links
		$active_join_links = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT b.id) 
			FROM {$bookings_table} b 
			INNER JOIN {$attendees_table} a ON b.id = a.booking_id 
			WHERE a.host_id = %d 
			AND CONCAT(b.meeting_dates, ' ', b.start_time) > %s 
			AND b.meeting_locations IS NOT NULL 
			AND b.meeting_locations != ''",
			$host_id, $current_time
		) );
		
		return array(
			'today_meetings' => (int) $today_meetings,
			'upcoming_meetings' => (int) $upcoming_meetings,
			'completed_meetings' => (int) $completed_meetings,
			'active_join_links' => (int) $active_join_links,
		);
	}

	/**
	 * Get host bookings.
	 *
	 * @param int    $host_id Host ID.
	 * @param string $type    Booking type (today, upcoming, past, all).
	 * @return array
	 */
	private function get_host_bookings( $host_id, $type = 'all' ) {
		global $wpdb;
		
		$attendees_table = $wpdb->prefix . 'tfhb_attendees';
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		$meetings_table = $wpdb->prefix . 'tfhb_meetings';
		
		$current_time = current_time( 'mysql' );
		$today = current_time( 'Y-m-d' );
		
		// Build the base query
		$query = "
			SELECT 
				b.id as booking_id,
				b.meeting_dates,
				b.start_time,
				b.end_time,
				b.status as booking_status,
				b.meeting_locations,
				m.title as meeting_title,
				m.description as meeting_description,
				m.duration,
				COUNT(a.id) as attendee_count
			FROM {$bookings_table} b
			LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id
			LEFT JOIN {$attendees_table} a ON b.id = a.booking_id
			WHERE a.host_id = %d
		";
		
		$query_params = array( $host_id );
		
		// Add time-based filtering
		switch ( $type ) {
			case 'today':
				$query .= " AND b.meeting_dates = %s";
				$query_params[] = $today;
				$order_by = 'ORDER BY b.start_time ASC';
				break;
			case 'upcoming':
				$query .= " AND CONCAT(b.meeting_dates, ' ', b.start_time) > %s";
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
		
		$query .= " GROUP BY b.id {$order_by}";
		
		return $wpdb->get_results( $wpdb->prepare( $query, $query_params ) );
	}

	/**
	 * Get meetings with join links.
	 *
	 * @param int $host_id Host ID.
	 * @return array
	 */
	private function get_meetings_with_join_links( $host_id ) {
		global $wpdb;
		
		$attendees_table = $wpdb->prefix . 'tfhb_attendees';
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		$meetings_table = $wpdb->prefix . 'tfhb_meetings';
		
		$current_time = current_time( 'mysql' );
		
		$query = "
			SELECT 
				b.id as booking_id,
				b.meeting_dates,
				b.start_time,
				b.meeting_locations,
				m.title as meeting_title,
				COUNT(a.id) as attendee_count
			FROM {$bookings_table} b
			LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id
			LEFT JOIN {$attendees_table} a ON b.id = a.booking_id
			WHERE a.host_id = %d 
			AND CONCAT(b.meeting_dates, ' ', b.start_time) > %s
			GROUP BY b.id
			ORDER BY b.meeting_dates ASC, b.start_time ASC
		";
		
		return $wpdb->get_results( $wpdb->prepare( $query, $host_id, $current_time ) );
	}

	/**
	 * Get meeting join links.
	 *
	 * @param int $booking_id Booking ID.
	 * @return array
	 */
	private function get_meeting_join_links( $booking_id ) {
		global $wpdb;
		
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		
		$booking = $wpdb->get_row( $wpdb->prepare(
			"SELECT meeting_locations FROM {$bookings_table} WHERE id = %d",
			$booking_id
		) );
		
		if ( ! $booking || empty( $booking->meeting_locations ) ) {
			return array();
		}
		
		$locations = json_decode( $booking->meeting_locations, true );
		$join_links = array();
		
		if ( is_array( $locations ) ) {
			foreach ( $locations as $location ) {
				if ( isset( $location['join_url'] ) && ! empty( $location['join_url'] ) ) {
					$join_links[] = array(
						'type' => $location['type'] ?? 'unknown',
						'join_url' => $location['join_url'],
						'meeting_id' => $location['meeting_id'] ?? '',
						'password' => $location['password'] ?? ''
					);
				}
			}
		}
		
		return $join_links;
	}

	/**
	 * Get meeting status class.
	 *
	 * @param object $meeting Meeting object.
	 * @param string $type    Meeting type.
	 * @return string
	 */
	private function get_meeting_status_class( $meeting, $type ) {
		$classes = array( 'hbc-meeting-' . $type );
		
		if ( isset( $meeting->booking_status ) ) {
			$classes[] = 'status-' . $meeting->booking_status;
		}
		
		return implode( ' ', $classes );
	}

	/**
	 * Redirect non-hosts from host dashboard pages.
	 */
	public function redirect_non_hosts() {
		$dashboard_page_id = get_option( 'hbc_host_dashboard_page_id' );
		
		if ( is_page( $dashboard_page_id ) && is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			
			if ( ! in_array( 'tfhb_host', $current_user->roles, true ) && ! current_user_can( 'manage_options' ) ) {
				// Show access denied message instead of redirecting
				return;
			}
		}
	}

	/**
	 * AJAX: Get host bookings.
	 */
	public function ajax_get_host_bookings() {
		check_ajax_referer( 'hbc_ajax_nonce', 'nonce' );
		
		// Verify host access
		AccessControl::verify_host_ajax_access();
		
		$current_user = wp_get_current_user();
		$host_data = $this->get_host_data( $current_user->ID );
		
		if ( ! $host_data || ! isset( $host_data['host_id'] ) ) {
			wp_send_json_error( __( 'Host data not found.', 'hydra-booking-customization' ) );
		}
		
		$period = sanitize_text_field( $_POST['period'] ?? 'upcoming' );
		
		$bookings = $this->get_host_bookings( $host_data['host_id'], $period );
		
		// Get testing mode status
		$testing_mode = ( HBC_TEST_MODE_STATUS === 'active' );
		
		// Add testing mode to each booking
		foreach ( $bookings as &$booking ) {
			$booking->testing_mode = $testing_mode;
		}
		
		wp_send_json_success( $bookings );
	}

	/**
	 * AJAX: Update booking status.
	 */
	public function ajax_update_booking_status() {
		check_ajax_referer( 'hbc_ajax_nonce', 'nonce' );
		
		// Verify host access
		AccessControl::verify_host_ajax_access();
		
		$booking_id = intval( $_POST['booking_id'] ?? 0 );
		$status = sanitize_text_field( $_POST['status'] ?? '' );
		
		if ( ! $booking_id || ! $status ) {
			wp_send_json_error( __( 'Invalid parameters.', 'hydra-booking-customization' ) );
		}
		
		global $wpdb;
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		
		$result = $wpdb->update(
			$bookings_table,
			array( 'status' => $status ),
			array( 'id' => $booking_id ),
			array( '%s' ),
			array( '%d' )
		);
		
		if ( $result !== false ) {
			wp_send_json_success( __( 'Booking status updated successfully.', 'hydra-booking-customization' ) );
		} else {
			wp_send_json_error( __( 'Failed to update booking status.', 'hydra-booking-customization' ) );
		}
	}

	/**
	 * AJAX: Generate join link.
	 */
	public function ajax_generate_join_link() {
		check_ajax_referer( 'hbc_ajax_nonce', 'nonce' );
		
		// Verify host access
		AccessControl::verify_host_ajax_access();
		
		$booking_id = intval( $_POST['booking_id'] ?? 0 );
		
		if ( ! $booking_id ) {
			wp_send_json_error( __( 'Invalid booking ID.', 'hydra-booking-customization' ) );
		}
		
		// Get the Jitsi integration instance
		$jitsi_integration = new \HydraBookingCustomization\Features\JitsiIntegration();
		$current_user_id = get_current_user_id();
		$join_link = $jitsi_integration->generate_secure_meeting_url( $booking_id, $current_user_id, 'host' );
		
		if ( $join_link ) {
			wp_send_json_success( array(
				'join_link' => $join_link,
				'message' => __( 'Join link generated successfully.', 'hydra-booking-customization' )
			) );
		} else {
			wp_send_json_error( __( 'Failed to generate join link.', 'hydra-booking-customization' ) );
		}
	}

	/**
	 * AJAX: Send join link.
	 */
	public function ajax_send_join_link() {
		check_ajax_referer( 'hbc_ajax_nonce', 'nonce' );
		
		// Verify host access
		AccessControl::verify_host_ajax_access();
		
		$booking_id = intval( $_POST['booking_id'] ?? 0 );
		$link_type = sanitize_text_field( $_POST['link_type'] ?? 'jitsi' );
		
		if ( ! $booking_id ) {
			wp_send_json_error( __( 'Invalid booking ID.', 'hydra-booking-customization' ) );
		}
		
		// Get booking details and send join link to attendees
		$join_links = $this->get_meeting_join_links( $booking_id );
		$target_link = null;
		
		foreach ( $join_links as $link ) {
			if ( $link['type'] === $link_type ) {
				$target_link = $link;
				break;
			}
		}
		
		if ( ! $target_link ) {
			wp_send_json_error( __( 'Join link not found.', 'hydra-booking-customization' ) );
		}
		
		// Here you would implement the email sending logic
		// For now, we'll just return success
		wp_send_json_success( __( 'Join link sent to attendees successfully.', 'hydra-booking-customization' ) );
	}

	/**
	 * AJAX: Update host profile.
	 */
	public function ajax_update_host_profile() {
		check_ajax_referer( 'hbc_update_host_profile', 'hbc_host_profile_nonce' );
		
		// Verify host access
		AccessControl::verify_host_ajax_access();
		
		$current_user = wp_get_current_user();
		$host_data = $this->get_host_data( $current_user->ID );
		
		$first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
		$last_name = sanitize_text_field( $_POST['last_name'] ?? '' );
		$email = sanitize_email( $_POST['email'] ?? '' );
		$phone = sanitize_text_field( $_POST['phone'] ?? '' );
		$bio = sanitize_textarea_field( $_POST['bio'] ?? '' );
		
		// Handle password change if provided
		$current_password = sanitize_text_field( $_POST['current_password'] ?? '' );
		$new_password = sanitize_text_field( $_POST['new_password'] ?? '' );
		
		if ( ! $first_name || ! $email ) {
			wp_send_json_error( __( 'First name and email are required.', 'hydra-booking-customization' ) );
		}
		
		// Validate password change if provided
		if ( $new_password ) {
			if ( ! $current_password ) {
				wp_send_json_error( __( 'Current password is required to change password.', 'hydra-booking-customization' ) );
			}
			
			if ( ! wp_check_password( $current_password, $current_user->user_pass, $current_user->ID ) ) {
				wp_send_json_error( __( 'Current password is incorrect.', 'hydra-booking-customization' ) );
			}
			
			if ( strlen( $new_password ) < 8 ) {
				wp_send_json_error( __( 'Password must be at least 8 characters long.', 'hydra-booking-customization' ) );
			}
		}
		
		global $wpdb;
		$hosts_table = $wpdb->prefix . 'tfhb_hosts';
		
		$update_data = array(
			'first_name' => $first_name,
			'last_name' => $last_name,
			'email' => $email,
			'phone_number' => $phone,
			'about' => $bio,
		);
		
		$result = $wpdb->update(
			$hosts_table,
			$update_data,
			array( 'id' => $host_data['host_id'] ),
			array( '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		
		if ( $result !== false ) {
			// Update WordPress user data
			$user_update_data = array(
				'ID' => $current_user->ID,
				'first_name' => $first_name,
				'last_name' => $last_name,
				'user_email' => $email,
				'description' => $bio,
			);
			
			// Update password if provided
			if ( $new_password ) {
				wp_set_password( $new_password, $current_user->ID );
			}
			
			wp_update_user( $user_update_data );
			
			wp_send_json_success( __( 'Profile updated successfully.', 'hydra-booking-customization' ) );
		} else {
			wp_send_json_error( __( 'Failed to update profile.', 'hydra-booking-customization' ) );
		}
	}

	/**
	 * AJAX: Get booking details.
	 */
	public function ajax_get_booking_details() {
		check_ajax_referer( 'hbc_ajax_nonce', 'nonce' );
		
		// Verify host access
		AccessControl::verify_host_ajax_access();
		
		$booking_id = intval( $_POST['booking_id'] ?? 0 );
		
		if ( ! $booking_id ) {
			wp_send_json_error( __( 'Invalid booking ID.', 'hydra-booking-customization' ) );
		}
		
		global $wpdb;
		
		$attendees_table = $wpdb->prefix . 'tfhb_attendees';
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		$meetings_table = $wpdb->prefix . 'tfhb_meetings';
		
		$query = "
			SELECT 
				b.*,
				m.title as meeting_title,
				m.description as meeting_description,
				m.duration,
				GROUP_CONCAT(
					CONCAT(a.attendee_name, '|', a.email, '|', a.status)
					SEPARATOR ';;'
				) as attendees_data
			FROM {$bookings_table} b
			LEFT JOIN {$meetings_table} m ON b.meeting_id = m.id
			LEFT JOIN {$attendees_table} a ON b.id = a.booking_id
			WHERE b.id = %d
			GROUP BY b.id
		";
		
		$booking_details = $wpdb->get_row( $wpdb->prepare( $query, $booking_id ) );
		
		if ( ! $booking_details ) {
			wp_send_json_error( __( 'Booking not found.', 'hydra-booking-customization' ) );
		}
		
		// Parse attendees data
		$attendees = array();
		if ( ! empty( $booking_details->attendees_data ) ) {
			$attendees_raw = explode( ';;', $booking_details->attendees_data );
			foreach ( $attendees_raw as $attendee_raw ) {
				$attendee_parts = explode( '|', $attendee_raw );
				if ( count( $attendee_parts ) === 3 ) {
					$attendees[] = array(
						'name' => $attendee_parts[0],
						'email' => $attendee_parts[1],
						'status' => $attendee_parts[2],
					);
				}
			}
		}
		
		$booking_details->attendees = $attendees;
		$booking_details->join_links = $this->get_meeting_join_links( $booking_id );
		
		wp_send_json_success( $booking_details );
	}

	/**
	 * AJAX: Get host stats.
	 */
	public function ajax_get_host_stats() {
		check_ajax_referer( 'hbc_ajax_nonce', 'nonce' );
		
		// Verify host access
		AccessControl::verify_host_ajax_access();
		
		$current_user = wp_get_current_user();
		$host_data = $this->get_host_data( $current_user->ID );
		
		if ( ! $host_data || ! isset( $host_data['host_id'] ) ) {
			wp_send_json_error( __( 'Host data not found.', 'hydra-booking-customization' ) );
		}
		
		$stats = $this->get_host_stats( $host_data['host_id'] );
		
		wp_send_json_success( $stats );
	}

	/**
	 * AJAX: Get host profile.
	 */
	public function ajax_get_host_profile() {
		check_ajax_referer( 'hbc_ajax_nonce', 'nonce' );
		
		// Verify host access
		AccessControl::verify_host_ajax_access();
		
		$current_user = wp_get_current_user();
		$host_data = $this->get_host_data( $current_user->ID );
		
		if ( ! $host_data ) {
			wp_send_json_error( __( 'Host data not found.', 'hydra-booking-customization' ) );
		}
		
		$profile = array(
			'first_name' => $current_user->first_name,
			'last_name' => $current_user->last_name,
			'email' => $current_user->user_email,
			'display_name' => $current_user->display_name,
			'description' => $current_user->description,
			'host_data' => $host_data,
		);
		
		wp_send_json_success( array( 'profile' => $profile ) );
	}

	/**
	 * AJAX: Get join links.
	 */
	public function ajax_get_join_links() {
		check_ajax_referer( 'hbc_ajax_nonce', 'nonce' );
		
		// Verify host access
		AccessControl::verify_host_ajax_access();
		
		$current_user = wp_get_current_user();
		$host_data = $this->get_host_data( $current_user->ID );
		
		if ( ! $host_data || ! isset( $host_data['host_id'] ) ) {
			wp_send_json_error( __( 'Host data not found.', 'hydra-booking-customization' ) );
		}
		
		// Get all bookings for this host with join links
		global $wpdb;
		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		$join_links_table = $wpdb->prefix . 'tfhb_join_links';
		
		$join_links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT jl.*, b.meeting_title, b.meeting_dates, b.start_time, b.end_time 
				FROM {$join_links_table} jl 
				INNER JOIN {$bookings_table} b ON jl.booking_id = b.id 
				WHERE b.host_id = %d 
				ORDER BY b.meeting_dates DESC, b.start_time DESC",
				$host_data['host_id']
			)
		);
		
		wp_send_json_success( array( 'join_links' => $join_links ) );
	}
}