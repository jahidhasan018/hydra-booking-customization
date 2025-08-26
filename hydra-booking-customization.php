<?php
/**
 * Plugin Name: Hydra Booking Customization
 * Plugin URI: https://github.com/your-username/hydra-booking-customization
 * Description: Extends Hydra Booking with Jitsi Meet integration, automatic attendee registration, and enhanced dashboard functionality.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hydra-booking-customization
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * Update URI: false
 *
 * @package HydraBookingCustomization
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access denied.' );
}

// Define plugin constants.
if ( ! defined( 'HBC_VERSION' ) ) {
	define( 'HBC_VERSION', '1.0.0' );
}
if ( ! defined( 'HBC_PLUGIN_FILE' ) ) {
	define( 'HBC_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'HBC_PLUGIN_DIR' ) ) {
	define( 'HBC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'HBC_PLUGIN_URL' ) ) {
	define( 'HBC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'HBC_PLUGIN_BASENAME' ) ) {
	define( 'HBC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'HBC_MIN_PHP_VERSION' ) ) {
	define( 'HBC_MIN_PHP_VERSION', '7.4' );
}
if ( ! defined( 'HBC_MIN_WP_VERSION' ) ) {
	define( 'HBC_MIN_WP_VERSION', '5.0' );
}
if ( ! defined( 'HBC_TEST_MODE_STATUS' ) ) {
	$testing_mode = (bool) get_option( 'hbc_jitsi_testing_mode', false );
	define( 'HBC_TEST_MODE_STATUS', $testing_mode ? 'active' : 'off' );
}

/**
 * Check system requirements before initializing the plugin.
 *
 * @since 1.0.0
 * @return bool True if requirements are met, false otherwise.
 */
function hbc_check_requirements() {
	global $wp_version;
	
	// Check PHP version.
	if ( version_compare( PHP_VERSION, HBC_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'hbc_php_version_notice' );
		return false;
	}
	
	// Check WordPress version.
	if ( version_compare( $wp_version, HBC_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'hbc_wp_version_notice' );
		return false;
	}
	
	// Check if Hydra Booking plugin is active.
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	
	if ( ! is_plugin_active( 'hydra-booking/hydra-booking.php' ) ) {
		add_action( 'admin_notices', 'hbc_hydra_booking_missing_notice' );
		return false;
	}
	
	return true;
}

// Early requirements check.
if ( ! hbc_check_requirements() ) {
	return;
}

/**
 * Display admin notice if PHP version is insufficient.
 *
 * @since 1.0.0
 */
function hbc_php_version_notice() {
	$message = sprintf(
		/* translators: 1: Required PHP version, 2: Current PHP version */
		esc_html__( 'Hydra Booking Customization requires PHP version %1$s or higher. You are running version %2$s. Please upgrade PHP.', 'hydra-booking-customization' ),
		HBC_MIN_PHP_VERSION,
		PHP_VERSION
	);
	printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
}

/**
 * Display admin notice if WordPress version is insufficient.
 *
 * @since 1.0.0
 */
function hbc_wp_version_notice() {
	global $wp_version;
	$message = sprintf(
		/* translators: 1: Required WordPress version, 2: Current WordPress version */
		esc_html__( 'Hydra Booking Customization requires WordPress version %1$s or higher. You are running version %2$s. Please upgrade WordPress.', 'hydra-booking-customization' ),
		HBC_MIN_WP_VERSION,
		$wp_version
	);
	printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
}

/**
 * Display admin notice if Hydra Booking plugin is not active.
 *
 * @since 1.0.0
 */
function hbc_hydra_booking_missing_notice() {
	$message = esc_html__( 'Hydra Booking Customization requires the Hydra Booking plugin to be installed and activated.', 'hydra-booking-customization' );
	$install_url = wp_nonce_url(
		self_admin_url( 'update.php?action=install-plugin&plugin=hydra-booking' ),
		'install-plugin_hydra-booking'
	);
	
	printf(
		'<div class="notice notice-error"><p>%s <a href="%s" class="button button-primary">%s</a></p></div>',
		esc_html( $message ),
		esc_url( $install_url ),
		esc_html__( 'Install Hydra Booking', 'hydra-booking-customization' )
	);
}

/**
 * Load Composer autoloader safely.
 *
 * @since 1.0.0
 * @return bool True if autoloader was loaded successfully, false otherwise.
 */
