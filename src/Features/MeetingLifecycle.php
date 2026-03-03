<?php
/**
 * Meeting Lifecycle Management
 *
 * Handles cron jobs, reminders, and cleanup for Jitsi meetings.
 *
 * @package HydraBookingCustomization\Features
 * @since   1.1.0
 */

namespace HydraBookingCustomization\Features;

defined( 'ABSPATH' ) || exit;

/**
 * Meeting Lifecycle class.
 *
 * Manages the scheduled lifecycle events for meetings:
 * cron registration, reminder emails, expired meeting cleanup,
 * and room termination.
 *
 * @since 1.1.0
 */
class MeetingLifecycle {

	/**
	 * Reminder time before meeting ends (5 minutes in seconds).
	 */
	const REMINDER_TIME_BEFORE_END = 300;

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
		$this->init_cron_jobs();
	}

	/**
	 * Register lifecycle-specific hooks.
	 *
	 * @since 1.1.0
	 */
	private function init_hooks() {
		add_action( 'hbc_meeting_reminder', array( $this, 'send_meeting_reminder' ), 10, 2 );
		add_action( 'hbc_meeting_cleanup', array( $this, 'cleanup_expired_meeting' ), 10, 1 );
		add_action( 'hbc_meeting_terminate', array( $this, 'terminate_meeting_room' ), 10, 1 );
	}

	/**
	 * Initialize cron jobs for meeting management.
	 *
	 * @since 1.0.0
	 */
	private function init_cron_jobs() {
		if ( ! wp_next_scheduled( 'hbc_check_meeting_reminders' ) ) {
			wp_schedule_event( time(), 'every_minute', 'hbc_check_meeting_reminders' );
		}

		if ( ! wp_next_scheduled( 'hbc_cleanup_expired_meetings' ) ) {
			wp_schedule_event( time(), 'hourly', 'hbc_cleanup_expired_meetings' );
		}

		add_action( 'hbc_check_meeting_reminders', array( $this, 'check_meeting_reminders' ) );
		add_action( 'hbc_cleanup_expired_meetings', array( $this, 'cleanup_all_expired_meetings' ) );
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function add_cron_schedules( $schedules ) {
		$schedules['every_minute'] = array(
			'interval' => 60,
			'display'  => __( 'Every Minute', 'hydra-booking-customization' ),
		);
		return $schedules;
	}

	/**
	 * Check for meetings that need reminders.
	 *
	 * @since 1.0.0
	 */
	public function check_meeting_reminders() {
		global $wpdb;

		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		$meta_table     = $wpdb->prefix . 'tfhb_booking_meta';

		$current_time  = current_time( 'mysql' );
		$reminder_time = gmdate( 'Y-m-d H:i:s', strtotime( $current_time ) + self::REMINDER_TIME_BEFORE_END );

		$query = $wpdb->prepare(
			"SELECT b.id as booking_id, b.meeting_dates, b.start_time, b.end_time, b.attendee_email, b.attendee_name
			 FROM {$bookings_table} b
			 LEFT JOIN {$meta_table} m ON b.id = m.booking_id AND m.meta_key = 'reminder_sent'
			 WHERE b.status = 'confirmed'
			 AND CONCAT(b.meeting_dates, ' ', b.end_time) BETWEEN %s AND %s
			 AND m.booking_id IS NULL",
			$current_time,
			$reminder_time
		);

		$meetings = $wpdb->get_results( $query );

		foreach ( $meetings as $meeting ) {
			$this->send_meeting_reminder( $meeting->booking_id, $meeting );
		}
	}

	/**
	 * Send meeting reminder to participants.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param object $booking    Booking object.
	 * @since 1.0.0
	 */
	public function send_meeting_reminder( $booking_id, $booking ) {
		global $wpdb;

		$meta_table = $wpdb->prefix . 'tfhb_booking_meta';
		$wpdb->replace(
			$meta_table,
			array(
				'booking_id' => $booking_id,
				'meta_key'   => 'reminder_sent',
				'value'      => current_time( 'mysql' ),
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		$subject = __( 'Meeting Ending Soon - 5 Minutes Remaining', 'hydra-booking-customization' );
		$message = sprintf(
			/* translators: 1: attendee name, 2: meeting date, 3: start time, 4: end time */
			__( "Hello %1\$s,\n\nYour meeting is scheduled to end in 5 minutes.\n\nMeeting Details:\nDate: %2\$s\nTime: %3\$s - %4\$s\n\nPlease wrap up your discussion.\n\nThank you!", 'hydra-booking-customization' ),
			$booking->attendee_name,
			$booking->meeting_dates,
			$booking->start_time,
			$booking->end_time
		);

		wp_mail( $booking->attendee_email, $subject, $message );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "HBC MeetingLifecycle: Sent 5-minute reminder for booking {$booking_id}" );
		}
	}

	/**
	 * Cleanup all expired meetings.
	 *
	 * @since 1.0.0
	 */
	public function cleanup_all_expired_meetings() {
		global $wpdb;

		$bookings_table = $wpdb->prefix . 'tfhb_bookings';
		$current_time   = current_time( 'mysql' );

		$query = $wpdb->prepare(
			"SELECT id as booking_id FROM {$bookings_table}
			 WHERE status = 'confirmed'
			 AND CONCAT(meeting_dates, ' ', end_time) < %s",
			$current_time
		);

		$expired_meetings = $wpdb->get_results( $query );

		foreach ( $expired_meetings as $meeting ) {
			$this->cleanup_expired_meeting( $meeting->booking_id );
		}
	}

	/**
	 * Cleanup expired meeting data.
	 *
	 * @param int $booking_id Booking ID.
	 * @since 1.0.0
	 */
	public function cleanup_expired_meeting( $booking_id ) {
		global $wpdb;

		$meta_table   = $wpdb->prefix . 'tfhb_booking_meta';
		$meeting_data = $this->jitsi->get_meeting_data( $booking_id );

		if ( $meeting_data ) {
			$meeting_data['terminated']    = true;
			$meeting_data['terminated_at'] = current_time( 'mysql' );

			$wpdb->replace(
				$meta_table,
				array(
					'booking_id' => $booking_id,
					'meta_key'   => 'jitsi_meeting',
					'value'      => wp_json_encode( $meeting_data ),
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s', '%s' )
			);
		}

		$wpdb->replace(
			$meta_table,
			array(
				'booking_id' => $booking_id,
				'meta_key'   => 'meeting_terminated',
				'value'      => current_time( 'mysql' ),
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$meta_table} WHERE booking_id = %d AND meta_key LIKE 'meeting_token_%%'",
			$booking_id
		) );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "HBC MeetingLifecycle: Cleaned up expired meeting {$booking_id}" );
		}
	}

	/**
	 * Terminate meeting room.
	 *
	 * Placeholder for future Jitsi API integration.
	 *
	 * @param int $booking_id Booking ID.
	 * @since 1.0.0
	 */
	public function terminate_meeting_room( $booking_id ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "HBC MeetingLifecycle: Meeting room terminated for booking {$booking_id}" );
		}
		$this->cleanup_expired_meeting( $booking_id );
	}

	/**
	 * Schedule meeting events (reminders and cleanup) for a specific booking.
	 *
	 * @param object $booking Booking object.
	 * @since 1.0.0
	 */
	public function schedule_meeting_events( $booking ) {
		$meeting_start = strtotime( $booking->meeting_dates . ' ' . $booking->start_time );
		$meeting_end   = strtotime( $booking->meeting_dates . ' ' . $booking->end_time );

		$reminder_time = $meeting_end - self::REMINDER_TIME_BEFORE_END;
		if ( $reminder_time > time() ) {
			wp_schedule_single_event( $reminder_time, 'hbc_meeting_reminder', array( $booking->booking_id, $booking ) );
		}

		if ( $meeting_end > time() ) {
			wp_schedule_single_event( $meeting_end + 60, 'hbc_meeting_cleanup', array( $booking->booking_id ) );
		}

		if ( $meeting_end > time() ) {
			wp_schedule_single_event( $meeting_end, 'hbc_meeting_terminate', array( $booking->booking_id ) );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'HBC MeetingLifecycle: Scheduled events for booking %d - reminder at %s, cleanup at %s',
				$booking->booking_id,
				date( 'Y-m-d H:i:s', $reminder_time ),
				date( 'Y-m-d H:i:s', $meeting_end + 60 )
			) );
		}
	}
}
