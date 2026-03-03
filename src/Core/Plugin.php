<?php
/**
 * Main Plugin Class
 *
 * @package HydraBookingCustomization\Core
 */

namespace HydraBookingCustomization\Core;

use HydraBookingCustomization\Features\AutoRegistration;
use HydraBookingCustomization\Features\AttendeeDashboard;
use HydraBookingCustomization\Features\HostDashboard;

use HydraBookingCustomization\Admin\Settings;
use HydraBookingCustomization\Features\JitsiIntegration;

/**
 * Main Plugin Class
 */
class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Auto registration feature.
	 *
	 * @var AutoRegistration
	 */
	private $auto_registration;

	/**
	 * Attendee dashboard feature.
	 *
	 * @var AttendeeDashboard
	 */
	private $attendee_dashboard;

	/**
	 * Host dashboard feature.
	 *
	 * @var HostDashboard
	 */
	private $host_dashboard;

	/**
	 * Settings page.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Jitsi integration feature.
	 *
	 * @var JitsiIntegration
	 */
	private $jitsi_integration;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
		$this->init_features();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Register Vue.js dashboard shortcodes.
	 *
	 * @since 1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'hbc_attendee_dashboard', array( $this, 'render_attendee_dashboard' ) );
		add_shortcode( 'hbc_host_dashboard', array( $this, 'render_host_dashboard' ) );

		// Legacy shortcode aliases.
		add_shortcode( 'vue_attendee_dashboard', array( $this, 'render_attendee_dashboard' ) );
		add_shortcode( 'vue_host_dashboard', array( $this, 'render_host_dashboard' ) );
	}

	/**
	 * Render login form for non-logged-in users.
	 *
	 * @since 1.0.0
	 * @param string $dashboard_type The type of dashboard ('attendee' or 'host').
	 * @return string Login form HTML.
	 */
	private function render_login_form( $dashboard_type = 'attendee' ) {
		$login_url       = wp_login_url();
		$dashboard_label = ( 'host' === $dashboard_type )
			? __( 'Host Dashboard', 'hydra-booking-customization' )
			: __( 'Attendee Dashboard', 'hydra-booking-customization' );

		return sprintf(
			'<div class="hbc-login-required" style="text-align: center; padding: 40px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; margin: 20px 0;">
				<h3 style="color: #333; margin-bottom: 15px;">%s</h3>
				<p style="color: #666; margin-bottom: 20px;">%s</p>
				<a href="%s" class="button button-primary" style="padding: 10px 20px; text-decoration: none;">%s</a>
			</div>',
			esc_html__( 'Login Required', 'hydra-booking-customization' ),
			/* translators: %s: Dashboard type label */
			sprintf( esc_html__( 'Please log in to access your %s.', 'hydra-booking-customization' ), esc_html( strtolower( $dashboard_label ) ) ),
			esc_url( $login_url ),
			esc_html__( 'Login', 'hydra-booking-customization' )
		);
	}

	/**
	 * Render Vue.js attendee dashboard shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Dashboard HTML.
	 */
	public function render_attendee_dashboard( $atts ) {
		$atts = shortcode_atts( array(), $atts, 'hbc_attendee_dashboard' );

		if ( ! is_user_logged_in() ) {
			return $this->render_login_form( 'attendee' );
		}

		$current_user = wp_get_current_user();

		if ( ! in_array( 'hbc_attendee', $current_user->roles, true ) && ! current_user_can( 'manage_options' ) ) {
			return '<div class="hbc-error alert alert-danger">' . esc_html__( 'Access denied. This dashboard is for attendees only.', 'hydra-booking-customization' ) . '</div>';
		}

		ob_start();
		include HBC_PLUGIN_DIR . 'templates/vue-attendee-dashboard.php';
		return ob_get_clean();
	}

	/**
	 * Render Vue.js host dashboard shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Dashboard HTML.
	 */
	public function render_host_dashboard( $atts ) {
		$atts = shortcode_atts( array(), $atts, 'hbc_host_dashboard' );

		if ( ! is_user_logged_in() ) {
			return $this->render_login_form( 'host' );
		}

		$current_user = wp_get_current_user();

		if ( ! in_array( 'tfhb_host', $current_user->roles, true ) && ! current_user_can( 'manage_options' ) ) {
			return '<div class="hbc-error alert alert-danger">' . esc_html__( 'Access denied. This dashboard is for hosts only.', 'hydra-booking-customization' ) . '</div>';
		}

		ob_start();
		include HBC_PLUGIN_DIR . 'templates/vue-host-dashboard.php';
		return ob_get_clean();
	}

	/**
	 * Initialize features.
	 */
	private function init_features() {
		$this->auto_registration  = new AutoRegistration();
		$this->attendee_dashboard = new AttendeeDashboard();
		$this->host_dashboard     = new HostDashboard();

		$this->settings          = new Settings();
		$this->jitsi_integration = new JitsiIntegration();
	}

	/**
	 * Initialize plugin on the 'init' action.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Handle Jitsi meeting link creation for existing bookings (admin only).
		if ( isset( $_GET['hbc_create_jitsi_links'] ) && current_user_can( 'manage_options' ) ) {
			$this->handle_create_jitsi_links();
		}
		
		// Flush rewrite rules if needed.
		if ( get_option( 'hbc_flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
			delete_option( 'hbc_flush_rewrite_rules' );
		}
	}

	/**
	 * Handle creation of Jitsi meeting links for existing bookings.
	 *
	 * @since 1.0.0
	 */
	private function handle_create_jitsi_links() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'hbc_create_jitsi_links' ) ) {
			wp_die(
				esc_html__( 'Security check failed.', 'hydra-booking-customization' ),
				esc_html__( 'Error', 'hydra-booking-customization' ),
				array( 'back_link' => true )
			);
		}

		$created_count = $this->jitsi_integration->create_missing_meeting_links();

		if ( $created_count > 0 ) {
			wp_die(
				sprintf(
					/* translators: 1: Number of bookings, 2: Admin URL link */
					esc_html__( 'Successfully created Jitsi meeting links for %1$d bookings. %2$s', 'hydra-booking-customization' ),
					$created_count,
					'<a href="' . esc_url( admin_url() ) . '">' . esc_html__( 'Back to Admin', 'hydra-booking-customization' ) . '</a>'
				)
			);
		} else {
			wp_die(
				sprintf(
					/* translators: %s: Admin URL link */
					esc_html__( 'No bookings found that need Jitsi meeting links. %s', 'hydra-booking-customization' ),
					'<a href="' . esc_url( admin_url() ) . '">' . esc_html__( 'Back to Admin', 'hydra-booking-customization' ) . '</a>'
				)
			);
		}
	}

	/**
	 * Enqueue frontend scripts and styles.
	 * 
	 * Note: Dashboard assets are now handled by Vue.js shortcode templates.
	 * This method is kept for future frontend assets that may be needed.
	 */
	public function enqueue_scripts() {
		// Enqueue toast notification styles globally for all frontend pages
		wp_enqueue_style(
			'hbc-toast-notifications',
			HBC_PLUGIN_URL . 'assets/css/toast-notifications.css',
			array(),
			HBC_VERSION
		);
		
		// Dashboard assets are now handled by shortcode templates
		// Keep this method for any future global frontend assets
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		if ( 'settings_page_hbc-settings' === $hook_suffix ) {
			wp_enqueue_style(
				'hbc-admin',
				HBC_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				HBC_VERSION
			);

			wp_enqueue_script(
				'hbc-admin',
				HBC_PLUGIN_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				HBC_VERSION,
				true
			);
		}
	}

	/**
	 * Check if current page is attendee dashboard.
	 *
	 * @return bool
	 */
	private function is_attendee_dashboard_page() {
		$dashboard_page_id = get_option( 'hbc_attendee_dashboard_page_id' );
		return is_page( $dashboard_page_id );
	}

	/**
	 * Check if current page is host dashboard.
	 *
	 * @return bool
	 */
	private function is_host_dashboard_page() {
		$dashboard_page_id = get_option( 'hbc_host_dashboard_page_id' );
		return is_page( $dashboard_page_id );
	}

	/**
	 * Get auto registration feature instance.
	 *
	 * @return AutoRegistration
	 */
	public function get_auto_registration() {
		return $this->auto_registration;
	}

	/**
	 * Get attendee dashboard feature instance.
	 *
	 * @return AttendeeDashboard
	 */
	public function get_attendee_dashboard() {
		return $this->attendee_dashboard;
	}

	/**
	 * Get host dashboard feature instance.
	 *
	 * @return HostDashboard
	 */
	public function get_host_dashboard() {
		return $this->host_dashboard;
	}

	/**
	 * Get settings instance.
	 *
	 * @return Settings
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get Jitsi integration instance.
	 *
	 * @return JitsiIntegration
	 */
	public function get_jitsi_integration() {
		return $this->jitsi_integration;
	}
}