function hbc_load_autoloader() {
	$autoloader_path = HBC_PLUGIN_DIR . 'vendor/autoload.php';
	
	if ( file_exists( $autoloader_path ) && is_readable( $autoloader_path ) ) {
		require_once $autoloader_path;
		return true;
	}
	
	add_action( 'admin_notices', 'hbc_autoloader_missing_notice' );
	return false;
}

/**
 * Display admin notice if Composer autoloader is missing.
 *
 * @since 1.0.0
 */
function hbc_autoloader_missing_notice() {
	$message = esc_html__( 'Hydra Booking Customization: Composer autoloader not found. Please run "composer install" in the plugin directory.', 'hydra-booking-customization' );
	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}

// Load Composer autoloader.
if ( ! hbc_load_autoloader() ) {
	return;
}

// Load debug admin page (temporary for debugging)
if ( is_admin() ) {
	require_once HBC_PLUGIN_DIR . 'debug-attendees-admin.php';
	require_once HBC_PLUGIN_DIR . 'fix-attendee-user-ids.php';
}

// Initialize the plugin.
add_action( 'plugins_loaded', 'hbc_init_plugin', 10 );

/**
  * Initialize Vue.js dashboard hooks.
  *
  * @since 1.0.0
  */
 function hbc_init_vue_dashboard_hooks() {
 	// Register Vue.js dashboard shortcodes
 	add_shortcode('hbc_attendee_dashboard', 'hbc_render_vue_attendee_dashboard');
 	add_shortcode('hbc_host_dashboard', 'hbc_render_vue_host_dashboard');
 	add_shortcode('vue_attendee_dashboard', 'hbc_render_vue_attendee_dashboard'); // Legacy support
 	add_shortcode('vue_host_dashboard', 'hbc_render_vue_host_dashboard'); // Legacy support
 }
 add_action('init', 'hbc_init_vue_dashboard_hooks');
 
 // Initialize login redirect hooks
 add_action('init', 'hbc_init_login_redirect_hooks');
 
 /**
  * Initialize AJAX handlers.
  *
  * @since 1.0.0
  */
 function hbc_init_ajax_handlers() {
	// AJAX logout handler
	add_action('wp_ajax_hbc_logout', 'hbc_ajax_logout');
	add_action('wp_ajax_nopriv_hbc_logout', 'hbc_ajax_logout');
}
 add_action('init', 'hbc_init_ajax_handlers');
 
 /**
  * Initialize login redirect hooks.
  *
  * @since 1.0.0
  */
 function hbc_init_login_redirect_hooks() {
	// Hook into login redirect filter
	add_filter('login_redirect', 'hbc_handle_login_redirect', 10, 3);
}
 add_action('init', 'hbc_init_login_redirect_hooks');
 
 /**
  * Handle login redirect based on user role.
  *
  * @param string $redirect_to The redirect destination URL.
  * @param string $requested_redirect_to The requested redirect destination URL passed as a parameter.
  * @param WP_User|WP_Error $user WP_User object if login was successful, WP_Error object otherwise.
  * @return string The redirect URL.
  * @since 1.0.0
  */
 function hbc_handle_login_redirect($redirect_to, $requested_redirect_to, $user) {
	// Only proceed if login was successful and we have a valid user
	if (is_wp_error($user) || !is_a($user, 'WP_User')) {
		return $redirect_to;
	}
 
	// Security check: Ensure user exists and is active
	if (!$user->exists() || !$user->ID) {
		return $redirect_to;
	}
 
	// Don't override if there's already a specific redirect requested (except admin_url)
	if (!empty($requested_redirect_to) && $requested_redirect_to !== admin_url()) {
		// Additional security: validate the requested redirect URL is safe
		if (hbc_is_safe_redirect_url($requested_redirect_to)) {
			return $redirect_to;
		}
	}
 
	// Get user's primary role using our AccessControl class
	$user_role = \HydraBookingCustomization\Core\AccessControl::get_user_primary_role($user->ID);
 
	// Security check: Ensure we have a valid role
	if (empty($user_role)) {
		// For users without specific roles, redirect to profile or home
		return $user->has_cap('read') ? admin_url('profile.php') : home_url();
	}
 
	// Redirect based on role with additional security validation
	switch ($user_role) {
		case 'hbc_attendee':
			// Double-check attendee access permission
			if (!\HydraBookingCustomization\Core\AccessControl::can_access_attendee_dashboard($user->ID)) {
				return home_url(); // Fallback to home if access denied
			}
			return home_url('/user-dashboard');
 
		case 'tfhb_host':
			// Double-check host access permission
			if (!\HydraBookingCustomization\Core\AccessControl::can_access_host_dashboard($user->ID)) {
				return home_url(); // Fallback to home if access denied
			}
			return home_url('/host-dashboard');
 
		case 'admin':
			// Admins go to admin dashboard unless specifically requested otherwise
			if (empty($requested_redirect_to) || $requested_redirect_to === admin_url()) {
				return admin_url();
			}
			break;
 
		default:
			// For users without specific roles, use safe default
			return $user->has_cap('read') ? admin_url('profile.php') : home_url();
	}
 
	// Return original redirect if no specific handling is needed
	return $redirect_to;
}
 
 /**
  * Check if a redirect URL is safe to use.
  *
  * @param string $url The URL to validate.
  * @return bool True if URL is safe, false otherwise.
  * @since 1.0.0
  */
 function hbc_is_safe_redirect_url($url) {
	// Use WordPress built-in function for URL validation
	if (!wp_validate_redirect($url)) {
		return false;
	}
 
	// Additional checks for our specific use case
	$parsed_url = wp_parse_url($url);
	if (!$parsed_url) {
		return false;
	}
 
	// Ensure it's a local URL or explicitly allowed external URL
	if (isset($parsed_url['host'])) {
		$site_host = wp_parse_url(home_url(), PHP_URL_HOST);
		if ($parsed_url['host'] !== $site_host) {
			// Allow only explicitly whitelisted external domains
			$allowed_hosts = apply_filters('hbc_allowed_redirect_hosts', array());
			if (!in_array($parsed_url['host'], $allowed_hosts, true)) {
				return false;
			}
		}
	}
 
	return true;
}
 
 /**
  * Handle AJAX logout request.
  *
  * @since 1.0.0
  */
 function hbc_ajax_logout() {
	// Verify nonce
	if (!wp_verify_nonce($_POST['nonce'], 'hbc_logout_nonce')) {
		wp_send_json_error(array('message' => __('Security check failed', 'hydra-booking-customization')));
	}
 
 	// Log out the user
 	wp_logout();
 
 	// Send success response with login URL
 	wp_send_json_success(array(
 		'message' => __('Logged out successfully', 'hydra-booking-customization'),
 		'login_url' => wp_login_url(home_url())
 	));
 }
 
 /**
  * Render login form for non-logged-in users.
  *
  * @param string $dashboard_type The type of dashboard (attendee or host)
  * @return string
  * @since 1.0.0
  */
 function hbc_render_login_form($dashboard_type = 'attendee') {
 	// Don't redirect back to current page to avoid loops
 	$login_url = wp_login_url();
 	$dashboard_label = ($dashboard_type === 'host') ? __('Host Dashboard', 'hydra-booking-customization') : __('Attendee Dashboard', 'hydra-booking-customization');
 	
 	return sprintf(
 		'<div class="hbc-login-required" style="text-align: center; padding: 40px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; margin: 20px 0;">
 			<h3 style="color: #333; margin-bottom: 15px;">%s</h3>
 			<p style="color: #666; margin-bottom: 20px;">%s</p>
 			<a href="%s" class="button button-primary" style="padding: 10px 20px; text-decoration: none;">%s</a>
 		</div>',
 		__('Login Required', 'hydra-booking-customization'),
 		sprintf(__('Please log in to access your %s.', 'hydra-booking-customization'), strtolower($dashboard_label)),
 		esc_url($login_url),
 		__('Login', 'hydra-booking-customization')
 	);
 }
 
 /**
  * Render Vue.js attendee dashboard shortcode.
  *
  * @since 1.0.0
  */
 function hbc_render_vue_attendee_dashboard($atts) {
 	$atts = shortcode_atts([], $atts, 'hbc_attendee_dashboard');
 	
 	// Check if user is logged in
 	if (!is_user_logged_in()) {
 		return hbc_render_login_form('attendee');
 	}
 	
 	// Use centralized access control
 	if (!\HydraBookingCustomization\Core\AccessControl::can_access_attendee_dashboard()) {
 		return \HydraBookingCustomization\Core\AccessControl::get_attendee_access_denied_message();
 	}
 	
 	ob_start();
 	include plugin_dir_path(__FILE__) . 'templates/vue-attendee-dashboard.php';
 	return ob_get_clean();
 }
 
 /**
  * Render Vue.js host dashboard shortcode.
  *
  * @since 1.0.0
  */
 function hbc_render_vue_host_dashboard($atts) {
 	$atts = shortcode_atts([], $atts, 'hbc_host_dashboard');
 	
 	// Check if user is logged in
 	if (!is_user_logged_in()) {
 		return hbc_render_login_form('host');
 	}
 	
 	// Use centralized access control
 	if (!\HydraBookingCustomization\Core\AccessControl::can_access_host_dashboard()) {
 		return \HydraBookingCustomization\Core\AccessControl::get_host_access_denied_message();
 	}
 	
 	ob_start();
 	include plugin_dir_path(__FILE__) . 'templates/vue-host-dashboard.php';
 	return ob_get_clean();
 }

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function hbc_init_plugin() {
	// Load text domain for internationalization.
	load_plugin_textdomain( 
		'hydra-booking-customization', 
		false, 
		dirname( HBC_PLUGIN_BASENAME ) . '/languages' 
	);
	
	// Check if the main plugin class exists.
	if ( ! class_exists( 'HydraBookingCustomization\Core\Plugin' ) ) {
		add_action( 'admin_notices', 'hbc_plugin_class_missing_notice' );
		return;
	}
	
	// Initialize the main plugin class.
	try {
		HydraBookingCustomization\Core\Plugin::get_instance();
	} catch ( Exception $e ) {
		add_action( 'admin_notices', function() use ( $e ) {
			hbc_plugin_init_error_notice( $e->getMessage() );
		} );
	}
}

