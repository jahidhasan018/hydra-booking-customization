<?php
/**
 * Meeting REST API
 *
 * REST API endpoints for Jitsi meeting link management.
 *
 * @package HydraBookingCustomization\Features
 * @since   1.1.0
 */

namespace HydraBookingCustomization\Features;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Meeting REST API class.
 *
 * Registers and handles REST API routes for retrieving
 * meeting links with proper authentication and validation.
 *
 * @since 1.1.0
 */
class MeetingRestApi {

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
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_routes() {
		register_rest_route( 'hydra-booking/v1', '/jitsi/meeting-link/(?P<booking_id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_meeting_link' ),
			'permission_callback' => array( $this, 'permission_check' ),
			'args'                => array(
				'booking_id' => array(
					'required'          => true,
					'validate_callback' => function ( $param ) {
						return is_numeric( $param );
					},
				),
			),
		) );
	}

	/**
	 * REST API permission check.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return bool|WP_Error
	 */
	public function permission_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You must be logged in to access meeting links.', 'hydra-booking-customization' ),
				array( 'status' => 401 )
			);
		}

		$booking_id = (int) $request['booking_id'];
		$user_id    = get_current_user_id();

		if ( ! $this->jitsi->user_has_booking_access( $user_id, $booking_id ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this meeting.', 'hydra-booking-customization' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * REST API endpoint to get meeting link.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function get_meeting_link( $request ) {
		$booking_id = (int) $request['booking_id'];
		$user_id    = get_current_user_id();

		try {
			$booking = $this->jitsi->get_booking_data( $booking_id );
			if ( ! $booking ) {
				return new WP_Error( 'booking_not_found', __( 'Booking not found.', 'hydra-booking-customization' ), array( 'status' => 404 ) );
			}

			if ( $booking->status !== 'confirmed' ) {
				return new WP_Error( 'booking_not_confirmed', __( 'Meeting is only available for confirmed bookings.', 'hydra-booking-customization' ), array( 'status' => 400 ) );
			}

			$meeting_data = $this->jitsi->get_meeting_data( $booking_id );
			if ( ! $meeting_data ) {
				return new WP_Error( 'meeting_not_found', __( 'Meeting link not found for this booking.', 'hydra-booking-customization' ), array( 'status' => 404 ) );
			}

			$meeting_start = strtotime( $booking->meeting_dates . ' ' . $booking->start_time );
			$meeting_end   = strtotime( $booking->meeting_dates . ' ' . $booking->end_time );

			$user_role          = $this->jitsi->get_user_role_in_meeting( $booking_id, $user_id );
			$secure_meeting_url = $this->jitsi->generate_secure_meeting_url( $booking_id, $user_id, $user_role );

			if ( ! $secure_meeting_url ) {
				return new WP_Error( 'meeting_link_generation_failed', __( 'Failed to generate meeting link.', 'hydra-booking-customization' ), array( 'status' => 500 ) );
			}

			return rest_ensure_response( array(
				'status'        => true,
				'meeting_url'   => $secure_meeting_url,
				'room_name'     => $meeting_data['room_name'],
				'role'          => $user_role,
				'booking_id'    => $booking_id,
				'meeting_start' => $meeting_start,
				'meeting_end'   => $meeting_end,
			) );

		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Jitsi REST API Error: ' . $e->getMessage() );
			}
			return new WP_Error( 'internal_error', __( 'An internal error occurred.', 'hydra-booking-customization' ), array( 'status' => 500 ) );
		}
	}
}
