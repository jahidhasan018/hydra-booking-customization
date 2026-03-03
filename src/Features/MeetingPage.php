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
	}

	/**
	 * Add rewrite rule for custom meeting page.
	 */
	public function add_rewrite_rule() {
		add_rewrite_rule(
			'^meeting/([^/]+)/?$',
			'index.php?hbc_meeting_token=$matches[1]',
			'top'
		);

		if ( ! get_option( 'hbc_rewrite_rules_added' ) ) {
			update_option( 'hbc_flush_rewrite_rules', true );
			update_option( 'hbc_rewrite_rules_added', true );
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
	 * Display meeting interface with embedded Jitsi.
	 *
	 * @param object $booking      Booking object.
	 * @param array  $meeting_data Meeting metadata.
	 * @param int    $user_id      Current user ID.
	 * @param string $role         User role (host|attendee).
	 */
	private function display_meeting_interface( $booking, $meeting_data, $user_id, $role ) {
		$user         = get_user_by( 'id', $user_id );
		$display_name = $user ? $user->display_name : 'Guest';

		?><!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( $booking->meeting_title ); ?> - <?php bloginfo( 'name' ); ?></title>
			<?php wp_head(); ?>
			<style>
				body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
				.meeting-header { background: #0073aa; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; }
				.meeting-title { margin: 0; font-size: 18px; }
				.user-info { font-size: 14px; }
				#jitsi-container { width: 100%; height: calc(100vh - 60px); }
				.loading { text-align: center; padding: 50px; }
			</style>
		</head>
		<body>
			<div class="meeting-header">
				<h1 class="meeting-title"><?php echo esc_html( $booking->meeting_title ); ?></h1>
				<div class="user-info">
					<?php
					printf(
						/* translators: %s: user display name */
						esc_html__( 'Welcome, %s', 'hydra-booking-customization' ),
						esc_html( $display_name )
					);
					?>
					<?php if ( 'host' === $role ) : ?>
						<span class="host-badge"><?php esc_html_e( '(Host)', 'hydra-booking-customization' ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			<div id="jitsi-container">
				<div class="loading"><?php esc_html_e( 'Loading meeting...', 'hydra-booking-customization' ); ?></div>
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
				document.addEventListener('DOMContentLoaded', function() {
					function checkBrowserCompatibility() {
						var userAgent = navigator.userAgent;
						var isChrome = /Chrome/.test(userAgent) && /Google Inc/.test(navigator.vendor);
						var isFirefox = /Firefox/.test(userAgent);
						var isSafari = /Safari/.test(userAgent) && /Apple Computer/.test(navigator.vendor);
						var isEdge = /Edg/.test(userAgent);
						var hasWebRTC = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
						var hasWebSocket = !!window.WebSocket;

						return {
							isSupported: (isChrome || isFirefox || isSafari || isEdge) && hasWebRTC && hasWebSocket,
							browser: isChrome ? 'Chrome' : isFirefox ? 'Firefox' : isSafari ? 'Safari' : isEdge ? 'Edge' : 'Unknown',
							hasWebRTC: hasWebRTC,
							hasWebSocket: hasWebSocket
						};
					}

					var browserInfo = checkBrowserCompatibility();
					var apiCheckAttempts = 0;
					var maxAttempts = 10;

					function waitForJitsiAPI() {
						if (typeof JitsiMeetExternalAPI !== 'undefined') {
							initializeJitsi();
						} else if (apiCheckAttempts < maxAttempts) {
							apiCheckAttempts++;
							setTimeout(waitForJitsiAPI, 500);
						} else {
							console.error('Jitsi Meet External API failed to load after multiple attempts');
							if (window.ToastNotifications) {
								window.ToastNotifications.showError('Failed to load meeting interface. Please refresh the page.');
							}
							showFallbackMessage();
						}
					}

					function showFallbackMessage() {
						var container = document.querySelector('#jitsi-container');
						container.innerHTML =
							'<div class="loading" style="padding: 40px; text-align: center;">' +
							'<h3>Unable to load meeting interface</h3>' +
							'<p>Please try one of the following:</p>' +
							'<ul style="text-align: left; display: inline-block;">' +
							'<li>Refresh this page</li>' +
							'<li>Check your internet connection</li>' +
							'<li>Try using a different browser (Chrome, Firefox, Safari, or Edge)</li>' +
							'<li>Disable browser extensions temporarily</li>' +
							'</ul>' +
							'<p><a href="<?php echo esc_url( $meeting_data['meeting_url'] ); ?>" target="_blank" class="button">Open in new tab</a></p>' +
							'<button onclick="location.reload()" class="button">Refresh Page</button>' +
							'</div>';
					}

					function initializeJitsi() {
						if (!browserInfo.isSupported) {
							console.warn('Browser may not be fully supported, but attempting to load Jitsi anyway');
						}

						var domain = '<?php echo esc_js( $meeting_data['domain'] ); ?>';
						var roomName = '<?php echo esc_js( $meeting_data['room_name'] ); ?>';
						var displayName = '<?php echo esc_js( $display_name ); ?>';
						var userRole = '<?php echo esc_js( $role ); ?>';

						var options = {
							roomName: roomName,
							width: '100%',
							height: '100%',
							parentNode: document.querySelector('#jitsi-container'),
							userInfo: {
								displayName: displayName
							},
							configOverwrite: {
								startWithAudioMuted: true,
								startWithVideoMuted: false,
								enableWelcomePage: false,
								prejoinPageEnabled: false,
								disableDeepLinking: true,
								enableClosePage: false,
								constraints: {
									video: {
										aspectRatio: 16 / 9,
										height: { ideal: 720, max: 720, min: 240 }
									}
								},
								disableH264: false,
								enableLayerSuspension: true,
								channelLastN: -1,
								enableInsecureRoomNameWarning: false,
								enableLobbyChat: false
							},
							interfaceConfigOverwrite: {
								TOOLBAR_BUTTONS: [
									'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
									'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
									'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
									'videoquality', 'filmstrip', 'invite', 'feedback', 'stats', 'shortcuts',
									'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone'
								],
								SETTINGS_SECTIONS: ['devices', 'language', 'moderator', 'profile', 'calendar'],
								SHOW_JITSI_WATERMARK: false,
								SHOW_WATERMARK_FOR_GUESTS: false,
								SHOW_BRAND_WATERMARK: false,
								BRAND_WATERMARK_LINK: '',
								SHOW_POWERED_BY: false,
								SHOW_PROMOTIONAL_CLOSE_PAGE: false,
								SHOW_CHROME_EXTENSION_BANNER: false
							}
						};

						if (userRole === 'host') {
							options.configOverwrite.startAudioMuted = 0;
							options.configOverwrite.startVideoMuted = 0;
						}

						try {
							var api = new JitsiMeetExternalAPI(domain, options);

							api.addEventListener('readyToClose', function() {
								if (window.opener) {
									window.close();
								} else {
									window.location.href = '<?php echo esc_url( home_url() ); ?>';
								}
							});

							api.addEventListener('participantLeft', function() {
								if (window.ToastNotifications) {
									window.ToastNotifications.showInfo('A participant left the meeting');
								}
							});

							api.addEventListener('participantJoined', function() {
								if (window.ToastNotifications) {
									window.ToastNotifications.showInfo('A participant joined the meeting');
								}
							});

							api.addEventListener('videoConferenceJoined', function() {
								var loadingDiv = document.querySelector('#jitsi-container .loading');
								if (loadingDiv) {
									loadingDiv.remove();
								}
								if (window.ToastNotifications) {
									window.ToastNotifications.showSuccess('Successfully joined the meeting!');
								}
							});

						} catch (error) {
							console.error('Error initializing Jitsi Meet:', error);
							if (window.ToastNotifications) {
								window.ToastNotifications.showError('Unable to initialize meeting. Please check your browser compatibility and try again.');
							}
							document.querySelector('#jitsi-container').innerHTML = '<div class="loading">Error: Unable to initialize meeting. Please check your browser compatibility and try again.</div>';
						}
					}

					waitForJitsiAPI();
				});
			</script>

			<?php wp_footer(); ?>
		</body>
		</html><?php
	}
}