/**
 * Display admin notice if plugin class is missing.
 *
 * @since 1.0.0
 */
function hbc_plugin_class_missing_notice() {
	$message = esc_html__( 'Hydra Booking Customization: Main plugin class not found. Please check the plugin installation.', 'hydra-booking-customization' );
	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}

/**
 * Display admin notice for plugin initialization errors.
 *
 * @since 1.0.0
 * @param string $error_message The error message to display.
 */
function hbc_plugin_init_error_notice( $error_message ) {
	$message = sprintf(
		/* translators: %s: Error message */
		esc_html__( 'Hydra Booking Customization failed to initialize: %s', 'hydra-booking-customization' ),
		esc_html( $error_message )
	);
	printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
}

// Activation hook.
register_activation_hook( __FILE__, 'hbc_activate_plugin' );

/**
 * Plugin activation callback.
 *
 * @since 1.0.0
 */
function hbc_activate_plugin() {
	// Check requirements again during activation.
	if ( ! hbc_check_requirements() ) {
		wp_die(
			esc_html__( 'Hydra Booking Customization cannot be activated due to unmet requirements.', 'hydra-booking-customization' ),
			esc_html__( 'Plugin Activation Error', 'hydra-booking-customization' ),
			array( 'back_link' => true )
		);
	}
	
	// Create attendee role with proper capabilities.
	hbc_create_attendee_role();
	
	// Create attendee dashboard page.
	hbc_create_dashboard_page();
	
	// Create host dashboard page.
	hbc_create_host_dashboard_page();
	
	// Set default options.
	hbc_set_default_options();
	
	// Flush rewrite rules.
	flush_rewrite_rules();
	
	// Set activation flag for welcome notice.
	set_transient( 'hbc_activation_notice', true, 30 );
}

