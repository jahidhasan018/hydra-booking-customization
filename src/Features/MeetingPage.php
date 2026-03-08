<?php
/**
 * Meeting Page Rendering
 *
 * Handles the frontend meeting page: rewrite rules, token-based routing,
 * and all display templates (waiting, active, ended, error).
 *
 * @package HydraBookingCustomization\Features
 * @since   1.1.0
 */

namespace HydraBookingCustomization\Features;

defined( 'ABSPATH' ) || exit;

/**
 * Meeting Page class.
 *
 * Manages the /meeting/{token}/ URL routing and renders the
 * full-page meeting interface for hosts and attendees.
 *
 * @since 1.1.0
 */
class MeetingPage {

	/**
	 * Reference to the parent JitsiIntegration instance.
	 *
	 * @var JitsiIntegration
	 */
	private $jitsi;

	/**
	 * Constructor.
	 *
	 * @param JitsiIntegration $jitsi Parent integration instance.
	 */
	public function __construct( JitsiIntegration $jitsi ) {
		$this->jitsi = $jitsi;
		$this->init_hooks();
	}

	/**
	 * Register page routing hooks.
	 *
	 * @since 1.1.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'add_rewrite_rule' ) );
		add_action( 'template_redirect', array( $this, 'handle_request' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'wp_ajax_nopriv_hbc_start_meeting', array( $this, 'ajax_start_meeting' ) );
		add_action( 'wp_ajax_hbc_start_meeting', array( $this, 'ajax_start_meeting' ) );
	}

	/**
	 * AJAX endpoint to mark meeting as started by host
	 */
	public function ajax_start_meeting() {
		check_ajax_referer( 'hbc_meeting_nonce', 'nonce' );

		$booking_id = isset( $_POST['booking_id'] ) ? intval( $_POST['booking_id'] ) : 0;
		if ( ! $booking_id ) {
			wp_send_json_error( 'Invalid booking ID' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'tfhb_booking_meta';

		$started_at = $wpdb->get_var( $wpdb->prepare(
			"SELECT value FROM {$table_name} WHERE booking_id = %d AND meta_key = 'hbc_meeting_started_at'",
			$booking_id
		) );

		if ( ! $started_at ) {
			$started_at = current_time( 'timestamp' ) * 1000;
			$wpdb->insert(
				$table_name,
				array(
					'booking_id' => $booking_id,
					'meta_key'   => 'hbc_meeting_started_at',
					'value'      => $started_at,
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' )
				),
				array( '%d', '%s', '%s', '%s', '%s' )
			);
		}

		wp_send_json_success( array( 'started_at' => (int) $started_at ) );
	}

	/**
	 * Add rewrite rule for custom meeting page.
	 *
	 * Uses a self-healing check: if the meeting rewrite rule is missing
	 * from the current ruleset, a flush is scheduled for the next page load.
	 *
	 * @since 1.1.0
	 */
	public function add_rewrite_rule() {
		add_rewrite_rule(
			'^meeting/([^/]+)/?$',
			'index.php?hbc_meeting_token=$matches[1]',
			'top'
		);

		// Self-healing: verify the rule actually exists in the persisted ruleset.
		$rules = get_option( 'rewrite_rules' );
		if ( ! is_array( $rules ) || ! isset( $rules['^meeting/([^/]+)/?$'] ) ) {
			flush_rewrite_rules( false );
		}
	}

	/**
	 * Add query vars for meeting page.
	 *
	 * @param array $vars Existing query vars.
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'hbc_meeting_token';
		return $vars;
	}

	/**
	 * Handle meeting page request.
	 */
	public function handle_request() {
		$meeting_token = get_query_var( 'hbc_meeting_token' );

		if ( ! empty( $meeting_token ) ) {
			$this->display_meeting_page( $meeting_token );
			exit;
		}
	}

	/**
	 * Display secure meeting page.
	 *
	 * Validates token, checks permissions, determines meeting status,
	 * and renders the appropriate interface.
	 *
	 * @param string $token The meeting token.
	 * @since 1.0.0
	 */
	private function display_meeting_page( $token ) {
		$token_data = $this->jitsi->validate_meeting_token( $token );

		if ( is_wp_error( $token_data ) ) {
			$this->display_error_page(
				__( 'Invalid Meeting Link', 'hydra-booking-customization' ),
				$token_data->get_error_message(),
				$token_data->get_error_code()
			);
			return;
		}

		$booking_id    = (int) $token_data['booking_id'];
		$token_user_id = (int) $token_data['user_id'];
		$role          = sanitize_text_field( $token_data['role'] );

		$current_user_id = get_current_user_id();
		if ( ! $current_user_id ) {
			$this->display_error_page(
				__( 'Authentication Required', 'hydra-booking-customization' ),
				__( 'You must be logged in to access this meeting. Please log in and try again.', 'hydra-booking-customization' ),
				'authentication_required'
			);
			return;
		}

		if ( $current_user_id !== $token_user_id ) {
			$this->display_error_page(
				__( 'Access Denied', 'hydra-booking-customization' ),
				__( 'This meeting link is not valid for your account. Please use the correct meeting link for your account.', 'hydra-booking-customization' ),
				'user_mismatch'
			);
			return;
		}

		$booking      = $this->jitsi->get_booking_data( $booking_id );
		$meeting_data = $this->jitsi->get_meeting_data( $booking_id );

		if ( ! $booking || ! $meeting_data ) {
			$this->display_error_page(
				__( 'Meeting Not Found', 'hydra-booking-customization' ),
				__( 'The requested meeting could not be found or has been removed.', 'hydra-booking-customization' ),
				'meeting_not_found'
			);
			return;
		}

		$meeting_status = $this->get_meeting_status( $booking );

		switch ( $meeting_status ) {
			case 'waiting':
				$meeting_start = strtotime( $booking->meeting_dates . ' ' . $booking->start_time );
				$this->display_waiting_page( $booking, $meeting_start );
				break;

			case 'ended':
				$this->display_ended_page( $booking );
				break;

			case 'active':
			case 'joinable':
				$this->display_meeting_interface( $booking, $meeting_data, $current_user_id, $role );
				break;

			default:
				$this->display_error_page(
					__( 'Meeting Status Error', 'hydra-booking-customization' ),
					__( 'Unable to determine meeting status. Please try again later.', 'hydra-booking-customization' ),
					'status_error'
				);
		}
	}

	/**
	 * Get meeting status based on current time and booking schedule.
	 *
	 * @param object $booking The booking object.
	 * @return string Meeting status (waiting|joinable|active|ended).
	 * @since 1.0.0
	 */
	private function get_meeting_status( $booking ) {
		// Test mode bypass: skip all time-based checks.
		if ( get_option( 'hbc_enable_test_mode', false ) ) {
			return 'active';
		}

		$meeting_start = strtotime( $booking->meeting_dates . ' ' . $booking->start_time );
		$meeting_end   = strtotime( $booking->meeting_dates . ' ' . $booking->end_time );
		$current_time  = current_time( 'timestamp' );

		$meeting_data = $this->jitsi->get_meeting_data( $booking->booking_id );
		if ( $meeting_data && isset( $meeting_data['terminated'] ) && $meeting_data['terminated'] ) {
			return 'ended';
		}

		$join_time = $meeting_start - JitsiIntegration::MEETING_GRACE_PERIOD;

		if ( $current_time < $join_time ) {
			return 'waiting';
		} elseif ( $current_time >= $join_time && $current_time < $meeting_start ) {
			return 'joinable';
		} elseif ( $current_time >= $meeting_start && $current_time <= $meeting_end ) {
			return 'active';
		} else {
			return 'ended';
		}
	}

	/**
	 * Display error page for meeting access issues.
	 *
	 * @param string $title   Error title.
	 * @param string $message Error message.
	 * @param string $code    Error code.
	 * @since 1.0.0
	 */
	private function display_error_page( $title, $message, $code = '' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Jitsi MeetingPage Error [%s]: %s', $code, $message ) );
		}

		?><!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( $title ); ?> - <?php bloginfo( 'name' ); ?></title>
			<?php wp_head(); ?>
			<style>
				body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
				.error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
				.error-title { color: #d63638; margin-bottom: 20px; font-size: 24px; }
				.error-message { color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 30px; }
				.error-actions { margin-top: 30px; }
				.button { display: inline-block; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; margin: 0 10px; }
				.button:hover { background: #005a87; color: white; text-decoration: none; }
				.error-code { font-size: 12px; color: #999; margin-top: 20px; }
			</style>
		</head>
		<body>
			<div class="error-container">
				<h1 class="error-title"><?php echo esc_html( $title ); ?></h1>
				<div class="error-message">
					<p><?php echo esc_html( $message ); ?></p>
				</div>
				<div class="error-actions">
					<a href="<?php echo esc_url( home_url() ); ?>" class="button">
						<?php esc_html_e( 'Go to Homepage', 'hydra-booking-customization' ); ?>
					</a>
					<a href="javascript:history.back()" class="button">
						<?php esc_html_e( 'Go Back', 'hydra-booking-customization' ); ?>
					</a>
				</div>
				<?php if ( $code ) : ?>
					<div class="error-code">
						<?php
						printf(
							/* translators: %s: error code */
							esc_html__( 'Error Code: %s', 'hydra-booking-customization' ),
							esc_html( $code )
						);
						?>
					</div>
				<?php endif; ?>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html><?php
		exit;
	}

	/**
	 * Display waiting page when meeting hasn't started.
	 *
	 * @param object $booking       Booking object.
	 * @param int    $meeting_start Unix timestamp of meeting start.
	 */
	private function display_waiting_page( $booking, $meeting_start ) {
		$meeting_time = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $meeting_start );

		?><!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( $booking->meeting_title ); ?> - <?php bloginfo( 'name' ); ?></title>
			<?php wp_head(); ?>
			<style>
				body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
				.waiting-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
				.meeting-title { color: #333; margin-bottom: 20px; }
				.countdown { font-size: 24px; color: #0073aa; margin: 20px 0; }
				.meeting-info { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0; }
			</style>
		</head>
		<body>
			<div class="waiting-container">
				<h1 class="meeting-title"><?php echo esc_html( $booking->meeting_title ); ?></h1>
				<p><?php esc_html_e( 'Your meeting will start at:', 'hydra-booking-customization' ); ?></p>
				<div class="countdown"><?php echo esc_html( $meeting_time ); ?></div>
				<div class="meeting-info">
					<p><?php esc_html_e( 'Please keep this page open and refresh it closer to the meeting time.', 'hydra-booking-customization' ); ?></p>
				</div>
				<button onclick="location.reload()" class="button"><?php esc_html_e( 'Refresh Page', 'hydra-booking-customization' ); ?></button>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html><?php
	}

	/**
	 * Display ended page when meeting has finished.
	 *
	 * @param object $booking Booking object.
	 */
	private function display_ended_page( $booking ) {
		?><!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( $booking->meeting_title ); ?> - <?php bloginfo( 'name' ); ?></title>
			<?php wp_head(); ?>
			<style>
				body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
				.ended-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
				.meeting-title { color: #333; margin-bottom: 20px; }
				.ended-message { color: #666; font-size: 18px; }
			</style>
		</head>
		<body>
			<div class="ended-container">
				<h1 class="meeting-title"><?php echo esc_html( $booking->meeting_title ); ?></h1>
				<div class="ended-message">
					<p><?php esc_html_e( 'This meeting has ended.', 'hydra-booking-customization' ); ?></p>
					<p><?php esc_html_e( 'Thank you for participating!', 'hydra-booking-customization' ); ?></p>
				</div>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html><?php
	}

	/**
	 * Display branded meeting interface with embedded Jitsi.
	 *
	 * @param object $booking      Booking object.
	 * @param array  $meeting_data Meeting metadata.
	 * @param int    $user_id      Current user ID.
	 * @param string $role         User role (host|attendee).
	 * @since 1.1.0
	 */
	private function display_meeting_interface( $booking, $meeting_data, $user_id, $role ) {
		$user         = get_user_by( 'id', $user_id );
		$display_name = $user ? $user->display_name : __( 'Guest', 'hydra-booking-customization' );
		$site_name    = get_bloginfo( 'name' );
		$logo_id      = get_theme_mod( 'custom_logo' );
		$logo_url     = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
		$role_label   = ( 'host' === $role )
			? __( 'Host', 'hydra-booking-customization' )
			: __( 'Attendee', 'hydra-booking-customization' );
		$initials     = mb_strtoupper( mb_substr( $display_name, 0, 1 ) );

		$booking_id_for_meta = isset( $booking->booking_id ) ? $booking->booking_id : $booking->id;
		global $wpdb;
		$meta_table = $wpdb->prefix . 'tfhb_booking_meta';
		$recorded_start_time = $wpdb->get_var( $wpdb->prepare(
			"SELECT value FROM {$meta_table} WHERE booking_id = %d AND meta_key = 'hbc_meeting_started_at'",
			$booking_id_for_meta
		) );
		$recorded_start_time = $recorded_start_time ? (int) $recorded_start_time : 0;

		$meeting_date_display = date_i18n(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			strtotime( $booking->meeting_dates . ' ' . $booking->start_time )
		);
		$meeting_start_timestamp = strtotime( $booking->meeting_dates . ' ' . $booking->start_time ) * 1000;
		$meeting_end_timestamp = strtotime( $booking->meeting_dates . ' ' . $booking->end_time ) * 1000;
		$duration_ms = max( 0, $meeting_end_timestamp - $meeting_start_timestamp );
		$duration_hours = floor( $duration_ms / ( 1000 * 60 * 60 ) );
		$duration_mins_left = floor( ( $duration_ms % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 ) );
		
		$booked_duration_text = '';
		if ( $duration_hours > 0 ) {
			$booked_duration_text .= $duration_hours . 'h ';
		}
		if ( $duration_mins_left > 0 || $duration_hours === 0 ) {
			$booked_duration_text .= $duration_mins_left . 'm';
		}
		$booked_duration_text = trim( $booked_duration_text );

		?><!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( $booking->meeting_title ); ?> &mdash; <?php bloginfo( 'name' ); ?></title>
			<link rel="preconnect" href="https://fonts.googleapis.com">
			<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
			<?php wp_head(); ?>
			<style>
				*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
				html,body{height:100%;overflow:hidden}
				body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#e2e8f0}
				.hbc-meeting-header{display:flex;align-items:center;justify-content:space-between;padding:0 24px;height:60px;background:linear-gradient(135deg,#1e293b,#0f172a);border-bottom:1px solid rgba(255,255,255,.08);position:relative;z-index:100}
				.hbc-header-left{display:flex;align-items:center;gap:16px;min-width:0}
				.hbc-site-logo img{height:32px;width:auto;display:block}
				.hbc-site-logo-text{font-weight:700;font-size:18px;color:#38bdf8;white-space:nowrap;text-decoration:none}
				.hbc-header-divider{width:1px;height:28px;background:rgba(255,255,255,.12);flex-shrink:0}
				.hbc-meeting-info{min-width:0}
				.hbc-meeting-title-text{font-size:15px;font-weight:600;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:340px}
				.hbc-meeting-meta{font-size:12px;color:#94a3b8;margin-top:2px}
				.hbc-header-right{display:flex;align-items:center;gap:16px;flex-shrink:0}
				.hbc-user-badge{display:flex;align-items:center;gap:10px}
				.hbc-user-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;flex-shrink:0}
				.hbc-user-details{line-height:1.3}
				.hbc-user-name{font-size:13px;font-weight:600;color:#e2e8f0}
				.hbc-user-role{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;padding:1px 6px;border-radius:3px;display:inline-block}
				.hbc-user-role--host{background:rgba(34,197,94,.15);color:#4ade80}
				.hbc-user-role--attendee{background:rgba(56,189,248,.15);color:#38bdf8}
				.hbc-leave-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.25);border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;transition:all .2s ease;text-decoration:none}
				.hbc-leave-btn:hover{background:#ef4444;color:#fff;border-color:#ef4444}
				.hbc-meeting-timer{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.1);padding:6px 14px;border-radius:20px;font-variant-numeric:tabular-nums;font-family:monospace;font-size:14px;color:#f1f5f9;cursor:default;line-height:1}
				.hbc-meeting-timer.warning{color:#fbbf24;background:rgba(251,191,36,.1)}
				.hbc-meeting-timer.danger{color:#ef4444;background:rgba(239,68,68,.1)}
				#jitsi-container{width:100%;height:calc(100vh - 60px);position:relative;background:#0f172a}
				#jitsi-container iframe{border:none!important}
				.hbc-loading-overlay{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#0f172a;z-index:10;transition:opacity .4s ease}
				.hbc-loading-overlay.hidden{opacity:0;pointer-events:none}
				.hbc-loading-spinner{width:48px;height:48px;border:3px solid rgba(255,255,255,.1);border-top-color:#38bdf8;border-radius:50%;animation:hbc-spin .8s linear infinite}
				@keyframes hbc-spin{to{transform:rotate(360deg)}}
				.hbc-loading-text{margin-top:16px;font-size:14px;color:#94a3b8}
				.hbc-loading-subtitle{margin-top:4px;font-size:12px;color:#475569}
				@media(max-width:768px){
					.hbc-meeting-header{padding:0 12px;gap:8px}
					.hbc-meeting-title-text{max-width:140px;font-size:13px}
					.hbc-meeting-meta{display:none}
					.hbc-header-divider{display:none}
					.hbc-user-details{display:none}
					.hbc-leave-btn span{display:none}
				}
			</style>
		</head>
		<body>
			<header class="hbc-meeting-header">
				<div class="hbc-header-left">
					<?php if ( $logo_url ) : ?>
						<a href="<?php echo esc_url( home_url() ); ?>" class="hbc-site-logo" title="<?php echo esc_attr( $site_name ); ?>">
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>">
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( home_url() ); ?>" class="hbc-site-logo-text"><?php echo esc_html( $site_name ); ?></a>
					<?php endif; ?>
					<div class="hbc-header-divider"></div>
					<div class="hbc-meeting-info">
						<div class="hbc-meeting-title-text"><?php echo esc_html( $booking->meeting_title ); ?></div>
						<div class="hbc-meeting-meta"><?php echo esc_html( $meeting_date_display ); ?></div>
					</div>
				</div>
				<div class="hbc-header-right">
					<div id="hbc-meeting-timer" class="hbc-meeting-timer" style="display:none;">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
						<span id="hbc-timer-text">00:00:00</span>
					</div>
					<div class="hbc-user-badge">
						<div class="hbc-user-avatar"><?php echo esc_html( $initials ); ?></div>
						<div class="hbc-user-details">
							<div class="hbc-user-name"><?php echo esc_html( $display_name ); ?></div>
							<span class="hbc-user-role hbc-user-role--<?php echo esc_attr( $role ); ?>"><?php echo esc_html( $role_label ); ?></span>
						</div>
					</div>
					<button class="hbc-leave-btn" onclick="leaveMeeting()" title="<?php esc_attr_e( 'Leave Meeting', 'hydra-booking-customization' ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
						<span><?php esc_html_e( 'Leave', 'hydra-booking-customization' ); ?></span>
					</button>
				</div>
			</header>

			<div id="jitsi-container">
				<div class="hbc-loading-overlay" id="hbc-loading">
					<div class="hbc-loading-spinner"></div>
					<div class="hbc-loading-text"><?php esc_html_e( 'Connecting to meeting...', 'hydra-booking-customization' ); ?></div>
					<div class="hbc-loading-subtitle"><?php echo esc_html( $booking->meeting_title ); ?></div>
				</div>
			</div>

			<?php
			$jitsi_config = $this->jitsi->get_jitsi_config();
			if ( 'jaas' === $jitsi_config['api_select'] ) {
				echo '<script src="https://8x8.vc/external_api.js"></script>';
			} else {
				echo '<script src="https://' . esc_attr( $meeting_data['domain'] ) . '/external_api.js"></script>';
			}
			echo '<script src="' . esc_url( HBC_PLUGIN_URL . 'src/utils/toast-notifications.js' ) . '"></script>';
			?>

			<script>
				var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
				var hbc_nonce = '<?php echo esc_js( wp_create_nonce( 'hbc_meeting_nonce' ) ); ?>';
				var currentBookingId = <?php echo (int) $booking_id_for_meta; ?>;

				function leaveMeeting() {
					if (window.jitsiApi) { window.jitsiApi.dispose(); }
					if (window.opener) { window.close(); } else { window.location.href = '<?php echo esc_url( home_url() ); ?>'; }
				}

				document.addEventListener('DOMContentLoaded', function() {
					var attempts = 0;
					
					var startTimestamp = <?php echo (int) $meeting_start_timestamp; ?>;
					var endTimestamp = <?php echo (int) $meeting_end_timestamp; ?>;
					var recordedStartTime = <?php echo (int) $recorded_start_time; ?>;
					var bookedDurationMs = <?php echo (int) $duration_ms; ?>;
					var bookedText = '<?php echo esc_js( $booked_duration_text ); ?>';
					var userRole = '<?php echo esc_js( $role ); ?>';
					var isTestMode = <?php echo get_option( 'hbc_enable_test_mode', false ) ? 'true' : 'false'; ?>;
					var timerEl = document.getElementById('hbc-meeting-timer');
					var timerText = document.getElementById('hbc-timer-text');
					
					var meetingState = {
						hostJoined: (recordedStartTime > 0),
						liveStartTime: recordedStartTime,
						notified10: false,
						notified5: false,
						timerInterval: null
					};

					if (meetingState.hostJoined) {
						meetingState.timerInterval = setInterval(updateActualTimer, 1000);
						updateActualTimer();
					}

					function formatTime(ms) {
						if (ms < 0) ms = 0;
						var hours = Math.floor(ms / (1000 * 60 * 60));
						var mins = Math.floor((ms % (1000 * 60 * 60)) / (1000 * 60));
						var secs = Math.floor((ms % (1000 * 60)) / 1000);
						
						var display = (hours < 10 ? "0" + hours : hours) + "h " +
									  (mins < 10 ? "0" + mins : mins) + "m " +
									  (secs < 10 ? "0" + secs : secs) + "s";
						return display;
					}

					function updateActualTimer() {
						if (!meetingState.hostJoined) {
							timerText.innerText = "Waiting for Host";
							timerEl.style.display = 'flex';
							timerEl.classList.remove('warning', 'danger');
							return;
						}

						var now = new Date().getTime();
						var elapsed = now - meetingState.liveStartTime;
						var remaining = bookedDurationMs - elapsed;

						if (remaining <= 0) {
							timerText.innerText = "Booked: " + bookedText + " | Elapsed: " + formatTime(bookedDurationMs);
							if (window.ToastNotifications) {
								window.ToastNotifications.showError('<?php echo esc_js( __( "Meeting duration reached. Closing...", "hydra-booking-customization" ) ); ?>');
							}
							setTimeout(function() {
								leaveMeeting();
							}, 3000);
							clearInterval(meetingState.timerInterval);
							return;
						}

						timerText.innerText = "Booked: " + bookedText + " | Elapsed: " + formatTime(elapsed);
						timerEl.style.display = 'flex';

						// Notifications mapping logic
						if (remaining <= 300000) { // 5 mins left
							timerEl.classList.add('danger');
							timerEl.classList.remove('warning');
							if (!meetingState.notified5 && window.ToastNotifications) {
								window.ToastNotifications.showInfo('<?php echo esc_js( __( "5 minutes remaining in this meeting", "hydra-booking-customization" ) ); ?>');
								meetingState.notified5 = true;
							}
						} else if (remaining <= 600000) { // 10 mins left
							timerEl.classList.add('warning');
							if (!meetingState.notified10 && window.ToastNotifications) {
								window.ToastNotifications.showInfo('<?php echo esc_js( __( "10 minutes remaining in this meeting", "hydra-booking-customization" ) ); ?>');
								meetingState.notified10 = true;
							}
						}
					}
					
					function startLiveMeetingTimer() {
						if (meetingState.hostJoined) return;
						meetingState.hostJoined = true;

						// Post to server to save session start state
						var xhr = new XMLHttpRequest();
						xhr.open('POST', ajaxurl, true);
						xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
						xhr.onload = function() {
							if (xhr.status >= 200 && xhr.status < 400) {
								var resp = JSON.parse(xhr.responseText);
								if (resp.success && resp.data.started_at) {
									meetingState.liveStartTime = resp.data.started_at;
									if (!meetingState.timerInterval) {
										meetingState.timerInterval = setInterval(updateActualTimer, 1000);
										updateActualTimer();
									}
								}
							}
						};
						xhr.send('action=hbc_start_meeting&nonce=' + hbc_nonce + '&booking_id=' + currentBookingId);

						// Fallback local start if AJAX fails
						if (!meetingState.liveStartTime) {
							meetingState.liveStartTime = new Date().getTime();
							if (!meetingState.timerInterval) {
								meetingState.timerInterval = setInterval(updateActualTimer, 1000);
								updateActualTimer();
							}
						}
					}

					function waitForAPI() {
						if (typeof JitsiMeetExternalAPI !== 'undefined') {
							initJitsi();
						} else if (attempts < 15) {
							attempts++;
							setTimeout(waitForAPI, 500);
						} else {
							var el = document.getElementById('hbc-loading');
							if (el) {
								el.innerHTML = '<div style="text-align:center;padding:40px"><svg width="48" height="48" fill="none" stroke="#f87171" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><h3 style="color:#f1f5f9;margin:16px 0 8px">Unable to load meeting</h3><p style="color:#94a3b8;font-size:14px;margin-bottom:20px">Please try refreshing the page or check your connection.</p><button onclick="location.reload()" style="padding:10px 24px;background:#38bdf8;color:#0f172a;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:14px">Refresh Page</button></div>';
							}
						}
					}

					function initJitsi() {
						var domain = '<?php echo esc_js( $meeting_data['domain'] ); ?>';
						var roomName = '<?php echo esc_js( $meeting_data['room_name'] ); ?>';
						var displayName = '<?php echo esc_js( $display_name ); ?>';
						var userRole = '<?php echo esc_js( $role ); ?>';
						var meetingTitle = '<?php echo esc_js( $booking->meeting_title ); ?>';

						var options = {
							roomName: roomName,
							lang: 'en',
							width: '100%',
							height: '100%',
							parentNode: document.querySelector('#jitsi-container'),
							userInfo: { displayName: displayName },
							configOverwrite: {
								defaultLanguage: 'en',
								prejoinConfig: { enabled: false },
								subject: meetingTitle,
								startWithAudioMuted: (userRole !== 'host'),
								startWithVideoMuted: false,
								enableWelcomePage: false,
								disableDeepLinking: true,
								enableClosePage: false,
								hideConferenceSubject: false,
								hideConferenceTimer: true,
								hideParticipantsStats: true,
								enableInsecureRoomNameWarning: false,
								enableLobbyChat: false,
								channelLastN: -1,
								enableLayerSuspension: true,
								disableH264: false,
								constraints: { video: { aspectRatio: 16/9, height: { ideal: 720, max: 720, min: 240 } } },
								notifications: [],
								disableThirdPartyRequests: true
							},
							interfaceConfigOverwrite: {
								TOOLBAR_BUTTONS: ['microphone','camera','desktop','fullscreen','fodeviceselection','hangup','chat','raisehand','videoquality','filmstrip','tileview','settings','shortcuts','select-background'],
								SETTINGS_SECTIONS: ['devices','language','profile'],
								SHOW_JITSI_WATERMARK: false,
								SHOW_WATERMARK_FOR_GUESTS: false,
								SHOW_BRAND_WATERMARK: false,
								BRAND_WATERMARK_LINK: '',
								SHOW_POWERED_BY: false,
								SHOW_PROMOTIONAL_CLOSE_PAGE: false,
								SHOW_CHROME_EXTENSION_BANNER: false,
								DEFAULT_BACKGROUND: '#0f172a',
								DISABLE_JOIN_LEAVE_NOTIFICATIONS: false,
								HIDE_INVITE_MORE_HEADER: true,
								MOBILE_APP_PROMO: false
							}
						};

						if (userRole === 'host') {
							options.configOverwrite.startAudioMuted = 0;
							options.configOverwrite.startVideoMuted = 0;
						}

						try {
							var api = new JitsiMeetExternalAPI(domain, options);
							window.jitsiApi = api;

							// If host joins immediately, start the live timer tracking
							if (userRole === 'host') {
								startLiveMeetingTimer();
							}

							api.addEventListener('videoConferenceJoined', function(p) {
								var overlay = document.getElementById('hbc-loading');
								if (overlay) { overlay.classList.add('hidden'); setTimeout(function(){ overlay.remove(); }, 500); }
								if (window.ToastNotifications) { window.ToastNotifications.showSuccess('<?php echo esc_js( __( 'Successfully joined the meeting!', 'hydra-booking-customization' ) ); ?>'); }
								
								// Wait for Host state tracking logic
								if (userRole !== 'host' && !meetingState.hostJoined) {
									timerText.innerText = "Waiting for Host";
									timerEl.style.display = 'flex';
									
									// check if host might already be in room
									var participants = api.getParticipantsInfo();
									var isHostPresent = participants.some(function(participant) {
										// This is a naive check; ideally robust meeting data passes a unique host identifier or we check affiliation
										return participant.role === 'moderator' || participant.displayName && participant.displayName.toLowerCase().includes('host');
									});
									
									// For strict strict synchronization across clients without WebSockets,
									// we fall back to "if the meeting is active, act as if host initiated" 
									// but we can trust participant events.
								}
							});
							
							api.addEventListener('participantRoleChanged', function(event) {
								if (event.role === 'moderator') {
									startLiveMeetingTimer();
								}
							});

							api.addEventListener('readyToClose', function() { leaveMeeting(); });
							api.addEventListener('participantJoined', function(p) {
								if (window.ToastNotifications && p.displayName) { window.ToastNotifications.showInfo(p.displayName + ' <?php echo esc_js( __( 'joined the meeting', 'hydra-booking-customization' ) ); ?>'); }
								
								// If host joins, start timer. 
								// In Jitsi, hosts generally get promoted to moderator.
							});
							api.addEventListener('participantLeft', function() {
								if (window.ToastNotifications) { window.ToastNotifications.showInfo('<?php echo esc_js( __( 'A participant left the meeting', 'hydra-booking-customization' ) ); ?>'); }
							});
						} catch (error) {
							console.error('Jitsi initialization error:', error);
							var el = document.getElementById('hbc-loading');
							if (el) { el.innerHTML = '<div style="text-align:center;padding:40px;color:#f87171"><h3>Error initializing meeting</h3><p style="color:#94a3b8;margin-top:8px">Please refresh the page and try again.</p></div>'; }
						}
					}

					waitForAPI();
				});
			</script>

			<?php wp_footer(); ?>
		</body>
		</html><?php
	}
}
