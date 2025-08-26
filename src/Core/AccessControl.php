<?php
/**
 * Access Control Helper
 *
 * @package HydraBookingCustomization\Core
 */

namespace HydraBookingCustomization\Core;

/**
 * Access Control Helper Class
 * 
 * Provides centralized role-based access control methods for the plugin.
 */
class AccessControl {

	/**
	 * Attendee role name
	 */
	const ATTENDEE_ROLE = 'hbc_attendee';

	/**
	 * Host role name
	 */
	const HOST_ROLE = 'tfhb_host';

	/**
	 * Check if current user has attendee access.
	 *
	 * @param int|null $user_id Optional user ID. Defaults to current user.
	 * @return bool True if user has attendee access, false otherwise.
	 */
	public static function can_access_attendee_dashboard( $user_id = null ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( $user_id === null ) {
			$user_id = get_current_user_id();
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		// Allow administrators and users with attendee role
		return current_user_can( 'manage_options' ) || in_array( self::ATTENDEE_ROLE, $user->roles, true );
	}

	/**
	 * Check if current user has host access.
	 *
	 * @param int|null $user_id Optional user ID. Defaults to current user.
	 * @return bool True if user has host access, false otherwise.
	 */
	public static function can_access_host_dashboard( $user_id = null ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( $user_id === null ) {
			$user_id = get_current_user_id();
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		// Allow administrators and users with host role
		return current_user_can( 'manage_options' ) || in_array( self::HOST_ROLE, $user->roles, true );
	}

	/**
	 * Verify attendee access for AJAX requests.
	 * 
	 * Dies with error if access is denied.
	 *
	 * @param int|null $user_id Optional user ID. Defaults to current user.
	 * @return void
	 */
	public static function verify_attendee_ajax_access( $user_id = null ) {
		if ( ! self::can_access_attendee_dashboard( $user_id ) ) {
			wp_die( 
				__( 'Access denied. This function is for attendees only.', 'hydra-booking-customization' ), 
				403 
			);
		}
	}

	/**
	 * Verify host access for AJAX requests.
	 * 
	 * Dies with error if access is denied.
	 *
	 * @param int|null $user_id Optional user ID. Defaults to current user.
	 * @return void
	 */
	public static function verify_host_ajax_access( $user_id = null ) {
		if ( ! self::can_access_host_dashboard( $user_id ) ) {
			wp_die( 
				__( 'Access denied. This function is for hosts only.', 'hydra-booking-customization' ), 
				403 
			);
		}
	}

	/**
	 * Get access denied message for attendee dashboard.
	 *
	 * @return string HTML error message.
	 */
	public static function get_attendee_access_denied_message() {
		return '<div class="hbc-error alert alert-danger" style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; margin: 20px 0;">' . 
			   '<strong>' . __( 'Access Denied', 'hydra-booking-customization' ) . '</strong><br>' .
			   __( 'This dashboard is exclusively for users with attendee privileges. Please contact an administrator if you believe this is an error.', 'hydra-booking-customization' ) . 
			   '</div>';
	}

	/**
	 * Get access denied message for host dashboard.
	 *
	 * @return string HTML error message.
	 */
	public static function get_host_access_denied_message() {
		return '<div class="hbc-error alert alert-danger" style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; margin: 20px 0;">' . 
			   '<strong>' . __( 'Access Denied', 'hydra-booking-customization' ) . '</strong><br>' .
			   __( 'This dashboard is exclusively for users with host privileges. Please contact an administrator if you believe this is an error.', 'hydra-booking-customization' ) . 
			   '</div>';
	}

	/**
	 * Check if user has specific role.
	 *
	 * @param string   $role    Role to check.
	 * @param int|null $user_id Optional user ID. Defaults to current user.
	 * @return bool True if user has the role, false otherwise.
	 */
	public static function user_has_role( $role, $user_id = null ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( $user_id === null ) {
			$user_id = get_current_user_id();
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		return in_array( $role, $user->roles, true );
	}

	/**
	 * Get user's primary role for this plugin (attendee or host).
	 *
	 * @param int|null $user_id Optional user ID. Defaults to current user.
	 * @return string|null 'attendee', 'host', 'admin', or null if no relevant role.
	 */
	public static function get_user_primary_role( $user_id = null ) {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return 'admin';
		}

		if ( self::user_has_role( self::HOST_ROLE, $user_id ) ) {
			return 'host';
		}

		if ( self::user_has_role( self::ATTENDEE_ROLE, $user_id ) ) {
			return 'attendee';
		}

		return null;
	}
}