/**
 * Create the attendee user role.
 *
 * @since 1.0.0
 */
function hbc_create_attendee_role() {
	if ( ! get_role( 'hbc_attendee' ) ) {
		$capabilities = array(
			'read'                   => true,
			'edit_posts'             => false,
			'delete_posts'           => false,
			'publish_posts'          => false,
			'upload_files'           => false,
			'edit_published_posts'   => false,
			'delete_published_posts' => false,
			'edit_others_posts'      => false,
			'delete_others_posts'    => false,
			'manage_categories'      => false,
		);
		
		add_role(
			'hbc_attendee',
			__( 'Attendee', 'hydra-booking-customization' ),
			$capabilities
		);
	}
}

/**
 * Create the attendee dashboard page.
 *
 * @since 1.0.0
 */
function hbc_create_dashboard_page() {
	// Check if page already exists.
	$existing_page_id = get_option( 'hbc_attendee_dashboard_page_id' );
	if ( $existing_page_id && get_post( $existing_page_id ) ) {
		return;
	}
	
	$page_data = array(
		'post_title'     => __( 'Attendee Dashboard', 'hydra-booking-customization' ),
		'post_content'   => '[hbc_attendee_dashboard]',
		'post_status'    => 'publish',
		'post_type'      => 'page',
		'post_name'      => 'attendee-dashboard',
		'post_author'    => get_current_user_id(),
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'meta_input'     => array(
			'_hbc_dashboard_page' => true,
		),
	);
	
	$page_id = wp_insert_post( $page_data, true );
	
	if ( ! is_wp_error( $page_id ) ) {
		update_option( 'hbc_attendee_dashboard_page_id', $page_id );
	}
}

