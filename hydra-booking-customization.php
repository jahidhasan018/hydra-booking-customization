<?php
/**
 * Plugin Name: Hydra Booking Customization
 * Plugin URI: https://github.com/jahid018/hydra-booking-customization
 * Description: Extends Hydra Booking with Jitsi Meet video integration, auto-registration, host & attendee dashboards, and transient-based caching.
 * Version: 1.1.0
 * Author: Jahid
 * Author URI: https://github.com/jahid018
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hydra-booking-customization
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.7
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

// Initialize the plugin.
add_action( 'plugins_loaded', 'hbc_init_plugin', 10 );

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
	
	// Clear the transient so the user_id column check re-runs.
	delete_transient( 'hbc_user_id_column_checked' );

	// Create attendee role with proper capabilities.
	hbc_create_attendee_role();
	
	// Create attendee dashboard page.
	hbc_create_dashboard_page();
	
	// Set default options.
	hbc_set_default_options();
	
	// Flush rewrite rules.
	flush_rewrite_rules();

	// Flush all plugin caches.
	HydraBookingCustomization\Core\CacheManager::flush_all();
	
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
	delete_transient( 'hbc_user_id_column_checked' );

	// Flush all plugin caches.
	HydraBookingCustomization\Core\CacheManager::flush_all();
	
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
	
	// Remove dashboard page.
	$page_id = get_option( 'hbc_attendee_dashboard_page_id' );
	if ( $page_id ) {
		wp_delete_post( $page_id, true );
	}
	
	// Remove plugin options.
	$options_to_remove = array(
		'hbc_attendee_dashboard_page_id',
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