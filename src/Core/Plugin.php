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
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
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
	 * Initialize plugin.
	 */
	public function init() {
		// Handle Jitsi meeting link creation for existing bookings
		if ( isset( $_GET['hbc_create_jitsi_links'] ) && current_user_can( 'manage_options' ) ) {
			$this->handle_create_jitsi_links();
		}
		
		// Flush rewrite rules if needed
		if ( get_option( 'hbc_flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
			delete_option( 'hbc_flush_rewrite_rules' );
		}
	}

	/**
	 * Handle creation of Jitsi meeting links for existing bookings.
	 */
	private function handle_create_jitsi_links() {
		// For now, skip nonce verification for easier testing
		// if ( ! wp_verify_nonce( $_GET['nonce'] ?? '', 'hbc_create_jitsi_links' ) ) {
		// 	wp_die( 'Invalid nonce' );
		// }

		$created_count = $this->jitsi_integration->create_missing_meeting_links();
		
		if ( $created_count > 0 ) {
			wp_die( "Successfully created Jitsi meeting links for {$created_count} bookings. <a href='" . admin_url() . "'>Back to Admin</a>" );
		} else {
			wp_die( "No bookings found that need Jitsi meeting links. <a href='" . admin_url() . "'>Back to Admin</a>" );
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
		
		// Enqueue public.js script on single meeting page
		wp_enqueue_script(
			'hbc-public',
			HBC_PLUGIN_URL . 'assets/js/public.js',
			array( 'jquery' ),
			HBC_VERSION,
			true
		);

		// User info
		if(is_user_logged_in()){
			$user = wp_get_current_user();
			$user_email = $user->user_email;
			$username = $user->user_login;
			wp_localize_script( 'hbc-public', 'hbc_user_info', array(
				'email' => $user_email,
				'username' => $username,
			));
		}
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