/**
 * Create the host dashboard page.
 *
 * @since 1.0.0
 */
function hbc_create_host_dashboard_page() {
	// Check if page already exists.
	$existing_page_id = get_option( 'hbc_host_dashboard_page_id' );
	if ( $existing_page_id && get_post( $existing_page_id ) ) {
		return;
	}
	
	$page_data = array(
		'post_title'     => __( 'Host Dashboard', 'hydra-booking-customization' ),
		'post_content'   => '[hbc_host_dashboard]',
		'post_status'    => 'publish',
		'post_type'      => 'page',
		'post_name'      => 'host-dashboard',
		'post_author'    => get_current_user_id(),
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'meta_input'     => array(
			'_hbc_dashboard_page' => true,
		),
	);
	
	$page_id = wp_insert_post( $page_data, true );
	
	if ( ! is_wp_error( $page_id ) ) {
		update_option( 'hbc_host_dashboard_page_id', $page_id );
	}
}

/**
 * Set default plugin options.
 *
 * @since 1.0.0
 */
function hbc_set_default_options() {
	$default_options = array(
		'hbc_jitsi_domain'           => 'meet.jit.si',
		'hbc_auto_registration'      => 'yes',
		'hbc_email_notifications'    => 'yes',
		'hbc_meeting_grace_period'   => 15,
		'hbc_token_expiration'       => 3600,
		'hbc_debug_mode'             => 'no',
	);
	
	foreach ( $default_options as $option_name => $default_value ) {
		if ( false === get_option( $option_name ) ) {
			add_option( $option_name, $default_value );
		}
	}
}

// Deactivation hook.
register_deactivation_hook( __FILE__, 'hbc_deactivate_plugin' );

/**
 * Plugin deactivation callback.
 *
 * @since 1.0.0
 */
function hbc_deactivate_plugin() {
	// Clear scheduled events.
	wp_clear_scheduled_hook( 'hbc_cleanup_expired_tokens' );
	wp_clear_scheduled_hook( 'hbc_send_meeting_reminders' );
	
	// Clear transients.
	delete_transient( 'hbc_activation_notice' );
	
	// Flush rewrite rules.
	flush_rewrite_rules();
}

// Uninstall hook (only if uninstall.php doesn't exist).
if ( ! file_exists( HBC_PLUGIN_DIR . 'uninstall.php' ) ) {
	register_uninstall_hook( __FILE__, 'hbc_uninstall_plugin' );
}

/**
 * Plugin uninstall callback.
 *
 * @since 1.0.0
 */
function hbc_uninstall_plugin() {
	// Remove custom role.
	remove_role( 'hbc_attendee' );
	
	// Remove dashboard pages.
	$attendee_page_id = get_option( 'hbc_attendee_dashboard_page_id' );
	if ( $attendee_page_id ) {
		wp_delete_post( $attendee_page_id, true );
	}
	
	$host_page_id = get_option( 'hbc_host_dashboard_page_id' );
	if ( $host_page_id ) {
		wp_delete_post( $host_page_id, true );
	}
	
	// Remove plugin options.
	$options_to_remove = array(
		'hbc_attendee_dashboard_page_id',
		'hbc_host_dashboard_page_id',
		'hbc_jitsi_domain',
		'hbc_auto_registration',
		'hbc_email_notifications',
		'hbc_meeting_grace_period',
		'hbc_token_expiration',
		'hbc_debug_mode',
	);
	
	foreach ( $options_to_remove as $option ) {
		delete_option( $option );
	}
	
	// Remove user meta.
	delete_metadata( 'user', 0, 'hbc_attendee_bookings', '', true );
	delete_metadata( 'user', 0, 'hbc_meeting_preferences', '', true );
	
	// Clear any remaining transients.
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hbc_%' OR option_name LIKE '_transient_timeout_hbc_%'" );
}